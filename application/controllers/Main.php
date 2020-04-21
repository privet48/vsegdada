<?php
defined('BASEPATH') OR exit('No direct script access allowed');
require_once APPPATH . 'controllers/Auth.php';
class Main extends Auth{
	public function __construct(){
		parent::__construct();
		$this->is_logged_in();
		if(empty($this->auth_role)){
			redirect('login');
		}
		$this->lang->load('form','ru');
		$this->config->load('idx');
		$this->load->helper('idx');
	}
	public function index(){
		if(($on_hold=$this->authentication->get_current_hold())!==false){
			$leftTime=date('Y-m-d H:i:s',strtotime($on_hold->time)+config_item('seconds_on_hold'));
			$on_hold_message=sprintf($this->lang->line('Due to incorrect data entry more than %d times, we are forced to block access to this account for 24 hours in order to ensure the security of your data. Your account will be distributed through: %s at %s'),config_item('max_allowed_attempts'),date('Y-m-d',strtotime($leftTime)),date('H:i',strtotime($leftTime)));	
			if(!$this->input->getIsPostRequest()){
				$this->layout->data=['on_hold_message' => $on_hold_message];
				$this->layout->viewFolder='';
				$this->layout->viewFolder='';
				$this->layout->view='denied_access';
				return $this->layout->render();
			}else{
				return $this->output
					->set_content_type('application/json')
					->set_output(json_encode([
							'result' => false,
							'error' => sprintf($this->lang->line('Due to incorrect data entry more than %d times, we are forced to block access to this account for 24 hours in order to ensure the security of your data. Your account will be distributed through: %s at %s'),config_item('max_allowed_attempts'),date('Y-m-d',strtotime($leftTime)),date('H:i',strtotime($leftTime))),
							'form_token' => $this->tokens->token(),
					]));				
			}
		}		
		switch($this->auth_current_step){
			case 1:
			$this->changePassword();
			break;
			case 2:
			$this->identificationStep();
			break;
			case 3:
			$this->photoStep();
			break;
			case 4:
			$this->idConfirmationStep();
			break;
			case 5:
			$this->idConfirmationSmsStep();
			break;
			case 6:
			$this->applicationResult();
			break;
		}
	}
	protected function changePassword(){
		if(!$this->input->is_ajax_request()){
			// return false;
		}
		if(!$this->input->getIsPostRequest()&&$this->input->is_ajax_request()){
			$this->load->view('_password_step',['form_url' => base_url('main')]);
		}elseif(!$this->input->getIsPostRequest()&&!$this->input->is_ajax_request()){
			$this->layout->data=['form_url' => base_url('main')];
			$this->layout->viewFolder='';
			$this->layout->view='password_step';
			$this->layout->render();
		}else{
			$this->load->library('form_validation');
			$this->load->model('auth/validation_callables');
			$postData=$this->security->xss_clean($this->input->raw_input_stream);
			parse_str($postData,$data);
			$this->form_validation->set_data($data);
			$validation_rules=[
				[
					'field' => 'password',
					'rules' => [
						'trim',
						'required',
						[ 
							'_check_password_strength',
							[ 
								$this->validation_callables,'_check_password_strength' 
							] 
						]
					],
					'errors' => [
						'required' => $this->lang->line('Password is required')
					]
				],[
					'field' => 'cpassword',
					'rules' => [
						'trim',
						[ 
							'_check_password_equal',
							[ 
								$this->validation_callables,'_check_password_equal'
							] 
						]						
					],
				]			
			];		
			$this->form_validation->set_rules($validation_rules);
			if(!$this->form_validation->run()){
				return $this->output
					->set_content_type('application/json')
					->set_output(json_encode([
							'result' => false,
							'errors' => validation_errors(),
							'errorsArray' => $this->form_validation->error_array(),
					]));				
			}
			$this->setUserPassword($this->auth_user_id,$data['password']);
			$this->setUserStep($this->auth_user_id,2);
			return $this->output
				->set_content_type('application/json')
				->set_output(json_encode([
						'result' => true,
						'redirect' => base_url('main')
				]));			
		}
	}
	public function recovery($hash){
		$this->load->library('form_validation');
		$hash=$this->security->xss_clean($hash);
		$this->form_validation->set_data(['hash' => $hash]);
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
			$this->authentication->logout();
			redirect(base_url('main'));
		}
		$this->load->model('auth/au_model');
		$user=$this->au_model->get_user_by_hash_id($this->form_validation->validation_data['hash'],$this->auth_user_id);
		if(!$user){
			$this->authentication->logout();
			redirect(base_url('main'));
		}		
		if(!$this->input->getIsPostRequest()&&$this->input->is_ajax_request()){
			$this->load->view('_password_step',['form_url' => base_url('main/recovery/'.$hash)]);
		}elseif(!$this->input->getIsPostRequest()&&!$this->input->is_ajax_request()){
			$this->layout->data=['form_url' => base_url('main/recovery/'.$hash)];
			$this->layout->viewFolder='';
			$this->layout->view='password_step';
			$this->layout->render();
		}else{
			$this->load->model('auth/validation_callables');
			$postData=$this->security->xss_clean($this->input->raw_input_stream);
			parse_str($postData,$data);
			$this->form_validation->set_data($data);
			$validation_rules=[
				[
					'field' => 'password',
					'rules' => [
						'trim',
						'required',
						[ 
							'_check_password_strength',
							[ 
								$this->validation_callables,'_check_password_strength' 
							] 
						]
					],
					'errors' => [
						'required' => $this->lang->line('Password is required')
					]
				],[
					'field' => 'cpassword',
					'rules' => [
						'trim',
						[ 
							'_check_password_equal',
							[ 
								$this->validation_callables,'_check_password_equal'
							] 
						]						
					],
				]			
			];		
			$this->form_validation->set_rules($validation_rules);
			if(!$this->form_validation->run()){
				return $this->output
					->set_content_type('application/json')
					->set_output(json_encode([
							'result' => false,
							'errors' => validation_errors(),
							'errorsArray' => $this->form_validation->error_array(),
					]));				
			}
			$this->setUserPassword($this->auth_user_id,$data['password']);
			$this->au_model->update_user_recovery_hash('',$user->user_id);	
			return $this->output
				->set_content_type('application/json')
				->set_output(json_encode([
						'result' => true,
						'redirect' => base_url('main')
				]));			
		}
	}
	protected function identificationStep(){	
		if(!$this->input->getIsPostRequest()){
			$this->layout->data=['formUrl' => base_url('main')];
			$this->layout->viewFolder='';
			$this->layout->view='identification_step';
			$this->layout->render();
		}else{
			$this->load->library('form_validation');
			$postData=$this->security->xss_clean($this->input->raw_input_stream);
			parse_str($postData,$data);
			$this->form_validation->set_data($data);
			$documents=[];
			$validation_rules=[
				[
					'field' => 'document',
					'rules' => 'required|integer|in_list['.implode(array_values(config_item('documentTypes')),',').']',
					'errors' => [
						'required' => $this->lang->line('Document type is required')
					]
				],[
					'field' => 'documentId',
					'rules' => [
						'trim',
						'required',
						'validateDocument['.$this->input->post('document').']',
					],
					'errors' => [
						'required' => $this->lang->line('Document ID is required'),
						'validateDocument' => $this->lang->line('Wrong document format'),
					]
				]
			];		
			$this->form_validation->set_rules($validation_rules);
			if(!$this->form_validation->run()){
				$this->authentication->log_error($this->auth_phone,true);
				return $this->output
					->set_content_type('application/json')
					->set_output(json_encode([
							'result' => false,
							'errors' => $this->form_validation->error_array(),
					]));				
			}
			$documents=[
				array_flip(config_item('documentTypes'))[$this->form_validation->validation_data['document']] => $this->form_validation->validation_data['documentId']
			];
			$this->updateUserStep([
				'user_id' => $this->auth_user_id,
				'step' => $this->auth_current_step,
				'fields' => json_encode($documents),
			]);
			$requestData=[
				'lastName' => $this->auth_userlastname,
				'firstName' => $this->auth_username,
				'midName' => $this->auth_usermiddlename,
			];
			if(array_key_exists($this->auth_userlastname,config_item('testUsersWhiteList'))){
				if(in_array($this->auth_current_step,config_item('testUsersWhiteList')[$this->auth_userlastname])){
					$this->updateUserStep([
						'user_id' => $this->auth_user_id,
						'step' => $this->auth_current_step,
						'fields' => json_encode($documents),
						'status' => 1,
					]);
					$this->setUserStep($this->auth_user_id,3);
					return $this->output
						->set_content_type('application/json')
						->set_output(json_encode([
								'result' => true,
								'redirect' => base_url('main')
						]));				
				}
			}
			if(array_key_exists($this->auth_userlastname,config_item('testUsersBlackList'))){
				if(array_key_exists($this->form_validation->validation_data['document'],config_item('testUsersBlackList')[$this->auth_userlastname])){
					return $this->output
						->set_content_type('application/json')
						->set_output(json_encode([
								'result' => false,
								'errors' => ['documentId' => $this->lang->line(config_item('testUsersBlackList')[$this->auth_userlastname][$this->form_validation->validation_data['document']])]
						]));
				}
			}
			if(ENVIRONMENT!='development'){
				if($this->form_validation->validation_data['document']==config_item('documentTypes')['identificationNumber']){
					$requestData['passportNumber']=$this->auth_user_passport;
					$requestData['inn']=$this->form_validation->validation_data['documentId'];
					$result=sendRequest($requestData,'checkInn');
				}elseif($this->form_validation->validation_data['document']==config_item('documentTypes')['insuranceNumber']){
					$requestData['snils']=$this->form_validation->validation_data['documentId'];
					$result=sendRequest($requestData,'checkSnils');
				}
			}else{
				$result=new \stdClass;
				$result->isValid=true;
			}
			if(isset($result->isValid)&&$result->isValid){
				$this->updateUserStep([
					'user_id' => $this->auth_user_id,
					'step' => $this->auth_current_step,
					'fields' => json_encode($documents),
					'status' => 1,
				]);
				$this->setUserStep($this->auth_user_id,3);
				$this->authentication->clear_all_login_errors();
				return $this->output
					->set_content_type('application/json')
					->set_output(json_encode([
							'result' => true,
							'redirect' => base_url('main')
					]));				
			}else{
				$this->authentication->log_error($this->auth_phone,true);
				$errors=['documentId' => ''];
				if(isset($result->resultMessage)){
					$errors['documentId']=$result->resultMessage;
				}else{
					$errors['documentId']=$this->lang->line('Document does not belongs to person');
				}
				return $this->output
					->set_content_type('application/json')
					->set_output(json_encode([
							'result' => false,
							'errors' => $errors
					]));
			}
		}
	}
	protected function photoStep(){
		$this->load->model('auth/au_model');
		$resultArray=[];
		if(($documents=$this->au_model->get_application_documents($this->auth_user_id))!==false){
			$fields=[];
			array_map(function($data)use(&$fields,&$resultArray){
				if($data['status']==1&&$data['step']==3){
					$resultArray=json_decode($data['fields'],true);
				}
			},$documents);
		}
		if(!$this->input->getIsPostRequest()){
			$this->layout->data=['formUrl' => base_url('main'),'isMobile' => $this->input->isMobile(),'images' => $resultArray];
			$this->layout->viewFolder='';
			$this->layout->view='photo_step';
			$this->layout->render();
		}else{
			if(empty($resultArray)){
				$resultArray['documents']=[];
			}
			$photos=[];
			$this->lang->load('upload', 'ru');
			$config['upload_path']='./uploads/'; 
			$config['allowed_types']='jpg|png|jpeg'; 
			$config['max_size']=4096;
			$config['file_name']=md5(microtime());
			$this->load->library('upload',$config);
			if(array_key_exists($this->auth_userlastname,config_item('testUsersWhiteList'))){
				if(in_array($this->auth_current_step,config_item('testUsersWhiteList')[$this->auth_userlastname])){
					if(!$this->upload->do_upload('file-self')){
						return $this->output
							->set_content_type('application/json')
							->set_output(json_encode([
									'result' => false,
									'errors' => ['file-self' => $this->upload->display_errors()],
							]));
					}
					if(!$this->upload->do_upload('file-turn')){
						return $this->output
							->set_content_type('application/json')
							->set_output(json_encode([
									'result' => false,
									'errors' => ['file-turn' => $this->upload->display_errors()],
							]));
					}
					if(!$this->upload->do_upload('file-registration')){
						return $this->output
							->set_content_type('application/json')
							->set_output(json_encode([
									'result' => false,
									'errors' => ['file-registration' => $this->upload->display_errors()],
							]));
					}
					$this->setUserStep($this->auth_user_id,4);	
					return $this->output
						->set_content_type('application/json')
						->set_output(json_encode([
								'result' => true,
								'redirect' => base_url('main')
						]));
				}
			}
			if(!$this->upload->do_upload('file-self')&&!in_array('PHOTO_SELFIE_WITH_PASSPORT',array_keys($resultArray['documents']))){
				$this->authentication->log_error($this->auth_phone,true);
				return $this->output
					->set_content_type('application/json')
					->set_output(json_encode([
							'result' => false,
							'errors' => ['file-self' => $this->upload->display_errors()],
					]));
			}elseif(!$this->upload->do_upload('file-self')&&in_array('PHOTO_SELFIE_WITH_PASSPORT',array_keys($resultArray['documents']))&&$resultArray['documents']['PHOTO_SELFIE_WITH_PASSPORT']['error']!==''){
				$this->authentication->log_error($this->auth_phone,true);
				if(in_array('PHOTO_SELFIE_WITH_PASSPORT',array_keys($resultArray['documents']))&&$resultArray['documents']['PHOTO_SELFIE_WITH_PASSPORT']['error']!=''){
					$error=$resultArray['documents']['PHOTO_SELFIE_WITH_PASSPORT']['error'];
				}else{
					$error=$this->upload->display_errors();
				}							
				return $this->output
					->set_content_type('application/json')
					->set_output(json_encode([
							'result' => false,
							'errors' => ['file-self' => $error],
					]));
			}else{
				if(!in_array('PHOTO_SELFIE_WITH_PASSPORT',array_keys($resultArray['documents']))||(in_array('PHOTO_SELFIE_WITH_PASSPORT',array_keys($resultArray['documents']))&&$resultArray['documents']['PHOTO_SELFIE_WITH_PASSPORT']['error']!=='')){
					$uploadInfo=$this->upload->data();
					$requestData=[
						'photo' => new CURLFile(realpath($uploadInfo['full_path'])),
					];
					$photos['PHOTO_SELFIE_WITH_PASSPORT']['url']=$uploadInfo['full_path'];
					$photos['PHOTO_SELFIE_WITH_PASSPORT']['error']='';
					$resultArray['documents']['PHOTO_SELFIE_WITH_PASSPORT']['url']=$uploadInfo['full_path'];
					$resultArray['documents']['PHOTO_SELFIE_WITH_PASSPORT']['error']='';
					if(ENVIRONMENT!='development'){
						$result=sendRequestPhoto($requestData,'compareSelf');
					}else{
						$result=new stdClass;
						$result->resultCode=0;
						$result->faces=new stdClass;
						$result->faces->equal=1;
					}
				}else{
					$result=new stdClass;
					$result->resultCode=0;
					$result->faces=new stdClass;
					$result->faces->equal=1;
				}
				if($result->resultCode==0){
					if(isset($result->faces)&&$result->faces->equal==1){
						// $resultArray['documents']['PASSPORT_2_3P']['url']='';
						if(!$this->upload->do_upload('file-turn')&&!in_array('PASSPORT_2_3P',array_keys($resultArray['documents']))){
							$this->authentication->log_error($this->auth_phone,true);
							if(in_array('PASSPORT_2_3P',array_keys($resultArray['documents']))&&$resultArray['documents']['PASSPORT_2_3P']['error']!=''){
								$error=$resultArray['documents']['PASSPORT_2_3P']['error'];
							}else{
								$error=$this->upload->display_errors();
							}
							$resultArray['documents']['PASSPORT_2_3P']['error']=$error;
							$this->updateUserStep([
								'user_id' => $this->auth_user_id,
								'step' => $this->auth_current_step,
								'fields' => json_encode($resultArray),
								'status' => 1,
							]);
							return $this->output
								->set_content_type('application/json')
								->set_output(json_encode([
										'result' => false,
										'errors' => ['file-turn' => $error],
								]));
						}elseif(!$this->upload->do_upload('file-turn')&&in_array('PASSPORT_2_3P',array_keys($resultArray['documents']))&&$resultArray['documents']['PASSPORT_2_3P']['error']!==''){
							$this->authentication->log_error($this->auth_phone,true);
							if(in_array('PASSPORT_2_3P',array_keys($resultArray['documents']))&&$resultArray['documents']['PASSPORT_2_3P']['error']!=''){
								$error=$resultArray['documents']['PASSPORT_2_3P']['error'];
							}else{
								$error=$this->upload->display_errors();
							}							
							return $this->output
								->set_content_type('application/json')
								->set_output(json_encode([
										'result' => false,
										'errors' => ['file-turn' => $error],
								]));							
						}else{
							if(!in_array('PASSPORT_2_3P',array_keys($resultArray['documents']))||(in_array('PASSPORT_2_3P',array_keys($resultArray['documents']))&&$resultArray['documents']['PASSPORT_2_3P']['error']!=='')){
								$uploadInfo=$this->upload->data();
								$requestData=[
									'file' => new CURLFile(realpath($uploadInfo['full_path'])),
								];
								$photos['PASSPORT_2_3P']['url']=$uploadInfo['full_path'];
								$photos['PASSPORT_2_3P']['error']='';
								$resultArray['documents']['PASSPORT_2_3P']['url']=$uploadInfo['full_path'];
								$resultArray['documents']['PASSPORT_2_3P']['error']='';
								if(ENVIRONMENT!='development'){
									$result=sendRequestPhoto($requestData,'parsePassportAutoV2');
								}
							}else{
								$result=new \stdClass;
								$result->resultCode=0;
							}
							if($result->resultCode==0){
								if(array_key_exists($this->auth_userlastname,config_item('testUsersBlackList'))){
									if(array_key_exists('file-turn',config_item('testUsersBlackList')[$this->auth_userlastname])){
										return $this->output
											->set_content_type('application/json')
											->set_output(json_encode([
													'result' => false,
													'errors' => ['file-turn' => $this->lang->line(config_item('testUsersBlackList')[$this->auth_userlastname]['file-turn'])],
											]));
									}
								}								
								$errors='';
								$userAdditionalFields=[
									'passport_issue_date' => isset($result->issueDate)?date('Y-m-d',strtotime($result->issueDate->text)):'',
									'passport_issuing_authority' => isset($result->issuingAuthority)?$result->issuingAuthority->text:'',
									'passport_subdivision_code' => isset($result->subdivisionCode)?$result->subdivisionCode->text:'',
									'birth_place' => isset($result->birthPlace)?$result->birthPlace->text:'',
									'sex' => isset($result->sex)?($result->sex->text=='МУЖ.'?1:0):'',
								];
								if(isset($result->birthDate)){
									if(date('Y-m-d',strtotime($result->birthDate->text))!=$this->auth_user_birth){
										$errors.='<p>'.$this->lang->line('Birth data in the passport does not match the data of the questionnaire').'</p>';
									}
								}
								if(isset($result->firstName)){
									if(mb_strtolower($result->firstName->text)!=mb_strtolower($this->auth_username)){
										$errors.='<p>'.$this->lang->line('First name in the passport does not match the data of the questionnaire').'</p>';
									}
								}
								if(isset($result->midName)){
									if(mb_strtolower($result->midName->text)!=mb_strtolower($this->auth_usermiddlename)){
										$errors.='<p>'.$this->lang->line('Middle name in the passport does not match the data of the questionnaire').'</p>';
									}
								}
								if(isset($result->lastName)){
									if(mb_strtolower($result->lastName->text)!=mb_strtolower($this->auth_userlastname)){
										$errors.='<p>'.$this->lang->line('Last name in the passport does not match the data of the questionnaire').'</p>';
									}
								}
								if(isset($result->passportNumber)){
									if(preg_replace('/[\\D]+/','',$result->passportNumber->text,-1,$count)!=$this->auth_user_passport){
										$errors.='<p>'.$this->lang->line('Passport number in the passport does not match the data of the questionnaire').'</p>';
									}
								}
								if($errors!=''){
									$this->authentication->log_error($this->auth_phone,true);
									$resultArray['documents']['PASSPORT_2_3P']['error']=$errors;
									$this->updateUserStep([
										'user_id' => $this->auth_user_id,
										'step' => $this->auth_current_step,
										'fields' => json_encode($resultArray),
										'status' => 1,
									]);
									return $this->output
										->set_content_type('application/json')
										->set_output(json_encode([
												'result' => false,
												'errors' => ['file-turn' => $errors],
										]));									
								}else{
									$this->updateUserStep([
										'user_id' => $this->auth_user_id,
										'step' => $this->auth_current_step,
										'fields' => json_encode($resultArray),
										'status' => 1,
									]);									
								}
							}else{
								if(isset($result->resultMessage)){
									$error=$result->resultMessage;
								}else{
									$error=$this->lang->line('Unknown error');
								}
								$photos['PASSPORT_2_3P']['error']=$error;
								$resultArray['documents']['PASSPORT_2_3P']['error']=$error;
								$this->updateUserStep([
									'user_id' => $this->auth_user_id,
									'step' => $this->auth_current_step,
									'fields' => json_encode($resultArray),
									'status' => 1,
								]);
								$this->authentication->log_error($this->auth_phone,true);
								return $this->output
									->set_content_type('application/json')
									->set_output(json_encode([
											'result' => false,
											'errors' => ['file-turn' => $error],
									]));
							}
						}
						if(!$this->upload->do_upload('file-registration')&&!in_array('PASSPORT_REG',array_keys($resultArray['documents']))){
							$this->authentication->log_error($this->auth_phone,true);
							if(in_array('PASSPORT_REG',array_keys($resultArray['documents']))&&$resultArray['documents']['PASSPORT_REG']['error']!=''){
								$error=$resultArray['documents']['PASSPORT_REG']['error'];
							}else{
								$error=$this->upload->display_errors();
							}
							$resultArray['documents']['PASSPORT_REG']['error']=$error;
							$this->updateUserStep([
								'user_id' => $this->auth_user_id,
								'step' => $this->auth_current_step,
								'fields' => json_encode($resultArray),
								'status' => 1,
							]);
							return $this->output
								->set_content_type('application/json')
								->set_output(json_encode([
										'result' => false,
										'errors' => ['file-registration' => $error],
								]));
						}elseif(!$this->upload->do_upload('file-registration')&&in_array('PASSPORT_REG',array_keys($resultArray['documents']))&&$resultArray['documents']['PASSPORT_REG']['error']!==''){
							$this->authentication->log_error($this->auth_phone,true);
							if(in_array('PASSPORT_REG',array_keys($resultArray['documents']))&&$resultArray['documents']['PASSPORT_REG']['error']!=''){
								$error=$resultArray['documents']['PASSPORT_REG']['error'];
							}else{
								$error=$this->upload->display_errors();
							}							
							return $this->output
								->set_content_type('application/json')
								->set_output(json_encode([
										'result' => false,
										'errors' => ['file-registration' => $error],
								]));							
						}else{
							if(!in_array('PASSPORT_REG',array_keys($resultArray['documents']))||(in_array('PASSPORT_REG',array_keys($resultArray['documents']))&&$resultArray['documents']['PASSPORT_REG']['error']!=='')){
								$uploadInfo=$this->upload->data();
								$requestData=[
									'file' => new CURLFile(realpath($uploadInfo['full_path'])),
								];
								$photos['PASSPORT_REG']['url']=$uploadInfo['full_path'];
								$photos['PASSPORT_REG']['error']='';
								$resultArray['documents']['PASSPORT_REG']['url']=$uploadInfo['full_path'];
								$resultArray['documents']['PASSPORT_REG']['error']='';
								$result=sendRequestPhoto($requestData,'parseDocTypeAutoV2');
							}else{
								$result=new \stdClass;
								$result->resultCode=0;
								$result->docType='passport_registration';
							}
							if($result->resultCode==0&&isset($result->docType)&&$result->docType=='passport_registration'){
								$this->updateUserStep([
									'user_id' => $this->auth_user_id,
									'step' => $this->auth_current_step,
									'fields' => json_encode($resultArray),
									'status' => 1,
								]);
								$this->setUserStep($this->auth_user_id,4);	
								$this->authentication->clear_all_login_errors();
								return $this->output
									->set_content_type('application/json')
									->set_output(json_encode([
											'result' => true,
											'redirect' => base_url('main')
									]));								
							}else{
								if(isset($result->resultMessage)&&$result->resultMessage!==''){
									$error=$result->resultMessage;
								}else{
									$error=$this->lang->line('Wrong document');
								}
								$photos['PASSPORT_REG']['error']=$error;
								$resultArray['documents']['PASSPORT_REG']['error']=$error;
								$this->updateUserStep([
									'user_id' => $this->auth_user_id,
									'step' => $this->auth_current_step,
									'fields' => json_encode($resultArray),
									'status' => 1,
								]);
								$this->authentication->log_error($this->auth_phone,true);
								return $this->output
									->set_content_type('application/json')
									->set_output(json_encode([
											'result' => false,
											'errors' => ['file-registration' => $error],
									]));								
							}
						}							
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
						$resultArray['documents']['PHOTO_SELFIE_WITH_PASSPORT']['error']=$error;
						$this->updateUserStep([
							'user_id' => $this->auth_user_id,
							'step' => $this->auth_current_step,
							'fields' => json_encode($resultArray),
							'result' => 1,
						]);
						$this->authentication->log_error($this->auth_phone,true);
						return $this->output
							->set_content_type('application/json')
							->set_output(json_encode([
									'result' => false,
									'errors' => ['file-self' => $error],
							]));
					}
				}else{
					if(isset($result->resultMessage)){
						$error=$result->resultMessage;
					}else{
						$error=$this->lang->line('Unknown error');
					}
					$photos['PHOTO_SELFIE_WITH_PASSPORT']['error']=$error;
					$resultArray['documents']['PHOTO_SELFIE_WITH_PASSPORT']['error']=$error;
					$this->updateUserStep([
						'user_id' => $this->auth_user_id,
						'step' => $this->auth_current_step,
						'fields' => json_encode($resultArray),
						'status' => 1,
					]);
					$this->authentication->log_error($this->auth_phone,true);
					return $this->output
						->set_content_type('application/json')
						->set_output(json_encode([
								'result' => false,
								'errors' => [
									'file-self' => $error
								],
						]));
				}
				$this->updateUserStep([
					'user_id' => $this->auth_user_id,
					'step' => $this->auth_current_step,
					'fields' => json_encode($resultArray),
					'status' => 1,
				]);				
			}			
		}
	}
	protected function idConfirmationStep(){
		if(!$this->input->getIsPostRequest()){
			$this->layout->data=['formUrl' => base_url('main')];
			$this->layout->viewFolder='';
			$this->layout->view='id_confirmation_step';
			$this->layout->render();
		}else{
			$this->load->library('form_validation');
			$postData=$this->security->xss_clean($this->input->raw_input_stream);
			parse_str($postData,$data);
			$this->form_validation->set_data($data);
			$this->load->library('validations');
			$validation_rules=[
				[
					'field' => 'birth_place',
					'rules' => 'required|trim|regex_match[/[а-я\d\s-\.\,:;]+/iu]',
					'errors' => [
						'required' => $this->lang->line('Birth place is required'),
						'regex_match' => $this->lang->line('Wrong format')
					]
				],[
					'field' => 'passport_issue_date',
					'rules' => [
						'trim',
						'required',
						'regex_match[/^(1[1-9][0-9][0-9]|20[0-2][0-9])-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/]',
					],
					'errors' => [
						'required' => $this->lang->line('Passport issue date is required'),
						'regex_match' => $this->lang->line('Wrong date format'),
					]
				],[
					'field' => 'passport_subdivision_code',
					'rules' => [
						'trim',
						'required',
						'regex_match[/\d{3}-\d{3}\d?$/]',
					],
					'errors' => [
						'required' => $this->lang->line('Subdivision code is required'),
						'regex_match' => $this->lang->line('Subdivision has wrong format'),
					]
				],[
					'field' => 'registration_address',
					'rules' => [
						'trim',
						[ 
							'_check_suggestion_address',
							[
								$this->validations,'_check_suggestion_address'
							] 
						]						
					],
					'errors' => [
						'required' => $this->lang->line('Registration address is required'),
						'regex_match' => $this->lang->line('Wrong format'),
					]
				],[
					'field' => 'secret',
					'rules' => [
						'trim',
						'required',
					],
					'errors' => [
						'required' => $this->lang->line('Secret word is required'),
					]
				],[
					'field' => 'passport_issuing_authority',
					'rules' => [
						'trim',
						'required',
						'regex_match[/[а-я\d\s-\.\,:;]+/iu]',
					],
					'errors' => [
						'required' => $this->lang->line('Issuing authority is required'),
						'regex_match' => $this->lang->line('Wrong format'),
					]
				],[
					'field' => 'agree_documents',
					'rules' => 'required|integer',
					'errors' => [
						'required' => $this->lang->line('It is necessary to agree with the documents')
					]
				],[
					'field' => 'agree_pass_data',
					'rules' => 'required|integer',
					'errors' => [
						'required' => $this->lang->line('You must agree to the transfer of data to third parties')
					]
				],[
					'field' => 'sex',
					'rules' => 'required|integer|in_list['.implode(array_keys(config_item('sexList')),',').']',
					'errors' => [
						'required' => $this->lang->line('You must select your sex')
					]
				],[
					'field' => 'occupation',
					'rules' => 'required|integer|in_list['.implode(array_keys(config_item('occupationList')),',').']',
					'errors' => [
						'required' => $this->lang->line('You must select your occupation')
					]
				],
			];		
			$this->form_validation->set_rules($validation_rules);
			if(!$this->form_validation->run()){
				$this->updateUserStep([
					'user_id' => $this->auth_user_id,
					'step' => $this->auth_current_step,
					'fields' => json_encode($this->form_validation->validation_data),
				]);
				$errors=$this->form_validation->error_array();
				return $this->output
					->set_content_type('application/json')
					->set_output(json_encode([
							'result' => false,
							'errors' => $errors,
					]));				
			}
			$errors=[];
			$user=$this->getUserById($this->auth_user_id);
			if($user->passport_issue_date&&$user->passport_issue_date!=$this->form_validation->validation_data['passport_issue_date']){
				// $errors['passport_issue_date']=$this->lang->line('Passport issuing dates differ from passport data');
			}		
			if($user->sex&&$user->sex!=$this->form_validation->validation_data['sex']){
				$errors['sex']=$this->lang->line('Sex differ from passport data');
			}			
			if($user->passport_issuing_authority&&$user->passport_issuing_authority!=$this->form_validation->validation_data['passport_issuing_authority']){
				$errors['passport_issuing_authority']=$this->lang->line('Passport issuing authority differ from passport data');
			}
			if($user->passport_subdivision_code&&$user->passport_subdivision_code!=$this->form_validation->validation_data['passport_subdivision_code']){
				$errors['passport_subdivision_code']=$this->lang->line('Passport subdivision code differ from passport data');
			}
			if($user->birth_place&&$user->birth_place!=$this->form_validation->validation_data['birth_place']){
				$errors['birth_place']=$this->lang->line('Birth place differ from passport data');
			}
			if(!empty($errors)){
				return $this->output
					->set_content_type('application/json')
					->set_output(json_encode([
							'result' => false,
							'errors' => $errors,
					]));				
			}
			$suggestions=$this->session->userdata('suggestions');
			$this->load->model('auth/au_model');
			$this->au_model->update_user_raw_data(
				$this->auth_user_id,[
					'birth_place' => $this->form_validation->validation_data['birth_place'],
					'passport_issue_date' => $this->form_validation->validation_data['passport_issue_date'],
					'passport_subdivision_code' => $this->form_validation->validation_data['passport_subdivision_code'],
					'registration_address' => $this->form_validation->validation_data['registration_address'],
					'secret' => $this->form_validation->validation_data['secret'],
					'sex' => $this->form_validation->validation_data['sex'],
					'passport_issuing_authority' => $this->form_validation->validation_data['passport_issuing_authority'],
					'occupation' => $this->form_validation->validation_data['occupation'],
					'registration_address_kladr' => json_encode($suggestions[$this->input->post('suggestion_address_id')]),
				]
			);	
			$this->updateUserStep([
				'user_id' => $this->auth_user_id,
				'step' => $this->auth_current_step,
				'fields' => json_encode($this->form_validation->validation_data),
				'status' => 1,
			]);
			$this->setUserStep($this->auth_user_id,5);
			$this->config->load('sms');
			$this->load->helper('sms');
			$message=substr(str_shuffle("0123456789"),0,4);
			$smsResult=sendSms($this->auth_phone,$message);
			if($smsResult instanceof \stdClass&&isset($smsResult->msg_id)){
				$this->setSmsCode($this->auth_user_id,$message);
			}else{
				print_r($smsResult);
			}			
			return $this->output
				->set_content_type('application/json')
				->set_output(json_encode([
						'result' => true,
				]));			
		}			
	}
	protected function idConfirmationSmsStep(){
		if(!$this->input->getIsPostRequest()){
			$this->layout->data=['formUrl' => base_url('main')];
			$this->layout->viewFolder='';
			$this->layout->view='id_confirmation_sms_step';
			$this->layout->render();
		}else{
			$this->load->library('form_validation');
			$postData=$this->security->xss_clean($this->input->raw_input_stream);
			parse_str($postData,$data);
			$this->form_validation->set_data($data);
			$validation_rules=[
				[
					'field' => 'code',
					'rules' => [
						'trim',
						'required',
						'integer'
					],
					'errors' => [
						'required' => $this->lang->line('Code is required'),
					]
				]			
			];		
			$this->form_validation->set_rules($validation_rules);
			if(!$this->form_validation->run()){
				$this->updateUserStep([
					'user_id' => $this->auth_user_id,
					'step' => $this->auth_current_step,
					'fields' => json_encode($this->form_validation->validation_data),
				]);					
				return $this->output
					->set_content_type('application/json')
					->set_output(json_encode([
							'result' => false,
							'errors' => $this->form_validation->error_array(),
					]));				
			}
			if($this->authentication->current_hold_status()===true){
				// return false;
			}			
			if(($user=$this->check_user_sms($this->auth_phone,$this->form_validation->validation_data['code']))==false){
				$this->authentication->log_error($this->auth_phone,true);
				$errors=['code' => $this->lang->line('Wrong code')];
				$error=$this->lang->line('Code is wrong');
				$smsTime=$this->get_user_sms_time($this->auth_user_id);
				$leftTime=0;
				if($smsTime){
					if(strtotime(date('Y-m-d H:i:s'))-strtotime($smsTime->sms_time)>60){
						$errors['code'].='. '.anchor(base_url('main/resendCode'),$this->lang->line('Resend SMS code'),['class' => 'resend-link']);
					}else{
						$leftTime=60-(strtotime(date('Y-m-d H:i:s'))-strtotime($smsTime->sms_time));
						$errors['code'].='. '.sprintf($this->lang->line('Resend SMS code available after <span>%s</span> seconds'),$leftTime);
					}
				}
				return $this->output
					->set_content_type('application/json')
					->set_output(json_encode([
							'result' => false,
							'errors' => $errors,
							'leftTime' => $leftTime,
							'resendText' => anchor(base_url('main/resendCode'),$this->lang->line('Resend SMS code'),['class' => 'resend-link']),
					]));			
			}else{
				$this->setUserStep($this->auth_user_id,6);
				return $this->output
					->set_content_type('application/json')
					->set_output(json_encode([
							'result' => true,
							'redirect' => base_url('main')
					]));				
			}
		}
	}
	protected function applicationResult(){
		if(!$this->input->getIsPostRequest()){
			$this->layout->data=['statusTest' => $this->lang->line('Your application has been approved and is pending.')];
			$this->layout->viewFolder='';
			$this->layout->view='application_result';
			$this->layout->render();
		}		
	}
	public function resendCode(){
		$this->lang->load('form','ru');
		if(($user=$this->getUserByPhonePassport($this->auth_phone,$this->auth_user_passport))==false){
			return $this->output
				->set_content_type('application/json')
				->set_output(json_encode([
						'result' => false,
						'errors' => [
							'phone' => $this->lang->line('User not found')
						],
				]));			
		}
		if($user->sms_time&&$user->current_step==5){
			if(strtotime(date('Y-m-d H:i:s'))-strtotime($user->sms_time)>60){
				$this->config->load('sms');
				$this->load->helper('sms');
				$message=substr(str_shuffle("0123456789"),0,4);
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
					return $this->output
						->set_content_type('application/json')
						->set_output(json_encode([
								'result' => false,
								'errors' => [
									'code' => $this->lang->line('Sms send error'),
								],
						]));
				}
			}
		}		
	}
	public function getAddressSuggestions(){
		$postData=$this->security->xss_clean($this->input->raw_input_stream);
		parse_str($postData,$data);
		if(!$this->input->is_ajax_request()){
			return false;
		}
		$this->config->load('dadata');
		$this->load->helper('dadata');
		$result=DADATA::sendDadataRequest(['query' => $data['text']],'address');
		$result=json_decode($result);
		if($result instanceof \stdClass&&count($result->suggestions)!==0){
			$this->session->set_userdata(['suggestions' => $result->suggestions]);
			ob_start();
			$this->load->view('_suggestions',['results' => $result->suggestions]);
			$content=ob_get_contents();
			ob_end_clean();
			return $this->output
				->set_content_type('application/json')
				->set_output(json_encode([
						'result' => true,
						'variants' => $content,
						'suggestions' => $result->suggestions,
				]));			
		}
		return $this->output
			->set_content_type('application/json')
			->set_output(json_encode([
					'result' => false,
			]));		
	}
	public function generatePhotoLink(){
		if(!$this->input->is_ajax_request()){
			return false;
		}
		$this->lang->load('form','ru');
		if(($user=$this->getUserByPhonePassport($this->auth_phone,$this->auth_user_passport))==false){
			return $this->output
				->set_content_type('application/json')
				->set_output(json_encode([
						'result' => false,
						'errors' => [
							'phone' => $this->lang->line('User not found')
						],
				]));			
		}		
		if($user->current_step!=3){
			return $this->output
				->set_content_type('application/json')
				->set_output(json_encode([
						'result' => false,
						'errors' => [
							'phone' => $this->lang->line('User not found')
						],
				]));			
		}
		if(strtotime(date('Y-m-d H:i:s'))-strtotime($user->sms_time)<60){
			$leftTime=60-(strtotime(date('Y-m-d H:i:s'))-strtotime($user->sms_time));
			return $this->output
				->set_content_type('application/json')
				->set_output(json_encode([
						'result' => true,
						'message' => sprintf($this->lang->line('Resend SMS code available after <span>%s</span> seconds'),$leftTime),
				]));
		}
		$hash=$this->authentication->hash_passwd($this->generatePassword());
		$this->updateUserData($user->user_id,['custom_hash' => $hash]);
		$tokenData=[
			'timestamp' => strtotime(date('Y-m-d H:i:s a')),
			'available_seconds' => $this->config->item('token_photo_timeout')*60,
			'exp' => strtotime(date("m/d/Y h:i:s a",time()+$this->config->item('token_photo_timeout')*60)),
			'user' => $user->user_id,
			'hash' => $hash,
			'scope' => 'photo',
			'token_type' => 'bearer'
		];
        $token=AUTHORIZATION::generateToken($tokenData);
		$link=sprintf($this->lang->line('Go to upload photos %s'),base_url('registration/photo/'.$token),$this->config->item('token_photo_timeout'));
		$link.='\r\n'.sprintf($this->lang->line('Link\'ll be abailable within %d minutes'),config_item('token_photo_timeout'));
		$this->config->load('sms');
		$this->load->helper('sms');
		$smsResult=sendSms($user->phone,$link);
		if(!$smsResult instanceof \stdClass||!isset($smsResult->msg_id)){
			return $this->output
				->set_content_type('application/json')
				->set_output(json_encode([
						'result' => true,
						'message' => $this->lang->line('Sms send error'),
						'error' => $smsResult
				]));
		}
		$this->setSmsCode($user->user_id,'');
		return $this->output
			->set_content_type('application/json')
			->set_output(json_encode([
					'result' => true,
					'message' => $this->lang->line('Successfully sent'),
			]));
	}
}