
/**
 * TARAZINO | JalaliDatePicker adapter (CodeIgniter 3 / Bootstrap 5 RTL)
 * Dependency: @majidh1/jalalidatepicker (local vendor JS + CSS).
 * Load vendor JS BEFORE this file and this file BEFORE page scripts.
 *
 * The ORIGINAL <input type="date"> keeps its ID, name, and ISO YYYY-MM-DD
 * value for the existing application logic/API. A read-only Persian input
 * becomes the user-facing picker, and the library writes Gregorian dates
 * directly into the original input via targetValueInput.
 *
 * Public API: TarazinoDatepicker.init(root?) / .refresh(root?).
 * No jQuery, no calendar implementation, no DOM-wide digit replacement.
 */
(function (window, document) {
    'use strict';

    var instances = new Map();
    var started = false;
    var FORMATTER = new Intl.DateTimeFormat('fa-IR-u-ca-persian', {
        year: 'numeric', month: '2-digit', day: '2-digit', timeZone: 'UTC'
    });

    function asciiDigits(value) {
        return String(value).replace(/[\u06F0-\u06F9]/g, function (d) {
            return String(d.charCodeAt(0) - 0x06F0);
        }).replace(/[\u0660-\u0669]/g, function (d) {
            return String(d.charCodeAt(0) - 0x0660);
        });
    }

    function toJalaliAscii(iso) {
        if (!/^\d{4}-\d{2}-\d{2}$/.test(iso || '')) return '';
        var y = Number(iso.slice(0, 4));
        var m = Number(iso.slice(5, 7));
        var d = Number(iso.slice(8, 10));
        var date = new Date(Date.UTC(y, m - 1, d));
        if (date.getUTCFullYear() !== y || date.getUTCMonth() + 1 !== m ||
            date.getUTCDate() !== d) return '';
        var parts = FORMATTER.formatToParts(date);
        var values = {};
        parts.forEach(function (part) {
            if (['year', 'month', 'day'].includes(part.type)) {
                values[part.type] = asciiDigits(part.value).padStart(2, '0');
            }
        });
        return [values.year, values.month, values.day].join('/');
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
        display.value = jalaliDate ? toPersian(jalaliDate) : '';

        // Attributes are read by JalaliDatePicker when the input is focused.
        if (jalaliDate) display.setAttribute('data-jdp-init-date', jalaliDate);
        else display.removeAttribute('data-jdp-init-date');
        ['min', 'max'].forEach(function (key) {
            var val = toJalaliAscii(source.getAttribute(key));
            if (val) display.setAttribute('data-jdp-' + key + '-date', val);
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
        if (instance.clear) instance.clear.disabled = source.disabled || !source.value;
        instance.lastValue = source.value;

        // Bootstrap feedback would otherwise be hidden because the original
        // input now lives in an input-group wrapper.
        var feedback = instance.feedback;
        if (feedback) {
            feedback.classList.toggle('d-block', source.classList.contains('is-invalid'));
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

    function createPicker(source) {
        if (instances.has(source) || !source.id ||
            !/^[A-Za-z][A-Za-z0-9_:-]*$/.test(source.id)) return;

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

        var feedback = source.id ? document.getElementById(source.id + 'Error') : null;
        var instance = { source: source, display: field, clear: clear,
            wrapper: wrapper, feedback: feedback, lastValue: source.value };
        instances.set(source, instance);
        setPickerAttributes(instance);

        field.addEventListener('focus', function () {
            setPickerAttributes(instance);
            // Run after the datepicker's focus handler assigns its active input.
            // Updating options before then makes its per-input date attributes
            // resolve without an input, leaving the calendar without day cells.
            window.setTimeout(function () {
                var modal = field.closest('.modal.show');
                var container = modal
                    ? (modal.querySelector('.modal-content') || modal)
                    : document.body;
                if (window.jalaliDatepicker.updateOptions) {
                    window.jalaliDatepicker.updateOptions({
                        container: container, zIndex: modal ? 1080 : 1100
                    });
                }
                ['jdp-container', 'jdp-overlay'].forEach(function (selector) {
                    var popup = document.querySelector(selector);
                    if (popup && popup.parentElement !== container) {
                        container.appendChild(popup);
                    }
                });
            }, 0);
        }, true);
        iconButton.addEventListener('click', function () {
            if (source.disabled) return;
            field.focus();
            window.jalaliDatepicker.show(field);
        });
        if (clear) clear.addEventListener('click', function () {
            source.value = '';
            notifyOriginal(instance);
            field.focus();
            window.jalaliDatepicker.hide();
        });

        // The library updates its Gregorian target and then fires a change
        // event on the Persian display field; propagate to existing listeners.
        field.addEventListener('jdp:change', function () {
            queueMicrotask(function () { notifyOriginal(instance); });
        });
        field.addEventListener('change', function () {
            queueMicrotask(function () { notifyOriginal(instance); });
        });
        source.addEventListener('input', function () { setPickerAttributes(instance); });
        source.addEventListener('change', function () { setPickerAttributes(instance); });
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
            console.warn('Tarazino: load vendor jalalidatepicker.min.js before jalali-datepicker.js');
            return false; // Preserve native <input type="date"> if vendor missing.
        }
        var parent = container || document;
        if (parent.matches && parent.matches('input[type="date"]') &&
            parent.getAttribute('data-jalali') !== 'off') createPicker(parent);
        parent.querySelectorAll('input[type="date"]:not([data-jalali="off"])')
            .forEach(createPicker);

        if (!started) {
            window.jalaliDatepicker.startWatch({
                selector: 'input[data-tz-jalali-picker]',
                persianDigits: true,
                targetValueInput: 'attr',
                targetValueType: 'attr',
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
        // Native form reset updates .value after event handlers complete.
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
