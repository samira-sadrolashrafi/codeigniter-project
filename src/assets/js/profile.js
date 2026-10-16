
/** TARAZINO | Profile form validation and future API connection (CI3). */
(function (w, d) {
    'use strict';
    const $ = id => d.getElementById(id);

    function initialize() {
        const root = $('profileMain');
        if (!root) return;
        const form = $('profileForm');
        const ui = w.TarazinoProfileUI && w.TarazinoProfileUI.initialize();
        if (!ui) return;
        // Set data-profile-api-url ONLY when the secured backend route exists.
        const endpoint = (root.dataset.profileApiUrl || '').trim();
        let initialQuestion = $('profileRecoveryQuestion').value;
        let saving = false;

        function message(text, type = 'danger') {
            const box = $('profileMessage');
            box.textContent = text;
            box.className = text ? 'alert alert-' + type + ' mt-3' : 'alert d-none mt-3';
        }

        function fieldError(id, problem) {
            const element = $(id);
            if (!element) return;
            element.classList.toggle('is-invalid', Boolean(problem));
            const errorId = id + 'Feedback';
            let feedback = $(errorId);
            if (!feedback && problem) {
                feedback = d.createElement('div');
                feedback.id = errorId;
                feedback.className = 'invalid-feedback d-block';
                (element.closest('.input-group') || element).after(feedback);
            }
            if (feedback) {
                feedback.textContent = problem || '';
                feedback.classList.toggle('d-none', !problem);
            }
        }

        function validate() {
            const first = $('profileFirstName').value.trim();
            const last = $('profileLastName').value.trim();
            const username = $('profileUsername').value.trim();
            const email = $('profileEmail').value.trim();
            const current = $('profileCurrentPassword').value;
            const password = $('profileNewPassword').value;
            const confirm = $('profileConfirmPassword').value;
            const question = $('profileRecoveryQuestion').value;
            const answer = $('profileRecoveryAnswer').value.trim();
            const questionChanged = !!question && question !== initialQuestion;
            const recoveryChanged = questionChanged || !!answer;
            const changingPassword = !!password || !!confirm;
            const emailInput = $('profileEmail');
            const avatar = ui.getAvatar();
            const issues = {
                profileFirstName: [...first].length >= 1 && [...first].length <= 100 ? '' : 'نام باید بین ۱ تا ۱۰۰ نویسه باشد.',
                profileLastName: [...last].length >= 1 && [...last].length <= 100 ? '' : 'نام خانوادگی باید بین ۱ تا ۱۰۰ نویسه باشد.',
                profileUsername: /^[A-Za-z0-9_]{3,100}$/.test(username) ? '' : 'نام کاربری: ۳ تا ۱۰۰ کاراکتر انگلیسی، عدد یا _',
                profileEmail: email && email.length <= 191 && !emailInput.validity.typeMismatch ? '' : 'ایمیل معتبر وارد کنید.',
                profileCurrentPassword: (changingPassword || recoveryChanged) && !current ? 'برای تغییر اطلاعات امنیتی، رمز فعلی الزامی است.' : '',
                profileNewPassword: changingPassword && !/^(?=.*[A-Z])(?=.*[a-z])(?=.*\d)(?=.*[@#$!%*?&]).{8,}$/.test(password)
                    ? 'رمز جدید حداقل ۸ کاراکتر، حرف بزرگ، حرف کوچک، عدد و علامت ویژه لازم دارد.' : '',
                profileConfirmPassword: changingPassword && password !== confirm ? 'تکرار رمز جدید مطابقت ندارد.' : '',
                profileRecoveryQuestion: recoveryChanged && !question ? 'سؤال بازیابی را انتخاب کنید.' : '',
                profileRecoveryAnswer: recoveryChanged && (new TextEncoder().encode(answer).length < 6 || new TextEncoder().encode(answer).length > 256)
                    ? 'برای تغییر سؤال یا پاسخ، پاسخ جدید ۶ تا ۲۵۶ بایت وارد کنید.' : ''
            };
            Object.entries(issues).forEach(([id, problem]) => fieldError(id, problem));
            const avatarError = ui.validateAvatar(avatar.file);
            if (avatarError) message(avatarError);
            if (avatarError || Object.values(issues).some(Boolean)) return null;

            const data = new FormData(form);
            ['first_name', 'last_name', 'username', 'email'].forEach((key, index) => {
                data.set(key, [first, last, username, email][index]);
            });
            // Theme is local-only. No avatar file or sensitive values are sent unless changed.
            data.delete('theme');
            if (!changingPassword) {
                data.delete('new_password');
                data.delete('confirm_password');
            }
            if (!recoveryChanged) {
                data.delete('question_id');
                data.delete('recovery_answer');
            }
            if (!changingPassword && !recoveryChanged) data.delete('current_password');
            if (avatar.removed) {
                data.delete('avatar');
                data.set('remove_avatar', '1');
            } else if (!avatar.file) data.delete('avatar');
            return { data, question, recoveryChanged };
        }

        form.addEventListener('input', event => {
            if (event.target.id) fieldError(event.target.id, '');
            message('');
        });
        form.addEventListener('change', event => {
            if (event.target.id) fieldError(event.target.id, '');
        });

        form.addEventListener('submit', async event => {
            event.preventDefault();
            if (saving) return;
            const draft = validate();
            if (!draft) return;
            if (!endpoint) {
                message('فرم آماده است، اما API پروفایل هنوز ساخته نشده؛ اطلاعات حساب، عکس و رمز عبور ذخیره نشدند. تم در مرورگر ذخیره می‌شود.', 'info');
                return;
            }
            if (!w.TarazinoApi) {
                message('ابتدا فایل api.js را بارگذاری کنید.');
                return;
            }
            const button = $('profileSaveButton');
            saving = true;
            button.disabled = true;
            try {
                const result = await w.TarazinoApi.post(endpoint, draft.data);
                if (draft.recoveryChanged) initialQuestion = draft.question;
                ['profileCurrentPassword', 'profileNewPassword', 'profileConfirmPassword', 'profileRecoveryAnswer']
                    .forEach(id => { $(id).value = ''; });
                const avatarUrl = result.profile && typeof result.profile.avatar_url === 'string'
                    ? result.profile.avatar_url : undefined;
                ui.saved(avatarUrl);
                message(result.message || 'تغییرات با موفقیت ذخیره شدند.', 'success');
            } catch (error) {
                message(error.message || 'ذخیره تغییرات انجام نشد.');
            } finally {
                saving = false;
                button.disabled = false;
            }
        });
    }
    if (d.readyState === 'loading') d.addEventListener('DOMContentLoaded', initialize, { once: true });
    else initialize();
})(window, document);
