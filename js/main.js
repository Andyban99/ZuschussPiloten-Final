/**
 * Zuschuss Piloten - Main JavaScript
 * ===================================
 */

(function() {
    'use strict';

    /**
     * DOM Elements
     */
    const elements = {
        nav: document.querySelector('nav'),
        mobileMenuBtn: document.querySelector('[data-mobile-menu-btn]'),
        mobileMenu: document.querySelector('[data-mobile-menu]'),
        forms: document.querySelectorAll('form'),
        animatedElements: document.querySelectorAll('[data-animate]')
    };

    /**
     * Navigation - Scroll Effect
     */
    function initNavScrollEffect() {
        let lastScroll = 0;
        const nav = elements.nav;

        if (!nav) return;

        window.addEventListener('scroll', () => {
            const currentScroll = window.pageYOffset;

            // Add shadow on scroll
            if (currentScroll > 50) {
                nav.classList.add('shadow-lg');
            } else {
                nav.classList.remove('shadow-lg');
            }

            lastScroll = currentScroll;
        }, { passive: true });
    }

    /**
     * Smooth Scroll for Anchor Links
     */
    function initSmoothScroll() {
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                const targetId = this.getAttribute('href');

                if (targetId === '#') return;

                const targetElement = document.querySelector(targetId);

                if (targetElement) {
                    e.preventDefault();

                    const navHeight = elements.nav ? elements.nav.offsetHeight : 0;
                    const targetPosition = targetElement.offsetTop - navHeight - 20;

                    window.scrollTo({
                        top: targetPosition,
                        behavior: 'smooth'
                    });
                }
            });
        });
    }

    /**
     * Form Handling
     */
    function initForms() {
        const kontaktFormular = document.getElementById('kontaktFormular');

        if (kontaktFormular) {
            kontaktFormular.addEventListener('submit', async function(e) {
                e.preventDefault();

                const submitBtn = this.querySelector('button[type="submit"]');
                const originalBtnContent = submitBtn.innerHTML;

                // Loading state
                submitBtn.disabled = true;
                submitBtn.innerHTML = `
                    <svg class="animate-spin h-5 w-5 mr-2" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span>Wird gesendet...</span>
                `;

                // Get form data
                const formData = new FormData(this);
                const data = Object.fromEntries(formData);

                try {
                    const response = await fetch('backend/submit.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify(data)
                    });

                    const result = await response.json();

                    if (result.success) {
                        // Success - show message and reset form
                        showNotification(result.message, 'success');
                        this.reset();

                        // Show success state in button
                        submitBtn.innerHTML = `
                            <iconify-icon icon="solar:check-circle-bold" width="20"></iconify-icon>
                            <span>Erfolgreich gesendet!</span>
                        `;
                        submitBtn.classList.remove('bg-blue-600', 'hover:bg-blue-700');
                        submitBtn.classList.add('bg-emerald-600');

                        // Reset button after 3 seconds
                        setTimeout(() => {
                            submitBtn.innerHTML = originalBtnContent;
                            submitBtn.classList.remove('bg-emerald-600');
                            submitBtn.classList.add('bg-blue-600', 'hover:bg-blue-700');
                            submitBtn.disabled = false;
                        }, 3000);
                    } else {
                        // Error
                        const errorMsg = result.errors ? result.errors.join(', ') : result.message;
                        showNotification(errorMsg, 'error');
                        submitBtn.innerHTML = originalBtnContent;
                        submitBtn.disabled = false;
                    }
                } catch (error) {
                    console.error('Fehler:', error);
                    showNotification('Ein Fehler ist aufgetreten. Bitte versuchen Sie es später erneut.', 'error');
                    submitBtn.innerHTML = originalBtnContent;
                    submitBtn.disabled = false;
                }
            });
        }

        // Handle other forms normally
        elements.forms.forEach(form => {
            if (form.id === 'kontaktFormular') return;

            form.addEventListener('submit', function(e) {
                e.preventDefault();
                const formData = new FormData(this);
                const data = Object.fromEntries(formData);
                console.log('Form submitted:', data);
                showNotification('Vielen Dank! Wir melden uns in Kürze bei Ihnen.', 'success');
            });
        });
    }

    /**
     * Notification System
     */
    function showNotification(message, type = 'info') {
        // Remove existing notifications
        const existingNotification = document.querySelector('.notification');
        if (existingNotification) {
            existingNotification.remove();
        }

        // Create notification element
        const notification = document.createElement('div');
        notification.className = `notification fixed bottom-4 right-4 px-6 py-4 rounded-lg shadow-lg z-50 transform transition-all duration-300 translate-y-full`;

        // Set colors based on type
        const colors = {
            success: 'bg-emerald-500 text-white',
            error: 'bg-red-500 text-white',
            info: 'bg-blue-500 text-white'
        };

        notification.classList.add(...(colors[type] || colors.info).split(' '));
        notification.textContent = message;

        document.body.appendChild(notification);

        // Animate in
        requestAnimationFrame(() => {
            notification.classList.remove('translate-y-full');
        });

        // Auto remove after 5 seconds
        setTimeout(() => {
            notification.classList.add('translate-y-full');
            setTimeout(() => notification.remove(), 300);
        }, 5000);
    }

    /**
     * Intersection Observer for Animations
     */
    function initScrollAnimations() {
        if (!('IntersectionObserver' in window)) return;

        const observerOptions = {
            root: null,
            rootMargin: '0px',
            threshold: 0.1
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('animate-fade-in-up');
                    observer.unobserve(entry.target);
                }
            });
        }, observerOptions);

        elements.animatedElements.forEach(el => observer.observe(el));
    }

    /**
     * Mobile Menu Toggle
     */
    function initMobileMenu() {
        const btn = elements.mobileMenuBtn;
        const menu = elements.mobileMenu;

        if (!btn || !menu) return;

        btn.addEventListener('click', () => {
            menu.classList.toggle('hidden');
            btn.setAttribute('aria-expanded', !menu.classList.contains('hidden'));
        });
    }

    /**
     * Counter Animation
     */
    function animateCounter(element, target, duration = 2000) {
        const start = 0;
        const startTime = performance.now();

        function update(currentTime) {
            const elapsed = currentTime - startTime;
            const progress = Math.min(elapsed / duration, 1);

            // Easing function
            const easeOutQuart = 1 - Math.pow(1 - progress, 4);
            const current = Math.floor(easeOutQuart * target);

            element.textContent = current.toLocaleString('de-DE');

            if (progress < 1) {
                requestAnimationFrame(update);
            }
        }

        requestAnimationFrame(update);
    }

    /**
     * Initialize Counter Animations on Scroll
     */
    function initCounterAnimations() {
        const counters = document.querySelectorAll('[data-counter]');

        if (!counters.length || !('IntersectionObserver' in window)) return;

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const target = parseInt(entry.target.dataset.counter, 10);
                    animateCounter(entry.target, target);
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.5 });

        counters.forEach(counter => observer.observe(counter));
    }

    /**
     * Initialize All Modules
     */
    function init() {
        initNavScrollEffect();
        initSmoothScroll();
        initForms();
        initScrollAnimations();
        initMobileMenu();
        initCounterAnimations();
    }

    // Run when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    // Expose utilities to global scope if needed
    window.ZuschussPiloten = {
        showNotification
    };

})();
