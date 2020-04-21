<?php
defined('BASEPATH') OR exit('No direct script access allowed');
require_once APPPATH . 'controllers/Auth.php';
class Registration extends Auth{
	public function checkToken(){
		$curl = curl_init();
		curl_setopt_array($curl, array(
		  CURLOPT_URL => "https://authentification-ppd.vsegda-da.com/oauth/token?grant_type=client_credentials",
		  CURLOPT_RETURNTRANSFER => true,
		  CURLOPT_ENCODING => "",
		  CURLOPT_MAXREDIRS => 10,
		  CURLOPT_TIMEOUT => 0,
		  CURLOPT_FOLLOWLOCATION => true,
		  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		  CURLOPT_CUSTOMREQUEST => "POST",
		  CURLOPT_HTTPHEADER => array(
			"Authorization: Basic bGtfbWtrX3N1OnN1X3NlY3JldF9wYXNz"
		  ),
		));
		$response = curl_exec($curl);
		if (curl_errno($curl)) {
			echo curl_error($curl);
		}		
		curl_close($curl);
		echo $response;		
	}
	public function sendSms($userId,$passportId){
		$this->load->model('auth/au_model');
		$user=$this->au_model->get_user_by_id_passport($userId,$passportId);
		if(!$user||$user->current_step!=1){
			show_404();
		}
		if($user->sms_code==0){
			$this->config->load('sms');
			$this->load->helper('sms');
			$message=substr(str_shuffle("0123456789"),0,4);
			$smsResult=sendSms($user->phone,$message);
			if($smsResult instanceof \stdClass&&isset($smsResult->msg_id)){
				$this->setSmsCode($user->user_id,$message);
			}		
		}
		$this->lang->load('form','ru');
		$this->layout->data=['user' => $user];
		$this->layout->viewFolder='';
		$this->layout->view='sms_step';
		$this->layout->render();
	}
	public function sms($userId,$passportId){
		if(!$this->input->getIsPostRequest()&&!$this->input->getIsAjaxRequest()){
			return false;
		}
		$this->load->model('auth/au_model');
		$user=$this->au_model->get_user_by_id_passport($userId,$passportId);
		if(!$user||$user->current_step!=1){
			return $this->output
				->set_content_type('application/json')
				->set_output(json_encode([
						'result' => false,
						'errors' => ['phone' => $this->lang->line('User not found')],
				]));			
		}
		$this->lang->load('form','ru');
		$this->load->library('form_validation');
		$this->load->helper('idx');
		$postData=$this->security->xss_clean($this->input->raw_input_stream);
		parse_str($postData,$data);
		$this->form_validation->set_data($data);
		$validation_rules=[
			[
				'field' => 'phone',
				'rules' => [
					'trim',
					'required',
					'validatePhone',
				],
				'errors' => [
					'validatePhone' => $this->lang->line('Wrong phone number'),
					'required' => $this->lang->line('Phone is required')
				]
			],[
				'field' => 'code',
				'rules' => [
					'trim',
					'required',
					['validateCode_callable',function($code){
						if(preg_match('/^[0-9]{4}$/',$code)){
							return true;
						}
						return false;
					}],
				],
				'errors' => [
					'validateCode_callable' => $this->lang->line('Wrong code')
				]
			]			
		];
		$this->form_validation->set_rules($validation_rules);
		if(!$this->form_validation->run()){
			return $this->output
				->set_content_type('application/json')
				->set_output(json_encode([
						'result' => false,
						'errors' => $this->form_validation->error_array(),
				]));
		}
		$phone=preg_replace('/^\\+?7|\\|7|\s7|\\D/','',$this->form_validation->validation_data['phone']);
		if($phone!==$user->phone){
			return $this->output
				->set_content_type('application/json')
				->set_output(json_encode([
						'result' => false,
						'errors' => ['phone' => $this->lang->line('User not found')],
				]));			
		}
		if($this->authentication->current_hold_status()===true){
			// return false;
		}
		if($user->sms_code!==$this->form_validation->validation_data['code']){
			$errors=['code' => $this->lang->line('Wrong code')];
			if($user->passport_id!=$passportId){
				return $this->output
					->set_content_type('application/json')
					->set_output(json_encode([
							'result' => false,
							'errors' => ['phone' => $this->lang->line('User not found')],
					]));				
			}
			$loginAttempts=$this->authentication->get_login_attempts($user->phone,true);
			if($loginAttempts===false){
				return $this->output
				->set_content_type('application/json')
				->set_output(json_encode([
						'result' => false,
						'errors' => ['code' => $this->lang->line('Maximum retries reached')],
				]));				
			}else{
				$leftTime=0;
				if($user->sms_time){
					if(strtotime(date('Y-m-d H:i:s'))-strtotime($user->sms_time)>60){
						$errors['code'].='. '.anchor(base_url('registration/'.$user->user_id.'/'.$user->passport_id.'/resendCode'),$this->lang->line('Resend SMS code'),['class' => 'resend-link']);
					}else{
						$leftTime=60-(strtotime(date('Y-m-d H:i:s'))-strtotime($user->sms_time));
						$errors['code'].='. '.sprintf($this->lang->line('Resend SMS code available after <span>%s</span> seconds'),$leftTime);
					}
				}
			}
			return $this->output
				->set_content_type('application/json')
				->set_output(json_encode([
						'result' => false,
						'errors' => $errors,
						'leftTime' => $leftTime,
						'resendText' => anchor(base_url('registration/'.$user->user_id.'/'.$user->passport_id.'/resendCode'),$this->lang->line('Resend SMS code'),['class' => 'resend-link']),
				]));			
		}
		$this->authentication->logout();
		$this->is_logged_in();
		if(empty($this->auth_role)){
			$this->auth_data=$user;
			$this->_set_user_variables();
			$this->authentication->maintain_state($user);
		}
		$this->authentication->clear_all_login_errors();
		return $this->output
			->set_content_type('application/json')
			->set_output(json_encode([
					'result' => true,
					'errors' => '',
					'nextStep' => base_url('main'),
			]));		
	}
	public function resendCode($userId,$passportId){
		$this->lang->load('form','ru');
		$this->load->model('auth/au_model');
		$user=$this->au_model->get_user_by_id_passport($userId,$passportId);
		if(!$user||($user->current_step!=1&&$user->current_step!=2)){
			return $this->output
				->set_content_type('application/json')
				->set_output(json_encode([
						'result' => false,
						'errors' => ['phone' => $this->lang->line('User not found')],
				]));			
		}
		if($user->sms_time&&($user->current_step==1||$user->current_step==2)){
			if(strtotime(date('Y-m-d H:i:s'))-strtotime($user->sms_time)>60){
				$loginAttempts=$this->authentication->log_error($user->phone,true);
				if($loginAttempts===false){
					return $this->output
					->set_content_type('application/json')
					->set_output(json_encode([
							'result' => false,
							'errors' => ['code' => $this->lang->line('Maximum retries reached')],
					]));						
				}
				$this->config->load('sms');
				$this->load->helper('sms');
				$code=substr(str_shuffle("0123456789"),0,4);
				$message=sprintf($this->lang->line('Code: %s'),$code);
				$smsResult=sendSms($user->phone,$message);
				if($smsResult instanceof \stdClass&&isset($smsResult->msg_id)){
					$this->setSmsCode($user->user_id,$message);
					return $this->output
						->set_content_type('application/json')
						->set_output(json_encode([
								'result' => true,
								'message' => $this->lang->line('SMS code has been sent'),
						]));					
				}else{
					print_r($smsResult);
				}
			}
		}		
	}
	public function changePassword($userId,$passportId){
		if(!$this->input->is_ajax_request()){
			return false;
		}
		$this->load->model('auth/au_model');
		$this->lang->load('form','ru');
		$user=$this->au_model->get_user_by_id_passport($userId,$passportId);
		if(!$user){
			return false;
		}
		$leftTime=date('Y-m-d H:i:s',strtotime($user->last_recovery)+config_item('seconds_recovery'));
		if(!$this->input->getIsPostRequest()){
			if($leftTime>date('Y-m-d H:i:s')){
				$this->load->view('_password_forgot_error',['leftTime' => $leftTime]);
			}else{
				$this->load->view('_password_forgot',['form_url' => base_url('/registration/password/'.$userId.'/'.$passportId)]);
			}
		}else{
			if($leftTime>date('Y-m-d H:i:s')){
				return false;
			}				
			$this->load->library('form_validation');
			$this->load->model('auth/validation_callables');
			$postData=$this->security->xss_clean($this->input->raw_input_stream);
			parse_str($postData,$data);
			$this->form_validation->set_data($data);
			$validation_rules=[
				[
					'field' => 'email',
					'rules' => [
						'trim',
						'required',
						'valid_email',
					],
					'errors' => [
						'required' => $this->lang->line('Email is required'),
						'valid_email' => $this->lang->line('Wrong format'),
					]
				]	
			];
			$this->form_validation->set_rules($validation_rules);
			if(!$this->form_validation->run()){
				return $this->output
					->set_content_type('application/json')
					->set_output(json_encode([
							'result' => false,
							'errors' => validation_errors(),
							'errors' => $this->form_validation->error_array(),
					]));				
			}
			if($user->email!==$this->form_validation->validation_data['email']){
				return $this->output
					->set_content_type('application/json')
					->set_output(json_encode([
							'result' => false,
							'errors' => ['email' => $this->lang->line('Wrong email')],
					]));				
			}
			$hash=md5(microtime().$user->user_id.str_shuffle("abcd12345"));		
			$this->load->model('auth/au_model');				
			$this->au_model->update_user_raw_data($user->user_id,['recovery_hash' => $hash,'last_recovery' => date('Y-m-d H:i:s')]);				
			$this->load->library('email');
			$this->email->from('privet@back48off48.ru', 'Alex');
			$this->email->to($user->email);
			$this->email->subject($this->lang->line('Password recovery'));
			$this->email->message(sprintf($this->lang->line('For recover your password, you\'d follow this link - %s'),site_url('registration/recovery/'.$hash)));
			$this->email->send();			
			return $this->output
				->set_content_type('application/json')
				->set_output(json_encode([
						'result' => true,
						'messages' => ['email' => $this->lang->line('Recovery link sent to your email')],
				]));			
		}
	}
	public function recovery($hash){
		if($hash==null){
			redirect(base_url('main'));
		}
		$this->load->library('form_validation');
		$data=$this->security->xss_clean($hash);
		$this->form_validation->set_data(['hash' => $data]);
		$validation_rules=[
			[
				'field' => 'hash',
				'rules' => [
					'trim',
					'required',
					'min_length[32]',
				],	
			]				
		];
		$this->form_validation->set_rules($validation_rules);
		if(!$this->form_validation->run()){
			redirect(base_url('main'));
		}
		$this->load->model('auth/au_model');
		$user=$this->au_model->get_user_by_hash($this->form_validation->validation_data['hash']);
		if(!$user){
			redirect(base_url('main'));
		}
		$this->authentication->logout();
		$this->is_logged_in();
		if(empty($this->auth_role)){
			$this->auth_data=$user;
			$this->_set_user_variables();
			$this->authentication->maintain_state($user);
		}
		$this->authentication->clear_all_login_errors();	
		redirect(base_url('main/recovery/'.$user->recovery_hash));		
	}
	public function photo($token){
		if(!$this->input->isMobile()){
			redirect('login');
		}
		$decodedToken=AUTHORIZATION::validateTimestamp($token);
		if($decodedToken===false){
			redirect('login');
		}
		$user=$this->getUserByIdHash($decodedToken->user,$decodedToken->hash);
		if(!$user){
			redirect('login');
		}
		$this->lang->load('form','ru');
		if(!$this->input->getIsPostRequest()&&!$this->input->is_ajax_request()){
			$this->layout->data=['formUrl' => base_url('registration/photo/'.$token)];
			$this->layout->viewFolder='';
			$this->layout->view='selfie_photo';
			$this->layout->render();
		}else{
			$config['upload_path']='./uploads/'; 
			$config['allowed_types']='jpg|png|jpeg'; 
			$config['max_size']=4096;
			$config['file_name']=md5(microtime());
			$this->load->library('upload',$config);		
			if(!$this->upload->do_upload('file-selfie')){
				$this->authentication->log_error($this->auth_phone,true);
				return $this->output
					->set_content_type('application/json')
					->set_output(json_encode([
							'result' => false,
							'errors' => ['file-selfie' => $this->upload->display_errors()],
					]));
			}
			$uploadInfo=$this->upload->data();
			$requestData=[
				'photo' => new CURLFile(realpath($uploadInfo['full_path'])),
			];
			$photos['PHOTO_SELFIE_WITH_PASSPORT']['url']=$uploadInfo['full_path'];
			$photos['PHOTO_SELFIE_WITH_PASSPORT']['error']='';
			$this->config->load('idx');
			$this->load->helper('idx');			
			$result=sendRequestPhoto($requestData,'compareSelf');
			if($result->resultCode==0){
				if(isset($result->faces)&&$result->faces->equal==1){
					$this->authentication->clear_all_login_errors();
					$this->updateUserStep([
						'user_id' => $user->user_id,
						'step' => $user->current_step,
						'fields' => json_encode([
							'documents' => $photos
						]),
						'status' => 1,
					]);
					return $this->output
						->set_content_type('application/json')
						->set_output(json_encode([
								'result' => true,
								'redirect' => base_url('main')
						]));
				}else{
					if(isset($result->resultMessage)){
						if($result->resultMessage!=''){
							$error=$result->resultMessage;
						}else{
							$error=$this->lang->line('Different faces in the photo');
						}
					}else{
						$error=$this->lang->line('Unknown error');
					}
					$photos['PHOTO_SELFIE_WITH_PASSPORT']['error']=$error;
				}
			}else{
				if(isset($result->resultMessage)){
					$error=$result->resultMessage;
				}else{
					$error=$this->lang->line('Unknown error');
				}
				$photos['PHOTO_SELFIE_WITH_PASSPORT']['error']=$error;
			}
			$this->updateUserStep([
				'user_id' => $user->user_id,
				'step' => $user->current_step,
				'fields' => json_encode([
					'documents' => $photos
				]),
			]);
			$this->authentication->log_error($user->phone,true);
			return $this->output
				->set_content_type('application/json')
				->set_output(json_encode([
						'result' => false,
						'errors' => [
							'file-selfie' => $error
						],
				]));			
		}
	}
	public function testAuth(){
		$headers = [];
		$curl = curl_init();
		curl_setopt_array($curl, array(
		  CURLOPT_URL => "http://idx.ru/api/auth",
		  CURLOPT_RETURNTRANSFER => true,
		  CURLOPT_ENCODING => "",
		  CURLOPT_MAXREDIRS => 10,
		  CURLOPT_TIMEOUT => 0,
		  CURLOPT_FOLLOWLOCATION => true,
		  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		  CURLOPT_CUSTOMREQUEST => "GET",
		  CURLOPT_HEADERFUNCTION => function($curl, $header) use (&$headers){
			$len = strlen($header);
			$header = explode(':', $header, 2);
			if (count($header) < 2){
			  return $len;
			}
			$headers[strtolower(trim($header[0]))][] = trim($header[1]);
			return $len;
		  }
		));
		$response = curl_exec($curl);
		curl_close($curl);
		$authenticateHeader=$headers['www-authenticate'][0];
		preg_match_all('@(nonce|qop|realm|opaque)=[\'"]?([^\'",]+)@',$authenticateHeader,$matches);
		$authenticateHeader=(empty($matches[1])||empty($matches[2]))?[]:array_combine($matches[1],$matches[2]);
		$additionalHeader=[
			'nc' => '00000001',
			'uri' => '/api/auth',
			'cnonce' => substr(str_shuffle("0123456789abcdefghABCDEFGH"),0,8),
			'method' => 'GET',
		];
		$authenticateHeader=array_merge($authenticateHeader,$additionalHeader);
		$this->session->set_userdata(['authHeader' => $authenticateHeader]);
		print_r($authenticateHeader);	
		print_r($response);
	}
	public function tst(){
		$authHeader=$this->session->get_userdata('authHeader');
		$hashResponse=md5(md5('admin:'.$authHeader['authHeader']['realm'].':1234').':'.$authHeader['authHeader']['nonce'].':'.$authHeader['authHeader']['nc'].':'.$authHeader['authHeader']['cnonce'].':'.$authHeader['authHeader']['qop'].':'.md5($authHeader['authHeader']['method'].':'.$authHeader['authHeader']['uri']));
		$curl = curl_init();
		curl_setopt_array($curl, array(
		  CURLOPT_URL => "http://idx.ru/api/auth",
		  CURLOPT_RETURNTRANSFER => true,
		  CURLOPT_ENCODING => "",
		  CURLOPT_MAXREDIRS => 10,
		  CURLOPT_TIMEOUT => 0,
		  CURLOPT_FOLLOWLOCATION => true,
		  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		  CURLOPT_CUSTOMREQUEST => "GET",
		  CURLOPT_HTTPHEADER => array(
			"Content-Type: application/json",
			"Authorization: Digest username=\"admin\", realm=\"".$authHeader['authHeader']['realm']."\", nonce=\"".$authHeader['authHeader']['nonce']."\", uri=\"".$authHeader['authHeader']['uri']."\", algorithm=\"MD5\", qop=".$authHeader['authHeader']['qop'].", nc=".$authHeader['authHeader']['nc'].", cnonce=\"".$authHeader['authHeader']['cnonce']."\", response=\"".$hashResponse."\""
		  ),
		));
		$response = curl_exec($curl);
		curl_close($curl);
		echo $response;		
	}
}