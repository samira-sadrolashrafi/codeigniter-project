<?php

class BaseApiController extends CI_Controller
{
    protected $user_id;

    public function __construct($require_authentication = true)
    {
        parent::__construct();
        $this->load->library('session');
        $this->load->helper('session');

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
        if (!$this->has_active_authenticated_user()) {
            $this->respond_json(false, 'ابتدا وارد حساب کاربری شوید.', 401);
            $this->output->_display();
            exit;
        }
    }

    protected function require_authenticated_user()
    {
        if (!$this->has_active_authenticated_user()) {
            $this->respond_json(false, 'ابتدا وارد حساب کاربری شوید.', 401);
            return false;
        }

        return true;
    }

    private function has_active_authenticated_user()
    {
        if (!$this->user_id || !is_numeric($this->user_id)) {
            return false;
        }

        $this->load->model('User_model');

        $user = $this->User_model->find_active_by_id((int) $this->user_id);

        if ($user) {
            return true;
        }

        clearRememberCookie();
        $this->session->sess_destroy();
        $this->user_id = null;

        return false;
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

    protected function respond_service_error(
        $service,
        $fallback_message,
        $fallback_status = 500
    ) {
        $error = $service->get_last_error();

        return $this->respond_json(
            false,
            $error['message'] ?? $fallback_message,
            $error['status'] ?? $fallback_status
        );
    }
}