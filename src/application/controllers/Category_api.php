<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH . 'services/Category_service.php';
require_once APPPATH . 'controllers/BaseApiController.php';

class Category_api extends BaseApiController
{
    private $category_service;

    public function __construct()
    {
        parent::__construct();
        $this->category_service = new Category_service();
    }

    public function index()
    {
        if (!$this->require_authenticated_user()) {
            return;
        }

        $categories = $this->category_service->get_all($this->user_id);
        if ($categories === false) {
            return $this->respond_json(false, 'دریافت دسته‌بندی‌ها انجام نشد.', 422);
        }

        return $this->respond_json(true, 'فهرست دسته‌بندی‌ها دریافت شد.', 200, array(
            'categories' => $categories
        ));
    }

    public function deleted()
    {
        if (!$this->require_authenticated_user()) {
            return;
        }

        $categories = $this->category_service->get_deleted($this->user_id);
        if ($categories === false) {
            return $this->respond_json(false, 'دریافت دسته‌بندی‌های حذف‌شده انجام نشد.', 422);
        }

        foreach ($categories as $category) {
            $category->restore_available = !empty($category->restore_until)
                && strtotime($category->restore_until) > time();
        }

        return $this->respond_json(true, 'فهرست دسته‌بندی‌های حذف‌شده دریافت شد.', 200, array(
            'categories' => $categories
        ));
    }

    public function store()
    {
        if (!$this->require_authenticated_user()) {
            return;
        }

        $data = $this->category_input_data(true);

        $category_id = $this->category_service->create($this->user_id, $data);
        if ($category_id === false) {
            return $this->respond_json(false, 'ایجاد دسته‌بندی انجام نشد.', 422);
        }

        return $this->respond_json(true, 'دسته‌بندی با موفقیت ایجاد شد.', 200, array(
            'category_id' => $category_id
        ));
    }

    public function update($category_id)
    {
        if (!$this->require_authenticated_user()) {
            return;
        }

        $data = $this->category_input_data();

        if (!$this->category_service->update($this->user_id, $category_id, $data)) {
            return $this->respond_json(false, 'ویرایش دسته‌بندی انجام نشد.', 422);
        }

        return $this->respond_json(true, 'دسته‌بندی با موفقیت ویرایش شد.');
    }

    public function delete($category_id)
    {
        if (!$this->require_authenticated_user()) {
            return;
        }

        if (!$this->category_service->delete($this->user_id, $category_id)) {
            return $this->respond_json(false, 'حذف دسته‌بندی انجام نشد.', 422);
        }

        return $this->respond_json(true, 'دسته‌بندی با موفقیت حذف شد.');
    }

    public function restore($category_id)
    {
        if (!$this->require_authenticated_user()) {
            return;
        }

        if (!$this->category_service->restore($this->user_id, $category_id)) {
            return $this->respond_json(false, 'بازگردانی دسته‌بندی انجام نشد.', 422);
        }

        return $this->respond_json(true, 'دسته‌بندی با موفقیت بازگردانی شد.');
    }

    private function category_input_data($allow_json = false)
    {
        $data = array(
            'name' => $this->input->post('name'),
            'type' => $this->input->post('type')
        );

        if ($allow_json && $data['name'] === null && $data['type'] === null) {
            $json = json_decode($this->input->raw_input_stream, true);
            if (is_array($json)) {
                $data = array(
                    'name' => $json['name'] ?? null,
                    'type' => $json['type'] ?? null
                );
            }
        }

        return $data;
    }
}
