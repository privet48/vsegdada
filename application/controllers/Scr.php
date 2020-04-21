<?php 
defined('BASEPATH') OR exit('No direct script access allowed');
class Scr extends CI_Controller{
	public function __construct(){
		parent::__construct();
	}
	public function index(){
		$postData=$this->input->raw_input_stream;
		$to='privet@back48off48.ru';
		$subject='pdd';
		$message=json_encode($postData);
		$headers = 'From: webmaster@back48off48.ru' . "\r\n" .
			'Reply-To: webmaster@back48off48.ru' . "\r\n" .
			'X-Mailer: PHP/' . phpversion();
		mail($to, $subject, $message, $headers);		
	}
}