/**
 * TARAZINO | Auto-dismiss transient informational alerts.
 *
 * Any element with class "alert" and role="alert" or role="status" is hidden
 * 3 seconds after it becomes visible (filled and no longer d-none), whether it
 * existed in the markup or was added later by JavaScript.
 *
 * Guarantees:
 *  - One timer per element; a new message restarts the 3s window, unrelated
 *    DOM mutations do not.
 *  - Manual close (Bootstrap btn-close or a helper hiding the alert) cancels
 *    the pending timer.
 *  - Inline field errors, permanent notes (role="note") and elements marked
 *    data-no-autohide="true" are never touched.
 *  - No library and no per-message timer code: the shared observer handles
 *    every existing and future message.
 *
 * Reusable API: window.TarazinoAutoHide.schedule(element[, delayMs]).
 */
(function (w, d) {
    'use strict';

    var AUTOHIDE_DELAY_MS = 3000;
    var state = new WeakMap(); // element -> { token, timer }

    function isShown(el) {
        if (!(el instanceof Element) || !el.classList.contains('alert')) return false;
        if (el.classList.contains('d-none') || el.hidden) return false;
        if (el.dataset.noAutohide === 'true') return false;
        var text = el.textContent ? el.textContent.trim() : '';
        if (!text) return false;
        var role = el.getAttribute('role');
        return role === 'alert' || role === 'status';
    }

    function tokenOf(el) {
        return (el.textContent || '').trim() + ' ' + el.className;
    }

    function dismiss(el) {
        var entry = state.get(el);
        if (entry && entry.timer) w.clearTimeout(entry.timer);
        state.delete(el);
        el.textContent = '';
        el.classList.add('d-none');
    }

    function schedule(el, delayMs) {
        if (!isShown(el)) {
            // Hidden or closed meanwhile: cancel any pending timer.
            var old = state.get(el);
            if (old) {
                w.clearTimeout(old.timer);
                state.delete(el);
            }
            return;
        }
        var token = tokenOf(el);
        var delay = typeof delayMs === 'number' ? delayMs : AUTOHIDE_DELAY_MS;
        var entry = state.get(el);
        if (entry && entry.timer && entry.token === token) return; // no duplicate timers
        if (entry && entry.timer) w.clearTimeout(entry.timer);
        entry = {
            token: token,
            timer: w.setTimeout(function () { dismiss(el); }, delay)
        };
        state.set(el, entry);
    }

    function scan() {
        var alerts = d.querySelectorAll('.alert');
        for (var i = 0; i < alerts.length; i++) schedule(alerts[i]);
    }

    // React to anything that can reveal or fill an alert: added nodes,
    // class changes (d-none removal / alert-* type) and text changes.
    // Deferred until <body> exists (the script loads from <head> in the layout).
    function mount() {
        var observer = new MutationObserver(scan);
        observer.observe(d.body, {
            childList: true,
            subtree: true,
            attributes: true,
            attributeFilter: ['class', 'role', 'hidden'],
            characterData: true
        });
        // Any alert already visible when the page loads.
        scan();
    }

    if (d.readyState === 'loading') {
        d.addEventListener('DOMContentLoaded', mount, { once: true });
    } else {
        mount();
    }

    w.TarazinoAutoHide = {
        AUTOHIDE_DELAY_MS: AUTOHIDE_DELAY_MS,
        schedule: schedule,
        dismiss: dismiss
    };
}(window, document));
