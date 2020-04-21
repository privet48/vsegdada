<?php
defined('BASEPATH') OR exit('No direct script access allowed');
abstract class REST_Controller extends CI_Controller{
	protected $CI;
    const HTTP_CONTINUE=100;
    const HTTP_SWITCHING_PROTOCOLS=101;
    const HTTP_PROCESSING=102;
    const HTTP_OK=200;
    const HTTP_CREATED=201;
    const HTTP_ACCEPTED=202;
    const HTTP_NON_AUTHORITATIVE_INFORMATION=203;
    const HTTP_NO_CONTENT=204;
    const HTTP_RESET_CONTENT=205;
    const HTTP_PARTIAL_CONTENT=206;
    const HTTP_MULTI_STATUS=207; 
    const HTTP_ALREADY_REPORTED=208;
    const HTTP_IM_USED=226;
    const HTTP_MULTIPLE_CHOICES=300;
    const HTTP_MOVED_PERMANENTLY=301;
    const HTTP_FOUND=302;
    const HTTP_SEE_OTHER=303;
    const HTTP_NOT_MODIFIED=304;
    const HTTP_USE_PROXY=305;
    const HTTP_RESERVED=306;
    const HTTP_TEMPORARY_REDIRECT=307;
    const HTTP_PERMANENTLY_REDIRECT=308;
    const HTTP_BAD_REQUEST=400;
    const HTTP_UNAUTHORIZED=401;
    const HTTP_PAYMENT_REQUIRED=402;
    const HTTP_FORBIDDEN=403;
    const HTTP_NOT_FOUND=404;
    const HTTP_METHOD_NOT_ALLOWED=405;
    const HTTP_NOT_ACCEPTABLE=406;
    const HTTP_PROXY_AUTHENTICATION_REQUIRED=407;
    const HTTP_REQUEST_TIMEOUT=408;
    const HTTP_CONFLICT=409;
    const HTTP_GONE=410;
    const HTTP_LENGTH_REQUIRED=411;
    const HTTP_PRECONDITION_FAILED=412;
    const HTTP_REQUEST_ENTITY_TOO_LARGE=413;
    const HTTP_REQUEST_URI_TOO_LONG=414;
    const HTTP_UNSUPPORTED_MEDIA_TYPE=415;
    const HTTP_REQUESTED_RANGE_NOT_SATISFIABLE=416;
    const HTTP_EXPECTATION_FAILED=417;
    const HTTP_I_AM_A_TEAPOT=418; 
    const HTTP_UNPROCESSABLE_ENTITY=422;
    const HTTP_LOCKED=423;
    const HTTP_FAILED_DEPENDENCY=424;
    const HTTP_RESERVED_FOR_WEBDAV_ADVANCED_COLLECTIONS_EXPIRED_PROPOSAL=425;
    const HTTP_UPGRADE_REQUIRED=426;
    const HTTP_PRECONDITION_REQUIRED=428;
    const HTTP_TOO_MANY_REQUESTS=429;
    const HTTP_REQUEST_HEADER_FIELDS_TOO_LARGE=431;
    const HTTP_INTERNAL_SERVER_ERROR=500;
    const HTTP_NOT_IMPLEMENTED=501;
    const HTTP_BAD_GATEWAY=502;
    const HTTP_SERVICE_UNAVAILABLE=503;
    const HTTP_GATEWAY_TIMEOUT=504;
    const HTTP_VERSION_NOT_SUPPORTED=505;
    const HTTP_VARIANT_ALSO_NEGOTIATES_EXPERIMENTAL=506;
    const HTTP_INSUFFICIENT_STORAGE=507;
    const HTTP_LOOP_DETECTED=508;
    const HTTP_NOT_EXTENDED=510;
    const HTTP_NETWORK_AUTHENTICATION_REQUIRED=511;
    protected $rest_format=null;
    protected $methods=[];
    protected $allowed_http_methods=['get', 'delete', 'post', 'put', 'options', 'patch', 'head'];
    protected $request=null;
    protected $response=null;
    protected $rest=null;
    protected $_get_args=[];
    protected $_post_args=[];
    protected $_put_args=[];
    protected $_delete_args=[];
    protected $_patch_args=[];
    protected $_head_args=[];
    protected $_options_args=[];
    protected $_query_args=[];
    protected $_args=[];
    protected $_insert_id='';
    protected $_allow=true;
    protected $_user_ldap_dn='';
    protected $_start_rtime='';
    protected $_end_rtime='';
    protected $_supported_formats=[
            'json' => 'application/json',
            'array' => 'application/json',
            'csv' => 'application/csv',
            'html' => 'text/html',
            'jsonp' => 'application/javascript',
            'php' => 'text/plain',
            'serialized' => 'application/vnd.php.serialized',
            'xml' => 'application/xml'
        ];
    protected $_apiuser;
    protected $check_cors=null;
    protected $_enable_xss=false;
	protected $isAuth=false;
    protected $http_status_codes=[
        self::HTTP_OK => 'OK',
        self::HTTP_CREATED => 'CREATED',
        self::HTTP_NO_CONTENT => 'NO CONTENT',
        self::HTTP_NOT_MODIFIED => 'NOT MODIFIED',
        self::HTTP_BAD_REQUEST => 'BAD REQUEST',
        self::HTTP_UNAUTHORIZED => 'UNAUTHORIZED',
        self::HTTP_FORBIDDEN => 'FORBIDDEN',
        self::HTTP_NOT_FOUND => 'NOT FOUND',
        self::HTTP_METHOD_NOT_ALLOWED => 'METHOD NOT ALLOWED',
        self::HTTP_NOT_ACCEPTABLE => 'NOT ACCEPTABLE',
        self::HTTP_CONFLICT => 'CONFLICT',
        self::HTTP_INTERNAL_SERVER_ERROR => 'INTERNAL SERVER ERROR',
        self::HTTP_NOT_IMPLEMENTED => 'NOT IMPLEMENTED'
    ];
	public $encryption;
    protected function early_checks(){}
    public function __construct($config='rest'){
        parent::__construct();
		$this->CI=& get_instance();
		$this->load->helper('idx');
		$this->config->load('idx');
		$this->load->library('form_validation');
		$this->lang->load('form','ru');
		$this->config->load('db_tables');
		$this->config->load('authentication');		
        libxml_disable_entity_loader(true);
        if(is_php('5.4')===false){
            throw new Exception('Using PHP v'.PHP_VERSION.', though PHP v5.4 or greater is required');
        }
        if(explode('.',CI_VERSION,2)[0]<3){
            throw new Exception('REST Server requires CodeIgniter 3.x');
        }
        $this->_enable_xss=($this->config->item('global_xss_filtering')===true);
        $this->output->parse_exec_vars=false;
        $this->_start_rtime=microtime(true);
        $this->load->config($config);
        $this->load->library('format');
        $supported_formats=$this->config->item('rest_supported_formats');
        if(empty($supported_formats)){
            $supported_formats=[];
        }
        if(!is_array($supported_formats)){
            $supported_formats=[$supported_formats];
        }
        $default_format=$this->_get_default_output_format();
        if(!in_array($default_format, $supported_formats)){
            $supported_formats[]=$default_format;
        }
        $this->_supported_formats=array_intersect_key($this->_supported_formats, array_flip($supported_formats));
        $language=$this->config->item('rest_language');
        if($language===null){
            $language='english';
        }
        $this->lang->load('rest_controller', $language);
        $this->request=new stdClass();
        $this->response=new stdClass();
        $this->rest=new stdClass();
        if($this->config->item('rest_ip_blacklist_enabled')===true){
            $this->_check_blacklist_auth();
        }
        $this->request->ssl=is_https();
        $this->request->method=$this->_detect_method();
        $check_cors=$this->config->item('check_cors');
        if($check_cors===true){
            $this->_check_cors();
        }
        if(isset($this->{'_'.$this->request->method.'_args'})===false){
            $this->{'_'.$this->request->method.'_args'}=[];
        }
        $this->_parse_query();
        $this->_get_args=array_merge($this->_get_args, $this->uri->ruri_to_assoc());
        $this->request->format=$this->_detect_input_format();
        $this->request->body=null;
        $this->{'_parse_' . $this->request->method}();
        if($this->request->format && $this->request->body){
            $this->request->body=$this->format->factory($this->request->body, $this->request->format)->to_array();
            $this->{'_'.$this->request->method.'_args'}=$this->request->body;
        }
        $this->_head_args=$this->input->request_headers();
        $this->_args=array_merge(
            $this->_get_args,
            $this->_options_args,
            $this->_patch_args,
            $this->_head_args,
            $this->_put_args,
            $this->_post_args,
            $this->_delete_args,
            $this->{'_'.$this->request->method.'_args'}
        );
        $this->response->format=$this->_detect_output_format();
        $this->response->lang=$this->_detect_lang();
        $this->early_checks();
        if($this->config->item('rest_database_group') && ($this->config->item('rest_enable_keys') || $this->config->item('rest_enable_logging'))){
            $this->rest->db=$this->load->database($this->config->item('rest_database_group'), true);
        }elseif(property_exists($this, 'db')){
            $this->rest->db=$this->db;
        }
        $this->auth_override=$this->_auth_override_check();
        if($this->config->item('rest_enable_keys') && $this->auth_override!==true){
            $this->_allow=$this->_detect_api_key();
        }
        if($this->input->is_ajax_request()===false && $this->config->item('rest_ajax_only')){
            $this->response([
                    $this->config->item('rest_status_field_name') => false,
                    $this->config->item('rest_message_field_name') => $this->lang->line('text_rest_ajax_only')
                ], self::HTTP_NOT_ACCEPTABLE);
        }
        if($this->auth_override===false&&(!($this->config->item('rest_enable_keys') && $this->_allow===true)||($this->config->item('allow_auth_and_keys')===true && $this->_allow===true))){
            $rest_auth=strtolower($this->config->item('rest_auth'));
			if($this->isAuth){
				$rest_auth='basic';
			}
            switch ($rest_auth){
                case 'basic':
                    $this->_prepare_basic_auth();
                    break;
                case 'digest':
                    $this->_prepare_digest_auth();
                    break;
                case 'session':
                    $this->_check_php_session();
                    break;
            }
            if($this->config->item('rest_ip_whitelist_enabled')===true){
                $this->_check_whitelist_auth();
            }
        }
    }
    public function __destruct(){
        $this->_end_rtime=microtime(true);
        if($this->config->item('rest_enable_logging')===true){
            $this->_log_access_time();
        }
    }
    public function _remap($object_called, $arguments=[]){
        if($this->config->item('force_https') && $this->request->ssl===false){
            $this->response([
                    $this->config->item('rest_status_field_name') => false,
                    $this->config->item('rest_message_field_name') => $this->lang->line('text_rest_unsupported')
                ], self::HTTP_FORBIDDEN);
        }
        $object_called=preg_replace('/^(.*)\.(?:'.implode('|', array_keys($this->_supported_formats)).')$/', '$1', $object_called);
        $controller_method=$object_called.'_'.$this->request->method;
        $log_method=! (isset($this->methods[$controller_method]['log']) && $this->methods[$controller_method]['log']===false);
        $use_key=! (isset($this->methods[$controller_method]['key']) && $this->methods[$controller_method]['key']===false);
        if($this->config->item('rest_enable_keys') && $use_key && $this->_allow===false){
            if($this->config->item('rest_enable_logging') && $log_method){
                $this->_log_request();
            }
            $this->response([
                    $this->config->item('rest_status_field_name') => false,
                    $this->config->item('rest_message_field_name') => sprintf($this->lang->line('text_rest_invalid_api_key'), $this->rest->key)
                ], self::HTTP_FORBIDDEN);
        }
        if($this->config->item('rest_enable_keys') && $use_key && empty($this->rest->key)===false && $this->_check_access()===false){
            if($this->config->item('rest_enable_logging') && $log_method){
                $this->_log_request();
            }
            $this->response([
                    $this->config->item('rest_status_field_name') => false,
                    $this->config->item('rest_message_field_name') => $this->lang->line('text_rest_api_key_unauthorized')
                ], self::HTTP_UNAUTHORIZED);
        }
        if(!method_exists($this, $controller_method)){
            $this->response([
                    $this->config->item('rest_status_field_name') => false,
                    $this->config->item('rest_message_field_name') => $this->lang->line('text_rest_unknown_method')
                ], self::HTTP_METHOD_NOT_ALLOWED);
        }
        if($this->config->item('rest_enable_keys') && empty($this->rest->key)===false){
            if($this->config->item('rest_enable_limits') && $this->_check_limit($controller_method)===false){
                $response=[$this->config->item('rest_status_field_name') => false, $this->config->item('rest_message_field_name') => $this->lang->line('text_rest_api_key_time_limit')];
                $this->response($response, self::HTTP_UNAUTHORIZED);
            }
            $level=isset($this->methods[$controller_method]['level']) ? $this->methods[$controller_method]['level'] : 0;
            $authorized=$level <= $this->rest->level;
            if($this->config->item('rest_enable_logging') && $log_method){
                $this->_log_request($authorized);
            }
            if($authorized===false){
                $response=[$this->config->item('rest_status_field_name') => false, $this->config->item('rest_message_field_name') => $this->lang->line('text_rest_api_key_permissions')];
                $this->response($response, self::HTTP_UNAUTHORIZED);
            }
        }elseif($this->config->item('rest_enable_logging') && $log_method){
            $this->_log_request($authorized=true);
        }
        try{
            call_user_func_array([$this, $controller_method], $arguments);
        }catch (Exception $ex){
            $this->response([
                    $this->config->item('rest_status_field_name') => false,
                    $this->config->item('rest_message_field_name') => [
                        'classname' => get_class($ex),
                        'message' => $ex->getMessage()
                    ]
                ], self::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    public function response($data=null, $http_code=null, $continue=false){
        if($http_code!==null){
            $http_code=(int) $http_code;
        }
        $output=null;
        if($data===null && $http_code===null){
            $http_code=self::HTTP_NOT_FOUND;
        }elseif($data!==null){
            if(method_exists($this->format, 'to_' . $this->response->format)){
                $this->output->set_content_type($this->_supported_formats[$this->response->format], strtolower($this->config->item('charset')));
                $output=$this->format->factory($data)->{'to_' . $this->response->format}();
                if($this->response->format==='array'){
                    $output=$this->format->factory($output)->{'to_json'}();
                }
            }else{
                if(is_array($data) || is_object($data)){
                    $data=$this->format->factory($data)->{'to_json'}();
                }
                $output=$data;
            }
        }
        $http_code > 0 || $http_code=self::HTTP_OK;
        $this->output->set_status_header($http_code);
        if($this->config->item('rest_enable_logging')===true){
            $this->_log_response_code($http_code);
        }
        $this->output->set_output($output);
        if($continue===false){
            $this->output->_display();
            exit;
        }
    }
    public function set_response($data=null, $http_code=null){
        $this->response($data, $http_code, true);
    }
    protected function _detect_input_format(){
        $content_type=$this->input->server('CONTENT_TYPE');
        if(empty($content_type)===false){
            $content_type=(strpos($content_type, ';')!==false ? current(explode(';', $content_type)) : $content_type);
            foreach ($this->_supported_formats as $type => $mime){
                if($content_type===$mime){
                    return $type;
                }
            }
        }
        return null;
    }
    protected function _get_default_output_format(){
        $default_format=(string) $this->config->item('rest_default_format');
        return $default_format==='' ? 'json' : $default_format;
    }
    protected function _detect_output_format(){
        $pattern='/\.('.implode('|', array_keys($this->_supported_formats)).')($|\/)/';
        $matches=[];
        if(preg_match($pattern, $this->uri->uri_string(), $matches)){
            return $matches[1];
        }
        if(isset($this->_get_args['format'])){
            $format=strtolower($this->_get_args['format']);
            if(isset($this->_supported_formats[$format])===true){
                return $format;
            }
        }
        $http_accept=$this->input->server('HTTP_ACCEPT');
        if($this->config->item('rest_ignore_http_accept')===false && $http_accept!==null){
            foreach (array_keys($this->_supported_formats) as $format){
                if(strpos($http_accept, $format)!==false){
                    if($format!=='html' && $format!=='xml'){
                        return $format;
                    }elseif($format==='html' && strpos($http_accept, 'xml')===false){
                        return $format;
                    }else if($format==='xml' && strpos($http_accept, 'html')===false){
                        return $format;
                    }
                }
            }
        }
        if(empty($this->rest_format)===false){
            return $this->rest_format;
        }
        return $this->_get_default_output_format();
    }
    protected function _detect_method(){
        $method=null;
        if($this->config->item('enable_emulate_request')===true){
            $method=$this->input->post('_method');
            if($method===null){
                $method=$this->input->server('HTTP_X_HTTP_METHOD_OVERRIDE');
            }
            $method=strtolower($method);
        }
        if(empty($method)){
            $method=$this->input->method();
        }
        return in_array($method, $this->allowed_http_methods) && method_exists($this, '_parse_' . $method) ? $method : 'get';
    }
    protected function _detect_api_key(){
        $api_key_variable=$this->config->item('rest_key_name');
        $key_name='HTTP_' . strtoupper(str_replace('-', '_', $api_key_variable));
        $this->rest->key=null;
        $this->rest->level=null;
        $this->rest->user_id=null;
        $this->rest->ignore_limits=false;
        if(($key=isset($this->_args[$api_key_variable]) ? $this->_args[$api_key_variable] : $this->input->server($key_name))){
            if(!($row=$this->rest->db->where($this->config->item('rest_key_column'), $key)->get($this->config->item('rest_keys_table'))->row())){
                return false;
            }
            $this->rest->key=$row->{$this->config->item('rest_key_column')};
            isset($row->user_id) && $this->rest->user_id=$row->user_id;
            isset($row->level) && $this->rest->level=$row->level;
            isset($row->ignore_limits) && $this->rest->ignore_limits=$row->ignore_limits;
            $this->_apiuser=$row;
            if(empty($row->is_private_key)===false){
                if(isset($row->ip_addresses)){
                    $list_ip_addresses=explode(',', $row->ip_addresses);
                    $found_address=false;
                    foreach ($list_ip_addresses as $ip_address){
                        if($this->input->ip_address()===trim($ip_address)){
                            $found_address=true;
                            break;
                        }
                    }
                    return $found_address;
                }else{
                    return false;
                }
            }
            return true;
        }
        return false;
    }
    protected function _detect_lang(){
        $lang=$this->input->server('HTTP_ACCEPT_LANGUAGE');
        if($lang===null){
            return null;
        }
        if(strpos($lang, ',')!==false){
            $langs=explode(',', $lang);
            $return_langs=[];
            foreach ($langs as $lang){
                list($lang)=explode(';', $lang);
                $return_langs[]=trim($lang);
            }
            return $return_langs;
        }
        return $lang;
    }
    protected function _log_request($authorized=false){
        $is_inserted=$this->rest->db
            ->insert(
                $this->config->item('rest_logs_table'), [
                'uri' => $this->uri->uri_string(),
                'method' => $this->request->method,
                'params' => $this->_args ? ($this->config->item('rest_logs_json_params')===true ? json_encode($this->_args) : serialize($this->_args)) : null,
                'api_key' => isset($this->rest->key) ? $this->rest->key : '',
                'ip_address' => $this->input->ip_address(),
                'time' => time(),
                'authorized' => $authorized
            ]);
        $this->_insert_id=$this->rest->db->insert_id();
        return $is_inserted;
    }
    protected function _check_limit($controller_method){
        if(empty($this->rest->ignore_limits)===false){
            return true;
        }
        switch ($this->config->item('rest_limits_method')){
          case 'API_KEY':
            $limited_uri='api-key:' . (isset($this->rest->key) ? $this->rest->key : '');
            $limited_method_name=isset($this->rest->key) ? $this->rest->key : '';
            break;
          case 'METHOD_NAME':
            $limited_uri='method-name:' . $controller_method;
            $limited_method_name= $controller_method;
            break;
          case 'ROUTED_URL':
          default:
            $limited_uri=$this->uri->ruri_string();
            if(strpos(strrev($limited_uri), strrev($this->response->format))===0)
            {
                $limited_uri=substr($limited_uri,0, -strlen($this->response->format) - 1);
            }
            $limited_uri='uri:'.$limited_uri.':'.$this->request->method; // It's good to differentiate GET from PUT
            $limited_method_name=$controller_method;
            break;
        }
        if(isset($this->methods[$limited_method_name]['limit'])===false){
            return true;
        }
        $limit=$this->methods[$limited_method_name]['limit'];
        $time_limit=(isset($this->methods[$limited_method_name]['time']) ? $this->methods[$limited_method_name]['time'] : 3600);
        $result=$this->rest->db
            ->where('uri', $limited_uri)
            ->where('api_key', $this->rest->key)
            ->get($this->config->item('rest_limits_table'))
            ->row();
        if($result===null){
            $this->rest->db->insert($this->config->item('rest_limits_table'), [
                'uri' => $limited_uri,
                'api_key' => isset($this->rest->key) ? $this->rest->key : '',
                'count' => 1,
                'hour_started' => time()
            ]);
        }elseif($result->hour_started<(time() - $time_limit)){
            $this->rest->db
                ->where('uri', $limited_uri)
                ->where('api_key', isset($this->rest->key) ? $this->rest->key : '')
                ->set('hour_started', time())
                ->set('count', 1)
                ->update($this->config->item('rest_limits_table'));
        }else{
            if($result->count >= $limit){
                return false;
            }
            $this->rest->db
                ->where('uri', $limited_uri)
                ->where('api_key', $this->rest->key)
                ->set('count', 'count + 1', false)
                ->update($this->config->item('rest_limits_table'));
        }
        return true;
    }
    protected function _auth_override_check(){
        $auth_override_class_method=$this->config->item('auth_override_class_method');
        if(!empty($auth_override_class_method)){
            if(!empty($auth_override_class_method[$this->router->class]['*'])){
                if($auth_override_class_method[$this->router->class]['*']==='none'){
                    return true;
                }
                if($auth_override_class_method[$this->router->class]['*']==='basic'){
                    $this->_prepare_basic_auth();
                    return true;
                }
                if($auth_override_class_method[$this->router->class]['*']==='digest'){
                    $this->_prepare_digest_auth();
                    return true;
                }
                if($auth_override_class_method[$this->router->class]['*']==='session'){
                    $this->_check_php_session();
                    return true;
                }
                if($auth_override_class_method[$this->router->class]['*']==='whitelist'){
                    $this->_check_whitelist_auth();
                    return true;
                }
            }
            if(!empty($auth_override_class_method[$this->router->class][$this->router->method])){
                if($auth_override_class_method[$this->router->class][$this->router->method]==='none'){
                    return true;
                }
                if($auth_override_class_method[$this->router->class][$this->router->method]==='basic'){
                    $this->_prepare_basic_auth();
                    return true;
                }
                if($auth_override_class_method[$this->router->class][$this->router->method]==='digest'){
                    $this->_prepare_digest_auth();
                    return true;
                }
                if($auth_override_class_method[$this->router->class][$this->router->method]==='session'){
                    $this->_check_php_session();
                    return true;
                }
                if($auth_override_class_method[$this->router->class][$this->router->method]==='whitelist'){
                    $this->_check_whitelist_auth();
                    return true;
                }
            }
        }
        $auth_override_class_method_http=$this->config->item('auth_override_class_method_http');
        if(!empty($auth_override_class_method_http)){
            if(!empty($auth_override_class_method_http[$this->router->class]['*'][$this->request->method])){
                if($auth_override_class_method_http[$this->router->class]['*'][$this->request->method]==='none'){
                    return true;
                }
                if($auth_override_class_method_http[$this->router->class]['*'][$this->request->method]==='basic'){
                    $this->_prepare_basic_auth();
                    return true;
                }
                if($auth_override_class_method_http[$this->router->class]['*'][$this->request->method]==='digest'){
                    $this->_prepare_digest_auth();
                    return true;
                }
                if($auth_override_class_method_http[$this->router->class]['*'][$this->request->method]==='session'){
                    $this->_check_php_session();
                    return true;
                }
                if($auth_override_class_method_http[$this->router->class]['*'][$this->request->method]==='whitelist'){
                    $this->_check_whitelist_auth();
                    return true;
                }
            }
            if(!empty($auth_override_class_method_http[$this->router->class][$this->router->method][$this->request->method])){
                if($auth_override_class_method_http[$this->router->class][$this->router->method][$this->request->method]==='none'){
                    return true;
                }
                if($auth_override_class_method_http[$this->router->class][$this->router->method][$this->request->method]==='basic'){
                    $this->_prepare_basic_auth();
                    return true;
                }
                if($auth_override_class_method_http[$this->router->class][$this->router->method][$this->request->method]==='digest'){
                    $this->_prepare_digest_auth();
                    return true;
                }
                if($auth_override_class_method_http[$this->router->class][$this->router->method][$this->request->method]==='session'){
                    $this->_check_php_session();
                    return true;
                }
                if($auth_override_class_method_http[$this->router->class][$this->router->method][$this->request->method]==='whitelist'){
                    $this->_check_whitelist_auth();
                    return true;
                }
            }
        }
        return false;
    }
    protected function _parse_get(){
        $this->_get_args=array_merge($this->_get_args, $this->_query_args);
    }
    protected function _parse_post(){
        $this->_post_args=$_POST;
        if($this->request->format){
            $this->request->body=$this->input->raw_input_stream;
        }
    }
    protected function _parse_put(){
        if($this->request->format){
            $this->request->body=$this->input->raw_input_stream;
            if($this->request->format==='json'){
                $this->_put_args=json_decode($this->input->raw_input_stream);
            }
        }else if($this->input->method()==='put'){
           $this->_put_args=$this->input->input_stream();
        }
    }
    protected function _parse_head(){
        parse_str(parse_url($this->input->server('REQUEST_URI'), PHP_URL_QUERY), $head);
        $this->_head_args=array_merge($this->_head_args, $head);
    }
    protected function _parse_options(){
        parse_str(parse_url($this->input->server('REQUEST_URI'), PHP_URL_QUERY), $options);
        $this->_options_args=array_merge($this->_options_args, $options);
    }
    protected function _parse_patch(){
        if($this->request->format){
            $this->request->body=$this->input->raw_input_stream;
        }else if($this->input->method()==='patch'){
            $this->_patch_args=$this->input->input_stream();
        }
    }
    protected function _parse_delete(){
        if($this->input->method()==='delete'){
            $this->_delete_args=$this->input->input_stream();
        }
    }	
    protected function _parse_query(){
        $this->_query_args=$this->input->get();
    }
    public function get($key=null, $xss_clean=null){
        if($key===null){
            return $this->_get_args;
        }
        return isset($this->_get_args[$key]) ? $this->_xss_clean($this->_get_args[$key], $xss_clean) : null;
    }
    public function options($key=null, $xss_clean=null){
        if($key===null){
            return $this->_options_args;
        }
        return isset($this->_options_args[$key]) ? $this->_xss_clean($this->_options_args[$key], $xss_clean) : null;
    }
    public function head($key=null, $xss_clean=null){
        if($key===null){
            return $this->_head_args;
        }
        return isset($this->_head_args[$key]) ? $this->_xss_clean($this->_head_args[$key], $xss_clean) : null;
    }
    public function post($key=null, $xss_clean=null){
        if($key===null){
            return $this->_post_args;
        }
        return isset($this->_post_args[$key]) ? $this->_xss_clean($this->_post_args[$key], $xss_clean) : null;
    }
    public function put($key=null, $xss_clean=null){
        if($key===null){
            return $this->_put_args;
        }
        return isset($this->_put_args[$key]) ? $this->_xss_clean($this->_put_args[$key], $xss_clean) : null;
    }
    public function delete($key=null, $xss_clean=null){
        if($key===null){
            return $this->_delete_args;
        }
        return isset($this->_delete_args[$key]) ? $this->_xss_clean($this->_delete_args[$key], $xss_clean) : null;
    }
    public function patch($key=null, $xss_clean=null){
        if($key===null){
            return $this->_patch_args;
        }
        return isset($this->_patch_args[$key]) ? $this->_xss_clean($this->_patch_args[$key], $xss_clean) : null;
    }
    public function query($key=null, $xss_clean=null){
        if($key===null){
            return $this->_query_args;
        }
        return isset($this->_query_args[$key]) ? $this->_xss_clean($this->_query_args[$key], $xss_clean) : null;
    }
    protected function _xss_clean($value, $xss_clean){
        is_bool($xss_clean) || $xss_clean=$this->_enable_xss;
        return $xss_clean===true ? $this->security->xss_clean($value) : $value;
    }
    public function validation_errors(){
        $string=strip_tags($this->form_validation->error_string());
        return explode(PHP_EOL, trim($string, PHP_EOL));
    }
    protected function _perform_ldap_auth($username='', $password=null){
        if(empty($username)){
            log_message('debug', 'LDAP Auth: failure, empty username');
            return false;
        }
        log_message('debug', 'LDAP Auth: Loading configuration');
        $this->config->load('ldap.php', true);
        $ldap=[
            'timeout' => $this->config->item('timeout', 'ldap'),
            'host' => $this->config->item('server', 'ldap'),
            'port' => $this->config->item('port', 'ldap'),
            'rdn' => $this->config->item('binduser', 'ldap'),
            'pass' => $this->config->item('bindpw', 'ldap'),
            'basedn' => $this->config->item('basedn', 'ldap'),
        ];
        log_message('debug', 'LDAP Auth: Connect to ' . (isset($ldaphost) ? $ldaphost : '[ldap not configured]'));
        $ldapconn=ldap_connect($ldap['host'], $ldap['port']);
        if($ldapconn){
            log_message('debug', 'Setting timeout to '.$ldap['timeout'].' seconds');
            ldap_set_option($ldapconn, LDAP_OPT_NETWORK_TIMEOUT, $ldap['timeout']);
            log_message('debug', 'LDAP Auth: Binding to '.$ldap['host'].' with dn '.$ldap['rdn']);
            $ldapbind=ldap_bind($ldapconn, $ldap['rdn'], $ldap['pass']);
            if($ldapbind===false){
                log_message('error', 'LDAP Auth: bind was unsuccessful');
                return false;
            }
            log_message('debug', 'LDAP Auth: bind successful');
        }
        if(($res_id=ldap_search($ldapconn, $ldap['basedn'], "uid=$username"))===false){
            log_message('error', 'LDAP Auth: User '.$username.' not found in search');
            return false;
        }
        if(ldap_count_entries($ldapconn, $res_id)!==1){
            log_message('error', 'LDAP Auth: Failure, username '.$username.'found more than once');
            return false;
        }
        if(($entry_id=ldap_first_entry($ldapconn, $res_id))===false){
            log_message('error', 'LDAP Auth: Failure, entry of search result could not be fetched');
            return false;
        }
        if(($user_dn=ldap_get_dn($ldapconn, $entry_id))===false){
            log_message('error', 'LDAP Auth: Failure, user-dn could not be fetched');
            return false;
        }
        if(($link_id=ldap_bind($ldapconn, $user_dn, $password))===false){
            log_message('error', 'LDAP Auth: Failure, username/password did not match: ' . $user_dn);
            return false;
        }
        log_message('debug', 'LDAP Auth: Success '.$user_dn.' authenticated successfully');
        $this->_user_ldap_dn=$user_dn;
        ldap_close($ldapconn);
        return true;
    }
    protected function _perform_library_auth($username='', $password=null){
        if(empty($username)){
            log_message('error', 'Library Auth: Failure, empty username');
            return false;
        }
        $auth_library_class=strtolower($this->config->item('auth_library_class'));
        $auth_library_function=strtolower($this->config->item('auth_library_function'));
        if(empty($auth_library_class)){
            log_message('debug', 'Library Auth: Failure, empty auth_library_class');
            return false;
        }
        if(empty($auth_library_function)){
            log_message('debug', 'Library Auth: Failure, empty auth_library_function');
            return false;
        }
        if(is_callable([$auth_library_class, $auth_library_function])===false){
            $this->load->library($auth_library_class);
        }
        return $this->{$auth_library_class}->$auth_library_function($username, $password);
    }
    protected function _check_login($username=null, $password=false){
        if(empty($username)){
            return false;
        }
        $auth_source=strtolower($this->config->item('auth_source'));
        $rest_auth=strtolower($this->config->item('rest_auth'));
        $valid_logins=$this->config->item('rest_valid_logins');
        if(!$this->config->item('auth_source') && $rest_auth==='digest'){
            return md5($username.':'.$this->config->item('rest_realm').':'.(isset($valid_logins[$username]) ? $valid_logins[$username] : ''));
        }
        if($password===false){
            return false;
        }
        if($auth_source==='ldap'){
            log_message('debug', "Performing LDAP authentication for $username");
            return $this->_perform_ldap_auth($username, $password);
        }
        if($auth_source==='library'){
            log_message('debug', "Performing Library authentication for $username");
            return $this->_perform_library_auth($username, $password);
        }
        if(array_key_exists($username, $valid_logins)===false){
            return false;
        }
        if($valid_logins[$username]!==$password){
            return false;
        }
        return true;
    }
    protected function _check_php_session(){
        $key=$this->config->item('auth_source');
        if(!$this->session->userdata($key)){
            $this->response([
                    $this->config->item('rest_status_field_name') => false,
                    $this->config->item('rest_message_field_name') => $this->lang->line('text_rest_unauthorized')
                ], self::HTTP_UNAUTHORIZED);
        }
    }
    protected function _prepare_basic_auth(){
        if($this->config->item('rest_ip_whitelist_enabled')){
            $this->_check_whitelist_auth();
        }
        $username=$this->input->server('PHP_AUTH_USER');
        $http_auth=$this->input->server('HTTP_AUTHENTICATION');
        $password=null;
        if($username!==null){
            $password=$this->input->server('PHP_AUTH_PW');
        }elseif($http_auth!==null){
            if(strpos(strtolower($http_auth), 'basic')===0){
                list($username, $password)=explode(':', base64_decode(substr($this->input->server('HTTP_AUTHORIZATION'), 6)));
            }
        }
        if($this->_check_login($username, $password)===false){
			$this->_force_login();
        }
    }
    protected function _prepare_digest_auth(){
        if($this->config->item('rest_ip_whitelist_enabled')){
            $this->_check_whitelist_auth();
        }
        $digest_string=$this->input->server('PHP_AUTH_DIGEST');
        if($digest_string===null){
            $digest_string=$this->input->server('HTTP_AUTHORIZATION');
        }
        $unique_id=uniqid();
        if(empty($digest_string)){
            $this->_force_login($unique_id);
        }
        $matches=[];
        preg_match_all('@(username|nonce|uri|nc|cnonce|qop|response)=[\'"]?([^\'",]+)@', $digest_string, $matches);
        $digest=(empty($matches[1]) || empty($matches[2])) ? [] : array_combine($matches[1], $matches[2]);
        $username=$this->_check_login($digest['username'], true);
        if(array_key_exists('username', $digest)===false || $username===false){
            $this->_force_login($unique_id);
        }
        $md5=md5(strtoupper($this->request->method).':'.$digest['uri']);
        $valid_response=md5($username.':'.$digest['nonce'].':'.$digest['nc'].':'.$digest['cnonce'].':'.$digest['qop'].':'.$md5);
        if(strcasecmp($digest['response'], $valid_response)!==0){
            $this->response([
                    $this->config->item('rest_status_field_name') => false,
                    $this->config->item('rest_message_field_name') => $this->lang->line('text_rest_invalid_credentials')
                ], self::HTTP_UNAUTHORIZED);
        }
    }
    protected function _check_blacklist_auth(){
        $pattern=sprintf('/(?:,\s*|^)\Q%s\E(?=,\s*|$)/m', $this->input->ip_address());
        if(preg_match($pattern, $this->config->item('rest_ip_blacklist'))){
            $this->response([
                    $this->config->item('rest_status_field_name') => false,
                    $this->config->item('rest_message_field_name') => $this->lang->line('text_rest_ip_denied')
                ], self::HTTP_UNAUTHORIZED);
        }
    }
    protected function _check_whitelist_auth(){
        $whitelist=explode(',', $this->config->item('rest_ip_whitelist'));
        array_push($whitelist, '127.0.0.1', '0.0.0.0');
        foreach ($whitelist as &$ip){
            $ip=trim($ip);
        }
        if(in_array($this->input->ip_address(), $whitelist)===false){
            $this->response([
                    $this->config->item('rest_status_field_name') => false,
                    $this->config->item('rest_message_field_name') => $this->lang->line('text_rest_ip_unauthorized')
                ], self::HTTP_UNAUTHORIZED);
        }
    }
    protected function _force_login($nonce=''){
        $rest_auth=$this->config->item('rest_auth');
        $rest_realm=$this->config->item('rest_realm');
        if(strtolower($rest_auth)==='basic'){
            header('WWW-Authenticate: Basic realm="'.$rest_realm.'"');
        }elseif(strtolower($rest_auth)==='digest'){
            header(
                'WWW-Authenticate: Digest realm="'.$rest_realm
                .'", qop="auth", nonce="'.$nonce
                .'", opaque="' . md5($rest_realm).'"');
        }
        $this->response([
                $this->config->item('rest_status_field_name') => false,
                $this->config->item('rest_message_field_name') => $this->lang->line('text_rest_unauthorized')
            ], self::HTTP_UNAUTHORIZED);
    }
    protected function _log_access_time(){
        $payload['rtime']=$this->_end_rtime - $this->_start_rtime;
        return $this->rest->db->update(
                $this->config->item('rest_logs_table'), $payload, [
                'id' => $this->_insert_id
            ]);
    }
    protected function _log_response_code($http_code){
        $payload['response_code']=$http_code;
        return $this->rest->db->update(
            $this->config->item('rest_logs_table'), $payload, [
            'id' => $this->_insert_id
        ]);
    }
    protected function _check_access(){
        if($this->config->item('rest_enable_access')===false){
            return true;
        }
        $controller=implode(
            '/', [
            $this->router->directory,
            $this->router->class
        ]);
        $controller=str_replace('//', '/', $controller);
        return $this->rest->db
            ->where('key', $this->rest->key)
            ->where('controller', $controller)
            ->get($this->config->item('rest_access_table'))
            ->num_rows() > 0;
    }
    protected function _check_cors(){
        $allowed_headers=implode(' ,', $this->config->item('allowed_cors_headers'));
        $allowed_methods=implode(' ,', $this->config->item('allowed_cors_methods'));
        if($this->config->item('allow_any_cors_domain')===true){
            header('Access-Control-Allow-Origin: *');
            header('Access-Control-Allow-Headers: '.$allowed_headers);
            header('Access-Control-Allow-Methods: '.$allowed_methods);
        }else{
            $origin=$this->input->server('HTTP_ORIGIN');
            if($origin===null){
                $origin='';
            }
            if(in_array($origin, $this->config->item('allowed_cors_origins'))){
                header('Access-Control-Allow-Origin: '.$origin);
                header('Access-Control-Allow-Headers: '.$allowed_headers);
                header('Access-Control-Allow-Methods: '.$allowed_methods);
            }
        }
        if($this->input->method()==='options'){
            exit;
        }
    }
}