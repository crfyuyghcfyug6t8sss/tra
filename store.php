<?php

// تحميل نظام الترجمة
require_once 'includes/translator.php';

require_once 'config/database.php';

require_once 'includes/functions.php';

 

// جلب التصنيفات

$stmt = $conn->query("SELECT * FROM store_categories WHERE is_active = TRUE ORDER BY display_order");

$categories = $stmt->fetchAll();

 

// الفلترة

$category_id = isset($_GET['category']) ? (int)$_GET['category'] : 0;

$search = isset($_GET['search']) ? clean($_GET['search']) : '';

$condition = isset($_GET['condition']) ? clean($_GET['condition']) : '';

$min_price = isset($_GET['min_price']) ? (float)$_GET['min_price'] : 0;

$max_price = isset($_GET['max_price']) ? (float)$_GET['max_price'] : 999999;

 

// بناء الاستعلام

$sql = "

    SELECT

        p.*,

        c.name as category_name,

        c.icon as category_icon,

        u.username as seller_name,

        (SELECT image_path FROM store_product_images WHERE product_id = p.id AND is_primary = TRUE LIMIT 1) as main_image,

        (SELECT COUNT(*) FROM store_product_images WHERE product_id = p.id) as images_count

    FROM store_products p

    JOIN store_categories c ON p.category_id = c.id

    JOIN users u ON p.seller_id = u.id

    WHERE p.status = 'active'

";

 

$params = [];

 

if ($category_id > 0) {

    $sql .= " AND p.category_id = ?";

    $params[] = $category_id;

}

 

if ($search) {

    $sql .= " AND (p.title LIKE ? OR p.description LIKE ?)";

    $params[] = "%$search%";

    $params[] = "%$search%";

}

 

if ($condition) {

    $sql .= " AND p.condition_type = ?";

    $params[] = $condition;

}

 

$sql .= " AND p.price BETWEEN ? AND ?";

$params[] = $min_price;

$params[] = $max_price;

 

$sql .= " ORDER BY p.featured DESC, p.created_at DESC";

 

$stmt = $conn->prepare($sql);

$stmt->execute($params);

$products = $stmt->fetchAll();

?>

<!DOCTYPE html>

<html lang="<?php echo currentLang(); ?>" dir="<?php echo textDirection(); ?>">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>    - البيع المباشر الحصري</title>

 

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

            --green-gradient: linear-gradient(135deg, #10b981 0%, #059669 100%);

 

            /* Glass Effects */

            --glass-white: rgba(255, 255, 255, 0.1);

            --glass-border: rgba(255, 255, 255, 0.18);

            --glass-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.37);

 

            /* Colors */

            --primary: #667eea;

            --secondary: #764ba2;

            --success: #10b981;

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

            background: radial-gradient(circle, rgba(16, 185, 129, 1) 0%, transparent 70%);

            border-radius: 50%;

            animation: particleFloat 25s infinite linear;

            box-shadow: 0 0 10px rgba(16, 185, 129, 0.8);

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

            background: radial-gradient(ellipse at center, rgba(16, 185, 129, 0.3) 0%, transparent 70%);

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

            box-shadow: 0 10px 30px rgba(16, 185, 129, 0.4);

        }

 

        .logo-text {

            font-size: 1.8rem;

            font-weight: 900;

            background: linear-gradient(135deg, #10b981, #059669, #34d399);

            -webkit-background-clip: text;

            -webkit-text-fill-color: transparent;

            animation: logoShimmer 3s ease-in-out infinite;

            text-shadow: 0 0 30px rgba(16, 185, 129, 0.5);

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

            background: var(--green-gradient);

            border-radius: 50%;

            transform: translate(-50%, -50%);

            transition: width 0.6s ease, height 0.6s ease;

        }

 

        .nav-item:hover {

            color: white;

            transform: translateY(-3px);

            box-shadow: 0 10px 30px rgba(16, 185, 129, 0.3);

            border-color: rgba(16, 185, 129, 0.5);

        }

 

        .nav-item:hover::before {

            width: 150%;

            height: 150%;

        }

 

        .nav-item.active {

            background: var(--green-gradient);

            color: white;

            border: none;

            box-shadow: 0 10px 30px rgba(16, 185, 129, 0.4);

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

 

        /* 📊 المحتوى الرئيسي */

        .main-content {

            padding: 40px 0;

            position: relative;

        }

 

        /* 🎉 بانر العنوان الفاخر */

        .page-banner-premium {

            background: var(--glass-white);

            backdrop-filter: blur(30px);

            -webkit-backdrop-filter: blur(30px);

            border: 1px solid var(--glass-border);

            border-radius: 30px;

            padding: 50px;

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

 

        .banner-bg-animation {

            position: absolute;

            top: -50%;

            left: -50%;

            width: 200%;

            height: 200%;

            background: radial-gradient(circle at center, rgba(16, 185, 129, 0.1) 0%, transparent 50%);

            animation: bannerRotate 30s linear infinite;

        }

 

        @keyframes bannerRotate {

            from { transform: rotate(0deg) scale(1); }

            to { transform: rotate(360deg) scale(1.1); }

        }

 

        .banner-content-premium {

            position: relative;

            z-index: 1;

            text-align: center;

        }

 

        .banner-title {

            font-size: 3rem;

            font-weight: 900;

            margin-bottom: 15px;

            background: linear-gradient(135deg, #10b981, #059669, #34d399, #6ee7b7);

            background-size: 300% 300%;

            -webkit-background-clip: text;

            -webkit-text-fill-color: transparent;

            animation: bannerGradient 5s ease infinite;

            line-height: 1.2;

        }

 

        @keyframes bannerGradient {

            0% { background-position: 0% 50%; }

            50% { background-position: 100% 50%; }

            100% { background-position: 0% 50%; }

        }

 

        .banner-subtitle {

            color: rgba(255, 255, 255, 0.8);

            font-size: 1.3rem;

            font-weight: 500;

        }

 

        /* 🏷️ شريط التصنيفات الفاخر */

        .categories-bar-premium {

            background: var(--glass-white);

            backdrop-filter: blur(20px);

            border: 1px solid var(--glass-border);

            border-radius: 25px;

            padding: 30px;

            margin-bottom: 30px;

            position: relative;

            overflow: hidden;

            animation: fadeInUp 0.8s ease-out;

        }

 

        @keyframes fadeInUp {

            from {

                opacity: 0;

                transform: translateY(30px);

            }

            to {

                opacity: 1;

                transform: translateY(0);

            }

        }

 

        .categories-scroll {

            display: flex;

            gap: 15px;

            overflow-x: auto;

            padding: 10px 0;

            scrollbar-width: thin;

            scrollbar-color: rgba(16, 185, 129, 0.3) transparent;

        }

 

        .categories-scroll::-webkit-scrollbar {

            height: 8px;

        }

 

        .categories-scroll::-webkit-scrollbar-track {

            background: rgba(255, 255, 255, 0.05);

            border-radius: 10px;

        }

 

        .categories-scroll::-webkit-scrollbar-thumb {

            background: rgba(16, 185, 129, 0.3);

            border-radius: 10px;

        }

 

        .categories-scroll::-webkit-scrollbar-thumb:hover {

            background: rgba(16, 185, 129, 0.5);

        }

 

        .category-chip {

            display: flex;

            align-items: center;

            gap: 10px;

            padding: 15px 25px;

            background: var(--glass-white);

            backdrop-filter: blur(10px);

            border: 2px solid var(--glass-border);

            border-radius: 25px;

            text-decoration: none;

            color: rgba(255, 255, 255, 0.8);

            white-space: nowrap;

            transition: var(--transition);

            cursor: pointer;

            font-weight: 600;

            position: relative;

            overflow: hidden;

        }

 

        .category-chip::before {

            content: '';

            position: absolute;

            top: 50%;

            left: 50%;

            width: 0;

            height: 0;

            background: var(--green-gradient);

            border-radius: 50%;

            transform: translate(-50%, -50%);

            transition: width 0.6s ease, height 0.6s ease;

            z-index: 0;

        }

 

        .category-chip:hover {

            transform: translateY(-3px);

            box-shadow: 0 10px 25px rgba(16, 185, 129, 0.3);

            border-color: rgba(16, 185, 129, 0.5);

            color: white;

        }

 

        .category-chip:hover::before {

            width: 150%;

            height: 150%;

        }

 

        .category-chip.active {

            background: var(--green-gradient);

            color: white;

            border-color: transparent;

            box-shadow: 0 10px 30px rgba(16, 185, 129, 0.4);

        }

 

        .category-chip i,

        .category-chip span {

            position: relative;

            z-index: 1;

        }

 

        .category-chip i {

            font-size: 1.2rem;

        }

 

        /* 🔍 شريط الفلاتر الفاخر */

        .filters-bar-premium {

            background: var(--glass-white);

            backdrop-filter: blur(20px);

            border: 1px solid var(--glass-border);

            border-radius: 25px;

            padding: 30px;

            margin-bottom: 40px;

            animation: fadeInUp 0.8s ease-out 0.2s backwards;

        }

 

        .filters-grid {

            display: grid;

            grid-template-columns: 2fr 1fr 1fr 1fr auto;

            gap: 20px;

            align-items: end;

        }

 

        .filter-group label {

            display: block;

            margin-bottom: 10px;

            font-weight: 700;

            color: rgba(255, 255, 255, 0.9);

            font-size: 0.95rem;

            text-transform: uppercase;

            letter-spacing: 0.5px;

        }

 

        .filter-group input,

        .filter-group select {

            width: 100%;

            padding: 15px 20px;

            border: 2px solid var(--glass-border);

            border-radius: 15px;

            font-size: 1rem;

            background: var(--glass-white);

            backdrop-filter: blur(10px);

            color: white;

            font-weight: 600;

            transition: var(--transition);

        }

 

        .filter-group input::placeholder {

            color: rgba(255, 255, 255, 0.5);

        }

 

        .filter-group input:focus,

        .filter-group select:focus {

            outline: none;

            border-color: rgba(16, 185, 129, 0.5);

            box-shadow: 0 0 20px rgba(16, 185, 129, 0.2);

        }

 

        .filter-group select option {

            background: #1e293b;

            color: white;

        }

 

        .btn-filter {

            padding: 15px 35px;

            background: var(--green-gradient);

            color: white;

            border: none;

            border-radius: 15px;

            cursor: pointer;

            font-weight: 700;

            font-size: 1rem;

            transition: var(--transition);

            box-shadow: 0 10px 30px rgba(16, 185, 129, 0.3);

            display: flex;

            align-items: center;

            gap: 10px;

            position: relative;

            overflow: hidden;

        }

 

        .btn-filter::before {

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

 

        .btn-filter:hover {

            transform: translateY(-3px);

            box-shadow: 0 15px 40px rgba(16, 185, 129, 0.4);

        }

 

        .btn-filter:hover::before {

            left: 100%;

        }

 

        /* 🛍️ شبكة المنتجات الفاخرة */

        .products-grid {

            display: grid;

            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));

            gap: 30px;

        }

 

        .product-card-3d {

            background: var(--glass-white);

            backdrop-filter: blur(20px);

            -webkit-backdrop-filter: blur(20px);

            border: 1px solid var(--glass-border);

            border-radius: 25px;

            overflow: hidden;

            text-decoration: none;

            color: inherit;

            display: block;

            transition: var(--transition);

            position: relative;

            animation: cardFadeIn 0.8s ease-out backwards;

            transform-style: preserve-3d;

        }

 

        .product-card-3d:nth-child(1) { animation-delay: 0.1s; }

        .product-card-3d:nth-child(2) { animation-delay: 0.2s; }

        .product-card-3d:nth-child(3) { animation-delay: 0.3s; }

        .product-card-3d:nth-child(4) { animation-delay: 0.4s; }

        .product-card-3d:nth-child(5) { animation-delay: 0.5s; }

        .product-card-3d:nth-child(6) { animation-delay: 0.6s; }

        .product-card-3d:nth-child(7) { animation-delay: 0.7s; }

        .product-card-3d:nth-child(8) { animation-delay: 0.8s; }

 

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

 

        .product-card-3d:hover {

            transform: translateY(-10px) scale(1.02);

            box-shadow: 0 20px 60px rgba(16, 185, 129, 0.3);

            border-color: rgba(16, 185, 129, 0.5);

        }

 

        .product-card-3d::before {

            content: '';

            position: absolute;

            top: 0;

            left: 0;

            right: 0;

            height: 5px;

            background: var(--green-gradient);

            transform: scaleX(0);

            transform-origin: left;

            transition: transform 0.5s ease;

        }

 

        .product-card-3d:hover::before {

            transform: scaleX(1);

        }

 

        .product-image-premium {

            width: 100%;

            height: 250px;

            background: linear-gradient(135deg, #10b981 0%, #059669 100%);

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 5rem;

            color: white;

            position: relative;

            overflow: hidden;

        }

 

        .product-image-premium::before {

            content: '';

            position: absolute;

            top: -50%;

            left: -50%;

            width: 200%;

            height: 200%;

            background: radial-gradient(circle, rgba(255, 255, 255, 0.1) 0%, transparent 70%);

            animation: imageRotate 20s linear infinite;

        }

 

        @keyframes imageRotate {

            from { transform: rotate(0deg); }

            to { transform: rotate(360deg); }

        }

 

        .product-image-premium img {

            width: 100%;

            height: 100%;

            object-fit: cover;

            position: relative;

            z-index: 1;

            transition: transform 0.6s ease;

        }

 

        .product-card-3d:hover .product-image-premium img {

            transform: scale(1.1) rotate(2deg);

        }

 

        .product-badge-premium {

            position: absolute;

            top: 15px;

            right: 15px;

            padding: 8px 18px;

            border-radius: 20px;

            font-size: 0.85rem;

            font-weight: 700;

            z-index: 2;

            backdrop-filter: blur(10px);

            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);

            animation: badgePulse 2s ease-in-out infinite;

        }

 

        @keyframes badgePulse {

            0%, 100% {

                transform: scale(1);

            }

            50% {

                transform: scale(1.05);

            }

        }

 

        .badge-featured {

            background: var(--fire-gradient);

            color: white;

        }

 

        .badge-new {

            background: var(--green-gradient);

            color: white;

        }

 

        .product-content-premium {

            padding: 25px;

        }

 

        .product-category-premium {

            display: inline-flex;

            align-items: center;

            gap: 8px;

            font-size: 0.9rem;

            color: #10b981;

            margin-bottom: 12px;

            font-weight: 700;

            text-transform: uppercase;

            letter-spacing: 0.5px;

        }

 

        .product-category-premium i {

            font-size: 1.1rem;

        }

 

        .product-title-premium {

            font-size: 1.25rem;

            font-weight: 800;

            color: white;

            margin-bottom: 15px;

            line-height: 1.4;

            display: -webkit-box;

            -webkit-line-clamp: 2;

            -webkit-box-orient: vertical;

            overflow: hidden;

        }

 

        .product-price-premium {

            font-size: 2rem;

            font-weight: 900;

            background: var(--green-gradient);

            -webkit-background-clip: text;

            -webkit-text-fill-color: transparent;

            margin-bottom: 20px;

            text-shadow: 0 0 20px rgba(16, 185, 129, 0.3);

        }

 

        .product-meta-premium {

            display: flex;

            justify-content: space-between;

            align-items: center;

            font-size: 0.9rem;

            color: rgba(255, 255, 255, 0.7);

            padding-top: 20px;

            border-top: 1px solid rgba(255, 255, 255, 0.1);

        }

 

        .product-meta-premium span {

            display: flex;

            align-items: center;

            gap: 6px;

        }

 

        .product-meta-premium i {

            color: #10b981;

        }

 

        /* 📭 حالة فارغة فاخرة */

        .empty-state-premium {

            text-align: center;

            padding: 100px 20px;

            background: var(--glass-white);

            backdrop-filter: blur(20px);

            border: 1px solid var(--glass-border);

            border-radius: 30px;

            animation: fadeInUp 0.8s ease-out;

        }

 

        .empty-state-premium i {

            font-size: 6rem;

            color: rgba(255, 255, 255, 0.3);

            margin-bottom: 25px;

            animation: emptyIconFloat 3s ease-in-out infinite;

        }

 

        @keyframes emptyIconFloat {

            0%, 100% { transform: translateY(0); }

            50% { transform: translateY(-15px); }

        }

 

        .empty-state-premium h3 {

            color: white;

            font-size: 1.8rem;

            font-weight: 800;

            margin-bottom: 15px;

        }

 

        .empty-state-premium p {

            color: rgba(255, 255, 255, 0.6);

            font-size: 1.1rem;

        }

 

        /* 📱 استجابة الجوال */

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

 

            .banner-title {

                font-size: 2rem;

            }

 

            .banner-subtitle {

                font-size: 1rem;

            }

 

            .page-banner-premium {

                padding: 30px 20px;

            }

 

            .categories-bar-premium {

                padding: 20px;

            }

 

            .filters-bar-premium {

                padding: 20px;

            }

 

            .filters-grid {

                grid-template-columns: 1fr;

            }

 

            .products-grid {

                grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));

                gap: 20px;

            }

 

            .product-image-premium {

                height: 200px;

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

 

        /* Language Switcher */

        .lang-switcher-fixed {

            position: fixed;

            top: 20px;

            left: 20px;

            z-index: 1001;

        }

    </style>


    <!-- Responsive & Mobile Menu CSS -->
    <link rel="stylesheet" href="assets/css/responsive.css">

</head>

<body>

    <!-- الخلفية الديناميكية -->

    <div class="dynamic-background"></div>

    <div class="grid-3d"></div>

 

    <!-- الجسيمات المتوهجة -->

    <div class="glowing-particles" id="particles"></div>

 

    <!-- النجوم المتلألئة -->

    <div class="stars" id="stars"></div>

 

    <!-- Language Switcher -->

    <div class="lang-switcher-fixed">

        <?php include 'includes/lang-switcher.php'; ?>

    </div>

 

    <!-- Header الفاخر -->

    <header class="luxury-header">

        <div class="header-glow"></div>

        <div class="container">

            <div class="header-content">

                <!-- الشعار المتحرك -->

                <div class="animated-logo">

                    <div class="logo-icon">

                        <img src=" " alt="Logo">

                    </div>

                    <div class="logo-text">المتجر الفاخر</div>

                </div>

 

                <!-- قائمة التنقل الفاخرة -->

                <nav class="luxury-nav">

                    <a href="index.php" class="nav-item">

                        <i class="fas fa-home"></i>

                        <span>الرئيسية</span>

                    </a>

                    <a href="auctions.php" class="nav-item">

                        <i class="fas fa-gavel"></i>

                        <span>المزادات</span>

                    </a>

                    <a href="store.php" class="nav-item active">

                        <i class="fas fa-store"></i>

                        <span>المتجر</span>

                    </a>

                    <a href="add-product.php" class="nav-item">

                        <i class="fas fa-plus-circle"></i>

                        <span>أضف منتج</span>

                    </a>

                    <?php if (isLoggedIn()): ?>

                        <a href="dashboard.php" class="nav-item">

                            <i class="fas fa-tachometer-alt"></i>

                            <span>لوحة التحكم</span>

                        </a>

                    <?php else: ?>

                        <a href="auth/login.php" class="nav-item">

                            <i class="fas fa-sign-in-alt"></i>

                            <span>تسجيل الدخول</span>

                        </a>

                    <?php endif; ?>

                </nav>

            </div>

        </div>

    </header>

 

    <!-- المحتوى الرئيسي -->

    <main class="main-content">

        <div class="container">

            <!-- بانر العنوان الفاخر -->

            <div class="page-banner-premium" data-aos="fade-up">

                <div class="banner-bg-animation"></div>

                <div class="banner-content-premium">

                    <h1 class="banner-title">

                        المتجر الحصري للبيع المباشر

                    </h1>

                    <p class="banner-subtitle">

                        اكتشف مجموعة واسعة من المنتجات الفاخرة بأسعار لا تقبل المنافسة

                    </p>

                </div>

            </div>

 

            <!-- شريط التصنيفات الفاخر -->

            <div class="categories-bar-premium" data-aos="fade-up">

                <div class="categories-scroll">

                    <a href="store.php" class="category-chip <?php echo $category_id == 0 ? 'active' : ''; ?>">

                        <i class="fas fa-th"></i>

                        <span>جميع التصنيفات</span>

                    </a>

                    <?php foreach ($categories as $cat): ?>

                        <a href="store.php?category=<?php echo $cat['id']; ?>"

                           class="category-chip <?php echo $category_id == $cat['id'] ? 'active' : ''; ?>">

                            <i class="fas <?php echo $cat['icon']; ?>"></i>

                            <span><?php echo htmlspecialchars($cat['name']); ?></span>

                        </a>

                    <?php endforeach; ?>

                </div>

            </div>

 

            <!-- شريط الفلاتر الفاخر -->

            <form method="GET" class="filters-bar-premium" data-aos="fade-up">

                <input type="hidden" name="category" value="<?php echo $category_id; ?>">

                <div class="filters-grid">

                    <div class="filter-group">

                        <label><i class="fas fa-search"></i> البحث</label>

                        <input type="text" name="search" placeholder="ابحث عن منتج..." value="<?php echo htmlspecialchars($search); ?>">

                    </div>

                    <div class="filter-group">

                        <label><i class="fas fa-info-circle"></i> الحالة</label>

                        <select name="condition">

                            <option value="">جميع الحالات</option>

                            <option value="new" <?php echo $condition == 'new' ? 'selected' : ''; ?>>جديد</option>

                            <option value="used" <?php echo $condition == 'used' ? 'selected' : ''; ?>>مستعمل</option>

                            <option value="refurbished" <?php echo $condition == 'refurbished' ? 'selected' : ''; ?>>مجدد</option>

                        </select>

                    </div>

                    <div class="filter-group">

                        <label><i class="fas fa-dollar-sign"></i> من السعر</label>

                        <input type="number" name="min_price" placeholder="0" value="<?php echo $min_price > 0 ? $min_price : ''; ?>">

                    </div>

                    <div class="filter-group">

                        <label><i class="fas fa-dollar-sign"></i> إلى السعر</label>

                        <input type="number" name="max_price" placeholder="9999" value="<?php echo $max_price < 999999 ? $max_price : ''; ?>">

                    </div>

                    <button type="submit" class="btn-filter shimmer-effect">

                        <i class="fas fa-filter"></i>

                        تطبيق الفلاتر

                    </button>

                </div>

            </form>

 

            <!-- شبكة المنتجات الفاخرة -->

            <?php if (empty($products)): ?>

                <div class="empty-state-premium" data-aos="zoom-in">

                    <i class="fas fa-box-open"></i>

                    <h3>لا توجد منتجات</h3>

                    <p>جرب تغيير الفلاتر أو التصنيفات للعثور على ما تبحث عنه</p>

                </div>

            <?php else: ?>

                <div class="products-grid">

                    <?php foreach ($products as $product): ?>

                        <a href="product-details.php?id=<?php echo $product['id']; ?>" class="product-card-3d" data-aos="fade-up">

                            <div class="product-image-premium">

                                <?php if ($product['main_image']): ?>

                                    <img src="<?php echo htmlspecialchars($product['main_image']); ?>" alt="<?php echo htmlspecialchars($product['title']); ?>">

                                <?php else: ?>

                                    <i class="fas fa-image"></i>

                                <?php endif; ?>

                                <?php if ($product['featured']): ?>

                                    <span class="product-badge-premium badge-featured">

                                        <i class="fas fa-star"></i> مميز

                                    </span>

                                <?php elseif ($product['condition_type'] == 'new'): ?>

                                    <span class="product-badge-premium badge-new">

                                        <i class="fas fa-bolt"></i> جديد

                                    </span>

                                <?php endif; ?>

                            </div>

                            <div class="product-content-premium">

                                <div class="product-category-premium">

                                    <i class="fas <?php echo $product['category_icon']; ?>"></i>

                                    <span><?php echo htmlspecialchars($product['category_name']); ?></span>

                                </div>

                                <div class="product-title-premium">

                                    <?php echo htmlspecialchars($product['title']); ?>

                                </div>

                                <div class="product-price-premium">

                                    $<?php echo number_format($product['price'], 2); ?>

                                </div>

                                <div class="product-meta-premium">

                                    <span>

                                        <i class="fas fa-user"></i>

                                        <?php echo htmlspecialchars($product['seller_name']); ?>

                                    </span>

                                    <span>

                                        <i class="fas fa-eye"></i>

                                        <?php echo $product['views']; ?>

                                    </span>

                                </div>

                            </div>

                        </a>

                    <?php endforeach; ?>

                </div>

            <?php endif; ?>

        </div>

    </main>

 

    <!-- Scripts -->

    <script src="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.js"></script>

    <script src="js/auto-translate.js"></script>

 

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

 

        // تفعيل كل شيء عند تحميل الصفحة

        document.addEventListener('DOMContentLoaded', function() {

            createParticles();

            createStars();

 

            // إضافة تأثير parallax للماوس

            document.addEventListener('mousemove', (e) => {

                const x = e.clientX / window.innerWidth;

                const y = e.clientY / window.innerHeight;

 

                document.querySelectorAll('.product-card-3d').forEach(card => {

                    const speed = 2;

                    const xOffset = (x - 0.5) * speed;

                    const yOffset = (y - 0.5) * speed;

 

                    card.style.transform = `translateY(-10px) scale(1.02) rotateY(${xOffset}deg) rotateX(${-yOffset}deg)`;

                });

            });

        });

    </script>


    <!-- Mobile Menu & Responsive JavaScript -->
    <script src="assets/js/mobile-menu.js"></script>

</body>

</html>