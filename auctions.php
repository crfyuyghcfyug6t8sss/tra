<?php

require_once 'config/database.php';

require_once 'includes/functions.php';

 

// جلب المزادات النشطة

$stmt = $conn->query("

    SELECT

        a.*,

        v.brand,

        v.model,

        v.year,

        v.mileage,

        v.color,

        v.transmission,

        u.username as seller_name

    FROM auctions a

    JOIN vehicles v ON a.vehicle_id = v.id

    JOIN users u ON a.seller_id = u.id

    WHERE a.status = 'active' AND a.end_time > NOW()

    ORDER BY a.end_time ASC

");

$auctions = $stmt->fetchAll();

?>

<!DOCTYPE html>

<html lang="ar" dir="rtl">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>🌟 المزادات الحصرية - سيارات الأحلام الفاخرة</title>

 

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

 

    <!-- Swiper -->

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css">

 

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

 

        /* 🎯 قائمة التنقل الفاخرة */

        .luxury-nav {

            display: flex;

            gap: 10px;

            align-items: center;

            flex-wrap: wrap;

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

 

        /* 🎭 قسم البطل الفاخر */

        .hero-section-luxury {

            position: relative;

            padding: 100px 0 80px;

            text-align: center;

            overflow: hidden;

            background: var(--glass-white);

            backdrop-filter: blur(30px);

            -webkit-backdrop-filter: blur(30px);

            border-bottom: 2px solid var(--glass-border);

            margin-bottom: 60px;

        }

 

        .hero-bg-animation {

            position: absolute;

            top: -50%;

            left: -50%;

            width: 200%;

            height: 200%;

            background: radial-gradient(circle at center, rgba(102, 126, 234, 0.15) 0%, transparent 50%);

            animation: heroRotate 30s linear infinite;

        }

 

        @keyframes heroRotate {

            from { transform: rotate(0deg) scale(1); }

            to { transform: rotate(360deg) scale(1.1); }

        }

 

        .hero-content {

            position: relative;

            z-index: 1;

        }

 

        .hero-title {

            font-size: 4rem;

            font-weight: 900;

            margin-bottom: 20px;

            background: linear-gradient(135deg, #667eea, #764ba2, #f093fb, #f5576c);

            background-size: 300% 300%;

            -webkit-background-clip: text;

            -webkit-text-fill-color: transparent;

            animation: heroGradient 5s ease infinite;

            line-height: 1.2;

            text-shadow: 0 0 80px rgba(102, 126, 234, 0.5);

        }

 

        @keyframes heroGradient {

            0% { background-position: 0% 50%; }

            50% { background-position: 100% 50%; }

            100% { background-position: 0% 50%; }

        }

 

        .hero-subtitle {

            font-size: 1.4rem;

            color: rgba(255, 255, 255, 0.8);

            font-weight: 400;

            letter-spacing: 1px;

        }

 

        /* 🔍 فلاتر البحث الفاخرة */

        .luxury-filters {

            background: var(--glass-white);

            backdrop-filter: blur(20px);

            -webkit-backdrop-filter: blur(20px);

            border: 1px solid var(--glass-border);

            border-radius: 30px;

            padding: 40px;

            margin-bottom: 40px;

            position: relative;

            overflow: hidden;

            animation: filtersSlideIn 0.8s ease-out;

        }

 

        @keyframes filtersSlideIn {

            from {

                opacity: 0;

                transform: translateY(-30px);

            }

            to {

                opacity: 1;

                transform: translateY(0);

            }

        }

 

        .filters-header {

            display: flex;

            align-items: center;

            gap: 15px;

            margin-bottom: 30px;

        }

 

        .filters-icon {

            width: 60px;

            height: 60px;

            border-radius: 18px;

            background: var(--royal-gradient);

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 2rem;

            color: white;

            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);

        }

 

        .filters-title {

            font-size: 2rem;

            font-weight: 800;

            color: white;

        }

 

        .filters-grid {

            display: grid;

            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));

            gap: 20px;

        }

 

        .filter-item {

            position: relative;

        }

 

        .filter-label {

            display: block;

            color: rgba(255, 255, 255, 0.9);

            font-weight: 600;

            margin-bottom: 10px;

            font-size: 1rem;

        }

 

        .filter-input {

            width: 100%;

            padding: 15px 20px;

            background: rgba(255, 255, 255, 0.1);

            border: 1px solid var(--glass-border);

            border-radius: 15px;

            color: white;

            font-size: 1rem;

            font-family: 'Cairo', sans-serif;

            transition: var(--transition);

        }

 

        .filter-input:focus {

            outline: none;

            background: rgba(255, 255, 255, 0.15);

            border-color: var(--primary);

            box-shadow: 0 0 20px rgba(102, 126, 234, 0.3);

        }

 

        .filter-btn {

            grid-column: 1 / -1;

            padding: 18px;

            background: var(--royal-gradient);

            color: white;

            border: none;

            border-radius: 15px;

            font-size: 1.1rem;

            font-weight: 700;

            cursor: pointer;

            transition: var(--transition);

            display: flex;

            align-items: center;

            justify-content: center;

            gap: 10px;

            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);

        }

 

        .filter-btn:hover {

            transform: translateY(-3px);

            box-shadow: 0 15px 40px rgba(102, 126, 234, 0.4);

        }

 

        /* 🎨 شبكة المزادات الفاخرة */

        .auctions-grid-luxury {

            display: grid;

            grid-template-columns: repeat(auto-fill, minmax(380px, 1fr));

            gap: 35px;

            margin-top: 40px;

        }

 

        /* 🎪 بطاقة المزاد الفاخرة 3D */

        .auction-card-luxury {

            background: var(--glass-white);

            backdrop-filter: blur(20px);

            -webkit-backdrop-filter: blur(20px);

            border: 1px solid var(--glass-border);

            border-radius: 30px;

            overflow: hidden;

            transition: var(--transition);

            position: relative;

            transform-style: preserve-3d;

            transform: perspective(1000px);

            animation: cardFadeIn 0.8s ease-out backwards;

        }

 

        @keyframes cardFadeIn {

            from {

                opacity: 0;

                transform: perspective(1000px) rotateY(-20deg) translateY(30px);

            }

            to {

                opacity: 1;

                transform: perspective(1000px) rotateY(0) translateY(0);

            }

        }

 

        .auction-card-luxury:hover {

            transform: perspective(1000px) rotateY(5deg) translateY(-15px) scale(1.05);

            box-shadow: 0 30px 80px rgba(102, 126, 234, 0.4);

            border-color: var(--primary);

        }

 

        .auction-card-luxury::before {

            content: '';

            position: absolute;

            top: 0;

            left: 0;

            right: 0;

            height: 5px;

            background: var(--royal-gradient);

            transform: scaleX(0);

            transform-origin: left;

            transition: transform 0.5s ease;

        }

 

        .auction-card-luxury:hover::before {

            transform: scaleX(1);

        }

 

        /* 🏆 شارة الحالة الفاخرة */

        .luxury-badge {

            position: absolute;

            top: 20px;

            right: 20px;

            background: var(--emerald-gradient);

            color: white;

            padding: 10px 22px;

            border-radius: 25px;

            font-size: 0.95rem;

            font-weight: 700;

            z-index: 10;

            display: flex;

            align-items: center;

            gap: 8px;

            box-shadow: 0 10px 30px rgba(19, 241, 252, 0.4);

            animation: badgePulse 2s ease-in-out infinite;

        }

 

        @keyframes badgePulse {

            0%, 100% {

                box-shadow: 0 10px 30px rgba(19, 241, 252, 0.4);

                transform: scale(1);

            }

            50% {

                box-shadow: 0 10px 40px rgba(19, 241, 252, 0.6);

                transform: scale(1.05);

            }

        }

 

        .luxury-badge i {

            animation: badgeIcon 2s infinite;

        }

 

        @keyframes badgeIcon {

            0%, 100% { opacity: 1; }

            50% { opacity: 0.5; }

        }

 

        /* 🖼️ صورة السيارة الفاخرة */

        .card-image-luxury {

            position: relative;

            width: 100%;

            height: 300px;

            overflow: hidden;

            background: linear-gradient(135deg, #302b63 0%, #24243e 100%);

        }

 

        .card-image-luxury::before {

            content: '';

            position: absolute;

            top: 0;

            left: -100%;

            width: 100%;

            height: 100%;

            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);

            transition: left 0.8s;

            z-index: 1;

        }

 

        .auction-card-luxury:hover .card-image-luxury::before {

            left: 100%;

        }

 

        .card-image-luxury img {

            width: 100%;

            height: 100%;

            object-fit: cover;

            transition: var(--transition);

        }

 

        .auction-card-luxury:hover .card-image-luxury img {

            transform: scale(1.15) rotate(2deg);

        }

 

        /* 📝 محتوى البطاقة الفاخر */

        .card-content-luxury {

            padding: 30px;

        }

 

        .vehicle-title-luxury {

            font-size: 1.9rem;

            font-weight: 800;

            color: white;

            margin-bottom: 10px;

            line-height: 1.3;

            text-shadow: 0 0 20px rgba(255, 255, 255, 0.2);

        }

 

        .auction-id-luxury {

            color: rgba(255, 255, 255, 0.6);

            font-size: 0.95rem;

            display: flex;

            align-items: center;

            gap: 8px;

            margin-bottom: 25px;

        }

 

        /* 🔧 شبكة المواصفات الفاخرة */

        .specs-grid-luxury {

            display: grid;

            grid-template-columns: repeat(2, 1fr);

            gap: 15px;

            margin-bottom: 25px;

        }

 

        .spec-item-luxury {

            background: rgba(255, 255, 255, 0.05);

            backdrop-filter: blur(10px);

            padding: 15px;

            border-radius: 15px;

            display: flex;

            align-items: center;

            gap: 12px;

            border: 1px solid rgba(255, 255, 255, 0.1);

            transition: var(--transition);

        }

 

        .spec-item-luxury:hover {

            background: rgba(102, 126, 234, 0.2);

            border-color: var(--primary);

            transform: translateX(-3px);

        }

 

        .spec-icon-luxury {

            width: 45px;

            height: 45px;

            border-radius: 12px;

            background: var(--royal-gradient);

            display: flex;

            align-items: center;

            justify-content: center;

            flex-shrink: 0;

            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);

        }

 

        .spec-icon-luxury i {

            color: white;

            font-size: 1.2rem;

        }

 

        .spec-text-luxury {

            flex: 1;

            min-width: 0;

        }

 

        .spec-label-luxury {

            display: block;

            font-size: 0.85rem;

            color: rgba(255, 255, 255, 0.6);

            margin-bottom: 3px;

        }

 

        .spec-value-luxury {

            display: block;

            font-weight: 700;

            color: white;

            font-size: 1.05rem;

        }

 

        /* 💰 قسم السعر الفاخر */

        .price-section-luxury {

            background: linear-gradient(135deg, rgba(102, 126, 234, 0.2), rgba(118, 75, 162, 0.2));

            backdrop-filter: blur(10px);

            padding: 25px;

            border-radius: 20px;

            text-align: center;

            margin-bottom: 25px;

            border: 1px solid rgba(102, 126, 234, 0.3);

            position: relative;

            overflow: hidden;

        }

 

        .price-section-luxury::before {

            content: '';

            position: absolute;

            top: 0;

            left: -100%;

            width: 100%;

            height: 100%;

            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.1), transparent);

            animation: priceShimmer 3s infinite;

        }

 

        @keyframes priceShimmer {

            0% { left: -100%; }

            100% { left: 100%; }

        }

 

        .price-label-luxury {

            font-size: 1rem;

            color: rgba(255, 255, 255, 0.8);

            margin-bottom: 10px;

            display: flex;

            align-items: center;

            justify-content: center;

            gap: 8px;

            position: relative;

            z-index: 1;

        }

 

        .price-label-luxury i {

            color: var(--warning);

        }

 

        .price-value-luxury {

            font-size: 2.5rem;

            font-weight: 900;

            color: white;

            display: flex;

            align-items: center;

            justify-content: center;

            gap: 5px;

            text-shadow: 0 0 30px rgba(255, 255, 255, 0.3);

            position: relative;

            z-index: 1;

        }

 

        .price-currency-luxury {

            font-size: 1.8rem;

            color: var(--warning);

        }

 

        /* ⏱️ العداد التنازلي الفاخر */

        .countdown-section-luxury {

            background: linear-gradient(135deg, rgba(19, 241, 252, 0.2), rgba(4, 112, 220, 0.2));

            backdrop-filter: blur(10px);

            padding: 20px;

            border-radius: 20px;

            text-align: center;

            margin-bottom: 25px;

            border: 1px solid rgba(19, 241, 252, 0.3);

        }

 

        .countdown-section-luxury.urgent {

            background: linear-gradient(135deg, rgba(255, 78, 80, 0.2), rgba(249, 212, 35, 0.2));

            border-color: rgba(255, 78, 80, 0.4);

            animation: urgentPulse 2s infinite;

        }

 

        @keyframes urgentPulse {

            0%, 100% { box-shadow: 0 0 0 0 rgba(255, 78, 80, 0.5); }

            50% { box-shadow: 0 0 0 20px rgba(255, 78, 80, 0); }

        }

 

        .countdown-label-luxury {

            font-size: 1rem;

            color: rgba(255, 255, 255, 0.8);

            margin-bottom: 10px;

            display: flex;

            align-items: center;

            justify-content: center;

            gap: 8px;

        }

 

        .countdown-label-luxury i {

            color: var(--success);

        }

 

        .countdown-section-luxury.urgent .countdown-label-luxury i {

            color: var(--danger);

        }

 

        .countdown-timer-luxury {

            font-size: 1.8rem;

            font-weight: 800;

            color: white;

            font-family: 'Courier New', monospace;

            letter-spacing: 3px;

            text-shadow: 0 0 20px rgba(255, 255, 255, 0.3);

        }

 

        /* 📊 معلومات إضافية فاخرة */

        .auction-meta-luxury {

            display: flex;

            gap: 15px;

            padding-top: 20px;

            border-top: 1px solid rgba(255, 255, 255, 0.1);

            margin-bottom: 25px;

        }

 

        .meta-item-luxury {

            flex: 1;

            display: flex;

            align-items: center;

            gap: 12px;

        }

 

        .meta-icon-luxury {

            width: 45px;

            height: 45px;

            border-radius: 12px;

            background: rgba(255, 255, 255, 0.05);

            display: flex;

            align-items: center;

            justify-content: center;

            flex-shrink: 0;

        }

 

        .meta-icon-luxury i {

            color: var(--primary);

            font-size: 1.1rem;

        }

 

        .meta-text-luxury {

            flex: 1;

            min-width: 0;

        }

 

        .meta-label-luxury {

            display: block;

            font-size: 0.85rem;

            color: rgba(255, 255, 255, 0.6);

            margin-bottom: 3px;

        }

 

        .meta-value-luxury {

            display: block;

            font-weight: 700;

            color: white;

            font-size: 1rem;

        }

 

        /* 🎯 زر الإجراءات الفاخر */

        .btn-luxury {

            display: flex;

            align-items: center;

            justify-content: center;

            gap: 12px;

            width: 100%;

            padding: 18px;

            background: var(--royal-gradient);

            color: white;

            text-decoration: none;

            border-radius: 18px;

            font-weight: 700;

            font-size: 1.15rem;

            transition: var(--transition);

            border: none;

            cursor: pointer;

            position: relative;

            overflow: hidden;

            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.4);

        }

 

        .btn-luxury::before {

            content: '';

            position: absolute;

            top: 0;

            left: -100%;

            width: 100%;

            height: 100%;

            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);

            transition: left 0.6s;

        }

 

        .btn-luxury:hover {

            transform: translateY(-3px);

            box-shadow: 0 15px 40px rgba(102, 126, 234, 0.6);

        }

 

        .btn-luxury:hover::before {

            left: 100%;

        }

 

        /* 🎭 رسالة فارغة فاخرة */

        .empty-state-luxury {

            text-align: center;

            padding: 100px 40px;

            background: var(--glass-white);

            backdrop-filter: blur(20px);

            border-radius: 30px;

            border: 2px dashed var(--glass-border);

            animation: emptyState 0.8s ease-out;

        }

 

        @keyframes emptyState {

            from {

                opacity: 0;

                transform: scale(0.9);

            }

            to {

                opacity: 1;

                transform: scale(1);

            }

        }

 

        .empty-icon-luxury {

            width: 140px;

            height: 140px;

            margin: 0 auto 40px;

            background: var(--royal-gradient);

            border-radius: 50%;

            display: flex;

            align-items: center;

            justify-content: center;

            box-shadow: 0 20px 60px rgba(102, 126, 234, 0.5);

            animation: emptyIconFloat 3s ease-in-out infinite;

        }

 

        @keyframes emptyIconFloat {

            0%, 100% { transform: translateY(0) scale(1); }

            50% { transform: translateY(-10px) scale(1.05); }

        }

 

        .empty-icon-luxury i {

            font-size: 5rem;

            color: white;

        }

 

        .empty-title-luxury {

            font-size: 2.5rem;

            font-weight: 800;

            color: white;

            margin-bottom: 20px;

            text-shadow: 0 0 20px rgba(255, 255, 255, 0.2);

        }

 

        .empty-text-luxury {

            color: rgba(255, 255, 255, 0.8);

            font-size: 1.3rem;

        }

 

        /* 📱 التجاوب مع الجوال */

        @media (max-width: 1200px) {

            .auctions-grid-luxury {

                grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));

            }

        }

 

        @media (max-width: 768px) {

            .header-content {

                flex-direction: column;

                gap: 20px;

            }

 

            .luxury-nav {

                display: grid;

                grid-template-columns: repeat(2, 1fr);

                width: 100%;

            }

 

            .hero-title {

                font-size: 2.5rem;

            }

 

            .hero-subtitle {

                font-size: 1.1rem;

            }

 

            .luxury-filters {

                padding: 25px;

            }

 

            .filters-grid {

                grid-template-columns: 1fr;

            }

 

            .auctions-grid-luxury {

                grid-template-columns: 1fr;

                gap: 25px;

            }

 

            .specs-grid-luxury {

                grid-template-columns: 1fr;

            }

 

            .vehicle-title-luxury {

                font-size: 1.6rem;

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

            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.4), transparent);

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

 

                <!-- قائمة التنقل الفاخرة -->

                <nav class="luxury-nav">

                    <a href="index.php" class="nav-item">

                        <i class="fas fa-home"></i>

                        <span>الرئيسية</span>

                    </a>

                    <a href="auctions.php" class="nav-item active">

                        <i class="fas fa-gavel"></i>

                        <span>المزادات</span>

                    </a>

                    <a href="store.php" class="nav-item">

                        <i class="fas fa-store"></i>

                        <span>المتجر</span>

                    </a>

                    <a href="dashboard.php" class="nav-item">

                        <i class="fas fa-tachometer-alt"></i>

                        <span>لوحة التحكم</span>

                    </a>

                </nav>

            </div>

        </div>

    </header>

 

    <!-- قسم البطل الفاخر -->

    <section class="hero-section-luxury">

        <div class="hero-bg-animation"></div>

        <div class="container">

            <div class="hero-content">

                <h1 class="hero-title" data-translate="page_title">المزادات الحصرية</h1>

                <p class="hero-subtitle" data-translate="page_subtitle">اكتشف أفخم السيارات في مزادات مذهلة بتصميم ثلاثي الأبعاد</p>

            </div>

        </div>

    </section>

 

    <!-- المحتوى الرئيسي -->

    <main class="container" style="padding: 60px 20px;">

        <!-- فلاتر البحث الفاخرة -->

        <div class="luxury-filters" data-aos="fade-up">

            <div class="filters-header">

                <div class="filters-icon">

                    <i class="fas fa-filter"></i>

                </div>

                <h2 class="filters-title">فلتر المزادات</h2>

            </div>

            <form class="filters-grid" method="GET" action="">

                <div class="filter-item">

                    <label class="filter-label">الماركة</label>

                    <input type="text" class="filter-input" name="brand" placeholder="مثال: BMW">

                </div>

                <div class="filter-item">

                    <label class="filter-label">الموديل</label>

                    <input type="text" class="filter-input" name="model" placeholder="مثال: X5">

                </div>

                <div class="filter-item">

                    <label class="filter-label">السنة من</label>

                    <input type="number" class="filter-input" name="year_from" placeholder="2020">

                </div>

                <div class="filter-item">

                    <label class="filter-label">السنة إلى</label>

                    <input type="number" class="filter-input" name="year_to" placeholder="2024">

                </div>

                <div class="filter-item">

                    <label class="filter-label">السعر من</label>

                    <input type="number" class="filter-input" name="price_from" placeholder="$10,000">

                </div>

                <div class="filter-item">

                    <label class="filter-label">السعر إلى</label>

                    <input type="number" class="filter-input" name="price_to" placeholder="$100,000">

                </div>

                <button type="submit" class="filter-btn shimmer-effect">

                    <i class="fas fa-search"></i>

                    <span>بحث متقدم</span>

                </button>

            </form>

        </div>

 

        <?php if (empty($auctions)): ?>

            <div class="empty-state-luxury" data-aos="zoom-in">

                <div class="empty-icon-luxury">

                    <i class="fas fa-gavel"></i>

                </div>

                <h2 class="empty-title-luxury" data-translate="no_auctions">لا توجد مزادات نشطة حالياً</h2>

                <p class="empty-text-luxury" data-translate="check_later">تحقق مرة أخرى قريباً للحصول على أفضل العروض الحصرية</p>

            </div>

        <?php else: ?>

            <div class="auctions-grid-luxury">

                <?php foreach ($auctions as $index => $auction): ?>

                    <div class="auction-card-luxury" data-aos="fade-up" data-aos-delay="<?php echo $index * 100; ?>">

                        <!-- شارة الحالة الفاخرة -->

                        <div class="luxury-badge">

                            <i class="fas fa-circle"></i>

                            <span data-translate="active">نشط الآن</span>

                        </div>

 

                        <!-- صورة السيارة -->

                        <div class="card-image-luxury">

                            <?php if (!empty($auction['image'])): ?>

                                <img src="<?php echo htmlspecialchars($auction['image']); ?>"

                                     alt="<?php echo htmlspecialchars($auction['brand'] . ' ' . $auction['model']); ?>">

                            <?php else: ?>

                                <img src="assets/images/default-car.jpg" alt="Car">

                            <?php endif; ?>

                        </div>

 

                        <!-- محتوى البطاقة -->

                        <div class="card-content-luxury">

                            <!-- عنوان السيارة -->

                            <h3 class="vehicle-title-luxury">

                                <?php echo htmlspecialchars($auction['brand'] . ' ' . $auction['model']); ?>

                            </h3>

                            <div class="auction-id-luxury">

                                <i class="fas fa-hashtag"></i>

                                <span>رقم المزاد: <?php echo $auction['id']; ?></span>

                            </div>

 

                            <!-- المواصفات الفاخرة -->

                            <div class="specs-grid-luxury">

                                <div class="spec-item-luxury">

                                    <div class="spec-icon-luxury">

                                        <i class="fas fa-calendar-alt"></i>

                                    </div>

                                    <div class="spec-text-luxury">

                                        <span class="spec-label-luxury" data-translate="year">السنة</span>

                                        <span class="spec-value-luxury"><?php echo $auction['year']; ?></span>

                                    </div>

                                </div>

 

                                <div class="spec-item-luxury">

                                    <div class="spec-icon-luxury">

                                        <i class="fas fa-road"></i>

                                    </div>

                                    <div class="spec-text-luxury">

                                        <span class="spec-label-luxury" data-translate="mileage">الكيلومترات</span>

                                        <span class="spec-value-luxury"><?php echo number_format($auction['mileage']); ?></span>

                                    </div>

                                </div>

 

                                <?php if (!empty($auction['color'])): ?>

                                    <div class="spec-item-luxury">

                                        <div class="spec-icon-luxury">

                                            <i class="fas fa-palette"></i>

                                        </div>

                                        <div class="spec-text-luxury">

                                            <span class="spec-label-luxury" data-translate="color">اللون</span>

                                            <span class="spec-value-luxury"><?php echo htmlspecialchars($auction['color']); ?></span>

                                        </div>

                                    </div>

                                <?php endif; ?>

 

                                <div class="spec-item-luxury">

                                    <div class="spec-icon-luxury">

                                        <i class="fas fa-cogs"></i>

                                    </div>

                                    <div class="spec-text-luxury">

                                        <span class="spec-label-luxury" data-translate="transmission">الناقل</span>

                                        <span class="spec-value-luxury">

                                            <?php echo $auction['transmission'] == 'automatic' ? 'أوتوماتيك' : 'مانيوال'; ?>

                                        </span>

                                    </div>

                                </div>

                            </div>

 

                            <!-- السعر الفاخر -->

                            <div class="price-section-luxury">

                                <div class="price-label-luxury">

                                    <i class="fas fa-tag"></i>

                                    <span data-translate="current_price">السعر الحالي</span>

                                </div>

                                <div class="price-value-luxury">

                                    <span class="price-currency-luxury">$</span>

                                    <?php echo number_format($auction['current_price'], 0); ?>

                                </div>

                            </div>

 

                            <!-- العد التنازلي الفاخر -->

                            <?php

                            $now = time();

                            $end = strtotime($auction['end_time']);

                            $remaining = $end - $now;

                            $urgent = $remaining < 86400;

                            ?>

                            <div class="countdown-section-luxury <?php echo $urgent ? 'urgent' : ''; ?>"

                                 data-endtime="<?php echo $auction['end_time']; ?>">

                                <div class="countdown-label-luxury">

                                    <i class="fas fa-clock"></i>

                                    <span data-translate="time_remaining">الوقت المتبقي</span>

                                </div>

                                <div class="countdown-timer-luxury">

                                    <?php

                                    if ($remaining <= 0) {

                                        echo 'انتهى المزاد';

                                    } else {

                                        $days = floor($remaining / 86400);

                                        $hours = floor(($remaining % 86400) / 3600);

                                        $minutes = floor(($remaining % 3600) / 60);

 

                                        if ($days > 0) {

                                            echo $days . ' يوم ';

                                        }

                                        echo sprintf("%02d:%02d", $hours, $minutes);

                                    }

                                    ?>

                                </div>

                            </div>

 

                            <!-- معلومات إضافية فاخرة -->

                            <div class="auction-meta-luxury">

                                <div class="meta-item-luxury">

                                    <div class="meta-icon-luxury">

                                        <i class="fas fa-user"></i>

                                    </div>

                                    <div class="meta-text-luxury">

                                        <span class="meta-label-luxury" data-translate="seller">البائع</span>

                                        <span class="meta-value-luxury"><?php echo htmlspecialchars($auction['seller_name']); ?></span>

                                    </div>

                                </div>

                                <div class="meta-item-luxury">

                                    <div class="meta-icon-luxury">

                                        <i class="fas fa-gavel"></i>

                                    </div>

                                    <div class="meta-text-luxury">

                                        <span class="meta-label-luxury" data-translate="bids">المزايدات</span>

                                        <span class="meta-value-luxury"><?php echo $auction['total_bids']; ?></span>

                                    </div>

                                </div>

                            </div>

 

                            <!-- زر الإجراءات الفاخر -->

                            <a href="auction-details.php?id=<?php echo $auction['id']; ?>" class="btn-luxury shimmer-effect">

                                <i class="fas fa-eye"></i>

                                <span data-translate="view_details">عرض التفاصيل الكاملة</span>

                            </a>

                        </div>

                    </div>

                <?php endforeach; ?>

            </div>

        <?php endif; ?>

    </main>

 

    <!-- Scripts -->

    <script src="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.js"></script>

    <script src="js/auto-translate.js"></script>

 

    <script>

        // تهيئة AOS

        AOS.init({

            duration: 1200,

            once: false,

            offset: 100,

            easing: 'ease-out-cubic'

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

 

        // تحديث العدادات التنازلية

        function updateCountdowns() {

            const countdowns = document.querySelectorAll('.countdown-section-luxury');

 

            countdowns.forEach(countdown => {

                const endTime = new Date(countdown.dataset.endtime).getTime();

                const now = new Date().getTime();

                const distance = endTime - now;

 

                const timer = countdown.querySelector('.countdown-timer-luxury');

 

                if (distance < 0) {

                    timer.innerHTML = 'انتهى المزاد';

                    countdown.classList.remove('urgent');

                    return;

                }

 

                const days = Math.floor(distance / (1000 * 60 * 60 * 24));

                const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));

                const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));

                const seconds = Math.floor((distance % (1000 * 60)) / 1000);

 

                let timeString = '';

                if (days > 0) {

                    timeString += days + ' يوم ';

                }

                timeString += `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;

 

                timer.innerHTML = timeString;

 

                // إضافة تصنيف urgent إذا كان الوقت المتبقي أقل من يوم

                if (distance < 86400000 && !countdown.classList.contains('urgent')) {

                    countdown.classList.add('urgent');

                }

            });

        }

 

        // تفعيل كل شيء عند التحميل

        document.addEventListener('DOMContentLoaded', function() {

            createParticles();

            createStars();

            setInterval(updateCountdowns, 1000);

            updateCountdowns();

 

            // تأثير Parallax 3D للبطاقات عند حركة الماوس

            document.addEventListener('mousemove', (e) => {

                const cards = document.querySelectorAll('.auction-card-luxury');

                const x = e.clientX / window.innerWidth;

                const y = e.clientY / window.innerHeight;

 

                cards.forEach(card => {

                    const rect = card.getBoundingClientRect();

                    const cardCenterX = rect.left + rect.width / 2;

                    const cardCenterY = rect.top + rect.height / 2;

 

                    const distanceX = e.clientX - cardCenterX;

                    const distanceY = e.clientY - cardCenterY;

 

                    const maxDistance = 300;

                    const distance = Math.sqrt(distanceX * distanceX + distanceY * distanceY);

 

                    if (distance < maxDistance) {

                        const angleX = (distanceY / maxDistance) * 10;

                        const angleY = (distanceX / maxDistance) * 10;

 

                        if (card.matches(':hover')) {

                            card.style.transform = `perspective(1000px) rotateX(${-angleX}deg) rotateY(${angleY}deg) translateY(-15px) scale(1.05)`;

                        }

                    }

                });

            });

 

            // إعادة تعيين التحويلات عند مغادرة البطاقة

            document.querySelectorAll('.auction-card-luxury').forEach(card => {

                card.addEventListener('mouseleave', function() {

                    this.style.transform = 'perspective(1000px) rotateX(0) rotateY(0) translateY(0) scale(1)';

                });

            });

        });

    </script>

</body>

</html>