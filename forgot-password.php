<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax']) && $_POST['ajax'] == '1') {
    header('Content-Type: application/json');
    
    $email = clean($_POST['email'] ?? '');
    $errors = [];
    
    if (empty($email)) {
        $errors[] = 'البريد الإلكتروني مطلوب';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'البريد الإلكتروني غير صحيح';
    }
    
    if (empty($errors)) {
        $stmt = $conn->prepare("SELECT id, username FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            // حذف التوكنات القديمة
            $conn->prepare("DELETE FROM password_resets WHERE user_id = ? AND created_at < DATE_SUB(NOW(), INTERVAL 1 HOUR)")->execute([$user['id']]);
            
            // توليد توكن جديد
            $reset_token = bin2hex(random_bytes(32));
            $expires_at = date('Y-m-d H:i:s', strtotime('+1 hour'));
            
            $stmt = $conn->prepare("
                INSERT INTO password_resets (user_id, token, expires_at) 
                VALUES (?, ?, ?)
            ");
            $stmt->execute([$user['id'], $reset_token, $expires_at]);
            
            // إرسال البريد
            $reset_link = 'https://bidora.de/auth/reset-password.php?token=' . $reset_token;
            
            $emailBody = "
            <html dir='rtl'>
            <head>
                <meta charset='UTF-8'>
                <style>
                    body { font-family: Cairo, sans-serif; }
                    .container { background: white; padding: 40px; border-radius: 8px; max-width: 600px; margin: 20px auto; }
                    .btn { display: inline-block; background: #6366f1; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; }
                </style>
            </head>
            <body>
                <div class='container'>
                    <h1>استعادة كلمة المرور</h1>
                    <p>مرحباً " . htmlspecialchars($user['username']) . "،</p>
                    <p>تم طلب استعادة كلمة المرور. اضغط على الزر أدناه:</p>
                    <a href='" . $reset_link . "' class='btn'>استعادة كلمة المرور</a>
                    <p>أو انسخ هذا الرابط: " . $reset_link . "</p>
                    <p>صلاحية هذا الرابط ساعة واحدة فقط.</p>
                </div>
            </body>
            </html>
            ";
            
            require_once '../includes/send-email.php';
            sendEmailViaSMTP($email, 'استعادة كلمة المرور', $emailBody);
        }
        
        // لا نخبر المستخدم إذا كان البريد موجوداً أم لا (أمان)
        echo json_encode([
            'success' => true,
            'message' => 'إذا كان البريد موجوداً، ستتلقى رسالة استعادة'
        ]);
        exit;
    }
    
    echo json_encode([
        'success' => false,
        'errors' => $errors
    ]);
    exit;
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>نسيت كلمة المرور - مزادات السيارات</title>
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
        }
        
        .brand {
            text-align: center;
            margin-bottom: 40px;
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
        }
        
        .form-control {
            width: 100%;
            padding: 14px 48px 14px 16px;
            background: rgba(15, 23, 42, 0.5);
            border: 2px solid rgba(148, 163, 184, 0.2);
            border-radius: 12px;
            color: #f1f5f9;
            font-family: 'Cairo', sans-serif;
            transition: all 0.3s;
        }
        
        .form-control:focus {
            outline: none;
            border-color: #6366f1;
            box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1);
        }
        
        .btn {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
            border: none;
            border-radius: 12px;
            color: white;
            font-weight: 600;
            font-family: 'Cairo', sans-serif;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        
        .btn:hover:not(:disabled) {
            transform: translateY(-2px);
        }
        
        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        
        .back-link {
            text-align: center;
            margin-top: 20px;
        }
        
        .back-link a {
            color: #6366f1;
            text-decoration: none;
            font-size: 0.9rem;
            transition: color 0.3s;
        }
        
        .back-link a:hover {
            color: #8b5cf6;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="brand">
                <h1>استعادة كلمة المرور</h1>
                <p>أدخل بريدك الإلكتروني لاستعادة كلمة المرور</p>
            </div>
            
            <div id="alertContainer"></div>
            
            <form id="forgotForm">
                <input type="hidden" name="ajax" value="1">
                
                <div class="form-group">
                    <label class="form-label">البريد الإلكتروني</label>
                    <div class="input-group">
                        <input 
                            type="email" 
                            name="email" 
                            class="form-control"
                            placeholder="أدخل بريدك الإلكتروني"
                            required
                        >
                        <i class="fas fa-envelope"></i>
                    </div>
                </div>
                
                <button type="submit" class="btn" id="submitBtn">
                    <i class="fas fa-paper-plane"></i>
                    إرسال رابط الاستعادة
                </button>
            </form>
            
            <div class="back-link">
                <a href="login.php">العودة لتسجيل الدخول</a>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('forgotForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const form = this;
            const submitBtn = document.getElementById('submitBtn');
            const alertContainer = document.getElementById('alertContainer');
            const formData = new FormData(form);
            
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<div class="spinner"></div> جاري الإرسال...';
            alertContainer.innerHTML = '';
            
            try {
                const response = await fetch('forgot-password.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    alertContainer.innerHTML = `
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle"></i>
                            <div>${data.message}</div>
                        </div>
                    `;
                    form.reset();
                } else {
                    let errorsHtml = '<div class="alert alert-danger"><i class="fas fa-exclamation-triangle"></i><div><ul>';
                    data.errors.forEach(error => {
                        errorsHtml += `<li>${error}</li>`;
                    });
                    errorsHtml += '</ul></div></div>';
                    alertContainer.innerHTML = errorsHtml;
                }
                
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-paper-plane"></i> إرسال رابط الاستعادة';
            } catch (error) {
                alertContainer.innerHTML = `
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle"></i>
                        <div>حدث خطأ. يرجى المحاولة لاحقاً</div>
                    </div>
                `;
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-paper-plane"></i> إرسال رابط الاستعادة';
            }
        });
    </script>
</body>
</html>