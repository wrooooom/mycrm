/**
 * Mobile Navigation System
 * Responsive hamburger menu with touch support
 */

class MobileNavigation {
    constructor() {
        this.isOpen = false;
        this.breakpoint = 768;
        this.init();
    }

    init() {
        // Create mobile navigation elements
        this.createMobileElements();
        
        // Setup event listeners
        this.setupEventListeners();
        
        // Handle resize
        this.handleResize();
        window.addEventListener('resize', () => this.handleResize());
    }

    createMobileElements() {
        // Create mobile toggle button
        const toggle = document.createElement('button');
        toggle.className = 'mobile-menu-toggle';
        toggle.setAttribute('aria-label', 'Toggle menu');
        toggle.setAttribute('aria-expanded', 'false');
        toggle.innerHTML = `
            <div class="hamburger">
                <span></span>
                <span></span>
                <span></span>
            </div>
        `;
        document.body.appendChild(toggle);
        this.toggleBtn = toggle;

        // Create overlay
        const overlay = document.createElement('div');
        overlay.className = 'mobile-overlay';
        overlay.setAttribute('aria-hidden', 'true');
        document.body.appendChild(overlay);
        this.overlay = overlay;

        // Convert sidebar to mobile version
        this.createMobileSidebar();
    }

    createMobileSidebar() {
        const sidebar = document.querySelector('.sidebar');
        if (!sidebar) return;

        // Clone sidebar for mobile
        const mobileSidebar = sidebar.cloneNode(true);
        mobileSidebar.classList.add('sidebar-mobile');
        mobileSidebar.classList.remove('sidebar');
        
        // Add close button to mobile sidebar
        const closeBtn = document.createElement('button');
        closeBtn.className = 'btn btn-sm btn-outline-primary m-3';
        closeBtn.innerHTML = '<i class="fas fa-times me-2"></i>Закрыть';
        closeBtn.addEventListener('click', () => this.closeMenu());
        mobileSidebar.prepend(closeBtn);

        document.body.appendChild(mobileSidebar);
        this.mobileSidebar = mobileSidebar;

        // Add click handlers to mobile nav links
        const links = mobileSidebar.querySelectorAll('a');
        links.forEach(link => {
            link.addEventListener('click', () => {
                // Close menu after navigation (with delay for visual feedback)
                setTimeout(() => this.closeMenu(), 300);
            });
        });
    }

    setupEventListeners() {
        // Toggle button
        this.toggleBtn.addEventListener('click', () => this.toggleMenu());

        // Overlay click closes menu
        this.overlay.addEventListener('click', () => this.closeMenu());

        // Escape key closes menu
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && this.isOpen) {
                this.closeMenu();
            }
        });

        // Prevent body scroll when menu is open
        document.addEventListener('touchmove', (e) => {
            if (this.isOpen && !this.mobileSidebar.contains(e.target)) {
                e.preventDefault();
            }
        }, { passive: false });
    }

    toggleMenu() {
        if (this.isOpen) {
            this.closeMenu();
        } else {
            this.openMenu();
        }
    }

    openMenu() {
        this.isOpen = true;
        this.toggleBtn.classList.add('active');
        this.toggleBtn.setAttribute('aria-expanded', 'true');
        this.mobileSidebar.classList.add('active');
        this.overlay.classList.add('active');
        this.overlay.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';

        // Focus trap
        this.mobileSidebar.querySelector('a, button')?.focus();
    }

    closeMenu() {
        this.isOpen = false;
        this.toggleBtn.classList.remove('active');
        this.toggleBtn.setAttribute('aria-expanded', 'false');
        this.mobileSidebar.classList.remove('active');
        this.overlay.classList.remove('active');
        this.overlay.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
    }

    handleResize() {
        const width = window.innerWidth;
        
        // Auto-close menu when resizing to desktop
        if (width >= this.breakpoint && this.isOpen) {
            this.closeMenu();
        }

        // Show/hide mobile elements
        const isMobile = width < this.breakpoint;
        this.toggleBtn.style.display = isMobile ? 'flex' : 'none';
        
        if (!isMobile) {
            this.overlay.classList.remove('active');
            this.mobileSidebar.classList.remove('active');
        }
    }
}

// Initialize mobile navigation when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        window.mobileNav = new MobileNavigation();
    });
} else {
    window.mobileNav = new MobileNavigation();
}

// Export for module systems
if (typeof module !== 'undefined' && module.exports) {
    module.exports = MobileNavigation;
}
