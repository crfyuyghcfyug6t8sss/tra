# 🌍 نظام الترجمة والـ Responsive - BidOra

## 📋 المحتويات

1. [نظرة عامة](#نظرة-عامة)
2. [اللغات المدعومة](#اللغات-المدعومة)
3. [بنية المشروع](#بنية-المشروع)
4. [كيفية الاستخدام](#كيفية-الاستخدام)
5. [الترجمة التلقائية](#الترجمة-التلقائية)
6. [القائمة الجانبية للموبايل](#القائمة-الجانبية-للموبايل)
7. [تخصيص الترجمات](#تخصيص-الترجمات)

---

## 🌟 نظرة عامة

تم تطبيق نظام ترجمة شامل ومتكامل على جميع صفحات BidOra (26 صفحة) مع دعم كامل للـ Responsive Design وقائمة جانبية للموبايل.

### ✨ المميزات

- ✅ **4 لغات مدعومة**: عربي، إنجليزي، ألماني، تركي
- ✅ **ترجمة تلقائية**: باستخدام Google Translate API المجاني
- ✅ **تعديل يدوي**: يمكن تعديل أي ترجمة في ملفات JSON
- ✅ **Responsive Design**: تصميم متجاوب لكل الأجهزة
- ✅ **قائمة جانبية**: Sidebar menu للموبايل والتابلت
- ✅ **تغيير اللغة فوري**: بدون إعادة تحميل الصفحة
- ✅ **حفظ اللغة**: في Session و Cookies

---

## 🌐 اللغات المدعومة

| اللغة | الكود | الاتجاه | الأيقونة |
|------|------|---------|---------|
| العربية | `ar` | RTL | 🇸🇦 |
| English | `en` | LTR | 🇺🇸 |
| Deutsch | `de` | LTR | 🇩🇪 |
| Türkçe | `tr` | LTR | 🇹🇷 |

---

## 📁 بنية المشروع

```
tra/
├── translations/           # ملفات الترجمة
│   ├── ar.json            # العربية
│   ├── en.json            # الإنجليزية
│   ├── de.json            # الألمانية
│   └── tr.json            # التركية
├── includes/
│   ├── translator.php     # نظام الترجمة الرئيسي
│   ├── lang-switcher.php  # زر تبديل اللغة
│   └── functions.php      # الدوال المساعدة
├── assets/
│   ├── css/
│   │   └── responsive.css # CSS للموبايل والـ responsive
│   └── js/
│       └── mobile-menu.js # JavaScript للقائمة الجانبية
├── tools/
│   ├── auto-translate.php          # الترجمة التلقائية
│   └── apply-translation-to-all.php # تطبيق على كل الصفحات
└── config/
    └── database.php       # إعدادات قاعدة البيانات
```

---

## 🚀 كيفية الاستخدام

### 1️⃣ استخدام الترجمة في PHP

```php
<?php
// تحميل نظام الترجمة (تم تطبيقه تلقائياً على كل الصفحات)
require_once 'includes/translator.php';

// طريقة 1: استخدام الدالة __()
echo __('home');           // الرئيسية
echo __('auctions');       // المزادات
echo __('login');          // تسجيل الدخول

// طريقة 2: مع قيمة افتراضية
echo __('custom_key', 'Default Value');

// طريقة 3: الحصول على اللغة الحالية
echo currentLang();        // ar, en, de, or tr

// طريقة 4: الحصول على اتجاه النص
echo textDirection();      // rtl أو ltr
?>
```

### 2️⃣ استخدام في HTML

```html
<!-- العنوان الديناميكي -->
<title><?php echo __('site_name'); ?> - <?php echo __('luxury_car_auction'); ?></title>

<!-- النصوص -->
<h1><?php echo __('welcome'); ?></h1>
<a href="#"><?php echo __('home'); ?></a>

<!-- HTML Tag بالاتجاه الديناميكي -->
<html lang="<?php echo currentLang(); ?>" dir="<?php echo textDirection(); ?>">
```

### 3️⃣ إضافة زر تبديل اللغة

```php
<!-- في الهيدر أو أي مكان -->
<?php include 'includes/lang-switcher.php'; ?>
```

---

## 🤖 الترجمة التلقائية

### تشغيل الترجمة التلقائية

```bash
# ترجمة كل الملفات
php tools/auto-translate.php
```

### إضافة مفتاح ترجمة جديد

```bash
# إضافة مفتاح جديد مع ترجمة تلقائية
php tools/auto-translate.php add "new_key" "القيمة بالعربية"
```

### مثال:

```bash
php tools/auto-translate.php add "contact_us" "اتصل بنا"
```

سيتم ترجمته تلقائياً إلى:
- **en**: "Contact Us"
- **de**: "Kontaktiere uns"
- **tr**: "Bize Ulaşın"

---

## 📱 القائمة الجانبية للموبايل

### ✨ المميزات

- ✅ تظهر تلقائياً على الشاشات الأصغر من 768px
- ✅ انيميشن سلس للفتح والإغلاق
- ✅ Overlay خلفي شفاف
- ✅ إغلاق بالضغط خارج القائمة أو ESC
- ✅ دعم RTL و LTR

### 🎨 التخصيص

يمكنك تخصيص القائمة الجانبية من خلال تعديل:

**CSS**: `assets/css/responsive.css`
```css
.mobile-sidebar {
    width: 300px;  /* عرض القائمة */
    /* المزيد من التخصيصات... */
}
```

**JavaScript**: `assets/js/mobile-menu.js`
```javascript
// يمكنك التحكم في القائمة برمجياً
MobileMenu.open();    // فتح القائمة
MobileMenu.close();   // إغلاق القائمة
MobileMenu.toggle();  // تبديل الحالة
```

---

## ✏️ تخصيص الترجمات

### تعديل ترجمة موجودة

1. افتح الملف المناسب: `translations/[ar|en|de|tr].json`
2. ابحث عن المفتاح المطلوب
3. عدل القيمة

**مثال** (`translations/ar.json`):
```json
{
  "home": "الرئيسية",
  "auctions": "المزادات",
  "welcome": "أهلاً وسهلاً"    ← يمكنك تعديل هذا
}
```

### إضافة ترجمة جديدة يدوياً

أضف المفتاح والقيمة في كل ملف لغة:

**`translations/ar.json`**:
```json
{
  "my_new_key": "النص بالعربية"
}
```

**`translations/en.json`**:
```json
{
  "my_new_key": "Text in English"
}
```

**استخدامها**:
```php
<?php echo __('my_new_key'); ?>
```

---

## 🔧 إعدادات متقدمة

### تغيير اللغة الافتراضية

في `includes/translator.php`:
```php
private $defaultLang = 'ar'; // غير هذا إلى en, de, أو tr
```

### إضافة لغة جديدة

1. أنشئ ملف JSON جديد: `translations/fr.json` (مثلاً للفرنسية)
2. في `includes/translator.php`، أضف اللغة:
```php
private $supportedLangs = ['ar', 'en', 'de', 'tr', 'fr'];

public function getSupportedLanguages() {
    return [
        'ar' => ['name' => 'العربية', 'flag' => '🇸🇦', 'dir' => 'rtl'],
        'en' => ['name' => 'English', 'flag' => '🇺🇸', 'dir' => 'ltr'],
        'de' => ['name' => 'Deutsch', 'flag' => '🇩🇪', 'dir' => 'ltr'],
        'tr' => ['name' => 'Türkçe', 'flag' => '🇹🇷', 'dir' => 'ltr'],
        'fr' => ['name' => 'Français', 'flag' => '🇫🇷', 'dir' => 'ltr'], // جديد
    ];
}
```

---

## 📊 إحصائيات التطبيق

- ✅ **26 صفحة PHP** تم تطبيق النظام عليها
- ✅ **90+ مفتاح ترجمة** جاهز للاستخدام
- ✅ **4 لغات** مدعومة
- ✅ **100% Responsive** على كل الأجهزة

---

## 🐛 استكشاف الأخطاء

### الترجمة لا تعمل

1. تأكد من وجود `require_once 'includes/translator.php';` في بداية الملف
2. تحقق من وجود ملفات JSON في مجلد `translations/`
3. تأكد من صحة المفاتيح في ملفات JSON

### القائمة الجانبية لا تظهر

1. تأكد من تضمين `assets/css/responsive.css`
2. تأكد من تضمين `assets/js/mobile-menu.js`
3. جرب التصغير إلى أقل من 768px

### اللغة لا تتغير

1. تحقق من أن session مفعّل
2. احذف الـ cookies وجرب مرة أخرى
3. تأكد من صلاحيات الكتابة على السيرفر

---

## 💡 نصائح

- 📝 استخدم مفاتيح واضحة ومعبرة (مثل `login` بدلاً من `btn1`)
- 🔄 شغل الترجمة التلقائية بشكل دوري لإضافة مفاتيح جديدة
- ✏️ راجع الترجمات يدوياً للتأكد من دقتها
- 📱 اختبر على أجهزة مختلفة (موبايل، تابلت، ديسكتوب)

---

## 📞 الدعم

للمساعدة أو الاستفسارات، يمكنك:
- مراجعة الكود في `includes/translator.php`
- التحقق من ملفات JSON في `translations/`
- فحص السكربتات في مجلد `tools/`

---

**🎉 تم تطبيق نظام الترجمة والـ Responsive بنجاح على جميع صفحات BidOra!**
