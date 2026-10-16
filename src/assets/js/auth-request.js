/**
 * TARAZINO | Auth-only request helper.
 * Unlike TarazinoApi, unsuccessful responses retain their field-level errors.
 * No jQuery; FormData and server-side validation remain authoritative.
 */
(function (window) {
    'use strict';

    async function post(url, form) {
        if (!url) throw new Error('آدرس درخواست مشخص نیست.');

        let response;
        try {
            response = await fetch(url, {
                method: 'POST',
                credentials: 'same-origin',
                headers: { Accept: 'application/json' },
                body: new FormData(form)
            });
        } catch (_) {
            throw new Error('ارتباط با سرور برقرار نشد. لطفاً دوباره تلاش کنید.');
        }

        let result;
        try {
            result = await response.json();
        } catch (_) {
            throw new Error('پاسخ سرور معتبر نیست. اتصال یا نشست را بررسی کنید.');
        }

        if (!result || typeof result !== 'object' || Array.isArray(result)) {
            throw new Error('ساختار پاسخ سرور معتبر نیست.');
        }

        return {
            ok: response.ok && result.success === true,
            result,
            status: response.status
        };
    }

    window.TarazinoAuthRequest = Object.freeze({ post });
})(window);