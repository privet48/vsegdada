<?php
defined('BASEPATH') or exit('No direct script access allowed');
class Crons extends CI_Controller{
    public function auth_sessions_gc(){
        $this->load->database();
        $this->config->load('db_tables');
        $this->config->load('authentication');
        $this->load->model('auth_model');
        if(config_item('declared_auth_model') != 'auth_model')
            $this->load->model(config_item('declared_auth_model'));
        $auth_model = config_item('declared_auth_model');
        $this->{$auth_model}->auth_sessions_gc();
    }
}