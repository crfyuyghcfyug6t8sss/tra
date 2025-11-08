<?php
// تحميل نظام الترجمة
require_once 'includes/translator.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../config/database.php';
require_once '../includes/functions.php';

// معالجة طلبات AJAX
if (isset($_POST['ajax']) && $_POST['ajax'] == '1') {
    header('Content-Type: application/json; charset=utf-8');
    ob_clean();
    
    try {
        $errors = [];
        
        // جمع البيانات وتنظيفها
        $username = isset($_POST['username']) ? clean($_POST['username']) : '';
        $email = isset($_POST['email']) ? clean($_POST['email']) : '';
        $phone = isset($_POST['phone']) ? clean($_POST['phone']) : '';
        $country = isset($_POST['country']) ? clean($_POST['country']) : '';
        $password = isset($_POST['password']) ? $_POST['password'] : '';
        $confirm_password = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';
        
        // التحقق من المدخلات
        if (empty($username)) {
            $errors[] = 'اسم المستخدم مطلوب';
        } elseif (strlen($username) < 3) {
            $errors[] = 'اسم المستخدم يجب أن يكون 3 أحرف على الأقل';
        } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
            $errors[] = 'اسم المستخدم يجب أن يحتوي على حروف وأرقام فقط';
        }
        
        if (empty($email)) {
            $errors[] = 'البريد الإلكتروني مطلوب';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'البريد الإلكتروني غير صالح';
        }
        
        if (empty($phone)) {
            $errors[] = 'رقم الهاتف مطلوب';
        } elseif (!preg_match('/^[0-9+\-\s()]+$/', $phone)) {
            $errors[] = 'رقم الهاتف غير صحيح';
        }
        
        if (empty($country)) {
            $errors[] = 'يرجى اختيار البلد';
        }
        
        if (empty($password)) {
            $errors[] = 'كلمة المرور مطلوبة';
        } elseif (strlen($password) < 6) {
            $errors[] = 'كلمة المرور يجب أن تكون 6 أحرف على الأقل';
        }
        
        if (empty($confirm_password)) {
            $errors[] = 'تأكيد كلمة المرور مطلوب';
        }
        
        if (!empty($password) && !empty($confirm_password) && $password !== $confirm_password) {
            $errors[] = 'كلمات المرور غير متطابقة';
        }
        
        // إذا كانت هناك أخطاء، أرجعها
        if (!empty($errors)) {
            echo json_encode([
                'success' => false,
                'errors' => $errors
            ]);
            exit;
        }
        
        // التحقق من عدم وجود المستخدم مسبقاً
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        
        if ($stmt->rowCount() > 0) {
            echo json_encode([
                'success' => false,
                'errors' => ['اسم المستخدم أو البريد الإلكتروني مستخدم مسبقاً']
            ]);
            exit;
        }
        
        // تشفير كلمة المرور
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        // إدراج المستخدم الجديد
        $stmt = $conn->prepare("
            INSERT INTO users (username, email, phone, country, password, role, status, email_verified) 
            VALUES (?, ?, ?, ?, ?, 'user', 'active', FALSE)
        ");
        $stmt->execute([$username, $email, $phone, $country, $hashed_password]);
        
        $user_id = $conn->lastInsertId();
        
        // إنشاء محفظة للمستخدم
        $stmt = $conn->prepare("INSERT INTO wallet (user_id, available_balance, frozen_balance) VALUES (?, 0, 0)");
        $stmt->execute([$user_id]);
        
        // توليد رمز التحقق من البريد
        $verification_token = bin2hex(random_bytes(32));
        $expires_at = date('Y-m-d H:i:s', strtotime('+24 hours'));
        
        $stmt = $conn->prepare("
            INSERT INTO email_verifications (user_id, token, expires_at) 
            VALUES (?, ?, ?)
        ");
        $stmt->execute([$user_id, $verification_token, $expires_at]);
        
        // بناء رابط التحقق
        $verification_link = 'https://bidora.de/auth/verify-email.php?token=' . $verification_token;
        
        // بناء نص البريد الإلكتروني
        $emailBody = "
        <!DOCTYPE html>
        <html dir='rtl' lang='ar'>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <style>
                * { margin: 0; padding: 0; box-sizing: border-box; }
                body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f5f5f5; direction: rtl; padding: 20px; }
                .container { background: white; padding: 40px; border-radius: 12px; max-width: 600px; margin: 0 auto; box-shadow: 0 4px 20px rgba(0,0,0,0.1); }
                .header { text-align: center; margin-bottom: 30px; padding-bottom: 20px; border-bottom: 3px solid #6366f1; }
                .header h1 { color: #6366f1; font-size: 2rem; margin-bottom: 10px; }
                .header p { color: #999; font-size: 0.9rem; }
                .content { line-height: 1.8; color: #333; font-size: 1rem; }
                .content p { margin-bottom: 15px; }
                .button-container { text-align: center; margin: 30px 0; }
                .btn { display: inline-block; background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%); color: white; padding: 15px 40px; text-decoration: none; border-radius: 8px; font-weight: 600; font-size: 1.1rem; transition: all 0.3s; }
                .btn:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(99, 102, 241, 0.4); }
                .link-text { background: #f9f9f9; padding: 15px; border-radius: 8px; word-break: break-all; color: #6366f1; font-size: 0.9rem; margin: 20px 0; border: 1px solid #e2e8f0; }
                .warning { background: #fff3cd; border-right: 4px solid #ffc107; padding: 15px; margin: 20px 0; border-radius: 6px; color: #856404; }
                .warning strong { display: block; margin-bottom: 5px; }
                .footer { text-align: center; color: #999; font-size: 0.85rem; margin-top: 40px; padding-top: 20px; border-top: 1px solid #eee; }
                .footer p { margin: 5px 0; }
                .icon { font-size: 3rem; margin-bottom: 20px; }
            </style>
        
    <!-- Responsive & Mobile Menu CSS -->
    <link rel="stylesheet" href="assets/css/responsive.css">

</head>
        <body>
            <div class='container'>
                <div class='header'>
                    <div class='icon'>🚗</div>
                    <h1>مزادات السيارات</h1>
                    <p>تحقق من بريدك الإلكتروني</p>
                </div>
                
                <div class='content'>
                    <p>مرحباً <strong>" . htmlspecialchars($username) . "</strong>،</p>
                    
                    <p>شكراً لتسجيلك معنا في منصة مزادات السيارات! 🎉</p>
                    
                    <p>لإكمال تفعيل حسابك، يرجى التحقق من بريدك الإلكتروني بالضغط على الزر أدناه:</p>
                    
                    <div class='button-container'>
                        <a href='" . $verification_link . "' class='btn'>تحقق من البريد الإلكتروني</a>
                    </div>
                    
                    <p>أو انسخ الرابط التالي والصقه في المتصفح:</p>
                    <div class='link-text'>" . htmlspecialchars($verification_link) . "</div>
                    
                    <div class='warning'>
                        <strong>⏱️ هام:</strong>
                        صلاحية رابط التحقق 24 ساعة فقط. تأكد من التحقق قبل انتهاء الصلاحية.
                    </div>
                    
                    <p>إذا لم تقم بإنشاء هذا الحساب، يرجى تجاهل هذا البريد.</p>
                </div>
                
                <div class='footer'>
                    <p>© 2024 مزادات السيارات. جميع الحقوق محفوظة.</p>
                    <p>إذا كان لديك أي استفسارات، تواصل معنا على: <a href='mailto:support@bidora.de' style='color: #6366f1;'>support@bidora.de</a></p>
                </div>
            </div>
        
    <!-- Mobile Menu & Responsive JavaScript -->
    <script src="assets/js/mobile-menu.js"></script>

</body>
        </html>
        ";
        
        // إرسال البريد الإلكتروني
        require_once '../includes/send-email.php';
        $email_sent = sendEmailViaSMTP($email, 'تحقق من بريدك الإلكتروني - مزادات السيارات', $emailBody);
        
        // الرد على العميل
        if ($email_sent) {
            echo json_encode([
                'success' => true,
                'message' => 'تم التسجيل بنجاح! تحقق من بريدك الإلكتروني لتفعيل الحساب.',
                'redirect' => '../verify-pending.php'
            ]);
        } else {
            echo json_encode([
                'success' => true,
                'message' => 'تم التسجيل بنجاح! لكن حدث خطأ في إرسال البريد. يمكنك إعادة الإرسال لاحقاً.',
                'redirect' => '../verify-pending.php'
            ]);
        }
        exit;
        
    } catch(PDOException $e) {
        error_log("Registration DB Error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'errors' => ['حدث خطأ في قاعدة البيانات. يرجى المحاولة لاحقاً']
        ]);
        exit;
    } catch(Exception $e) {
        error_log("Registration Error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'errors' => ['حدث خطأ: ' . $e->getMessage()]
        ]);
        exit;
    }
}

// إذا كان المستخدم مسجل دخول بالفعل
if (isLoggedIn()) {
    redirect('../dashboard.php');
}

// قائمة الدول
$countries = [
    'SY' => 'سوريا',
    'AT' => 'النمسا',
    'BE' => 'بلجيكا',
    'BG' => 'بلغاريا',
    'HR' => 'كرواتيا',
    'CY' => 'قبرص',
    'CZ' => 'التشيك',
    'DK' => 'الدنمارك',
    'EE' => 'إستونيا',
    'FI' => 'فنلندا',
    'FR' => 'فرنسا',
    'DE' => 'ألمانيا',
    'GR' => 'اليونان',
    'HU' => 'المجر',
    'IE' => 'أيرلندا',
    'IT' => 'إيطاليا',
    'LV' => 'لاتفيا',
    'LT' => 'ليتوانيا',
    'LU' => 'لوكسمبورغ',
    'MT' => 'مالطا',
    'NL' => 'هولندا',
    'PL' => 'بولندا',
    'PT' => 'البرتغال',
    'RO' => 'رومانيا',
    'SK' => 'سلوفاكيا',
    'SI' => 'سلوفينيا',
    'ES' => 'إسبانيا',
    'SE' => 'السويد'
];
?>
<!DOCTYPE html>
<html lang="<?php echo currentLang(); ?>" dir="<?php echo textDirection(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>التسجيل - مزادات السيارات</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap');
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Cairo', sans-serif;
            background: #0f172a;
            min-height: 100vh;
            display: grid;
            place-items: center;
            padding: 20px;
            direction: rtl;
            overflow-x: hidden;
        }
        
        .particles {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            overflow: hidden;
            pointer-events: none;
            z-index: 0;
        }
        
        .particle {
            position: absolute;
            width: 3px;
            height: 3px;
            background: rgba(99, 102, 241, 0.5);
            border-radius: 50%;
            animation: float linear infinite;
        }
        
        @keyframes float {
            0% {
                transform: translateY(100vh) rotate(0deg);
                opacity: 0;
            }
            10% {
                opacity: 1;
            }
            90% {
                opacity: 1;
            }
            100% {
                transform: translateY(-100vh) rotate(360deg);
                opacity: 0;
            }
        }
        
        .container {
            position: relative;
            z-index: 1;
            width: 100%;
            max-width: 480px;
        }
        
        .card {
            background: rgba(30, 41, 59, 0.9);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(148, 163, 184, 0.1);
            border-radius: 24px;
            padding: 40px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
            animation: slideUp 0.6s cubic-bezier(0.16, 1, 0.3, 1);
        }
        
        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(40px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .brand {
            text-align: center;
            margin-bottom: 40px;
        }
        
        .brand-icon {
            width: 70px;
            height: 70px;
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
            border-radius: 16px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 20px;
            box-shadow: 0 8px 24px rgba(99, 102, 241, 0.4);
        }
        
        .brand-icon i {
            font-size: 2rem;
            color: white;
        }
        
        .brand h1 {
            font-size: 1.8rem;
            font-weight: 700;
            color: #f1f5f9;
            margin-bottom: 8px;
        }
        
        .brand p {
            color: #94a3b8;
            font-size: 0.95rem;
        }
        
        .alert {
            padding: 16px 20px;
            border-radius: 12px;
            margin-bottom: 24px;
            display: flex;
            align-items: start;
            gap: 12px;
            animation: shake 0.5s;
        }
        
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-10px); }
            75% { transform: translateX(10px); }
        }
        
        .alert-danger {
            background: rgba(239, 68, 68, 0.15);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #fca5a5;
        }
        
        .alert-success {
            background: rgba(34, 197, 94, 0.15);
            border: 1px solid rgba(34, 197, 94, 0.3);
            color: #86efac;
        }
        
        .alert i {
            font-size: 1.2rem;
            margin-top: 2px;
            flex-shrink: 0;
        }
        
        .alert ul {
            list-style: none;
            padding: 0;
        }
        
        .alert li {
            margin-bottom: 6px;
        }
        
        .alert li:before {
            content: "•";
            margin-left: 8px;
        }
        
        .form-group {
            margin-bottom: 24px;
        }
        
        .form-label {
            display: block;
            color: #cbd5e1;
            font-size: 0.9rem;
            font-weight: 600;
            margin-bottom: 10px;
        }
        
        .input-group {
            position: relative;
        }
        
        .input-group i {
            position: absolute;
            right: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: #64748b;
            font-size: 1.1rem;
            transition: color 0.3s;
        }
        
        .form-control {
            width: 100%;
            padding: 14px 48px 14px 16px;
            background: rgba(15, 23, 42, 0.5);
            border: 2px solid rgba(148, 163, 184, 0.2);
            border-radius: 12px;
            color: #f1f5f9;
            font-size: 1rem;
            font-family: 'Cairo', sans-serif;
            transition: all 0.3s;
        }
        
        .form-control:focus {
            outline: none;
            border-color: #6366f1;
            background: rgba(15, 23, 42, 0.8);
            box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1);
        }
        
        .form-control:focus + i {
            color: #6366f1;
        }
        
        .form-control::placeholder {
            color: #64748b;
        }
        
        select.form-control {
            cursor: pointer;
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='%2364748b' viewBox='0 0 16 16'%3E%3Cpath d='M7.247 11.14L2.451 5.658C1.885 5.013 2.345 4 3.204 4h9.592a1 1 0 0 1 .753 1.659l-4.796 5.48a1 1 0 0 1-1.506 0z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: left 16px center;
            padding-left: 48px;
        }
        
        select.form-control option {
            background: #1e293b;
            color: #f1f5f9;
        }
        
        .btn {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
            border: none;
            border-radius: 12px;
            color: white;
            font-size: 1.05rem;
            font-weight: 600;
            font-family: 'Cairo', sans-serif;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            box-shadow: 0 4px 16px rgba(99, 102, 241, 0.4);
        }
        
        .btn:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(99, 102, 241, 0.5);
        }
        
        .btn:active:not(:disabled) {
            transform: translateY(0);
        }
        
        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }
        
        .spinner {
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255, 255, 255, 0.3);
            border-top-color: white;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        .divider {
            text-align: center;
            margin: 32px 0;
            position: relative;
        }
        
        .divider:before {
            content: '';
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            height: 1px;
            background: rgba(148, 163, 184, 0.2);
        }
        
        .divider span {
            position: relative;
            background: rgba(30, 41, 59, 0.9);
            padding: 0 16px;
            color: #64748b;
            font-size: 0.9rem;
        }
        
        .footer-link {
            text-align: center;
            color: #94a3b8;
            font-size: 0.95rem;
        }
        
        .footer-link a {
            color: #6366f1;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s;
        }
        
        .footer-link a:hover {
            color: #8b5cf6;
        }
        
        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: #64748b;
            text-decoration: none;
            font-size: 0.9rem;
            margin-top: 20px;
            transition: all 0.3s;
        }
        
        .back-btn:hover {
            color: #94a3b8;
            gap: 12px;
        }
        
        @media (max-width: 520px) {
            .card {
                padding: 30px 24px;
            }
            
            .brand h1 {
                font-size: 1.5rem;
            }
            
            .form-control {
                padding: 12px 44px 12px 14px;
            }
        }
    </style>

    <!-- Responsive & Mobile Menu CSS -->
    <link rel="stylesheet" href="assets/css/responsive.css">

</head>
<body>
                                    <div style="position: fixed; top: 20px; left: 20px; z-index: 1000;">
        <?php include '../includes/lang-switcher.php'; ?>
    </div>
    <script src="../js/auto-translate.js"></script>

    <div class="particles" id="particles"></div>
    
    <div class="container">
        <div class="card">
            <div class="brand">
                <div class="brand-icon">
                    <i class="fas fa-car-side"></i>
                </div>
                <h1>مزادات السيارات</h1>
                <p>إنشاء حساب جديد</p>
            </div>
            
            <div id="alertContainer"></div>
            
            <form id="registerForm">
                <input type="hidden" name="ajax" value="1">
                
                <div class="form-group">
                    <label class="form-label">اسم المستخدم</label>
                    <div class="input-group">
                        <input 
                            type="text" 
                            name="username" 
                            class="form-control"
                            placeholder="اختر اسم مستخدم فريد"
                            required
                            minlength="3"
                            pattern="[a-zA-Z0-9_]+"
                        >
                        <i class="fas fa-user"></i>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">البريد الإلكتروني</label>
                    <div class="input-group">
                        <input 
                            type="email" 
                            name="email" 
                            class="form-control"
                            placeholder="example@email.com"
                            required
                        >
                        <i class="fas fa-envelope"></i>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">رقم الهاتف</label>
                    <div class="input-group">
                        <input 
                            type="text" 
                            name="phone" 
                            class="form-control"
                            placeholder="+963 XXX XXX XXX"
                            required
                        >
                        <i class="fas fa-phone"></i>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">البلد</label>
                    <div class="input-group">
                        <select name="country" class="form-control" required>
                            <option value="">اختر البلد</option>
                            <?php foreach ($countries as $code => $name): ?>
                                <option value="<?php echo htmlspecialchars($code); ?>"><?php echo htmlspecialchars($name); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <i class="fas fa-globe"></i>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">كلمة المرور</label>
                    <div class="input-group">
                        <input 
                            type="password" 
                            name="password" 
                            class="form-control"
                            placeholder="6 أحرف على الأقل"
                            required
                            minlength="6"
                        >
                        <i class="fas fa-lock"></i>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">تأكيد كلمة المرور</label>
                    <div class="input-group">
                        <input 
                            type="password" 
                            name="confirm_password" 
                            class="form-control"
                            placeholder="أعد إدخال كلمة المرور"
                            required
                        >
                        <i class="fas fa-lock"></i>
                    </div>
                </div>
                
                <button type="submit" class="btn" id="submitBtn">
                    <i class="fas fa-rocket"></i>
                    إنشاء حساب
                </button>
            </form>
            
            <div class="divider">
                <span>أو</span>
            </div>
            
            <div class="footer-link">
                لديك حساب بالفعل؟ <a href="login.php">تسجيل الدخول</a>
            </div>
            
            <div style="text-align: center;">
                <a href="../index.php" class="back-btn">
                    <i class="fas fa-arrow-right"></i>
                    العودة للرئيسية
                </a>
            </div>
        </div>
    </div>

    <script>
        // إنشاء الجزيئات المتحركة
        function createParticles() {
            const container = document.getElementById('particles');
            for (let i = 0; i < 50; i++) {
                const particle = document.createElement('div');
                particle.className = 'particle';
                particle.style.left = Math.random() * 100 + '%';
                particle.style.animationDuration = (Math.random() * 10 + 10) + 's';
                particle.style.animationDelay = Math.random() * 5 + 's';
                container.appendChild(particle);
            }
        }
        createParticles();
        
        // معالجة إرسال النموذج
        document.getElementById('registerForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const form = this;
            const submitBtn = document.getElementById('submitBtn');
            const alertContainer = document.getElementById('alertContainer');
            const formData = new FormData(form);
            
            // تعطيل الزر وعرض حالة التحميل
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<div class="spinner"></div> جاري التسجيل...';
            alertContainer.innerHTML = '';
            
            try {
                const response = await fetch('register.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    // عرض رسالة النجاح
                    alertContainer.innerHTML = `
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle"></i>
                            <div>${data.message}</div>
                        </div>
                    `;
                    
                    // إعادة تعيين النموذج
                    form.reset();
                    
                    // إعادة التوجيه بعد ثانيتين
                    setTimeout(() => {
                        window.location.href = data.redirect;
                    }, 2000);
                    
                } else {
                    // عرض الأخطاء
                    let errorsHtml = '<div class="alert alert-danger"><i class="fas fa-exclamation-triangle"></i><div><ul>';
                    data.errors.forEach(error => {
                        errorsHtml += `<li>${error}</li>`;
                    });
                    errorsHtml += '</ul></div></div>';
                    
                    alertContainer.innerHTML = errorsHtml;
                    
                    // إعادة تفعيل الزر
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<i class="fas fa-rocket"></i> إنشاء حساب';
                    
                    // التمرير إلى منطقة الأخطاء
                    alertContainer.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                }
                
            } catch (error) {
                console.error('Error:', error);
                alertContainer.innerHTML = `
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle"></i>
                        <div>حدث خطأ في الاتصال بالخادم. يرجى المحاولة مرة أخرى.</div>
                    </div>
                `;
                
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-rocket"></i> إنشاء حساب';
            }
        });
        
        // التحقق من تطابق كلمات المرور في الوقت الفعلي
        const password = document.querySelector('input[name="password"]');
        const confirmPassword = document.querySelector('input[name="confirm_password"]');
        
        confirmPassword.addEventListener('input', function() {
            if (this.value !== password.value) {
                this.setCustomValidity('كلمات المرور غير متطابقة');
            } else {
                this.setCustomValidity('');
            }
        });
        
        password.addEventListener('input', function() {
            if (confirmPassword.value && confirmPassword.value !== this.value) {
                confirmPassword.setCustomValidity('كلمات المرور غير متطابقة');
            } else {
                confirmPassword.setCustomValidity('');
            }
        });
    </script>

    <!-- Mobile Menu & Responsive JavaScript -->
    <script src="assets/js/mobile-menu.js"></script>

</body>
</html>