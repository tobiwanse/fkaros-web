<?php
	
if (!defined('ABSPATH')) {
	exit;
}

if ( ! class_exists('Skywin_Accounts', false) ):

class Skywin_Accounts {
	public static $instance = null;
	
	private $name;
	
	private $title;
	
	private $shortcode;
	
	public function __construct(){

		error_log('Skywin_Accounts::__construct');
				
		$this->name = "accounts";
		
		$this->title = "Accounts";
		
		$this->shortcode = "skywin-" . $this->name;
		
		$this->add_actions();
		
		$this->add_filters();
		
		$this->add_shortcodes();
		
	}
	
	public function add_actions() {
		
		error_log('Skywin_Accounts::add_actions');
		
		add_action( 'lostpassword_post', array($this, 'validate_lostpassword_post'), 10, 2 );
		
	}
	
	public function add_filters() {
		
	}
	
	public function add_shortcodes() {
		
	}
		
	public function validate_lostpassword_post($errors, $user_data){
		error_log('Skywin_Accounts::validate_lostpassword_post');
		
		if( is_user_logged_in() || is_admin() ) {
			return;
		}
		
		if( ! isset( $_POST['wc_reset_password'] ) || empty( $_POST['wc_reset_password'] ) ){
			return;
		}
		
		if( !isset($_POST['user_login']) || empty($_POST['user_login']) ){
			return;
		}

		if( ! $user_data ){
			
			$skywin_user = Skywin_API::get_account_by_email( $_POST['user_login'] );
			
			if( ! $skywin_user ) {
				
				$message = 'Could not find skywin account';
				
				wc_add_notice('Could not create wallet account: ' . $message, 'error');
				
				return;
			}
			
			$wallet_account = $this->create_wp_account( $skywin_user );

			if( is_wp_error( $wallet_account ) ) {
				
				$message = $wallet_account->get_error_message();
				
				wc_add_notice('Could not create wallet account: ' . $message, 'error');
				
				return;
				
			}
			
			if ( ! is_wp_error( $wallet_account ) ) {
				
				wp_safe_redirect( add_query_arg( 'reset-link-sent', 'true', wc_get_account_endpoint_url('lost-password' ) ) );
				
				exit;
			
			}
		
		}
		
	}
	
	public function create_wp_account( $skywin_user ){
		error_log('Skywin_Wallet_accounts::create_wp_account');

		$email = $skywin_user['Emailaddress'];
		$username = '';
		$password = '';
		
		$args = array(
			'first_name'	=> $skywin_user['FirstName'],
			'last_name' 	=> $skywin_user['LastName'],
		);		
		
		$result = wc_create_new_customer($email, $username, $password, $args);
				
		if( ! is_wp_error( $result ) ) {
			
			update_user_meta( $result, 'Skywin_InternalNo', $skywin_user['InternalNo'] );
			update_user_meta( $result, 'Skywin_AccountNo', $skywin_user['AccountNo'] );
		
		}
		
		return $result;
	}
	
	public static function instance() {
		
		if ( is_null(self::$instance) ) {
			
			self::$instance = new self();
		
		}
		
		return self::$instance;
			
	}

}

function wswh_accounts() {
	
	return Skywin_Accounts::instance();
		
}

$WSWH_ACCOUNTS = wswh_accounts();

endif;
