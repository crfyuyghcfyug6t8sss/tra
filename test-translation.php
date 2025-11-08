<?php
/**
 * صفحة اختبار نظام الترجمة
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html>\n";
echo "<html>\n";
echo "<head>\n";
echo "    <meta charset='UTF-8'>\n";
echo "    <title>Test Translation System</title>\n";
echo "    <style>\n";
echo "        body { font-family: Arial; padding: 20px; background: #f5f5f5; }\n";
echo "        .success { color: green; font-weight: bold; }\n";
echo "        .error { color: red; font-weight: bold; }\n";
echo "        .info { background: #e3f2fd; padding: 15px; margin: 10px 0; border-radius: 5px; }\n";
echo "        .code { background: #263238; color: #aed581; padding: 10px; border-radius: 5px; font-family: monospace; }\n";
echo "    </style>\n";
echo "</head>\n";
echo "<body>\n";
echo "    <h1>🔍 اختبار نظام الترجمة - BidOra</h1>\n";

// 1. Test translator.php
echo "    <div class='info'>\n";
echo "        <h3>1️⃣ اختبار تحميل translator.php</h3>\n";
try {
    require_once 'includes/translator.php';
    echo "        <p class='success'>✅ تم تحميل translator.php بنجاح</p>\n";
} catch (Exception $e) {
    echo "        <p class='error'>❌ خطأ: " . $e->getMessage() . "</p>\n";
}
echo "    </div>\n";

// 2. Test current language
echo "    <div class='info'>\n";
echo "        <h3>2️⃣ اللغة الحالية</h3>\n";
if (function_exists('currentLang')) {
    $lang = currentLang();
    echo "        <p class='success'>✅ اللغة الحالية: <strong>$lang</strong></p>\n";
} else {
    echo "        <p class='error'>❌ دالة currentLang() غير موجودة</p>\n";
}
echo "    </div>\n";

// 3. Test text direction
echo "    <div class='info'>\n";
echo "        <h3>3️⃣ اتجاه النص</h3>\n";
if (function_exists('textDirection')) {
    $dir = textDirection();
    echo "        <p class='success'>✅ اتجاه النص: <strong>$dir</strong></p>\n";
} else {
    echo "        <p class='error'>❌ دالة textDirection() غير موجودة</p>\n";
}
echo "    </div>\n";

// 4. Test translations
echo "    <div class='info'>\n";
echo "        <h3>4️⃣ اختبار الترجمات</h3>\n";
if (function_exists('__')) {
    echo "        <p class='success'>✅ دالة __() موجودة</p>\n";
    echo "        <div class='code'>\n";
    echo "            home: " . __('home') . "<br>\n";
    echo "            auctions: " . __('auctions') . "<br>\n";
    echo "            store: " . __('store') . "<br>\n";
    echo "            login: " . __('login') . "<br>\n";
    echo "            dashboard: " . __('dashboard') . "<br>\n";
    echo "        </div>\n";
} else {
    echo "        <p class='error'>❌ دالة __() غير موجودة</p>\n";
}
echo "    </div>\n";

// 5. Test database connection
echo "    <div class='info'>\n";
echo "        <h3>5️⃣ اختبار الاتصال بقاعدة البيانات</h3>\n";
try {
    require_once 'config/database.php';
    if ($conn !== null) {
        echo "        <p class='success'>✅ الاتصال بقاعدة البيانات ناجح</p>\n";
    } else {
        echo "        <p class='error'>⚠️ الاتصال بقاعدة البيانات فشل (لكن الصفحة لا تزال تعمل)</p>\n";
    }
} catch (Exception $e) {
    echo "        <p class='error'>❌ خطأ: " . $e->getMessage() . "</p>\n";
}
echo "    </div>\n";

// 6. Test functions
echo "    <div class='info'>\n";
echo "        <h3>6️⃣ اختبار الدوال المساعدة</h3>\n";
try {
    require_once 'includes/functions.php';
    echo "        <p class='success'>✅ تم تحميل functions.php بنجاح</p>\n";

    if (function_exists('isLoggedIn')) {
        echo "        <p class='success'>✅ دالة isLoggedIn() موجودة</p>\n";
    }
    if (function_exists('clean')) {
        echo "        <p class='success'>✅ دالة clean() موجودة</p>\n";
    }
} catch (Exception $e) {
    echo "        <p class='error'>❌ خطأ: " . $e->getMessage() . "</p>\n";
}
echo "    </div>\n";

// 7. Test language switcher
echo "    <div class='info'>\n";
echo "        <h3>7️⃣ اختبار زر تبديل اللغة</h3>\n";
if (file_exists('includes/lang-switcher.php')) {
    echo "        <p class='success'>✅ ملف lang-switcher.php موجود</p>\n";
    echo "        <br>\n";
    include 'includes/lang-switcher.php';
} else {
    echo "        <p class='error'>❌ ملف lang-switcher.php غير موجود</p>\n";
}
echo "    </div>\n";

// 8. Test translation files
echo "    <div class='info'>\n";
echo "        <h3>8️⃣ اختبار ملفات الترجمة</h3>\n";
$langs = ['ar', 'en', 'de', 'tr'];
foreach ($langs as $lang) {
    $file = "translations/$lang.json";
    if (file_exists($file)) {
        $size = filesize($file);
        echo "        <p class='success'>✅ $file موجود (حجم: $size بايت)</p>\n";
    } else {
        echo "        <p class='error'>❌ $file غير موجود</p>\n";
    }
}
echo "    </div>\n";

echo "    <hr>\n";
echo "    <p style='text-align: center;'>\n";
echo "        <a href='index.php' style='padding: 10px 20px; background: #667eea; color: white; text-decoration: none; border-radius: 5px;'>العودة للصفحة الرئيسية</a>\n";
echo "    </p>\n";

echo "</body>\n";
echo "</html>\n";
?>
