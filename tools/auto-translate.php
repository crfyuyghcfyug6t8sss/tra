<?php
/**
 * سكربت الترجمة التلقائية - Auto Translation Script
 * يستخدم Google Translate API المجاني لترجمة الملفات
 * يمكن تشغيله من سطر الأوامر: php tools/auto-translate.php
 */

// تحديد مجلد الترجمات
$translationsDir = __DIR__ . '/../translations/';

// اللغات المدعومة
$languages = [
    'ar' => 'Arabic',
    'en' => 'English',
    'de' => 'German',
    'tr' => 'Turkish'
];

// اللغة المصدر (العربية)
$sourceLang = 'ar';

/**
 * ترجمة نص باستخدام Google Translate API المجاني
 */
function translateText($text, $fromLang, $toLang) {
    // إذا كانت اللغة نفسها، لا حاجة للترجمة
    if ($fromLang === $toLang) {
        return $text;
    }

    // استخدام Google Translate API المجاني
    $url = "https://translate.googleapis.com/translate_a/single?client=gtx&sl="
           . $fromLang . "&tl=" . $toLang . "&dt=t&q=" . urlencode($text);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');

    $response = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error) {
        echo "خطأ في الترجمة: $error\n";
        return $text;
    }

    // فك تشفير الاستجابة
    $result = json_decode($response);

    if (isset($result[0][0][0])) {
        return $result[0][0][0];
    }

    return $text;
}

/**
 * ترجمة ملف كامل
 */
function translateFile($sourceFile, $targetFile, $fromLang, $toLang) {
    echo "جاري الترجمة من $fromLang إلى $toLang...\n";

    // قراءة الملف المصدر
    if (!file_exists($sourceFile)) {
        echo "الملف المصدر غير موجود: $sourceFile\n";
        return false;
    }

    $sourceData = json_decode(file_get_contents($sourceFile), true);

    if (!$sourceData) {
        echo "خطأ في قراءة الملف المصدر\n";
        return false;
    }

    // إذا كان الملف الهدف موجود، نقرأه للحفاظ على الترجمات اليدوية
    $targetData = [];
    if (file_exists($targetFile)) {
        $existing = json_decode(file_get_contents($targetFile), true);
        if ($existing) {
            $targetData = $existing;
        }
    }

    // ترجمة كل عنصر
    $total = count($sourceData);
    $current = 0;

    foreach ($sourceData as $key => $value) {
        $current++;

        // إذا كانت الترجمة موجودة بالفعل، نتخطاها (للحفاظ على التعديلات اليدوية)
        if (isset($targetData[$key]) && !empty($targetData[$key])) {
            echo "[$current/$total] تم التخطي (موجود): $key\n";
            continue;
        }

        // ترجمة النص
        echo "[$current/$total] جاري الترجمة: $key\n";
        $translated = translateText($value, $fromLang, $toLang);
        $targetData[$key] = $translated;

        // انتظار قصير لتجنب تجاوز حدود API
        usleep(100000); // 0.1 ثانية
    }

    // حفظ الملف المترجم
    $json = json_encode($targetData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    file_put_contents($targetFile, $json);

    echo "تم حفظ الترجمة في: $targetFile\n\n";
    return true;
}

/**
 * دالة لإضافة مفاتيح جديدة للترجمة
 */
function addTranslationKeys($newKeys = []) {
    global $translationsDir, $languages, $sourceLang;

    if (empty($newKeys)) {
        echo "لا توجد مفاتيح جديدة لإضافتها\n";
        return;
    }

    foreach ($languages as $lang => $langName) {
        $file = $translationsDir . $lang . '.json';

        // قراءة الملف الحالي
        $data = [];
        if (file_exists($file)) {
            $data = json_decode(file_get_contents($file), true);
        }

        // إضافة المفاتيح الجديدة
        foreach ($newKeys as $key => $value) {
            if (!isset($data[$key])) {
                if ($lang === $sourceLang) {
                    // للغة المصدر، استخدم القيمة مباشرة
                    $data[$key] = $value;
                } else {
                    // للغات الأخرى، ترجم القيمة
                    $data[$key] = translateText($value, $sourceLang, $lang);
                    echo "تمت إضافة: $key => {$data[$key]} [$lang]\n";
                    usleep(100000);
                }
            }
        }

        // حفظ الملف
        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        file_put_contents($file, $json);
    }

    echo "تمت إضافة المفاتيح الجديدة بنجاح!\n";
}

// ====================
// التشغيل الرئيسي
// ====================

echo "=================================\n";
echo "سكربت الترجمة التلقائية - BidOra\n";
echo "=================================\n\n";

// فحص الأوامر
if (isset($argv[1])) {
    $command = $argv[1];

    if ($command === 'add' && isset($argv[2]) && isset($argv[3])) {
        // إضافة مفتاح جديد
        // مثال: php tools/auto-translate.php add "new_key" "القيمة بالعربية"
        $key = $argv[2];
        $value = $argv[3];
        addTranslationKeys([$key => $value]);
        exit;
    }
}

// الترجمة التلقائية الكاملة
$sourceFile = $translationsDir . $sourceLang . '.json';

foreach ($languages as $lang => $langName) {
    if ($lang === $sourceLang) {
        echo "تخطي اللغة المصدر: $langName\n\n";
        continue;
    }

    $targetFile = $translationsDir . $lang . '.json';
    translateFile($sourceFile, $targetFile, $sourceLang, $lang);
}

echo "=================================\n";
echo "اكتملت عملية الترجمة!\n";
echo "=================================\n\n";
echo "ملاحظة: يمكنك تعديل الترجمات يدوياً في مجلد translations/\n";
echo "الترجمات اليدوية لن يتم استبدالها عند تشغيل السكربت مرة أخرى\n\n";
echo "لإضافة مفتاح ترجمة جديد:\n";
echo "php tools/auto-translate.php add \"key_name\" \"القيمة بالعربية\"\n";

?>
