<?php
defined('BASEPATH') OR exit('No direct script access allowed');
require APPPATH . '/libraries/REST_Controller.php';
class Api extends REST_Controller{
	public function __construct(){
		if($_SERVER['REQUEST_METHOD'] == 'GET'&&isset($_SERVER['PHP_AUTH_USER'])){
			$this->isAuth=true;
		}
		parent::__construct('rest');
		if($_SERVER['REQUEST_METHOD'] == 'POST'||($_SERVER['REQUEST_METHOD'] == 'GET'&&!isset($_SERVER['PHP_AUTH_USER']))){
			$this->checkToken();
		}
	}
	public function get_token_get(){
		if(!isset($_SERVER['PHP_AUTH_USER'])){
			$this->set_response(['message' => 'Unauthorised'], REST_Controller::HTTP_UNAUTHORIZED);return false;
		}
		$tokenData=[];
        $tokenData['timestamp'] = strtotime(date('Y-m-d H:i:s'));
        $tokenData['available_seconds'] = $this->config->item('token_timeout')*60;
        $tokenData['exp'] = strtotime(date("m/d/Y h:i:s a",time()+30));
        $tokenData['user'] = $_SERVER['PHP_AUTH_USER'];
        $tokenData['scope'] = 'access';
        $tokenData['token_type'] = 'bearer';
        $output['access_token'] = AUTHORIZATION::generateToken($tokenData);
        $output['token_type'] = 'bearer';
        $output['scope'] = 'access';
        $this->set_response($output, REST_Controller::HTTP_OK);
    }
    public function token_post(){
        $headers = $this->input->request_headers();
        if (array_key_exists('Authorization', $headers) && !empty($headers['Authorization'])) {
            $decodedToken = AUTHORIZATION::validateTimestamp($headers['Authorization']);
            // $decodedToken = AUTHORIZATION::validateToken($headers['Authorization']);
            if ($decodedToken != false) {
                $this->set_response($decodedToken, REST_Controller::HTTP_OK);
                return;
            }
        }
        $this->set_response("Unauthorised", REST_Controller::HTTP_UNAUTHORIZED);
    }
	protected function checkToken(){
        $headers = $this->input->request_headers();
        if (array_key_exists('Authorization', $headers) && !empty($headers['Authorization'])) {
			$decodedToken = AUTHORIZATION::validateTimestamp($headers['Authorization']);
            // $decodedToken = AUTHORIZATION::validateToken($headers['Authorization']);
            if ($decodedToken != false) {
                return true;
            }
			$this->response([
				'message' => $this->lang->line('Unauthorised')
			],401);
        }		
		$this->set_response("Unauthorised", REST_Controller::HTTP_UNAUTHORIZED);
		$this->response([
			'message' => $this->lang->line('Unauthorised')
		],401);
	}
	public function getStatus_get($applicationId=null){
		$this->checkToken();
		if($applicationId==null){
			$this->response([
				'message' => $this->lang->line('Application ID is required')
			],400);
		}
		$this->load->model('auth/au_model');
		$applicationId=$this->security->xss_clean($applicationId);
		if(($application=$this->au_model->get_by_application_id($applicationId))===false){
			$this->response([
				'message' => $this->lang->line('Application not found')
			],404);
		}
		$this->config->load('idx');
		if($application->is_terrorist){
			$this->response([
				'status' => 'STATUS_DENIED'
			],400);				
		}
		if($application->current_step==count(config_item('steps'))-1){
			$this->response([
				'status' => config_item('steps')[count(config_item('steps'))-1]['name']
			],200);				
		}
		if(array_key_exists($application->current_step-1,config_item('steps'))){
			$status=config_item('steps')[$application->current_step-1]['name'];
		}else{
			if($application->current_step==1){
				$status='STATUS_REGISTRATION';
			}else{
				$status=$this->lang->line('Unknown');				
			}
		}
		if($application->current_step==0){
			$this->response([
				'status' => $status
			],400);			
		}else{
			$this->response([
				'status' => $status
			],200);			
		}
	}
	public function getDocs_get($applicationId=null){
		$this->checkToken();
		if($applicationId==null){
			$this->response([
				'message' => $this->lang->line('Application ID is required')
			],400);
		}
		$this->load->model('auth/au_model');
		$applicationId=$this->security->xss_clean($applicationId);
		if(($application=$this->au_model->get_by_application_id($applicationId))===false){
			$this->response([
				'message' => $this->lang->line('Application not found')
			],404);
		}
		if(array_key_exists($application->current_step-1,config_item('steps'))){
			if(($status=config_item('steps')[$application->current_step]['is_termanal'])===false){
				$this->response([
					'message' => $this->lang->line('The application is not in the terminal status. Need IDENTIFY_SUCCESSFUL or STATUS_DENIED'),
				],403);				
			}else{
				if(($documents=$this->au_model->get_application_documents($application->user_id))===false){
					$this->response([
						'message' => $this->lang->line('There\'s no documents'),
					],403);					
				}
				$fields=[];
				$resultArray=[];
				array_map(function($data)use(&$fields,&$resultArray){
					if($data['status']==1&&in_array($data['step'],config_item('apiDocumentsByStep'))){
						$documents=json_decode($data['fields'],true);
						if(isset($documents['documents'])){
							$resultArray=array_merge($fields,['documents' => array_keys($documents['documents'])]);
							$fields=array_keys($documents['documents']);
						}else{
							$resultArray=array_merge($fields,$documents);
							$fields=json_decode($data['fields'],true);
						}
					}
				},$documents);
				$registrationAddressKladr=json_decode($application->registration_address_kladr);
				if($registrationAddressKladr instanceof \stdClass){
					$registrationAddressKladrArray=[
						'addressFormat' => 's',
						'kladrCode' => $registrationAddressKladr->data->kladr_id,
						'country' => $registrationAddressKladr->data->country_iso_code,
						'regionType' => $registrationAddressKladr->data->region_type_full,
						'regionValue' => $registrationAddressKladr->data->region,
						'districtValue' => $registrationAddressKladr->data->federal_district,
						'townType' => $registrationAddressKladr->data->city_type_full,
						'townValue' => $registrationAddressKladr->data->city,
						'streetType' => $registrationAddressKladr->data->street_type_full,
						'streetValue' => $registrationAddressKladr->data->street,
						'houseType' => $registrationAddressKladr->data->house_type_full,
						'houseValue' => $registrationAddressKladr->data->house,
						'flatType' => $registrationAddressKladr->data->flat_type_full,
						'flatValue' => $registrationAddressKladr->data->flat,
						'blockType' => $registrationAddressKladr->data->block_type_full,
						'blockValue' => $registrationAddressKladr->data->block,
						'zipcode' => $registrationAddressKladr->data->postal_code,
					];
				}else{
					$registrationAddressKladrArray=[
						'addressFormat' => 'n',
						'address' => $application->registration_address,
					];
				}
				$resultArray['documents'][]='AGREEMENT_SES';
				$resultArray['documents'][]='BCI_PD_MKK';
				$additionalFields=[
					// 'registrationAddress' => $application->registration_address,
					'registrationAddress' => $registrationAddressKladrArray,
					'occupationTypeCode' => $application->occupation>=0?config_item('occupationList')[$application->occupation]['code']:'',
					'sex' => $application->sex,
					'placeOfBirth' => $application->birth_place,
					'orgIssue' => $application->passport_issuing_authority,
					'dateIssue' => $application->passport_issue_date,
					'codeIssue' => $application->passport_subdivision_code,
					'codeWord' => $application->secret,
				];				
				$resultArray=array_merge($additionalFields,$resultArray);
				$this->response($resultArray,200);	
			}
		}else{
			$this->response([
				'message' => $this->lang->line('The application is not in the terminal status. Need IDENTIFY_SUCCESSFUL or STATUS_DENIED'),
			],403);			
		}
	}
	public function getDoc_get($applicationId=null,$type=null){
		$this->checkToken();
		if($applicationId==null){
			$this->response([
				'message' => $this->lang->line('Application ID is required')
			],400);
		}
		$this->load->model('auth/au_model');
		$applicationId=$this->security->xss_clean($applicationId);
		if(($application=$this->au_model->get_by_application_id($applicationId))===false){
			$this->response([
				'message' => $this->lang->line('Application not found')
			],404);
		}
		if(array_key_exists($application->current_step-1,config_item('steps'))){
			if(($status=config_item('steps')[$application->current_step]['is_termanal'])===false){
				$this->response([
					'message' => $this->lang->line('The application is not in the terminal status. Need IDENTIFY_SUCCESSFUL or STATUS_DENIED'),
				],403);					
			}else{
				if(($documents=$this->au_model->get_application_documents($application->user_id))===false){
					$this->response([
						'message' => $this->lang->line('There\'s no documents'),
					],403);					
				}
				$fields=[];
				$result=false;
				array_map(function($data)use(&$type,&$result){
					$documents=json_decode($data['fields'],true);
					$documents['documents']['AGREEMENT_SES']=[
						'url' => 'assets/documents/agreeDocument.pdf',
					];
					$documents['documents']['BCI_PD_MKK']=[
						'url' => 'assets/documents/dataPassDocument.pdf',
					];
					if(isset($documents['documents'])){
						if(in_array($type,array_keys($documents['documents']))){
							$url=$documents['documents'][$type]['url'];
							$file=file_get_contents(realpath($documents['documents'][$type]['url']));
							$result['type']=$type;
							$result['document']=base64_encode($file);
							return true;
						}
					}
				},$documents);
				if(!$result){
					$this->response([
						'message' => $this->lang->line('Unsupported document type'),
					],404);					
				}
				$this->response($result,200);					
			}
		}else{
			$this->response([
				'message' => $this->lang->line('The application is not in the terminal status. Need IDENTIFY_SUCCESSFUL or STATUS_DENIED'),
			],403);			
		}
	}
	public function createPersonalCabinet_post($applicationId=null,$param2=null){
		if($applicationId==null){
			$applicationId=$this->input->get('applicationId');
		}
		if(!$this->input->getIsPostRequest()){
			return false;
		}
		if($applicationId==null){
			$this->response([
				'message' => $this->lang->line('Application ID is required')
			],400);				
		}
		$data=[];
		$data=$this->security->xss_clean($this->input->raw_input_stream);
		if(empty($data)){
			$this->response([
				'message' => $this->lang->line('Wrong parameters')
			],400);					
		}
		$data=json_decode($data,true);
		// $data=array_merge(['applicationId' => $this->uri->segment(4)],$data);
		$data=array_merge(['applicationId' => $applicationId],$data);
		$this->form_validation->set_data($data);
		$validation_rules=[
			[
				'field' => 'applicationId',
				'rules' => [
					'trim',
					'required',
					'min_length[1]',
				],
				'errors' => [
					'required' => $this->lang->line('Application ID is required'),
					'min_length' => $this->lang->line('Application ID is required')
				]
			],[
				'field' => 'mobilePhone',
				'rules' => [
					'trim',
					'required',
					'regex_match[/^[0-9]{9,10}$/]',
				],
			],[
				'field' => 'email',
				'rules' => [
					'required',
					'valid_email',
				],
			],[
				'field' => 'lastName',
				'rules' => [
					'trim',
					'required',
					'validateFullName',
				],
				'errors' => [
					'validateFullName' => $this->lang->line('Last name error')
				]
			],[
				'field' => 'firstName',
				'rules' => [
					'trim',
					'required',
					'validateFullName',
				],
				'errors' => [
					'validateFullName' => $this->lang->line('First name error')
				]
			],[
				'field' => 'patronymicName',
				'rules' => [
					'trim',
					'validateFullName',
				],
				'errors' => [
					'validateFullName' => $this->lang->line('Middlename error')
				]
			],[
				'field' => 'passportNumber',
				'rules' => [
					'trim',
					'required',
					'regex_match[/^[0-9a-zа-я]{6,10}$/ui]',
				],
			],[
				'field' => 'birthDate',
				'rules' => [
					'trim',
					'required',
					'validateDateTimestamp',
				],
				'errors' => [
					'validateDateTimestamp' => $this->lang->line('Invalid date'),
				],
			],[
				'field' => 'income',
				'rules' => [
					'trim',
					'required',
					'regex_match[/(^\d+|^\d+[.]\d+)+$/]',
				],
			],				
		];
		$this->form_validation->set_rules($validation_rules);
		if($this->form_validation->run()){
			if($this->checkApplication($this->form_validation->validation_data['applicationId'])!==false){
				$this->response([
					'message' => $this->lang->line('Application already exist')
				],400);
			}			
			if(ENVIRONMENT=='development'){
				$this->config->load('sms');
				// $data['mobilePhone']=config_item('smsTestPhone');
			}
			if(($user=$this->checkUserByPhone($this->form_validation->validation_data['mobilePhone']))!==false){
				$this->response([
					'message' => $this->lang->line('User already exist')
				],400);
			}
			if(array_key_exists($this->form_validation->validation_data['lastName'],config_item('testUsersBlackList'))){
				if(($userId=$this->auto_create_user($this->form_validation->validation_data))!==false){
					$this->config->load('sms');
					$this->load->helper('sms');
					$message=substr(str_shuffle("1111111111"),0,4);
					$this->setSmsCode($userId,$message);
				}
				$this->response([
					'personalCabinetUrl' => base_url('registration/'.$userId.'/'.$this->form_validation->validation_data['passportNumber']),
				],201);				
			}
			if(ENVIRONMENT!='development'){
				$result=sendRequest([
					'lastName' => $this->form_validation->validation_data['lastName'],
					'firstName' => $this->form_validation->validation_data['firstName'],
					'midName' => isset($this->form_validation->validation_data['patronymicName'])?$this->form_validation->validation_data['patronymicName']:'',
				],'checkTerrorist');
			}else{
				$result=new \stdClass;
				$result->found=false;
				if(in_array($this->form_validation->validation_data['lastName'],config_item('teroristList'))){
					$result->found=true;					
				}
			}
			if($result instanceof \stdClass){
				if($result->found===true){
					if(($userId=$this->auto_create_user($this->form_validation->validation_data))!==false){
						$this->load->model('auth/au_model');
						$this->au_model->update_user_raw_data(
							$userId,[
								'current_step' => 0,
								'is_terrorist' => 1,
							]
						);
						$this->response([
							'message' => $this->lang->line('User is terrorist')
						],400);						
					}else{
						$this->response([
							'message' => $this->lang->line('Error create user')
						],400);							
					}
				}
			}else{
				$this->response([
					'message' => $result,
				],400);
			}
			if(ENVIRONMENT!='development'){
				$data['phone']=$data['mobilePhone'];
				$requestData=$data;
				$requestData['birthDate']=date('d.m.Y',$this->form_validation->validation_data['birthDate']);
				$result=sendRequest($requestData,'verifyPhoneNew');
			}else{
				$result=new stdClass;
				$result->operationResult='success';
				$result->resultCode=0;
				if(in_array($this->form_validation->validation_data['lastName'],config_item('phoneList'))){
					$result->operationResult='fail';
				}
			}
			if($result->resultCode===0&&$result->operationResult=='success'){
				if(ENVIRONMENT!='development'){
					$resultEsia=sendRequest([
							'passportNumber' => $this->form_validation->validation_data['passportNumber'],
							'phone' => $this->form_validation->validation_data['mobilePhone'],
							'lastName' => $this->form_validation->validation_data['lastName'],
							'firstName' => $this->form_validation->validation_data['firstName'],
							'midName' => isset($this->form_validation->validation_data['patronymicName'])?$this->form_validation->validation_data['patronymicName']:'',
						],'checkEsia');		
					if($resultEsia instanceof \stdClass){
						if($resultEsia->resultCode!='-100'){
							if($resultEsia->esiaStatus==='verified'){
								if($resultEsia->resultCode==='0'){
									if($resultEsia->passportMatch===false){
										$this->response([
											'message' => $this->lang->line('User passport data not found in ESIA')
										],400);				
									}
								}
							}else{
								$this->response([
									'message' => $this->lang->line('User not found in ESIA')
								],400);		
							}
						}else{
							$this->response([
								'message' => $this->lang->line('User not found in ESIA')
							],400);	
						}
					}
				}else{
					if(array_key_exists($this->form_validation->validation_data['lastName'],config_item('esiaList'))){
						$this->response([
							'message' => config_item('esiaList')[$this->form_validation->validation_data['lastName']]
						],400);
					}
				}
				$code=substr(str_shuffle("0123456789"),0,4);
				$this->form_validation->validation_data['sms_code']=$code;
				if(($userId=$this->auto_create_user($this->form_validation->validation_data))!==false){
					$this->config->load('sms');
					$this->load->helper('sms');
					$message=sprintf($this->lang->line('Login to log in to your account: your mobile phone. Code: %s'),$code);
					$smsResult=sendSms($this->form_validation->validation_data['mobilePhone'],$message);
					if($smsResult instanceof \stdClass&&isset($smsResult->msg_id)){
						// $this->setSmsCode($userId,$code);
					}else{}
				}
			}else{
				if(in_array($this->form_validation->validation_data['lastName'],config_item('phoneList'))){
					$this->response([
						'message' => $this->lang->line('A bunch of names + phone is not confirmed.')
					],400);
				}
				$this->response([
					'message' => isset($result->operationMessage)?$this->lang->line($result->operationMessage):$this->lang->line($result->resultMessage),
				],400);					
			}
			$this->response([
				'personalCabinetUrl' => base_url('registration/'.$userId.'/'.$this->form_validation->validation_data['passportNumber']),
			],201);				
		}else{
			$this->response([
				'message' => $this->form_validation->error_array()
			],400);
		}
	}
	protected function checkUserByPhone($phone){
		$user=$this->db->get_where(
			'users',
			['phone' => $phone]
		);
		if($user->num_rows()>0)
			return $user->row();
		return false;		
	}
	protected function checkApplication($applicationId){
		$application=$this->db->get_where(
			'users',
			['id_vd' => $applicationId]
		);
		if($application->num_rows()>0)
			return $application->row();
		return false;		
	}
	protected function auto_create_user($data){
		$this->load->model('auth/au_model');
		$this->load->helper('auth');
		$userId=$this->au_model->get_unused_id();
		$user_data['passwd']=$this->authentication->hash_passwd($this->generatePassword());
		$user_data['user_id']=$userId;
		$user_data['created_at']=date('Y-m-d H:i:s');
		$user_data['username']=$data['firstName'];
		$user_data['userlastname']=$data['lastName'];
		$user_data['usermiddlename']=isset($data['patronymicName'])?$data['patronymicName']:'';
		$user_data['phone']=$data['mobilePhone'];
		$user_data['email']=$data['email'];
		$user_data['id_vd']=$data['applicationId'];
		$user_data['passport_id']=$data['passportNumber'];
		$user_data['birth_date']=date('Y-m-d',$data['birthDate']);
		$user_data['income']=$data['income'];
		$user_data['sms_code']=$data['sms_code'];
		$user_data['auth_level']=1;
		$this->db->set($user_data)
			->insert('users');
		if($this->db->affected_rows()==1){
			return $userId;
		}
		return false;
	}
	private function generatePassword() {
		$alphabet='abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890';
		$pass=[];
		$alphaLength=strlen($alphabet)-1;
		for($i=0;$i<config_item('max_chars_for_password');$i++){
			$n=rand(0,$alphaLength);
			$pass[]=$alphabet[$n];
		}
		return implode($pass);
	}
	protected function setSmsCode($userId,$code){
		$this->load->model('auth/au_model');
		$this->au_model->update_user_raw_data(
			$userId,[
				'sms_code' => $code,
				'sms_time' => date('Y-m-d H:i:s'),
			]
		);		
	}		
}