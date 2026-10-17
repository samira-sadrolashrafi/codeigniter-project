/** TARAZINO | Shared light/dark theme control. */
(function (w, d) {
    'use strict';

    const radios = Array.from(d.querySelectorAll('[data-component="theme-selector"] input[type="radio"]'));
    const current = d.documentElement.getAttribute('data-bs-theme') === 'dark' ? 'dark' : 'light';

    function selectTheme(theme) {
        d.documentElement.setAttribute('data-bs-theme', theme);
        radios.forEach(radio => { radio.checked = radio.value === theme; });
        try { w.localStorage.setItem('tarazino-theme', theme); }
        catch (_) { /* Storage can be unavailable. */ }
    }

    radios.forEach(radio => {
        radio.checked = radio.value === current;
        radio.addEventListener('change', () => {
            if (radio.checked) selectTheme(radio.value);
        });
    });
})(window, document);
