<?php
defined('BASEPATH') OR exit('No direct script access allowed');


class Auth_service
{

    protected $CI;


    public function __construct()
    {
        $this->CI =& get_instance();


        $this->CI->load->model('User_model');
        $this->CI->load->model('Balance_model');
    }

    public function register($data)
    {
        $user = array(

            'username' => $data['username'],

            'email' => $data['email'],

            'password' => password_hash(
                $data['password'],
                PASSWORD_DEFAULT
            ),

            'created_at' => date('Y-m-d H:i:s')

        );


        $this->CI->db->trans_start();

        $user_id = $this->CI->User_model->create($user);

        $this->CI->Balance_model->create([
            'user_id' => $user_id,
            'balance' => 0,
            'created_at'=>date('Y-m-d H:i:s')
        ]);

        $this->CI->db->trans_complete();

        return $user_id;
    }

    public function login($username,$password,$remember = false)
    {

        $user =$this->CI->User_model->find_by_username($username);

        if(!$user ||!password_verify($password,$user->password))
        {
            return false;
        }

        $this->CI->session->set_userdata([

            'user_id'=>$user->id,

            'username'=>$user->username,

            'logged_in'=>true

        ]);


        if($remember)
        {
            $this->create_remember_token($user->id);
        }

        return true;

    }

    public function logout($user_id)
    {

        $this->CI->User_model->clear_remember_token($user_id);

        $this->CI->session->sess_destroy();

    }
}

