<?php
/**
 * Helper Functions
 * BidOra - الدوال المساعدة
 */

/**
 * فحص تسجيل الدخول
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * إعادة التوجيه
 */
function redirect($url) {
    header("Location: $url");
    exit;
}

/**
 * تنظيف البيانات
 */
function clean($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

/**
 * تعيين رسالة flash
 */
function setMessage($message, $type = 'info') {
    $_SESSION['flash_message'] = $message;
    $_SESSION['flash_type'] = $type;
}

/**
 * الحصول على رسالة flash
 */
function getMessage() {
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        $type = $_SESSION['flash_type'] ?? 'info';
        unset($_SESSION['flash_message']);
        unset($_SESSION['flash_type']);
        return ['message' => $message, 'type' => $type];
    }
    return null;
}

/**
 * تنسيق التاريخ
 */
function formatDate($date, $format = 'Y-m-d H:i:s') {
    return date($format, strtotime($date));
}

/**
 * تنسيق المبلغ المالي
 */
function formatMoney($amount, $currency = '$') {
    return $currency . number_format($amount, 2);
}

/**
 * الحصول على الوقت المتبقي
 */
function getTimeRemaining($endTime) {
    $now = time();
    $end = strtotime($endTime);
    $diff = $end - $now;

    if ($diff <= 0) {
        return 'انتهى';
    }

    $days = floor($diff / (60 * 60 * 24));
    $hours = floor(($diff % (60 * 60 * 24)) / (60 * 60));
    $minutes = floor(($diff % (60 * 60)) / 60);

    if ($days > 0) {
        return "$days يوم $hours ساعة";
    } elseif ($hours > 0) {
        return "$hours ساعة $minutes دقيقة";
    } else {
        return "$minutes دقيقة";
    }
}

/**
 * الحصول على widget الـ chatbot (placeholder)
 */
function getChatbotWidget() {
    // يمكن إضافة كود chatbot هنا
    return '';
}

/**
 * رفع صورة
 */
function uploadImage($file, $directory = 'uploads/') {
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        return false;
    }

    // التحقق من نوع الملف
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    if (!in_array($file['type'], $allowedTypes)) {
        return false;
    }

    // إنشاء اسم فريد للملف
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid() . '.' . $extension;
    $destination = $directory . $filename;

    // إنشاء المجلد إذا لم يكن موجوداً
    if (!is_dir($directory)) {
        mkdir($directory, 0755, true);
    }

    // نقل الملف
    if (move_uploaded_file($file['tmp_name'], $destination)) {
        return $destination;
    }

    return false;
}

/**
 * حذف صورة
 */
function deleteImage($path) {
    if (file_exists($path)) {
        return unlink($path);
    }
    return false;
}

/**
 * توليد رمز عشوائي
 */
function generateToken($length = 32) {
    return bin2hex(random_bytes($length));
}

/**
 * التحقق من صحة البريد الإلكتروني
 */
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

/**
 * إرسال بريد إلكتروني (placeholder)
 */
function sendEmail($to, $subject, $message) {
    // يمكن إضافة كود إرسال البريد هنا
    // مثل: PHPMailer أو mail() function
    return true;
}

?>
