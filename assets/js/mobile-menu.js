/**
 * Mobile Menu & Sidebar Navigation
 * BidOra - نظام القائمة الجانبية للموبايل
 */

document.addEventListener('DOMContentLoaded', function() {
    // إنشاء عناصر القائمة الجانبية
    createMobileMenu();
    initMobileMenu();
});

/**
 * إنشاء القائمة الجانبية
 */
function createMobileMenu() {
    // فحص إذا كانت القائمة موجودة
    if (document.querySelector('.mobile-sidebar')) {
        return;
    }

    // إنشاء زر القائمة
    const menuToggle = document.createElement('button');
    menuToggle.className = 'mobile-menu-toggle';
    menuToggle.id = 'mobileMenuToggle';
    menuToggle.setAttribute('aria-label', 'فتح القائمة');
    menuToggle.innerHTML = `
        <div class="menu-icon">
            <span></span>
            <span></span>
            <span></span>
        </div>
    `;

    // إنشاء القائمة الجانبية
    const sidebar = document.createElement('div');
    sidebar.className = 'mobile-sidebar';
    sidebar.id = 'mobileSidebar';

    // إنشاء محتوى القائمة
    const sidebarContent = createSidebarContent();
    sidebar.innerHTML = sidebarContent;

    // إنشاء الـ overlay
    const overlay = document.createElement('div');
    overlay.className = 'mobile-overlay';
    overlay.id = 'mobileOverlay';

    // إضافة العناصر للصفحة
    document.body.appendChild(menuToggle);
    document.body.appendChild(sidebar);
    document.body.appendChild(overlay);
}

/**
 * إنشاء محتوى القائمة الجانبية
 */
function createSidebarContent() {
    // الحصول على عناصر القائمة من الـ navigation الموجودة
    const desktopNav = document.querySelector('.luxury-nav');
    let menuItems = '';

    if (desktopNav) {
        const navItems = desktopNav.querySelectorAll('.nav-item');
        navItems.forEach(item => {
            const icon = item.querySelector('i') ? item.querySelector('i').className : 'fas fa-circle';
            const text = item.querySelector('span') ? item.querySelector('span').textContent : item.textContent;
            const href = item.getAttribute('href');
            const isActive = item.classList.contains('active') || window.location.pathname.includes(href) ? 'active' : '';

            menuItems += `
                <a href="${href}" class="sidebar-menu-item ${isActive}">
                    <i class="${icon}"></i>
                    <span>${text.trim()}</span>
                </a>
            `;
        });
    } else {
        // قائمة افتراضية إذا لم تكن موجودة
        menuItems = getDefaultMenuItems();
    }

    return `
        <div class="sidebar-header">
            <a href="index.php" class="sidebar-logo">
                <i class="fas fa-car"></i>
                <span>BidOra</span>
            </a>
        </div>

        <div class="sidebar-menu">
            ${menuItems}
        </div>

        <div class="sidebar-footer">
            <p class="sidebar-footer-text">© 2024 BidOra. All rights reserved.</p>
        </div>
    `;
}

/**
 * قائمة افتراضية
 */
function getDefaultMenuItems() {
    const currentPage = window.location.pathname;

    const menuItems = [
        { icon: 'fas fa-home', text: 'الرئيسية', href: 'index.php' },
        { icon: 'fas fa-gavel', text: 'المزادات', href: 'auctions.php' },
        { icon: 'fas fa-store', text: 'المتجر', href: 'store.php' },
        { icon: 'fas fa-th-large', text: 'لوحة التحكم', href: 'dashboard.php' },
        { icon: 'fas fa-wallet', text: 'المحفظة', href: 'wallet.php' },
        { icon: 'fas fa-user', text: 'الملف الشخصي', href: 'profile.php' },
        { icon: 'fas fa-bell', text: 'الإشعارات', href: 'notifications.php' },
        { icon: 'fas fa-comments', text: 'المحادثات', href: 'my-chats.php' },
        { icon: 'fas fa-sign-in-alt', text: 'تسجيل الدخول', href: 'login.php' }
    ];

    return menuItems.map(item => {
        const isActive = currentPage.includes(item.href) ? 'active' : '';
        return `
            <a href="${item.href}" class="sidebar-menu-item ${isActive}">
                <i class="${item.icon}"></i>
                <span>${item.text}</span>
            </a>
        `;
    }).join('');
}

/**
 * تفعيل القائمة الجانبية
 */
function initMobileMenu() {
    const menuToggle = document.getElementById('mobileMenuToggle');
    const sidebar = document.getElementById('mobileSidebar');
    const overlay = document.getElementById('mobileOverlay');

    if (!menuToggle || !sidebar || !overlay) {
        return;
    }

    // فتح/إغلاق القائمة
    menuToggle.addEventListener('click', function() {
        toggleMenu();
    });

    // إغلاق عند الضغط على الـ overlay
    overlay.addEventListener('click', function() {
        closeMenu();
    });

    // إغلاق عند الضغط على ESC
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && sidebar.classList.contains('open')) {
            closeMenu();
        }
    });

    // إغلاق عند الضغط على أي رابط في القائمة
    const sidebarLinks = sidebar.querySelectorAll('.sidebar-menu-item');
    sidebarLinks.forEach(link => {
        link.addEventListener('click', function() {
            // تأخير بسيط قبل الإغلاق لتحسين تجربة المستخدم
            setTimeout(closeMenu, 200);
        });
    });

    // إغلاق القائمة عند تغيير حجم الشاشة لأكبر من 768px
    window.addEventListener('resize', function() {
        if (window.innerWidth > 768 && sidebar.classList.contains('open')) {
            closeMenu();
        }
    });
}

/**
 * تبديل حالة القائمة
 */
function toggleMenu() {
    const menuToggle = document.getElementById('mobileMenuToggle');
    const sidebar = document.getElementById('mobileSidebar');
    const overlay = document.getElementById('mobileOverlay');

    if (sidebar.classList.contains('open')) {
        closeMenu();
    } else {
        openMenu();
    }
}

/**
 * فتح القائمة
 */
function openMenu() {
    const menuToggle = document.getElementById('mobileMenuToggle');
    const sidebar = document.getElementById('mobileSidebar');
    const overlay = document.getElementById('mobileOverlay');

    menuToggle.classList.add('active');
    sidebar.classList.add('open');
    overlay.classList.add('show');

    // منع التمرير في الخلفية
    document.body.style.overflow = 'hidden';

    // تحديث aria-label
    menuToggle.setAttribute('aria-label', 'إغلاق القائمة');
}

/**
 * إغلاق القائمة
 */
function closeMenu() {
    const menuToggle = document.getElementById('mobileMenuToggle');
    const sidebar = document.getElementById('mobileSidebar');
    const overlay = document.getElementById('mobileOverlay');

    menuToggle.classList.remove('active');
    sidebar.classList.remove('open');
    overlay.classList.remove('show');

    // السماح بالتمرير في الخلفية
    document.body.style.overflow = '';

    // تحديث aria-label
    menuToggle.setAttribute('aria-label', 'فتح القائمة');
}

/**
 * تحديث النص بناءً على اللغة
 */
function updateMenuLanguage(lang) {
    // يمكن إضافة ترجمات للقائمة هنا
    const translations = {
        'ar': {
            'Home': 'الرئيسية',
            'Auctions': 'المزادات',
            'Store': 'المتجر',
            'Dashboard': 'لوحة التحكم',
            'Wallet': 'المحفظة',
            'Profile': 'الملف الشخصي',
            'Notifications': 'الإشعارات',
            'Chats': 'المحادثات',
            'Login': 'تسجيل الدخول'
        },
        'en': {
            'الرئيسية': 'Home',
            'المزادات': 'Auctions',
            'المتجر': 'Store',
            'لوحة التحكم': 'Dashboard',
            'المحفظة': 'Wallet',
            'الملف الشخصي': 'Profile',
            'الإشعارات': 'Notifications',
            'المحادثات': 'Chats',
            'تسجيل الدخول': 'Login'
        }
        // يمكن إضافة المزيد من اللغات
    };

    // تطبيق الترجمات
    if (translations[lang]) {
        const menuItems = document.querySelectorAll('.sidebar-menu-item span');
        menuItems.forEach(item => {
            const currentText = item.textContent.trim();
            if (translations[lang][currentText]) {
                item.textContent = translations[lang][currentText];
            }
        });
    }
}

// تصدير الدوال للاستخدام الخارجي
window.MobileMenu = {
    toggle: toggleMenu,
    open: openMenu,
    close: closeMenu,
    updateLanguage: updateMenuLanguage
};
