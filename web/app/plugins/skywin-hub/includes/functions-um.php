<?php
/**
 * Functions for Ultimate Member integration with Skywin Hub.
 *
 * @package SkywinHub
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }
function before_form( $args ) {
    if ( (defined('REST_REQUEST') && REST_REQUEST) || (defined('DOING_AJAX') && DOING_AJAX) ) return;
    add_filter( 'woocommerce_enqueue_styles', '__return_empty_array' );
    if( is_wp_error(skywin_hub_api()->status()) || is_wp_error(skywin_hub_db()->status()) ){
        wp_redirect( home_url() . '/error/' );
        die();
    }
    if( is_user_logged_in() ){
        $user_id = get_current_user_id();
        if( isset($_REQUEST['um_action']) && $_REQUEST['um_action'] != 'edit' ){
            return;
        }
        if( is_page('user') ){
            $result = update_wordpress_user($user_id);
            UM()->user()->remove_cache( $user_id );
            UM()->form()->add_error('heading', 'message');
        }
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
add_action( 'um_before_form', 'before_form', 1, 1 );
function create_wordpress_user( $skywin_account ) {
    $instructorType = explode(' ',$skywin_account['InstructorText']);
    $certificateType = explode(' ', $skywin_account['CertificateText']);
    $infoViaEmail = $skywin_account['InfoViaEmail'] == 'Y' ? ['Yes'] : ['No'];
    $verifiedLicense = $skywin_account['VerifiedLicense'] == 'Y' ? ['Yes'] : ['No'];
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
            'instructType'      => $instructorType,
            'licenseType'       => $skywin_account['LicenseType'],
            'certificateType'   => $certificateType,
            'year'              => $skywin_account['Year'],
            'club'              => $skywin_account['Club'],
            'infoViaEmail'      => $infoViaEmail,
            'internalNo'        => $skywin_account['InternalNo'],
            'accountNo'         => $skywin_account['AccountNo'],
            'comment'           => $skywin_account['Comment'],
            'verifiedLicense'   => $verifiedLicense
        ]
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
    if( !is_user_logged_in() || !isset($skywin_account['InternalNo']) || empty($skywin_account['InternalNo']) ){
        return false;
    }
    $instructorType = explode(' ', $skywin_account['InstructorText']) ?? [];
    $certificateType = explode(' ', $skywin_account['CertificateText']) ?? [];
    $infoViaEmail = $skywin_account['InfoViaEmail'] == 'Y' ? ['Yes'] : ['No'];
    $verifiedLicense = $skywin_account['VerifiedLicense'] == 'Y' ? ['Yes'] : ['No'];
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
    update_user_meta( $user_id, 'instructType', $instructorType );
    update_user_meta( $user_id, 'licenseType', $skywin_account['LicenseType'] );
    update_user_meta( $user_id, 'certificateType',  $certificateType );
    update_user_meta( $user_id, 'year', $skywin_account['Year'] );
    update_user_meta( $user_id, 'club', $skywin_account['Club'] );
    update_user_meta( $user_id, 'infoViaEmail', $infoViaEmail );
    update_user_meta( $user_id, 'internalNo', $skywin_account['InternalNo'] );
    update_user_meta( $user_id, 'accountNo', $skywin_account['AccountNo'] );
    update_user_meta( $user_id, 'verifiedLicense', $verifiedLicense );
    return true;
}
function create_skywin_user( $user_id ) {
    um_fetch_user( $user_id );
    $instructTypeText = um_user('instructType');
    $certificateTypeText = um_user('certificateType');
    $instructType = is_array($instructTypeText) ? implode(" ",$instructTypeText) : "";
    $certificateType = is_array( $certificateTypeText) ? implode(" ", $certificateTypeText) : "";
    $infoViaEmail = um_user('infoViaEmail');
    $infoViaEmail = isset($infoViaEmail) && um_user('infoViaEmail')[0] == 'Yes' ? true : false;

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
        'infoViaEmail'      => $infoViaEmail,
    );

    $skywin_account = skywin_hub_api()->create_skywin_account( $body );
    if( is_wp_error( $skywin_account ) ){
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
    if( !is_user_logged_in() || is_admin()){
        return; //silens;
    }
    um_fetch_user( $args['user_id'] );
    $instructorText = isset($args["instructType"]) ? implode(" ", $args["instructType"]) : '';
    $certificateText = isset($args["certificateType"]) ? implode(" ", $args["certificateType"]) : '';
    $infoViaEmail = isset($args["infoViaEmail"]) && $args["infoViaEmail"][0] == 'Yes' ? true : false;
    $verifiedLicense = isset($args["verifiedLicense"]) && $args["verifiedLicense"][0] == 'Yes' ? true : false;

    $body = array(
        'firstName'         => $args["first_name"],
        'lastName'          => $args["last_name"],
        'nickName'          => $args["nickname"],
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
        'instructorText'    => $instructorText,
        'certificateText'   => $certificateText,
        'licenseType'       => $args["licenseType"],
        'contactName'       => $args["contactName"],
        'contactPhone'      => $args["contactPhone"],
        'infoViaEmail'      => $infoViaEmail,
        'verifiedLicense'   => $verifiedLicense
    );
    $skywin_account = skywin_hub_api()->update_skywin_account( $body, um_user('internalNo') );
    if( is_wp_error( $skywin_account ) ){
        if( isset($skywin_account->errors) ){
            foreach( $skywin_account->errors as $error ){
                UM()->form()->add_error( $error['field'], $error['message'] );
            }
        }
    }
    return $skywin_account;
}
function user_login($submitted_data){}
add_action( 'um_user_login', 'user_login' );
function reset_password_errors( $submission_data, $form_data ) {
    $skywin_account = skywin_hub_db()->get_account_by_email( $submission_data['username_b'] );
    if( is_wp_error($skywin_account) ){
        wp_safe_redirect( home_url() . '/error/' );
        exit;
    }
    if( isset($skywin_account['InternalNo']) && !empty($skywin_account['InternalNo']) ){
        create_wordpress_user($skywin_account);
    }else{
        UM()->form()->add_error( 'username_b', 'Could not find emailaddress please contact the admin' );
    }
}
add_action( 'um_reset_password_errors_hook', 'reset_password_errors', 10, 2 );
function um_custom_validate( $args ) {
    if( is_user_logged_in() && isset($_REQUEST['um_action']) && $_REQUEST['um_action'] == 'edit' ){
        $result = update_skywin_user($args);
        if( is_wp_error($result) || array_key_exists('errors', $result)){
            $admin_email = get_bloginfo( 'admin_email' );
            UM()->form()->add_error('heading', "Something whent wrong. Please contact $admin_email");
            return false;
        }
        return true;
    }
    $pid_exists = skywin_hub_db()->pid_exists($args['pid']);
    if( $args['nationalityCode'] == 'SE' && $pid_exists){
        UM()->form()->add_error( 'pid', 'Social security number is already registered' );
    }
    $memberNo_exists = skywin_hub_db()->get_account_by_MemberNo($args['memberNo']);
    if( $args['nationalityCode'] == 'SE' && isset($memberNo_exists['MemberNo']) ){
        UM()->form()->add_error( 'memberNo', 'License number is already registered' );
    }
    $email_exists = skywin_hub_db()->get_account_by_email($args['user_email']);
    if ( $email_exists ){
        UM()->form()->add_error( 'user_email', __('Email is already registered.') );
    }
    if ( !empty(UM()->form()->errors) ) {
        return false;
    }
}
add_action('um_submit_form_errors_hook_', 'um_custom_validate', 10, 1);
function before_user_is_approved($user_id){
    if( !isset($_REQUEST['_um_password_reset']) || $_REQUEST['_um_password_reset'] != true ){
        create_skywin_user( $user_id );
    }
}
add_action('um_before_user_is_approved', 'before_user_is_approved', 10, 2);
function set_default_profile_privacy($profile_privacy, $object_id, $meta_key) {
    if ($meta_key === 'profile_privacy') {
        $profile_privacy = 'Only me';
    }
    return $profile_privacy;
}
add_filter('get_user_metadata', 'set_default_profile_privacy', 10, 3);
function user_profile_restricted_edit_fields( $fields ){
    return $fields;
}
add_filter( 'um_user_profile_restricted_edit_fields', 'user_profile_restricted_edit_fields');
