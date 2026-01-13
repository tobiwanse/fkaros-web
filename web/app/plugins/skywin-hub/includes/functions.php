<?php

function skywin_countries()
{
    global $countries;

    if (empty($countries)) {
        $countries = skywin_hub_db()->get_typecountries();
    }

    if (is_wp_error($countries)) {
        return [];
    }
    $countries_array = [];
    foreach ($countries as $key => $country) {
        $countries_array[$country['CountryCode']] = $country['CountryName'];
    }

    return $countries_array;
}
function skywin_clubs()
{
    global $clubs;
    if (empty($clubs)) {
        $clubs = skywin_hub_db()->clubs('Y');
    }
    if (is_wp_error($clubs)) {
        return [];
    }
    $clubs_array = [];
    foreach ($clubs as $key => $club) {
        $clubs_array[$club['Club']] = $club['Name'];
    }
    return $clubs_array;
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
    global $typelicenses;
    if (empty($typelicenses)) {
        $typelicenses = skywin_hub_db()->get_typelicenses();
    }
    if (is_wp_error($typelicenses)) {
        return [];
    }
    $typelicenses_array = [];
    foreach ($typelicenses as $key => $typelicense) {
        $typelicenses_array[$typelicense['LicenseType']] = $typelicense['LicenseTypename'];
    }
    return $typelicenses_array;
}
function skywin_typeinstructors()
{
    global $typeinstructors;
    if (empty($typeinstructors)) {
        $typeinstructors = skywin_hub_db()->get_typeinstructors();
    }
    if (is_wp_error($typeinstructors)) {
        return [];
    }
    $typeinstructors_array = [];
    foreach ($typeinstructors as $key => $typeinstructor) {
        $typeinstructors_array[$typeinstructor['InstructType']] = $typeinstructor['InstructTypename'];
    }
    return $typeinstructors_array;
}
function skywin_typecertificates()
{
    global $typecertificates;
    if (empty($typecertificates)) {
        $typecertificates = skywin_hub_db()->get_typecertificates();
    }
    if (is_wp_error($typecertificates)) {
        return [];
    }
    $typecertificates_array = [];
    foreach ($typecertificates as $key => $typecertificate) {
        $typecertificates_array[$typecertificate['CertificateType']] = $typecertificate['CertTypename'];
    }
    return $typecertificates_array;
}
function encrypt_decrypt($stringToHandle = "", $encryptDecrypt = 'e')
{
    $output = null;
    $secret_key = 'hgfdr3ys%h';
    $secret_iv = 'e*rt"dh46Gv';
    if (defined('AUTH_KEY')) {
        $secret_key = AUTH_KEY;
    }
    if (defined('AUTH_SALT')) {
        $secret_iv = AUTH_SALT;
    }
    $key = hash('sha256', $secret_key);
    $iv = substr(hash('sha256', $secret_iv), 0, 16); // using salt technique
    if ($encryptDecrypt == 'e') {
        $output = base64_encode(openssl_encrypt($stringToHandle, "AES-256-CBC", $key, 0, $iv));
    } else if ($encryptDecrypt == 'd') {
        $output = openssl_decrypt(base64_decode($stringToHandle), "AES-256-CBC", $key, 0, $iv);
    }
    return $output;
}