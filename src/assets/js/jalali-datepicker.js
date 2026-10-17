/**
 * TARAZINO | JalaliDatePicker adapter (CodeIgniter 3 / Bootstrap 5 RTL)
 * Dependency: @majidh1/jalalidatepicker (local vendor JS + CSS).
 * Load vendor JS BEFORE this file and this file BEFORE page scripts.
 *
 * The original input[type="date"] keeps its ID, name, and ISO YYYY-MM-DD
 * value for application logic/API. A read-only Jalali display input is added
 * for users. The picker writes Gregorian values to the original input.
 *
 * Public API: TarazinoDatepicker.init(root?) / .refresh(root?).
 * No jQuery and no custom calendar implementation.
 */
(function (window, document) {
    'use strict';

    var instances = new Map();
    var started = false;
    var FORMATTER = new Intl.DateTimeFormat('fa-IR-u-ca-persian', {
        year: 'numeric', month: '2-digit', day: '2-digit', timeZone: 'UTC'
    });

    function asciiDigits(value) {
        return String(value)
            .replace(/[\u06F0-\u06F9]/g, function (digit) {
                return String(digit.charCodeAt(0) - 0x06F0);
            })
            .replace(/[\u0660-\u0669]/g, function (digit) {
                return String(digit.charCodeAt(0) - 0x0660);
            });
    }

    function toJalaliAscii(iso) {
        if (!/^\d{4}-\d{2}-\d{2}$/.test(iso || '')) return '';

        var year = Number(iso.slice(0, 4));
        var month = Number(iso.slice(5, 7));
        var day = Number(iso.slice(8, 10));
        var date = new Date(Date.UTC(year, month - 1, day));

        if (date.getUTCFullYear() !== year ||
            date.getUTCMonth() + 1 !== month ||
            date.getUTCDate() !== day) {
            return '';
        }

        var values = {};
        FORMATTER.formatToParts(date).forEach(function (part) {
            if (['year', 'month', 'day'].includes(part.type)) {
                values[part.type] = asciiDigits(part.value).padStart(2, '0');
            }
        });

        return [values.year, values.month, values.day].join('/');
    }

    function localTodayIso() {
        var today = new Date();
        var pad = function (value) { return String(value).padStart(2, '0'); };
        return today.getFullYear() + '-' + pad(today.getMonth() + 1) + '-' + pad(today.getDate());
    }

    function toPersian(value) {
        if (window.PersianFormat && typeof window.PersianFormat.digits === 'function') {
            return window.PersianFormat.digits(value);
        }
        return String(value).replace(/\d/g, function (digit) {
            return '۰۱۲۳۴۵۶۷۸۹'[Number(digit)];
        });
    }

    function setPickerAttributes(instance) {
        var source = instance.source;
        var display = instance.display;
        var jalaliDate = toJalaliAscii(source.value);
        var initialCalendarDate = jalaliDate || toJalaliAscii(localTodayIso());

        display.value = jalaliDate ? toPersian(jalaliDate) : '';

        // Keep a valid initial month even when the original date field is empty.
        // The library reads this attribute dynamically for the currently active input.
        // Do not call datepicker.updateOptions() here: its implementation resolves
        // and freezes the "attr" getters, which can make later inputs write to the
        // first input that was opened.
        if (initialCalendarDate) {
            display.setAttribute('data-jdp-init-date', initialCalendarDate);
        } else {
            display.removeAttribute('data-jdp-init-date');
        }

        ['min', 'max'].forEach(function (key) {
            var value = toJalaliAscii(source.getAttribute(key));
            if (value) display.setAttribute('data-jdp-' + key + '-date', value);
            else display.removeAttribute('data-jdp-' + key + '-date');
        });

        display.disabled = source.disabled;
        display.classList.toggle('is-invalid', source.classList.contains('is-invalid'));
        display.classList.toggle('is-valid', source.classList.contains('is-valid'));

        if (source.hasAttribute('aria-invalid')) {
            display.setAttribute('aria-invalid', source.getAttribute('aria-invalid'));
        } else {
            display.removeAttribute('aria-invalid');
        }

        if (instance.clear) {
            instance.clear.disabled = source.disabled || !source.value;
        }
        instance.lastValue = source.value;

        // The native input is hidden, so expose its validation feedback beside
        // the visible display input.
        if (instance.feedback) {
            instance.feedback.classList.toggle(
                'd-block', source.classList.contains('is-invalid')
            );
        }
    }

    function notifyOriginal(instance) {
        var value = instance.source.value;
        if (value === instance.lastValue) {
            setPickerAttributes(instance);
            return;
        }

        instance.lastValue = value;
        instance.source.dispatchEvent(new Event('input', { bubbles: true }));
        instance.source.dispatchEvent(new Event('change', { bubbles: true }));
        setPickerAttributes(instance);
    }

    function openPicker(instance) {
        if (instance.source.disabled || !window.jalaliDatepicker) return;
        setPickerAttributes(instance);
        window.jalaliDatepicker.show(instance.display);
    }

    function createPicker(source) {
        if (instances.has(source) || !source.id ||
            !/^[A-Za-z][A-Za-z0-9_:-]*$/.test(source.id)) {
            return;
        }

        var field = document.createElement('input');
        field.type = 'text';
        field.id = source.id + 'Jalali';
        field.className = 'form-control' +
            (source.classList.contains('form-control-sm') ? ' form-control-sm' : '') +
            (source.classList.contains('form-control-lg') ? ' form-control-lg' : '');
        field.placeholder = 'تاریخ شمسی را انتخاب کنید';
        field.readOnly = true;
        field.autocomplete = 'off';
        field.dir = 'ltr';
        field.setAttribute('data-jdp', '');
        field.setAttribute('data-tz-jalali-picker', '');
        field.setAttribute('data-jdp-target-value-input', '#' + source.id);
        field.setAttribute('data-jdp-target-value-type', 'gregorian');
        field.setAttribute('aria-label', 'انتخاب تاریخ شمسی');
        field.setAttribute('aria-required', source.required ? 'true' : 'false');

        var describedBy = source.getAttribute('aria-describedby');
        if (describedBy) field.setAttribute('aria-describedby', describedBy);

        var wrapper = document.createElement('div');
        wrapper.className = 'input-group has-validation';
        wrapper.setAttribute('data-tz-date-wrapper', '');

        var iconButton = document.createElement('button');
        iconButton.type = 'button';
        iconButton.className = 'btn btn-outline-secondary';
        iconButton.setAttribute('aria-label', 'باز کردن تقویم شمسی');
        iconButton.innerHTML = '<i class="fa-regular fa-calendar-days" aria-hidden="true"></i>';
        wrapper.appendChild(field);
        wrapper.appendChild(iconButton);

        var clear = null;
        if (!source.required) {
            clear = document.createElement('button');
            clear.type = 'button';
            clear.className = 'btn btn-outline-secondary';
            clear.setAttribute('aria-label', 'پاک کردن تاریخ');
            clear.innerHTML = '<i class="fa-solid fa-xmark" aria-hidden="true"></i>';
            wrapper.appendChild(clear);
        }

        source.insertAdjacentElement('afterend', wrapper);
        source.classList.add('d-none');
        source.setAttribute('aria-hidden', 'true');
        source.tabIndex = -1;

        var label = document.querySelector('label[for="' + source.id + '"]');
        if (label) label.htmlFor = field.id;

        var feedback = document.getElementById(source.id + 'Error');
        var instance = {
            source: source,
            display: field,
            clear: clear,
            wrapper: wrapper,
            feedback: feedback,
            lastValue: source.value,
            openedOnFocus: false
        };

        instances.set(source, instance);
        setPickerAttributes(instance);

        // The picker is kept in document.body with a z-index above Bootstrap's
        // modal. Moving its singleton DOM nodes between modal containers, or
        // calling updateOptions() after a field opens, breaks the library's
        // per-input "attr" options and can route a selected date to another field.
        field.addEventListener('focus', function () {
            openPicker(instance);
            instance.openedOnFocus = true;
            window.setTimeout(function () {
                instance.openedOnFocus = false;
            }, 0);
        });

        // Clicking an already-focused field must reopen the picker after a
        // previous selection hid it. Avoid showing it twice on the focus+click
        // sequence when focus has just opened it.
        field.addEventListener('click', function () {
            if (source.disabled) return;
            if (instance.openedOnFocus) {
                instance.openedOnFocus = false;
                return;
            }
            openPicker(instance);
        });

        // Clicking the calendar icon must not bubble to the library's document
        // auto-hide listener. With autoShow disabled, focus opens exactly once.
        iconButton.addEventListener('click', function (event) {
            if (source.disabled) return;
            event.preventDefault();
            event.stopPropagation();
            if (document.activeElement === field) {
                openPicker(instance);
            } else {
                field.focus();
            }
        });

        if (clear) {
            clear.addEventListener('click', function (event) {
                event.preventDefault();
                event.stopPropagation();
                source.value = '';
                notifyOriginal(instance);
                window.jalaliDatepicker.hide();
            });
        }

        // The library updates the Gregorian target before firing change events
        // on the visible Jalali field. Forward the resulting value to app listeners.
        field.addEventListener('jdp:change', function () {
            queueMicrotask(function () { notifyOriginal(instance); });
        });
        field.addEventListener('change', function () {
            queueMicrotask(function () { notifyOriginal(instance); });
        });

        source.addEventListener('input', function () {
            setPickerAttributes(instance);
        });
        source.addEventListener('change', function () {
            setPickerAttributes(instance);
        });

        if (window.MutationObserver) {
            var observer = new MutationObserver(function () {
                setPickerAttributes(instance);
            });
            observer.observe(source, {
                attributes: true,
                attributeFilter: ['class', 'disabled', 'min', 'max', 'aria-invalid']
            });
            instance.observer = observer;
        }
    }

    function init(container) {
        if (!window.jalaliDatepicker ||
            typeof window.jalaliDatepicker.startWatch !== 'function') {
            console.warn(
                'Tarazino: load vendor jalalidatepicker.min.js before jalali-datepicker.js'
            );
            return false;
        }

        var parent = container || document;
        if (parent.matches && parent.matches('input[type="date"]') &&
            parent.getAttribute('data-jalali') !== 'off') {
            createPicker(parent);
        }
        parent.querySelectorAll('input[type="date"]:not([data-jalali="off"])')
            .forEach(createPicker);

        if (!started) {
            window.jalaliDatepicker.startWatch({
                selector: 'input[data-tz-jalali-picker]',
                // Auto-open is handled by this adapter so focus/click/icon actions
                // cannot double-open the singleton calendar.
                autoShow: false,
                persianDigits: true,
                targetValueInput: 'attr',
                targetValueType: 'attr',
                initDate: 'attr',
                minDate: 'attr',
                maxDate: 'attr',
                date: true,
                time: false,
                zIndex: 1100,
                separatorChars: {
                    date: '/', between: ' ', time: ':',
                    targetDate: '-', targetBetween: ' ', targetTime: ':'
                }
            });
            started = true;
        }
        return true;
    }

    function refresh(container) {
        instances.forEach(function (instance, source) {
            if (!source.isConnected) {
                if (instance.observer) instance.observer.disconnect();
                instances.delete(source);
                return;
            }
            if (!container || container === source || container.contains(source)) {
                setPickerAttributes(instance);
            }
        });
    }

    document.addEventListener('reset', function (event) {
        // Native form reset updates .value after reset listeners finish.
        window.setTimeout(function () { refresh(event.target); }, 0);
    }, true);

    ['show.bs.modal', 'shown.bs.modal'].forEach(function (eventName) {
        document.addEventListener(eventName, function (event) {
            refresh(event.target);
        });
    });

    window.addEventListener('pageshow', function () { refresh(); });

    window.TarazinoDatepicker = Object.freeze({ init: init, refresh: refresh });

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () { init(); }, { once: true });
    } else {
        init();
    }
})(window, document);