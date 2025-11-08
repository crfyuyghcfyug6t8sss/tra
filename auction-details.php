<?php
// تحميل نظام الترجمة
require_once 'includes/translator.php';

require_once 'config/database.php';
require_once 'includes/functions.php';

// التحقق من وجود ID المزاد
if (!isset($_GET['id']) || empty($_GET['id'])) {
    redirect('auctions.php');
}

$auction_id = clean($_GET['id']);

// جلب تفاصيل المزاد
// (v.*) ستجلب كل حقول السيارة (brand, model, mileage, etc.)
$stmt = $conn->prepare("
    SELECT 
        a.*,
        v.*,
        u.username as seller_name,
        u.email as seller_email,
        u.phone as seller_phone,
        u.id as seller_user_id,
        winner.username as winner_name
    FROM auctions a
    JOIN vehicles v ON a.vehicle_id = v.id
    JOIN users u ON a.seller_id = u.id
    LEFT JOIN users winner ON a.highest_bidder_id = winner.id
    WHERE a.id = ?
");
$stmt->execute([$auction_id]);
$auction = $stmt->fetch();

if (!$auction) {
    redirect('auctions.php');
}

// جلب المزايدات (الكل، وليس 10 فقط، ليعمل التصميم الجديد)
$stmt = $conn->prepare("
    SELECT b.*, u.username 
    FROM bids b
    JOIN users u ON b.user_id = u.id
    WHERE b.auction_id = ?
    ORDER BY b.bid_amount DESC, b.created_at DESC
");
$stmt->execute([$auction_id]);
$bids = $stmt->fetchAll();

// جلب صور السيارة
$stmt = $conn->prepare("SELECT * FROM vehicle_images WHERE vehicle_id = ? ORDER BY is_primary DESC");
$stmt->execute([$auction['vehicle_id']]);
$images = $stmt->fetchAll();

// حساب عدد المشاركين (المزايدين الفريدين)
$stmt = $conn->prepare("SELECT COUNT(DISTINCT user_id) as p_count FROM bids WHERE auction_id = ?");
$stmt->execute([$auction_id]);
$participants_data = $stmt->fetch();
$participants = $participants_data['p_count'] ?? 0;

// حساب الوقت
$now = time();
$start = strtotime($auction['start_time']);
$end = strtotime($auction['end_time']);
$time_remaining = $end - $now;
$total_duration = $end - $start; // المدة الإجمالية للمزاد
$auction_ended = ($time_remaining <= 0);

// معالجة المزايدة
$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['place_bid'])) {
    
    if (!isLoggedIn()) {
        $errors[] = 'يجب تسجيل الدخول للمزايدة';
    } else {
        $bid_amount = clean($_POST['bid_amount']);
        $user_id = $_SESSION['user_id'];
        
        // التحقق من عدم مزايدة البائع على منتجه
        if ($user_id == $auction['seller_user_id']) {
            $errors[] = 'لا يمكنك المزايدة على منتجك الخاص';
        }
        
        // التحقق من انتهاء المزاد بناءً على الوقت المتبقي الفعلي
        if ($auction_ended) {
            $errors[] = 'انتهى وقت هذا المزاد';
        }
        
        // التحقق من المبلغ
        // استخدام min_bid_increment من قاعدة البيانات
        $min_increment = $auction['min_bid_increment'] ?? 100;
        $min_bid = $auction['current_price'] + $min_increment; 
        if (empty($bid_amount) || $bid_amount < $min_bid) {
            $errors[] = 'المبلغ يجب أن يكون ' . number_format($min_bid, 0) . '$ أو أكثر';
        }
        
        // جلب رصيد المحفظة
        if (empty($errors)) {
            $stmt = $conn->prepare("SELECT * FROM wallet WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $wallet = $stmt->fetch();
            
            // التحقق من الرصيد المتاح
            if (!$wallet || $wallet['available_balance'] < $bid_amount) {
                $errors[] = 'رصيدك غير كافٍ. رصيدك المتاح: ' . number_format($wallet['available_balance'] ?? 0, 2) . '$';
            }
        }
        
        // إذا لا توجد أخطاء
        if (empty($errors)) {
            try {
                $conn->beginTransaction();
                
                // التحقق من وجود مزايدة سابقة من نفس المستخدم
                $stmt = $conn->prepare("SELECT * FROM bids WHERE auction_id = ? AND user_id = ? AND status = 'active'");
                $stmt->execute([$auction_id, $user_id]);
                $previous_bid = $stmt->fetch();
                
                if ($previous_bid) {
                    // إلغاء حجز المزايدة السابقة
                    $stmt = $conn->prepare("UPDATE wallet SET available_balance = available_balance + ?, frozen_balance = frozen_balance - ? WHERE user_id = ?");
                    $stmt->execute([$previous_bid['frozen_amount'], $previous_bid['frozen_amount'], $user_id]);
                    
                    // تحديث حالة المزايدة السابقة
                    $stmt = $conn->prepare("UPDATE bids SET status = 'outbid' WHERE id = ?");
                    $stmt->execute([$previous_bid['id']]);
                    
                    logTransaction($user_id, 'bid_release', $previous_bid['frozen_amount'], $auction_id, 'auction', 'إلغاء حجز مزايدة سابقة');
                }
                
                // إذا كان هناك مزايد آخر، إلغاء حجز رصيده
                if ($auction['highest_bidder_id'] && $auction['highest_bidder_id'] != $user_id) {
                    $stmt = $conn->prepare("SELECT * FROM bids WHERE auction_id = ? AND user_id = ? AND status = 'active'");
                    $stmt->execute([$auction_id, $auction['highest_bidder_id']]);
                    $old_highest_bid = $stmt->fetch();
                    
                    if ($old_highest_bid) {
                        $stmt = $conn->prepare("UPDATE wallet SET available_balance = available_balance + ?, frozen_balance = frozen_balance - ? WHERE user_id = ?");
                        $stmt->execute([$old_highest_bid['frozen_amount'], $old_highest_bid['frozen_amount'], $auction['highest_bidder_id']]);
                        
                        $stmt = $conn->prepare("UPDATE bids SET status = 'outbid' WHERE id = ?");
                        $stmt->execute([$old_highest_bid['id']]);
                        
                        logTransaction($auction['highest_bidder_id'], 'bid_release', $old_highest_bid['frozen_amount'], $auction_id, 'auction', 'تم تجاوز مزايدتك');
                    }
                }
                
                // حجز الرصيد للمزايدة الجديدة
                $stmt = $conn->prepare("UPDATE wallet SET available_balance = available_balance - ?, frozen_balance = frozen_balance + ? WHERE user_id = ?");
                $stmt->execute([$bid_amount, $bid_amount, $user_id]);
                
                // إضافة المزايدة الجديدة
                $stmt = $conn->prepare("INSERT INTO bids (auction_id, user_id, bid_amount, frozen_amount, status) VALUES (?, ?, ?, ?, 'active')");
                $stmt->execute([$auction_id, $user_id, $bid_amount, $bid_amount]);
                
                // تحديث المزاد
                $stmt = $conn->prepare("UPDATE auctions SET current_price = ?, highest_bidder_id = ?, total_bids = total_bids + 1 WHERE id = ?");
                $stmt->execute([$bid_amount, $user_id, $auction_id]);
                
                // تسجيل المعاملة
                logTransaction($user_id, 'bid_freeze', $bid_amount, $auction_id, 'auction', 'حجز رصيد للمزايدة');
                
                $conn->commit();
                $success = 'تم تسجيل مزايدتك بنجاح!';
                
                // إعادة تحميل الصفحة لإظهار التحديثات
                header("Location: auction-details.php?id=$auction_id");
                exit;
                
            } catch(PDOException $e) {
                $conn->rollBack();
                $errors[] = 'حدث خطأ أثناء تسجيل المزايدة. حاول مرة أخرى.';
                // logError($e->getMessage()); // (Optional)
            }
        }
    }
}

// التحقق من KYC للمستخدم الحالي
$can_bid = false;
$kyc_message = '';
$current_user_kyc_status = '';

if (isLoggedIn()) {
    $stmt = $conn->prepare("SELECT kyc_status FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $current_user = $stmt->fetch();
    $current_user_kyc_status = $current_user['kyc_status'] ?? 'unverified';

    if ($current_user_kyc_status == 'verified') {
        $can_bid = true;
    } elseif ($current_user_kyc_status == 'unverified') {
        $kyc_message = 'للمزايدة، يجب عليك التحقق من هويتك أولاً.';
    } elseif ($current_user_kyc_status == 'pending') {
        $kyc_message = 'طلب التحقق من الهوية قيد المراجعة.';
    } elseif ($current_user_kyc_status == 'rejected') {
        $kyc_message = 'تم رفض طلب التحقق. يرجى إعادة رفع الوثائق.';
    }
}

// حساب الحد الأدنى للمزايدة
$min_bid_amount = $auction['current_price'] + ($auction['min_bid_increment'] ?? 100);

?>
<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($auction['brand'] . ' ' . $auction['model'] . ' ' . $auction['year']); ?> - المزاد</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Arial, sans-serif;
        }
        body {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            min-height: 100vh;
            color: #f1f5f9;
            position: relative;
            overflow-x: hidden;
        }
        /* Animated Background */
        body::before {
            content: "";
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: 
                radial-gradient(circle at 20% 50%, rgba(59, 130, 246, 0.15) 0%, transparent 50%),
                radial-gradient(circle at 80% 80%, rgba(168, 85, 247, 0.15) 0%, transparent 50%),
                radial-gradient(circle at 40% 20%, rgba(34, 197, 94, 0.15) 0%, transparent 50%);
            animation: backgroundShift 20s ease-in-out infinite;
            pointer-events: none;
            z-index: 0;
        }
        @keyframes backgroundShift {
            0%, 100% { transform: rotate(0deg) scale(1); }
            50% { transform: rotate(180deg) scale(1.1); }
        }
        /* Container */
        .container {
            position: relative;
            z-index: 1;
            max-width: 450px;
            margin: 0 auto;
            padding: 20px;
            padding-top: 80px; /* لإعطاء مساحة للـ header */
        }
        /* Header */
        .header {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            padding: 20px;
            border-radius: 0 0 30px 30px;
            margin: -20px -20px 20px -20px;
            position: fixed; /* تثبيت الهيدر في الأعلى */
            top: 0;
            left: 0;
            right: 0;
            width: 100%;
            z-index: 100;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0,0,0,0.2);
        }
        .header::before {
            content: "";
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
            animation: headerPulse 3s ease-in-out infinite;
        }
        @keyframes headerPulse {
            0%, 100% { transform: scale(1) rotate(0deg); }
            50% { transform: scale(1.1) rotate(180deg); }
        }
        .header-content {
            position: relative;
            z-index: 1;
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 450px; /* مطابقة للـ container */
            margin: 0 auto;
            padding: 0 20px;
        }
        .back-btn {
            background: rgba(255, 255, 255, 0.2);
            border: none;
            color: white;
            padding: 10px 15px;
            border-radius: 50px;
            cursor: pointer;
            backdrop-filter: blur(10px);
            transition: all 0.3s ease;
            text-decoration: none;
        }
        .back-btn:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: translateX(5px);
        }
        .lots-count {
            background: rgba(255, 255, 255, 0.9);
            color: #2563eb;
            padding: 5px 12px;
            border-radius: 50px;
            font-weight: bold;
            font-size: 14px;
        }
        
        /* === Circular Timer (New) === */
        .circular-timer-container {
            position: fixed;
            top: 85px; /* تحت الهيدر */
            right: 20px;
            z-index: 99;
            width: 120px;
            height: 120px;
            background: rgba(30, 41, 59, 0.8);
            border-radius: 50%;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 5px 20px rgba(0,0,0,0.3);
        }
        .timer-svg {
            width: 100%;
            height: 100%;
            transform: rotate(-90deg); /* لتبدأ الدائرة من الأعلى */
        }
        .timer-circle-bg {
            fill: none;
            stroke: rgba(255, 255, 255, 0.1);
            stroke-width: 8;
        }
        .timer-circle-progress {
            fill: none;
            stroke: #3b82f6; /* أزرق */
            stroke-width: 8;
            stroke-linecap: round;
            transition: stroke-dashoffset 1s linear;
        }
        .timer-circle-progress.ending-soon {
            stroke: #f59e0b; /* برتقالي */
        }
        .timer-circle-progress.last-minute {
            stroke: #ef4444; /* أحمر */
            animation: timerPulse 1s ease-in-out infinite;
        }
        .timer-text-group {
            transform: rotate(90deg); /* إعادة تدوير النص */
            font-size: 10px;
            font-weight: bold;
            fill: #f1f5f9;
        }
        .timer-text {
            font-size: 18px;
            font-weight: bold;
        }
        .timer-label {
            font-size: 8px;
            fill: #94a3b8;
        }
        .timer-ended-text {
            fill: #94a3b8;
            font-size: 12px;
            font-weight: bold;
            text-anchor: middle;
            transform: rotate(90deg) translate(0, -5px);
        }
         @keyframes timerPulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.7; }
        }
        /* === End Circular Timer === */


        /* Image Gallery */
        .image-gallery {
            margin: 20px 0;
            border-radius: 20px;
            overflow: hidden;
            background: rgba(30, 41, 59, 0.5);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        .main-image {
            width: 100%;
            height: 250px;
            object-fit: cover;
        }
        .thumbnails {
            display: flex;
            gap: 2px;
            padding: 2px;
            background: rgba(0, 0, 0, 0.3);
            overflow-x: auto;
        }
        .thumbnail {
            flex-shrink: 0;
            width: 90px;
            height: 80px;
            object-fit: cover;
            cursor: pointer;
            transition: all 0.3s ease;
            opacity: 0.7;
            border-radius: 4px;
        }
        .thumbnail:hover,
        .thumbnail.active {
            opacity: 1;
            transform: scale(1.05);
            border: 2px solid #3b82f6;
        }
        /* Car Info Card */
        .car-info {
            background: rgba(30, 41, 59, 0.8);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            padding: 20px;
            margin: 20px 0;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        .car-title {
            font-size: 20px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        .car-details {
            display: flex;
            align-items: center;
            gap: 10px;
            color: #94a3b8;
            font-size: 14px;
            margin-bottom: 10px;
        }
        
        /* Bid Status */
        .bid-status {
            background: rgba(30, 41, 59, 0.8);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            padding: 15px;
            margin: 20px 0;
            text-align: center;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        .bid-status-text {
            color: #94a3b8;
            font-size: 14px;
            margin-bottom: 5px;
        }
        .bid-status.active {
            animation: statusPulse 2s ease-in-out infinite;
            border: 1px solid rgba(59, 130, 246, 0.3);
        }
        .bid-status.outbid {
            border: 1px solid rgba(239, 68, 68, 0.3);
        }
         .bid-status.highest {
            border: 1px solid rgba(34, 197, 94, 0.3);
        }
        @keyframes statusPulse {
            0%, 100% { border-color: rgba(59, 130, 246, 0.3); }
            50% { border-color: rgba(59, 130, 246, 0.6); }
        }
        
        /* Current Price */
        .current-price {
            background: rgba(30, 41, 59, 0.8);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            padding: 20px;
            margin: 20px 0;
            text-align: center;
            border: 2px solid rgba(239, 68, 68, 0.3);
        }
        .price-label {
            color: #ef4444;
            font-size: 14px;
            margin-bottom: 10px;
            font-weight: bold;
        }
        .price-amount {
            font-size: 36px;
            font-weight: bold;
            color: white;
            animation: priceGlow 2s ease-in-out infinite;
        }
        @keyframes priceGlow {
            0%, 100% { text-shadow: 0 0 20px rgba(239, 68, 68, 0.5); }
            50% { text-shadow: 0 0 30px rgba(239, 68, 68, 0.8); }
        }
        /* Bid Input */
        .bid-input-container {
            background: rgba(30, 41, 59, 0.8);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            padding: 20px;
            margin: 20px 0;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        .bid-input {
            width: 100%;
            background: rgba(15, 23, 42, 0.8);
            border: 2px solid rgba(59, 130, 246, 0.3);
            padding: 15px;
            border-radius: 15px;
            color: white;
            font-size: 18px;
            text-align: center;
            transition: all 0.3s ease;
            -moz-appearance: textfield; /* Firefox */
        }
        .bid-input::-webkit-outer-spin-button,
        .bid-input::-webkit-inner-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }
        .bid-input:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 20px rgba(59, 130, 246, 0.3);
        }
        .bid-buttons {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }
        .bid-adjust-btn {
            flex: 1;
            background: rgba(59, 130, 246, 0.2);
            border: 1px solid rgba(59, 130, 246, 0.3);
            color: #60a5fa;
            padding: 10px;
            border-radius: 50px;
            font-size: 20px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .bid-adjust-btn:hover {
            background: rgba(59, 130, 246, 0.3);
            transform: scale(1.1);
        }
        .submit-bid-btn {
            width: 100%;
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            color: white;
            padding: 15px;
            border: none;
            border-radius: 50px;
            font-size: 18px;
            font-weight: bold;
            cursor: pointer;
            margin-top: 15px;
            transition: all 0.3s ease;
            box-shadow: 0 5px 20px rgba(59, 130, 246, 0.4);
        }
        .submit-bid-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(59, 130, 246, 0.6);
        }
        .submit-bid-btn:disabled {
            background: #475569;
            cursor: not-allowed;
            box-shadow: none;
        }
        /* Previous Bids */
        .previous-bids {
            background: rgba(30, 41, 59, 0.8);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            padding: 20px;
            margin: 20px 0;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        .bids-title {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .bid-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px;
            background: rgba(15, 23, 42, 0.5);
            border-radius: 15px;
            margin-bottom: 10px;
            border: 1px solid rgba(255, 255, 255, 0.05);
            animation: bidSlideIn 0.5s ease-out;
        }
        @keyframes bidSlideIn {
            from {
                opacity: 0;
                transform: translateX(-20px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }
        .bid-item.winner {
            background: linear-gradient(135deg, rgba(34, 197, 94, 0.2), rgba(22, 163, 74, 0.2));
            border: 1px solid rgba(34, 197, 94, 0.3);
        }
        .bidder-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .bidder-flag {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            font-weight: bold;
        }
        .bid-item.winner .bidder-flag {
             background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
             content: '🏆';
        }
        .bidder-name {
            font-size: 14px;
            color: #e2e8f0;
            font-weight: 500;
        }
        .bidder-location {
            font-size: 12px;
            color: #64748b;
        }
        .bid-amount {
            font-size: 16px;
            font-weight: bold;
            color: #60a5fa;
        }
        .bid-item.winner .bid-amount {
            color: #86efac;
        }
        
        /* === Vehicle Details Grid (Updated) === */
        .details-section {
            background: rgba(30, 41, 59, 0.8);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            padding: 20px;
            margin: 20px 0;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        .details-title {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 15px;
        }
        .details-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
        }
        .detail-item {
            padding: 10px;
            background: rgba(15, 23, 42, 0.5);
            border-radius: 10px;
            border: 1px solid rgba(255, 255, 255, 0.05);
        }
        .detail-label {
            font-size: 12px;
            color: #64748b;
            margin-bottom: 5px;
        }
        .detail-value {
            font-size: 14px;
            color: #f1f5f9;
            font-weight: 500;
        }
        .detail-full-width {
            grid-column: 1 / -1;
            line-height: 1.6;
        }
        /* === End Vehicle Details === */

        /* Alerts */
        .alert {
            padding: 15px;
            border-radius: 15px;
            margin: 15px 0;
            animation: alertFadeIn 0.5s ease-out;
            font-weight: 500;
        }
        .alert a {
            display: inline-block;
            margin-top: 10px;
            padding: 5px 10px;
            background: rgba(255,255,255,0.2);
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
        }
        @keyframes alertFadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .alert-error {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #fca5a5;
        }
        .alert-success {
            background: rgba(34, 197, 94, 0.1);
            border: 1px solid rgba(34, 197, 94, 0.3);
            color: #86efac;
        }
        .alert-warning {
            background: rgba(245, 158, 11, 0.1);
            border: 1px solid rgba(245, 158, 11, 0.3);
            color: #fcd34d;
        }
        
        /* Responsive */
        @media (max-width: 480px) {
            .container {
                padding: 10px;
                padding-top: 80px;
            }
            .header-content {
                padding: 0 10px;
            }
            .circular-timer-container {
                width: 90px;
                height: 90px;
                top: 80px;
                right: 10px;
            }
            .timer-svg {
                stroke-width: 6;
            }
            .timer-text { font-size: 14px; }
            .timer-label { font-size: 7px; }
            .timer-ended-text { font-size: 10px; }

            .price-amount {
                font-size: 28px;
            }
        }
    </style>

    <!-- Responsive & Mobile Menu CSS -->
    <link rel="stylesheet" href="assets/css/responsive.css">

</head>
<body>
    <!-- Timer الدائري الجديد -->
    <div class="circular-timer-container" data-remaining="<?php echo $time_remaining; ?>" data-total-duration="<?php echo $total_duration > 0 ? $total_duration : 1; ?>">
        <svg class="timer-svg" viewBox="0 0 100 100">
            <!-- الخلفية -->
            <circle class="timer-circle-bg" cx="50" cy="50" r="42"></circle>
            <!-- مؤشر التقدم -->
            <circle class="timer-circle-progress" id="timer-circle-progress" cx="50" cy="50" r="42"></circle>
            
            <!-- النص (يتم تحديثه بـ JS) -->
            <g class="timer-text-group" id="timer-text-active">
                <text id="timer-days" x="30" y="35" text-anchor="middle" class="timer-text">--</text>
                <text id="timer-hours" x="70" y="35" text-anchor="middle" class="timer-text">--</text>
                <text x="30" y="45" text-anchor="middle" class="timer-label">أيام</text>
                <text x="70" y="45" text-anchor="middle" class="timer-label">ساعات</text>
                
                <text id="timer-minutes" x="30" y="70" text-anchor="middle" class="timer-text">--</text>
                <text id="timer-seconds" x="70" y="70" text-anchor="middle" class="timer-text">--</text>
                <text x="30" y="80" text-anchor="middle" class="timer-label">دقائق</text>
                <text x="70" y="80" text-anchor="middle" class="timer-label">ثواني</text>
            </g>
            <text id="timer-text-ended" class="timer-ended-text" x="50" y="55" display="none">انتهى المزاد</text>
        </svg>
    </div>


    <div class="container">
        <!-- Header -->
        <div class="header">
            <div class="header-content">
                <a href="auctions.php" class="back-btn">← رجوع</a>
                <div class="lots-count"><?php echo $auction['total_bids']; ?> مزايدة</div>
            </div>
        </div>

        <!-- Image Gallery -->
        <div class="image-gallery">
            <?php 
            $main_image = !empty($images) ? $images[0]['image_path'] : 'https://placehold.co/600x400/1e293b/f1f5f9?text=No+Image';
            ?>
            <img src="<?php echo htmlspecialchars($main_image); ?>" alt="Main" class="main-image" id="mainImage">
                        
            <?php if (count($images) > 1): ?>
            <div class="thumbnails">
                <?php foreach ($images as $index => $img): ?>
                <img src="<?php echo htmlspecialchars($img['image_path']); ?>"
                     alt="Thumbnail <?php echo $index + 1; ?>"
                     class="thumbnail <?php echo $index === 0 ? 'active' : ''; ?>"
                     onclick="changeMainImage(this, '<?php echo htmlspecialchars($img['image_path']); ?>')">
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- Car Info -->
        <div class="car-info">
            <div class="car-title">
                <?php echo htmlspecialchars($auction['brand'] . ' ' . $auction['model'] . ' ' . $auction['year']); ?>
            </div>
            <div class="car-details">
                <span>Lot: <?php echo htmlspecialchars($auction['id']); ?></span>
            </div>
        </div>

        <!-- Bid Status -->
        <div class="bid-status <?php echo !$auction_ended ? 'active' : ''; ?>">
            <?php if (!isLoggedIn()): ?>
                <div class="bid-status-text">أنت لم تقم بالمزايدة (زائر)</div>
            <?php else: ?>
                <?php 
                $user_bid_status = "not_bid"; // 'not_bid', 'outbid', 'highest'
                $user_highest_bid = 0;
                
                if ($auction['highest_bidder_id'] == $_SESSION['user_id']) {
                    $user_bid_status = "highest";
                } else {
                    foreach ($bids as $bid) {
                        if ($bid['user_id'] == $_SESSION['user_id']) {
                            $user_bid_status = "outbid";
                            break; 
                        }
                    }
                }
                
                if ($user_bid_status == "highest"): ?>
                    <div class="bid-status-text" style="color: #86efac;">✓ أنت المزايد الأعلى حالياً</div>
                <?php elseif ($user_bid_status == "outbid"): ?>
                    <div class="bid-status-text" style="color: #fca5a5;">! تم تجاوز مزايدتك</div>
                <?php else: ?>
                    <div class="bid-status-text">أنت لم تقم بالمزايدة بعد</div>
                <?php endif; ?>
            <?php endif; ?>
        </div>


        <!-- Current Price -->
        <div class="current-price">
            <div class="price-label">Current Bid (USD)</div>
            <div class="price-amount" id="currentPrice">$<?php echo number_format($auction['current_price'], 0); ?></div>
        </div>

        <!-- Alerts -->
        <?php if (!empty($errors)): ?>
            <div class="alert alert-error">
                <?php foreach ($errors as $error): ?>
                    <div>⚠️ <?php echo $error; ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success">
                ✅ <?php echo $success; ?>
            </div>
        <?php endif; ?>


        <!-- Bid Input Form & KYC Logic -->
        <?php if (isLoggedIn()): ?>
            <?php if (!$can_bid && $kyc_message): ?>
                <!-- إذا لم يتم التحقق من KYC -->
                <div class="alert alert-warning">
                    <strong>⚠️ يجب التحقق من الهوية</strong><br>
                    <?php echo $kyc_message; ?>
                    <?php if ($current_user_kyc_status != 'pending'): ?>
                        <a href="kyc/submit.php">التحقق من الهوية الآن</a>
                    <?php endif; ?>
                </div>
            <?php elseif ($_SESSION['user_id'] == $auction['seller_user_id']): ?>
                <!-- إذا كان هو البائع -->
                <div class="alert alert-warning">
                    لا يمكنك المزايدة على منتجك الخاص
                </div>
            <?php elseif ($auction_ended): ?>
                <!-- إذا انتهى المزاد -->
                <div class="alert alert-warning">
                    انتهى هذا المزاد
                </div>
            <?php else: ?>
                <!-- جاهز للمزايدة: اعرض النموذج -->
                <div class="bid-input-container" id="bidForm">
                    <form method="POST" action="">
                        <input type="number"
                               name="bid_amount"
                               class="bid-input"
                               id="bidAmount"
                               min="<?php echo $min_bid_amount; ?>"
                               value="<?php echo $min_bid_amount; ?>"
                               step="<?php echo $auction['min_bid_increment'] ?? 100; ?>"
                               required>
                                
                        <div class="bid-buttons">
                            <button type="button" class="bid-adjust-btn" onclick="adjustBid(-<?php echo $auction['min_bid_increment'] ?? 100; ?>)">−</button>
                            <button type="button" class="bid-adjust-btn" onclick="adjustBid(<?php echo $auction['min_bid_increment'] ?? 100; ?>)">+</button>
                        </div>
                                
                        <button type="submit" name="place_bid" class="submit-bid-btn">
                            Bid $<span id="bidButtonAmount"><?php echo number_format($min_bid_amount, 0); ?></span>
                        </button>
                    </form>
                </div>
            <?php endif; ?>

        <?php else: ?>
            <!-- إذا كان زائراً -->
            <div class="alert alert-warning">
                يجب تسجيل الدخول للمزايدة
                <a href="auth/login.php">تسجيل الدخول</a>
            </div>
        <?php endif; ?>


        <!-- Previous Bids -->
        <div class="previous-bids">
            <div class="bids-title">
                <span>Previous Bids</span>
                <span style="color: #64748b; font-size: 14px;"><?php echo count($bids); ?> bids</span>
            </div>
                        
            <?php if (!empty($bids)): ?>
                <?php foreach ($bids as $index => $bid): ?>
                <div class="bid-item <?php echo ($bid['user_id'] == $auction['highest_bidder_id']) ? 'winner' : ''; ?>">
                    <div class="bidder-info">
                        <div class="bidder-flag">
                            <?php echo htmlspecialchars(strtoupper(substr($bid['username'], 0, 1))); ?>
                        </div>
                        <div>
                            <div class="bidder-name"><?php echo htmlspecialchars($bid['username']); ?></div>
                        </div>
                    </div>
                    <div class="bid-amount">$<?php echo number_format($bid['bid_amount'], 0); ?></div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div style="text-align: center; color: #64748b; padding: 20px;">
                    لا توجد مزايدات بعد
                </div>
            <?php endif; ?>
        </div>

        <!-- Vehicle Details (Updated based on SQL) -->
        <div class="details-section">
            <div class="details-title">تفاصيل السيارة</div>
            <div class="details-grid">
                
                <?php if (!empty($auction['brand'])): ?>
                <div class="detail-item">
                    <div class="detail-label">الماركة</div>
                    <div class="detail-value"><?php echo htmlspecialchars($auction['brand']); ?></div>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($auction['model'])): ?>
                <div class="detail-item">
                    <div class="detail-label">الموديل</div>
                    <div class="detail-value"><?php echo htmlspecialchars($auction['model']); ?></div>
                </div>
                <?php endif; ?>

                <?php if (!empty($auction['year'])): ?>
                <div class="detail-item">
                    <div class="detail-label">سنة الصنع</div>
                    <div class="detail-value"><?php echo htmlspecialchars($auction['year']); ?></div>
                </div>
                <?php endif; ?>

                <?php if (isset($auction['mileage']) && $auction['mileage'] !== null): ?>
                <div class="detail-item">
                    <div class="detail-label">المسافة المقطوعة</div>
                    <div class="detail-value"><?php echo number_format($auction['mileage']); ?> km</div>
                </div>
                <?php endif; ?>

                <?php if (!empty($auction['color'])): ?>
                <div class="detail-item">
                    <div class="detail-label">اللون</div>
                    <div class="detail-value"><?php echo htmlspecialchars($auction['color']); ?></div>
                </div>
                <?php endif; ?>

                <?php if (!empty($auction['transmission'])): ?>
                <div class="detail-item">
                    <div class="detail-label">ناقل الحركة</div>
                    <div class="detail-value"><?php echo $auction['transmission'] == 'automatic' ? 'أوتوماتيك' : 'يدوي'; ?></div>
                </div>
                <?php endif; ?>

                <?php if (!empty($auction['fuel_type'])): ?>
                <div class="detail-item">
                    <div class="detail-label">نوع الوقود</div>
                    <div class="detail-value"><?php echo htmlspecialchars($auction['fuel_type']); ?></div>
                </div>
                <?php endif; ?>

            </div>

            <?php if (!empty($auction['description'])): ?>
                <div class="details-title" style="margin-top: 20px;">الوصف</div>
                <div class="detail-item detail-full-width">
                    <div class="detail-value"><?php echo nl2br(htmlspecialchars($auction['description'])); ?></div>
                </div>
            <?php endif; ?>

            <?php if (!empty($auction['condition_notes'])): ?>
                <div class="details-title" style="margin-top: 20px;">ملاحظات الحالة</div>
                <div class="detail-item detail-full-width">
                    <div class="detail-value"><?php echo nl2br(htmlspecialchars($auction['condition_notes'])); ?></div>
                </div>
            <?php endif; ?>
        </div>

    </div> <!-- /container -->

    <!-- زر الترجمة من الكود القديم -->
    <div style="position: fixed; top: 20px; left: 20px; z-index: 1000;">
        <?php include 'includes/lang-switcher.php'; ?>
    </div>
    <script src="js/auto-translate.js"></script>

    <script>
        // JS Gallery
        function changeMainImage(thumbnail, newSrc) {
            document.getElementById('mainImage').src = newSrc;
            const thumbnails = document.querySelectorAll('.thumbnail');
            thumbnails.forEach(thumb => thumb.classList.remove('active'));
            thumbnail.classList.add('active');
        }

        // JS Timer (Updated for Circular Timer)
        const timerContainer = document.querySelector('.circular-timer-container');
        let remaining = <?php echo $time_remaining; ?>;
        const totalDuration = <?php echo $total_duration > 0 ? $total_duration : 1; ?>;
        
        const circle = document.getElementById('timer-circle-progress');
        const radius = circle.r.baseVal.value;
        const circumference = radius * 2 * Math.PI;
        circle.style.strokeDasharray = `${circumference} ${circumference}`;
        circle.style.strokeDashoffset = circumference;

        const textActive = document.getElementById('timer-text-active');
        const textEnded = document.getElementById('timer-text-ended');
        const elDays = document.getElementById('timer-days');
        const elHours = document.getElementById('timer-hours');
        const elMinutes = document.getElementById('timer-minutes');
        const elSeconds = document.getElementById('timer-seconds');
        
        function setProgress(percent) {
            const offset = circumference - (percent / 100) * circumference;
            circle.style.strokeDashoffset = offset;
        }

        function updateTimer() {
            if (remaining <= 0) {
                textActive.style.display = 'none';
                textEnded.style.display = 'block';
                setProgress(0);
                clearInterval(timerInterval);
                
                const bidButton = document.querySelector('.submit-bid-btn');
                if(bidButton) bidButton.disabled = true;
                return;
            }

            // تلوين الدائرة
            if (remaining < 60) { // آخر دقيقة
                circle.classList.add('last-minute');
            } else if (remaining < 3600) { // آخر ساعة
                circle.classList.add('ending-soon');
            }

            const days = Math.floor(remaining / 86400);
            const hours = Math.floor((remaining % 86400) / 3600);
            const minutes = Math.floor((remaining % 3600) / 60);
            const seconds = remaining % 60;
            
            elDays.textContent = String(days).padStart(2, '0');
            elHours.textContent = String(hours).padStart(2, '0');
            elMinutes.textContent = String(minutes).padStart(2, '0');
            elSeconds.textContent = String(seconds).padStart(2, '0');
            
            const percent = (remaining / totalDuration) * 100;
            setProgress(percent > 0 ? percent : 0);

            remaining--;
        }
        
        updateTimer(); // Call once immediately
        var timerInterval = setInterval(updateTimer, 1000);


        // JS Bid Form
        const bidAmountInput = document.getElementById('bidAmount');
        const bidButtonAmount = document.getElementById('bidButtonAmount');
        const minBid = <?php echo $min_bid_amount; ?>;
        const step = <?php echo $auction['min_bid_increment'] ?? 100; ?>;

        function adjustBid(amount) {
            if (!bidAmountInput) return;
            let currentVal = parseInt(bidAmountInput.value) || minBid;
            let newVal = currentVal + amount;
            
            if (newVal < minBid) {
                newVal = minBid;
            }
            
            bidAmountInput.value = newVal;
            updateBidButton(newVal);
        }

        function updateBidButton(amount) {
            if (bidButtonAmount) {
                bidButtonAmount.innerText = new Intl.NumberFormat('en-US').format(amount);
            }
        }

        if (bidAmountInput) {
            bidAmountInput.addEventListener('input', (e) => {
                let val = parseInt(e.target.value) || minBid;
                 if (val < minBid) {
                    val = minBid;
                 }
                updateBidButton(val);
            });
             bidAmountInput.addEventListener('blur', (e) => {
                let val = parseInt(e.target.value) || minBid;
                 if (val < minBid) {
                    e.target.value = minBid;
                    updateBidButton(minBid);
                 }
                 // Make sure value is a multiple of step
                 if (val % step !== 0 && val > minBid) {
                     val = Math.ceil(val / step) * step;
                     if(val < minBid) val = minBid;
                     e.target.value = val;
                     updateBidButton(val);
                 }
            });
        }
        
    </script>

    <!-- Mobile Menu & Responsive JavaScript -->
    <script src="assets/js/mobile-menu.js"></script>

</body>
</html>

