<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
/**
 * Functions for Ultimate Member integration with Skywin Hub.
 *
 * @package SkywinHub
 */
function before_form( $args ) {
    add_filter( 'woocommerce_enqueue_styles', '__return_empty_array' );
    $admin_email = get_option('admin_email');
    if( is_wp_error(skywin_hub_api()->status()) || is_wp_error(skywin_hub_db()->status()) ){
        UM()->form()->add_error( 'heading', __("Skywin Hub is not available at the moment. Please try again later or contact $admin_email", "skywin_hub") );
    }
    if( isset( UM()->form()->errors['heading'] ) ){
         if( is_array( UM()->form()->errors['heading'] ) && !empty( UM()->form()->errors['heading'] ) ){
            foreach( UM()->form()->errors['heading'] as $key => $error ) {
                ob_start();
                ?>
                <div class="um-field-error">
                    <h3><?php echo esc_html( $error ); ?></h3>
                </div>
                <?php
                $html = ob_get_clean();
                echo $html;
            }
         } else {
            echo '<div class="um-field-error"><h3>' . UM()->form()->errors['heading'] . '</h3></div>';
         }
    }
}
add_action( 'um_before_form', 'before_form', 10, 1 );
function create_wordpress_user( $skywin_account ) {
    error_log('create_wordpress_user');
    $userdata = [
        'user_login'           => $skywin_account['Emailaddress'],
        'user_email'           => $skywin_account['Emailaddress'], 
        'nickname'             => $skywin_account['NickName'],     
        'first_name'           => $skywin_account['FirstName'],    
        'last_name'            => $skywin_account['LastName'],     
        'show_admin_bar_front' => 'false',                         
        'role'                 => 'subscriber',
        'user_pass'            => null,                            
        'meta_input'           => [
            'account_status'    => 'approved',
            'address1'          => $skywin_account['Address1'],
            'postCode'          => $skywin_account['Postcode'],
            'postTown'          => $skywin_account['Posttown'],
            'country'           => $skywin_account['CountryCode'],
            'phoneNo'           => $skywin_account['PhoneNo'],
            'nationalityCode'   => $skywin_account['NationalityCode'],
            'pid'               => $skywin_account['PID'],
            'gender2'           => $skywin_account['Sex'],
            'homeDz'            => $skywin_account['Homedz'],
            'contactName'       => $skywin_account['ContactName'],
            'contactPhone'      => $skywin_account['ContactPhone'],
            'weight'            => $skywin_account['Weight'],
            'repackDate'        => $skywin_account['RepackDate'],
            'memberNo'          => $skywin_account['MemberNo'],
            'externalMemberNo'  => $skywin_account['ExternalMemberNo'],
            'instructType'      => explode(' ',$skywin_account['InstructorText']),
            'licenseType'       => $skywin_account['LicenseType'],
            'certificateType'   => explode(' ', $skywin_account['CertificateText']),
            'year'              => $skywin_account['Year'],
            'club'              => $skywin_account['Club'],
            'infoViaEmail'      => $skywin_account['InfoViaEmail'],
            'internalNo'        => $skywin_account['InternalNo'],
            'accountNo'         => $skywin_account['AccountNo'],
            'comment'           => $skywin_account['Comment'],
        ],
    ];
    
    $user = get_user_by('email', $skywin_account['Emailaddress'] );

    if ( $user ) {
        $user_id = $user->ID;
        update_wordpress_user( $user_id );
    } else {
        $user_id = wp_insert_user( $userdata );
    }
}
function update_wordpress_user( $user_id ) {
    um_fetch_user( $user_id );
    $internalNo = get_user_meta( $user_id, 'internalNo', true );
    $skywin_account = skywin_hub_db()->get_account_by_id( $internalNo );
    if(!isset($skywin_account['InternalNo']) || empty($skywin_account['InternalNo']) ){
        return false;
    }
    update_user_meta( $user_id, 'nickname', $skywin_account['NickName'] );
    update_user_meta( $user_id, 'address1', $skywin_account['Address1'] );
    update_user_meta( $user_id, 'postCode', $skywin_account['Postcode'] );
    update_user_meta( $user_id, 'postTown', $skywin_account['Posttown'] );
    update_user_meta( $user_id, 'country', $skywin_account['CountryCode'] );
    update_user_meta( $user_id, 'phoneNo', $skywin_account['PhoneNo'] );
    update_user_meta( $user_id, 'nationalityCode', $skywin_account['NationalityCode'] );
    update_user_meta( $user_id, 'pid', $skywin_account['PID'] );
    update_user_meta( $user_id, 'gender2', $skywin_account['Sex'] );
    update_user_meta( $user_id, 'homeDz', $skywin_account['Homedz'] );
    update_user_meta( $user_id, 'contactName', $skywin_account['ContactName'] );
    update_user_meta( $user_id, 'contactPhone', $skywin_account['ContactPhone'] );
    update_user_meta( $user_id, 'weight', $skywin_account['Weight'] );
    update_user_meta( $user_id, 'repackDate', $skywin_account['RepackDate'] );
    update_user_meta( $user_id, 'comment', $skywin_account['Comment'] );
    update_user_meta( $user_id, 'memberNo', $skywin_account['MemberNo'] );
    update_user_meta( $user_id, 'externalMemberNo', $skywin_account['ExternalMemberNo'] );
    update_user_meta( $user_id, 'instructType', explode(' ',$skywin_account['InstructorText']) );
    update_user_meta( $user_id, 'licenseType', $skywin_account['LicenseType'] );
    update_user_meta( $user_id, 'certificateType', explode(' ', $skywin_account['CertificateText']) );
    update_user_meta( $user_id, 'year', $skywin_account['Year'] );
    update_user_meta( $user_id, 'club', $skywin_account['Club'] );
    $infoViaEmail = $skywin_account['InfoViaEmail'] == 'Y' ? ['Yes'] : ['No'];
    update_user_meta( $user_id, 'infoViaEmail', $infoViaEmail );
    update_user_meta( $user_id, 'internalNo', $skywin_account['InternalNo'] );
    update_user_meta( $user_id, 'accountNo', $skywin_account['AccountNo'] );
}
function create_skywin_user( $user_id ) {
    um_fetch_user( $user_id );
    $instructTypeText = um_user('instructType');
    $certificateTypeText = um_user('certificateType');
    $instructType = is_array($instructTypeText) ? implode(" ",$instructTypeText) : "";
    $certificateType = is_array( $certificateTypeText) ? implode(" ", $certificateTypeText) : "";
    $body = array(
        'firstName'         => um_user('first_name') ? um_user('first_name') : '',
        'lastName'          => um_user('last_name') ? um_user('last_name') : '',
        'nickName'          => um_user('nickname') ? um_user('nickname') : '',
        'emailAddress'      => um_user('user_email') ? um_user('user_email') : '',
        'pid'               => um_user('pid') ? um_user('pid') : '',
        'address1'          => um_user('address1') ? um_user('address1') : '',
        'postCode'          => um_user('postCode') ? um_user('postCode') : '',
        'postTown'          => um_user('postTown') ? um_user('postTown') : '',
        'countryCode'       => um_user('country') ? um_user('country') : '',
        'phoneNo'           => um_user('phoneNo') ? um_user('phoneNo') : '',
        'gender'            => um_user('gender2') ? um_user('gender2') : '',
        'weight'            => um_user('weight') ? um_user('weight') : '',
        'repackDate'        => um_user('repackDate') ? um_user('repackDate') : '',

        'memberNo'          => um_user('memberNo') ? um_user('memberNo') : null,
        'externalMemberNo'  => um_user('externalMemberNo') ? um_user('externalMemberNo') : null,
        'nationalityCode'   => um_user('nationalityCode') ? um_user('nationalityCode') : '',
        'year'              => um_user('year') ? um_user('year') : '',
        'licenseType'       => um_user('licenseType') ? um_user('licenseType') : '',
        'instructorText'    => $instructType,
        'certificateText'   => $certificateType,        
        'homeDz'            => um_user('homeDz') ? um_user('homeDz') : '',
        'club'              => um_user('club') ? um_user('club') : '',

        'contactName'       => um_user('contactName') ? um_user('contactName') : '',
        'contactPhone'      => um_user('contactPhone') ? um_user('contactPhone') : '',

        'comment'           => um_user('comment') ? um_user('comment') : '',
        'infoViaEmail'      => (um_user('infoViaEmail')[0] ?? '') == 'Yes' ? 'Y' : 'N',
    );

    $skywin_account = skywin_hub_api()->create_skywin_account( $body );
    if( isset( $skywin_account['errors'] ) ){
        foreach( $skywin_account['errors'] as $error ){
            UM()->form()->add_error( $error['field'], $error['message'] );
        }
        return false;
    }
    $skywin_account = is_array($skywin_account) ? $skywin_account : json_decode($skywin_account, true);
    update_user_meta( $user_id, 'internalNo', $skywin_account['id'] );
    update_user_meta( $user_id, 'accountNo', $skywin_account['account']['accountNo'] );
    return $skywin_account;
}
function update_skywin_user($args) {
    um_fetch_user( $args['user_id'] );
    $body = array(
        'firstName'         => $args["first_name"],
        'lastName'          => $args["last_name"],
        'nickName'          => $args["nickname"],
        'emailAddress'      => $args["user_email"],
        'address1'          => $args["address1"],
        'postCode'          => $args["postCode"],
        'postTown'          => $args["postTown"],
        'countryCode'       => $args["country"],
        'phoneNo'           => $args["phoneNo"],
        'weight'            => $args["weight"],
        'repackDate'        => $args["repackDate"],
        'gender'            => $args["gender2"],
        'nationalityCode'   => $args["nationalityCode"],
        'memberNo'          => $args["memberNo"],
        'externalMemberNo'  => $args["externalMemberNo"],
        'pid'               => $args["pid"],
        'year'              => $args["year"],
        'club'              => $args["club"],
        'homeDz'            => $args["homeDz"],
        'instructorText'    => isset($args["instructType"]) ? implode(" ", $args["instructType"]) : '',
        'certificateText'   => isset($args["certificateType"]) ? implode(" ", $args["certificateType"]) : '',
        'licenseType'       => $args["licenseType"],
        'contactName'       => $args["contactName"],
        'contactPhone'      => $args["contactPhone"],
        'infoViaEmail'      => isset($args["infoViaEmail"]) && $args["infoViaEmail"][0] == 'Yes' ? true : false,
    );
    $skywin_account = skywin_hub_api()->update_skywin_account( $body, um_user('internalNo') );
    if( isset( $skywin_account['errors'] ) ){
        foreach( $skywin_account['errors'] as $error ){
            UM()->form()->add_error( $error['field'], $error['message'] );
        }
    }
    return $skywin_account;
}
function user_login($submitted_data){
    $user_id = isset( UM()->login()->auth_id ) ? UM()->login()->auth_id : '';
     if ( empty( $user_id ) ) {
        return;
    }
    update_wordpress_user( $user_id );
}
add_action( 'um_user_login', 'user_login' );
function reset_password_errors( $submission_data, $form_data ) {
    $skywin_account = skywin_hub_db()->get_account_by_email( $submission_data['username_b'] );
    if( isset($skywin_account['InternalNo']) && !empty($skywin_account['InternalNo']) ){
        create_wordpress_user($skywin_account);
    }else{
        UM()->form()->add_error( 'username_b', 'Could not find emailaddress please contact the administration' );
    }
}
add_action( 'um_reset_password_errors_hook', 'reset_password_errors', 10, 2 );
function um_custom_validate( $args ) {
    error_log('um_custom_validate');
    if(isset($_REQUEST['um_action']) && $_REQUEST['um_action'] == 'edit'){
        $skywin_account = update_skywin_user($args);
        error_log('Skywin_Hub_API::update_skywin_user' . print_r($skywin_account, true));
        return $skywin_account;
    }
    $admin_email = get_option('admin_email');
    $pid_exists = skywin_hub_db()->get_account_by_pid($args['pid']);

    if( $args['nationalityCode'] == 'SE' && $pid_exists ){
        UM()->form()->add_error( 'pid', 'Social security number is already registered' );
    }
    if(isset($args['pid']) && !empty($args['pid']) ){
        $pid_exists = get_users(array(
            'meta_key' => 'pid',
            'meta_value' => $args['pid'],
            'number' => 1
        ));
        if( $args['nationalityCode'] == 'SE' && $pid_exists ){
            UM()->form()->add_error( 'pid', 'Social security number is already registered' );
        }
    }
    $memberNo_exists = skywin_hub_db()->get_account_by_MemberNo($args['memberNo']);
    if( $args['nationalityCode'] == 'SE' && $memberNo_exists ){
        UM()->form()->add_error( 'memberNo', 'Member number is already registered' );
    }
    if(isset($args['memberNo']) && !empty($args['memberNo']) ){
        $memberNo_exists = get_users(array(
            'meta_key' => 'memberNo',
            'meta_value' => $args['memberNo'],
            'number' => 1
        ));
        if( $args['nationalityCode'] == 'SE' && $memberNo_exists ){
            UM()->form()->add_error( 'memberNo', 'Member number is already registered' );
        }
    }
    $email_exists = skywin_hub_db()->get_account_by_email($args['user_email']);
    if ( isset( $args['user_email'] ) ) {
		if ( isset( UM()->form()->errors['user_email'] ) ) {
			unset( UM()->form()->errors['user_email'] );
		}
		if ( empty( $args['user_email'] ) ) {
			UM()->form()->add_error( 'user_email', __( 'E-mail Address is required', 'ultimate-member' ) );
		} elseif ( ! is_email( $args['user_email'] ) ) {
			UM()->form()->add_error( 'user_email', __( 'The email you entered is invalid', 'ultimate-member' ) );
		} elseif ( email_exists( $args['user_email'] ) ) {
			UM()->form()->add_error( 'user_email', __( 'The email you entered is already registered', 'ultimate-member' ) );
		} elseif ( $email_exists ){
            UM()->form()->add_error( 'user_email', __('Email is already registered.') );
        }
	} else {
        UM()->form()->add_error( 'user_email', __( 'E-mail Address is required', 'ultimate-member' ) );
    }
}
add_action('um_submit_form_errors_hook_', 'um_custom_validate', 10, 1);
function before_user_is_approved($user_id){
    UM()->form()->add_error( 'user_email2', 'Email2 is already registered.' );
    if( !isset($_REQUEST['_um_password_reset']) || $_REQUEST['_um_password_reset'] != true ){
        create_skywin_user( $user_id );
    }
}
add_action('um_before_user_is_approved', 'before_user_is_approved', 10, 2);
function user_before_updating_profile($user){
}
add_action( 'um_user_before_updating_profile', 'user_before_updating_profile', 10, 1 );
function set_default_profile_privacy($profile_privacy, $object_id, $meta_key) {
    if ($meta_key === 'profile_privacy') {
        $profile_privacy = 'Only me';
    }
    return $profile_privacy;
}
add_filter('get_user_metadata', 'set_default_profile_privacy', 10, 3);
function user_profile_restricted_edit_fields( $fields ){
    unset( $fields[0] );
    return $fields;
}
add_filter( 'um_user_profile_restricted_edit_fields', 'user_profile_restricted_edit_fields');
