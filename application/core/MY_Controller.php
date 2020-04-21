<?php
defined('BASEPATH') OR exit('No direct script access allowed');
require_once APPPATH . 'core/Auth_Controller.php';
class MY_Controller extends Auth_Controller{
	public function __construct(){
		parent::__construct();
	}
}