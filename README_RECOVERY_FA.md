# افزونه بازیابی رمز با سؤال امنیتی برای CodeIgniter 3

این بسته روی سورس `samira-sadrolashrafi/codeigniter-project` (نسخه ZIP ارسال‌شده) ساخته شده است.

> **هشدار امنیتی:** این پیاده‌سازی صرفاً برای دموی آموزشی با خواسته منتور است. **یک سؤال امنیتی به‌تنهایی روش قابل‌قبولی برای بازنشانی رمز در محیط عملیاتی نیست**؛ پاسخ‌ها ممکن است قابل حدس یا بازیابی از شبکه‌های اجتماعی باشند. پیش از انتشار واقعی، احراز مالکیت ایمیل با لینک/توکن کوتاه‌عمر یک‌بارمصرف یا یک عامل بازیابی معتبر اضافه کنید. CSRF پروژه اصلی نیز غیرفعال است و باید در انتشار عملیاتی اصلاح شود.

## محتویات

- فرم ثبت‌نام: انتخاب **یکی از ۵ سؤال** + یک پاسخ محرمانه (حداقل ۶ و حداکثر ۲۵۶ بایت).
- هش پاسخ با `password_hash()` در جدول `user_recovery` ذخیره می‌شود؛ متن اصلی ذخیره نمی‌شود.
- Modal فراموشی رمز در صفحه ورود، **بدون صفحه جدید**:
  1. نام کاربری → نمایش سؤال (برای حساب ناموجود، سؤال پیش‌فرض نمایش داده می‌شود تا وجود حساب مستقیماً اعلام نشود).
  2. تأیید پاسخ با `password_verify()`؛ تعداد تلاش‌های بازیابی محدود نمی‌شود.
  3. مجوز بازیابی Session با اعتبار ۵ دقیقه و Nonce یک‌بارمصرف → تعیین رمز جدید و تکرار آن → تأیید.
  4. ورود دستی با رمز جدید؛ پاسخ امنیتی به‌تنهایی باعث ورود مستقیم نمی‌شود.
- پس از تغییر رمز، Remember-Me Token کاربر باطل می‌شود. **نشست‌های باز دیگر به‌طور خودکار باطل نمی‌شوند**؛ برای محیط عملیاتی باید سازوکار ابطال نشست نیز اضافه شود.
- برای **کاربران قدیمی** که قبلاً ثبت‌نام کرده‌اند، دکمه «سؤال بازیابی» در هدر داشبورد، دسته‌بندی‌ها و تراکنش‌ها اضافه شده است. تغییر یا انتخاب سؤال نیازمند ورود رمز فعلی کاربر است.
- مسیرهای API:
  - `POST /auth/recovery/question`
  - `POST /auth/recovery/verify`
  - `POST /auth/recovery/reset`
  - `POST /auth/recovery/enroll` (نیازمند ورود و رمز فعلی)
- محدودسازی تلاش‌های ورود حذف شده است. Migration شماره `006` فقط به‌عنوان تاریخچهٔ طرح پایگاه داده باقی مانده و اجرای آن روی پایگاه دادهٔ جدید جدول `login_throttles` را ایجاد می‌کند؛ حذف خودکار آن از پایگاه داده‌های موجود انجام نمی‌شود تا داده‌ای پاک نشود.
- Migration شماره `010` جدول قدیمی `recovery_throttles` را با `DROP TABLE IF EXISTS` حذف می‌کند؛ جدول و اطلاعات `user_recovery` حفظ می‌شوند. Migrationهای قدیمی تغییر نکرده‌اند.

## نصب

### گزینه ۱: بسته کامل

فایل `codeigniter_project_recovery_full.zip` را Extract کرده و به‌عنوان نسخه جدید سورس استفاده کنید. از دیتابیس و تغییرات محلی فعلی Backup بگیرید.

### گزینه ۲: پچ فایل‌ها

فایل `codeigniter_account_recovery_patch.zip` را در **ریشه ریپو** Extract کنید تا پوشه `src/` روی سورس موجود ادغام شود. پیش از جایگزینی فایل‌های تغییر یافته، تغییرات محلی را با Diff بررسی کنید.

### اجرای Migration

در پوشه حاوی `compose.yml`:

```bash
# اگر شبکه خارجی تعریف‌شده در Compose از قبل وجود ندارد:
docker network create codeigniter_dev_environment_network

docker compose up -d --build
docker compose exec codeigniter-dev-environment php index.php migrate
```

باید `Migration completed successfully.` نمایش داده شود و Migrationهای جدید تا نسخه 10 اعمال شوند. Migration 7 جداول اولیه بازیابی را اضافه می‌کند و Migration 10 جدول محدودسازی قدیمی بازیابی را حذف می‌کند. جدول `user_recovery` باقی می‌ماند. بدون Migration فرم ثبت‌نام جدید کار نمی‌کند.

## تست پیشنهادی در مرورگر

1. `http://localhost:8000/register` را باز کنید. یک نام کاربری جدید بسازید؛ یکی از ۵ سؤال و یک جواب حداقل ۶ بایتی انتخاب کنید.
2. در `http://localhost:8000/login` روی «رمز عبور را فراموش کرده‌اید؟» کلیک کنید؛ Modal نمایش داده می‌شود.
3. نام کاربری ساخته‌شده را وارد کنید؛ **سؤال انتخاب‌شده** نمایش داده می‌شود.
4. یک جواب غلط وارد کنید؛ باید خطا دریافت کنید. با جواب صحیح مرحله تنظیم رمز جدید نمایش داده می‌شود.
5. رمز مطابق الگوی اعتبارسنجی قبلی پروژه (۸ کاراکتر، حروف بزرگ/کوچک انگلیسی، عدد و کاراکتر خاص) ثبت کنید. باید پیام موفقیت بگیرید.
6. Modal را ببندید و با **رمز جدید** وارد شوید؛ رمز قبلی نباید کار کند.
7. با یک حساب قدیمی وارد شوید و از دکمه «سؤال بازیابی» پاسخ را تنظیم کنید. برای ذخیره رمز فعلی لازم است.
8. تعداد تلاش‌های بازیابی محدود نمی‌شود؛ پاسخ‌های نادرست همچنان رد می‌شوند و برای ادامه باید پاسخ درست ارائه شود.

## تست API از Postman

> **الزامی:** مرحله‌های بازیابی باید در یک مرورگر یا کلاینت با **کوکی Session ثابت** انجام شوند. Postman Cookie Jar را فعال کنید. اگر در درخواست اول نام کاربری متفاوت ارسال کردید، فرایند را از ابتدا شروع کنید.

```text
POST /auth/recovery/question
x-www-form-urlencoded: username=<USERNAME>

POST /auth/recovery/verify
x-www-form-urlencoded: answer=<THE_ANSWER>

POST /auth/recovery/reset
x-www-form-urlencoded: nonce=<NONCE_FROM_VERIFY>, password=<NEW_PASSWORD>, password_confirm=<NEW_PASSWORD>
```

در صورت موفقیت مرحله دوم، فقط `nonce` به همان کلاینت برگردانده می‌شود و **Session محدود بازیابی** در سرور ذخیره می‌شود. Nonce و Session باید هر دو معتبر باشند.

## فایل‌های تغییر‌یافته و جدید

- `src/application/controllers/Auth.php` (انتقال سؤال‌ها به صفحه ثبت‌نام)
- `src/application/controllers/Auth_api.php` (ثبت‌نام و ورود بدون محدودسازی تلاش‌ها)
- `src/application/controllers/Recovery_api.php` (جدید)
- `src/application/services/Auth_service.php` (درج اتمیک سؤال و پاسخ همراه ثبت‌نام)
- `src/application/models/Recovery_model.php` (جدید)
- `src/application/config/recovery_questions.php` (جدید)
- `src/application/config/routes.php` (چهار Route جدید)
- `src/application/config/migration.php` (نسخه ۱۰)
- `src/application/migrations/007_create_account_recovery.php` (جدید)
- `src/application/migrations/010_drop_recovery_throttles.php` (جدید؛ حذف جدول قدیمی محدودسازی بازیابی)
- `src/application/controllers/Recovery_api.php` و `src/application/services/Profile_service.php` (حذف محدودسازی تلاش بازیابی)
- `src/application/views/auth/register.php`
- `src/application/views/auth/login.php`
- `src/application/views/auth/recovery_modal.php` (جدید)
- `src/application/views/layouts/app_header.php`
- `src/application/views/layouts/recovery_enroll_modal.php` (جدید)
- `src/assets/js/auth.js`
- `src/assets/js/recovery.js` (جدید)

## تست‌های انجام شده / انجام نشده

- بررسی نحوی `php -l` روی همه فایل‌های PHP برنامه: موفق.
- بررسی نحوی `node --check` برای فایل‌های JavaScript: موفق.
- تطبیق ساختار فرم‌ها، APIها و Migrationها: ایستا (Static) انجام شده.
- **تست واقعی HTTP و دیتابیس MariaDB**: در محیط حاضر انجام نشده؛ Docker/DB در دسترس نبود. قبل از Merge، راه‌اندازی و آزمون عملی لازم است.

## ملاحظات امنیتی برای نسخه عملیاتی

- بهترین روش بازیابی: ایمیل تأییدشده + لینک تصادفی کوتاه‌عمر یک‌بارمصرف.
- فعال‌سازی CSRF برای تمامی فرم‌ها و Fetchهای برنامه؛ پروژه اولیه `csrf_protection = FALSE` دارد.
- جلوگیری از User Enumeration زمانی: پاسخ سؤال از نظر زمان و توزیع واقعی اطلاعات هنوز کاملاً یکنواخت نیست.
- مکانیزم invalidate-all-sessions پس از تغییر رمز.
- فعال‌سازی HTTPS و Cookieهای Secure در محیط عملیاتی.
- کنترل دقیق Abuse/DoS ناشی از قفل شدن شمارنده هر حساب.
