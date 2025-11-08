<?php
/**
 * سكربت تطبيق نظام الترجمة على كل الصفحات
 * Apply Translation System to All Pages
 *
 * Usage: php tools/apply-translation-to-all.php
 */

$rootDir = __DIR__ . '/../';

// قائمة الصفحات PHP في المشروع
$phpFiles = glob($rootDir . '*.php');

echo "=================================\n";
echo "تطبيق نظام الترجمة على كل الصفحات\n";
echo "=================================\n\n";

echo "تم العثور على " . count($phpFiles) . " ملف PHP\n\n";

$successCount = 0;
$failCount = 0;
$skippedCount = 0;

foreach ($phpFiles as $file) {
    $filename = basename($file);
    echo "جاري معالجة: $filename ... ";

    // قراءة محتوى الملف
    $content = file_get_contents($file);

    // تخطي الملفات التي تحتوي بالفعل على نظام الترجمة
    if (strpos($content, "require_once 'includes/translator.php'") !== false) {
        echo "تم التخطي (موجود بالفعل)\n";
        $skippedCount++;
        continue;
    }

    $modified = false;

    // 1. إضافة require للمترجم في بداية الملف
    if (strpos($content, '<?php') !== false && strpos($content, "require_once 'includes/translator.php'") === false) {
        // البحث عن أول سطر PHP
        $content = preg_replace(
            '/(<\?php\s*\n)/',
            "$1// تحميل نظام الترجمة\nrequire_once 'includes/translator.php';\n\n",
            $content,
            1
        );
        $modified = true;
    }

    // 2. تحديث HTML tag
    if (preg_match('/<html[^>]*>/i', $content)) {
        $content = preg_replace(
            '/<html\s+lang="ar"\s+dir="rtl">/i',
            '<html lang="<?php echo currentLang(); ?>" dir="<?php echo textDirection(); ?>">',
            $content
        );
        $modified = true;
    }

    // 3. تحديث title tag
    if (preg_match('/<title>[^<]*بيدورا[^<]*<\/title>/iu', $content)) {
        $content = preg_replace(
            '/<title>([^<]*)<\/title>/iu',
            '<title><?php echo __("site_name"); ?> - <?php echo __("luxury_car_auction"); ?></title>',
            $content,
            1
        );
        $modified = true;
    }

    // 4. إضافة CSS responsive قبل </head>
    if (strpos($content, '</head>') !== false && strpos($content, 'responsive.css') === false) {
        $content = str_replace(
            '</head>',
            "\n    <!-- Responsive & Mobile Menu CSS -->\n    <link rel=\"stylesheet\" href=\"assets/css/responsive.css\">\n\n</head>",
            $content
        );
        $modified = true;
    }

    // 5. إضافة JavaScript للموبايل قبل </body>
    if (strpos($content, '</body>') !== false && strpos($content, 'mobile-menu.js') === false) {
        $content = str_replace(
            '</body>',
            "\n    <!-- Mobile Menu & Responsive JavaScript -->\n    <script src=\"assets/js/mobile-menu.js\"></script>\n\n</body>",
            $content
        );
        $modified = true;
    }

    // 6. تحديث lang-switcher include
    if (strpos($content, "include 'includes/lang-switcher.php'") !== false) {
        // already has it
    } elseif (preg_match('/<header/i', $content)) {
        // إضافة lang-switcher في الهيدر
        $content = preg_replace(
            '/(<header[^>]*>)/i',
            "$1\n    <div style=\"position: fixed; top: 20px; left: 20px; z-index: 1000;\">\n        <?php include 'includes/lang-switcher.php'; ?>\n    </div>\n",
            $content,
            1
        );
        $modified = true;
    }

    // حفظ الملف إذا تم التعديل
    if ($modified) {
        file_put_contents($file, $content);
        echo "✅ تم التعديل\n";
        $successCount++;
    } else {
        echo "⚠️  لم يتم التعديل\n";
        $failCount++;
    }
}

echo "\n=================================\n";
echo "النتائج:\n";
echo "=================================\n";
echo "✅ تم التعديل: $successCount\n";
echo "⚠️  تم التخطي: $skippedCount\n";
echo "❌ فشل التعديل: $failCount\n";
echo "=================================\n\n";

// تحديث النصوص المشتركة
echo "الآن يمكنك تحديث النصوص يدوياً في كل صفحة باستخدام:\n";
echo "<?php echo __('key'); ?>\n\n";
echo "مثال:\n";
echo "الرئيسية => <?php echo __('home'); ?>\n";
echo "المزادات => <?php echo __('auctions'); ?>\n";
echo "المتجر => <?php echo __('store'); ?>\n\n";

?>
