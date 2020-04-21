<?php 
defined('BASEPATH') OR exit('No direct script access allowed');
class Key_creator extends CI_Controller{
	public function __construct(){
		parent::__construct();
		if( ! empty( config_item('encryption_key') ) )
			show_404();
	}
	public function index(){
		$this->load->helper('url');
		$options = [
			'16?cipher='  . urlencode('AES-128 / Rijndael-128')     => 'AES-128 / Rijndael-128 (CodeIgniter Default)',
			'24?cipher='  . urlencode('AES-192')                    => 'AES-192',
			'32?cipher='  . urlencode('AES-256')                    => 'AES-256',
			'7?cipher='   . urlencode('DES')                        => 'DES',
			'7?cipher='   . urlencode('TripleDES (56 bit)')         => 'TripleDES (56 bit)',
			'14?cipher='  . urlencode('TripleDES (112 bit)')        => 'TripleDES (112 bit)',
			'21?cipher='  . urlencode('TripleDES (168 bit)')        => 'TripleDES (168 bit)',
			'16?cipher='  . urlencode('Blowfish (128 bit)')         => 'Blowfish (128 bit)',
			'32?cipher='  . urlencode('Blowfish (256 bit)')         => 'Blowfish (256 bit)',
			'48?cipher='  . urlencode('Blowfish (384 bit)')         => 'Blowfish (384 bit)',
			'56?cipher='  . urlencode('Blowfish (448 bit)')         => 'Blowfish (448 bit)',
			'11?cipher='  . urlencode('CAST5 / CAST-128 (88 bit)')  => 'CAST5 / CAST-128 (88 bit)',
			'16?cipher='  . urlencode('CAST5 / CAST-128 (128 bit)') => 'CAST5 / CAST-128 (128 bit)',
			'5?cipher='   . urlencode('RC4 / ARCFour (40 bit)')     => 'RC4 / ARCFour (40 bit)',
			'256?cipher=' . urlencode('RC4 / ARCFour (2048 bit)')   => 'RC4 / ARCFour (2048 bit)'
		];
		echo '<ul>';
		foreach( $options as $k => $v ){
			echo '<li>' . anchor( 'key_creator/create/' . $k, $v ) . '</li>';
		}
		echo '</ul>';
	}
	public function create( $length = 16 ){
		$this->load->library('encryption');
		$cipher = $this->input->get('cipher')
			? urldecode( $this->input->get('cipher') )
			: $length . ' byte key';
		$key = bin2hex( $this->encryption->create_key( $length ) );
		echo '// ' . $cipher . '<br /> 
		$config[\'encryption_key\'] = hex2bin(\'' . $key . '\');';
	}
}