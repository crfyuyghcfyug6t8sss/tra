<?php
// بداية الجلسة قبل أي شيء
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../config/database.php';
require_once '../includes/functions.php';

// معالجة طلبات AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax']) && $_POST['ajax'] == '1') {
    header('Content-Type: application/json; charset=utf-8');
    ob_clean();
    
    $errors = [];
    
    $username_or_email = isset($_POST['username_or_email']) ? trim($_POST['username_or_email']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $remember = isset($_POST['remember']) ? true : false;
    
    // التحقق من المدخلات
    if (empty($username_or_email)) {
        $errors[] = 'اسم المستخدم أو البريد الإلكتروني مطلوب';
    }
    
    if (empty($password)) {
        $errors[] = 'كلمة المرور مطلوبة';
    }
    
    if (empty($errors)) {
        try {
            $stmt = $conn->prepare("SELECT * FROM users WHERE username = ? OR email = ? LIMIT 1");
            $stmt->execute([$username_or_email, $username_or_email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user && password_verify($password, $user['password'])) {
                
                // التحقق من حالة الحساب
                if ($user['status'] === 'suspended') {
                    $errors[] = 'حسابك موقوف. يرجى التواصل مع الإدارة';
                } 
                // ✅ التحقق من تأكيد البريد الإلكتروني
                elseif (!$user['email_verified']) {
                    // تسجيل الجلسة لكن إعادة توجيه لصفحة التحقق
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['role'] = $user['role'];
                    $_SESSION['email'] = $user['email'];
                    $_SESSION['logged_in'] = true;
                    
                    echo json_encode([
                        'success' => true,
                        'message' => 'يرجى التحقق من بريدك الإلكتروني أولاً',
                        'email_verified' => false,
                        'redirect' => '../verify-pending.php'
                    ]);
                    exit;
                } 
                else {
                    // تسجيل الدخول الناجح
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['role'] = $user['role'];
                    $_SESSION['email'] = $user['email'];
                    $_SESSION['logged_in'] = true;
                    
                    // تحديث آخر دخول
                    try {
                        $update = $conn->prepare("UPDATE users SET updated_at = NOW() WHERE id = ?");
                        $update->execute([$user['id']]);
                    } catch(Exception $e) {
                        error_log("Update last login error: " . $e->getMessage());
                    }
                    
                    // حفظ في كوكيز "تذكرني"
                    if ($remember) {
                        $token = bin2hex(random_bytes(32));
                        setcookie('remember_token', $token, time() + (86400 * 30), '/', '', false, true);
                        
                        // حفظ التوكن في قاعدة البيانات
                        try {
                            $stmt = $conn->prepare("UPDATE users SET remember_token = ? WHERE id = ?");
                            $stmt->execute([$token, $user['id']]);
                        } catch(Exception $e) {
                            error_log("Remember token error: " . $e->getMessage());
                        }
                    }
                    
                    // تحديد صفحة التوجيه حسب الدور
                    $redirect = ($user['role'] === 'admin') ? '../admin/dashboard.php' : '../dashboard.php';
                    
                    echo json_encode([
                        'success' => true,
                        'message' => 'تم تسجيل الدخول بنجاح! جاري تحويلك...',
                        'email_verified' => true,
                        'redirect' => $redirect
                    ]);
                    exit;
                }
            } else {
                $errors[] = 'اسم المستخدم أو كلمة المرور غير صحيحة';
            }
            
        } catch (PDOException $e) {
            error_log("Login DB Error: " . $e->getMessage());
            $errors[] = 'حدث خطأ في الاتصال بقاعدة البيانات';
        }
    }
    
    echo json_encode([
        'success' => false,
        'errors' => $errors
    ]);
    exit;
}

// التحقق من تسجيل الدخول
if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) {
    // التحقق من البريد المحقق
    $stmt = $conn->prepare("SELECT email_verified FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user && !$user['email_verified']) {
        header('Location: ../verify-pending.php');
        exit;
    }
    
    header('Location: ../dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تسجيل الدخول - مزادات السيارات</title>
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
            max-width: 450px;
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
        
        .alert-warning {
            background: rgba(245, 158, 11, 0.15);
            border: 1px solid rgba(245, 158, 11, 0.3);
            color: #fbbf24;
        }
        
        .alert i {
            font-size: 1.2rem;
            margin-top: 2px;
        }
        
        .alert ul {
            list-style: none;
            padding: 0;
        }
        
        .alert li {
            margin-bottom: 4px;
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
        
        .form-options {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
            flex-wrap: wrap;
            gap: 12px;
        }
        
        .remember-me {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #94a3b8;
            font-size: 0.9rem;
            cursor: pointer;
            user-select: none;
        }
        
        .remember-me input[type="checkbox"] {
            width: 18px;
            height: 18px;
            cursor: pointer;
            accent-color: #6366f1;
        }
        
        .forgot-link {
            color: #6366f1;
            text-decoration: none;
            font-size: 0.9rem;
            transition: color 0.3s;
        }
        
        .forgot-link:hover {
            color: #8b5cf6;
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
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(99, 102, 241, 0.5);
        }
        
        .btn:active {
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
            
            .form-options {
                flex-direction: column;
                align-items: flex-start;
            }
        }
    </style>
</head>
<body>
    <div class="particles" id="particles"></div>
                                    <div style="position: fixed; top: 20px; left: 20px; z-index: 1000;">
        <?php include '../includes/lang-switcher.php'; ?>
    </div>
    <script src="../js/auto-translate.js"></script>

    <div class="container">
        <div class="card">
            <div class="brand">
                <div class="brand-icon">
                    <i class="fas fa-car-side"></i>
                </div>
                <h1>مزادات السيارات</h1>
                <p>تسجيل الدخول إلى حسابك</p>
            </div>
            
            <div id="alertContainer"></div>
            
            <form id="loginForm">
                <input type="hidden" name="ajax" value="1">
                
                <div class="form-group">
                    <label class="form-label">اسم المستخدم أو البريد الإلكتروني</label>
                    <div class="input-group">
                        <input 
                            type="text" 
                            name="username_or_email" 
                            class="form-control"
                            placeholder="أدخل اسم المستخدم أو البريد الإلكتروني"
                            required
                            autofocus
                        >
                        <i class="fas fa-user"></i>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">كلمة المرور</label>
                    <div class="input-group">
                        <input 
                            type="password" 
                            name="password" 
                            class="form-control"
                            placeholder="أدخل كلمة المرور"
                            required
                        >
                        <i class="fas fa-lock"></i>
                    </div>
                </div>
                
                <div class="form-options">
                    <label class="remember-me">
                        <input type="checkbox" name="remember" value="1">
                        <span>تذكرني</span>
                    </label>
                    <a href="forgot-password.php" class="forgot-link">نسيت كلمة المرور؟</a>
                </div>
                
                <button type="submit" class="btn" id="submitBtn">
                    <i class="fas fa-sign-in-alt"></i>
                    تسجيل الدخول
                </button>
            </form>
            
            <div class="divider">
                <span>أو</span>
            </div>
            
            <div class="footer-link">
                ليس لديك حساب؟ <a href="register.php">إنشاء حساب جديد</a>
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
        
        document.getElementById('loginForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const form = this;
            const submitBtn = document.getElementById('submitBtn');
            const alertContainer = document.getElementById('alertContainer');
            const formData = new FormData(form);
            
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<div class="spinner"></div> جاري تسجيل الدخول...';
            alertContainer.innerHTML = '';
            
            try {
                const response = await fetch('login.php', {
                    method: 'POST',
                    body: formData
                });
                
                const text = await response.text();
                let data;
                
                try {
                    data = JSON.parse(text);
                } catch(e) {
                    console.error('Response:', text);
                    throw new Error('استجابة غير صحيحة من الخادم');
                }
                
                if (data.success) {
                    // ✅ التحقق من حالة البريد
                    if (!data.email_verified) {
                        alertContainer.innerHTML = `
                            <div class="alert alert-warning">
                                <i class="fas fa-envelope"></i>
                                <div>${data.message}</div>
                            </div>
                        `;
                    } else {
                        alertContainer.innerHTML = `
                            <div class="alert alert-success">
                                <i class="fas fa-check-circle"></i>
                                <div>${data.message}</div>
                            </div>
                        `;
                    }
                    
                    setTimeout(() => {
                        window.location.href = data.redirect;
                    }, 1500);
                    
                } else {
                    let errorsHtml = '<div class="alert alert-danger"><i class="fas fa-exclamation-triangle"></i><div><ul>';
                    data.errors.forEach(error => {
                        errorsHtml += `<li>${error}</li>`;
                    });
                    errorsHtml += '</ul></div></div>';
                    
                    alertContainer.innerHTML = errorsHtml;
                    
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<i class="fas fa-sign-in-alt"></i> تسجيل الدخول';
                }
                
            } catch (error) {
                console.error('Error:', error);
                alertContainer.innerHTML = `
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle"></i>
                        <div>حدث خطأ في الاتصال. يرجى المحاولة مرة أخرى.</div>
                    </div>
                `;
                
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-sign-in-alt"></i> تسجيل الدخول';
            }
        });
    </script>
</body>
</html>