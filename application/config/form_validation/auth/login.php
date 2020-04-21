<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$CI =& get_instance();
$CI->load->model('auth/validation_callables');
$config['login_rules'] = [
	[
		'field' => 'login_string',
		'label' => 'USERNAME OR EMAIL ADDRESS',
		'rules' => 'trim|required|max_length[255]'
	],
	[
		'field' => 'login_pass',
		'label' => 'PASSWORD',
		'rules' => [
            'trim',
            'required',
            [ 
                '_check_password_strength', 
                [ $CI->validation_callables, '_check_password_strength' ] 
            ]
        ]
	]
];