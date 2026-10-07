<?php
defined('BASEPATH') OR exit('No direct script access allowed');


class Category_service
{

    protected $CI;


    public function __construct()
    {
        $this->CI =& get_instance();


        $this->CI->load->model('Category_model');

        $this->CI->load->model('Transaction_model');

    }


    public function create($user_id, $data)
    {

        if ($this->check_duplicate_name($user_id,$data['name'])) {
            return false;
        }

        $data['user_id'] = $user_id;

        $data['created_at'] =date('Y-m-d H:i:s');

        return $this->CI->Category_model->create($data);

    }



   
    public function update($user_id,$category_id,$data)
    {

        $category =$this->check_owner($user_id,$category_id);

        if (!$category) {
            return false;
        }



        if (isset($data['name'])) {
            if ($this->check_duplicate_name($user_id,$data['name'],$category_id)) {
                return false;
            }
        }


        $data['updated_at'] =date('Y-m-d H:i:s');


        return $this->CI->Category_model->update($category_id,$data); 

    }




    
    public function delete(
        $user_id,
        $category_id
    )
    {

        $category =
            $this->check_owner(
                $user_id,
                $category_id
            );


        if (!$category) {
            return false;
        }



        $restore_until =
            date(
                'Y-m-d H:i:s',
                strtotime('+30 days')
            );



        $this->CI->db->trans_start();



        // حذف Category

        $this->CI
             ->Category_model
             ->soft_delete(
                 $category_id,
                 $restore_until
             );



        // پیدا کردن Transactionها

        $transactions =
            $this->CI
                 ->Transaction_model
                 ->find_by_category_id(
                     $category_id
                 );



        foreach ($transactions as $transaction)
        {

            $this->CI
                 ->Transaction_model
                 ->soft_delete(
                     $transaction->id,
                     $restore_until
                 );

        }



        $this->CI->db->trans_complete();



        return true;

    }




    /**
     * Restore Category
     *
     * Transactionها Restore نمی‌شوند
     */
    public function restore(
        $user_id,
        $category_id
    )
    {

        $category =
            $this->check_owner(
                $user_id,
                $category_id
            );


        if (!$category) {
            return false;
        }



        if (
            strtotime($category->restore_until)
            < time()
        ) {
            return false;
        }



        return $this->CI
                    ->Category_model
                    ->restore(
                        $category_id
                    );

    }





    /**
     * بررسی مالکیت Category
     */
    private function check_owner(
        $user_id,
        $category_id
    )
    {

        $category =
            $this->CI
                 ->Category_model
                 ->find_by_id(
                     $category_id
                 );


        if (
            !$category ||
            $category->user_id != $user_id
        ) {
            return false;
        }



        return $category;

    }





    /**
     * بررسی نام تکراری Category
     *
     * برای Restore Window
     */
    private function check_duplicate_name(
        $user_id,
        $name,
        $ignore_id = null
    )
    {

        $category =
            $this->CI
                 ->Category_model
                 ->find_by_name(
                     $user_id,
                     $name
                 );



        if (!$category) {
            return false;
        }



        if (
            $ignore_id &&
            $category->id == $ignore_id
        ) {
            return false;
        }



        /*
         اگر Category حذف شده باشد،
         ولی restore_until تمام نشده باشد،
         هنوز اجازه ساخت مجدد نداریم
        */

        if (
            $category->deleted_at &&
            strtotime($category->restore_until) > time()
        ) {
            return true;
        }



        if (!$category->deleted_at) {
            return true;
        }



        return false;

    }


}