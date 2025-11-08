<!-- Language Switcher Component -->
<style>
    .lang-switcher {
        position: fixed;
        top: 20px;
        left: 20px;
        z-index: 9999;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        padding: 8px;
        border-radius: 50px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        display: flex;
        gap: 8px;
        transition: all 0.3s ease;
    }

    .lang-switcher:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(0,0,0,0.3);
    }

    .lang-btn {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        border: 2px solid transparent;
        background: white;
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
        position: relative;
        overflow: hidden;
    }

    .lang-btn:hover {
        transform: scale(1.15);
        border-color: #ffd700;
        box-shadow: 0 0 10px rgba(255,215,0,0.5);
    }

    .lang-btn.active {
        border-color: #ffd700;
        background: linear-gradient(135deg, #ffd700 0%, #ffed4e 100%);
        box-shadow: 0 0 15px rgba(255,215,0,0.7);
    }

    .lang-btn::after {
        content: attr(data-lang);
        position: absolute;
        top: -30px;
        left: 50%;
        transform: translateX(-50%);
        background: rgba(0,0,0,0.8);
        color: white;
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 11px;
        white-space: nowrap;
        opacity: 0;
        transition: all 0.3s ease;
        pointer-events: none;
    }

    .lang-btn:hover::after {
        opacity: 1;
        top: -35px;
    }

    @media (max-width: 768px) {
        .lang-switcher {
            top: 10px;
            left: 10px;
            padding: 6px;
            gap: 6px;
        }

        .lang-btn {
            width: 35px;
            height: 35px;
            font-size: 18px;
        }
    }
</style>

<div class="lang-switcher">
    <!-- Arabic -->
    <button class="lang-btn active" onclick="changeLanguage('ar')" data-lang="العربية" title="العربية">
        🇸🇦
    </button>

    <!-- English -->
    <button class="lang-btn" onclick="changeLanguage('en')" data-lang="English" title="English">
        🇬🇧
    </button>

    <!-- German -->
    <button class="lang-btn" onclick="changeLanguage('de')" data-lang="Deutsch" title="Deutsch">
        🇩🇪
    </button>

    <!-- Turkish -->
    <button class="lang-btn" onclick="changeLanguage('tr')" data-lang="Türkçe" title="Türkçe">
        🇹🇷
    </button>
</div>

<script>
// Initialize language from localStorage or default to Arabic
document.addEventListener('DOMContentLoaded', function() {
    const savedLang = localStorage.getItem('selectedLanguage') || 'ar';
    const buttons = document.querySelectorAll('.lang-btn');

    buttons.forEach(btn => {
        btn.classList.remove('active');
    });

    const activeBtn = document.querySelector(`[onclick="changeLanguage('${savedLang}')"]`);
    if (activeBtn) {
        activeBtn.classList.add('active');
    }

    // Apply saved language if not Arabic
    if (savedLang !== 'ar') {
        changeLanguage(savedLang);
    }
});

function changeLanguage(lang) {
    // Save selected language
    localStorage.setItem('selectedLanguage', lang);

    // Update active button
    const buttons = document.querySelectorAll('.lang-btn');
    buttons.forEach(btn => {
        btn.classList.remove('active');
    });
    event.target.classList.add('active');

    // Change page direction based on language
    if (lang === 'ar') {
        document.documentElement.setAttribute('dir', 'rtl');
        document.documentElement.setAttribute('lang', 'ar');
    } else {
        document.documentElement.setAttribute('dir', 'ltr');
        document.documentElement.setAttribute('lang', lang);
    }

    // Trigger translation
    if (typeof translatePage === 'function') {
        translatePage(lang);
    }
}
</script>
