<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** منطق پروفایل؛ تمام کوئری‌های اختصاصی در Profile_model هستند. */
class Profile_service
{
    private $CI;

    public function __construct()
    {
        $this->CI =& get_instance();
        $this->CI->load->helper(array('validation', 'session'));
        $this->CI->load->model(array('Profile_model', 'Recovery_throttle_model'));
        $this->CI->load->config('recovery_questions');
    }

    public function update($user_id, $request, $files, $ip_address)
    {
        $user = $this->CI->Profile_model->find_active_user($user_id);
        if (!$user) {
            return $this->error('حساب کاربری فعال پیدا نشد.', 401);
        }

        $validated = validateProfileUpdate(
            $request,
            $this->CI->config->item('recovery_questions')
        );
        if (!$validated['success']) {
            return $validated;
        }

        $data = $validated['data'];
        if ($this->CI->Profile_model->username_taken($data['username'], $user_id)) {
            return $this->error('نام کاربری قبلاً استفاده شده است.', 422);
        }
        if ($this->CI->Profile_model->email_taken($data['email'], $user_id)) {
            return $this->error('ایمیل قبلاً استفاده شده است.', 422);
        }

        $password_change = $validated['password_change'];
        $recovery_change = $validated['recovery_change'];
        $security = $validated['security'];

        if ($password_change || $recovery_change) {
            $allowed = $this->CI->Recovery_throttle_model->allow(
                $user->username, $ip_address, 'profile', 5, 25
            );
            if (!$allowed) {
                return $this->error('تعداد تلاش‌ها زیاد است. ۱۵ دقیقه بعد تلاش کنید.', 429);
            }
        }

        $remove_avatar = isset($request['remove_avatar']) && $request['remove_avatar'] === '1';
        $upload = isset($files['avatar']) ? $files['avatar'] : null;
        $has_upload = is_array($upload) && isset($upload['error']) &&
            $upload['error'] !== UPLOAD_ERR_NO_FILE;

        if ($remove_avatar && $has_upload) {
            return $this->error('حذف و انتخاب عکس جدید هم‌زمان مجاز نیست.', 422);
        }
        $new_avatar = $has_upload ? $this->save_avatar($upload) : null;
        if ($new_avatar === false) {
            return $this->error(
                'عکس نامعتبر است؛ JPG، PNG یا WebP با حداکثر ۲ مگابایت انتخاب کنید.', 422
            );
        }

        $db = $this->CI->db;
        $old_debug = $db->db_debug;
        $db->db_debug = false;
        $db->trans_begin();

        try {
            $locked_user = $this->CI->Profile_model->lock_active_user($user_id);
            if (!$locked_user || (($password_change || $recovery_change) &&
                !password_verify($security['current_password'], $locked_user->password))) {
                return $this->rollback($new_avatar,
                    'رمز فعلی اشتباه است یا حساب در دسترس نیست.', 401);
            }

            $changes = $data;
            $changes['updated_at'] = date('Y-m-d H:i:s');
            if ($new_avatar !== null) {
                $changes['avatar'] = $new_avatar;
            } elseif ($remove_avatar) {
                $changes['avatar'] = null;
            }

            if ($password_change) {
                $hash = password_hash($security['new_password'], PASSWORD_DEFAULT);
                if ($hash === false) {
                    return $this->rollback($new_avatar, 'ساخت هش رمز جدید انجام نشد.', 500);
                }
                $changes['password'] = $hash;
                $changes['remember_token'] = null;
                $changes['remember_token_expires_at'] = null;
            }

            $saved = $this->CI->Profile_model->update_active_user($user_id, $changes);
            if ($saved && $recovery_change) {
                $saved = $this->CI->Profile_model->save_recovery_question(
                    $user_id, (int) $security['question_id'], $security['recovery_answer']
                );
            }

            if (!$saved || $db->trans_status() === false) {
                return $this->rollback($new_avatar,
                    'ذخیره پروفایل انجام نشد. اطلاعات را بررسی و دوباره تلاش کنید.', 409);
            }
            if (!$db->trans_commit()) {
                $this->remove_avatar($new_avatar);
                return $this->error('ذخیره پروفایل انجام نشد.', 409);
            }
        } catch (Throwable $exception) {
            return $this->rollback($new_avatar, 'خطا در ذخیره پروفایل.', 500);
        } finally {
            $db->db_debug = $old_debug;
        }

        if ($password_change) {
            clearRememberCookie();
            $this->CI->session->sess_regenerate(true);
        }

        $avatar = $new_avatar !== null ? $new_avatar :
            ($remove_avatar ? null : $locked_user->avatar);
        $this->CI->session->set_userdata(array(
            'username' => $changes['username'],
            'avatar' => $avatar
        ));
        if (($new_avatar !== null || $remove_avatar) && $locked_user->avatar !== $avatar) {
            $this->remove_avatar($locked_user->avatar);
        }

        return array(
            'success' => true, 'status' => 200,
            'message' => 'تغییرات پروفایل ذخیره شد.',
            'profile' => array(
                'username' => $changes['username'],
                'avatar_url' => $avatar ? base_url($avatar) : ''
            )
        );
    }

    private function rollback($new_avatar, $message, $status)
    {
        $this->CI->db->trans_rollback();
        $this->remove_avatar($new_avatar);
        return $this->error($message, $status);
    }

    private function error($message, $status)
    {
        return array('success' => false, 'message' => $message, 'status' => $status);
    }

    /** آواتار فقط در همین سرویس بررسی و ذخیره می‌شود. */
    private function save_avatar($file)
    {
        if (!is_array($file) || !isset($file['error'], $file['size'], $file['tmp_name']) ||
            $file['error'] !== UPLOAD_ERR_OK || !is_int($file['size']) ||
            $file['size'] < 1 || $file['size'] > 2 * 1024 * 1024 ||
            !is_string($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            return false;
        }

        $info = @getimagesize($file['tmp_name']);
        if (!$info || !isset($info[0], $info[1], $info['mime']) ||
            $info[0] > 6000 || $info[1] > 6000) {
            return false;
        }

        $extensions = array(
            'image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'
        );
        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
        if (!isset($extensions[$mime]) || $info['mime'] !== $mime) {
            return false;
        }

        $directory = FCPATH . 'uploads/avatars/';
        if (!is_dir($directory) && !@mkdir($directory, 0755, true)) {
            return false;
        }
        $filename = bin2hex(random_bytes(16)) . '.' . $extensions[$mime];
        if (!move_uploaded_file($file['tmp_name'], $directory . $filename)) {
            return false;
        }
        @chmod($directory . $filename, 0644);
        return 'uploads/avatars/' . $filename;
    }

    private function remove_avatar($path)
    {
        if (!is_string($path) ||
            !preg_match('#\Auploads/avatars/[a-f0-9]{32}\.(?:jpg|png|webp)\z#', $path)) {
            return;
        }
        $file = FCPATH . $path;
        if (is_file($file)) {
            @unlink($file);
        }
    }
}
