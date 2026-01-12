/**
 * Theme System - Dark Mode Toggle
 * Production Ready with localStorage persistence
 */

class ThemeManager {
    constructor() {
        this.theme = this.getStoredTheme() || this.getPreferredTheme();
        this.init();
    }

    init() {
        // Set initial theme
        this.setTheme(this.theme);
        
        // Create theme toggle button if not exists
        this.createToggleButton();
        
        // Listen for system theme changes
        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (e) => {
            if (!this.getStoredTheme()) {
                this.setTheme(e.matches ? 'dark' : 'light');
            }
        });
    }

    getStoredTheme() {
        return localStorage.getItem('theme');
    }

    getPreferredTheme() {
        return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
    }

    setTheme(theme) {
        this.theme = theme;
        document.documentElement.setAttribute('data-theme', theme);
        localStorage.setItem('theme', theme);
        this.updateToggleButton();
        
        // Dispatch event for other components
        window.dispatchEvent(new CustomEvent('themechange', { detail: { theme } }));
    }

    toggleTheme() {
        const newTheme = this.theme === 'light' ? 'dark' : 'light';
        this.setTheme(newTheme);
    }

    createToggleButton() {
        // Check if button already exists
        if (document.querySelector('.theme-toggle')) {
            this.updateToggleButton();
            return;
        }

        const toggle = document.createElement('div');
        toggle.className = 'theme-toggle';
        toggle.setAttribute('role', 'button');
        toggle.setAttribute('aria-label', 'Toggle dark mode');
        toggle.setAttribute('tabindex', '0');
        toggle.innerHTML = `
            <div class="theme-toggle-icon" data-theme="light">
                <i class="fas fa-sun"></i>
            </div>
            <div class="theme-toggle-icon" data-theme="dark">
                <i class="fas fa-moon"></i>
            </div>
        `;

        // Add to header
        const header = document.querySelector('.navbar .navbar-nav') || document.querySelector('.navbar');
        if (header) {
            const wrapper = document.createElement('div');
            wrapper.className = 'nav-item me-3';
            wrapper.appendChild(toggle);
            header.prepend(wrapper);
        }

        // Event listeners
        toggle.addEventListener('click', () => this.toggleTheme());
        toggle.addEventListener('keypress', (e) => {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                this.toggleTheme();
            }
        });

        this.updateToggleButton();
    }

    updateToggleButton() {
        const toggle = document.querySelector('.theme-toggle');
        if (!toggle) return;

        const icons = toggle.querySelectorAll('.theme-toggle-icon');
        icons.forEach(icon => {
            const iconTheme = icon.getAttribute('data-theme');
            if (iconTheme === this.theme) {
                icon.classList.add('active');
            } else {
                icon.classList.remove('active');
            }
        });
    }
}

// Initialize theme manager when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        window.themeManager = new ThemeManager();
    });
} else {
    window.themeManager = new ThemeManager();
}

// Export for module systems
if (typeof module !== 'undefined' && module.exports) {
    module.exports = ThemeManager;
}
