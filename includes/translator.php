<?php
/**
 * نظام الترجمة - BidOra Translation System
 * يدعم: العربية، الإنجليزية، الألمانية، التركية
 */

// بدء الجلسة إذا لم تكن مبدوءة
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

class Translator {
    private $currentLang;
    private $translations;
    private $defaultLang = 'ar'; // العربية هي اللغة الافتراضية
    private $supportedLangs = ['ar', 'en', 'de', 'tr'];
    private $rtlLangs = ['ar']; // اللغات التي تكتب من اليمين لليسار

    public function __construct() {
        // تحديد اللغة الحالية من الجلسة أو الكوكيز أو المتصفح
        $this->currentLang = $this->detectLanguage();
        $this->loadTranslations();
    }

    /**
     * كشف اللغة المفضلة للمستخدم
     */
    private function detectLanguage() {
        // 1. فحص الـ GET parameter
        if (isset($_GET['lang']) && in_array($_GET['lang'], $this->supportedLangs)) {
            $_SESSION['lang'] = $_GET['lang'];
            setcookie('lang', $_GET['lang'], time() + (86400 * 365), '/'); // سنة
            return $_GET['lang'];
        }

        // 2. فحص الجلسة
        if (isset($_SESSION['lang']) && in_array($_SESSION['lang'], $this->supportedLangs)) {
            return $_SESSION['lang'];
        }

        // 3. فحص الكوكيز
        if (isset($_COOKIE['lang']) && in_array($_COOKIE['lang'], $this->supportedLangs)) {
            $_SESSION['lang'] = $_COOKIE['lang'];
            return $_COOKIE['lang'];
        }

        // 4. فحص لغة المتصفح
        if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            $browserLang = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2);
            if (in_array($browserLang, $this->supportedLangs)) {
                $_SESSION['lang'] = $browserLang;
                return $browserLang;
            }
        }

        // 5. اللغة الافتراضية
        $_SESSION['lang'] = $this->defaultLang;
        return $this->defaultLang;
    }

    /**
     * تحميل ملف الترجمة
     */
    private function loadTranslations() {
        $file = __DIR__ . '/../translations/' . $this->currentLang . '.json';

        if (file_exists($file)) {
            $json = file_get_contents($file);
            $this->translations = json_decode($json, true);
        } else {
            // في حالة عدم وجود الملف، استخدم اللغة الافتراضية
            $defaultFile = __DIR__ . '/../translations/' . $this->defaultLang . '.json';
            if (file_exists($defaultFile)) {
                $json = file_get_contents($defaultFile);
                $this->translations = json_decode($json, true);
            } else {
                $this->translations = [];
            }
        }
    }

    /**
     * الحصول على الترجمة
     * @param string $key مفتاح الترجمة
     * @param string $default القيمة الافتراضية إذا لم يتم العثور على الترجمة
     * @return string النص المترجم
     */
    public function get($key, $default = null) {
        if (isset($this->translations[$key])) {
            return $this->translations[$key];
        }

        // إذا لم يتم العثور على الترجمة، استخدم القيمة الافتراضية أو المفتاح نفسه
        return $default !== null ? $default : $key;
    }

    /**
     * دالة مختصرة للترجمة
     */
    public function t($key, $default = null) {
        return $this->get($key, $default);
    }

    /**
     * الحصول على اللغة الحالية
     */
    public function getCurrentLang() {
        return $this->currentLang;
    }

    /**
     * الحصول على اتجاه النص (RTL أو LTR)
     */
    public function getDirection() {
        return in_array($this->currentLang, $this->rtlLangs) ? 'rtl' : 'ltr';
    }

    /**
     * الحصول على اللغات المدعومة
     */
    public function getSupportedLanguages() {
        return [
            'ar' => ['name' => 'العربية', 'flag' => '🇸🇦', 'dir' => 'rtl'],
            'en' => ['name' => 'English', 'flag' => '🇺🇸', 'dir' => 'ltr'],
            'de' => ['name' => 'Deutsch', 'flag' => '🇩🇪', 'dir' => 'ltr'],
            'tr' => ['name' => 'Türkçe', 'flag' => '🇹🇷', 'dir' => 'ltr']
        ];
    }

    /**
     * تغيير اللغة
     */
    public function setLanguage($lang) {
        if (in_array($lang, $this->supportedLangs)) {
            $this->currentLang = $lang;
            $_SESSION['lang'] = $lang;
            setcookie('lang', $lang, time() + (86400 * 365), '/');
            $this->loadTranslations();
            return true;
        }
        return false;
    }

    /**
     * الحصول على رابط تغيير اللغة
     */
    public function getLangUrl($lang) {
        $currentUrl = $_SERVER['REQUEST_URI'];
        $separator = strpos($currentUrl, '?') !== false ? '&' : '?';

        // إزالة lang parameter القديم إن وجد
        $currentUrl = preg_replace('/[?&]lang=[^&]*/', '', $currentUrl);

        return $currentUrl . $separator . 'lang=' . $lang;
    }

    /**
     * إنشاء HTML لزر تبديل اللغة
     */
    public function getLanguageSwitcher() {
        $languages = $this->getSupportedLanguages();
        $current = $this->currentLang;

        $html = '<div class="language-switcher">';
        $html .= '<button class="lang-toggle" id="langToggle">';
        $html .= '<i class="fas fa-globe"></i> ';
        $html .= '<span>' . $languages[$current]['name'] . '</span>';
        $html .= '<i class="fas fa-chevron-down"></i>';
        $html .= '</button>';
        $html .= '<div class="lang-dropdown" id="langDropdown">';

        foreach ($languages as $code => $lang) {
            $active = $code === $current ? 'active' : '';
            $html .= '<a href="' . $this->getLangUrl($code) . '" class="lang-option ' . $active . '">';
            $html .= '<span class="lang-flag">' . $lang['flag'] . '</span>';
            $html .= '<span class="lang-name">' . $lang['name'] . '</span>';
            if ($code === $current) {
                $html .= '<i class="fas fa-check"></i>';
            }
            $html .= '</a>';
        }

        $html .= '</div>';
        $html .= '</div>';

        return $html;
    }
}

// إنشاء instance عام من المترجم
$translator = new Translator();

// دالة مساعدة عامة للترجمة
function __($key, $default = null) {
    global $translator;
    return $translator->get($key, $default);
}

// دالة مساعدة للحصول على اللغة الحالية
function currentLang() {
    global $translator;
    return $translator->getCurrentLang();
}

// دالة مساعدة للحصول على اتجاه النص
function textDirection() {
    global $translator;
    return $translator->getDirection();
}

?>
