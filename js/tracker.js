/**
 * Zuschuss Piloten - DSGVO-konformer Tracker
 * Leichtgewichtig (~3KB), cookielos, keine IP-Speicherung
 *
 * Automatisches Tracking:
 * - Seitenaufrufe
 * - CTA-Button Klicks (ohne Formular-Submits)
 * - Formular-Absendungen (separat)
 * - Kunden-Login Klicks (separat)
 * - Telefon/E-Mail Links
 * - Scroll-Tiefe (25%, 50%, 75%, 100%)
 * - Verweildauer auf der Seite
 *
 * Manuelles Tracking via: window.ZPTracker.track(eventName, category, value)
 */

(function() {
    'use strict';

    // Konfiguration
    var CONFIG = {
        endpoint: '/backend/api/track.php',
        scrollThresholds: [25, 50, 75, 100],
        debounceMs: 150
    };

    // Zustand
    var state = {
        scrollTracked: {},
        pageStartTime: Date.now(),
        timeOnPageSent: false
    };

    /**
     * Daten an Server senden (non-blocking via Beacon API)
     */
    function sendData(payload) {
        var url = CONFIG.endpoint;

        // Beacon API für non-blocking (bevorzugt)
        if (navigator.sendBeacon) {
            var blob = new Blob([JSON.stringify(payload)], { type: 'application/json' });
            navigator.sendBeacon(url, blob);
        } else {
            // Fallback: Fetch mit keepalive
            fetch(url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload),
                keepalive: true
            }).catch(function() {
                // Stille Fehlerbehandlung
            });
        }
    }

    /**
     * Seiten-Pfad normalisieren (index.html → /)
     */
    function normalizePath(path) {
        // /index.html → /
        if (path === '/index.html') return '/';
        // /seite/index.html → /seite/
        return path.replace(/\/index\.html$/, '/');
    }

    /**
     * Basis-Informationen für alle Requests
     */
    function getBaseData() {
        return {
            page: normalizePath(window.location.pathname),
            referrer: document.referrer || '',
            screen_width: window.screen.width,
            screen_height: window.screen.height,
            language: navigator.language || navigator.userLanguage || 'de',
            timestamp: Date.now()
        };
    }

    /**
     * Seitenaufruf tracken
     */
    function trackPageview() {
        var data = getBaseData();
        data.type = 'pageview';
        sendData(data);
    }

    /**
     * Event tracken
     */
    function trackEvent(eventName, category, value, elementInfo, eventType) {
        var data = getBaseData();
        data.type = 'event';
        data.event_type = eventType || 'click';
        data.event_name = eventName;
        data.category = category || '';
        data.value = value || '';

        if (elementInfo) {
            data.element_text = elementInfo.text || '';
            data.element_id = elementInfo.id || '';
            data.element_classes = elementInfo.classes || '';
        }

        sendData(data);
    }

    /**
     * Scroll-Tiefe tracken
     */
    function trackScrollDepth(percent) {
        if (state.scrollTracked[percent]) return;
        state.scrollTracked[percent] = true;

        var data = getBaseData();
        data.type = 'event';
        data.event_type = 'scroll';
        data.event_name = 'scroll_depth';
        data.category = 'engagement';
        data.value = percent.toString();

        sendData(data);
    }

    /**
     * Element-Info extrahieren
     */
    function getElementInfo(el) {
        if (!el) return null;

        var text = el.innerText || el.textContent || '';
        text = text.trim().substring(0, 100);

        return {
            text: text,
            id: el.id || '',
            classes: el.className || ''
        };
    }

    /**
     * Submit-Button erkennen (Formular absenden)
     */
    function isSubmitButton(el) {
        if (!el) return false;

        var tagName = el.tagName.toLowerCase();
        var type = (el.getAttribute('type') || '').toLowerCase();
        var text = (el.innerText || '').toLowerCase();
        var classes = (el.className || '').toLowerCase();

        // Input type="submit"
        if (tagName === 'input' && type === 'submit') {
            return true;
        }

        // Button type="submit" oder ohne type in einem Form
        if (tagName === 'button') {
            if (type === 'submit') return true;
            if (!type && el.closest && el.closest('form')) return true;
        }

        // Button mit "absenden", "senden", "anfrage" im Text
        if (/absenden|senden|anfrage\s+senden|jetzt\s+anfragen|formular/i.test(text)) {
            return true;
        }

        return false;
    }

    /**
     * Kunden-Login Link erkennen
     */
    function isLoginLink(el) {
        if (!el) return false;

        var tagName = el.tagName.toLowerCase();
        var href = (el.getAttribute('href') || '').toLowerCase();
        var text = (el.innerText || '').toLowerCase();
        var id = (el.id || '').toLowerCase();

        if (tagName === 'a') {
            // Link zum Kundenportal
            if (href.includes('kunde/login') || href.includes('kundenportal')) {
                return true;
            }
            // ID enthält "kunden-login"
            if (id.includes('kunden-login') || id.includes('kunde-login')) {
                return true;
            }
            // Text enthält "Kunden Login"
            if (/kunden\s*login|kundenbereich|mein\s*konto/i.test(text)) {
                return true;
            }
        }

        return false;
    }

    /**
     * CTA-Element erkennen (OHNE Submit-Buttons und Login)
     */
    function isCTAElement(el) {
        if (!el) return false;

        // Keine Submit-Buttons als CTA zählen
        if (isSubmitButton(el)) return false;

        // Keine Login-Links als CTA zählen
        if (isLoginLink(el)) return false;

        var tagName = el.tagName.toLowerCase();
        var classes = (el.className || '').toLowerCase();
        var text = (el.innerText || '').toLowerCase();

        // Buttons und Links mit bestimmten Eigenschaften
        if (tagName === 'button' || el.getAttribute('role') === 'button') {
            return true;
        }

        if (tagName === 'a') {
            // CTA-typische Klassen
            if (/btn|button|cta|contact|anfrag|termin|beratung/i.test(classes)) {
                return true;
            }
            // CTA-typische Texte
            if (/jetzt|anfragen|kontakt|termin|beratung|starten|mehr erfahren/i.test(text)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Telefon-Link erkennen
     */
    function isPhoneLink(el) {
        if (!el || el.tagName.toLowerCase() !== 'a') return false;
        var href = el.getAttribute('href') || '';
        return href.startsWith('tel:');
    }

    /**
     * E-Mail-Link erkennen
     */
    function isEmailLink(el) {
        if (!el || el.tagName.toLowerCase() !== 'a') return false;
        var href = el.getAttribute('href') || '';
        return href.startsWith('mailto:');
    }

    /**
     * Debounce-Funktion
     */
    function debounce(func, wait) {
        var timeout;
        return function() {
            var context = this;
            var args = arguments;
            clearTimeout(timeout);
            timeout = setTimeout(function() {
                func.apply(context, args);
            }, wait);
        };
    }

    /**
     * Scroll-Handler
     */
    var handleScroll = debounce(function() {
        var scrollTop = window.pageYOffset || document.documentElement.scrollTop;
        var docHeight = Math.max(
            document.body.scrollHeight,
            document.documentElement.scrollHeight
        ) - window.innerHeight;

        if (docHeight <= 0) return;

        var scrollPercent = Math.round((scrollTop / docHeight) * 100);

        CONFIG.scrollThresholds.forEach(function(threshold) {
            if (scrollPercent >= threshold) {
                trackScrollDepth(threshold);
            }
        });
    }, CONFIG.debounceMs);

    /**
     * Klick-Handler
     */
    function handleClick(e) {
        var target = e.target;

        // Durch DOM nach oben traversieren um das relevante Element zu finden
        var el = target;
        var maxDepth = 5;
        var depth = 0;

        while (el && depth < maxDepth) {
            // Formular Submit-Button (höchste Priorität)
            if (isSubmitButton(el)) {
                var formName = 'Kontaktformular';
                var form = el.closest ? el.closest('form') : null;
                if (form) {
                    formName = form.getAttribute('data-form-name') || form.id || 'Kontaktformular';
                }
                trackEvent('formular_absenden', 'formular', formName, getElementInfo(el), 'submit');
                return;
            }

            // Kunden-Login Link
            if (isLoginLink(el)) {
                trackEvent('kunden_login_klick', 'login', window.location.pathname, getElementInfo(el));
                return;
            }

            // Telefon-Link
            if (isPhoneLink(el)) {
                trackEvent('phone_click', 'telefon', el.getAttribute('href'), getElementInfo(el));
                return;
            }

            // E-Mail-Link
            if (isEmailLink(el)) {
                trackEvent('email_click', 'email', el.getAttribute('href'), getElementInfo(el));
                return;
            }

            // CTA-Button (ohne Submit und Login)
            if (isCTAElement(el)) {
                var ctaName = el.getAttribute('data-track') || el.innerText.trim().substring(0, 50);
                trackEvent('cta_click', 'cta', ctaName, getElementInfo(el));
                return;
            }

            // Navigation Links
            if (el.tagName && el.tagName.toLowerCase() === 'a' && el.closest && el.closest('nav')) {
                trackEvent('nav_click', 'navigation', el.getAttribute('href'), getElementInfo(el));
                return;
            }

            el = el.parentElement;
            depth++;
        }
    }

    /**
     * Verweildauer tracken beim Verlassen (nur einmal senden)
     */
    function trackTimeOnPage() {
        if (state.timeOnPageSent) return;
        state.timeOnPageSent = true;

        var timeSpent = Math.round((Date.now() - state.pageStartTime) / 1000);

        // Nur tracken wenn mindestens 3 Sekunden auf der Seite
        if (timeSpent < 3) return;

        var data = getBaseData();
        data.type = 'event';
        data.event_type = 'engagement';
        data.event_name = 'time_on_page';
        data.category = 'verweildauer';
        data.value = timeSpent.toString();

        sendData(data);
    }

    /**
     * Formular-Submit Handler
     */
    function handleFormSubmit(e) {
        var form = e.target;
        if (form && form.tagName && form.tagName.toLowerCase() === 'form') {
            var formName = form.getAttribute('data-form-name') || form.id || 'Kontaktformular';
            trackEvent('formular_absenden', 'formular', formName, null, 'submit');
        }
    }

    /**
     * Initialisierung
     */
    function init() {
        // Seitenaufruf tracken
        trackPageview();

        // Event Listener
        document.addEventListener('click', handleClick, { passive: true });
        window.addEventListener('scroll', handleScroll, { passive: true });

        // Formular-Submits abfangen
        document.addEventListener('submit', handleFormSubmit, { passive: true });

        // Verweildauer beim Verlassen
        window.addEventListener('beforeunload', trackTimeOnPage);

        // Visibility Change (Tab-Wechsel)
        document.addEventListener('visibilitychange', function() {
            if (document.visibilityState === 'hidden') {
                trackTimeOnPage();
            }
        });

        // Verweildauer auch nach 30 Sekunden senden (falls Seite offen bleibt)
        setTimeout(function() {
            if (!state.timeOnPageSent) {
                var timeSpent = Math.round((Date.now() - state.pageStartTime) / 1000);
                var data = getBaseData();
                data.type = 'event';
                data.event_type = 'engagement';
                data.event_name = 'time_on_page';
                data.category = 'verweildauer';
                data.value = timeSpent.toString();
                sendData(data);
            }
        }, 30000);
    }

    /**
     * Öffentliche API
     */
    window.ZPTracker = {
        /**
         * Manuelles Event-Tracking
         */
        track: function(eventName, category, value) {
            trackEvent(eventName, category || 'custom', value || '');
        },

        /**
         * Formular-Submit tracken
         */
        trackForm: function(formName) {
            trackEvent('formular_absenden', 'formular', formName, null, 'submit');
        },

        /**
         * Download tracken
         */
        trackDownload: function(fileName) {
            trackEvent('download', 'download', fileName);
        },

        /**
         * Video-Event tracken
         */
        trackVideo: function(action, videoName) {
            trackEvent('video_' + action, 'video', videoName);
        }
    };

    // Bei DOM-Ready initialisieren
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

})();
