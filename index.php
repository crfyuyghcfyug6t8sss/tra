<?php

require_once 'config/database.php';

require_once 'includes/functions.php';

// ✅ تحويل تلقائي للداشبورد إذا كان المستخدم مسجل دخول

if (isLoggedIn()) {

    header("Location: dashboard.php");

    exit;

}

 

// جلب المزادات النشطة

$stmt = $conn->query("

    SELECT

        a.*,

        v.*,

        u.username as seller_name,

        (SELECT image_path FROM vehicle_images WHERE vehicle_id = v.id AND is_primary = TRUE LIMIT 1) as main_image,

        (SELECT COUNT(*) FROM vehicle_images WHERE vehicle_id = v.id) as images_count

    FROM auctions a

    JOIN vehicles v ON a.vehicle_id = v.id

    JOIN users u ON a.seller_id = u.id

    WHERE a.status = 'active' AND a.end_time > NOW()

    ORDER BY a.created_at DESC

    LIMIT 6

");

$auctions = $stmt->fetchAll();

 

// إحصائيات الموقع

$stmt = $conn->query("SELECT COUNT(*) as total FROM users");

$total_users = $stmt->fetch()['total'];

 

$stmt = $conn->query("SELECT COUNT(*) as total FROM auctions WHERE status = 'completed'");

$total_completed = $stmt->fetch()['total'];

 

$stmt = $conn->query("SELECT COUNT(*) as total FROM auctions WHERE status = 'active'");

$total_active = $stmt->fetch()['total'];

 

$stmt = $conn->query("SELECT COUNT(DISTINCT seller_id) as total FROM auctions");

$total_sellers = $stmt->fetch()['total'];

?>

<!DOCTYPE html>

<html lang="ar" dir="rtl">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>🌟 BidOra - منصة مزادات السيارات الفاخرة</title>

 

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

 

        /* 🌌 خلفية ديناميكية متحركة */

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

            text-decoration: none;

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

 

        .logo-icon i {

            font-size: 3rem;

            background: var(--royal-gradient);

            -webkit-background-clip: text;

            -webkit-text-fill-color: transparent;

            filter: drop-shadow(0 10px 30px rgba(102, 126, 234, 0.4));

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

 

        .nav-item i {

            font-size: 1.1rem;

            position: relative;

            z-index: 1;

        }

 

        .nav-item span {

            position: relative;

            z-index: 1;

        }

 

        .cta-button {

            background: var(--fire-gradient);

            color: white;

            padding: 12px 28px;

            border: none;

            border-radius: 15px;

            font-weight: 700;

            cursor: pointer;

            transition: var(--transition);

            text-decoration: none;

            display: inline-flex;

            align-items: center;

            gap: 8px;

            box-shadow: 0 10px 30px rgba(249, 212, 35, 0.3);

        }

 

        .cta-button:hover {

            transform: translateY(-3px);

            box-shadow: 0 15px 40px rgba(249, 212, 35, 0.4);

        }

 

        /* 🎬 Hero Section الفاخر */

        .hero-section {

            min-height: 90vh;

            display: flex;

            align-items: center;

            justify-content: center;

            position: relative;

            overflow: hidden;

            padding: 100px 0 80px;

        }

 

        .hero-content-wrapper {

            text-align: center;

            position: relative;

            z-index: 2;

        }

 

        .hero-title {

            font-size: 4.5rem;

            font-weight: 900;

            margin-bottom: 25px;

            background: linear-gradient(135deg, #667eea, #764ba2, #f093fb, #f5576c);

            background-size: 300% 300%;

            -webkit-background-clip: text;

            -webkit-text-fill-color: transparent;

            animation: heroGradient 5s ease infinite;

            line-height: 1.2;

            text-shadow: 0 0 50px rgba(102, 126, 234, 0.5);

        }

 

        @keyframes heroGradient {

            0% { background-position: 0% 50%; }

            50% { background-position: 100% 50%; }

            100% { background-position: 0% 50%; }

        }

 

        .hero-subtitle {

            color: rgba(255, 255, 255, 0.9);

            font-size: 1.5rem;

            font-weight: 500;

            margin-bottom: 40px;

            text-shadow: 0 0 20px rgba(0, 0, 0, 0.3);

        }

 

        .hero-buttons {

            display: flex;

            gap: 20px;

            justify-content: center;

            flex-wrap: wrap;

        }

 

        .hero-btn {

            padding: 18px 40px;

            border-radius: 15px;

            font-weight: 700;

            font-size: 1.1rem;

            text-decoration: none;

            display: inline-flex;

            align-items: center;

            gap: 10px;

            transition: var(--transition);

            position: relative;

            overflow: hidden;

        }

 

        .hero-btn.primary {

            background: var(--fire-gradient);

            color: white;

            box-shadow: 0 15px 40px rgba(249, 212, 35, 0.4);

        }

 

        .hero-btn.primary:hover {

            transform: translateY(-5px) scale(1.05);

            box-shadow: 0 20px 50px rgba(249, 212, 35, 0.5);

        }

 

        .hero-btn.secondary {

            background: var(--glass-white);

            backdrop-filter: blur(10px);

            color: white;

            border: 2px solid var(--glass-border);

        }

 

        .hero-btn.secondary:hover {

            background: var(--royal-gradient);

            transform: translateY(-5px) scale(1.05);

            box-shadow: 0 20px 50px rgba(102, 126, 234, 0.4);

        }

 

        /* ✨ قسم المميزات الفاخر */

        .features-section {

            padding: 100px 0;

            position: relative;

        }

 

        .section-header-premium {

            text-align: center;

            margin-bottom: 60px;

        }

 

        .section-title {

            font-size: 3.5rem;

            font-weight: 900;

            color: white;

            margin-bottom: 20px;

            background: linear-gradient(135deg, #fff, #e0e7ff);

            -webkit-background-clip: text;

            -webkit-text-fill-color: transparent;

            text-shadow: 0 0 30px rgba(255, 255, 255, 0.3);

        }

 

        .section-subtitle {

            color: rgba(255, 255, 255, 0.7);

            font-size: 1.3rem;

            max-width: 700px;

            margin: 0 auto;

        }

 

        .features-grid {

            display: grid;

            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));

            gap: 30px;

        }

 

        .feature-card-3d {

            background: var(--glass-white);

            backdrop-filter: blur(20px);

            border: 1px solid var(--glass-border);

            border-radius: 25px;

            padding: 45px 35px;

            text-align: center;

            transition: var(--transition);

            position: relative;

            overflow: hidden;

            animation: cardFadeIn 0.8s ease-out backwards;

        }

 

        @keyframes cardFadeIn {

            from {

                opacity: 0;

                transform: translateY(30px) scale(0.9);

            }

            to {

                opacity: 1;

                transform: translateY(0) scale(1);

            }

        }

 

        .feature-card-3d:nth-child(1) { animation-delay: 0.1s; }

        .feature-card-3d:nth-child(2) { animation-delay: 0.2s; }

        .feature-card-3d:nth-child(3) { animation-delay: 0.3s; }

 

        .feature-card-3d::before {

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

 

        .feature-card-3d:hover {

            transform: translateY(-15px) scale(1.05);

            box-shadow: 0 25px 60px rgba(0, 0, 0, 0.3);

        }

 

        .feature-card-3d:hover::before {

            transform: scaleX(1);

        }

 

        .feature-icon-3d {

            width: 100px;

            height: 100px;

            border-radius: 25px;

            display: flex;

            align-items: center;

            justify-content: center;

            margin: 0 auto 25px;

            font-size: 3rem;

            color: white;

            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.2);

            animation: iconRotate 10s linear infinite;

        }

 

        @keyframes iconRotate {

            from { transform: rotateY(0deg); }

            to { transform: rotateY(360deg); }

        }

 

        .feature-card-3d h3 {

            color: white;

            font-size: 1.8rem;

            font-weight: 700;

            margin-bottom: 15px;

        }

 

        .feature-card-3d p {

            color: rgba(255, 255, 255, 0.7);

            font-size: 1.05rem;

            line-height: 1.6;

        }

 

        /* 🚗 قسم السيارات الفاخر */

        .cars-section {

            padding: 100px 0;

            position: relative;

        }

 

        .cars-grid {

            display: grid;

            grid-template-columns: repeat(auto-fit, minmax(380px, 1fr));

            gap: 35px;

        }

 

        .car-card-luxury {

            background: var(--glass-white);

            backdrop-filter: blur(20px);

            border: 1px solid var(--glass-border);

            border-radius: 30px;

            overflow: hidden;

            transition: var(--transition);

            position: relative;

            animation: carCardSlide 0.8s ease-out backwards;

        }

 

        @keyframes carCardSlide {

            from {

                opacity: 0;

                transform: translateX(-30px) rotateY(-15deg);

            }

            to {

                opacity: 1;

                transform: translateX(0) rotateY(0);

            }

        }

 

        .car-card-luxury:hover {

            transform: translateY(-15px) scale(1.03);

            box-shadow: 0 30px 70px rgba(0, 0, 0, 0.4);

        }

 

        .car-image-wrapper {

            position: relative;

            height: 280px;

            overflow: hidden;

            background: linear-gradient(135deg, rgba(102, 126, 234, 0.3), rgba(118, 75, 162, 0.3));

            display: flex;

            align-items: center;

            justify-content: center;

        }

 

        .car-image-wrapper img {

            width: 100%;

            height: 100%;

            object-fit: cover;

            transition: var(--transition);

        }

 

        .car-card-luxury:hover .car-image-wrapper img {

            transform: scale(1.15) rotate(2deg);

        }

 

        .car-image-wrapper i {

            font-size: 5rem;

            color: rgba(255, 255, 255, 0.3);

        }

 

        .car-badge-luxury {

            position: absolute;

            top: 20px;

            right: 20px;

            background: var(--fire-gradient);

            color: white;

            padding: 10px 20px;

            border-radius: 15px;

            font-weight: 700;

            font-size: 0.95rem;

            box-shadow: 0 5px 20px rgba(249, 212, 35, 0.4);

            animation: badgePulse 2s ease-in-out infinite;

        }

 

        @keyframes badgePulse {

            0%, 100% { transform: scale(1); }

            50% { transform: scale(1.05); }

        }

 

        .countdown-luxury {

            position: absolute;

            bottom: 20px;

            left: 20px;

            background: rgba(0, 0, 0, 0.85);

            backdrop-filter: blur(10px);

            color: white;

            padding: 12px 20px;

            border-radius: 15px;

            font-weight: 700;

            font-size: 1rem;

            border: 1px solid rgba(255, 255, 255, 0.2);

            display: flex;

            align-items: center;

            gap: 8px;

        }

 

        .car-content-luxury {

            padding: 30px;

        }

 

        .car-title-luxury {

            font-size: 1.6rem;

            font-weight: 700;

            color: white;

            margin-bottom: 15px;

        }

 

        .car-specs-luxury {

            display: flex;

            justify-content: space-between;

            margin-bottom: 20px;

            padding: 15px;

            background: rgba(255, 255, 255, 0.05);

            border-radius: 15px;

        }

 

        .car-spec-item {

            display: flex;

            flex-direction: column;

            align-items: center;

            gap: 5px;

        }

 

        .car-spec-item i {

            color: var(--warning);

            font-size: 1.2rem;

        }

 

        .car-spec-item span {

            color: rgba(255, 255, 255, 0.8);

            font-size: 0.9rem;

        }

 

        .car-price-luxury {

            font-size: 2.2rem;

            font-weight: 900;

            background: var(--fire-gradient);

            -webkit-background-clip: text;

            -webkit-text-fill-color: transparent;

            margin-bottom: 20px;

            text-shadow: 0 0 30px rgba(249, 212, 35, 0.5);

        }

 

        .car-actions-luxury {

            display: grid;

            grid-template-columns: 1fr 1fr;

            gap: 12px;

        }

 

        .car-btn {

            padding: 14px 20px;

            border-radius: 15px;

            font-weight: 700;

            text-decoration: none;

            text-align: center;

            transition: var(--transition);

            display: flex;

            align-items: center;

            justify-content: center;

            gap: 8px;

        }

 

        .car-btn.primary {

            background: var(--fire-gradient);

            color: white;

            box-shadow: 0 8px 25px rgba(249, 212, 35, 0.3);

        }

 

        .car-btn.primary:hover {

            transform: translateY(-3px);

            box-shadow: 0 12px 35px rgba(249, 212, 35, 0.4);

        }

 

        .car-btn.secondary {

            background: var(--glass-white);

            color: white;

            border: 2px solid var(--glass-border);

        }

 

        .car-btn.secondary:hover {

            background: var(--royal-gradient);

            border-color: transparent;

            transform: translateY(-3px);

        }

 

        /* 📊 قسم الإحصائيات الفاخر */

        .stats-section {

            padding: 100px 0;

            position: relative;

            overflow: hidden;

        }

 

        .stats-section::before {

            content: '';

            position: absolute;

            top: 0;

            left: 0;

            right: 0;

            bottom: 0;

            background: radial-gradient(ellipse at center, rgba(102, 126, 234, 0.2) 0%, transparent 70%);

            pointer-events: none;

        }

 

        .stats-grid {

            display: grid;

            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));

            gap: 30px;

        }

 

        .stat-card-luxury {

            background: var(--glass-white);

            backdrop-filter: blur(20px);

            border: 1px solid var(--glass-border);

            border-radius: 25px;

            padding: 40px 30px;

            text-align: center;

            transition: var(--transition);

            position: relative;

            overflow: hidden;

        }

 

        .stat-card-luxury::before {

            content: '';

            position: absolute;

            top: 0;

            left: 0;

            right: 0;

            height: 5px;

            background: var(--gradient);

        }

 

        .stat-card-luxury:hover {

            transform: translateY(-10px) scale(1.05);

            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.3);

        }

 

        .stat-icon-luxury {

            width: 80px;

            height: 80px;

            border-radius: 20px;

            margin: 0 auto 20px;

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 2.5rem;

            color: white;

            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);

        }

 

        .stat-number {

            font-size: 3.5rem;

            font-weight: 900;

            color: white;

            margin-bottom: 10px;

            text-shadow: 0 0 30px rgba(255, 255, 255, 0.3);

        }

 

        .stat-label {

            color: rgba(255, 255, 255, 0.8);

            font-size: 1.2rem;

            font-weight: 600;

        }

 

        /* 🎯 Footer الفاخر */

        .footer-luxury {

            background: var(--glass-white);

            backdrop-filter: blur(20px);

            border-top: 1px solid var(--glass-border);

            padding: 60px 0 30px;

            position: relative;

        }

 

        .footer-content {

            display: grid;

            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));

            gap: 40px;

            margin-bottom: 40px;

        }

 

        .footer-section h3 {

            font-size: 1.5rem;

            font-weight: 700;

            color: white;

            margin-bottom: 20px;

            background: var(--royal-gradient);

            -webkit-background-clip: text;

            -webkit-text-fill-color: transparent;

        }

 

        .footer-section p {

            color: rgba(255, 255, 255, 0.7);

            line-height: 1.6;

            margin-bottom: 15px;

        }

 

        .footer-section a {

            color: rgba(255, 255, 255, 0.7);

            text-decoration: none;

            display: block;

            margin-bottom: 12px;

            transition: var(--transition);

            padding: 8px 0;

        }

 

        .footer-section a:hover {

            color: white;

            transform: translateX(-5px);

            padding-right: 10px;

        }

 

        .social-links {

            display: flex;

            gap: 15px;

            margin-top: 20px;

        }

 

        .social-link {

            width: 50px;

            height: 50px;

            border-radius: 15px;

            background: var(--glass-white);

            border: 1px solid var(--glass-border);

            display: flex;

            align-items: center;

            justify-content: center;

            color: white;

            font-size: 1.3rem;

            transition: var(--transition);

            text-decoration: none;

        }

 

        .social-link:hover {

            background: var(--royal-gradient);

            transform: translateY(-5px);

            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.4);

        }

 

        .footer-bottom {

            border-top: 1px solid rgba(255, 255, 255, 0.1);

            padding-top: 30px;

            text-align: center;

        }

 

        .footer-bottom p {

            color: rgba(255, 255, 255, 0.6);

            font-size: 1rem;

        }

 

        /* 🔝 زر العودة للأعلى */

        .scroll-top {

            position: fixed;

            bottom: 30px;

            left: 30px;

            width: 60px;

            height: 60px;

            background: var(--fire-gradient);

            border-radius: 50%;

            display: flex;

            align-items: center;

            justify-content: center;

            color: white;

            font-size: 1.5rem;

            cursor: pointer;

            opacity: 0;

            visibility: hidden;

            transition: var(--transition);

            z-index: 999;

            box-shadow: 0 10px 30px rgba(249, 212, 35, 0.4);

        }

 

        .scroll-top.visible {

            opacity: 1;

            visibility: visible;

        }

 

        .scroll-top:hover {

            transform: translateY(-5px);

            box-shadow: 0 15px 40px rgba(249, 212, 35, 0.5);

        }

 

        /* 📱 استجابة الجوال */

        @media (max-width: 768px) {

            .header-content {

                flex-direction: column;

                gap: 20px;

            }

 

            .luxury-nav {

                flex-wrap: wrap;

                justify-content: center;

            }

 

            .hero-title {

                font-size: 2.5rem;

            }

 

            .hero-subtitle {

                font-size: 1.2rem;

            }

 

            .section-title {

                font-size: 2.5rem;

            }

 

            .features-grid,

            .cars-grid {

                grid-template-columns: 1fr;

            }

 

            .stats-grid {

                grid-template-columns: repeat(2, 1fr);

            }

 

            .footer-content {

                grid-template-columns: 1fr;

            }

        }

 

        /* 🎆 تأثيرات إضافية */

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

 

        .no-auctions-message {

            text-align: center;

            padding: 80px 20px;

            background: var(--glass-white);

            backdrop-filter: blur(20px);

            border: 1px solid var(--glass-border);

            border-radius: 30px;

        }

 

        .no-auctions-message i {

            font-size: 5rem;

            color: rgba(255, 255, 255, 0.3);

            margin-bottom: 20px;

        }

 

        .no-auctions-message h3 {

            color: white;

            font-size: 2rem;

            margin-bottom: 10px;

        }

 

        .no-auctions-message p {

            color: rgba(255, 255, 255, 0.7);

            font-size: 1.1rem;

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

                <a href="index.php" class="animated-logo">

                    <div class="logo-icon">

                        <i class="fas fa-car"></i>

                    </div>

                    <div class="logo-text">BidOra</div>

                </a>

 

                <!-- مبدل اللغة -->

                <div style="position: fixed; top: 20px; left: 20px; z-index: 1000;">

                    <?php include 'includes/lang-switcher.php'; ?>

                </div>

 

                <!-- قائمة التنقل الفاخرة -->

                <nav class="luxury-nav">

                    <a href="#home" class="nav-item">

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

                    <?php if (isLoggedIn()): ?>

                        <a href="dashboard.php" class="nav-item">

                            <i class="fas fa-th-large"></i>

                            <span>لوحة التحكم</span>

                        </a>

                        <a href="auth/logout.php" class="nav-item">

                            <i class="fas fa-power-off"></i>

                            <span>خروج</span>

                        </a>

                    <?php else: ?>

                        <a href="auth/login.php" class="nav-item">

                            <i class="fas fa-sign-in-alt"></i>

                            <span>دخول</span>

                        </a>

                    <?php endif; ?>

                </nav>

 

                <a href="<?php echo isLoggedIn() ? 'add-vehicle.php' : 'auth/login.php'; ?>" class="cta-button">

                    <i class="fas fa-plus-circle"></i>

                    ابدأ المزايدة

                </a>

            </div>

        </div>

    </header>

 

    <!-- Hero Section الفاخر -->

    <section class="hero-section" id="home">

        <div class="container">

            <div class="hero-content-wrapper" data-aos="fade-up">

                <h1 class="hero-title">منصة مزادات السيارات الفاخرة</h1>

                <p class="hero-subtitle">اكتشف عالم السيارات الاستثنائية واحصل على سيارة أحلامك بأفضل الأسعار</p>

                <div class="hero-buttons">

                    <a href="auth/register.php" class="hero-btn primary" data-aos="zoom-in" data-aos-delay="300">

                        <i class="fas fa-rocket"></i>

                        ابدأ الآن

                    </a>

                    <a href="auctions.php" class="hero-btn secondary" data-aos="zoom-in" data-aos-delay="500">

                        <i class="fas fa-search"></i>

                        جميع المزادات

                    </a>

                </div>

            </div>

        </div>

    </section>

 

    <!-- قسم المميزات الفاخر -->

    <section class="features-section" id="features">

        <div class="container">

            <div class="section-header-premium" data-aos="fade-up">

                <h2 class="section-title">لماذا تختار BidOra؟</h2>

                <p class="section-subtitle">نحن نقدم تجربة مزادات فريدة ومبتكرة مع أعلى معايير الأمان والشفافية</p>

            </div>

 

            <div class="features-grid">

                <div class="feature-card-3d" style="--gradient: var(--ocean-gradient);" data-aos="fade-up" data-aos-delay="100">

                    <div class="feature-icon-3d" style="background: var(--ocean-gradient);">

                        <i class="fas fa-shield-alt"></i>

                    </div>

                    <h3>أمان وثقة</h3>

                    <p>جميع المعاملات محمية بأحدث تقنيات الأمان مع ضمان شامل لحقوقك</p>

                </div>

 

                <div class="feature-card-3d" style="--gradient: var(--emerald-gradient);" data-aos="fade-up" data-aos-delay="200">

                    <div class="feature-icon-3d" style="background: var(--emerald-gradient);">

                        <i class="fas fa-clock"></i>

                    </div>

                    <h3>مزادات مباشرة</h3>

                    <p>مزايدة حية ومباشرة مع عدادات زمنية دقيقة وتحديثات فورية</p>

                </div>

 

                <div class="feature-card-3d" style="--gradient: var(--fire-gradient);" data-aos="fade-up" data-aos-delay="300">

                    <div class="feature-icon-3d" style="background: var(--fire-gradient);">

                        <i class="fas fa-car"></i>

                    </div>

                    <h3>تنوع هائل</h3>

                    <p>آلاف السيارات من جميع الماركات والفئات بأفضل الأسعار</p>

                </div>

            </div>

        </div>

    </section>

 

    <!-- قسم السيارات الفاخر -->

    <section class="cars-section" id="cars">

        <div class="container">

            <div class="section-header-premium" data-aos="fade-up">

                <h2 class="section-title">المزادات الحية</h2>

                <p class="section-subtitle">اكتشف أحدث السيارات المعروضة في المزادات الحية الآن</p>

            </div>

 

            <?php if (empty($auctions)): ?>

                <div class="no-auctions-message" data-aos="fade-up">

                    <i class="fas fa-inbox"></i>

                    <h3>لا توجد مزادات نشطة حالياً</h3>

                    <p>تحقق مرة أخرى قريباً لمشاهدة أحدث المزادات</p>

                </div>

            <?php else: ?>

                <div class="cars-grid">

                    <?php foreach ($auctions as $index => $auction): ?>

                    <div class="car-card-luxury" data-aos="fade-up" data-aos-delay="<?php echo $index * 100; ?>">

                        <div class="car-image-wrapper">

                            <?php if ($auction['main_image']): ?>

                                <img src="<?php echo htmlspecialchars($auction['main_image']); ?>" alt="<?php echo htmlspecialchars($auction['brand'] . ' ' . $auction['model']); ?>">

                            <?php else: ?>

                                <i class="fas fa-car"></i>

                            <?php endif; ?>

                            <div class="car-badge-luxury">

                                <i class="fas fa-fire"></i>

                                مزاد حي

                            </div>

                            <div class="countdown-luxury" data-endtime="<?php echo strtotime($auction['end_time']); ?>">

                                <i class="fas fa-clock"></i>

                                <span>جاري الحساب...</span>

                            </div>

                        </div>

                        <div class="car-content-luxury">

                            <h3 class="car-title-luxury">

                                <?php echo htmlspecialchars($auction['brand'] . ' ' . $auction['model'] . ' ' . $auction['year']); ?>

                            </h3>

                            <div class="car-specs-luxury">

                                <div class="car-spec-item">

                                    <i class="fas fa-calendar"></i>

                                    <span><?php echo $auction['year']; ?></span>

                                </div>

                                <div class="car-spec-item">

                                    <i class="fas fa-road"></i>

                                    <span><?php echo number_format($auction['mileage']); ?> كم</span>

                                </div>

                                <div class="car-spec-item">

                                    <i class="fas fa-cog"></i>

                                    <span><?php echo $auction['transmission'] == 'automatic' ? 'أوتوماتيك' : 'مانيوال'; ?></span>

                                </div>

                            </div>

                            <div class="car-price-luxury">

                                $<?php echo number_format($auction['current_price'], 2); ?>

                            </div>

                            <div class="car-actions-luxury">

                                <a href="auction-details.php?id=<?php echo $auction['id']; ?>" class="car-btn primary">

                                    <i class="fas fa-gavel"></i>

                                    شارك الآن

                                </a>

                                <a href="auction-details.php?id=<?php echo $auction['id']; ?>" class="car-btn secondary">

                                    <i class="fas fa-eye"></i>

                                    التفاصيل

                                </a>

                            </div>

                        </div>

                    </div>

                    <?php endforeach; ?>

                </div>

            <?php endif; ?>

        </div>

    </section>

 

    <!-- قسم الإحصائيات الفاخر -->

    <section class="stats-section">

        <div class="container">

            <div class="section-header-premium" data-aos="fade-up">

                <h2 class="section-title">إحصائيات المنصة</h2>

                <p class="section-subtitle">أرقام تعكس ثقة عملائنا ونجاحنا</p>

            </div>

 

            <div class="stats-grid">

                <div class="stat-card-luxury" style="--gradient: var(--ocean-gradient);" data-aos="zoom-in">

                    <div class="stat-icon-luxury" style="background: var(--ocean-gradient);">

                        <i class="fas fa-users"></i>

                    </div>

                    <div class="stat-number"><?php echo number_format($total_users); ?></div>

                    <div class="stat-label">عميل مسجل</div>

                </div>

 

                <div class="stat-card-luxury" style="--gradient: var(--emerald-gradient);" data-aos="zoom-in" data-aos-delay="100">

                    <div class="stat-icon-luxury" style="background: var(--emerald-gradient);">

                        <i class="fas fa-check-circle"></i>

                    </div>

                    <div class="stat-number"><?php echo number_format($total_completed); ?></div>

                    <div class="stat-label">مزاد مكتمل</div>

                </div>

 

                <div class="stat-card-luxury" style="--gradient: var(--fire-gradient);" data-aos="zoom-in" data-aos-delay="200">

                    <div class="stat-icon-luxury" style="background: var(--fire-gradient);">

                        <i class="fas fa-gavel"></i>

                    </div>

                    <div class="stat-number"><?php echo number_format($total_active); ?></div>

                    <div class="stat-label">مزاد نشط</div>

                </div>

 

                <div class="stat-card-luxury" style="--gradient: var(--mystic-gradient);" data-aos="zoom-in" data-aos-delay="300">

                    <div class="stat-icon-luxury" style="background: var(--mystic-gradient);">

                        <i class="fas fa-store"></i>

                    </div>

                    <div class="stat-number"><?php echo number_format($total_sellers); ?></div>

                    <div class="stat-label">بائع نشط</div>

                </div>

            </div>

        </div>

    </section>

 

    <!-- Footer الفاخر -->

    <footer class="footer-luxury">

        <div class="container">

            <div class="footer-content">

                <div class="footer-section">

                    <h3>BidOra</h3>

                    <p>منصة مزادات السيارات الرائدة في المنطقة. نوفر تجربة مزادات فريدة وآمنة لجميع عملائنا.</p>

                    <div class="social-links">

                        <a href="#" class="social-link">

                            <i class="fab fa-facebook-f"></i>

                        </a>

                        <a href="#" class="social-link">

                            <i class="fab fa-twitter"></i>

                        </a>

                        <a href="#" class="social-link">

                            <i class="fab fa-instagram"></i>

                        </a>

                        <a href="#" class="social-link">

                            <i class="fab fa-linkedin-in"></i>

                        </a>

                    </div>

                </div>

 

                <div class="footer-section">

                    <h3>روابط سريعة</h3>

                    <a href="auctions.php">

                        <i class="fas fa-chevron-left"></i>

                        المزادات

                    </a>

                    <a href="store.php">

                        <i class="fas fa-chevron-left"></i>

                        المتجر

                    </a>

                    <a href="dashboard.php">

                        <i class="fas fa-chevron-left"></i>

                        لوحة التحكم

                    </a>

                    <a href="auth/register.php">

                        <i class="fas fa-chevron-left"></i>

                        تسجيل حساب جديد

                    </a>

                </div>

 

                <div class="footer-section">

                    <h3>اتصل بنا</h3>

                    <a href="mailto:info@carauction.com">

                        <i class="fas fa-envelope"></i>

                        info@carauction.com

                    </a>

                    <a href="tel:+966501234567">

                        <i class="fas fa-phone"></i>

                        +966 50 123 4567

                    </a>

                    <a href="#">

                        <i class="fas fa-map-marker-alt"></i>

                        الرياض، المملكة العربية السعودية

                    </a>

                </div>

            </div>

 

            <div class="footer-bottom">

                <p>&copy; 2025 BidOra. جميع الحقوق محفوظة.</p>

            </div>

        </div>

    </footer>

 

    <!-- زر العودة للأعلى -->

    <div class="scroll-top" id="scrollTop">

        <i class="fas fa-arrow-up"></i>

    </div>

 

    <!-- Scripts -->

    <script src="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.js"></script>

    <script src="js/auto-translate.js"></script>

 

    <script>

        // تهيئة AOS

        AOS.init({

            duration: 1200,

            once: true,

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

 

        // زر العودة للأعلى

        window.addEventListener('scroll', () => {

            const scrollTop = document.getElementById('scrollTop');

            if (window.scrollY > 300) {

                scrollTop.classList.add('visible');

            } else {

                scrollTop.classList.remove('visible');

            }

        });

 

        document.getElementById('scrollTop').addEventListener('click', () => {

            window.scrollTo({

                top: 0,

                behavior: 'smooth'

            });

        });

 

        // العداد التنازلي للمزادات

        function updateCountdowns() {

            document.querySelectorAll('.countdown-luxury').forEach(el => {

                const endTime = parseInt(el.dataset.endtime) * 1000;

                const now = Date.now();

                const remaining = endTime - now;

 

                const span = el.querySelector('span');

 

                if (remaining <= 0) {

                    span.textContent = 'انتهى المزاد';

                    el.style.background = 'rgba(239, 68, 68, 0.85)';

                } else {

                    const days = Math.floor(remaining / 86400000);

                    const hours = Math.floor((remaining % 86400000) / 3600000);

                    const mins = Math.floor((remaining % 3600000) / 60000);

                    const secs = Math.floor((remaining % 60000) / 1000);

 

                    if (days > 0) {

                        span.textContent = `${days}ي ${hours}س ${mins}د`;

                    } else if (hours > 0) {

                        span.textContent = `${hours}س ${mins}د ${secs}ث`;

                    } else {

                        span.textContent = `${mins}د ${secs}ث`;

                    }

                }

            });

        }

 

        // تفعيل كل شيء عند تحميل الصفحة

        document.addEventListener('DOMContentLoaded', function() {

            createParticles();

            createStars();

 

            // تحديث العدادات التنازلية

            updateCountdowns();

            setInterval(updateCountdowns, 1000);

 

            // إضافة تأثير parallax خفيف للبطاقات

            document.addEventListener('mousemove', (e) => {

                const cards = document.querySelectorAll('.car-card-luxury, .feature-card-3d, .stat-card-luxury');

                const x = e.clientX / window.innerWidth;

                const y = e.clientY / window.innerHeight;

 

                cards.forEach((card, index) => {

                    const speed = (index % 3 + 1) * 2;

                    const xOffset = (x - 0.5) * speed;

                    const yOffset = (y - 0.5) * speed;

 

                    card.style.transform = `translateX(${xOffset}px) translateY(${yOffset}px)`;

                });

            });

        });

    </script>

</body>

</html>