
/**
 * TARAZINO | Persian display utilities
 *
 * Bootstrap / CodeIgniter 3 today, reusable pure functions in Vue 3 later.
 * No jQuery, no DOM scanning, no mutation of business data or input values.
 *
 * Usage after including this script BEFORE page-specific scripts:
 *   PersianFormat.number(1250000);               // "۱٬۲۵۰٬۰۰۰"
 *   PersianFormat.digits('Page 12');              // "Page ۱۲"
 *   PersianFormat.date('2026-10-15');             // "۱۴۰۵/۰۷/۲۳"
 *   PersianFormat.dateTime('2026-10-15 13:05:00'); // "۱۴۰۵/۰۷/۲۳ - ۱۳:۰۵"
 *
 * IMPORTANT:
 * - Keep original numeric values and Gregorian/ISO dates for calculations/API.
 * - These functions produce NEW presentation strings; they never change input.
 * - Input normalization is ONLY needed for digits a user manually types.
 * - A Persian date picker is a separate UI component, not part of this file.
 */
(function (root) {
    'use strict';

    const PERSIAN_DIGITS = '۰۱۲۳۴۵۶۷۸۹';
    const EMPTY_DISPLAY = '—';

    // Preserve the precise date/time components of an ISO DATE or a MySQL
    // DATETIME, without a browser timezone shift (e.g. crossing midnight).
    const WALL_TIME_PATTERN = /^(\d{4})-(\d{2})-(\d{2})(?:[ T](\d{2}):(\d{2})(?::(\d{2})(?:\.(\d{1,3}))?)?)?$/;
    const OFFSET_TIMESTAMP_PATTERN = /^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}(?::\d{2}(?:\.\d{1,3})?)?(?:Z|[+-]\d{2}:\d{2})$/i;

    const dateFormatters = {
        utc: new Intl.DateTimeFormat('fa-IR-u-ca-persian', {
            year: 'numeric', month: '2-digit', day: '2-digit', timeZone: 'UTC'
        }),
        local: new Intl.DateTimeFormat('fa-IR-u-ca-persian', {
            year: 'numeric', month: '2-digit', day: '2-digit'
        })
    };

    const timeFormatters = {
        utc: new Intl.DateTimeFormat('fa-IR', {
            hour: '2-digit', minute: '2-digit', hourCycle: 'h23', timeZone: 'UTC'
        }),
        local: new Intl.DateTimeFormat('fa-IR', {
            hour: '2-digit', minute: '2-digit', hourCycle: 'h23'
        })
    };

    function digits(value) {
        if (value === null || value === undefined) return '';
        return String(value).replace(/[0-9]/g, function (digit) {
            return PERSIAN_DIGITS[Number(digit)];
        });
    }

    function isNumeric(value) {
        if (typeof value === 'number') return Number.isFinite(value);
        if (typeof value === 'bigint') return true;

        // API decimal values can arrive as strings: preserve original precision.
        return typeof value === 'string' &&
            /^[+-]?(?:\d+(?:\.\d*)?|\.\d+)$/.test(value.trim());
    }

    function number(value, options) {
        if (!isNumeric(value)) return EMPTY_DISPLAY;

        const settings = Object.assign(
            { maximumFractionDigits: 2 },
            options || {}
        );

        return new Intl.NumberFormat('fa-IR', settings).format(value);
    }

    // For counters/page labels where fractional digits should never appear.
    function integer(value) {
        return number(value, { maximumFractionDigits: 0 });
    }

    // Percent accepts a ratio (e.g. 0.125 -> "۱۲٫۵٪"),
    // NOT a percentage number.
    function percent(ratio, options) {
        if (!isNumeric(ratio)) return EMPTY_DISPLAY;

        return new Intl.NumberFormat('fa-IR', Object.assign({
            style: 'percent',
            maximumFractionDigits: 1
        }, options || {})).format(ratio);
    }

    function readDate(value) {
        if (value instanceof Date) {
            return Number.isNaN(value.getTime())
                ? null
                : { date: value, utc: false };
        }

        if (typeof value !== 'string') return null;

        const source = value.trim();
        const wall = WALL_TIME_PATTERN.exec(source);

        if (wall) {
            const year = Number(wall[1]);
            const month = Number(wall[2]);
            const day = Number(wall[3]);
            const hour = wall[4] === undefined ? 0 : Number(wall[4]);
            const minute = wall[5] === undefined ? 0 : Number(wall[5]);
            const second = wall[6] === undefined ? 0 : Number(wall[6]);
            const ms = wall[7] === undefined
                ? 0
                : Number(wall[7].padEnd(3, '0'));

            if (
                month < 1 || month > 12 ||
                day < 1 || day > 31 ||
                hour > 23 || minute > 59 || second > 59
            ) {
                return null;
            }

            const parsed = new Date(
                Date.UTC(year, month - 1, day, hour, minute, second, ms)
            );

            // Reject impossible dates instead of rolling forward.
            if (
                parsed.getUTCFullYear() !== year ||
                parsed.getUTCMonth() !== month - 1 ||
                parsed.getUTCDate() !== day
            ) {
                return null;
            }

            return { date: parsed, utc: true };
        }

        // An explicit timezone represents a real moment.
        if (OFFSET_TIMESTAMP_PATTERN.test(source)) {
            const parsed = new Date(source);

            if (!Number.isNaN(parsed.getTime())) {
                return { date: parsed, utc: false };
            }
        }

        return null;
    }

    function date(value) {
        const parsed = readDate(value);
        if (!parsed) return EMPTY_DISPLAY;

        const formatter = dateFormatters[parsed.utc ? 'utc' : 'local'];
        const parts = formatter.formatToParts(parsed.date);

        const find = function (type) {
            const item = parts.find(function (part) {
                return part.type === type;
            });

            return item ? item.value : '';
        };

        // Explicit ordering avoids invisible RTL marks.
        return find('year') + '/' + find('month') + '/' + find('day');
    }

    function dateTime(value) {
        const parsed = readDate(value);
        if (!parsed) return EMPTY_DISPLAY;

        const day = date(value);

        const time = timeFormatters[
            parsed.utc ? 'utc' : 'local'
        ].format(parsed.date);

        return day + ' - ' + time;
    }

    // Use only at the INPUT boundary when users type Persian digits.
    // It is NOT needed to convert formatted output back for the API.
    function normalizeInputNumber(value) {
        if (value === null || value === undefined) return '';

        return String(value)
            .replace(/[\u06F0-\u06F9]/g, function (digit) {
                return String(digit.charCodeAt(0) - 0x06F0);
            })
            .replace(/[\u0660-\u0669]/g, function (digit) {
                return String(digit.charCodeAt(0) - 0x0660);
            })
            .replace(/[\u066C,]/g, '')
            .replace(/\u066B/g, '.');
    }

    const PersianFormat = Object.freeze({
        digits: digits,
        number: number,
        integer: integer,
        percent: percent,
        date: date,
        dateTime: dateTime,
        normalizeInputNumber: normalizeInputNumber
    });

    // Current CodeIgniter views: window.PersianFormat
    // A future Vue/ES-module wrapper can import these pure functions.
    root.PersianFormat = PersianFormat;

    if (typeof module !== 'undefined' && module.exports) {
        module.exports = PersianFormat;
    }
})(typeof window !== 'undefined' ? window : globalThis);
