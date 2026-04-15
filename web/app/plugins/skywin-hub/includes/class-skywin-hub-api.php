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
	public function __construct() {
		// Constructor code if needed
		
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
		$args = array(
			'method'      => $requestMethod,
			'headers'     => array(
				'Content-Type'  => 'application/json',
				'Accept'        => 'application/json'
			),
			'timeout'     => 10,
			'redirection' => 5,
			'user-agent'  => 'WordPress/' . get_bloginfo('version'),
		);
		$args['headers']['Authorization'] = 'Basic ' . base64_encode( "$username:$password" );
		if ( $requestMethod === 'POST' || $requestMethod === 'PUT' ) {
			$args['body'] = json_encode( $body );
		}
		$response = wp_remote_request( $endpoint, $args );
		if ( is_wp_error( $response ) ) {
			$error_msg = $response->get_error_message();
			error_log( 'HTTP Request Error: ' . $error_msg );
			return new WP_Error( 'http_error', __('Connection failed. Please try again.', 'skywin_hub') );
		}
		$http_code = wp_remote_retrieve_response_code( $response );
		$body = wp_remote_retrieve_body( $response );
		$results = json_decode( $body, true );
		if ( $http_code >= 400 ) {
			error_log( 'HTTP ' . $http_code . ': ' . $body );
			if ( $http_code === 401 || $http_code === 403 ) {
				return new WP_Error( 'auth_error', __('Authentication failed', 'skywin_hub') );
			}
			if ( $http_code === 404 ) {
				return new WP_Error( 'not_found', __('Resource not found', 'skywin_hub') );
			}
			if ( is_array( $results ) && ( isset( $results['errors'] ) || isset( $results['error'] ) ) ) {
				$error_data = $results['errors'] ?? $results['error'];
				return new WP_Error( 'api_error', $error_data  );
			}
			return new WP_Error( 'http_error', __('An error occurred. Please try again later.', 'skywin_hub') );
		}
		if ( $results === null ) {
			error_log( 'JSON decode error: Invalid JSON response' );
			return new WP_Error( 'json_error', __('An error occurred processing the response.', 'skywin_hub') );
		}
		if ( is_array( $results ) && isset( $results['errors'] ) ) {
			error_log( 'API Errors: ' . json_encode( $results['errors'] ) );
			return new WP_Error( 'api_error', __('Request failed. Please check your information.', 'skywin_hub'), $results['errors'] );
		}
		if ( is_array( $results ) && isset( $results['error'] ) ) {
			error_log( 'API Error: ' . json_encode( $results['error'] ) );
			return new WP_Error( 'api_error', __('Request failed. Please check your information.', 'skywin_hub'), $results['error'] );
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
		$result = $this->apiCall( 'POST', 'members/checkin', $body );		
		return $result;
	}
	public function update_skywin_account( $body = null, $id = null )
	{
		if( !$body || !$id ){
			error_log('skywin_api_error: You are not doing it right!');
			return new WP_Error('skywin_api_error', 'You are not doing it right!');
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
		if($date){
			$date = '?jumpDate=' . $date;
		}
		$result = $this->apiCall('GET', 'skyview', $date);
		return $result;
	}
	public function loads($date = null)
	{
		$result = $this->apiCall('GET', 'loads', $date);
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