<?php
defined('BASEPATH') OR exit('No direct script access allowed');
class Au_model extends MY_Model{
	public function update_user_raw_data($the_user,$user_data=[]){
		$this->db->where('user_id',$the_user)
			->update($this->db_table('user_table'),$user_data);
	}
	public function get_recovery_data($email){
		$query=$this->db->select('u.user_id,u.email,u.banned')
			->from($this->db_table('user_table').' u')
			->where('LOWER(u.email) =',strtolower($email))
			->limit(1)
			->get();
		if($query->num_rows()==1)
			return $query->row();
		return false;
	}
    public function get_unused_id(){
        $random_unique_int=2147483648+mt_rand(-2147482448,2147483647);
        $query=$this->db->where('user_id',$random_unique_int)
            ->get_where($this->db_table('user_table'));
        if($query->num_rows()>0){
            $query->free_result();
            return $this->get_unused_id();
        }
        return $random_unique_int;
    }
	public function check_user_sms($phone,$code){
		$user=$this->db->get_where(
			$this->db_table('user_table'),[
				'phone' => $phone,
				'sms_code' => $code
			] 
		);
		if($user->num_rows()>0)
			return $user->row();
		return false;
	}
	public function get_by_application_id($applicationId){
		$application=$this->db->get_where(
			$this->db_table('user_table'),[
				'id_vd' => $applicationId
			],1,0
		);
		if($application->num_rows()>0){
			return $application->row();
		}
		return false;
	}
	public function get_application_documents($userId){
		$application=$this->db->get_where(
			$this->db_table('steps_table'),[
				'user_id' => $userId
			]
		);
		if($application->num_rows()>0){
			return $application->result_array();
		}
		return false;
	}
	public function get_user($phone){
		$user=$this->db->get_where(
			$this->db_table('user_table'),[
				'phone' => $phone,
			] 
		);
		if($user->num_rows()>0)
			return $user->row();
		return false;
	}
	public function get_user_by_id_hash($userId,$hash){
		$user=$this->db->get_where(
			$this->db_table('user_table'),[
				'user_id' => $userId,
				'custom_hash' => $hash,
			] 
		);
		if($user->num_rows()>0)
			return $user->row();
		return false;
	}
	public function get_user_by_phone_password($phone,$password){
		$user=$this->db->get_where(
			$this->db_table('user_table'),[
				'phone' => $phone,
				'passwd' => $password,
			] 
		);
		if($user->num_rows()>0)
			return $user->row();
		return false;
	}
	public function get_application($applicationId){
		$application=$this->db->get_where(
			$this->db_table('user_table'),[
				'id_vd' => $applicationId,
			] 
		);
		if($application->num_rows()>0)
			return $application->row();
		return false;
	}
	public function get_user_by_id_passport($id,$passportId){
		$user=$this->db->get_where(
			$this->db_table('user_table'),[
				'user_id' => $id,
				'passport_id' => $passportId,
			] 
		);
		if($user->num_rows()>0)
			return $user->row();
		return false;
	}
	public function get_user_by_email($email){
		$user=$this->db->get_where(
			$this->db_table('user_table'),[
				'email' => $email,
			] 
		);
		if($user->num_rows()>0)
			return $user->row();
		return false;
	}
	public function get_user_by_hash($hash){
		$user=$this->db->get_where(
			$this->db_table('user_table'),[
				'recovery_hash' => $hash,
			] 
		);
		if($user->num_rows()>0)
			return $user->row();
		return false;
	}
	public function get_user_by_hash_id($hash,$user_id){
		$user=$this->db->get_where(
			$this->db_table('user_table'),[
				'recovery_hash' => $hash,
				'user_id' => $user_id,
			] 
		);
		if($user->num_rows()>0)
			return $user->row();
		return false;
	}
	public function get_user_by_phone_passport($phone,$passportId){
		$user=$this->db->get_where(
			$this->db_table('user_table'),[
				'phone' => $phone,
				'passport_id' => $passportId,
			] 
		);
		if($user->num_rows()>0)
			return $user->row();
		return false;
	}
	public function get_user_sms_time($userId){
		$selected_columns=[
			'sms_time',
		];
		$query=$this->db->select($selected_columns)
			->from($this->db_table('user_table'))
			->where('user_id =',$userId)
			->limit(1)
			->get();
		if($query->num_rows()==1){
			return $query->row();
		}
		return false;
	}
	public function update_user_step_raw_data($data){
		$data['date']=date('Y-m-d H:i:s');
		$query=$this->db->insert_string($this->db_table('steps_table'),$data).' ON DUPLICATE KEY UPDATE user_id=VALUES(user_id),step=VALUES(step),fields=VALUES(fields),status=VALUES(status),date=VALUES(date)';
		$this->db->query($query);
		$id=$this->db->insert_id();
	}
	public function update_user_recovery_hash($hash,$user_id){
		$this->db->where('user_id',$user_id)->update($this->db_table('user_table'),['recovery_hash' => $hash]);
	}	
}