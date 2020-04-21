<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$autoload['packages'] = array();
$autoload['libraries'] = array('session','layout','database','authentication');
$autoload['drivers'] = array();
$autoload['helper'] = array('assets','auth','url','jwt','authorization','html');
$autoload['config'] = array('authentication','jwt');
$autoload['language'] = array();
$autoload['model'] = array();