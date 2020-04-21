<?php
defined('BASEPATH')||exit('No direct script access allowed');
class MY_Model extends CI_Model{
	public $acl=NULL;
	public function __construct(){
		parent::__construct();
	}
	public function acl_query($user_id, $called_during_auth=false){
		$query=$this->db->select('b.action_id, b.action_code, c.category_code')
			->from($this->db_table('acl_table') . ' a')
			->join($this->db_table('acl_actions_table') . ' b', 'a.action_id=b.action_id')
			->join($this->db_table('acl_categories_table') . ' c', 'b.category_id=c.category_id')
			->where('a.user_id', $user_id)
			->get();
		$acl=[];
		if($query->num_rows() > 0){
			foreach($query->result() as $row){
				$acl[$row->action_id]=$row->category_code . '.' . $row->action_code;
			}
		}
		if($called_during_auth||$user_id == config_item('auth_user_id'))
			$this->acl=$acl;
		return $acl;
	}
	public function acl_permits($str){
		list($category_code, $action_code)=explode('.', $str);
		if(strlen($category_code) < 1||strlen($action_code) < 1)
			return false;
		if(is_null($this->acl)){
			if($this->acl=$this->acl_query(config_item('auth_user_id'))){
				$this->load->vars(['acl' => $this->acl]);
				$this->config->set_item('acl', $this->acl);
			}
		}
		if(in_array($category_code . '.*', $this->acl)|| in_array($category_code . '.all', $this->acl)||in_array($category_code . '.' . $action_code, $this->acl)){
			return true;
		}
		return false;
	}
	public function is_role($role=''){
		$auth_role=config_item('auth_role');
		if($role!=''&&!empty($auth_role)){
			$role_array=explode(',', $role);
			if(in_array($auth_role, $role_array)){
				return true;
			}
		}
		return false;
	}
	public function db_table($name){
		$name=config_item($name);
		return $this->db->dbprefix($name);
	}
}