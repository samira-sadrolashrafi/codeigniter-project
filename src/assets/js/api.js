/**
 * TARAZINO | Shared JSON API client
 * CodeIgniter 3 now; reusable as a service in Vue 3 later.
 *
 * - No jQuery or page-specific DOM code.
 * - Keep FormData unchanged so CI3's $this->input->post() works.
 * - API amounts and dates are NOT converted or formatted here.
 * - All endpoints must return JSON with { success: true | false }.
 *
 * Examples:
 *   const data = await TarazinoApi.get(transactionsUrl);
 *   const saved = await TarazinoApi.post(categoriesUrl, formData);
 */
(function (root) {
    'use strict';

    async function request(url, options) {
        if (typeof url !== 'string' || !url.trim()) {
            throw new Error('آدرس درخواست مشخص نیست.');
        }

        const settings = Object.assign({
            method: 'GET',
            credentials: 'same-origin'
        }, options || {});

        // Do not set Content-Type for FormData: the browser adds its boundary.
        const headers = new Headers(settings.headers || {});
        if (!headers.has('Accept')) headers.set('Accept', 'application/json');
        settings.headers = headers;

        let response;
        try {
            response = await fetch(url, settings);
        } catch (error) {
            if (error && error.name === 'AbortError') throw error;
            throw new Error('ارتباط با سرور برقرار نشد. لطفاً دوباره تلاش کنید.');
        }

        let payload;
        try {
            payload = await response.json();
        } catch (error) {
            throw new Error('پاسخ سرور JSON معتبر نیست. ممکن است نشست شما پایان یافته باشد.');
        }

        if (!payload || typeof payload !== 'object' || Array.isArray(payload)) {
            throw new Error('ساختار پاسخ سرور معتبر نیست.');
        }

        if (!response.ok || payload.success !== true) {
            const fallback = response.status === 401
                ? 'نشست شما پایان یافته است. دوباره وارد حساب شوید.'
                : 'درخواست انجام نشد. لطفاً دوباره تلاش کنید.';
            throw new Error(typeof payload.message === 'string' && payload.message.trim()
                ? payload.message : fallback);
        }

        return payload;
    }

    function get(url, options) {
        return request(url, Object.assign({}, options, { method: 'GET' }));
    }

    function post(url, formData, options) {
        return request(url, Object.assign({}, options, {
            method: 'POST',
            body: formData
        }));
    }

    root.TarazinoApi = Object.freeze({ request: request, get: get, post: post });
})(typeof window !== 'undefined' ? window : globalThis);
