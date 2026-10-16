
/** TARAZINO | Profile presentation: avatar preview, theme, password visibility. */
(function (w, d) {
    'use strict';
    const $ = id => d.getElementById(id);

    function initialize() {
        if (!$('profileMain')) return null;
        const input = $('profileAvatarInput');
        const image = $('profileAvatarImage');
        const fallback = $('profileAvatarFallback');
        const error = $('profileAvatarError');
        const notice = $('profileAvatarPreviewNotice');
        let objectUrl = null;
        let removed = false;

        function avatarMessage(text, invalid = false) {
            const box = invalid ? error : notice;
            const other = invalid ? notice : error;
            other.classList.add('d-none');
            box.textContent = text;
            box.classList.toggle('d-none', !text);
        }

        function preview(url) {
            const hasImage = Boolean(url);
            image.classList.toggle('d-none', !hasImage);
            fallback.classList.toggle('d-none', hasImage);
            if (hasImage) image.src = url;
            else image.removeAttribute('src');
        }

        function releasePreview() {
            if (objectUrl) w.URL.revokeObjectURL(objectUrl);
            objectUrl = null;
        }

        function selectedFile() {
            return input.files && input.files.length ? input.files[0] : null;
        }

        function validAvatar(file) {
            if (!file) return '';
            if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type)) {
                return 'فرمت عکس باید JPG، PNG یا WebP باشد.';
            }
            if (file.size < 1 || file.size > 2 * 1024 * 1024) {
                return 'حجم عکس باید بیشتر از صفر و حداکثر ۲ مگابایت باشد.';
            }
            return '';
        }

        input.addEventListener('change', () => {
            const file = selectedFile();
            const problem = validAvatar(file);
            if (problem) {
                input.value = '';
                avatarMessage(problem, true);
                return;
            }
            if (!file) return;
            releasePreview();
            objectUrl = w.URL.createObjectURL(file);
            removed = false;
            preview(objectUrl);
            avatarMessage('پیش‌نمایش عکس آماده است؛ برای ثبت روی «ذخیره تغییرات» بزنید.');
        });

        $('profileRemoveAvatar').addEventListener('click', () => {
            input.value = '';
            releasePreview();
            removed = true;
            preview('');
            avatarMessage('حذف عکس انتخاب شد؛ هنوز روی سرور ذخیره نشده است.');
        });

        const theme = d.documentElement.getAttribute('data-bs-theme') === 'dark' ? 'dark' : 'light';
        d.querySelectorAll('input[name="theme"]').forEach(radio => {
            radio.checked = radio.value === theme;
            radio.addEventListener('change', () => {
                if (!radio.checked) return;
                d.documentElement.setAttribute('data-bs-theme', radio.value);
                try { w.localStorage.setItem('tarazino-theme', radio.value); }
                catch (_) { /* Storage can be unavailable. */ }
            });
        });

        d.querySelectorAll('[data-profile-password-toggle]').forEach(button => {
            button.addEventListener('click', () => {
                const field = $(button.dataset.profilePasswordToggle);
                if (!field) return;
                const showing = field.type === 'text';
                field.type = showing ? 'password' : 'text';
                button.setAttribute('aria-pressed', String(!showing));
                button.setAttribute('aria-label', showing ? 'نمایش رمز عبور' : 'پنهان کردن رمز عبور');
                const icon = button.querySelector('i');
                if (icon) {
                    icon.classList.toggle('fa-eye', showing);
                    icon.classList.toggle('fa-eye-slash', !showing);
                }
            });
        });

        w.addEventListener('pagehide', releasePreview);
        return Object.freeze({
            getAvatar: () => ({ file: selectedFile(), removed }),
            validateAvatar: validAvatar,
            saved: avatarUrl => {
                removed = false;
                input.value = '';
                avatarMessage('');
                if (typeof avatarUrl === 'string') {
                    releasePreview();
                    preview(avatarUrl);
                }
            }
        });
    }
    w.TarazinoProfileUI = Object.freeze({ initialize });
})(window, document);
