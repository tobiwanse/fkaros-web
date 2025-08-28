<?php
defined('ABSPATH') || exit;

if( ! class_exists('Skywin_Checkin') ):

class Skywin_Checkin {
	
	public static $instance = null;
		
	private $name;
	
	private $title;
	
	private $shortcode;
	
	private $errors = array();
	
	private $userdata;

	public function __construct() {

		error_log('Skywin_Checkin::__construct');
				
		$this->name = "checkin";
		
		$this->title = "Checkin";
		
		$this->shortcode = "skywin-" . $this->name;
		
		$this->create_page_if_not_exist();
		
		$this->add_actions();
						
		$this->add_filters();
		
		$this->maybe_checkin();
		
		$this->add_shortcodes();
		 
	}
			
	public function create_page_if_not_exist() {
		error_log('Skywin_Checkin::create_page_if_not_exist');
		
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
		error_log('Skywin_Checkin::add_actions');
		
		add_action( 'wp_enqueue_scripts', array( $this, 'wp_enqueue_scripts' ), 10 );
		
		add_action( 'wp_enqueue_scripts', array( $this, 'wp_enqueue_styles' ), 10 );
		
		add_action( 'wp_ajax_get_account_by_email', array ( $this, 'ajax_get_account_by_email' ) );
		
		add_action( 'wp_ajax_nopriv_get_account_by_email', array ( $this, 'ajax_get_account_by_email' ) );
		
		add_action( 'template_redirect', array( $this, 'redirect_myaccount' ) );

	}
	
	public function ajax_get_account_by_email() {
		error_log('Skywin_Checkin::get_account_by_email');
		
		if ( !isset($_POST['email']) && empty($_POST['email']) ) {

			wp_die();
		}

		$email = sanitize_text_field( $_POST['email'] );
		
		$is_valid = true;
		
		check_ajax_referer('get_account_by_email', 'nonce');
		
		if ( !filter_var($email, FILTER_VALIDATE_EMAIL) ) {
			
		  	$is_valid = false; 
		
		}

		if ( isset($email) && !empty($email) && $is_valid ) {
		
			$result = skywin_hub_api()->get_account_by_email( $email );

			if ( $result ) {
		
				$is_valid = false;
		
			}
		
		}

		wp_send_json( $is_valid );
		
		wp_die();
		
	}
	
	public function add_filters () {
		error_log('Skywin_Checkin::add_filters');

	}
	
	public function add_shortcodes () {
		error_log('Skywin_Checkin::add_shortcodes');
		
		add_shortcode( $this->shortcode, array( $this, 'output_fields' ) );
		
	}
	
	public function redirect_myaccount(){
		error_log('Skywin_Checkin::redirect_myaccount');
		
		if( is_page( $this->name ) && is_user_logged_in() ) {
			
			wp_redirect( wc_get_page_permalink( 'myaccount' ) );
		
		}
			
	}
	
	public function wp_enqueue_styles ( ){
				
		if ( is_page( $this->name ) ) :
		// wp_enqueue_style('jquery-ui-tooltip');
		wp_enqueue_style( 'daterangepicker-css', 'https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css' );
		wp_enqueue_style( 'skywin-checkin-css', plugin_dir_url( SW_PLUGIN_FILE ) . 'assets/css/skywin-checkin.css' );
		endif;

	}	
	
	public function wp_enqueue_scripts () {
		
		if ( is_page( $this->name ) ) :
		wp_enqueue_script('jquery-ui');
		wp_enqueue_script('jquery-ui-tooltip');
		
		wp_enqueue_script( 'moment-js', 'https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.30.1/moment.min.js', array('jquery'), null, true );
		
		wp_enqueue_script( 'daterangepicker-js', 'https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js', array('jquery'), null, true );

		wp_enqueue_script( 'skywin-checkin-js', plugin_dir_url( SW_PLUGIN_FILE ) . 'assets/js/skywin-checkin.js', array('jquery'), null, true );
		
		wp_localize_script( 'skywin-checkin-js', 'get_account_by_email_params', array(
			'ajax_url' =>  admin_url( 'admin-ajax.php' ),
			'action' => 'get_account_by_email',
			'nonce' => wp_create_nonce( 'get_account_by_email' )
		));
		
		endif;
		
	}
	
	public function maybe_checkin() {
		
		error_log('Skywin_Checkin::maybe_checkin');

		if ( $_SERVER["REQUEST_METHOD"] !== "POST" || !isset($_POST[$this->name . '-submit']) ) { return; }
		
		if ( ! wp_verify_nonce( $_POST['_wpnonce'], 'skywin-' . $this->name ) ) { return; }

		$is_valid = $this->validate_checkin_form();
				
		if ( ! $is_valid ) { return; }
		
		$result = $this->create_skywin_account();
		
		if (!$result) { return; }
		
		if ( is_wp_error($result) ) {
			
			$message = $result->get_error_message();
			
			wc_add_notice($message, "error");
			
			return;
			
		}
						
		if ( isset($result['id']) && !empty($result['id'])) {
						
			$message = __("Wellcome to or Skydive Aros. Bring your license, logbook and your rig to the manifest for verifying.", 'wc-skywin-hub');
						
			wc_add_notice($message, "success");
			
			$this->notify();
			
			return;
			
		} else {
			
			$message = __('Unknown error while creating skywin account', 'wc-skywin-hub');
						
			wc_add_notice($message, "error");	
				
			return;
									
		}
		
	}
	
	public function get_message () {
		
		ob_start();
		
		$fields = $this->form_fields();
		
		$user_data = $this->userdata;
				
		foreach ( $fields as $key => $value) {
			
			if ( isset($value['label']) && isset($value['id'])  ) {
				
				echo '<div class="wrapper" style="border-bottom:1px solid; margin-bottom:20px;">';
				echo '<div class="row" style="margin:0px 0px 10px 10px;">';
				echo '<div class="label" style="font-weight:bold;">' . $value['label'] . '</div>';
				echo '</div>';
				echo '<div class="row" style="margin:0px 0px 20px 10px;">';
				echo '<div class="value">' . $user_data[$value['id']] . '</div>';
				echo '</div>';
				echo '</div>';
			
			}
			
		}
		
		$additional_content = get_option('skywin_checkin_notify_additional_content');
		
		if ( isset($additional_content) && !empty($additional_content) ) {
			
			echo '<div class="wrapper" style="border-bottom:1px solid; margin-bottom:20px;">';
			echo '<div class="row" style="margin:0px 0px 10px 10px;">';
			echo '<div class="label" style="font-weight:bold;">Additional content</div>';
			echo '</div>';
			echo '<div class="row" style="margin:0px 0px 20px 10px;">';
			echo '<div class="value">' . get_option('skywin_checkin_notify_additional_content', "") . '</div>';
			echo '</div>';
			echo '</div>';
			
		}
		
		$html = ob_get_clean();
				
		return $html;
	}
	
	public function notify () {
		
		if ( get_option('skywin_checkin_notify') !== 'yes' ) { return; }
		
		$mailer = WC()->mailer();
				
		$heading = get_option('skywin_checkin_notify_heading', __('New Skywin Checkin', 'wc-skywin-hub') );
		
		$message = $this->get_message();
		
		$wrapped_message = $mailer->wrap_message($heading, $message);
		
		$wc_email = new WC_Email;
				
		$html_message = $wc_email->style_inline($wrapped_message);
		
		$resipients = explode(',', get_option('skywin_checkin_notify_recipients', get_option( 'admin_email' )));
				
		$subject = get_option('skywin_checkin_notify_subject', __('New Skywin Checkin', 'wc-skywin-hub'));
		
		$headers[] = 'Content-Type: text/html; charset=UTF-8';
		
		$headers[] = 'From:' . get_option('skywin_checkin_notify_from_name', get_bloginfo( 'name' ) ) .' <' . get_option('skywin_checkin_notify_from_email') . '>';
					
		wp_mail( $resipients, $subject, $html_message, $headers, $attachments = array() );		
	}
		
	public function create_skywin_account() {
		error_log('Skywin_Checkin::create_skywin_account');
		
		$userdata = $this->userdata;

		$result = skywin_hub_api()->create_skywin_account( $userdata );
		
		if ( is_wp_error($result) ) {
			
			$message = $result->get_error_message();

			wc_add_notice( $message, 'error' );

			$this->errors[] = $message;
			
			return $result;

		}

		if ( isset($result['errors']) ) {
										
			$errors = $result['errors'];

			foreach ($errors as $error) {
				
				$message = $error['message'];
				
				wc_add_notice( $message, 'error' );
				
				$this->errors[] = $message;

			}
			
			return false;
			
		}

		return $result;
		
	}
	
	public function create_wp_account( $skywin_account = null ){
		error_log('Skywin_Checkin::create_wp_account');
		if ( !$skywin_account ) { return; }
		$email = $skywin_account['emailAddress'];
		$password = $_REQUEST['password'];
		$userdata = array(
			'first_name'	=> $skywin_account['firstName'],
			'last_name' 	=> $skywin_account['lastName'],
		);
		$result = wc_create_new_customer($email, $username = NULL, $password, $userdata);
		if(  is_wp_error( $result ) ) {
			$message = __('Something went wrong! please contact the administrator.', 'wc-skywin-hub');
			wc_add_notice($message, 'error');
			error_log('Faild to create account! ERROR: ' . json_encode($result) );
			return false;
		}

		update_user_meta( $result, 'Skywin_InternalNo', $skywin_account['id'] );
		update_user_meta( $result, 'Skywin_AccountNo', $skywin_account["accountNo"] );
		return $result;
		
	}
		
	public function validate_checkin_form( $is_valid = true ) {
		error_log('Skywin_Checkin::validate_checkin_form');
		if ( $_SERVER["REQUEST_METHOD"] !== "POST" || !isset($_POST[$this->name . '-submit']) ) { return; }
		if ( ! wp_verify_nonce( $_POST['_wpnonce'], 'skywin-' . $this->name ) ) { return; }
		$instructorText = (isset($_POST["instructorText"]) && is_array($_POST["instructorText"])) ? implode( " ", $_POST["instructorText"] ) : '';
		$certificateText = (isset($_POST["certificateText"]) && is_array($_POST["certificateText"])) ? implode( " ", $_POST["certificateText"] ) : '';
		$infoViaEmail = (isset($_POST['infoViaEmail'])) ? true : false;

		$form = array(
			"firstName" 		=> sanitize_text_field( $_POST["firstName"] ),
			"lastName" 			=> sanitize_text_field( $_POST["lastName"] ),
			"address1" 			=> sanitize_text_field( $_POST["address1"] ),
			"address2"			=> sanitize_text_field( $_POST["address2"] ),
			"postCode"			=> sanitize_text_field( $_POST["postCode"] ),
			"postTown"			=> sanitize_text_field( $_POST["postTown"] ),
			"countryCode" 		=> sanitize_text_field( $_POST["countryCode"] ),
			"pid"				=> sanitize_text_field( $_POST["pid"] ),
			"birthday"			=> sanitize_text_field( $_POST["birthday"] ),
			"gender"			=> sanitize_text_field( $_POST["gender"] ),
			"occupation"		=> sanitize_text_field( $_POST["occupation"] ),
			"weight"			=> sanitize_text_field( $_POST["weight"] ),
			"emailAddress"		=> sanitize_text_field( $_POST["emailAddress"] ),
			"phoneNo"			=> sanitize_text_field( $_POST["phoneNo"] ),
			"contactName" 		=> sanitize_text_field( $_POST["contactName"] ),
			"contactPhone"		=> sanitize_text_field( $_POST["contactPhone"] ),
			"nationalityCode" 	=> sanitize_text_field( $_POST["nationalityCode"] ),
			"memberType"		=> sanitize_text_field( $_POST["memberType"] ),
			"memberNo"			=> sanitize_text_field( $_POST["memberNo"] ),
			"externalMemberNo" 	=> sanitize_text_field( $_POST['externalMemberNo'] ),
			"licenseType"		=> sanitize_text_field( $_POST["licenseType"] ),
			"year"				=> sanitize_text_field( $_POST["year"] ),
			"club"				=> sanitize_text_field( $_POST["club"] ),
			"repackDate"		=> sanitize_text_field( $_POST["repackDate"] ),
			"homeDz"			=> sanitize_text_field( $_POST["homeDz"] ),
			"instructorText" 	=> sanitize_text_field( $instructorText ) ,
			"certificateText" 	=> sanitize_text_field( $certificateText ) ,
			"comment" 			=> sanitize_text_field( $_POST["comment"] ) ,
			"infoViaEmail"		=> sanitize_text_field( $infoViaEmail )
		);
				
		$fields = $this->form_fields();

		foreach ($fields as $field) {
			
			if ( !isset($field['required']) || empty($field['required']) ) { continue; }
			
			if ( !isset( $form[$field['id']] ) || empty( $form[$field['id']] ) ) {
				
				$label = $field['label'];

				$message = sprintf('%s "%s" %s', __('Field', 'wc-skywin-hub'), $label, __('is required and can not be empty.', 'wc-skywin-hub'));
				
				wc_add_notice($message, 'error');

				$this->errors[] = $field['id'];

			}

		}
		
		if ( (isset($form['emailAddress']) && !empty($form['emailAddress']) ) && ! filter_var( $form['emailAddress'], FILTER_VALIDATE_EMAIL ) ) {
		
			$message = __('Invalid email format!', 'wc-skywin-hub');
		
			wc_add_notice($message, 'error');
		
			$this->errors[] = "emailAddress";
				
		}
		
		if ( (isset($form['emailAddress']) && !empty($form['emailAddress']) ) && skywin_hub_db()->get_account_by_email( $form['emailAddress'] ) ) {
		
			$message = __('Email address already exist.', 'wc-skywin-hub');
		
			wc_add_notice($message, 'error');
		
			$this->errors[] = "emailAddress";
				
		}		
		
		if ( preg_match('([a-zA-Z])', $form['phoneNo'] ) ) {
			
			$message = __( 'Phone number can not include letters!', 'wc-skywin-hub' );
		
			wc_add_notice( $message, 'error' );
		
			$this->errors[] = "phoneNo";
			
		}
		
		if ( preg_match('([a-zA-Z])', $form['contactPhone']) ) {
			
			$message = __( 'Contact phone number can not include letters!', 'wc-skywin-hub' );
		
			wc_add_notice( $message, 'error' );
		
			$this->errors[] = "contactPhone";
			
		}						
		
		if ( count($this->errors) > 0 ) { $is_valid = false; }

		$this->userdata = $form;

		return $is_valid;
		
	}
	
	public function get_typecountries() {
				
		$items = skywin_hub_db()->get_typecountries();
		
		$new_array = array();
		
		foreach( $items as $key => $item ) {
				
			$new_array[$item['CountryCode']] = $item['CountryName'];
		
		}
		
		return $new_array;
		
	}
	
	public function get_member_types () {
		
		$items = skywin_hub_db()->get_membertypes();
		
		$new_array = array();
		
		foreach( $items as $key => $item ) {
				
			$new_array[$item['MemberType']] = $item['MemberTypename'];
		
		}
		
		return $new_array;
		
	}
	
	public function get_typegenders(){
		
		$items = array();
		
		$items['M'] = 'Male';
		
		$items['F'] = 'Female';
		
		return $items;
	}
	
	public function get_typeyearsofissue() {
		
		$items = array();
				
		$items[date('Y')] = date('Y');
		
		$items[date('Y', strtotime('+1 year'))] = date('Y', strtotime('+1 year') );
						
		return $items;
	
	}
	
	public function get_typelicenses() {
				
		$items = skywin_hub_db()->get_typelicenses();
				
		$new_array = array();
		
		foreach ($items as $key => $item) {

			$new_array[$item["LicenseType"]] = $item["LicenseTypename"]; 

		}
		
		return $new_array;
	}
	
	public function get_clubs() {
		
		$items = skywin_hub_db()->clubs("Y");
		
		$new_array = array();
		
		foreach ($items as $key => $item) {

			$new_array[$item["Club"]] =  sprintf('%s (%s)', $item["Name"], $item["Club"]);

		}

		return $new_array;
	}
	
	public function get_typeinstructors() {
		$items = skywin_hub_db()->get_typeinstructors();
		$new_array = array();
		foreach ($items as $key => $item) {
			$new_array[$item["InstructType"]] = $item["InstructTypename"];
		}
		return 	$new_array;
	}
	
	public function get_typecertificates() {
		$items = skywin_hub_db()->get_typecertificates();
		$new_array = array();
		foreach ($items as $key => $item) {
			$new_array[$item["CertificateType"]] = $item["CertTypename"];
		}
		return 	$new_array;
	}
	
	public function hidden_fields () {
		$hidden_fields = get_option('checkin');
		if ( !$hidden_fields ) {
			$hidden_fields = array();
		}
		return $hidden_fields;
	}
	
	public function form_fields( $current_view = 'checkin'){
		$fields = array();
		
		if ( $current_view === 'checkin'):

			$fields[] = array(
				"id" 			=> "skywin-". $this->name ."-form",
				"name" 			=> "skywin-". $this->name ."-form",
				"type" 			=> "hidden",
				"value" 		=> true	
			);

			$fields[] = array(
				"id" 			=> "_wpnonce",
				"name" 			=>"_wpnonce",
				"type" 			=> "hidden",
				"value" 		=> wp_create_nonce('skywin-' . $this->name)	
			);
						
			$fields[] = array(
				"id" 			=> "firstName",
				"name" 			=> "firstName",
				"label" 		=> __("First name", 'wc-skywin-hub'),
				"type" 			=> "text",
				"attr"			=> array('maxlength' => '40', 'autofill' => 'off', 'autocomplete' => 'off', 'autocomplete' => 'false' ),
				"class" 		=> array("form-row", "form-row-first"),
				"required" 		=> true,
				"placeholder" 	=> __("First name", "wc-skywin-hub")
			);
			
			$fields[] = array(
				"id" 			=> "lastName",
				"name" 			=> "lastName",
				"label" 		=> __("Last name", 'wc-skywin-hub'),
				"type" 			=> "text",
				"attr"			=> array('maxlength' => '40'),
				"class" 		=> array("form-row", "form-row-last"),
				"required" 		=> true,
				"placeholder" 	=> __("Last name", "wc-skywin-hub")
			);
			
			$fields[] = array(
				"id" 			=> "emailAddress",
				"name" 			=> "emailAddress",
				"label" 		=> __("Email", 'wc-skywin-hub'),
				"type" 			=> "text",
				"attr"			=> array('maxlength' => '80'),
				"class" 		=> array("form-row", "form-row-wide"),
				"required" 		=> false,
				"placeholder" 	=> __("myemail@mail.com", "wc-skywin-hub"),
				"tooltip"		=> __("Must be unique.", "wc-skywin-hub")
			);
			
			$fields[] = array(
				"id" 			=> "address1",
				"name" 			=> "address1",
				"label" 		=> __("Address", 'wc-skywin-hub'),
				"type" 			=> "text",
				"class" 		=> array("form-row", "form-row-wide"),
				"required" 		=> false,
				"attr"			=> array('maxlength' => '40'),
				"placeholder" 	=> __("Your address", "wc-skywin-hub")
			);
			
			$fields[] = array(
				"id" 			=> "address2",
				"name" 			=> "address2",
				"label" 		=> __("Address 2", 'wc-skywin-hub'),
				"type" 			=> "text",
				"attr"			=> array('maxlength' => '40'),
				"class" 		=> array("form-row", "form-row-wide"),
				"placeholder" 	=> __("Alternative address", "wc-skywin-hub"),
				"required"		=> false
			);
			
			$fields[] = array(
				"id" 			=> "postCode",
				"name" 			=> "postCode",
				"label" 		=> __("Post/Zip code", 'wc-skywin-hub'),
				"type" 			=> "text",
				"attr"			=> array('maxlength' => '40'),
				"class" 		=> array("form-row", "form-row-first"),
				"required" 		=> false,
				"placeholder" 	=> __("12345", "wc-skywin-hub")
			);
			
			$fields[] = array(
				"id" 			=> "postTown",
				"name" 			=> "postTown",
				"label" 		=> __("City", 'wc-skywin-hub'),
				"type" 			=> "text",
				"class" 		=> array("form-row", "form-row-last"),
				"required" 		=> false,
				"placeholder" 	=> __("City", "wc-skywin-hub")
			);
			
			$fields[] = array(
				"id" 			=> "countryCode",
				"name" 			=> "countryCode",
				"label" 		=> __("Country", 'wc-skywin-hub'),
				"type" 			=> "select",
				"options" 		=> $this->get_typecountries(),
				"class" 		=> array("form-row", "form-row-wide"),
				"required" 		=> false,
				"default"		=> __( 'Select country' , 'wc-skywin-hub' ),
			);
				
			$fields[] = array(
				"id" 			=> "pid",
				"name" 			=> "pid",
				"label" 		=> __("Social security number", 'wc-skywin-hub'),
				"type" 			=> "text",
				"class" 		=> array("form-row", "form-row-first"),
				"required" 		=> false,
				"placeholder" 	=> __("YYYYMMDD-XXXX"),
				"tooltip" 		=> __("Must be unique", "wc-skywin-hub")
			);
			
			$fields[] = array(
				"id" 			=> "birthday",
				"name" 			=> "birthday",
				"label" 		=> __( 'Birthday', 'wc-skywin-hub'),
				"type" 			=> "text",
				"attr"			=> array('maxlength' => '10'),
				"class" 		=> array("form-row", "form-row-last"),
				"placeholder" 	=> __('YYYYMMDD', 'wc-skywin-hub'),
				"tooltip" 		=> __( 'Your birthday.', 'wc-skywin-hub' ),
				"required"		=> false
			);
			
			$fields[] = array(
				"id" 			=> "gender",
				"name" 			=> "gender",
				"label" 		=> __("Gender", 'wc-skywin-hub'),
				"type" 			=> "select",
				"options" 		=> $this->get_typegenders(),
				"class" 		=> array("form-row", "form-row-first"),
				"required" 		=> false,
				"default" 		=> __("Select Gender", "wc-skywin-hub"),
			);
						
			$fields[] = array(
				"id" 			=> "weight",
				"name" 			=> "weight",
				"label" 		=> __("Exit weight (kg)", 'wc-skywin-hub'),
				"type" 			=> "number",
				"attr"			=> array('maxlength' => '17'),
				"class" 		=> array("form-row", "form-row-last"),
				"required" 		=> false,
				"placeholder" 	=> __("Weight in kg", "wc-skywin-hub")
			);
			
			$fields[] = array(
				"id" 			=> "occupation",
				"name" 			=> "occupation",
				"label" 		=> __("Occupation", 'wc-skywin-hub'),
				"type" 			=> "text",
				"attr"			=> array('maxlength' => '80'),
				"class" 		=> array("form-row", "form-row-wide"),
				"required"		=> false,
				"placeholder" 	=> __("Occupation", "wc-skywin-hub")
			);
								
			$fields[] = array(
				"id" 			=> "phoneNo",
				"name" 			=> "phoneNo",
				"label" 		=> __("Phone number", 'wc-skywin-hub'),
				"type" 			=> "text",
				"attr"			=> array('maxlength' => '30'),
				"class" 		=> array("form-row", "form-row-wide"),
				"required" 		=> false,
				"placeholder" 	=> __("(+46)0701234567", "wc-skywin-hub"),
				"tooltip" 		=> __("If not swedish please add country code.")
			);
							
			$fields[] = array(
				"id" 			=> "contactName",
				"name" 			=> "contactName",
				"label" 		=> __("Next of kin, name", 'wc-skywin-hub'),
				"type" 			=> "text",
				"attr"			=> array('maxlength' => '50'),
				"class" 		=> array("form-row", "form-row-first"),
				"required" 		=> false,
				"placeholder" 	=> __("Contact name", "wc-skywin-hub"),
				"tooltip" 		=> __("Contact name in case of emergency.", "wc-skywin-hub")
			);
			
			$fields[] = array(
				"id" 			=> "contactPhone",
				"name" 			=> "contactPhone",
				"label" 		=> __("Next of kin, phone", 'wc-skywin-hub'),
				"type" 			=> "text",
				"attr"			=> array('maxlength' => '50'),
				"class" 		=> array("form-row", "form-row-last"),
				"required" 		=> false,
				"placeholder" 	=> __("(+46)08123456", "wc-skywin-hub"),
				"tooltip" 		=> __("Contact phone number in case of emergency. Add country code if not swedish.", "wc-skywin-hub")
			);
			
			$fields[] = array(
				"id" 			=> "memberType",
				"name" 			=> "memberType",
				"label" 		=> __("Member type", 'wc-skywin-hub'),
				"type" 			=> "select",
				"options" 		=> $this->get_member_types(),
				"class" 		=> array("form-row", "form-row-wide"),
				"required" 		=> false,
				"default"		=> __( 'Select member type' , 'wc-skywin-hub' ),
			);
			
			$fields[] = array(
				"id" 			=> "nationalityCode",
				"name" 			=> "nationalityCode",
				"label" 		=> __("Nationality", 'wc-skywin-hub'),
				"type" 			=> "select",
				"options" 		=> $this->get_typecountries(),
				"class" 		=> array("form-row", "form-row-wide"),
				"required" 		=> false,
				"default"		=> __( 'Select nationality' , 'wc-skywin-hub' ),
			);
			
			$fields[] = array(
				"id" 			=> "memberNo",
				"name" 			=> "memberNo",
				"label" 		=> __( 'License number', 'wc-skywin-hub' ),
				"type" 			=> "text",
				"attr"			=> array('maxlength' => '17'),
				"class" 		=> array("form-row", "form-row-wide"),
				"required" 		=> false,
				"tooltip"		=> __('Can only be set if nationality is swedish and must be unique', 'wc-skywin-hub')
			);
								
			$fields[] = array(
				"id" 			=> "externalMemberNo",
				"name" 			=> "externalMemberNo",
				"label" 		=> __( 'External License number', 'wc-skywin-hub' ),
				"type" 			=> "text",
				"attr"			=> array('maxlength' => '17'),
				"class" 		=> array("form-row", "form-row-wide"),
				"required" 		=> false,
				"tooltip"		=> __("Can only be set if nationality is not swedish", "wc-skywin-hub")
			);
					
			$fields[] = array(
				"id" 			=> "licenseType",
				"name" 			=> "licenseType",
				"label" 		=> __("License type", 'wc-skywin-hub'),
				"type" 			=> "select",
				"options" 		=> $this->get_typelicenses(),
				"class" 		=> array("form-row", "form-row-first"),
				"required" 		=> false,
				"default" 		=> __( "Select License", "wc-skywin-hub" )
			);
			
			$fields[] = array(
				"id" 			=> "year",
				"name" 			=> "year",
				"label" 		=> __("Year", 'wc-skywin-hub'),
				"type" 			=> "select",
				"options" 		=> $this->get_typeyearsofissue(),
				"class" 		=> array("form-row", "form-row-last"),
				"required" 		=> false,
				"default" 		=> __("Select Year", "wc-skywin-hub")
			);
			
			$fields[] = array(
				"id" 			=> "club",
				"name" 			=> "club",
				"label" 		=> __( 'Club', 'wc-skywin-hub'),
				"type" 			=> "select",
				"options" 		=> $this->get_clubs(),
				"class" 		=> array( 'form-row', 'form-row-first' ),
				"default" 		=> __( 'Select Club', 'wc-skywin-hub' ),
				"required" 		=> false,
				"tooltip"		=> __( 'If not in the list select "other". ', 'wc-skywin-hub')
			);
						
			$fields[] = array(
				"id" 			=> "repackDate",
				"name" 			=> "repackDate",
				"label" 		=> __( 'Repack date', 'wc-skywin-hub'),
				"type" 			=> "text",
				"attr"			=> array('maxlength' => '10'),
				"class" 		=> array("form-row", "form-row-last"),
				"placeholder" 	=> __('YYYYMMDD', 'wc-skywin-hub'),
				"tooltip" 		=> __( 'Date when your reservepack expires.', 'wc-skywin-hub' ),
				"required"		=> false
			);
			
			$fields[] = array(
				"id" 			=> "homeDz",
				"name" 			=> "homeDz",
				"label" 		=> __( 'Home dropzone', 'wc-skywin-hub' ),
				"type" 			=> "text",
				"attr"			=> array('maxlength' => '80'),
				"class" 		=> array("form-row", "form-row-wide"),
				"required" 		=> false,
			);

			
			$fields[] = array(
				"id" 			=> "instructorText",
				"name" 			=> "instructorText",
				"label" 		=> __( 'Instructor ratings', 'wc-skywin-hub' ),
				"type" 			=> "multiple",
				"options" 		=> $this->get_typeinstructors(),
				"class" 		=> array("form-row", "form-row-wide"),
				"required"		=> false
			);
			
			$fields[] = array(
				"id" 			=> "certificateText",
				"name" 			=> "certificateText",
				"label" 		=> __("Certificates", 'wc-skywin-hub'),
				"type" 			=> "multiple",
				"options" 		=> $this->get_typecertificates(),
				"class" 		=> array("form-row", "form-row-wide"),
				"required"		=> false
			);
			
			$fields[] = array(
				"id" 			=> "comment",
				"name" 			=> "comment",
				"label" 		=> __("Comment", 'wc-skywin-hub'),
				"type" 			=> "textarea",
				"class" 		=> array("form-row", "form-row-wide"),
				"required"		=> false
			);
			
			$fields[] = array(
				"id"			=> "infoViaEmail",
				"name"			=> "infoViaEmail",
				"type"			=> "checkbox",
				"class" 		=> array("form-row", "form-row-wide"),
				"required"		=> false,
				"desc"			=> __("Sign up for news emails", "wc-skywin-hub"),
			);
		
		elseif ( $current_view === 'update' ):
			error_log('WC_Skywin_Checkin::current view update');

		endif;

		return $fields;
		
	}
	
	public function output_fields() {
		error_log('Skywin_Checkin::checkin_template');
				
		ob_start();
		
		?>
		
		<div class="alignwide">
		<form method="POST" action="">
			<?php
			
			if( function_exists('wc_print_notices') ){ wc_print_notices(); }
			
			$fields = $this->form_fields();
			
			foreach ( $fields as $key => $field ) {
				
				if ( in_array( $field['id'], $this->hidden_fields() ) ) { continue; }
				
				$classes = isset( $field["class"] ) && ! empty( $field["class"] ) ? $field["class"] : array();
				
				$required = isset( $field["required"] ) && ! empty( $field["required"] ) ? 'required' : false;
				
				$attributes = isset( $field["attr"] ) && ! empty( $field["attr"] ) ? $field["attr"] : array();
				
				$value = isset( $_POST[$field["id"]] ) ? $_POST[$field["id"]] : '';

				$value = sanitize_text_field( $value );
				
				$placeholder = isset( $field['placeholder'] ) && ! empty( $field['placeholder'] ) ? $field['placeholder'] : '';
				
				$classes[] = "input-text";
				
				if ( is_array( $this->errors ) && in_array( $field["id"], $this->errors ) ) {
					$classes[] = "has_error";
				}
				
				$classes = implode( " ", $classes );
				
				$attr = '';
				
				if ( isset( $attributes ) && ! empty( $attributes ) ) {
					
					foreach ( $attributes as $key => $val ) {
						
						if ( isset( $key ) && ! empty( $key ) ){
						
							$attr .= $key . '=' . esc_attr( $val ) . ' ';
						
						} else {
							
							$attr .= $val . ' ';
						
						}
						
					}

				}
				
				if ( $field["type"] !== "hidden" && $field["type"] !== "title" ) {
					
					echo '<p class="' . $classes . '">';
				
				}
								
				if ( $field["type"] === "hidden" ) {
					
					echo '<input type="'. esc_attr( $field["type"] ) .'" id="'. esc_attr( $field["id"] ) .'" name="' . esc_attr( $field["name"] ) .'" value="'. esc_attr( $field["value"] ) .'" />';
				
				}
				
				if ( $field["type"] === "title" ) {
				
					echo '<h2>' . $field["title"] . '</h2>';
				
				}
							
				if ( isset( $field["label"] ) && ! empty($field["label"]) ) {
				
					echo '<span><label for="'. esc_attr( $field['id'] ) .'" >';
					
					echo esc_html( $field['label'] ). ' ';
					
					if ( isset( $field['required'] ) && $field['required'] ) {
					
						echo '<span class="required">*</span>';
					
					} else {

						echo esc_html(__("(optional)", "wc-skywin-hub"));
					
					}
					
					echo '</label></span>';
				}
				
				if ( isset( $field['tooltip'] ) && ! empty( $field['tooltip'] ) ) {
					
					echo '<span class="tooltip" style="padding-left:8px;" title="'. esc_attr( $field['tooltip'] ) .'"><img src="'. plugin_dir_url( SW_PLUGIN_FILE ) .'assets/img/desctip.svg"></span>';
					
				}
				
				if ( $field["type"] === "text" ) {
					
					echo '<input type="'. esc_attr( $field["type"] ) .'"  id="'. esc_attr( $field["id"] ) .'" name="'. esc_attr( $field["name"] ) .'" value="'.  $value  .'" '. $required .' '. $attr .' placeholder = "'. $placeholder .'"/>';
					
				}
				
				if ( $field["type"] === "textarea" ) {
					
					echo '<textarea id="'. esc_attr( $field["id"] ) .'" name="'. esc_attr( $field["name"] ) .'" value="'.  $value  .'" '. $required .' '. $attr .' placeholder = "'. $placeholder .'">'. $value .'</textarea>';
					
				}
				
				if ( $field["type"] === "number" ) {
					
					echo '<input type="'. esc_attr( $field["type"] ) .'"  id="'. esc_attr( $field["id"] ) .'" name="'. esc_attr( $field["name"] ) .'" value="'. esc_attr( $value ) .'" value="" '. $required .' '. $attr .' placeholder = "'. $placeholder .'"/>';
					
				}
				
				if ( $field["type"] === "password" ) {
					
					echo '<input type="'. esc_attr( $field["type"] ) .'" id="'. esc_attr( $field["id"] ) .'" name="'. esc_attr( $field["name"] ) .'" value="'.  $value  .'" '. $required .' '. $attr .' placeholder = "'. $placeholder .'"/>';
					
				}

				if ( $field["type"] === "select" ) {
					
					echo '<select id="'. esc_attr( $field["id"] ) .'" name="'. esc_attr( $field["name"] ) .'" '. $required .'>';
					
					if ( isset( $field["default"] ) ) {
						
						echo '<option value="" >'. esc_html( $field["default"] ) .'</option>';
					
					}
					
					$options = $field['options'];
					
					foreach ( $options as $key => $value ) {
																		
						if ( isset($_POST[$field["id"]]) && $key == $_POST[ $field["id"] ] ) {
							
							$selected = 'selected';
						
						} else {
						
							$selected = '';
						
						}
						
						echo '<option value="'. esc_attr( $key ) .'" '. esc_attr( $selected ) .'>';
						
						echo esc_html( $value );
						
						echo '</option>';
					
					}
					
					echo '</select>';
				}
				
				if ( $field["type"] === "multiple" ) {
					
					echo '<select id="'. esc_attr( $field["id"] ) .'[]" name="'. esc_attr( $field["name"] ) .'[]" multiple '. $required .'>';
					
					$options = $field['options'];
					
					if ( isset($field["default"]) && ! empty($field["default"]) ){
					
						echo '<option disabled selected>'. $field["default"] .'</option>';
					
					}
					
					foreach ($options as $key => $value) {
												
						if ( isset( $_POST[$field["id"]] ) && in_array($key, $_POST[$field["id"]])  ) {
							$selected = 'selected';
						}else{
							$selected = '';
						}
						
						echo '<option value="'. esc_attr( $key ) .'" '. $selected .'>';
						
						echo esc_html( $value );
						
						echo '</option>';

					}
					
					echo '</select>';
					
				}

				if ( $field["type"] === "checkbox" ) {

					$checked = isset($_POST[$field["id"]]) ? 'checked' : '';

					echo '<span style="display:block;"><input style="vertical-align: middle; " type="'. esc_attr( $field["type"] ) .'" id="'. esc_attr( $field["id"] ) .'" name="'. esc_attr( $field["name"] ) .'" value="yes" '. $checked .'/>';
					
					echo '<span style="padding-left:5px;">' . $field["desc"] . '</span>';
					
					echo '</span>';
				
				}
				
				if ($field["type"] !== "hidden" && $field["type"] !== "title" ) {
				
					echo '</p>';
				
				}
				
				if ( in_array( 'form-row-last', explode(" ",  $classes ) ) ) {
					
					echo '<span class="clear" ></span>';
				
				}

			}
			
			echo '<input type="submit" value="Submit" name="'. $this->name .'-submit" />';
			
			?>
			
		</form>
		</div>
		<?php
		
		$html = ob_get_clean();
		
		return $html;
	}
	
	public static function instance() {
		
		if (null === self::$instance) {
			
			self::$instance = new self();
		
		}
		
		return self::$instance;

	}
	
}

function wswh_checkin () {

	return Skywin_Checkin::instance();
	
}

$wswh_checkin = wswh_checkin();
		
endif;