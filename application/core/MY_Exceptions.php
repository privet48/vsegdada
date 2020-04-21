<?php
defined('BASEPATH') OR exit('No direct script access allowed');
class MY_Exceptions extends CI_Exceptions{
    public function show_404($page='',$log_error=true){
        if(is_cli()){
            $heading='Not Found';
            $message='The controller/method pair you requested was not found.';
            echo $this->show_error($heading, $message, 'error_404', 404);
        }else{
            $CI=&get_instance();
			$CI->load->helper(['url','assets_helper']);
			$CI->lang->load('errors','ru');
			$CI->layout->viewFolder='';
			$CI->layout->data=['message' => $CI->lang->line('The page you requested was not found'),'code' => 404];
			$CI->layout->layout='errors';
			$CI->layout->view='/errors/error_404';
            $CI->layout->render();
            echo $CI->output->get_output();
        }
        exit(4);
    }
}