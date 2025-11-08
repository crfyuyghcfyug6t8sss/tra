<?php
/**
 * Database Configuration
 * BidOra - إعدادات قاعدة البيانات
 */

// إعدادات قاعدة البيانات
define('DB_HOST', 'localhost');
define('DB_NAME', 'bidora');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// متغير الاتصال
$conn = null;

try {
    // إنشاء اتصال PDO
    $conn = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET,
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch (PDOException $e) {
    // في حالة فشل الاتصال، عرض رسالة خطأ بدون إيقاف التنفيذ
    error_log("Database connection error: " . $e->getMessage());

    // عرض رسالة للمستخدم فقط في بيئة التطوير
    if (isset($_SERVER['SERVER_NAME']) && $_SERVER['SERVER_NAME'] === 'localhost') {
        echo "<!-- Database connection error. Please check config/database.php -->";
        echo "<!-- Error: " . htmlspecialchars($e->getMessage()) . " -->";
    }

    // لا تستخدم die() لتجنب infinite reload
    // $conn سيبقى null والصفحات يجب أن تتحقق منه
}

?>
