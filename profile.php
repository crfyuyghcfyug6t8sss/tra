<?php

require_once 'config/database.php';

require_once 'includes/functions.php';

 

if (!isLoggedIn()) {

    redirect('auth/login.php');

}

 

$user_id = $_SESSION['user_id'];

$success = '';

$errors = [];

 

// معالجة تغيير كلمة السر

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['change_password'])) {

    $current_password = clean($_POST['current_password']);

    $new_password = clean($_POST['new_password']);

    $confirm_password = clean($_POST['confirm_password']);

 

    if (empty($current_password)) {

        $errors[] = 'يرجى إدخال كلمة السر الحالية';

    }

 

    if (empty($new_password)) {

        $errors[] = 'يرجى إدخال كلمة السر الجديدة';

    } elseif (strlen($new_password) < 6) {

        $errors[] = 'كلمة السر يجب أن تكون 6 أحرف على الأقل';

    }

 

    if ($new_password !== $confirm_password) {

        $errors[] = 'كلمة السر الجديدة غير متطابقة';

    }

 

    if (empty($errors)) {

        // التحقق من كلمة السر الحالية

        $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");

        $stmt->execute([$user_id]);

        $user = $stmt->fetch();

 

        if (password_verify($current_password, $user['password'])) {

            // تحديث كلمة السر

            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

            $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");

            $stmt->execute([$hashed_password, $user_id]);

 

            $_SESSION['success'] = 'تم تغيير كلمة السر بنجاح!';

            header('Location: profile.php');

            exit;

        } else {

            $errors[] = 'كلمة السر الحالية غير صحيحة';

        }

    }

}

 

// عرض الرسائل

$success = isset($_SESSION['success']) ? $_SESSION['success'] : '';

unset($_SESSION['success']);

 

// جلب معلومات المستخدم

$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");

$stmt->execute([$user_id]);

$user = $stmt->fetch();

 

// جلب معلومات المحفظة

$stmt = $conn->prepare("SELECT * FROM wallet WHERE user_id = ?");

$stmt->execute([$user_id]);

$wallet = $stmt->fetch();

 

// جلب معلومات KYC

$stmt = $conn->prepare("SELECT * FROM kyc_verifications WHERE user_id = ?");

$stmt->execute([$user_id]);

$kyc = $stmt->fetch();

 

// إحصائيات

$stmt = $conn->prepare("SELECT COUNT(*) as count FROM auctions WHERE seller_id = ?");

$stmt->execute([$user_id]);

$total_auctions = $stmt->fetch()['count'];

 

$stmt = $conn->prepare("SELECT COUNT(*) as count FROM bids WHERE user_id = ?");

$stmt->execute([$user_id]);

$total_bids = $stmt->fetch()['count'];

 

$stmt = $conn->prepare("SELECT AVG(rating) as avg, COUNT(*) as count FROM ratings WHERE to_user_id = ? AND rating IS NOT NULL");

$stmt->execute([$user_id]);

$rating_data = $stmt->fetch();

 

// عدد الإشعارات غير المقروءة

$stmt = $conn->prepare("SELECT COUNT(*) as unread FROM notifications WHERE user_id = ? AND is_read = 0");

$stmt->execute([$user_id]);

$unread_count = $stmt->fetch()['unread'];

?>

<!DOCTYPE html>

<html lang="ar" dir="rtl">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>الملف الشخصي الفاخر - Bidora</title>

 

    <!-- Fonts -->

    <link rel="preconnect" href="https://fonts.googleapis.com">

    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@200;300;400;500;600;700;800;900&family=Tajawal:wght@300;400;500;700;900&display=swap" rel="stylesheet">

 

    <!-- Icons -->

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.min.css">

    <link rel="stylesheet" href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css">

 

    <!-- AOS Animation -->

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.css">

 

    <style>

        :root {

            /* Gradient Palettes */

            --royal-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);

            --sunset-gradient: linear-gradient(135deg, #fa709a 0%, #fee140 100%);

            --ocean-gradient: linear-gradient(135deg, #89f7fe 0%, #66a6ff 100%);

            --emerald-gradient: linear-gradient(135deg, #13f1fc 0%, #0470dc 100%);

            --fire-gradient: linear-gradient(135deg, #f9d423 0%, #ff4e50 100%);

            --mystic-gradient: linear-gradient(135deg, #ec77ab 0%, #7873f5 100%);

            --cosmic-gradient: linear-gradient(135deg, #7F00FF 0%, #E100FF 100%);

            --aurora-gradient: linear-gradient(135deg, #00c9ff 0%, #92fe9d 100%);

 

            /* Glass Effects */

            --glass-white: rgba(255, 255, 255, 0.1);

            --glass-border: rgba(255, 255, 255, 0.18);

            --glass-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.37);

 

            /* Colors */

            --primary: #667eea;

            --secondary: #764ba2;

            --success: #13f1fc;

            --warning: #f9d423;

            --danger: #ff4e50;

            --info: #00c9ff;

            --dark: #0f0c29;

            --light: #f7fafc;

 

            /* Neon Colors */

            --neon-pink: #ff006e;

            --neon-blue: #00d4ff;

            --neon-green: #39ff14;

            --neon-purple: #bc13fe;

 

            /* Shadows */

            --shadow-sm: 0 2px 20px rgba(0, 0, 0, 0.1);

            --shadow-md: 0 10px 40px rgba(0, 0, 0, 0.15);

            --shadow-lg: 0 20px 60px rgba(0, 0, 0, 0.2);

            --shadow-xl: 0 40px 80px rgba(0, 0, 0, 0.25);

            --shadow-neon: 0 0 50px rgba(102, 126, 234, 0.5);

 

            /* Animations */

            --transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);

            --bounce: cubic-bezier(0.68, -0.55, 0.265, 1.55);

        }

 

        * {

            margin: 0;

            padding: 0;

            box-sizing: border-box;

        }

 

        body {

            font-family: 'Cairo', 'Tajawal', sans-serif;

            background: #0f0c29;

            min-height: 100vh;

            direction: rtl;

            overflow-x: hidden;

            position: relative;

        }

 

        /* الخلفية الديناميكية المتحركة */

        .dynamic-background {

            position: fixed;

            top: 0;

            left: 0;

            width: 100%;

            height: 100%;

            z-index: -2;

            background: linear-gradient(125deg, #0f0c29, #302b63, #24243e, #0f0c29);

            background-size: 400% 400%;

            animation: gradientFlow 20s ease infinite;

        }

 

        @keyframes gradientFlow {

            0% { background-position: 0% 0%; }

            25% { background-position: 100% 0%; }

            50% { background-position: 100% 100%; }

            75% { background-position: 0% 100%; }

            100% { background-position: 0% 0%; }

        }

 

        /* شبكة ثلاثية الأبعاد */

        .grid-3d {

            position: fixed;

            top: 0;

            left: 0;

            width: 100%;

            height: 100%;

            z-index: -1;

            opacity: 0.1;

            background-image:

                linear-gradient(rgba(102, 126, 234, 0.2) 2px, transparent 2px),

                linear-gradient(90deg, rgba(102, 126, 234, 0.2) 2px, transparent 2px);

            background-size: 50px 50px;

            animation: grid3d 10s linear infinite;

            transform: perspective(1000px) rotateX(60deg);

        }

 

        @keyframes grid3d {

            0% { transform: perspective(1000px) rotateX(60deg) translateY(0); }

            100% { transform: perspective(1000px) rotateX(60deg) translateY(50px); }

        }

 

        /* جسيمات متوهجة */

        .glowing-particles {

            position: fixed;

            top: 0;

            left: 0;

            width: 100%;

            height: 100%;

            z-index: -1;

            pointer-events: none;

        }

 

        .particle {

            position: absolute;

            width: 6px;

            height: 6px;

            background: radial-gradient(circle, rgba(102, 126, 234, 1) 0%, transparent 70%);

            border-radius: 50%;

            animation: particleFloat 25s infinite linear;

            box-shadow: 0 0 10px rgba(102, 126, 234, 0.8);

        }

 

        @keyframes particleFloat {

            0% {

                transform: translateY(100vh) translateX(0) scale(0);

                opacity: 0;

            }

            10% {

                transform: translateY(80vh) translateX(10px) scale(1);

                opacity: 1;

            }

            90% {

                transform: translateY(10vh) translateX(-10px) scale(1);

                opacity: 1;

            }

            100% {

                transform: translateY(-100vh) translateX(0) scale(0);

                opacity: 0;

            }

        }

 

        /* نجوم متلألئة */

        .stars {

            position: fixed;

            top: 0;

            left: 0;

            width: 100%;

            height: 100%;

            z-index: -1;

        }

 

        .star {

            position: absolute;

            width: 2px;

            height: 2px;

            background: white;

            border-radius: 50%;

            animation: twinkle 5s infinite;

        }

 

        @keyframes twinkle {

            0%, 100% { opacity: 0; transform: scale(0.5); }

            50% { opacity: 1; transform: scale(1.5); }

        }

 

        /* Header الفاخر */

        .luxury-header {

            background: var(--glass-white);

            backdrop-filter: blur(20px);

            -webkit-backdrop-filter: blur(20px);

            border-bottom: 1px solid var(--glass-border);

            position: sticky;

            top: 0;

            z-index: 1000;

            animation: slideDownHeader 0.8s ease-out;

        }

 

        @keyframes slideDownHeader {

            from {

                transform: translateY(-100%);

                opacity: 0;

            }

            to {

                transform: translateY(0);

                opacity: 1;

            }

        }

 

        .header-glow {

            position: absolute;

            top: 0;

            left: 50%;

            transform: translateX(-50%);

            width: 80%;

            height: 100%;

            background: radial-gradient(ellipse at center, rgba(102, 126, 234, 0.3) 0%, transparent 70%);

            filter: blur(40px);

            pointer-events: none;

        }

 

        .container {

            max-width: 1400px;

            margin: 0 auto;

            padding: 0 20px;

            position: relative;

        }

 

        .header-content {

            display: flex;

            justify-content: space-between;

            align-items: center;

            padding: 20px 0;

            position: relative;

        }

 

        /* شعار متحرك */

        .animated-logo {

            display: flex;

            align-items: center;

            gap: 15px;

            position: relative;

        }

 

        .logo-icon {

            width: 55px;

            height: 55px;

            position: relative;

            animation: logoFloat 3s ease-in-out infinite;

        }

 

        @keyframes logoFloat {

            0%, 100% { transform: translateY(0) rotate(0deg); }

            50% { transform: translateY(-5px) rotate(5deg); }

        }

 

        .logo-icon img {

            width: 100%;

            height: 100%;

            border-radius: 15px;

            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.4);

        }

 

        .logo-text {

            font-size: 1.8rem;

            font-weight: 900;

            background: linear-gradient(135deg, #fff, #e0e7ff, #c7d2fe);

            -webkit-background-clip: text;

            -webkit-text-fill-color: transparent;

            animation: logoShimmer 3s ease-in-out infinite;

            text-shadow: 0 0 30px rgba(102, 126, 234, 0.5);

        }

 

        @keyframes logoShimmer {

            0%, 100% { filter: brightness(1); }

            50% { filter: brightness(1.2); }

        }

 

        /* معلومات المستخدم الفاخرة */

        .user-section {

            display: flex;

            align-items: center;

            gap: 20px;

        }

 

        .user-profile {

            display: flex;

            align-items: center;

            gap: 12px;

            padding: 10px 20px;

            background: var(--glass-white);

            backdrop-filter: blur(10px);

            border: 1px solid var(--glass-border);

            border-radius: 50px;

            position: relative;

            overflow: hidden;

        }

 

        .user-profile::before {

            content: '';

            position: absolute;

            top: -2px;

            left: -2px;

            right: -2px;

            bottom: -2px;

            background: var(--royal-gradient);

            border-radius: 50px;

            opacity: 0;

            transition: opacity 0.3s;

            z-index: -1;

        }

 

        .user-profile:hover::before {

            opacity: 1;

        }

 

        .user-avatar {

            width: 40px;

            height: 40px;

            border-radius: 50%;

            background: var(--royal-gradient);

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 1.3rem;

            color: white;

            box-shadow: 0 0 20px rgba(102, 126, 234, 0.5);

            animation: avatarPulse 2s ease-in-out infinite;

        }

 

        @keyframes avatarPulse {

            0%, 100% { box-shadow: 0 0 20px rgba(102, 126, 234, 0.5); }

            50% { box-shadow: 0 0 30px rgba(102, 126, 234, 0.8); }

        }

 

        .user-name {

            color: white;

            font-weight: 700;

            font-size: 1.05rem;

        }

 

        /* شارات التوثيق */

        .premium-badge {

            display: inline-flex;

            align-items: center;

            gap: 5px;

            padding: 5px 12px;

            background: linear-gradient(135deg, #ffd700, #ffed4e);

            color: #7c3f00;

            border-radius: 20px;

            font-size: 0.75rem;

            font-weight: 800;

            box-shadow: 0 0 15px rgba(255, 215, 0, 0.5);

            animation: badgeGlow 2s ease-in-out infinite;

        }

 

        @keyframes badgeGlow {

            0%, 100% {

                box-shadow: 0 0 15px rgba(255, 215, 0, 0.5);

                transform: scale(1);

            }

            50% {

                box-shadow: 0 0 25px rgba(255, 215, 0, 0.8);

                transform: scale(1.05);

            }

        }

 

        .verified-badge-premium {

            background: var(--emerald-gradient);

            color: white;

            animation: verifiedPulse 3s ease-in-out infinite;

        }

 

        @keyframes verifiedPulse {

            0%, 100% {

                box-shadow: 0 0 15px rgba(19, 241, 252, 0.5);

            }

            50% {

                box-shadow: 0 0 25px rgba(19, 241, 252, 0.8);

            }

        }

 

        /* قائمة التنقل الفاخرة */

        .luxury-nav {

            display: flex;

            gap: 10px;

            align-items: center;

        }

 

        .nav-item {

            position: relative;

            padding: 12px 18px;

            background: var(--glass-white);

            backdrop-filter: blur(10px);

            border: 1px solid var(--glass-border);

            border-radius: 15px;

            color: rgba(255, 255, 255, 0.9);

            text-decoration: none;

            font-weight: 600;

            font-size: 0.95rem;

            display: flex;

            align-items: center;

            gap: 8px;

            transition: var(--transition);

            overflow: hidden;

        }

 

        .nav-item::before {

            content: '';

            position: absolute;

            top: 50%;

            left: 50%;

            width: 0;

            height: 0;

            background: var(--royal-gradient);

            border-radius: 50%;

            transform: translate(-50%, -50%);

            transition: width 0.6s ease, height 0.6s ease;

        }

 

        .nav-item:hover {

            color: white;

            transform: translateY(-3px);

            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);

            border-color: rgba(102, 126, 234, 0.5);

        }

 

        .nav-item:hover::before {

            width: 150%;

            height: 150%;

        }

 

        .nav-item.active {

            background: var(--royal-gradient);

            color: white;

            border: none;

            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.4);

        }

 

        .nav-item i {

            font-size: 1.1rem;

            position: relative;

            z-index: 1;

        }

 

        .nav-item span {

            position: relative;

            z-index: 1;

        }

 

        /* إشعارات متحركة */

        .notification-btn {

            position: relative;

        }

 

        .notification-dot {

            position: absolute;

            top: -5px;

            right: -5px;

            min-width: 22px;

            height: 22px;

            background: var(--fire-gradient);

            color: white;

            border-radius: 50%;

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 0.7rem;

            font-weight: 800;

            box-shadow: 0 0 10px rgba(255, 78, 80, 0.5);

            animation: notificationPulse 1s ease-in-out infinite;

        }

 

        @keyframes notificationPulse {

            0%, 100% {

                transform: scale(1);

                box-shadow: 0 0 10px rgba(255, 78, 80, 0.5);

            }

            50% {

                transform: scale(1.1);

                box-shadow: 0 0 20px rgba(255, 78, 80, 0.8);

            }

        }

 

        .logout-btn {

            background: linear-gradient(135deg, rgba(239, 68, 68, 0.2), rgba(220, 38, 38, 0.2));

            border: 1px solid rgba(239, 68, 68, 0.3);

        }

 

        .logout-btn:hover {

            background: var(--fire-gradient);

            border: none;

        }

 

        /* المحتوى الرئيسي */

        .main-content {

            padding: 40px 0;

            position: relative;

        }

 

        /* بطاقة الملف الشخصي الفاخرة */

        .profile-card-3d {

            background: var(--glass-white);

            backdrop-filter: blur(30px);

            -webkit-backdrop-filter: blur(30px);

            border: 1px solid var(--glass-border);

            border-radius: 30px;

            padding: 50px;

            margin-bottom: 40px;

            position: relative;

            overflow: hidden;

            animation: profileSlideIn 1s ease-out;

            text-align: center;

        }

 

        @keyframes profileSlideIn {

            from {

                opacity: 0;

                transform: translateY(30px) scale(0.95);

            }

            to {

                opacity: 1;

                transform: translateY(0) scale(1);

            }

        }

 

        .profile-bg-animation {

            position: absolute;

            top: -50%;

            left: -50%;

            width: 200%;

            height: 200%;

            background: radial-gradient(circle at center, rgba(102, 126, 234, 0.15) 0%, transparent 50%);

            animation: profileRotate 30s linear infinite;

        }

 

        @keyframes profileRotate {

            from { transform: rotate(0deg) scale(1); }

            to { transform: rotate(360deg) scale(1.1); }

        }

 

        .profile-avatar-3d {

            width: 150px;

            height: 150px;

            border-radius: 50%;

            background: var(--royal-gradient);

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 4rem;

            color: white;

            margin: 0 auto 30px;

            box-shadow: 0 20px 60px rgba(102, 126, 234, 0.5);

            position: relative;

            z-index: 1;

            animation: avatarFloat 4s ease-in-out infinite;

        }

 

        @keyframes avatarFloat {

            0%, 100% { transform: translateY(0) rotate(0deg); }

            50% { transform: translateY(-10px) rotate(5deg); }

        }

 

        .profile-name-3d {

            font-size: 2.5rem;

            font-weight: 900;

            margin-bottom: 15px;

            background: linear-gradient(135deg, #667eea, #764ba2, #f093fb, #f5576c);

            background-size: 300% 300%;

            -webkit-background-clip: text;

            -webkit-text-fill-color: transparent;

            animation: nameGradient 5s ease infinite;

            position: relative;

            z-index: 1;

        }

 

        @keyframes nameGradient {

            0% { background-position: 0% 50%; }

            50% { background-position: 100% 50%; }

            100% { background-position: 0% 50%; }

        }

 

        .profile-email-3d {

            color: rgba(255, 255, 255, 0.8);

            font-size: 1.2rem;

            font-weight: 500;

            margin-bottom: 20px;

            position: relative;

            z-index: 1;

        }

 

        /* التنبيهات الفاخرة */

        .premium-alert {

            background: var(--glass-white);

            backdrop-filter: blur(20px);

            border: 2px solid;

            border-radius: 25px;

            padding: 25px 35px;

            margin-bottom: 25px;

            position: relative;

            overflow: hidden;

            animation: alertSlide 0.8s ease-out;

            display: flex;

            align-items: center;

            gap: 20px;

        }

 

        @keyframes alertSlide {

            from {

                opacity: 0;

                transform: translateX(50px);

            }

            to {

                opacity: 1;

                transform: translateX(0);

            }

        }

 

        .premium-alert.success {

            border-color: rgba(16, 185, 129, 0.5);

            background: linear-gradient(135deg, rgba(16, 185, 129, 0.1), rgba(5, 150, 105, 0.1));

        }

 

        .premium-alert.danger {

            border-color: rgba(239, 68, 68, 0.5);

            background: linear-gradient(135deg, rgba(239, 68, 68, 0.1), rgba(220, 38, 38, 0.1));

        }

 

        .alert-icon-3d {

            width: 50px;

            height: 50px;

            border-radius: 15px;

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 1.8rem;

            color: white;

            flex-shrink: 0;

            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);

        }

 

        .alert-icon-3d.success {

            background: linear-gradient(135deg, #10b981, #059669);

        }

 

        .alert-icon-3d.danger {

            background: linear-gradient(135deg, #ef4444, #dc2626);

        }

 

        .alert-message {

            color: white;

            font-size: 1.1rem;

            font-weight: 600;

            flex: 1;

        }

 

        /* شبكة البطاقات */

        .cards-grid {

            display: grid;

            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));

            gap: 25px;

            margin-bottom: 40px;

        }

 

        .card-3d {

            background: var(--glass-white);

            backdrop-filter: blur(20px);

            -webkit-backdrop-filter: blur(20px);

            border: 1px solid var(--glass-border);

            border-radius: 25px;

            padding: 30px;

            position: relative;

            overflow: hidden;

            transition: var(--transition);

            animation: cardFadeIn 0.8s ease-out backwards;

        }

 

        .card-3d:nth-child(1) { animation-delay: 0.1s; }

        .card-3d:nth-child(2) { animation-delay: 0.2s; }

        .card-3d:nth-child(3) { animation-delay: 0.3s; }

        .card-3d:nth-child(4) { animation-delay: 0.4s; }

 

        @keyframes cardFadeIn {

            from {

                opacity: 0;

                transform: translateY(30px);

            }

            to {

                opacity: 1;

                transform: translateY(0);

            }

        }

 

        .card-3d:hover {

            transform: translateY(-10px);

            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);

        }

 

        .card-3d::before {

            content: '';

            position: absolute;

            top: 0;

            left: 0;

            right: 0;

            height: 5px;

            background: var(--gradient);

            transform: scaleX(0);

            transform-origin: left;

            transition: transform 0.5s ease;

        }

 

        .card-3d:hover::before {

            transform: scaleX(1);

        }

 

        .card-header-3d {

            display: flex;

            align-items: center;

            gap: 15px;

            margin-bottom: 25px;

            padding-bottom: 15px;

            border-bottom: 2px solid rgba(255, 255, 255, 0.1);

        }

 

        .card-icon-3d {

            width: 50px;

            height: 50px;

            border-radius: 15px;

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 1.5rem;

            color: white;

            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);

        }

 

        .card-title-3d {

            color: white;

            font-size: 1.4rem;

            font-weight: 800;

        }

 

        .info-grid-3d {

            display: grid;

            gap: 20px;

        }

 

        .info-item-3d {

            display: flex;

            flex-direction: column;

            gap: 10px;

        }

 

        .info-label-3d {

            color: rgba(255, 255, 255, 0.7);

            font-size: 0.95rem;

            font-weight: 600;

            display: flex;

            align-items: center;

            gap: 8px;

        }

 

        .info-value-3d {

            color: white;

            font-size: 1.2rem;

            font-weight: 700;

            padding: 15px;

            background: rgba(255, 255, 255, 0.05);

            border-radius: 12px;

            border: 1px solid rgba(255, 255, 255, 0.1);

        }

 

        /* بطاقة الإحصائيات */

        .stats-card-3d {

            display: grid;

            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));

            gap: 20px;

        }

 

        .stat-box-3d {

            text-align: center;

            padding: 25px;

            background: rgba(255, 255, 255, 0.05);

            border-radius: 15px;

            border: 1px solid rgba(255, 255, 255, 0.1);

            transition: var(--transition);

        }

 

        .stat-box-3d:hover {

            background: rgba(255, 255, 255, 0.1);

            transform: translateY(-5px);

        }

 

        .stat-value-3d {

            font-size: 2.5rem;

            font-weight: 900;

            color: white;

            margin-bottom: 10px;

            text-shadow: 0 0 20px rgba(255, 255, 255, 0.3);

        }

 

        .stat-label-3d {

            color: rgba(255, 255, 255, 0.7);

            font-size: 0.95rem;

            font-weight: 600;

        }

 

        /* الشارات */

        .badge-3d {

            display: inline-flex;

            align-items: center;

            gap: 8px;

            padding: 8px 16px;

            border-radius: 20px;

            font-size: 0.95rem;

            font-weight: 700;

            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);

        }

 

        .badge-success {

            background: linear-gradient(135deg, #10b981, #059669);

            color: white;

        }

 

        .badge-warning {

            background: linear-gradient(135deg, #f59e0b, #d97706);

            color: white;

        }

 

        .badge-danger {

            background: linear-gradient(135deg, #ef4444, #dc2626);

            color: white;

        }

 

        /* نموذج تغيير كلمة المرور */

        .password-form-3d {

            grid-column: 1 / -1;

        }

 

        .form-group-3d {

            margin-bottom: 25px;

        }

 

        .form-label-3d {

            display: flex;

            align-items: center;

            gap: 10px;

            color: white;

            font-weight: 700;

            font-size: 1.05rem;

            margin-bottom: 12px;

        }

 

        .form-input-3d {

            width: 100%;

            padding: 15px 20px;

            background: rgba(255, 255, 255, 0.05);

            border: 2px solid rgba(255, 255, 255, 0.1);

            border-radius: 15px;

            color: white;

            font-size: 1rem;

            transition: var(--transition);

        }

 

        .form-input-3d:focus {

            outline: none;

            border-color: var(--primary);

            background: rgba(255, 255, 255, 0.1);

            box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.2);

        }

 

        .form-input-3d::placeholder {

            color: rgba(255, 255, 255, 0.4);

        }

 

        .btn-3d {

            display: flex;

            align-items: center;

            justify-content: center;

            gap: 12px;

            width: 100%;

            padding: 18px;

            background: var(--royal-gradient);

            color: white;

            border: none;

            border-radius: 15px;

            font-size: 1.2rem;

            font-weight: 700;

            cursor: pointer;

            transition: var(--transition);

            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.4);

            position: relative;

            overflow: hidden;

        }

 

        .btn-3d::before {

            content: '';

            position: absolute;

            top: 50%;

            left: -100%;

            width: 100%;

            height: 100%;

            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);

            transform: translateY(-50%);

            transition: left 0.6s;

        }

 

        .btn-3d:hover {

            transform: translateY(-3px);

            box-shadow: 0 15px 40px rgba(102, 126, 234, 0.5);

        }

 

        .btn-3d:hover::before {

            left: 100%;

        }

 

        .btn-3d:active {

            transform: translateY(-1px);

        }

 

        /* استجابة الجوال */

        @media (max-width: 768px) {

            .header-content {

                flex-direction: column;

                gap: 20px;

            }

 

            .luxury-nav {

                display: grid;

                grid-template-columns: repeat(3, 1fr);

                width: 100%;

            }

 

            .profile-card-3d {

                padding: 30px 20px;

            }

 

            .profile-avatar-3d {

                width: 100px;

                height: 100px;

                font-size: 2.5rem;

            }

 

            .profile-name-3d {

                font-size: 1.8rem;

            }

 

            .cards-grid {

                grid-template-columns: 1fr;

            }

 

            .stats-card-3d {

                grid-template-columns: 1fr;

            }

        }

    </style>

</head>

<body>

    <!-- الخلفية الديناميكية -->

    <div class="dynamic-background"></div>

    <div class="grid-3d"></div>

 

    <!-- الجسيمات المتوهجة -->

    <div class="glowing-particles" id="particles"></div>

 

    <!-- النجوم المتلألئة -->

    <div class="stars" id="stars"></div>

 

    <!-- Header الفاخر -->

    <header class="luxury-header">

        <div class="header-glow"></div>

        <div class="container">

            <div class="header-content">

                <!-- الشعار المتحرك -->

                <div class="animated-logo">

                    <div class="logo-icon">

                        <img src="https://img.icons8.com/fluency/96/tesla-model-x.png" alt="Logo">

                    </div>

                    <div class="logo-text">Bidora</div>

                </div>

 

                <!-- معلومات المستخدم -->

                <div class="user-section">

                    <div class="user-profile">

                        <div class="user-avatar">

                            <i class="fas fa-user"></i>

                        </div>

                        <div class="user-name"><?php echo htmlspecialchars($user['username']); ?></div>

                        <?php if ($user['kyc_status'] == 'verified'): ?>

                            <span class="premium-badge verified-badge-premium">

                                <i class="fas fa-check-circle"></i>

                                موثق

                            </span>

                        <?php else: ?>

                            <span class="premium-badge">

                                <i class="fas fa-exclamation-circle"></i>

                                غير موثق

                            </span>

                        <?php endif; ?>

                    </div>

                </div>

 

                <!-- قائمة التنقل الفاخرة -->

                <nav class="luxury-nav">

                    <a href="dashboard.php" class="nav-item">

                        <i class="fas fa-home"></i>

                        <span>الرئيسية</span>

                    </a>

                    <a href="auctions.php" class="nav-item">

                        <i class="fas fa-gavel"></i>

                        <span>المزادات</span>

                    </a>

                    <a href="store.php" class="nav-item">

                        <i class="fas fa-store"></i>

                        <span>المتجر</span>

                    </a>

                    <a href="wallet.php" class="nav-item">

                        <i class="fas fa-wallet"></i>

                        <span>المحفظة</span>

                    </a>

                    <a href="notifications.php" class="nav-item notification-btn">

                        <i class="fas fa-bell"></i>

                        <span>الإشعارات</span>

                        <?php if ($unread_count > 0): ?>

                            <span class="notification-dot"><?php echo $unread_count; ?></span>

                        <?php endif; ?>

                    </a>

                    <a href="profile.php" class="nav-item active">

                        <i class="fas fa-user-cog"></i>

                        <span>الملف</span>

                    </a>

                    <a href="auth/logout.php" class="nav-item logout-btn">

                        <i class="fas fa-power-off"></i>

                        <span>خروج</span>

                    </a>

                </nav>

            </div>

        </div>

    </header>

 

    <!-- المحتوى الرئيسي -->

    <main class="main-content">

        <div class="container">

            <!-- بطاقة الملف الشخصي الفاخرة -->

            <div class="profile-card-3d" data-aos="fade-up">

                <div class="profile-bg-animation"></div>

                <div class="profile-avatar-3d">

                    <i class="fas fa-user"></i>

                </div>

                <div class="profile-name-3d">

                    <?php echo htmlspecialchars($user['username']); ?>

                </div>

                <div class="profile-email-3d">

                    <i class="fas fa-envelope"></i>

                    <?php echo htmlspecialchars($user['email']); ?>

                </div>

                <?php if ($user['kyc_status'] == 'verified'): ?>

                    <span class="badge-3d badge-success">

                        <i class="fas fa-shield-check"></i>

                        حساب موثق

                    </span>

                <?php endif; ?>

            </div>

 

            <!-- رسائل النجاح والخطأ -->

            <?php if ($success): ?>

                <div class="premium-alert success" data-aos="slide-right">

                    <div class="alert-icon-3d success">

                        <i class="fas fa-check-circle"></i>

                    </div>

                    <div class="alert-message">

                        <?php echo $success; ?>

                    </div>

                </div>

            <?php endif; ?>

 

            <?php if (!empty($errors)): ?>

                <div class="premium-alert danger" data-aos="slide-left">

                    <div class="alert-icon-3d danger">

                        <i class="fas fa-exclamation-circle"></i>

                    </div>

                    <div class="alert-message">

                        <?php foreach ($errors as $error): ?>

                            <div><?php echo $error; ?></div>

                        <?php endforeach; ?>

                    </div>

                </div>

            <?php endif; ?>

 

            <!-- شبكة البطاقات -->

            <div class="cards-grid">

                <!-- المعلومات الشخصية -->

                <div class="card-3d" style="--gradient: var(--ocean-gradient);" data-aos="zoom-in">

                    <div class="card-header-3d">

                        <div class="card-icon-3d" style="background: var(--ocean-gradient);">

                            <i class="fas fa-user-circle"></i>

                        </div>

                        <h2 class="card-title-3d">المعلومات الشخصية</h2>

                    </div>

                    <div class="info-grid-3d">

                        <div class="info-item-3d">

                            <div class="info-label-3d">

                                <i class="fas fa-user"></i>

                                اسم المستخدم

                            </div>

                            <div class="info-value-3d"><?php echo htmlspecialchars($user['username']); ?></div>

                        </div>

 

                        <div class="info-item-3d">

                            <div class="info-label-3d">

                                <i class="fas fa-envelope"></i>

                                البريد الإلكتروني

                            </div>

                            <div class="info-value-3d"><?php echo htmlspecialchars($user['email']); ?></div>

                        </div>

 

                        <div class="info-item-3d">

                            <div class="info-label-3d">

                                <i class="fas fa-phone"></i>

                                رقم الهاتف

                            </div>

                            <div class="info-value-3d"><?php echo htmlspecialchars($user['phone']); ?></div>

                        </div>

 

                        <div class="info-item-3d">

                            <div class="info-label-3d">

                                <i class="fas fa-calendar-alt"></i>

                                تاريخ التسجيل

                            </div>

                            <div class="info-value-3d"><?php echo date('Y-m-d', strtotime($user['created_at'])); ?></div>

                        </div>

                    </div>

                </div>

 

                <!-- حالة الحساب -->

                <div class="card-3d" style="--gradient: var(--emerald-gradient);" data-aos="zoom-in">

                    <div class="card-header-3d">

                        <div class="card-icon-3d" style="background: var(--emerald-gradient);">

                            <i class="fas fa-shield-alt"></i>

                        </div>

                        <h2 class="card-title-3d">حالة الحساب</h2>

                    </div>

                    <div class="info-grid-3d">

                        <div class="info-item-3d">

                            <div class="info-label-3d">

                                <i class="fas fa-check-circle"></i>

                                حالة الحساب

                            </div>

                            <div class="info-value-3d">

                                <?php if ($user['status'] == 'active'): ?>

                                    <span class="badge-3d badge-success">

                                        <i class="fas fa-check"></i>

                                        نشط

                                    </span>

                                <?php elseif ($user['status'] == 'suspended'): ?>

                                    <span class="badge-3d badge-danger">

                                        <i class="fas fa-ban"></i>

                                        موقوف

                                    </span>

                                <?php else: ?>

                                    <span class="badge-3d badge-warning">

                                        <i class="fas fa-clock"></i>

                                        معلق

                                    </span>

                                <?php endif; ?>

                            </div>

                        </div>

 

                        <div class="info-item-3d">

                            <div class="info-label-3d">

                                <i class="fas fa-id-card"></i>

                                حالة التوثيق

                            </div>

                            <div class="info-value-3d">

                                <?php if ($user['kyc_status'] == 'verified'): ?>

                                    <span class="badge-3d badge-success">

                                        <i class="fas fa-check-circle"></i>

                                        موثق

                                    </span>

                                <?php elseif ($user['kyc_status'] == 'pending'): ?>

                                    <span class="badge-3d badge-warning">

                                        <i class="fas fa-hourglass-half"></i>

                                        قيد المراجعة

                                    </span>

                                <?php elseif ($user['kyc_status'] == 'rejected'): ?>

                                    <span class="badge-3d badge-danger">

                                        <i class="fas fa-times-circle"></i>

                                        مرفوض

                                    </span>

                                <?php else: ?>

                                    <span class="badge-3d badge-danger">

                                        <i class="fas fa-exclamation-triangle"></i>

                                        غير موثق

                                    </span>

                                <?php endif; ?>

                            </div>

                        </div>

 

                        <div class="info-item-3d">

                            <div class="info-label-3d">

                                <i class="fas fa-user-tag"></i>

                                نوع الحساب

                            </div>

                            <div class="info-value-3d">

                                <?php echo $user['role'] === 'admin' ? 'مدير' : 'مستخدم'; ?>

                            </div>

                        </div>

                    </div>

                </div>

 

                <!-- معلومات المحفظة -->

                <div class="card-3d" style="--gradient: var(--mystic-gradient);" data-aos="zoom-in">

                    <div class="card-header-3d">

                        <div class="card-icon-3d" style="background: var(--mystic-gradient);">

                            <i class="fas fa-wallet"></i>

                        </div>

                        <h2 class="card-title-3d">معلومات المحفظة</h2>

                    </div>

                    <div class="info-grid-3d">

                        <div class="info-item-3d">

                            <div class="info-label-3d">

                                <i class="fas fa-dollar-sign"></i>

                                الرصيد المتاح

                            </div>

                            <div class="info-value-3d" style="color: #13f1fc;">

                                $<?php echo number_format($wallet['available_balance'], 2); ?>

                            </div>

                        </div>

 

                        <div class="info-item-3d">

                            <div class="info-label-3d">

                                <i class="fas fa-lock"></i>

                                الرصيد المحجوز

                            </div>

                            <div class="info-value-3d" style="color: #f9d423;">

                                $<?php echo number_format($wallet['frozen_balance'], 2); ?>

                            </div>

                        </div>

 

                        <div class="info-item-3d">

                            <div class="info-label-3d">

                                <i class="fas fa-hourglass-half"></i>

                                رصيد المبيعات المعلق

                            </div>

                            <div class="info-value-3d" style="color: #ec77ab;">

                                $<?php echo number_format($wallet['sales_hold_balance'], 2); ?>

                            </div>

                        </div>

                    </div>

                </div>

 

                <!-- الإحصائيات -->

                <div class="card-3d" style="--gradient: var(--fire-gradient);" data-aos="zoom-in">

                    <div class="card-header-3d">

                        <div class="card-icon-3d" style="background: var(--fire-gradient);">

                            <i class="fas fa-chart-bar"></i>

                        </div>

                        <h2 class="card-title-3d">الإحصائيات</h2>

                    </div>

                    <div class="stats-card-3d">

                        <div class="stat-box-3d">

                            <div class="stat-value-3d"><?php echo $total_auctions; ?></div>

                            <div class="stat-label-3d">إجمالي المزادات</div>

                        </div>

 

                        <div class="stat-box-3d">

                            <div class="stat-value-3d"><?php echo $total_bids; ?></div>

                            <div class="stat-label-3d">إجمالي المزايدات</div>

                        </div>

 

                        <div class="stat-box-3d">

                            <div class="stat-value-3d">

                                <?php echo $rating_data['count'] > 0 ? number_format($rating_data['avg'], 1) : '-'; ?>

                                <?php if ($rating_data['count'] > 0): ?>

                                    <i class="fas fa-star" style="color: #ffd700; font-size: 1.2rem;"></i>

                                <?php endif; ?>

                            </div>

                            <div class="stat-label-3d">

                                التقييم (<?php echo $rating_data['count']; ?>)

                            </div>

                        </div>

                    </div>

                </div>

 

                <!-- نموذج تغيير كلمة المرور -->

                <div class="card-3d password-form-3d" style="--gradient: var(--cosmic-gradient);" data-aos="fade-up">

                    <div class="card-header-3d">

                        <div class="card-icon-3d" style="background: var(--cosmic-gradient);">

                            <i class="fas fa-key"></i>

                        </div>

                        <h2 class="card-title-3d">تغيير كلمة السر</h2>

                    </div>

                    <form method="POST" action="">

                        <div class="form-group-3d">

                            <label class="form-label-3d">

                                <i class="fas fa-lock"></i>

                                كلمة السر الحالية

                            </label>

                            <input

                                type="password"

                                name="current_password"

                                class="form-input-3d"

                                placeholder="أدخل كلمة السر الحالية"

                                required

                            >

                        </div>

 

                        <div class="form-group-3d">

                            <label class="form-label-3d">

                                <i class="fas fa-lock"></i>

                                كلمة السر الجديدة

                            </label>

                            <input

                                type="password"

                                name="new_password"

                                class="form-input-3d"

                                placeholder="أدخل كلمة السر الجديدة"

                                required

                            >

                        </div>

 

                        <div class="form-group-3d">

                            <label class="form-label-3d">

                                <i class="fas fa-lock"></i>

                                تأكيد كلمة السر الجديدة

                            </label>

                            <input

                                type="password"

                                name="confirm_password"

                                class="form-input-3d"

                                placeholder="أعد إدخال كلمة السر الجديدة"

                                required

                            >

                        </div>

 

                        <button type="submit" name="change_password" class="btn-3d">

                            <i class="fas fa-save"></i>

                            تحديث كلمة السر

                        </button>

                    </form>

                </div>

            </div>

        </div>

    </main>

 

    <!-- Scripts -->

    <script src="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.js"></script>

 

    <script>

        // تهيئة AOS

        AOS.init({

            duration: 1200,

            once: false,

            offset: 100

        });

 

        // إنشاء الجسيمات المتوهجة

        function createParticles() {

            const container = document.getElementById('particles');

            const particleCount = 60;

 

            for (let i = 0; i < particleCount; i++) {

                const particle = document.createElement('div');

                particle.className = 'particle';

                particle.style.left = Math.random() * 100 + '%';

                particle.style.animationDelay = Math.random() * 25 + 's';

                particle.style.animationDuration = (20 + Math.random() * 10) + 's';

                container.appendChild(particle);

            }

        }

 

        // إنشاء النجوم المتلألئة

        function createStars() {

            const container = document.getElementById('stars');

            const starCount = 100;

 

            for (let i = 0; i < starCount; i++) {

                const star = document.createElement('div');

                star.className = 'star';

                star.style.left = Math.random() * 100 + '%';

                star.style.top = Math.random() * 100 + '%';

                star.style.animationDelay = Math.random() * 5 + 's';

                container.appendChild(star);

            }

        }

 

        // إخفاء الرسائل تلقائياً بعد 5 ثوان

        function hideAlerts() {

            const alerts = document.querySelectorAll('.premium-alert');

            alerts.forEach(alert => {

                setTimeout(() => {

                    alert.style.transition = 'opacity 0.5s';

                    alert.style.opacity = '0';

                    setTimeout(() => alert.remove(), 500);

                }, 5000);

            });

        }

 

        // تفعيل كل شيء عند تحميل الصفحة

        document.addEventListener('DOMContentLoaded', function() {

            createParticles();

            createStars();

            hideAlerts();

 

            // إضافة تأثير parallax للماوس

            document.addEventListener('mousemove', (e) => {

                const x = e.clientX / window.innerWidth;

                const y = e.clientY / window.innerHeight;

 

                document.querySelectorAll('.card-3d').forEach(card => {

                    const speed = 2;

                    const xOffset = (x - 0.5) * speed;

                    const yOffset = (y - 0.5) * speed;

 

                    if (card.matches(':hover')) {

                        card.style.transform = `perspective(1000px) rotateY(${xOffset}deg) rotateX(${-yOffset}deg) translateY(-10px)`;

                    }

                });

            });

        });

    </script>

</body>

</html>