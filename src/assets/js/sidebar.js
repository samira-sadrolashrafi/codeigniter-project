(function (document) {
    'use strict';

    var sidebar = document.querySelector('.dashboard-sidebar-wrap');
    var toggle = document.querySelector('[data-sidebar-toggle]');
    if (!sidebar || !toggle) return;

    var root = document.documentElement;

    function render(expanded) {
        sidebar.classList.toggle('is-expanded', expanded);
        root.setAttribute('data-sidebar-expanded', expanded ? 'true' : 'false');
        var label = expanded ? 'بستن منو' : 'باز کردن منو';
        toggle.setAttribute('aria-expanded', expanded ? 'true' : 'false');
        toggle.setAttribute('aria-label', label);
        toggle.setAttribute('title', label);
        var icon = toggle.querySelector('i');
        if (icon) {
            icon.classList.toggle('fa-angles-left', !expanded);
            icon.classList.toggle('fa-angles-right', expanded);
        }
    }

    var initiallyExpanded = root.getAttribute('data-sidebar-expanded') === 'true';
    render(initiallyExpanded);

    toggle.addEventListener('click', function () {
        var expanded = !sidebar.classList.contains('is-expanded');
        render(expanded);
        try {
            window.localStorage.setItem('tarazino-sidebar-expanded', expanded ? 'true' : 'false');
        } catch (error) {
            // The current page still responds when browser storage is unavailable.
        }
    });
}(document));
