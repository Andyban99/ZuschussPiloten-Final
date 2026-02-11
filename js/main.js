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
        elements.forms.forEach(form => {
            form.addEventListener('submit', function(e) {
                e.preventDefault();

                // Get form data
                const formData = new FormData(this);
                const data = Object.fromEntries(formData);

                // Here you would typically send the data to your backend
                console.log('Form submitted:', data);

                // Show success message (customize as needed)
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
