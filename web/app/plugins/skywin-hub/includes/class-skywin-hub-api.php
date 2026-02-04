<?php
if ( !defined('ABSPATH') ) {
	exit;
}

if ( !class_exists('Skywin_Hub_API') ) :
class Skywin_Hub_API {
	protected static $_instance = null;
	public static function instance() {
		if ( is_null(self::$_instance) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}
	private function apiCall($requestMethod, $entity='', $body = null)
	{
		$username = get_option('skywin_hub_api_username');
		$password = get_option('skywin_hub_api_password');
		$host     = get_option('skywin_hub_api_host');
		$port     = get_option('skywin_hub_api_port');
		$path     = get_option('skywin_hub_api_path');
		$password = encrypt_decrypt($password, 'd');
		
		$endpoint 	= "{$host}";
		if( $port ){
			$endpoint .= ":{$port}";
		}
		$endpoint .= "{$path}{$entity}";
		if( !$username || !$password || !$host){
			$error = new WP_Error('error', __('Api Credentials can not be empty.', 'skywin_hub') );
			error_log('Api error: ' . json_encode($error));
			return $error;
		}
		$curl = curl_init();
		
		$headers = array(
			'Content-Type: plain/text',
			'Accept: application/json'
		);
		curl_setopt($curl, CURLOPT_URL, $endpoint);
		curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true); 
		curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $requestMethod);
		curl_setopt($curl, CURLOPT_ENCODING, '');
		curl_setopt($curl, CURLOPT_MAXREDIRS, 10);
		curl_setopt($curl, CURLOPT_TIMEOUT, 10);
		curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 10);
		curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($curl, CURLOPT_USERPWD, "$username:$password");

		if ($requestMethod == 'POST' || $requestMethod == 'PUT') {
			curl_setopt($curl, CURLOPT_POSTFIELDS,  json_encode($body)  );
		}
		
		if ($requestMethod == 'HEAD') {
			curl_setopt($curl, CURLOPT_NOBODY, true);
		}
		
		$results = curl_exec($curl);
		$curl_info = curl_getinfo($curl);
		$info = [
			'http_code'  => $curl_info['http_code'] ?? null,
			'dns'        => $curl_info['namelookup_time'] ?? null,
			'connect'    => $curl_info['connect_time'] ?? null,
			'tls'        => $curl_info['appconnect_time'] ?? null,
			'ttfb'       => $curl_info['starttransfer_time'] ?? null,
			'total'      => $curl_info['total_time'] ?? null,
		];
		$httpCode = $curl_info['http_code'];
		if ( curl_errno( $curl )  ){
			error_log('curlerr: ' . json_encode( curl_error( $curl ) ) );
			return new WP_Error( 'curlerr', curl_error( $curl ) );
		}
		if ( $httpCode >= 400 || $httpCode == 0) {
			error_log('curlerr: ' . 'Internal server error: ' . $httpCode );
		}
		$results = json_decode($results, true);
		if ( !is_array( $results ) || array_key_exists('error', $results) ) {
			error_log('sw_err: ' . json_encode( $results ) );
			return new WP_Error('sw_err', $results['error'] );
		}
		return $results;
	}
	public function status($entity = 'clubs?max=1')
	{
		$results = $this->apiCall('GET', $entity);
		return $results;
	}	
	public function create_transaction($body)
	{
		$result = $this->apiCall('POST', 'trans', $body);
		return $result;
	}
	public function create_skywin_account( $body )
	{
		error_log('Skywin_Hub_API::create_skywin_account');
		$result = $this->apiCall( 'POST', 'members/checkin', $body );		
		return $result;
	}
	public function update_skywin_account( $body = null, $id = null )
	{
		if( !$body || !$id ){
			return new WP_Error('sw_err', 'You are not doing it right!');
		}
		$result = $this->apiCall( 'PUT', "members/checkin/$id", $body );		
		return $result;
	}
	public function add_members_phone($body, $id)
	{
		$result = $this->apiCall( 'POST', 'memberphones/' . $id, $body );
		return $result;
	}
	public function get_members_phones($id)
	{
		$result = $this->apiCall( 'GET', 'memberphones/' . $id);		
		return $result;
	}	
	public function skyview($date = null)
	{
		$result = $this->apiCall('GET', 'skyview', $date);
		return $result;
	}
	public function skywish($date = null)
	{
		$result = $this->apiCall('GET', 'skywish', $date);
		return $result;
	}
}
function skywin_hub_api(){
	return Skywin_Hub_API::instance();
}
endif;