
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
