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
}
