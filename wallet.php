<?php

require_once 'config/database.php';

require_once 'includes/functions.php';

 

if (!isLoggedIn()) {

    redirect('auth/login.php');

}

 

// معالجة طلبات AJAX

if (isset($_GET['ajax']) && $_GET['ajax'] == '1') {

    header('Content-Type: application/json');

 

    $filter_type = isset($_GET['filter']) ? $_GET['filter'] : 'all';

    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;

    $limit = 10;

    $offset = ($page - 1) * $limit;

 

    $where = "user_id = ?";

    $params = [$_SESSION['user_id']];

 

    if ($filter_type != 'all') {

        $where .= " AND type = ?";

        $params[] = $filter_type;

    }

 

    $stmt = $conn->prepare("SELECT * FROM transactions WHERE $where ORDER BY created_at DESC LIMIT ? OFFSET ?");

    $params[] = $limit;

    $params[] = $offset;

    $stmt->execute($params);

    $transactions = $stmt->fetchAll();

 

    $stmt = $conn->prepare("SELECT COUNT(*) FROM transactions WHERE $where");

    $stmt->execute(array_slice($params, 0, -2));

    $total_transactions = $stmt->fetchColumn();

    $total_pages = ceil($total_transactions / $limit);

 

    echo json_encode([

        'success' => true,

        'transactions' => $transactions,

        'current_page' => $page,

        'total_pages' => $total_pages,

        'total_transactions' => $total_transactions

    ]);

    exit;

}

 

// معالجة العمليات POST

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {

 

    if ($_POST['action'] == 'deposit') {

        $amount = clean($_POST['amount']);

 

        if (empty($amount) || $amount <= 0) {

            $_SESSION['error'] = 'المبلغ غير صحيح';

        } else {

            try {

                $stmt = $conn->prepare("UPDATE wallet SET available_balance = available_balance + ? WHERE user_id = ?");

                $stmt->execute([$amount, $_SESSION['user_id']]);

 

                logTransaction($_SESSION['user_id'], 'deposit', $amount, null, null, 'شحن رصيد');

 

                $_SESSION['success'] = 'تم شحن المحفظة بنجاح! 🎉';

            } catch(PDOException $e) {

                $_SESSION['error'] = 'حدث خطأ في عملية الشحن';

            }

        }

 

        header('Location: wallet.php?tab=deposit');

        exit;

    }

 

    elseif ($_POST['action'] == 'transfer') {

        $amount = clean($_POST['transfer_amount']);

        $recipient_username = clean($_POST['recipient_username']);

 

        if (empty($amount) || $amount <= 0) {

            $_SESSION['error'] = 'المبلغ غير صحيح';

        } elseif (empty($recipient_username)) {

            $_SESSION['error'] = 'يرجى إدخال اسم المستخدم';

        } else {

            try {

                $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? AND id != ?");

                $stmt->execute([$recipient_username, $_SESSION['user_id']]);

                $recipient = $stmt->fetch();

 

                if (!$recipient) {

                    $_SESSION['error'] = 'المستخدم غير موجود';

                } else {

                    $wallet = getUserWallet($_SESSION['user_id']);

 

                    if ($wallet['available_balance'] < $amount) {

                        $_SESSION['error'] = 'رصيد غير كافي';

                    } else {

                        $conn->beginTransaction();

 

                        $stmt = $conn->prepare("UPDATE wallet SET available_balance = available_balance - ? WHERE user_id = ?");

                        $stmt->execute([$amount, $_SESSION['user_id']]);

 

                        $stmt = $conn->prepare("UPDATE wallet SET available_balance = available_balance + ? WHERE user_id = ?");

                        $stmt->execute([$amount, $recipient['id']]);

 

                        logTransaction($_SESSION['user_id'], 'withdraw', $amount, null, 'transfer', "تحويل إلى @$recipient_username");

                        logTransaction($recipient['id'], 'deposit', $amount, null, 'transfer', "تحويل من مستخدم");

 

                        $conn->commit();

                        $_SESSION['success'] = 'تم التحويل بنجاح!  ';

                    }

                }

            } catch(PDOException $e) {

                $conn->rollBack();

                $_SESSION['error'] = 'حدث خطأ في عملية التحويل';

            }

        }

 

        header('Location: wallet.php?tab=transfer');

        exit;

    }

}

 

$success = isset($_SESSION['success']) ? $_SESSION['success'] : '';

$error = isset($_SESSION['error']) ? $_SESSION['error'] : '';

unset($_SESSION['success'], $_SESSION['error']);

 

$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'deposit';

 

$wallet = getUserWallet($_SESSION['user_id']);

 

$stmt = $conn->prepare("

    SELECT

        SUM(CASE WHEN type = 'deposit' THEN amount ELSE 0 END) as total_deposits,

        SUM(CASE WHEN type = 'withdraw' THEN amount ELSE 0 END) as total_withdrawals,

        SUM(CASE WHEN type = 'commission' THEN amount ELSE 0 END) as total_commissions,

        COUNT(CASE WHEN type = 'bid_deduct' AND status = 'completed' THEN 1 END) as total_purchases

    FROM transactions

    WHERE user_id = ?

");

$stmt->execute([$_SESSION['user_id']]);

$stats = $stmt->fetch();

 

$stmt = $conn->prepare("

    SELECT COUNT(*) as total_sales,

           COALESCE(SUM(seller_net_amount), 0) as total_sales_amount

    FROM sales_confirmations

    WHERE seller_id = ? AND status = 'confirmed'

");

$stmt->execute([$_SESSION['user_id']]);

$sales_stats = $stmt->fetch();

 

?>

<!DOCTYPE html>

<html lang="ar" dir="rtl">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>💎 المحفظة الرقمية الفاخرة - Bidora</title>

 

    <!-- Fonts -->

    <link rel="preconnect" href="https://fonts.googleapis.com">

    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@200;300;400;500;600;700;800;900&family=Tajawal:wght@300;400;500;700;900&display=swap" rel="stylesheet">

 

    <!-- Icons -->

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.min.css">

 

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

 

            /* Shadows */

            --shadow-sm: 0 2px 20px rgba(0, 0, 0, 0.1);

            --shadow-md: 0 10px 40px rgba(0, 0, 0, 0.15);

            --shadow-lg: 0 20px 60px rgba(0, 0, 0, 0.2);

 

            /* Animations */

            --transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);

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

            gap: 20px;

            flex-wrap: wrap;

        }

 

        /* 🎨 شعار متحرك */

        .animated-logo {

            display: flex;

            align-items: center;

            gap: 15px;

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

        }

 

        @keyframes logoShimmer {

            0%, 100% { filter: brightness(1); }

            50% { filter: brightness(1.2); }

        }

 

        .back-link {

            padding: 12px 24px;

            background: var(--glass-white);

            backdrop-filter: blur(10px);

            border: 1px solid var(--glass-border);

            border-radius: 15px;

            color: rgba(255, 255, 255, 0.9);

            text-decoration: none;

            font-weight: 600;

            display: inline-flex;

            align-items: center;

            gap: 8px;

            transition: var(--transition);

            margin-left: auto;

        }

 

        .back-link:hover {

            background: var(--royal-gradient);

            color: white;

            transform: translateY(-3px);

            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);

        }

 

        /* المحتوى الرئيسي */

        .main-content {

            padding: 40px 0;

            position: relative;

        }

 

        /* 🎉 بانر الصفحة الفاخر */

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

            background: radial-gradient(circle at center, rgba(102, 126, 234, 0.1) 0%, transparent 50%);

            animation: bannerRotate 30s linear infinite;

        }

 

        @keyframes bannerRotate {

            from { transform: rotate(0deg) scale(1); }

            to { transform: rotate(360deg) scale(1.1); }

        }

 

        .banner-content {

            position: relative;

            z-index: 1;

            text-align: center;

        }

 

        .banner-title {

            font-size: 3rem;

            font-weight: 900;

            margin-bottom: 15px;

            background: linear-gradient(135deg, #667eea, #764ba2, #f093fb, #f5576c);

            background-size: 300% 300%;

            -webkit-background-clip: text;

            -webkit-text-fill-color: transparent;

            animation: bannerGradient 5s ease infinite;

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

 

        /* الرسائل */

        .premium-alert {

            background: var(--glass-white);

            backdrop-filter: blur(20px);

            border: 2px solid;

            border-radius: 25px;

            padding: 25px 35px;

            margin-bottom: 35px;

            display: flex;

            align-items: center;

            gap: 20px;

            animation: alertSlide 0.8s ease-out;

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

            border-color: rgba(19, 241, 252, 0.5);

            background: linear-gradient(135deg, rgba(19, 241, 252, 0.1), rgba(4, 112, 220, 0.1));

        }

 

        .premium-alert.danger {

            border-color: rgba(255, 78, 80, 0.5);

            background: linear-gradient(135deg, rgba(255, 78, 80, 0.1), rgba(249, 66, 58, 0.1));

        }

 

        .alert-icon {

            width: 60px;

            height: 60px;

            border-radius: 18px;

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 2rem;

            flex-shrink: 0;

        }

 

        .alert-icon.success {

            background: var(--emerald-gradient);

            color: white;

        }

 

        .alert-icon.danger {

            background: var(--fire-gradient);

            color: white;

        }

 

        .alert-message {

            color: white;

            font-size: 1.1rem;

            font-weight: 600;

            flex: 1;

        }

 

        /* 📈 شبكة الإحصائيات */

        .stats-grid-premium {

            display: grid;

            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));

            gap: 25px;

            margin-bottom: 40px;

        }

 

        .stat-card-3d {

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

            transform-style: preserve-3d;

        }

 

        .stat-card-3d:nth-child(1) { animation-delay: 0.1s; }

        .stat-card-3d:nth-child(2) { animation-delay: 0.2s; }

        .stat-card-3d:nth-child(3) { animation-delay: 0.3s; }

        .stat-card-3d:nth-child(4) { animation-delay: 0.4s; }

        .stat-card-3d:nth-child(5) { animation-delay: 0.5s; }

        .stat-card-3d:nth-child(6) { animation-delay: 0.6s; }

 

        @keyframes cardFadeIn {

            from {

                opacity: 0;

                transform: perspective(1000px) rotateY(-30deg) translateY(30px);

            }

            to {

                opacity: 1;

                transform: perspective(1000px) rotateY(0) translateY(0);

            }

        }

 

        .stat-card-3d:hover {

            transform: perspective(1000px) rotateY(5deg) translateY(-10px) scale(1.05);

            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);

        }

 

        .stat-card-3d::before {

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

 

        .stat-card-3d:hover::before {

            transform: scaleX(1);

        }

 

        .stat-header-premium {

            display: flex;

            justify-content: space-between;

            align-items: flex-start;

            margin-bottom: 20px;

        }

 

        .stat-icon-3d {

            width: 70px;

            height: 70px;

            border-radius: 20px;

            display: flex;

            align-items: center;

            justify-content: center;

            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);

            animation: iconRotate 10s linear infinite;

        }

 

        @keyframes iconRotate {

            from { transform: rotateY(0deg); }

            to { transform: rotateY(360deg); }

        }

 

        .stat-icon-3d i {

            font-size: 2rem;

            color: white;

        }

 

        .stat-value-premium {

            font-size: 2.5rem;

            font-weight: 900;

            color: white;

            margin-bottom: 5px;

            text-shadow: 0 0 20px rgba(255, 255, 255, 0.3);

            display: flex;

            align-items: baseline;

            gap: 8px;

        }

 

        .stat-value-premium .currency {

            font-size: 1.5rem;

            color: rgba(255, 255, 255, 0.7);

        }

 

        .stat-label-premium {

            color: rgba(255, 255, 255, 0.7);

            font-size: 1rem;

            font-weight: 600;

            text-transform: uppercase;

            letter-spacing: 1px;

        }

 

        .stat-trend {

            display: flex;

            align-items: center;

            gap: 8px;

            margin-top: 15px;

            padding-top: 15px;

            border-top: 1px solid rgba(255, 255, 255, 0.1);

        }

 

        .trend-icon {

            width: 35px;

            height: 35px;

            border-radius: 10px;

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 1.2rem;

        }

 

        .trend-up {

            background: linear-gradient(135deg, rgba(16, 185, 129, 0.2), rgba(5, 150, 105, 0.2));

            color: #10b981;

        }

 

        .trend-down {

            background: linear-gradient(135deg, rgba(239, 68, 68, 0.2), rgba(220, 38, 38, 0.2));

            color: #ef4444;

        }

 

        .trend-text {

            color: rgba(255, 255, 255, 0.8);

            font-size: 0.95rem;

            font-weight: 600;

        }

 

        /* منطقة المحتوى الرئيسي */

        .wallet-main-card {

            background: var(--glass-white);

            backdrop-filter: blur(30px);

            -webkit-backdrop-filter: blur(30px);

            border: 1px solid var(--glass-border);

            border-radius: 30px;

            overflow: hidden;

            animation: fadeIn 1s ease-out;

        }

 

        @keyframes fadeIn {

            from { opacity: 0; }

            to { opacity: 1; }

        }

 

        /* التبويبات الفاخرة */

        .luxury-tabs {

            display: flex;

            background: rgba(255, 255, 255, 0.05);

            padding: 15px;

            gap: 15px;

            border-bottom: 1px solid var(--glass-border);

        }

 

        .tab-button {

            flex: 1;

            padding: 18px 30px;

            background: transparent;

            border: 2px solid transparent;

            border-radius: 18px;

            color: rgba(255, 255, 255, 0.7);

            font-size: 1.1rem;

            font-weight: 700;

            cursor: pointer;

            transition: var(--transition);

            position: relative;

            overflow: hidden;

        }

 

        .tab-button::before {

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

            z-index: 0;

        }

 

        .tab-button:hover {

            color: #fff;

            transform: translateY(-2px);

            background: rgba(255, 255, 255, 0.1);

        }

 

        .tab-button.active {

            background: var(--royal-gradient);

            color: white;

            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.4);

        }

 

        .tab-button.active::before {

            width: 100%;

            height: 100%;

            border-radius: 18px;

        }

 

        .tab-button i {

            margin-left: 10px;

            font-size: 1.3rem;

            position: relative;

            z-index: 1;

        }

 

        .tab-button span {

            position: relative;

            z-index: 1;

        }

 

        /* محتوى التبويبات */

        .tab-content {

            display: none;

            padding: 50px;

            animation: slideIn 0.5s ease-out;

        }

 

        .tab-content.active {

            display: block;

        }

 

        @keyframes slideIn {

            from {

                opacity: 0;

                transform: translateY(20px);

            }

            to {

                opacity: 1;

                transform: translateY(0);

            }

        }

 

        /* النماذج الفاخرة */

        .form-section {

            max-width: 700px;

            margin: 0 auto;

        }

 

        .form-group {

            margin-bottom: 30px;

        }

 

        .form-group label {

            display: block;

            color: white;

            font-weight: 700;

            margin-bottom: 12px;

            font-size: 1.1rem;

            text-transform: uppercase;

            letter-spacing: 1px;

        }

 

        .input-wrapper {

            position: relative;

        }

 

        .input-wrapper i {

            position: absolute;

            right: 25px;

            top: 50%;

            transform: translateY(-50%);

            color: rgba(255, 255, 255, 0.5);

            font-size: 1.4rem;

            pointer-events: none;

            transition: var(--transition);

        }

 

        .form-control {

            width: 100%;

            padding: 20px 60px 20px 25px;

            background: rgba(255, 255, 255, 0.1);

            border: 2px solid rgba(255, 255, 255, 0.2);

            border-radius: 18px;

            color: white;

            font-size: 1.2rem;

            font-weight: 600;

            transition: var(--transition);

        }

 

        .form-control:focus {

            outline: none;

            background: rgba(255, 255, 255, 0.15);

            border-color: var(--primary);

            box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1),

                        0 10px 30px rgba(102, 126, 234, 0.2);

            transform: translateY(-2px);

        }

 

        .form-control:focus + i {

            color: var(--primary);

            transform: translateY(-50%) scale(1.1);

        }

 

        .form-control::placeholder {

            color: rgba(255, 255, 255, 0.4);

        }

 

        /* الأزرار الفاخرة */

        .btn-luxury {

            width: 100%;

            padding: 20px;

            border: none;

            border-radius: 18px;

            font-size: 1.2rem;

            font-weight: 800;

            cursor: pointer;

            transition: var(--transition);

            position: relative;

            overflow: hidden;

            text-transform: uppercase;

            letter-spacing: 1.5px;

            display: flex;

            align-items: center;

            justify-content: center;

            gap: 12px;

            margin-top: 30px;

        }

 

        .btn-luxury::before {

            content: '';

            position: absolute;

            top: 50%;

            left: 50%;

            width: 0;

            height: 0;

            background: rgba(255, 255, 255, 0.3);

            border-radius: 50%;

            transform: translate(-50%, -50%);

            transition: width 0.6s ease, height 0.6s ease;

        }

 

        .btn-luxury:hover::before {

            width: 300%;

            height: 300%;

        }

 

        .btn-luxury.primary {

            background: var(--royal-gradient);

            color: white;

            box-shadow: 0 15px 40px rgba(102, 126, 234, 0.4);

        }

 

        .btn-luxury.primary:hover {

            transform: translateY(-5px);

            box-shadow: 0 20px 50px rgba(102, 126, 234, 0.5);

        }

 

        .btn-luxury.success {

            background: var(--emerald-gradient);

            color: white;

            box-shadow: 0 15px 40px rgba(19, 241, 252, 0.4);

        }

 

        .btn-luxury.success:hover {

            transform: translateY(-5px);

            box-shadow: 0 20px 50px rgba(19, 241, 252, 0.5);

        }

 

        .btn-luxury i {

            font-size: 1.5rem;

            position: relative;

            z-index: 1;

        }

 

        .btn-luxury span {

            position: relative;

            z-index: 1;

        }

 

        /* الفلاتر */

        .filters-container {

            display: flex;

            gap: 15px;

            margin-bottom: 30px;

            flex-wrap: wrap;

            padding: 20px;

        }

 

        .filter-btn {

            padding: 14px 26px;

            background: rgba(255, 255, 255, 0.1);

            border: 2px solid rgba(255, 255, 255, 0.2);

            border-radius: 15px;

            color: rgba(255, 255, 255, 0.8);

            font-weight: 700;

            cursor: pointer;

            transition: var(--transition);

            display: inline-flex;

            align-items: center;

            gap: 10px;

        }

 

        .filter-btn:hover {

            background: rgba(255, 255, 255, 0.15);

            transform: translateY(-2px);

            color: white;

        }

 

        .filter-btn.active {

            background: var(--royal-gradient);

            border-color: transparent;

            color: white;

            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.4);

        }

 

        /* جدول المعاملات الفاخر */

        .table-container {

            overflow-x: auto;

            border-radius: 20px;

            background: rgba(255, 255, 255, 0.05);

            padding: 25px;

        }

 

        .transactions-table {

            width: 100%;

            border-collapse: separate;

            border-spacing: 0 15px;

        }

 

        .transactions-table thead th {

            background: var(--royal-gradient);

            color: white;

            padding: 20px;

            text-align: right;

            font-weight: 800;

            text-transform: uppercase;

            letter-spacing: 1px;

            font-size: 1rem;

        }

 

        .transactions-table thead th:first-child {

            border-radius: 15px 0 0 15px;

        }

 

        .transactions-table thead th:last-child {

            border-radius: 0 15px 15px 0;

        }

 

        .transactions-table tbody tr {

            background: rgba(255, 255, 255, 0.08);

            backdrop-filter: blur(10px);

            transition: var(--transition);

            animation: fadeInRow 0.5s ease-out backwards;

        }

 

        @keyframes fadeInRow {

            from {

                opacity: 0;

                transform: translateX(-20px);

            }

            to {

                opacity: 1;

                transform: translateX(0);

            }

        }

 

        .transactions-table tbody tr:hover {

            background: rgba(255, 255, 255, 0.15);

            transform: scale(1.01);

            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);

        }

 

        .transactions-table tbody td {

            padding: 20px;

            color: rgba(255, 255, 255, 0.9);

            font-weight: 600;

        }

 

        .transactions-table tbody td:first-child {

            border-radius: 15px 0 0 15px;

        }

 

        .transactions-table tbody td:last-child {

            border-radius: 0 15px 15px 0;

        }

 

        /* الشارات */

        .badge {

            display: inline-flex;

            align-items: center;

            gap: 8px;

            padding: 10px 18px;

            border-radius: 25px;

            font-size: 0.9rem;

            font-weight: 800;

            text-transform: uppercase;

            letter-spacing: 0.5px;

        }

 

        .badge-success {

            background: var(--emerald-gradient);

            color: white;

            box-shadow: 0 5px 15px rgba(19, 241, 252, 0.3);

        }

 

        .badge-danger {

            background: var(--fire-gradient);

            color: white;

            box-shadow: 0 5px 15px rgba(255, 78, 80, 0.3);

        }

 

        .badge-warning {

            background: var(--sunset-gradient);

            color: white;

            box-shadow: 0 5px 15px rgba(250, 112, 154, 0.3);

        }

 

        .badge-info {

            background: var(--ocean-gradient);

            color: white;

            box-shadow: 0 5px 15px rgba(137, 247, 254, 0.3);

        }

 

        /* المبالغ */

        .amount-positive {

            color: #13f1fc;

            font-weight: 800;

            font-size: 1.2rem;

            text-shadow: 0 0 15px rgba(19, 241, 252, 0.5);

        }

 

        .amount-negative {

            color: #ff4e50;

            font-weight: 800;

            font-size: 1.2rem;

            text-shadow: 0 0 15px rgba(255, 78, 80, 0.5);

        }

 

        /* الترقيم */

        .pagination {

            display: flex;

            justify-content: center;

            align-items: center;

            gap: 12px;

            margin-top: 40px;

            padding: 25px;

        }

 

        .pagination span {

            display: inline-flex;

            align-items: center;

            justify-content: center;

            min-width: 50px;

            height: 50px;

            padding: 0 18px;

            background: rgba(255, 255, 255, 0.1);

            border: 2px solid rgba(255, 255, 255, 0.2);

            border-radius: 15px;

            color: rgba(255, 255, 255, 0.8);

            font-weight: 700;

            cursor: pointer;

            transition: var(--transition);

        }

 

        .pagination span:hover {

            background: var(--royal-gradient);

            border-color: transparent;

            color: white;

            transform: translateY(-3px);

            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.4);

        }

 

        .pagination span.active {

            background: var(--royal-gradient);

            border-color: transparent;

            color: white;

            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.4);

        }

 

        .pagination span.disabled {

            opacity: 0.3;

            cursor: not-allowed;

        }

 

        .pagination span.disabled:hover {

            background: rgba(255, 255, 255, 0.1);

            transform: none;

            box-shadow: none;

        }

 

        /* الحالة الفارغة */

        .empty-state {

            text-align: center;

            padding: 80px 20px;

            color: rgba(255, 255, 255, 0.6);

        }

 

        .empty-state i {

            font-size: 5rem;

            margin-bottom: 25px;

            color: rgba(255, 255, 255, 0.3);

            animation: float 3s ease-in-out infinite;

        }

 

        @keyframes float {

            0%, 100% { transform: translateY(0); }

            50% { transform: translateY(-15px); }

        }

 

        .empty-state p {

            font-size: 1.3rem;

            font-weight: 600;

        }

 

        /* Loading */

        .loading {

            display: inline-block;

            width: 60px;

            height: 60px;

            border: 4px solid rgba(255, 255, 255, 0.2);

            border-top: 4px solid var(--primary);

            border-radius: 50%;

            animation: spin 1s linear infinite;

        }

 

        @keyframes spin {

            0% { transform: rotate(0deg); }

            100% { transform: rotate(360deg); }

        }

 

        /* التأثيرات الإضافية */

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

 

        /* 📱 استجابة الجوال */

        @media (max-width: 768px) {

            .header-content {

                flex-direction: column;

                align-items: stretch;

            }

 

            .banner-title {

                font-size: 2rem;

            }

 

            .stats-grid-premium {

                grid-template-columns: 1fr;

            }

 

            .luxury-tabs {

                flex-direction: column;

            }

 

            .tab-content {

                padding: 30px 20px;

            }

 

            .table-container {

                padding: 15px;

            }

 

            .transactions-table {

                font-size: 0.9rem;

            }

 

            .transactions-table thead th,

            .transactions-table tbody td {

                padding: 12px 8px;

            }

        }

    </style>

</head>

<body>

    <!-- Language Switcher -->
    <?php include 'includes/lang-switcher.php'; ?>

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

 

                <!-- رابط العودة -->

                <a href="dashboard.php" class="back-link">

                    <i class="fas fa-arrow-right"></i>

                    <span>العودة للرئيسية</span>

                </a>

            </div>

        </div>

    </header>

 

    <!-- المحتوى الرئيسي -->

    <main class="main-content">

        <div class="container">

            <!-- بانر الصفحة الفاخر -->

            <div class="page-banner-premium" data-aos="fade-up">

                <div class="banner-bg-animation"></div>

                <div class="banner-content">

                    <h1 class="banner-title">

                        <i class="fas fa-wallet"></i> المحفظة الرقمية الفاخرة

                    </h1>

                    <p class="banner-subtitle">

                        إدارة أموالك بكل سهولة وأمان مع أفضل التقنيات المالية

                    </p>

                </div>

            </div>

 

            <!-- عرض الرسائل -->

            <?php if($success): ?>

            <div class="premium-alert success" data-aos="slide-right">

                <div class="alert-icon success">

                    <i class="fas fa-check-circle"></i>

                </div>

                <div class="alert-message">

                    <?php echo $success; ?>

                </div>

            </div>

            <?php endif; ?>

 

            <?php if($error): ?>

            <div class="premium-alert danger" data-aos="slide-left">

                <div class="alert-icon danger">

                    <i class="fas fa-exclamation-circle"></i>

                </div>

                <div class="alert-message">

                    <?php echo $error; ?>

                </div>

            </div>

            <?php endif; ?>

 

            <!-- شبكة الإحصائيات الفاخرة -->

            <div class="stats-grid-premium">

                <div class="stat-card-3d" style="--gradient: var(--ocean-gradient);" data-aos="zoom-in">

                    <div class="stat-header-premium">

                        <div>

                            <div class="stat-value-premium">

                                <span class="currency">$</span>

                                <span class="counter"><?php echo number_format($wallet['available_balance'], 2); ?></span>

                            </div>

                            <div class="stat-label-premium">الرصيد المتاح</div>

                        </div>

                        <div class="stat-icon-3d" style="background: var(--ocean-gradient);">

                            <i class="fas fa-coins"></i>

                        </div>

                    </div>

                    <div class="stat-trend">

                        <div class="trend-icon trend-up">

                            <i class="fas fa-arrow-up"></i>

                        </div>

                        <div class="trend-text">+12% هذا الشهر</div>

                    </div>

                </div>

 

                <div class="stat-card-3d" style="--gradient: var(--emerald-gradient);" data-aos="zoom-in">

                    <div class="stat-header-premium">

                        <div>

                            <div class="stat-value-premium">

                                <span class="currency">$</span>

                                <span class="counter"><?php echo number_format($wallet['frozen_balance'], 2); ?></span>

                            </div>

                            <div class="stat-label-premium">رصيد محجوز</div>

                        </div>

                        <div class="stat-icon-3d" style="background: var(--emerald-gradient);">

                            <i class="fas fa-lock"></i>

                        </div>

                    </div>

                    <div class="stat-trend">

                        <div class="trend-icon trend-up">

                            <i class="fas fa-shield-alt"></i>

                        </div>

                        <div class="trend-text">في مزايدات نشطة</div>

                    </div>

                </div>

 

                <div class="stat-card-3d" style="--gradient: var(--fire-gradient);" data-aos="zoom-in">

                    <div class="stat-header-premium">

                        <div>

                            <div class="stat-value-premium">

                                <span class="currency">$</span>

                                <span class="counter"><?php echo number_format($stats['total_deposits'] ?? 0, 2); ?></span>

                            </div>

                            <div class="stat-label-premium">إجمالي الإيداعات</div>

                        </div>

                        <div class="stat-icon-3d" style="background: var(--fire-gradient);">

                            <i class="fas fa-chart-line"></i>

                        </div>

                    </div>

                    <div class="stat-trend">

                        <div class="trend-icon trend-up">

                            <i class="fas fa-arrow-up"></i>

                        </div>

                        <div class="trend-text">+25% نمو</div>

                    </div>

                </div>

 

                <div class="stat-card-3d" style="--gradient: var(--mystic-gradient);" data-aos="zoom-in">

                    <div class="stat-header-premium">

                        <div>

                            <div class="stat-value-premium">

                                <span class="counter"><?php echo $stats['total_purchases'] ?? 0; ?></span>

                                <span style="font-size: 1rem;">عملية</span>

                            </div>

                            <div class="stat-label-premium">المشتريات</div>

                        </div>

                        <div class="stat-icon-3d" style="background: var(--mystic-gradient);">

                            <i class="fas fa-shopping-cart"></i>

                        </div>

                    </div>

                    <div class="stat-trend">

                        <div class="trend-icon trend-down">

                            <i class="fas fa-arrow-down"></i>

                        </div>

                        <div class="trend-text">-5% هذا الأسبوع</div>

                    </div>

                </div>

 

                <div class="stat-card-3d" style="--gradient: var(--sunset-gradient);" data-aos="zoom-in">

                    <div class="stat-header-premium">

                        <div>

                            <div class="stat-value-premium">

                                <span class="counter"><?php echo $sales_stats['total_sales'] ?? 0; ?></span>

                                <span style="font-size: 1rem;">صفقة</span>

                            </div>

                            <div class="stat-label-premium">مبيعات مؤكدة</div>

                        </div>

                        <div class="stat-icon-3d" style="background: var(--sunset-gradient);">

                            <i class="fas fa-handshake"></i>

                        </div>

                    </div>

                    <div class="stat-trend">

                        <div class="trend-icon trend-up">

                            <i class="fas fa-thumbs-up"></i>

                        </div>

                        <div class="trend-text">رائع!</div>

                    </div>

                </div>

 

                <div class="stat-card-3d" style="--gradient: var(--cosmic-gradient);" data-aos="zoom-in">

                    <div class="stat-header-premium">

                        <div>

                            <div class="stat-value-premium">

                                <span class="currency">$</span>

                                <span class="counter"><?php echo number_format($stats['total_commissions'] ?? 0, 2); ?></span>

                            </div>

                            <div class="stat-label-premium">عمولات مدفوعة</div>

                        </div>

                        <div class="stat-icon-3d" style="background: var(--cosmic-gradient);">

                            <i class="fas fa-percentage"></i>

                        </div>

                    </div>

                    <div class="stat-trend">

                        <div class="trend-icon trend-up">

                            <i class="fas fa-info-circle"></i>

                        </div>

                        <div class="trend-text">عمولة منخفضة</div>

                    </div>

                </div>

            </div>

 

            <!-- البطاقة الرئيسية للمحفظة -->

            <div class="wallet-main-card" data-aos="fade-up">

                <!-- التبويبات الفاخرة -->

                <div class="luxury-tabs">

                    <button class="tab-button <?php echo $active_tab == 'deposit' ? 'active' : ''; ?>"

                            onclick="openTab('deposit')">

                        <i class="fas fa-plus-circle"></i>

                        <span>شحن المحفظة</span>

                    </button>

                    <button class="tab-button <?php echo $active_tab == 'transfer' ? 'active' : ''; ?>"

                            onclick="openTab('transfer')">

                        <i class="fas fa-exchange-alt"></i>

                        <span>التحويل</span>

                    </button>

                    <button class="tab-button <?php echo $active_tab == 'transactions' ? 'active' : ''; ?>"

                            onclick="openTab('transactions')">

                        <i class="fas fa-history"></i>

                        <span>سجل المعاملات</span>

                    </button>

                </div>

 

                <!-- محتوى شحن المحفظة -->

                <div id="deposit" class="tab-content <?php echo $active_tab == 'deposit' ? 'active' : ''; ?>">

                    <div class="form-section">

                        <form method="POST" action="">

                            <input type="hidden" name="action" value="deposit">

 

                            <div class="form-group">

                                <label for="amount">

                                    <i class="fas fa-dollar-sign"></i> المبلغ المراد شحنه

                                </label>

                                <div class="input-wrapper">

                                    <input type="number"

                                           id="amount"

                                           name="amount"

                                           class="form-control"

                                           placeholder="أدخل المبلغ بالدولار"

                                           min="10"

                                           step="0.01"

                                           required>

                                    <i class="fas fa-money-bill-wave"></i>

                                </div>

                            </div>

 

                            <button type="submit" class="btn-luxury primary shimmer-effect">

                                <i class="fas fa-rocket"></i>

                                <span>شحن المحفظة الآن</span>

                            </button>

                        </form>

                    </div>

                </div>

 

                <!-- محتوى التحويل -->

                <div id="transfer" class="tab-content <?php echo $active_tab == 'transfer' ? 'active' : ''; ?>">

                    <div class="form-section">

                        <form method="POST" action="">

                            <input type="hidden" name="action" value="transfer">

 

                            <div class="form-group">

                                <label for="recipient_username">

                                    <i class="fas fa-user"></i> اسم المستخدم المستلم

                                </label>

                                <div class="input-wrapper">

                                    <input type="text"

                                           id="recipient_username"

                                           name="recipient_username"

                                           class="form-control"

                                           placeholder="@username"

                                           required>

                                    <i class="fas fa-at"></i>

                                </div>

                            </div>

 

                            <div class="form-group">

                                <label for="transfer_amount">

                                    <i class="fas fa-coins"></i> المبلغ المراد تحويله

                                </label>

                                <div class="input-wrapper">

                                    <input type="number"

                                           id="transfer_amount"

                                           name="transfer_amount"

                                           class="form-control"

                                           placeholder="0.00"

                                           min="1"

                                           step="0.01"

                                           required>

                                    <i class="fas fa-exchange-alt"></i>

                                </div>

                            </div>

 

                            <button type="submit" class="btn-luxury success shimmer-effect">

                                <i class="fas fa-paper-plane"></i>

                                <span>إرسال التحويل</span>

                            </button>

                        </form>

                    </div>

                </div>

 

                <!-- محتوى سجل المعاملات -->

                <div id="transactions" class="tab-content <?php echo $active_tab == 'transactions' ? 'active' : ''; ?>">

                    <div class="filters-container">

                        <button class="filter-btn active" onclick="filterTransactions('all')">

                            <i class="fas fa-globe"></i> جميع المعاملات

                        </button>

                        <button class="filter-btn" onclick="filterTransactions('deposit')">

                            <i class="fas fa-plus"></i> الإيداعات

                        </button>

                        <button class="filter-btn" onclick="filterTransactions('withdraw')">

                            <i class="fas fa-minus"></i> السحوبات

                        </button>

                        <button class="filter-btn" onclick="filterTransactions('bid_freeze')">

                            <i class="fas fa-lock"></i> الحجوزات

                        </button>

                        <button class="filter-btn" onclick="filterTransactions('bid_deduct')">

                            <i class="fas fa-shopping-cart"></i> المشتريات

                        </button>

                    </div>

 

                    <div class="table-container">

                        <table class="transactions-table">

                            <thead>

                                <tr>

                                    <th>#</th>

                                    <th>النوع</th>

                                    <th>المبلغ</th>

                                    <th>الرصيد السابق</th>

                                    <th>الرصيد الحالي</th>

                                    <th>الوصف</th>

                                    <th>الحالة</th>

                                    <th>التاريخ</th>

                                </tr>

                            </thead>

                            <tbody id="transactionsTableBody">

                                <tr>

                                    <td colspan="8" class="empty-state">

                                        <div class="loading"></div>

                                        <p>جاري تحميل المعاملات...</p>

                                    </td>

                                </tr>

                            </tbody>

                        </table>

                    </div>

 

                    <div class="pagination" id="paginationContainer"></div>

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

 

        // تبديل التبويبات

        function openTab(tabName) {

            const tabs = document.querySelectorAll('.tab-content');

            const buttons = document.querySelectorAll('.tab-button');

 

            tabs.forEach(tab => tab.classList.remove('active'));

            buttons.forEach(btn => btn.classList.remove('active'));

 

            document.getElementById(tabName).classList.add('active');

            event.target.classList.add('active');

 

            // تحديث URL

            const url = new URL(window.location);

            url.searchParams.set('tab', tabName);

            window.history.pushState({}, '', url);

 

            // تحميل المعاملات إذا كان التبويب المفتوح هو المعاملات

            if (tabName === 'transactions') {

                loadTransactions('all', 1);

            }

        }

 

        let currentPage = 1;

        let currentFilter = 'all';

 

        // تصفية المعاملات

        function filterTransactions(type) {

            currentFilter = type;

            currentPage = 1;

 

            // تحديث الأزرار النشطة

            document.querySelectorAll('.filter-btn').forEach(btn => {

                btn.classList.remove('active');

            });

            event.target.classList.add('active');

 

            loadTransactions(type, 1);

        }

 

        // تحميل المعاملات

        function loadTransactions(filter, page) {

            const tbody = document.getElementById('transactionsTableBody');

            tbody.innerHTML = `

                <tr>

                    <td colspan="8" class="empty-state">

                        <div class="loading"></div>

                        <p>جاري تحميل المعاملات...</p>

                    </td>

                </tr>

            `;

 

            fetch(`wallet.php?ajax=1&filter=${filter}&page=${page}`)

                .then(response => response.json())

                .then(data => {

                    if (data.success) {

                        renderTransactions(data.transactions, page);

                        renderPagination(data.current_page, data.total_pages, filter);

                    } else {

                        tbody.innerHTML = `

                            <tr>

                                <td colspan="8" class="empty-state">

                                    <i class="fas fa-exclamation-triangle"></i>

                                    <p>حدث خطأ في تحميل البيانات</p>

                                </td>

                            </tr>

                        `;

                    }

                })

                .catch(error => {

                    console.error('Error:', error);

                    tbody.innerHTML = `

                        <tr>

                            <td colspan="8" class="empty-state">

                                <i class="fas fa-exclamation-triangle"></i>

                                <p>حدث خطأ في الاتصال بالخادم</p>

                            </td>

                        </tr>

                    `;

                });

        }

 

        // عرض المعاملات

        function renderTransactions(transactions, page) {

            const tbody = document.getElementById('transactionsTableBody');

 

            if (transactions.length === 0) {

                tbody.innerHTML = `

                    <tr>

                        <td colspan="8" class="empty-state">

                            <i class="fas fa-inbox"></i>

                            <p>لا توجد معاملات بعد</p>

                        </td>

                    </tr>

                `;

                return;

            }

 

            const typeLabels = {

                'deposit': '<span class="badge badge-success"><i class="fas fa-plus"></i> شحن</span>',

                'withdraw': '<span class="badge badge-danger"><i class="fas fa-minus"></i> سحب</span>',

                'bid_freeze': '<span class="badge badge-warning"><i class="fas fa-lock"></i> حجز</span>',

                'bid_release': '<span class="badge badge-info"><i class="fas fa-unlock"></i> إلغاء حجز</span>',

                'bid_deduct': '<span class="badge badge-danger"><i class="fas fa-shopping-cart"></i> شراء</span>',

                'commission': '<span class="badge badge-warning"><i class="fas fa-percentage"></i> عمولة</span>',

                'refund': '<span class="badge badge-success"><i class="fas fa-undo"></i> استرداد</span>'

            };

 

            const statusLabels = {

                'completed': '<span class="badge badge-success">مكتمل</span>',

                'pending': '<span class="badge badge-warning">معلق</span>',

                'failed': '<span class="badge badge-danger">فشل</span>'

            };

 

            let html = '';

            const offset = (page - 1) * 10;

 

            transactions.forEach((transaction, index) => {

                const isPositive = ['deposit', 'bid_release', 'refund'].includes(transaction.type);

                const amountClass = isPositive ? 'amount-positive' : 'amount-negative';

 

                html += `

                    <tr style="animation-delay: ${index * 0.05}s">

                        <td>${offset + index + 1}</td>

                        <td>${typeLabels[transaction.type] || transaction.type}</td>

                        <td>

                            <span class="${amountClass}">

                                $${parseFloat(transaction.amount).toFixed(2)}

                            </span>

                        </td>

                        <td>$${parseFloat(transaction.balance_before).toFixed(2)}</td>

                        <td>$${parseFloat(transaction.balance_after).toFixed(2)}</td>

                        <td>${escapeHtml(transaction.description)}</td>

                        <td>${statusLabels[transaction.status]}</td>

                        <td style="font-size: 0.9rem; color: rgba(255, 255, 255, 0.7);">

                            ${formatDate(transaction.created_at)}

                        </td>

                    </tr>

                `;

            });

 

            tbody.innerHTML = html;

        }

 

        // عرض أزرار الترقيم

        function renderPagination(currentPage, totalPages, filter) {

            const container = document.getElementById('paginationContainer');

 

            if (totalPages <= 1) {

                container.innerHTML = '';

                return;

            }

 

            let html = '';

 

            // زر السابق

            if (currentPage > 1) {

                html += `

                    <span onclick="changePage(${currentPage - 1}, '${filter}')">

                        <i class="fas fa-chevron-right"></i> السابق

                    </span>

                `;

            } else {

                html += `

                    <span class="disabled">

                        <i class="fas fa-chevron-right"></i> السابق

                    </span>

                `;

            }

 

            // أرقام الصفحات

            const maxButtons = 5;

            let startPage = Math.max(1, currentPage - Math.floor(maxButtons / 2));

            let endPage = Math.min(totalPages, startPage + maxButtons - 1);

 

            if (endPage - startPage < maxButtons - 1) {

                startPage = Math.max(1, endPage - maxButtons + 1);

            }

 

            for (let i = startPage; i <= endPage; i++) {

                const activeClass = i === currentPage ? 'active' : '';

                html += `

                    <span class="${activeClass}" onclick="changePage(${i}, '${filter}')">

                        ${i}

                    </span>

                `;

            }

 

            // زر التالي

            if (currentPage < totalPages) {

                html += `

                    <span onclick="changePage(${currentPage + 1}, '${filter}')">

                        التالي <i class="fas fa-chevron-left"></i>

                    </span>

                `;

            } else {

                html += `

                    <span class="disabled">

                        التالي <i class="fas fa-chevron-left"></i>

                    </span>

                `;

            }

 

            container.innerHTML = html;

        }

 

        // تغيير الصفحة

        function changePage(page, filter) {

            currentPage = page;

            loadTransactions(filter, page);

 

            // التمرير لأعلى الجدول

            document.querySelector('.table-container').scrollIntoView({

                behavior: 'smooth',

                block: 'start'

            });

        }

 

        // دوال مساعدة

        function escapeHtml(text) {

            const div = document.createElement('div');

            div.textContent = text;

            return div.innerHTML;

        }

 

        function formatDate(dateString) {

            const date = new Date(dateString);

            const options = {

                year: 'numeric',

                month: 'short',

                day: 'numeric',

                hour: '2-digit',

                minute: '2-digit'

            };

            return date.toLocaleDateString('ar-SA', options);

        }

 

        // التهيئة عند تحميل الصفحة

        document.addEventListener('DOMContentLoaded', function() {

            // إنشاء التأثيرات

            createParticles();

            createStars();

 

            // إخفاء الرسائل تلقائياً

            const alerts = document.querySelectorAll('.premium-alert');

            alerts.forEach(alert => {

                setTimeout(() => {

                    alert.style.transition = 'opacity 0.5s';

                    alert.style.opacity = '0';

                    setTimeout(() => alert.remove(), 500);

                }, 5000);

            });

 

            // تحميل المعاملات إذا كان التبويب نشطاً

            const transactionsTab = document.getElementById('transactions');

            if (transactionsTab && transactionsTab.classList.contains('active')) {

                loadTransactions('all', 1);

            }

        });

    </script>

    <!-- Auto Translation Script -->
    <script src="js/auto-translate.js"></script>

</body>

</html>