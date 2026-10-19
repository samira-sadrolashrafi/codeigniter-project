<?php

class BaseApiController extends CI_Controller
{
    protected $user_id;

    public function __construct($require_authentication = true)
    {
        parent::__construct();
        $this->load->library('session');
        $this->load->helper('session');

        // Read the authenticated user once per request and reuse it in actions.
        $this->user_id = $this->session->userdata('user_id');

        if ($require_authentication) {
            $this->checkAuth();
        }
    }

    protected function current_user_id()
    {
        return $this->user_id;
    }

    private function checkAuth()
    {
        if (!$this->user_id) {
            $this->respond_json(false, 'ابتدا وارد حساب کاربری شوید.', 401);
            $this->output->_display();
            exit;
        }
    }

    protected function require_authenticated_user()
    {
        if (!$this->user_id) {
            $this->respond_json(false, 'ابتدا وارد حساب کاربری شوید.', 401);
            return false;
        }

        return true;
    }

    protected function respond_json($success, $message, $status_code = 200, $data = array())
    {
        $response = array(
            'success' => $success,
            'message' => $message
        );

        if (!empty($data)) {
            $response = array_merge($response, $data);
        }

        $this->output
            ->set_status_header($status_code)
            ->set_content_type('application/json', 'utf-8')
            ->set_output(json_encode($response, JSON_UNESCAPED_UNICODE));
    }

    /**
     * پاسخ خطای یک عملیات بر اساس آخرین علت ثبت‌شده در سرویس.
     *
     * سرویس‌ها هنگام شکست، پیام و وضعیت HTTP علت واقعی را در
     * get_last_error() قرار می‌دهند. اگر سرویس دلیلی ثبت نکرده
     * باشد، پیام عمومی محترمانه برگردانده می‌شود تا جزئیات فنی
     * یا خطاهای ناشناخته به کاربر نمایش داده نشود.
     */
    protected function respond_service_error(
        $service,
        $fallback_message,
        $fallback_status = 500
    ) {
        $error = is_object($service) &&
            method_exists($service, 'get_last_error')
                ? $service->get_last_error()
                : null;

        $message = is_array($error) && !empty($error['message'])
            ? $error['message']
            : $fallback_message;

        $status = is_array($error) && isset($error['status'])
            ? (int) $error['status']
            : $fallback_status;

        if ($error === null) {
            log_message(
                'error',
                'Service failure without a recorded reason: ' . $fallback_message
            );
        }

        return $this->respond_json(false, $message, $status);
    }
}
