<?php
	
if ( ! defined('ABSPATH') ) { exit; }

if ( ! class_exists('Skywin_Transfer', false) ):

class Skywin_Transfer {
   
	public static $instance = null;
	private $name;
	private $title;
	public function __construct(){		
		error_log('Skywin_Transfer::__construct');		
		
		$this->name = 'transfer';
						
		$this->title = 'Transfer';
		
		$this->shortcode = "skywin-" . $this->name;	
				
		$this->create_page_if_not_exist();
		
		$this->add_actions();
								
		$this->add_filters();
		
		$this->add_shortcodes();
				
		$this->maybe_create_transfer();
	}

	public function create_page_if_not_exist() {
		error_log('Skywin_Transfer::create_page_if_not_exist');
		
		$page_data = array(
			'post_status'    => 'publish',
			'post_type'      => 'page',
			'post_author'    => 1,
			'post_name'      => $this->name,
			'post_title'     => $this->title,
			'post_content'   => '<!-- wp:shortcode -->[' . $this->shortcode . ']<!-- /wp:shortcode -->',
			'post_parent'    => 0,
			'comment_status' => 'closed',
		);
		 
		if ( ! get_page_by_path( $this->name, OBJECT, 'page') ) { 
			$new_page_id = wp_insert_post( $page_data );
		}
		
	}
	
	public function add_actions() {
		error_log('Skywin_Transfer::add_actions');
		
		add_action( 'wp_enqueue_scripts', array( $this, 'wp_enqueue_scripts' ), 10 );
			
		add_action( 'wp_enqueue_scripts', array( $this, 'wp_enqueue_styles' ), 10 );

		add_action( 'wp_ajax_get_skywin_accounts', array ( $this, 'ajax_get_skywin_accounts' ), 10 );
		
		add_action( 'wp_ajax_nopriv_get_skywin_accounts', array ( $this, 'ajax_get_skywin_accounts' ), 10 );

	}
	
	public function add_filters () {
		error_log('Skywin_Transfer::add_filters');
	
	}
	
	public function wp_enqueue_scripts (){
		error_log('Skywin_Transfer::wp_enqueue_scripts');
								
		wp_register_script( 'skywin-'. $this->name .'-js', plugin_dir_url( WC_Skywin_Hub::PLUGIN_FILE ) . 'assets/js/skywin-'. $this->name .'.js', array('jquery'), null, true );
				
		wp_localize_script( 'skywin-'. $this->name .'-js', 'ajax_get_skywin_accounts_params', array(
			'ajax_url' =>  admin_url( 'admin-ajax.php' ),
			'action' => 'get_skywin_accounts',
			'nonce' => wp_create_nonce( 'ajax_get_skywin_accounts_nonce' ),
		));
		
	}
	
	public function wp_enqueue_styles () {
		error_log('Skywin_Transfer::wp_enqueue_styles');
							
		wp_register_style( 'skywin-'. $this->name .'-css', plugin_dir_url( WC_Skywin_Hub::PLUGIN_FILE ) . 'assets/css/skywin-'. $this->name .'.css' );
		
		wp_enqueue_style( 'skywin-'. $this->name .'-css' );
	}

	public function ajax_get_skywin_accounts(){
		error_log('Skywin_Transfer::ajax_get_skywin_accounts');
		
		check_ajax_referer( 'ajax_get_skywin_accounts_nonce', 'security' );
		
		wp_enqueue_script('wc-checkout', plugins_url().'/woocommerce/assets/js/frontend/checkout.min.js',array('jquery'), null, true);
		
		$terms = $_REQUEST['terms'];
		
		$terms = explode(' ', $terms);
				
		$items = $this->get_skywin_accounts();
						
		$results = array();
				
		if ( is_wp_error( $items ) ) {
			
			wp_send_json( $results );
					
			wp_die();
			
		}
				
		foreach ( $items as $key => $item ) {
						
			$fields = array(
				$item['Club'],
				$item['AccountNo'],
				$item['MemberNo'],
				$item['ExternalMemberNo'],
				$item['FirstName'],
				$item['LastName'],
				$item['Emailaddress'],
				$item['NickName'],
				$item['PhoneNo'],
			);
			
			$is_found = false;
						
			$regex = array();
			
			foreach ( $terms as $key => $term ) {
				
				$regex[] = '\b(?=\w)'. $term .'.*';
				
			}
			
			$pattern = '/'. implode('', $regex) .'/i';
			
			$subject = implode(' ', $fields);
			
			preg_match($pattern, $subject, $matches);
						
			if ( $matches ) {
				
				if ( isset($item['MemberNo']) && !empty($item['MemberNo'])) {
					
					$memberno = $item['MemberNo'];
				
				} else {
					
					$memberno = '<' . $item['ExternalMemberNo'] . '>';
					
				}
				
				if ( isset($item['Club']) && !empty($item['Club'])) {
					
					$club = $item['Club'];
					
				} else {
					
					$club = 'N/A';
	
				}
				
				$results[] = array(
					'value' => $club.' '.$memberno.' '.$item['FirstName'].' '.$item['LastName']. ' ' .$item['PhoneNo'],
					'label' => $club.' '.$memberno.' '.$item['FirstName'].' '.$item['LastName']. ' ' .$item['PhoneNo'],
					'data' => array(
						'Club' => $item['Club'],
						'AccountNo' => $item['AccountNo'],
						'MemberNo' => $item['MemberNo'],
						'ExternalMemberNo' => $item['ExternalMemberNo'],
						'FirstName' => $item['FirstName'],
						'LastName' => $item['LastName'],
						'PhoneNo' => $item['PhoneNo']
					),
				);
								
			}
		
		}
				
		$results = array_slice($results, 0, 20);
		
		wp_send_json( array('js' => $js, 'html' => $html, 'results' => $results) );
		
		wp_die();
	
	}
	
	public function get_skywin_accounts () {
		error_log('Skywin_Transfer::get_skywin_accounts');
					
		$accounts = skywin_api()->get_accounts();
		
		return $accounts;
				
	}
	
	public function maybe_create_transfer() {
		
		if ( !isset($_REQUEST['skywin-transfer']) ){
			return false;
		}
		
		if ( !$this->validate_form() ) {
			return false;
		}
		
	}
	
	public function validate_form( $true = true ) { 
		error_log('Skywin_Transfer::Transfer_validation');
			
		if ( !isset($_REQUEST['skywin-transfer-nonce']) || is_null($_REQUEST['skywin-transfer-nonce']) ){
			
			wc_add_notice( __( 'Security check error', 'wc-skywin-hub' ), 'error' );
			
			$true = false;
		
		}
				
		if ( !wp_verify_nonce($_REQUEST['skywin-transfer-nonce'], 'skywin-transfer-nonce') ) {
		
			wc_add_notice( __( 'Security check error', 'wc-skywin-hub' ), 'error' );

			$true = false;
		
		}
	
		return $true; 
				
	}
	
	public function add_shortcodes () {
		error_log('Skywin_Transfer::add_shortcodes');
		
		add_shortcode( $this->shortcode, array( $this, 'output' ) );
		
	}
	
	public function output () {
		error_log('Skywin_Transfer::custom_fields_template');
		
		wp_enqueue_script( 'jquery-ui-autocomplete' );
							
		wp_enqueue_script( 'skywin-'. $this->name .'-js' );
		
		wp_enqueue_style( 'skywin-'. $this->name .'-css' );

		$template_path = plugin_dir_path( WC_Skywin_Hub::PLUGIN_FILE ). 'templates/' ;
		
		wc_print_notices();
		
		wc_get_template( 'html-skywin-transfer.php',
			'',
			'',
			trailingslashit( $template_path ) );			
				
	}

	public static function instance() {
			
		if (is_null(self::$instance)) {
			
			self::$instance = new self();
		
		}
		
		return self::$instance;
			
	}
		
}

function skywin_transfer() {

	return Skywin_Transfer::instance();

}

$SKYWIN_TRANSFER = skywin_transfer();
	
endif;
