<?php
/**
 * Functions for Ultimate Member integration with Skywin Hub.
 *
 * @package SkywinHub
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }
function before_form( $args ) {
    if ( ( defined( 'REST_REQUEST' ) && REST_REQUEST ) || ( defined( 'DOING_AJAX' ) && DOING_AJAX ) ) {
        return;
    }
    add_filter( 'woocommerce_enqueue_styles', '__return_empty_array' );
    $user_id = um_profile_id();
    $errors = UM()->form()->errors['heading'] ?? null;
    if ( empty( $errors ) ) {
        return;
    }

    if ( is_array( $errors ) ) {
        foreach ( $errors as $error ) {
            echo '<div class="um-field-error"><h3>' . esc_html( $error ) . '</h3></div>';
        }
    } else {
        echo '<div class="um-field-error"><h3>' . esc_html( $errors ) . '</h3></div>';
    }
}
add_action( 'um_before_form', 'before_form' );
function create_wordpress_user( $skywin_account ) {
    $password = wp_generate_password(32, true, true);
    $instructorType = explode(' ',$skywin_account['InstructorText']);
    $certificateType = explode(' ', $skywin_account['CertificateText']);
    $infoViaEmail = $skywin_account['InfoViaEmail'] == 'Y' ? ['Yes'] : ['No'];
    $verifiedLicense = $skywin_account['VerifiedLicense'] == 'Y' ? ['Yes'] : ['No'];
    $userdata = [
        'user_email'           => $skywin_account['Emailaddress'], 
        'user_pass'            => $password,
        'first_name'           => $skywin_account['FirstName'],    
        'last_name'            => $skywin_account['LastName'],     
        'show_admin_bar_front' => 'false',                         
        'role'                 => 'subscriber',
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
            'verifiedLicense'   => $verifiedLicense,
            'lastUpdated'       => $skywin_account['LastUpd'],
        ]
    ];
    $user_id = wp_insert_user( $userdata );
    return $user_id;
}
function update_wordpress_user( $user_id ) {
    um_fetch_user( $user_id );
    $skywin_account = skywin_hub_db()->get_account_by_id( um_user( 'internalNo' ) );
    if ( ! is_user_logged_in() || !isset( $skywin_account['InternalNo'] ) || empty( $skywin_account['InternalNo'] ) ) {
        UM()->form()->add_error( 'heading', __( 'Could not update your profile. Please contact the administrator.', 'skywin_hub' ) );
        return;
    }


    $instructorType     = explode( ' ', $skywin_account['InstructorText'] ) ?? [];
    $certificateType    = explode( ' ', $skywin_account['CertificateText'] ) ?? [];
    $infoViaEmail       = $skywin_account['InfoViaEmail'] == 'Y' ? [ 'Yes' ] : [ 'No' ];
    $verifiedLicense    = $skywin_account['VerifiedLicense'] == 'Y' ? [ 'Yes' ] : [ 'No' ];

    $meta_map = [
        'internalNo'        => 'InternalNo',
        'accountNo'         => 'AccountNo',
        'first_name'        => 'FirstName',
        'last_name'         => 'LastName',
        'address1'          => 'Address1',
        'postCode'          => 'Postcode',
        'postTown'          => 'Posttown',
        'country'           => 'CountryCode',
        'phoneNo'           => 'PhoneNo',
        'nationalityCode'   => 'NationalityCode',
        'pid'               => 'PID',
        'gender2'           => 'Sex',
        'homeDz'            => 'Homedz',
        'contactName'       => 'ContactName',
        'contactPhone'      => 'ContactPhone',
        'weight'            => 'Weight',
        'repackDate'        => 'RepackDate',
        'comment'           => 'Comment',
        'memberNo'          => 'MemberNo',
        'externalMemberNo'  => 'ExternalMemberNo',
        'licenseType'       => 'LicenseType',
        'year'              => 'Year',
        'club'              => 'Club',
    ];

    foreach ( $meta_map as $meta_key => $source_key ) {
        if ( isset( $skywin_account[ $source_key ] ) ) {
            update_user_meta( $user_id, $meta_key, $skywin_account[ $source_key ] );
        }
    }

    update_user_meta( $user_id, 'instructType', $instructorType );
    update_user_meta( $user_id, 'certificateType', $certificateType );
    update_user_meta( $user_id, 'infoViaEmail', $infoViaEmail );
    update_user_meta( $user_id, 'verifiedLicense', $verifiedLicense );

    $utcDate = $skywin_account['LastUpd'];
    $date = new DateTime($utcDate);
    $date->setTimezone(new DateTimeZone('Europe/Stockholm'));
    update_user_meta( $user_id, 'lastUpdated', $date->format('Y-m-d H:i:s') );

    UM()->user()->remove_cache( $user_id );
    return $user_id;
}
function create_or_update_skywin_user( $args, $form_data = null ) {
    $user_id = um_profile_id();
    um_fetch_user( um_profile_id() );
    $instructorText = isset( $args['instructType'] ) ? implode( ' ', $args['instructType'] ) : '';
    $certificateText = isset( $args['certificateType'] ) ? implode( ' ', $args['certificateType'] ) : '';
    $infoViaEmail = isset( $args['infoViaEmail'] ) && $args['infoViaEmail'][0] === 'Yes';
    $verifiedLicense = isset( $args['verifiedLicense'] ) && $args['verifiedLicense'][0] === 'Yes';
    $repackDate = isset( $args['repackDate'] ) ? date( 'Y-m-d', strtotime( $args['repackDate'] ) ) : '';

    if( isset($args['nationalityCode']) && $args['nationalityCode'] === 'SE' ){
        
    }
    
    $body = [
        'firstName'         => sanitize_text_field( $args['first_name'] ?? um_user( 'first_name' ) ),
        'lastName'          => sanitize_text_field( $args['last_name'] ?? um_user( 'last_name' ) ),
        'emailAddress'      => sanitize_email( $args['user_email'] ?? um_user( 'user_email' ) ),
        'address1'          => sanitize_text_field( $args['address1'] ?? '' ),
        'postCode'          => sanitize_text_field( $args['postCode'] ?? '' ),
        'postTown'          => sanitize_text_field( $args['postTown'] ?? '' ),
        'countryCode'       => sanitize_text_field( $args['country'] ?? '' ),
        'phoneNo'           => sanitize_text_field( $args['phoneNo'] ?? '' ),
        'weight'            => sanitize_text_field( $args['weight'] ?? '' ),
        'repackDate'        => sanitize_text_field( $repackDate ),
        'gender'            => sanitize_text_field( $args['gender2'] ?? '' ),
        'nationalityCode'   => sanitize_text_field( $args['nationalityCode'] ?? '' ),
        'memberNo'          => sanitize_text_field( $args['memberNo'] ?? '' ),
        'externalMemberNo'  => sanitize_text_field( $args['externalMemberNo'] ?? '' ),
        'year'              => sanitize_text_field( $args['year'] ?? '' ),
        'club'              => sanitize_text_field( $args['club'] ?? '' ),
        'homeDz'            => sanitize_text_field( $args['homeDz'] ?? '' ),
        'instructorText'    => sanitize_text_field( $instructorText ),
        'certificateText'   => sanitize_text_field( $certificateText ),
        'licenseType'       => sanitize_text_field( $args['licenseType'] ?? '' ),
        'contactName'       => sanitize_text_field( $args['contactName'] ?? '' ),
        'contactPhone'      => sanitize_text_field( $args['contactPhone'] ?? '' ),
        'infoViaEmail'      => $infoViaEmail,
        'verifiedLicense'   => $verifiedLicense
    ];
    if( isset($args['pid']) && !empty($args['pid']) ){
        if( $args['pid'] !== um_user('pid') ){
            $body['pid'] = $args['pid'];
        }
    }

    if ( ! um_user( 'internalNo' ) ) {
        $skywin_account = skywin_hub_api()->create_skywin_account( $body );
    } else {
        $skywin_account = skywin_hub_api()->update_skywin_account( $body, um_user( 'internalNo' ) );
    }
    if ( !is_wp_error( $skywin_account ) && isset( $skywin_account['id'] ) && ! empty( $skywin_account['id'] ) ) {
        update_wordpress_user($user_id);
    }
    return $skywin_account;
}
function on_login_before_redirect($user_id){
    $result = update_wordpress_user($user_id);
    if( !$result ){
        $admin_email = get_bloginfo( 'admin_email' );
        UM()->form()->add_error('heading', "Could not update profile. Please contact $admin_email");
    }
}
add_action( 'um_on_login_before_redirect', 'on_login_before_redirect', 10, 1 );
function reset_password_errors( $submission_data, $form_data ) {
    $skywin_account = skywin_hub_db()->get_account_by_email( $submission_data['username_b'] );
    if( is_wp_error($skywin_account) ){
        wp_safe_redirect( home_url() . '/error/' );
        exit;
    }
    if( isset($skywin_account['InternalNo']) && !empty($skywin_account['InternalNo']) ){
        $result = create_wordpress_user($skywin_account);
    }
}
add_action( 'um_reset_password_errors_hook', 'reset_password_errors', 10, 2 );
function um_custom_validate_form( $args, $form_data ) {
    $mode = $form_data['mode'];
    if ( isset( $args['user_email'] ) && ! empty( $args['user_email'] ) ) {
        if( um_user('user_email') !== $args['user_email'] ){
            $email_exists = skywin_hub_db()->email_exists( $args['user_email'] );
            if( is_wp_error($email_exists) ) {
                //UM()->form()->add_error( 'heading', __( 'An error occurred while validating the email. Please try again later.', 'skywin_hub' ) );
                //return;
            }
            if ( $email_exists ) {
                //UM()->form()->add_error( 'user_email', __( 'Email is already registered.', 'skywin_hub' ) );
            }
        }
    }
    if ( isset( $args['pid'] ) && ! empty( $args['pid'] ) ) {
        $pid_exists = skywin_hub_db()->pid_exists( $args['pid'] );
        if( is_wp_error($pid_exists) ) {
            //UM()->form()->add_error( 'heading', __( 'An error occurred while validating the social security number. Please try again later.', 'skywin_hub' ) );
            //return;
        }
        if ( $args['nationalityCode'] === 'SE' && $pid_exists ) {
            //UM()->form()->add_error( 'pid', __( 'Social security number is already registered.', 'skywin_hub' ) );
        }
    }
    if ( isset( $args['memberNo'] ) && ! empty( $args['memberNo'] ) ) {
        $current_memberNo = um_user( 'memberNo' );
        if ( $current_memberNo !== $args['memberNo'] ) {
            $memberNo_exists = skywin_hub_db()->memberno_exists( $args['memberNo'] );
            if ( $args['nationalityCode'] === 'SE' && isset( $memberNo_exists['MemberNo'] ) ) {
                //UM()->form()->add_error( 'memberNo', __( 'License number is already registered.', 'skywin_hub' ) );
            }
        }
    }
    if ( um_user( 'internalNo' ) && ! empty( um_user( 'internalNo' ) ) ) {
        $internalNoExists = skywin_hub_db()->internalNo_exists( um_user( 'internalNo' ) );
        if ( ! $internalNoExists ) {
            $admin_email = get_bloginfo( 'admin_email' );
            //UM()->form()->add_error( 'heading', sprintf( __( 'Could not update your profile. Please contact %s', 'skywin_hub' ), esc_html( $admin_email ) ) );
        }
    }
    if ( ! UM()->form()->errors && 'profile' === $mode ) {
        $result = create_or_update_skywin_user( $args, $form_data );
        if ( is_wp_error( $result ) ) {
            $admin_email = get_bloginfo( 'admin_email' );
            $errors = $result->get_error_message();
            if( is_array($errors) ){
                foreach($errors as $error){
                    if( isset($error['field']) && isset($error['message']) ){
                        UM()->form()->add_error( $error['field'], esc_html( $error['message'] ) );
                        continue;
                    }
                    UM()->form()->add_error( 'heading', sprintf( __( 'Could not update your profile: %s. Please contact %s', 'skywin_hub' ), esc_html( $error ), esc_html( get_bloginfo( 'admin_email' ) ) ) );
                }
            } else {
                UM()->form()->add_error( 'heading', sprintf( __( 'Could not update your profile. Please contact %s', 'skywin_hub' ), esc_html( $errors ), esc_html( get_bloginfo( 'admin_email' ) ) ) );
            }
        }
    }
}
add_action('um_submit_form_errors_hook', 'um_custom_validate_form', 10, 2);
// function before_user_is_approved($user_id){
//     if( !isset($_REQUEST['_um_password_reset']) || $_REQUEST['_um_password_reset'] != true ){
//         create_skywin_user( $user_id );
//     }
// }
// add_action('um_before_user_is_approved', 'before_user_is_approved', 10, 2);
// function set_default_profile_privacy($profile_privacy, $object_id, $meta_key) {
//     if ($meta_key === 'profile_privacy') {
//         $profile_privacy = 'Only me';
//     }
//     return $profile_privacy;
// }
// add_filter('get_user_metadata', 'set_default_profile_privacy', 10, 3);
function user_profile_restricted_edit_fields( $fields ){
    return $fields;
}
add_filter( 'um_user_profile_restricted_edit_fields', 'user_profile_restricted_edit_fields');
