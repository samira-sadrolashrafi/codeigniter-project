<?php
defined('BASEPATH') OR exit('No direct script access allowed');


class Balance_service
{

    protected $CI;

    public function __construct()
    {
        $this->CI =& get_instance();


        $this->CI->load->model('Balance_model');
    }

    public function create_initial_balance($user_id)
    {

        $data = array(

            'user_id' => $user_id,

            'balance' => 0,

            'created_at' => date('Y-m-d H:i:s')

        );

        return $this->CI->Balance_model->create($data);

    }


    public function increase($user_id, $amount)
    {

        $balance = $this->CI->Balance_model->find_by_user_id($user_id);

        if (!$balance) {
            return false;
        }

        $new_balance =$balance->balance + $amount;

        return $this->CI->Balance_model->update_balance($user_id,$new_balance);
    }


    public function decrease($user_id, $amount)
    {

        $balance = $this->CI->Balance_model->find_by_user_id($user_id);

        if (!$balance) {
            return false;
        }

        $new_balance =$balance->balance - $amount;

        return $this->CI->Balance_model->update_balance($user_id,$new_balance);

    }


    public function apply_transaction_effect($user_id,$type,$amount)
    {

        if ($type === 'income') {

            return $this->increase($user_id,$amount);

        }

        if ($type === 'expense') {

            return $this->decrease($user_id,$amount);

        }

        return false;

    }


    public function reverse_transaction_effect($user_id,$type,$amount)
    {

        if ($type === 'income') {

            return $this->decrease($user_id,$amount);

        }

        if ($type === 'expense') {

            return $this->increase($user_id,$amount);

        }

        return false;
    }

    
    public function update_manual_balance($user_id,$new_balance)
    {
        return $this->CI->Balance_model->update_balance($user_id,$new_balance);
    }

}