<?php
defined('BASEPATH') OR exit('No direct script access allowed');

function validateUsername($username)
{
    if (empty($username)) {
        return "وارد کردن نام کاربری الزامی است.";
    }

    if (!preg_match('/^[a-zA-Z0-9_]{3,}$/', $username)) {
        return "نام کاربری باید حداقل ۳ کاراکتر و شامل حروف انگلیسی، عدد یا _ باشد.";
    }

    return '';
}


function validateEmail($email)
{
    if (empty($email)) {
        return "وارد کردن ایمیل الزامی است.";
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return "فرمت ایمیل صحیح نیست.";
    }

    return '';
}


function validatePassword($password)
{
    if (empty($password)) {
        return "وارد کردن رمز عبور الزامی است.";
    }

    if (!preg_match('/^(?=.*[A-Z])(?=.*[a-z])(?=.*\d)(?=.*[@#$!%*?&]).{8,}$/', $password)) {
        return "رمز عبور باید حداقل ۸ کاراکتر و شامل حروف انگلیسی بزرگ و کوچک، حداقل یک عدد و یک کاراکتر خاص مانند @، #، $ یا ! باشد.";
    }

    return '';
}


function validateLoginPassword($password)
{
    if (empty($password)) {
        return "لطفا رمز عبور خود را وارد کنید. ";
    }

    return '';
}


/**
 * Validate a person's display name. Names are NOT unique.
 * Unicode letters/marks, spaces, apostrophes, hyphens and Persian half-space
 * are accepted. No mbstring dependency is required.
 */
function validatePersonName($name, $label)
{
    if (!is_string($name) || trim($name) === '') {
        return 'وارد کردن ' . $label . ' الزامی است.';
    }

    // utf8mb4 VARCHAR(100): maximum 100 Unicode code points / 400 bytes.
    if (strlen($name) > 400
        || !preg_match('/\A[\p{L}\p{M}\p{Zs}\x{200C}\x{0027}\x{2019}\-]+\z/u', $name)
        || !preg_match('/\p{L}/u', $name)) {
        return $label . ' فقط می‌تواند شامل حروف، فاصله، نیم‌فاصله، خط تیره یا آپاستروف باشد.';
    }

    $length = preg_match_all('/./us', $name);
    if ($length === false || $length > 100) {
        return $label . ' نباید بیشتر از ۱۰۰ کاراکتر باشد.';
    }

    return '';
}

/**
 * بررسی ورودی پروفایل بدون هیچ کوئری دیتابیس.
 * از همان اعتبارسنج‌های ثبت‌نام استفاده می‌شود.
 */
function validateProfileUpdate($request, $questions)
{
    if (!is_array($request)) {
        return profileValidationError('اطلاعات فرم نامعتبر است.');
    }

    $fields = array('first_name', 'last_name', 'username', 'email');
    $data = array();
    foreach ($fields as $field) {
        if (!isset($request[$field]) || !is_string($request[$field])) {
            return profileValidationError('اطلاعات فرم نامعتبر است.');
        }
        $data[$field] = trim($request[$field]);
    }

    $errors = array(
        validatePersonName($data['first_name'], 'نام'),
        validatePersonName($data['last_name'], 'نام خانوادگی'),
        strlen($data['username']) > 100 ? 'نام کاربری طولانی است.' : validateUsername($data['username']),
        strlen($data['email']) > 191 ? 'ایمیل طولانی است.' : validateEmail($data['email'])
    );
    foreach ($errors as $message) {
        if ($message !== '') {
            return profileValidationError($message);
        }
    }

    $security = array();
    foreach (array('new_password', 'confirm_password', 'current_password', 'question_id', 'recovery_answer') as $field) {
        $value = array_key_exists($field, $request) ? $request[$field] : null;
        if ($value !== null && !is_string($value)) {
            return profileValidationError('فیلد امنیتی نامعتبر است.');
        }
        $security[$field] = $value;
    }

    $password_change = $security['new_password'] !== null && $security['confirm_password'] !== null;
    $recovery_change = $security['question_id'] !== null && $security['recovery_answer'] !== null;

    if ($password_change) {
        $password = $security['new_password'];
        if (!is_string($password) || strlen($password) > 1024 || validatePassword($password) !== '') {
            return profileValidationError('رمز جدید الزامات امنیتی را ندارد.');
        }
        if ($password !== $security['confirm_password']) {
            return profileValidationError('تکرار رمز جدید مطابقت ندارد.');
        }
    }

    if ($recovery_change) {
        $question = $security['question_id'];
        $answer = $security['recovery_answer'];
        if (!is_string($question) || !ctype_digit($question)
            || !is_array($questions) || !isset($questions[(int) $question])
            || !is_string($answer) || strlen(trim($answer)) < 6 || strlen($answer) > 256) {
            return profileValidationError('سؤال یا پاسخ بازیابی معتبر نیست.');
        }
    }

    if ($password_change || $recovery_change) {
        $current = $security['current_password'];
        if (!is_string($current) || $current === '' || strlen($current) > 1024) {
            return profileValidationError('برای تغییر اطلاعات امنیتی، رمز فعلی را وارد کنید.');
        }
    }

    return array(
        'success' => true,
        'data' => $data,
        'security' => $security,
        'password_change' => $password_change,
        'recovery_change' => $recovery_change
    );
}

function profileValidationError($message)
{
    return array('success' => false, 'message' => $message, 'status' => 422);
}
