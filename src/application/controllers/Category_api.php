<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH . 'services/Category_service.php';

class Category_api extends CI_Controller
{
    private $category_service;

    public function __construct()
    {
        parent::__construct();

        $this->category_service = new Category_service();

        $this->load->library('session');
    }


    public function index()
    {
        $user_id = $this->session->userdata('user_id');

        $categories = $this->category_service->get_all($user_id);

        if ($categories === false) {
            echo json_encode(array(
                'success' => false,
                'message' => 'دریافت دسته‌بندی‌ها انجام نشد.'
            ));
            return;
        }

        echo json_encode(array(
            'success' => true,
            'categories' => $categories
        ));
    }

    public function deleted()
    {
        $user_id = $this->session->userdata('user_id');
        if (!$user_id) {
            return $this->respond_deleted(false, 'ابتدا وارد حساب کاربری شوید.', 401);
        }

        $categories = $this->category_service->get_deleted($user_id);
        if ($categories === false) {
            return $this->respond_deleted(false, 'دریافت دسته‌بندی‌های حذف‌شده انجام نشد.', 422);
        }

        foreach ($categories as $category) {
            $category->restore_available = !empty($category->restore_until) && strtotime($category->restore_until) > time();
        }

        return $this->respond_deleted(true, 'فهرست دسته‌بندی‌های حذف‌شده دریافت شد.', 200, array('categories' => $categories));
    }

    public function store()
    {
        $user_id = $this->session->userdata('user_id');
        if (!$user_id) {
            $this->output->set_status_header(401)->set_content_type('application/json', 'utf-8');
            echo json_encode(array(
                'success' => false,
                'message' => 'ابتدا وارد حساب کاربری شوید.'
            ), JSON_UNESCAPED_UNICODE);
            return;
        }

        // Thunder Client commonly sends JSON, which CI3's input->post() does
        // not parse. Keep form submissions working and accept JSON as well.
        $data = array(
            'name' => $this->input->post('name'),
            'type' => $this->input->post('type')
        );

        if ($data['name'] === null && $data['type'] === null) {
            $json = json_decode($this->input->raw_input_stream, true);
            if (is_array($json)) {
                $data = array(
                    'name' => $json['name'] ?? null,
                    'type' => $json['type'] ?? null
                );
            }
        }

        $category_id = $this->category_service->create($user_id, $data);

        if ($category_id === false) {
            $this->output->set_status_header(422)->set_content_type('application/json', 'utf-8');
            echo json_encode(array(
                'success' => false,
                'message' => 'ایجاد دسته‌بندی انجام نشد.'
            ), JSON_UNESCAPED_UNICODE);
            return;
        }

        $this->output->set_content_type('application/json', 'utf-8');
        echo json_encode(array(
            'success' => true,
            'message' => 'دسته‌بندی با موفقیت ایجاد شد.',
            'category_id' => $category_id
        ), JSON_UNESCAPED_UNICODE);
    }

    public function update($category_id)
    {
        $user_id = $this->session->userdata('user_id');

        if (!$user_id) {
            $this->output->set_status_header(401)->set_content_type('application/json', 'utf-8');
            echo json_encode(array(
                'success' => false,
                'message' => 'ابتدا وارد حساب کاربری شوید.'
            ), JSON_UNESCAPED_UNICODE);
            return;
        }

        $data = array(
            'name' => $this->input->post('name'),
            'type' => $this->input->post('type')
        );

        $result = $this->category_service->update(
            $user_id,
            $category_id,
            $data
        );

        if (!$result) {
            $this->output->set_status_header(422)->set_content_type('application/json', 'utf-8');
            echo json_encode(array(
                'success' => false,
                'message' => 'ویرایش دسته‌بندی انجام نشد.'
            ), JSON_UNESCAPED_UNICODE);
            return;
        }

        $this->output->set_content_type('application/json', 'utf-8');
        echo json_encode(array(
            'success' => true,
            'message' => 'دسته‌بندی با موفقیت ویرایش شد.'
        ), JSON_UNESCAPED_UNICODE);
    }

    public function delete($category_id)
    {
        $user_id = $this->session->userdata('user_id');

        $result = $this->category_service->delete(
            $user_id,
            $category_id
        );

        if (!$result) {
            echo json_encode(array(
                'success' => false,
                'message' => 'حذف دسته‌بندی انجام نشد.'
            ));
            return;
        }

        echo json_encode(array(
            'success' => true,
            'message' => 'دسته‌بندی با موفقیت حذف شد.'
        ));
    }

    public function restore($category_id)
    {
        $user_id = $this->session->userdata('user_id');
        if (!$user_id) {
            $this->output->set_status_header(401)->set_content_type('application/json', 'utf-8')
                ->set_output(json_encode(array('success' => false, 'message' => 'ابتدا وارد حساب کاربری شوید.'), JSON_UNESCAPED_UNICODE));
            return;
        }

        $result = $this->category_service->restore(
            $user_id,
            $category_id
        );

        if (!$result) {
            echo json_encode(array(
                'success' => false,
                'message' => 'بازگردانی دسته‌بندی انجام نشد.'
            ));
            return;
        }

        echo json_encode(array(
            'success' => true,
            'message' => 'دسته‌بندی با موفقیت بازگردانی شد.'
        ));
    }

    private function respond_deleted($success, $message, $status, $data = array())
    {
        $this->output->set_status_header($status)->set_content_type('application/json', 'utf-8')
            ->set_output(json_encode(array_merge(array('success' => $success, 'message' => $message), $data), JSON_UNESCAPED_UNICODE));
    }
}
