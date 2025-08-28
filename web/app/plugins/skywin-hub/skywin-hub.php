<?php
/*
    @wordpress-plugin
    Plugin Name:       Skywin Hub
    Plugin URI:        https://skycloud.nu
    Description:       Skywin hub
    Version:           0.0.1
    Author:            Tåbbe
    Author URI:        https://skycloud.nu
    Text Domain:       skywin-hub
    Domain Path:       /languages
    Copyright:         2021 Tåbbe
    License:           GNU General Public License v3.0
    License URI:       http://www.gnu.org/licenses/gpl-3.0.html
*/

defined('ABSPATH') || exit;
$countries = [];
$clubs = [];
$genders = [];
$typelicenses = [];

if ( ! defined( 'SW_PLUGIN_FILE' ) ) {
    define( 'SW_PLUGIN_FILE', __FILE__ );
}
if( ! class_exists('Skywin_Hub') ){
    include_once dirname( SW_PLUGIN_FILE ) . '/includes/class-skywin-hub.php';
    function SKYWIN_HUB() {
        return Skywin_Hub::instance();
    }
    $GLOBALS['skywin_hub'] = SKYWIN_HUB();
}
function skywin_countries(){
    global $countries;
    
    if( empty($countries) ){
        $countries = skywin_hub_db()->get_typecountries();
    }

    if( is_wp_error($countries) ){
        return [];
    }
    $countries_array = [];
    foreach ($countries as $key => $country) {
        $countries_array[$country['CountryCode']] = $country['CountryName'];
    }

    return $countries_array;
}
function skywin_clubs(){
    global $clubs;
    if( empty($clubs) ){
        $clubs = skywin_hub_db()->clubs('Y');
    }
    if( is_wp_error($clubs) ){
        return [];
    }
    $clubs_array = [];
    foreach ($clubs as $key => $club) {
        $clubs_array[$club['Club']] = $club['Name'];
    }
    return $clubs_array;
}
function skywin_genders(){
    $genders_array = ['M' => 'Male', 'F' => 'Female'];
    return $genders_array;
}
function skywin_license_year(){
    $current_year = date('Y');
    $years = [];
    for ($i=0; $i < 2; $i++) { 
        $years[$current_year + $i] = $current_year + $i;
    }
    return $years;
}
function skywin_typelicenses(){
    global $typelicenses;
    if( empty($typelicenses) ){
        $typelicenses = skywin_hub_db()->get_typelicenses();
    }
    if( is_wp_error($typelicenses) ){
        return [];
    }
    $typelicenses_array = [];
    foreach ($typelicenses as $key => $typelicense) {
        $typelicenses_array[$typelicense['LicenseType']] = $typelicense['LicenseTypename'];
    }
    return $typelicenses_array;
}
function skywin_typeinstructors(){
        global $typeinstructors;
    if( empty($typeinstructors) ){
        $typeinstructors = skywin_hub_db()->get_typeinstructors();
    }
    if( is_wp_error($typeinstructors) ){
        return [];
    }
    $typeinstructors_array = [];
    foreach ($typeinstructors as $key => $typeinstructor) {
        $typeinstructors_array[$typeinstructor['InstructType']] = $typeinstructor['InstructTypename'];
    }
    return $typeinstructors_array;
}
function skywin_typecertificates(){
        global $typecertificates;
    if( empty($typecertificates) ){
        $typecertificates = skywin_hub_db()->get_typecertificates();
    }
    if( is_wp_error($typecertificates) ){
        return [];
    }
    $typecertificates_array = [];
    foreach ($typecertificates as $key => $typecertificate) {
        $typecertificates_array[$typecertificate['CertificateType']] = $typecertificate['CertTypename'];
    }
    return $typecertificates_array;
}