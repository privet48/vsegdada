<?php
class JWT{
	public static $timestamp = null;
	public static function decode($jwt, $key=null, $verify=true){
		$tks=explode('.',str_replace('Bearer ','',$jwt));
		if (count($tks)!= 3) {
			return false;
		}
		list($headb64,$bodyb64,$cryptob64)=$tks;
		if(null===($header=JWT::jsonDecode(JWT::urlsafeB64Decode($headb64)))){
			return false;
		}
		if (null===$payload=JWT::jsonDecode(JWT::urlsafeB64Decode($bodyb64))){
			return false;
		}
		$sig=JWT::urlsafeB64Decode($cryptob64);
		if($verify){
			if (empty($header->alg)) {
				return false;
			}
			if ($sig!= JWT::sign("$headb64.$bodyb64", $key, $header->alg, $sig)){
				// throw new UnexpectedValueException('Signature verification failed');		
				return false;
			}
		}
		return $payload;
	}
	public static function encode($payload, $key, $algo='HS256'){
		$header=['typ' => 'JWT', 'alg' => $algo];
		$segments=[];
		$segments[]=JWT::urlsafeB64Encode(JWT::jsonEncode($header));
		$segments[]=JWT::urlsafeB64Encode(JWT::jsonEncode($payload));
		$signing_input=implode('.',$segments);
		$signature=JWT::sign($signing_input, $key, $algo);
		$segments[]=JWT::urlsafeB64Encode($signature);
		return implode('.', $segments);
	}
	public static function sign($msg, $key, $method='HS256', $sig=null){
		$methods=[
			'HS256' => 'sha256',
			'HS384' => 'sha384',
			'HS512' => 'sha512',
			'RS256' => 'sha256',
		];
		if(empty($methods[$method])){
			throw new DomainException('Algorithm not supported');
		}
		if($method==='RS256'){
			$pubKey=openssl_pkey_get_public('-----BEGIN PUBLIC KEY-----'.chunk_split(config_item('jwt_key'),64,"\n").'-----END PUBLIC KEY-----');
			$success=openssl_verify($msg,$sig,$pubKey,'sha256');
			if($success===1){
				return true;
			}elseif ($success===0){
				return false;
			}
			throw new DomainException(
				'OpenSSL error: ' . openssl_error_string()
			);
		}
		return hash_hmac($methods[$method], $msg, $key, true);
	}
	public static function jsonDecode($input){
		$obj=json_decode($input);
		if(function_exists('json_last_error')&&$errno=json_last_error()){
			JWT::_handleJsonError($errno);
		} else if($obj===null&&$input!=='null'){
			throw new DomainException('Null result with non-null input');
		}
		return $obj;
	}
	public static function jsonEncode($input){
		$json=json_encode($input);
		if (function_exists('json_last_error')&&$errno=json_last_error()) {
			JWT::_handleJsonError($errno);
		} else if ($json==='null'&&$input!==null) {
			throw new DomainException('Null result with non-null input');
		}
		return $json;
	}
	public static function urlsafeB64Decode($input){
		$remainder=strlen($input) % 4;
		if ($remainder) {
			$padlen=4-$remainder;
			$input.= str_repeat('=', $padlen);
		}
		return base64_decode(strtr($input, '-_', '+/'));
	}
	public static function urlsafeB64Encode($input){
		return str_replace('=', '', strtr(base64_encode($input), '+/', '-_'));
	}
	private static function _handleJsonError($errno){
		$messages=[
			JSON_ERROR_DEPTH => 'Maximum stack depth exceeded',
			JSON_ERROR_CTRL_CHAR => 'Unexpected control character found',
			JSON_ERROR_SYNTAX => 'Syntax error, malformed JSON'
		];
		throw new DomainException(
			isset($messages[$errno])
			? $messages[$errno]
			: 'Unknown JSON error: '.$errno
		);
	}
	private function decodeRs256($jwt,$key){
        $timestamp = is_null(static::$timestamp) ? time() : static::$timestamp;

        if (empty($key)) {
            // throw new InvalidArgumentException('Key may not be empty');
        }
        $tks = explode('.', $jwt);
        if (count($tks) != 3) {
            // throw new UnexpectedValueException('Wrong number of segments');
        }
        list($headb64, $bodyb64, $cryptob64) = $tks;
        if (null === ($header = static::jsonDecode(static::urlsafeB64Decode($headb64)))) {
            throw new UnexpectedValueException('Invalid header encoding');
        }
        if (null === $payload = static::jsonDecode(static::urlsafeB64Decode($bodyb64))) {
            throw new UnexpectedValueException('Invalid claims encoding');
        }
        if (false === ($sig = static::urlsafeB64Decode($cryptob64))) {
            throw new UnexpectedValueException('Invalid signature encoding');
        }
        if (empty($header->alg)) {
            throw new UnexpectedValueException('Empty algorithm');
        }
        /*if (empty(static::$supported_algs[$header->alg])) {
            throw new UnexpectedValueException('Algorithm not supported');
        }
        if (!in_array($header->alg, $allowed_algs)) {
            throw new UnexpectedValueException('Algorithm not allowed');
        }*/
        if (is_array($key) || $key instanceof \ArrayAccess) {
            if (isset($header->kid)) {
                if (!isset($key[$header->kid])) {
                    throw new UnexpectedValueException('"kid" invalid, unable to lookup correct key');
                }
                $key = $key[$header->kid];
            } else {
                throw new UnexpectedValueException('"kid" empty, unable to lookup correct key');
            }
        }
        if (!static::verify("$headb64.$bodyb64", $sig, $key, $header->alg)) {
            throw new SignatureInvalidException('Signature verification failed');
        }
        if (isset($payload->nbf) && $payload->nbf > ($timestamp + static::$leeway)) {
            throw new BeforeValidException(
                'Cannot handle token prior to ' . date(DateTime::ISO8601, $payload->nbf)
            );
        }
        if (isset($payload->iat) && $payload->iat > ($timestamp + static::$leeway)) {
            throw new BeforeValidException(
                'Cannot handle token prior to ' . date(DateTime::ISO8601, $payload->iat)
            );
        }
        if (isset($payload->exp) && ($timestamp - static::$leeway) >= $payload->exp) {
            throw new ExpiredException('Expired token');
        }
        return $payload;		
	}
}