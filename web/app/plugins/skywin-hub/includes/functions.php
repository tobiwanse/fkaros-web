<?php
function skywin_status(){
    $status = skywin_hub_db()->status();
    return $status;
}
function skywin_countries()
{
    $countries = get_transient('skywin_hub_countries');
    if( !$countries ){
        $countries = [];
        $results = skywin_hub_db()->get_typecountries();
        if ( is_wp_error( $results ) ) return $countries;
        foreach ( $results as $country ) {
            $countries[$country['CountryCode']] = $country['CountryName'];
        }
        set_transient('skywin_hub_countries', $countries, TRANSIENT_EXPIRATION_TIME);
    }
    return $countries;
}
function skywin_typepayments()
{
    $paymentTypes = get_transient('skywin_hub_typepayments');
    if(!$paymentTypes){
        $typepayments = [];
        $results = skywin_hub_db()->get_typepayments('Y');
        if ( is_wp_error($results) ) return $typepayments;
        foreach ( $results as $typepayment) {
            $typepayments[$typepayment['PaymentType']] = $typepayment['PaymentType'];
        }
        set_transient('skywin_hub_typepayments', $typepayments, TRANSIENT_EXPIRATION_TIME);

    }
    return $typepayments;
    
}
function skywin_clubs()
{
    $clubs = get_transient('skywin_hub_clubs');
    if ( !$clubs ) {
        $clubs = [];
        $results = skywin_hub_db()->clubs('Y');
        if ( is_wp_error($results) ) return $clubs;
        foreach ( $results as $club) {
            $clubs[$club['Club']] = $club['Name'];
        }
        set_transient('skywin_hub_clubs', $clubs, TRANSIENT_EXPIRATION_TIME);
    }
    return $clubs;
}
function skywin_genders()
{
    $genders_array = ['M' => 'Male', 'F' => 'Female'];
    return $genders_array;
}
function skywin_license_year()
{
    $current_year = date('Y');
    $years = [];
    for ($i = 0; $i < 2; $i++) {
        $years[$current_year + $i] = $current_year + $i;
    }
    return $years;
}
function skywin_typelicenses()
{
    $typelicenses = get_transient('skywin_hub_typelicenses');
    if ( !$typelicenses ) {
        $typelicenses = [];
        $results = skywin_hub_db()->get_typelicenses();
        if ( is_wp_error( $results ) ) return $typelicenses;
        foreach ($results as $typelicense) {
            $typelicenses[$typelicense['LicenseType']] = $typelicense['LicenseTypename'];
        }
        set_transient('skywin_hub_typelicenses', $typelicenses, TRANSIENT_EXPIRATION_TIME);
    }
    return $typelicenses;
}
function skywin_typeinstructors()
{
    $typeinstructors = get_transient('skywin_hub_typeinstructors');
    if ( !$typeinstructors ) {
        $typeinstructors = [];
        $results = skywin_hub_db()->get_typeinstructors();
        if ( is_wp_error($results) ) return $typeinstructors;
        foreach ($results as $typeinstructor) {
            $typeinstructors[$typeinstructor['InstructType']] = $typeinstructor['InstructTypename'];
        }
        set_transient('skywin_hub_typeinstructors', $typeinstructors, TRANSIENT_EXPIRATION_TIME);
    }
    return $typeinstructors;
}
function skywin_typecertificates()
{
    $typecertificates = get_transient('skywin_hub_typecertificates');
    if ( !$typecertificates ) {
        $typecertificates = [];
        $results = skywin_hub_db()->get_typecertificates();
        if (is_wp_error($results)) return $typecertificates;
        foreach ($results as $typecertificate) {
            $typecertificates[$typecertificate['CertificateType']] = $typecertificate['CertTypename'];
        }
        set_transient('skywin_hub_typecertificates', $typecertificates, TRANSIENT_EXPIRATION_TIME);
    }
    return $typecertificates;
}
function skywin_typephones()
{
    $typephones = get_transient('skywin_hub_typephones');
    if ( !$typephones ) {
        $typephones = [];
        $results = skywin_hub_db()->get_typephones();
        if (is_wp_error($results)) return $typephones;
        foreach ($results as $typephone) {
            $typephones[$typephone['PhoneType']] = $typephone['PhoneTypename'];
        }
        set_transient('skywin_hub_typephones', $typephones, TRANSIENT_EXPIRATION_TIME);
    }
    return $typephones;
}
function encrypt_decrypt($stringToHandle = "", $encryptDecrypt = 'e')
{
    $output = null;
    $secret_key = '<P]6Qibu4lU+a5W#zE;A.7K/5c-O0x-l{#@Tx3hy8v<gR.4BxvTmq,vt7xcdjDN';
    $secret_iv = 'd`NF-mfd@dcH9+D.@%9j*/wTq8;t!@O>(eSCea4CN2od+}6PYkZ%{S]9Q<}4KVZG';
    if ( defined('AUTH_KEY') ) {
        $secret_key = AUTH_KEY;
    }
    if ( defined('AUTH_SALT') ) {
        $secret_iv = AUTH_SALT;
    }
    $key = hash('sha256', $secret_key);
    $iv = substr(hash('sha256', $secret_iv), 0, 16);
    if ($encryptDecrypt == 'e') {
        $output = base64_encode(openssl_encrypt($stringToHandle, "AES-256-CBC", $key, 0, $iv));
    } else if ($encryptDecrypt == 'd') {
        $output = openssl_decrypt(base64_decode($stringToHandle), "AES-256-CBC", $key, 0, $iv);
    }
    return $output;
}