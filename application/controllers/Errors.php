<?php
defined('BASEPATH') or exit('No direct script access allowed');
class Errors extends CI_Controller{
    public function __construct(){
        parent::__construct();
		$this->load->helper([
			'assets'
		]);		
    }
	public function error_404(){
		$this->load->helper(['url','assets_helper']);
		$this->lang->load('errors','ru');
		$this->output->set_status_header(404);
		$this->layout->viewFolder='';
		$this->layout->data=['message' => $this->lang->line('The page you requested was not found'),'code' => 404];
		$this->layout->layout='errors';
		$this->layout->view='/errors/error_404';
		$this->layout->render();		
	}
}