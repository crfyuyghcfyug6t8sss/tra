<?php

require_once 'config/database.php';

require_once 'includes/functions.php';

 

if (!isLoggedIn()) {

    redirect('auth/login.php');

}

 

$user_id = $_SESSION['user_id'];

 

// تحديث الإشعارات كمقروءة إذا تم الضغط

if (isset($_GET['mark_read']) && $_GET['mark_read'] == 'all') {

    $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");

    $stmt->execute([$user_id]);

    redirect('notifications.php');

}

 

if (isset($_GET['mark_read']) && is_numeric($_GET['mark_read'])) {

    $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");

    $stmt->execute([$_GET['mark_read'], $user_id]);

    redirect('notifications.php');

}

 

// جلب الإشعارات

$stmt = $conn->prepare("

    SELECT * FROM notifications

    WHERE user_id = ?

    ORDER BY created_at DESC

    LIMIT 50

");

$stmt->execute([$user_id]);

$notifications = $stmt->fetchAll();

 

// تصنيف الإشعارات

$unread = array_filter($notifications, fn($n) => !$n['is_read']);

$read = array_filter($notifications, fn($n) => $n['is_read']);

 

// جلب معلومات المستخدم للهيدر

$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");

$stmt->execute([$user_id]);

$user = $stmt->fetch();

 

// عدد الإشعارات غير المقروءة للهيدر

$unread_count = count($unread);

?>

<!DOCTYPE html>

<html lang="ar" dir="rtl">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>🔔 الإشعارات الفاخرة - مزادات السيارات الحصرية</title>

 

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

 

        /* خلفية ديناميكية متحركة */

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

 

        /* 🌌 شبكة ثلاثية الأبعاد */

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

 

        /* 🎆 جسيمات متوهجة */

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

 

        /* 🌟 نجوم متلألئة */

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

 

        /* 🎭 Header الفاخر */

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

 

        /* 🎨 شعار متحرك */

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

 

        /* 🎭 معلومات المستخدم الفاخرة */

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

 

        /* 🏆 شارات التوثيق */

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

 

        /* 🎯 قائمة التنقل الفاخرة */

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

 

        /* 🔔 إشعارات متحركة */

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

 

        /* 📊 المحتوى الرئيسي */

        .main-content {

            padding: 40px 0;

            position: relative;

        }

 

        /* 🎉 بانر عنوان الصفحة */

        .page-header-banner {

            background: var(--glass-white);

            backdrop-filter: blur(30px);

            -webkit-backdrop-filter: blur(30px);

            border: 1px solid var(--glass-border);

            border-radius: 30px;

            padding: 40px 50px;

            margin-bottom: 40px;

            position: relative;

            overflow: hidden;

            animation: bannerSlideIn 1s ease-out;

        }

 

        @keyframes bannerSlideIn {

            from {

                opacity: 0;

                transform: translateY(30px) scale(0.95);

            }

            to {

                opacity: 1;

                transform: translateY(0) scale(1);

            }

        }

 

        .page-header-bg {

            position: absolute;

            top: -50%;

            left: -50%;

            width: 200%;

            height: 200%;

            background: radial-gradient(circle at center, rgba(102, 126, 234, 0.1) 0%, transparent 50%);

            animation: headerRotate 30s linear infinite;

        }

 

        @keyframes headerRotate {

            from { transform: rotate(0deg) scale(1); }

            to { transform: rotate(360deg) scale(1.1); }

        }

 

        .page-header-content {

            position: relative;

            z-index: 1;

            display: flex;

            justify-content: space-between;

            align-items: center;

        }

 

        .page-title-section h1 {

            font-size: 2.5rem;

            font-weight: 900;

            margin-bottom: 10px;

            background: linear-gradient(135deg, #667eea, #764ba2, #f093fb);

            background-size: 200% 200%;

            -webkit-background-clip: text;

            -webkit-text-fill-color: transparent;

            animation: titleGradient 5s ease infinite;

        }

 

        @keyframes titleGradient {

            0% { background-position: 0% 50%; }

            50% { background-position: 100% 50%; }

            100% { background-position: 0% 50%; }

        }

 

        .page-subtitle {

            color: rgba(255, 255, 255, 0.7);

            font-size: 1.1rem;

            font-weight: 500;

        }

 

        .mark-all-read-btn {

            padding: 15px 30px;

            background: var(--aurora-gradient);

            color: white;

            border: none;

            border-radius: 15px;

            font-weight: 700;

            font-size: 1rem;

            text-decoration: none;

            display: inline-flex;

            align-items: center;

            gap: 10px;

            transition: var(--transition);

            box-shadow: 0 10px 30px rgba(0, 201, 255, 0.3);

            position: relative;

            overflow: hidden;

        }

 

        .mark-all-read-btn::before {

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

 

        .mark-all-read-btn:hover {

            transform: translateY(-3px);

            box-shadow: 0 15px 40px rgba(0, 201, 255, 0.4);

        }

 

        .mark-all-read-btn:hover::before {

            left: 100%;

        }

 

        /* 🎯 التبويبات الفاخرة */

        .tabs-container {

            background: var(--glass-white);

            backdrop-filter: blur(20px);

            border: 1px solid var(--glass-border);

            border-radius: 25px;

            padding: 10px;

            margin-bottom: 30px;

            display: flex;

            gap: 10px;

            position: relative;

            overflow: hidden;

        }

 

        .tab-btn {

            flex: 1;

            padding: 15px 25px;

            background: transparent;

            border: none;

            border-radius: 18px;

            color: rgba(255, 255, 255, 0.6);

            font-size: 1rem;

            font-weight: 700;

            cursor: pointer;

            transition: var(--transition);

            position: relative;

            z-index: 1;

        }

 

        .tab-btn.active {

            background: var(--royal-gradient);

            color: white;

            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.3);

        }

 

        .tab-btn:hover:not(.active) {

            color: rgba(255, 255, 255, 0.9);

            background: rgba(255, 255, 255, 0.05);

        }

 

        .tab-count {

            display: inline-flex;

            align-items: center;

            justify-content: center;

            min-width: 25px;

            height: 25px;

            padding: 0 8px;

            background: rgba(255, 255, 255, 0.2);

            border-radius: 12px;

            font-size: 0.85rem;

            margin-right: 8px;

        }

 

        .tab-btn.active .tab-count {

            background: rgba(255, 255, 255, 0.3);

        }

 

        /* 📬 بطاقات الإشعارات الفاخرة */

        .notifications-grid {

            display: flex;

            flex-direction: column;

            gap: 20px;

        }

 

        .notification-card {

            background: var(--glass-white);

            backdrop-filter: blur(20px);

            -webkit-backdrop-filter: blur(20px);

            border: 1px solid var(--glass-border);

            border-radius: 25px;

            padding: 25px;

            display: flex;

            gap: 20px;

            align-items: start;

            transition: var(--transition);

            position: relative;

            overflow: hidden;

            animation: cardSlideIn 0.8s ease-out backwards;

        }

 

        @keyframes cardSlideIn {

            from {

                opacity: 0;

                transform: translateX(50px);

            }

            to {

                opacity: 1;

                transform: translateX(0);

            }

        }

 

        .notification-card:hover {

            transform: translateX(-5px);

            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.2);

        }

 

        .notification-card.unread {

            border-right: 4px solid var(--primary);

            background: linear-gradient(135deg, rgba(102, 126, 234, 0.1), rgba(118, 75, 162, 0.1));

        }

 

        .notification-card.unread::before {

            content: '';

            position: absolute;

            top: 0;

            right: 0;

            width: 100%;

            height: 100%;

            background: radial-gradient(circle at top right, rgba(102, 126, 234, 0.15) 0%, transparent 70%);

            pointer-events: none;

        }

 

        /* 🎨 أيقونات الإشعارات */

        .notification-icon-3d {

            width: 70px;

            height: 70px;

            border-radius: 20px;

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 2rem;

            flex-shrink: 0;

            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);

            animation: iconFloat 3s ease-in-out infinite;

            position: relative;

        }

 

        @keyframes iconFloat {

            0%, 100% {

                transform: translateY(0) rotateZ(0deg);

            }

            25% {

                transform: translateY(-5px) rotateZ(-5deg);

            }

            75% {

                transform: translateY(5px) rotateZ(5deg);

            }

        }

 

        .icon-bid {

            background: var(--ocean-gradient);

            color: white;

        }

 

        .icon-outbid {

            background: var(--fire-gradient);

            color: white;

        }

 

        .icon-won {

            background: var(--aurora-gradient);

            color: white;

        }

 

        .icon-auction_end {

            background: var(--sunset-gradient);

            color: white;

        }

 

        .icon-system {

            background: var(--cosmic-gradient);

            color: white;

        }

 

        /* 📄 محتوى الإشعار */

        .notification-content {

            flex: 1;

            position: relative;

            z-index: 1;

        }

 

        .notification-title {

            font-size: 1.3rem;

            font-weight: 800;

            color: white;

            margin-bottom: 10px;

            line-height: 1.4;

        }

 

        .notification-message {

            color: rgba(255, 255, 255, 0.8);

            font-size: 1rem;

            line-height: 1.6;

            margin-bottom: 15px;

        }

 

        .notification-time {

            display: inline-flex;

            align-items: center;

            gap: 8px;

            padding: 8px 15px;

            background: rgba(255, 255, 255, 0.1);

            border-radius: 12px;

            color: rgba(255, 255, 255, 0.7);

            font-size: 0.9rem;

            font-weight: 600;

        }

 

        /* 🎬 إجراءات الإشعار */

        .notification-actions {

            display: flex;

            gap: 12px;

            margin-top: 20px;

            flex-wrap: wrap;

        }

 

        .action-btn {

            padding: 12px 25px;

            border-radius: 15px;

            font-weight: 700;

            font-size: 0.95rem;

            text-decoration: none;

            display: inline-flex;

            align-items: center;

            gap: 8px;

            transition: var(--transition);

            border: none;

            cursor: pointer;

            position: relative;

            overflow: hidden;

        }

 

        .action-btn::before {

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

 

        .action-btn:hover::before {

            left: 100%;

        }

 

        .action-btn-primary {

            background: var(--royal-gradient);

            color: white;

            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.3);

        }

 

        .action-btn-primary:hover {

            transform: translateY(-2px);

            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);

        }

 

        .action-btn-outline {

            background: rgba(255, 255, 255, 0.1);

            color: white;

            border: 2px solid rgba(255, 255, 255, 0.3);

        }

 

        .action-btn-outline:hover {

            background: rgba(255, 255, 255, 0.2);

            transform: translateY(-2px);

        }

 

        /* 🎪 حالة فارغة */

        .empty-state {

            background: var(--glass-white);

            backdrop-filter: blur(20px);

            border: 1px solid var(--glass-border);

            border-radius: 30px;

            padding: 80px 40px;

            text-align: center;

            position: relative;

            overflow: hidden;

        }

 

        .empty-state::before {

            content: '';

            position: absolute;

            top: -50%;

            left: -50%;

            width: 200%;

            height: 200%;

            background: radial-gradient(circle at center, rgba(102, 126, 234, 0.05) 0%, transparent 70%);

            animation: emptyRotate 20s linear infinite;

        }

 

        @keyframes emptyRotate {

            from { transform: rotate(0deg); }

            to { transform: rotate(360deg); }

        }

 

        .empty-state-content {

            position: relative;

            z-index: 1;

        }

 

        .empty-state-icon {

            font-size: 6rem;

            margin-bottom: 25px;

            animation: emptyIconFloat 3s ease-in-out infinite;

        }

 

        @keyframes emptyIconFloat {

            0%, 100% { transform: translateY(0); }

            50% { transform: translateY(-10px); }

        }

 

        .empty-state h3 {

            font-size: 2rem;

            font-weight: 800;

            color: white;

            margin-bottom: 15px;

        }

 

        .empty-state p {

            color: rgba(255, 255, 255, 0.7);

            font-size: 1.1rem;

            line-height: 1.6;

        }

 

        /* 🔙 زر العودة */

        .back-link {

            display: inline-flex;

            align-items: center;

            gap: 10px;

            padding: 15px 30px;

            background: var(--glass-white);

            backdrop-filter: blur(10px);

            border: 1px solid var(--glass-border);

            border-radius: 15px;

            color: white;

            text-decoration: none;

            font-weight: 600;

            font-size: 1rem;

            transition: var(--transition);

            margin-top: 40px;

        }

 

        .back-link:hover {

            background: var(--royal-gradient);

            transform: translateX(5px);

            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);

        }

 

        /* 📱 استجابة الجوال */

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

 

            .page-header-banner {

                padding: 30px 25px;

            }

 

            .page-header-content {

                flex-direction: column;

                gap: 20px;

                text-align: center;

            }

 

            .page-title-section h1 {

                font-size: 1.8rem;

            }

 

            .tabs-container {

                flex-direction: column;

            }

 

            .notification-card {

                flex-direction: column;

            }

 

            .notification-icon-3d {

                width: 60px;

                height: 60px;

                font-size: 1.8rem;

            }

 

            .notification-title {

                font-size: 1.1rem;

            }

 

            .notification-actions {

                flex-direction: column;

            }

 

            .action-btn {

                width: 100%;

                justify-content: center;

            }

        }

 

        /* إخفاء وإظهار التبويبات */

        .tab-content {

            display: none;

        }

 

        .tab-content.active {

            display: block;

        }

 

        /* 🎨 تأثيرات إضافية */

        .shimmer-effect {

            position: relative;

            overflow: hidden;

        }

 

        .shimmer-effect::after {

            content: '';

            position: absolute;

            top: 0;

            left: -100%;

            width: 100%;

            height: 100%;

            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);

            animation: shimmer 3s infinite;

        }

 

        @keyframes shimmer {

            0% { left: -100%; }

            100% { left: 100%; }

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

                    <a href="index.php" class="nav-item">

                        <i class="fas fa-home"></i>

                        <span>الرئيسية</span>

                    </a>

                    <a href="dashboard.php" class="nav-item">

                        <i class="fas fa-th-large"></i>

                        <span>لوحة التحكم</span>

                    </a>

                    <a href="auctions.php" class="nav-item">

                        <i class="fas fa-gavel"></i>

                        <span>المزادات</span>

                    </a>

                    <a href="wallet.php" class="nav-item">

                        <i class="fas fa-wallet"></i>

                        <span>المحفظة</span>

                    </a>

                    <a href="notifications.php" class="nav-item active notification-btn">

                        <i class="fas fa-bell"></i>

                        <span>الإشعارات</span>

                        <?php if ($unread_count > 0): ?>

                            <span class="notification-dot"><?php echo $unread_count; ?></span>

                        <?php endif; ?>

                    </a>

                    <a href="profile.php" class="nav-item">

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

            <!-- بانر عنوان الصفحة -->

            <div class="page-header-banner" data-aos="fade-up">

                <div class="page-header-bg"></div>

                <div class="page-header-content">

                    <div class="page-title-section">

                        <h1>🔔 الإشعارات</h1>

                        <?php if (count($unread) > 0): ?>

                            <p class="page-subtitle">لديك <?php echo count($unread); ?> إشعار جديد في انتظارك</p>

                        <?php else: ?>

                            <p class="page-subtitle">جميع إشعاراتك مقروءة</p>

                        <?php endif; ?>

                    </div>

                    <?php if (count($unread) > 0): ?>

                        <a href="?mark_read=all" class="mark-all-read-btn shimmer-effect">

                            <i class="fas fa-check-double"></i>

                            تحديد الكل كمقروء

                        </a>

                    <?php endif; ?>

                </div>

            </div>

 

            <!-- التبويبات الفاخرة -->

            <div class="tabs-container" data-aos="fade-up">

                <button class="tab-btn active" onclick="switchTab('all')">

                    <span class="tab-count"><?php echo count($notifications); ?></span>

                    جميع الإشعارات

                </button>

                <button class="tab-btn" onclick="switchTab('unread')">

                    <span class="tab-count"><?php echo count($unread); ?></span>

                    غير المقروءة

                </button>

            </div>

 

            <!-- تبويب جميع الإشعارات -->

            <div id="all" class="tab-content active">

                <?php if (empty($notifications)): ?>

                    <div class="empty-state" data-aos="zoom-in">

                        <div class="empty-state-content">

                            <div class="empty-state-icon">🔔</div>

                            <h3>لا توجد إشعارات</h3>

                            <p>سنرسل لك إشعارات فورية عن جميع نشاطاتك في المزادات والمشتريات</p>

                        </div>

                    </div>

                <?php else: ?>

                    <div class="notifications-grid">

                        <?php

                        $delay = 0;

                        foreach ($notifications as $notif):

                            $delay += 100;

                        ?>

                            <div class="notification-card <?php echo !$notif['is_read'] ? 'unread' : ''; ?>"

                                 data-aos="fade-right"

                                 data-aos-delay="<?php echo $delay; ?>">

                                <div class="notification-icon-3d icon-<?php echo $notif['type']; ?>">

                                    <?php

                                    $icons = [

                                        'bid' => '🔨',

                                        'outbid' => '⚠️',

                                        'won' => '🏆',

                                        'auction_end' => '⏰',

                                        'system' => '📢'

                                    ];

                                    echo $icons[$notif['type']] ?? '📬';

                                    ?>

                                </div>

                                <div class="notification-content">

                                    <div class="notification-title"><?php echo htmlspecialchars($notif['title']); ?></div>

                                    <div class="notification-message"><?php echo htmlspecialchars($notif['message']); ?></div>

                                    <div class="notification-time">

                                        <i class="far fa-clock"></i>

                                        <?php

                                        $time_diff = time() - strtotime($notif['created_at']);

                                        if ($time_diff < 60) echo 'الآن';

                                        elseif ($time_diff < 3600) echo floor($time_diff / 60) . ' دقيقة';

                                        elseif ($time_diff < 86400) echo floor($time_diff / 3600) . ' ساعة';

                                        else echo floor($time_diff / 86400) . ' يوم';

                                        ?>

                                    </div>

                                    <div class="notification-actions">

                                        <?php if ($notif['reference_id']): ?>

                                            <a href="auction-details.php?id=<?php echo $notif['reference_id']; ?>" class="action-btn action-btn-primary">

                                                <i class="fas fa-eye"></i>

                                                عرض المزاد

                                            </a>

                                        <?php endif; ?>

                                        <?php if (!$notif['is_read']): ?>

                                            <a href="?mark_read=<?php echo $notif['id']; ?>" class="action-btn action-btn-outline">

                                                <i class="fas fa-check"></i>

                                                تحديد كمقروء

                                            </a>

                                        <?php endif; ?>

                                    </div>

                                </div>

                            </div>

                        <?php endforeach; ?>

                    </div>

                <?php endif; ?>

            </div>

 

            <!-- تبويب الإشعارات غير المقروءة -->

            <div id="unread" class="tab-content">

                <?php if (empty($unread)): ?>

                    <div class="empty-state" data-aos="zoom-in">

                        <div class="empty-state-content">

                            <div class="empty-state-icon">✅</div>

                            <h3>لا توجد إشعارات جديدة</h3>

                            <p>رائع! جميع إشعاراتك مقروءة ومتابعة بنجاح</p>

                        </div>

                    </div>

                <?php else: ?>

                    <div class="notifications-grid">

                        <?php

                        $delay = 0;

                        foreach ($unread as $notif):

                            $delay += 100;

                        ?>

                            <div class="notification-card unread"

                                 data-aos="fade-right"

                                 data-aos-delay="<?php echo $delay; ?>">

                                <div class="notification-icon-3d icon-<?php echo $notif['type']; ?>">

                                    <?php

                                    $icons = [

                                        'bid' => '🔨',

                                        'outbid' => '⚠️',

                                        'won' => '🏆',

                                        'auction_end' => '⏰',

                                        'system' => '📢'

                                    ];

                                    echo $icons[$notif['type']] ?? '📬';

                                    ?>

                                </div>

                                <div class="notification-content">

                                    <div class="notification-title"><?php echo htmlspecialchars($notif['title']); ?></div>

                                    <div class="notification-message"><?php echo htmlspecialchars($notif['message']); ?></div>

                                    <div class="notification-time">

                                        <i class="far fa-clock"></i>

                                        <?php

                                        $time_diff = time() - strtotime($notif['created_at']);

                                        if ($time_diff < 60) echo 'الآن';

                                        elseif ($time_diff < 3600) echo floor($time_diff / 60) . ' دقيقة';

                                        elseif ($time_diff < 86400) echo floor($time_diff / 3600) . ' ساعة';

                                        else echo floor($time_diff / 86400) . ' يوم';

                                        ?>

                                    </div>

                                    <div class="notification-actions">

                                        <?php if ($notif['reference_id']): ?>

                                            <a href="auction-details.php?id=<?php echo $notif['reference_id']; ?>" class="action-btn action-btn-primary">

                                                <i class="fas fa-eye"></i>

                                                عرض المزاد

                                            </a>

                                        <?php endif; ?>

                                        <a href="?mark_read=<?php echo $notif['id']; ?>" class="action-btn action-btn-outline">

                                            <i class="fas fa-check"></i>

                                            تحديد كمقروء

                                        </a>

                                    </div>

                                </div>

                            </div>

                        <?php endforeach; ?>

                    </div>

                <?php endif; ?>

            </div>

 

            <!-- زر العودة -->

            <div style="text-align: center; margin-top: 40px;" data-aos="fade-up">

                <a href="dashboard.php" class="back-link">

                    <i class="fas fa-arrow-right"></i>

                    العودة للوحة التحكم

                </a>

            </div>

        </div>

    </main>

 

    <!-- Language Switcher -->

    <div style="position: fixed; top: 20px; left: 20px; z-index: 1000;">

        <?php include 'includes/lang-switcher.php'; ?>

    </div>

    <script src="js/auto-translate.js"></script>

 

    <!-- Scripts -->

    <script src="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.js"></script>

 

    <script>

        // تهيئة AOS

        AOS.init({

            duration: 1000,

            once: false,

            offset: 100

        });

 

        // إنشاء الجسيمات المتوهجة

        function createParticles() {

            const container = document.getElementById('particles');

            const particleCount = 50;

 

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

            const starCount = 80;

 

            for (let i = 0; i < starCount; i++) {

                const star = document.createElement('div');

                star.className = 'star';

                star.style.left = Math.random() * 100 + '%';

                star.style.top = Math.random() * 100 + '%';

                star.style.animationDelay = Math.random() * 5 + 's';

                container.appendChild(star);

            }

        }

 

        // تبديل التبويبات

        function switchTab(tabName) {

            // إخفاء جميع التبويبات

            document.querySelectorAll('.tab-content').forEach(content => {

                content.classList.remove('active');

            });

 

            // إلغاء تفعيل جميع الأزرار

            document.querySelectorAll('.tab-btn').forEach(btn => {

                btn.classList.remove('active');

            });

 

            // تفعيل التبويب المحدد

            document.getElementById(tabName).classList.add('active');

            event.target.classList.add('active');

 

            // إعادة تهيئة AOS للعناصر الجديدة

            AOS.refresh();

        }

 

        // تفعيل كل شيء عند تحميل الصفحة

        document.addEventListener('DOMContentLoaded', function() {

            createParticles();

            createStars();

 

            // إضافة تأثير parallax للماوس

            document.addEventListener('mousemove', (e) => {

                const x = e.clientX / window.innerWidth;

                const y = e.clientY / window.innerHeight;

 

                document.querySelectorAll('.notification-card').forEach(card => {

                    const speed = 3;

                    const xOffset = (x - 0.5) * speed;

                    const yOffset = (y - 0.5) * speed;

 

                    card.style.transform = `translateX(${xOffset}px) translateY(${yOffset}px)`;

                });

            });

        });

    </script>

</body>

</html>