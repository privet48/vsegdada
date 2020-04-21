<?php
defined('BASEPATH')||exit('No direct script access allowed');
class Auth_model extends MY_Model{
	public function get_auth_data($user_string){
		$selected_columns=[
			'username',
			'userlastname',
			'usermiddlename',
			'passport_id',
			'birth_date',
			'sex',
			'birth_place',
			'passport_issue_date',
			'passport_subdivision_code',
			'passport_issuing_authority',
			'email',
			'phone',
			'auth_level',
			'passwd',
			'user_id',
			'banned',
			'current_step'
		];
		$query=$this->db->select($selected_columns)
			->from($this->db_table('user_table'))
			->where('phone =',strtolower($user_string))
			->or_where('LOWER(email) =',strtolower($user_string))
			->limit(1)
			->get();
		if($query->num_rows()==1){
			$row=$query->row_array();
			$acl=$this->add_acl_to_auth_data($row['user_id']);
			return (object) array_merge($row,$acl);
		}
		return false;
	}
	public function login_update($user_id,$login_time,$session_id){
		if(config_item('disallow_multiple_logins')===true){
			$this->db->where('user_id',$user_id)
				->delete($this->db_table('auth_sessions_table'));
		}
		$data=['last_login' => $login_time];
		$this->db->where('user_id',$user_id)
			->update($this->db_table('user_table'),$data);
		$data=[
			'id' => $session_id,
			'user_id' => $user_id,
			'login_time' => $login_time,
			'ip_address' => $this->input->ip_address(),
			'user_agent' => $this->_user_agent()
		];
		$this->db->insert($this->db_table('auth_sessions_table'),$data);
	}
	protected function _user_agent(){
		$this->load->library('user_agent');
		if($this->agent->is_browser()){
			$agent=$this->agent->browser().' '.$this->agent->version();
		}else if($this->agent->is_robot()){
			$agent=$this->agent->robot();
		}else if($this->agent->is_mobile()){
			$agent=$this->agent->mobile();
		}else{
			$agent='Unidentified User Agent';
		}
		$platform=$this->agent->platform();
		return $platform 
			? $agent.' on '.$platform 
			: $agent; 
	}
	public function check_login_status($user_id,$login_time){
		$selected_columns=[
			'u.username',
			'u.userlastname',
			'u.usermiddlename',			
			'u.passport_id',			
			'u.birth_date',
			'u.sex',
			'u.birth_place',
			'u.passport_issue_date',
			'u.passport_subdivision_code',
			'u.passport_issuing_authority',			
			'u.email',
			'u.phone',
			'u.auth_level',
			'u.user_id',
			'u.banned',
			'u.current_step'
		];
		$this->db->select($selected_columns)
			->from($this->db_table('user_table').' u')
			->join($this->db_table('auth_sessions_table').' s','u.user_id=s.user_id')
			->where('s.user_id',$user_id)
			->where('s.login_time',$login_time);
		if(is_null($this->session->regenerated_session_id)){
			$this->db->where('s.id',$this->session->session_id);
		}else{
			$this->db->where('s.id',$this->session->pre_regenerated_session_id);
		}
		$this->db->limit(1);
		$query=$this->db->get();
		if($query->num_rows()==1){
			$row=$query->row_array();
			$acl=$this->add_acl_to_auth_data($row['user_id']);
			return (object) array_merge($row,$acl);
		}
		return false;
	}
	public function add_acl_to_auth_data($user_id){
		$acl=[];
		if(config_item('add_acl_query_to_auth_functions')){
			$acl=$this->acl_query($user_id,true);
		}
		return ['acl' => $acl];
	}
	public function update_user_session_id($user_id){
		if(!is_null($this->session->regenerated_session_id)){
			$this->db->where('user_id',$user_id)
				->where('id',$this->session->pre_regenerated_session_id)
				->update(
					$this->db_table('auth_sessions_table'),
					['id' => $this->session->regenerated_session_id]
			);
		}
	}
	public function clear_expired_holds(){
		$expiration=date('Y-m-d H:i:s',time()-config_item('seconds_on_hold'));
		$this->db->delete($this->db_table('IP_hold_table'),['time <' => $expiration]);
		$this->db->delete($this->db_table('username_or_email_hold_table'),['time <' => $expiration]);
		$this->clear_login_errors();
	}
	public function clear_login_errors(){
		$expiration=date('Y-m-d H:i:s',time()-config_item('seconds_on_hold'));
		$this->db->delete($this->db_table('errors_table'),['time <' => $expiration]);
	}
	public function clear_all_login_errors($ipAddress){
		$this->db->delete($this->db_table('errors_table'),['ip_address' => $ipAddress]);
		$this->db->delete($this->db_table('IP_hold_table'),['ip_address' => $ipAddress]);
	}
	public function check_holds($recovery){
		$ip_hold=$this->check_ip_hold();
		$string_hold=$this->check_username_or_email_hold($recovery);
		if($ip_hold===true||$string_hold===true)
			return true;
		return false;
	}
	public function check_ip_hold(){
		$ip_hold=$this->db->get_where(
			$this->db_table('IP_hold_table'),
			['ip_address' => $this->input->ip_address()] 
		);
		if($ip_hold->num_rows()>0)
			return true;
		return false;
	}	
	public function get_ip_hold(){
		$ip_hold=$this->db->order_by('time','DESC')->get_where(
			$this->db_table('IP_hold_table'),
			['ip_address' => $this->input->ip_address()],1,0
		);
		if($ip_hold->num_rows()>0)
			return $ip_hold->row();
		return false;
	}
	public function check_username_or_email_hold($recovery){
		$posted_string=(!$recovery) 
			? $this->input->post('login_string')||preg_replace('/[\\D]/','',$this->input->post('phone'),-1)
			: $this->input->post('email',true);
		if(!empty($posted_string) && strlen($posted_string) < 256){
			$string_hold=$this->db->get_where(
				$this->db_table('username_or_email_hold_table'),
				['username_or_email' => $posted_string] 
			);
			if($string_hold->num_rows()>0)
				return true;
		}
		return false;
	}
	public function create_login_error($data){
		$this->db->set($data)
			->insert($this->db_table('errors_table'));
	}
	public function check_login_attempts($string,$isSms=false){
		if(!$isSms){
			$maxAttempts=config_item('max_allowed_attempts');
			$denyAccessCount=config_item('deny_access_at');
		}else{
			$maxAttempts=config_item('max_allowed_sms_attempts');
			$denyAccessCount=config_item('deny_access_sms_at');
		}
		$ip_address=$this->input->ip_address();
		$count1=$this->db->where('ip_address',$ip_address)
			->count_all_results($this->db_table('errors_table'));
		if($count1==$maxAttempts){
			$data=[
				'ip_address' => $ip_address,
				'time' => date('Y-m-d H:i:s')
			];
			$this->db->set($data)
				->insert($this->db_table('IP_hold_table'));
			return false;
		}else if($count1>$maxAttempts && $count1 >= $denyAccessCount){
			return false;
			if($denyAccessCount>0){
				$data=[
					'ip_address'  => $ip_address,
					'time' => date('Y-m-d H:i:s'),
					'reason_code' => '1'
				];
				$this->_insert_denial($data);
				header('HTTP/1.1 403 Forbidden');
				die();
			}
		}
		$count2=0;
		if($string != ''){
			$count2=$this->db->where('username_or_email',$string)
				->count_all_results($this->db_table('errors_table'));
			if($count2==config_item('max_allowed_attempts')){
				$data=[
					'username_or_email' => $string,
					'time' => date('Y-m-d H:i:s')
				];
				$this->db->set($data)
					->insert($this->db_table('username_or_email_hold_table'));
			}
		}
		return max($count1,$count2);
	}
	public function get_deny_list($field=false){
		if($field !== false)
			$this->db->select($field);
		$query=$this->db->from($this->db_table('denied_access_table'))->get();
		if($query->num_rows()>0)
			return $query->result();
		return false;
	}
	protected function _insert_denial($data){
		if($data['ip_address']=='0.0.0.0')
			return false;
		$this->db->set($data)
			->insert($this->db_table('denied_access_table'));
		// $this->_rebuild_deny_list();
	}
	protected function _remove_denial($ips){
		$i=0;
		foreach($ips as $ip){
			if($i==0){
				$this->db->where('ip_address',$ip);
			}else{
				$this->db->or_where('ip_address',$ip);
			}
			$i++;
		}
		$this->db->delete($this->db_table('denied_access_table'));
		// $this->_rebuild_deny_list();
	}
	protected function _rebuild_deny_list(){
		$query_result=$this->get_deny_list('ip_address');
		if($query_result !== false){
			$deny_list='<Limit GET POST>'."\n".'order deny,allow';
			foreach($query_result as $row){
				$deny_list .= "\n".'deny from '.$row->ip_address;
			}
			$deny_list .= "\n".'</Limit>'."\n";
		}
		$htaccess=config_item('apache_config_file_location');
		$this->load->helper('file');
		$windowsOs=strtoupper(substr(PHP_OS,0,3))==='WIN';
		$initial_file_permissions=(is_file($htaccess) && !$windowsOs) 
			? decoct(fileperms($htaccess) & 0777)
			: 0644;
		if(is_file($htaccess))
			@chmod($htaccess,0644);
		$string=(string) read_file($htaccess);
		$arr=explode('END DENY LIST --',$string);
		$nonDenyListContent=stripos($string,'END DENY LIST --') !== false
			? $arr[1]
			: $string;
		$string=$deny_list.
				trim($nonDenyListContent)."\n";
		if (!write_file($htaccess,$string))
			die('Could not write to Apache configuration file');
		if(!$windowsOs)
			@chmod($htaccess,$initial_file_permissions);
	}
	public function failed_login_attempt_hook($login_errors_count){
		return;
	}
	public function logout($user_id,$session_id){
		$this->db->where('user_id',$user_id)
			->where('id',$session_id)
			->delete($this->db_table('auth_sessions_table'));
	}
	public function auth_sessions_gc(){
		if(config_item('sess_driver')=='database'){
			$this->db->query('
				DELETE a
				FROM `'.$this->db_table('auth_sessions_table').'` a
				LEFT JOIN `'.$this->db_table('sessions_table').'` b
				ON  b.id=a.id
				WHERE b.id IS NULL
			');
		}
		if(config_item('sess_expiration') != 0){
			$this->db->query('
				DELETE FROM `'.$this->db_table('auth_sessions_table').'` 
				WHERE modified_at < CURDATE()-INTERVAL '.config_item('sess_expiration').' SECOND
			');
		}
	}
    public function get_unused_id(){
        $random_unique_int = 2147483648 + mt_rand( -2147482448, 2147483647 );
        $query = $this->db->where( 'user_id', $random_unique_int )
            ->get_where( $this->db_table('user_table') );
        if( $query->num_rows() > 0 ){
            $query->free_result();
            return $this->get_unused_id();
        }
        return $random_unique_int;
    }	
}