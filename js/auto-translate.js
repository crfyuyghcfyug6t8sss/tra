/**
 * Auto Translation Script for BidOra
 * Supports: Arabic, English, German, Turkish
 * Using Google Translate Element API
 */

// Language codes mapping
const languageCodes = {
    'ar': 'ar',
    'en': 'en',
    'de': 'de',
    'tr': 'tr'
};

// Initialize Google Translate
function googleTranslateElementInit() {
    new google.translate.TranslateElement({
        pageLanguage: 'ar',
        includedLanguages: 'ar,en,de,tr',
        layout: google.translate.TranslateElement.InlineLayout.SIMPLE,
        autoDisplay: false
    }, 'google_translate_element');
}

// Load Google Translate script
function loadGoogleTranslate() {
    if (!document.getElementById('google-translate-script')) {
        const script = document.createElement('script');
        script.id = 'google-translate-script';
        script.src = '//translate.google.com/translate_a/element.js?cb=googleTranslateElementInit';
        script.async = true;
        document.head.appendChild(script);
    }
}

// Create hidden div for Google Translate Element
function createTranslateElement() {
    if (!document.getElementById('google_translate_element')) {
        const div = document.createElement('div');
        div.id = 'google_translate_element';
        div.style.display = 'none';
        document.body.appendChild(div);
    }
}

// Translate page to specified language
function translatePage(targetLang) {
    // If translating back to Arabic (original language)
    if (targetLang === 'ar') {
        // Find and click the "Show original" button if it exists
        const frame = document.querySelector('.goog-te-banner-frame');
        if (frame) {
            const innerDoc = frame.contentDocument || frame.contentWindow.document;
            const restoreBtn = innerDoc.querySelector('.goog-te-button button');
            if (restoreBtn) {
                restoreBtn.click();
                return;
            }
        }

        // If no restore button, reload the page
        const currentLang = getCookie('googtrans');
        if (currentLang && currentLang !== '/ar/ar') {
            setCookie('googtrans', '/ar/ar', 1);
            setCookie('googtrans', '/ar/ar', 1, 'translate.googleapis.com');
            window.location.reload();
        }
        return;
    }

    // Translate to target language
    const langPair = `/ar/${targetLang}`;

    // Set cookie for Google Translate
    setCookie('googtrans', langPair, 1);
    setCookie('googtrans', langPair, 1, 'translate.googleapis.com');

    // Trigger translation
    const selectElement = document.querySelector('.goog-te-combo');
    if (selectElement) {
        selectElement.value = targetLang;
        selectElement.dispatchEvent(new Event('change'));
    } else {
        // If widget not ready, reload page with cookie set
        window.location.reload();
    }
}

// Cookie helper functions
function setCookie(name, value, days, domain) {
    let expires = '';
    if (days) {
        const date = new Date();
        date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
        expires = '; expires=' + date.toUTCString();
    }

    const domainStr = domain ? `; domain=${domain}` : '';
    document.cookie = name + '=' + value + expires + domainStr + '; path=/';
}

function getCookie(name) {
    const nameEQ = name + '=';
    const ca = document.cookie.split(';');
    for (let i = 0; i < ca.length; i++) {
        let c = ca[i];
        while (c.charAt(0) === ' ') c = c.substring(1, c.length);
        if (c.indexOf(nameEQ) === 0) return c.substring(nameEQ.length, c.length);
    }
    return null;
}

// Hide Google Translate banner and branding
function hideGoogleTranslateUI() {
    // Add CSS to hide Google Translate UI elements
    const style = document.createElement('style');
    style.innerHTML = `
        .goog-te-banner-frame {
            display: none !important;
        }
        .goog-te-gadget {
            display: none !important;
        }
        body {
            top: 0 !important;
        }
        .skiptranslate {
            display: none !important;
        }
        #google_translate_element {
            display: none !important;
        }
        .goog-logo-link {
            display: none !important;
        }
        .goog-te-balloon-frame {
            display: none !important;
        }
    `;
    document.head.appendChild(style);

    // Check if banner exists and hide it
    const checkBanner = setInterval(function() {
        const banner = document.querySelector('.goog-te-banner-frame');
        if (banner) {
            banner.style.display = 'none';
            document.body.style.top = '0';
        }

        const gadget = document.querySelector('.goog-te-gadget');
        if (gadget) {
            gadget.style.display = 'none';
        }
    }, 100);

    // Stop checking after 5 seconds
    setTimeout(function() {
        clearInterval(checkBanner);
    }, 5000);
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    // Create translate element container
    createTranslateElement();

    // Load Google Translate
    loadGoogleTranslate();

    // Hide Google UI
    hideGoogleTranslateUI();

    // Check for saved language and apply it
    const savedLang = localStorage.getItem('selectedLanguage');
    if (savedLang && savedLang !== 'ar') {
        // Small delay to ensure Google Translate is loaded
        setTimeout(function() {
            translatePage(savedLang);
        }, 1000);
    }
});

// Apply saved language on navigation
window.addEventListener('load', function() {
    const savedLang = localStorage.getItem('selectedLanguage') || 'ar';

    // Update page direction
    if (savedLang === 'ar') {
        document.documentElement.setAttribute('dir', 'rtl');
        document.documentElement.setAttribute('lang', 'ar');
    } else {
        document.documentElement.setAttribute('dir', 'ltr');
        document.documentElement.setAttribute('lang', savedLang);
    }
});
