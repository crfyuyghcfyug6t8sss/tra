<?php
/**
 * زر تبديل اللغة - Language Switcher Widget
 */

// تحميل المترجم
if (!isset($translator)) {
    require_once __DIR__ . '/translator.php';
}

$currentLang = $translator->getCurrentLang();
$languages = $translator->getSupportedLanguages();
$dir = $translator->getDirection();
?>

<!-- Language Switcher CSS -->
<style>
.language-switcher {
    position: relative;
    display: inline-block;
    z-index: 1000;
}

.lang-toggle {
    background: rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.2);
    border-radius: 15px;
    padding: 12px 20px;
    color: #fff;
    cursor: pointer;
    font-size: 14px;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 10px;
    transition: all 0.3s ease;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
    font-family: 'Cairo', 'Tajawal', sans-serif;
}

.lang-toggle:hover {
    background: rgba(255, 255, 255, 0.15);
    border-color: rgba(255, 255, 255, 0.3);
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
}

.lang-toggle i.fa-globe {
    font-size: 18px;
    color: #667eea;
}

.lang-toggle i.fa-chevron-down {
    font-size: 12px;
    transition: transform 0.3s ease;
}

.lang-toggle.active i.fa-chevron-down {
    transform: rotate(180deg);
}

.lang-dropdown {
    position: absolute;
    top: calc(100% + 10px);
    <?php echo $dir === 'rtl' ? 'right: 0;' : 'left: 0;'; ?>
    background: rgba(15, 12, 41, 0.95);
    backdrop-filter: blur(20px);
    border: 1px solid rgba(255, 255, 255, 0.2);
    border-radius: 15px;
    padding: 10px;
    min-width: 200px;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.5);
    opacity: 0;
    visibility: hidden;
    transform: translateY(-10px);
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.lang-dropdown.show {
    opacity: 1;
    visibility: visible;
    transform: translateY(0);
}

.lang-option {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 15px;
    color: #fff;
    text-decoration: none;
    border-radius: 10px;
    transition: all 0.3s ease;
    font-size: 14px;
    font-weight: 500;
}

.lang-option:hover {
    background: rgba(102, 126, 234, 0.2);
    transform: translateX(<?php echo $dir === 'rtl' ? '-5px' : '5px'; ?>);
}

.lang-option.active {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
}

.lang-flag {
    font-size: 24px;
    line-height: 1;
}

.lang-name {
    flex: 1;
}

.lang-option i.fa-check {
    font-size: 14px;
    color: #13f1fc;
}

/* Mobile Responsive */
@media (max-width: 768px) {
    .lang-toggle {
        padding: 10px 15px;
        font-size: 13px;
    }

    .lang-dropdown {
        min-width: 180px;
    }

    .lang-option {
        padding: 10px 12px;
        font-size: 13px;
    }
}
</style>

<!-- Language Switcher HTML -->
<div class="language-switcher">
    <button class="lang-toggle" id="langToggle" type="button">
        <i class="fas fa-globe"></i>
        <span><?php echo $languages[$currentLang]['name']; ?></span>
        <i class="fas fa-chevron-down"></i>
    </button>
    <div class="lang-dropdown" id="langDropdown">
        <?php foreach ($languages as $code => $lang): ?>
            <a href="<?php echo $translator->getLangUrl($code); ?>"
               class="lang-option <?php echo $code === $currentLang ? 'active' : ''; ?>"
               data-lang="<?php echo $code; ?>">
                <span class="lang-flag"><?php echo $lang['flag']; ?></span>
                <span class="lang-name"><?php echo $lang['name']; ?></span>
                <?php if ($code === $currentLang): ?>
                    <i class="fas fa-check"></i>
                <?php endif; ?>
            </a>
        <?php endforeach; ?>
    </div>
</div>

<!-- Language Switcher JavaScript -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const langToggle = document.getElementById('langToggle');
    const langDropdown = document.getElementById('langDropdown');

    if (langToggle && langDropdown) {
        langToggle.addEventListener('click', function(e) {
            e.stopPropagation();
            langDropdown.classList.toggle('show');
            langToggle.classList.toggle('active');
        });

        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.language-switcher')) {
                langDropdown.classList.remove('show');
                langToggle.classList.remove('active');
            }
        });

        // Close dropdown on ESC key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                langDropdown.classList.remove('show');
                langToggle.classList.remove('active');
            }
        });
    }
});
</script>
