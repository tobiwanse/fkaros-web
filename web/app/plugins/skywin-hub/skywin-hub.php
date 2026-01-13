<?php
/*
    @wordpress-plugin
    Plugin Name:       Skywin Hub
    Plugin URI:        https://skycloud.nu
    Description:       Skywin hub
    Version:           1.0.0
    Author:            Tåbbe
    Author URI:        https://skycloud.nu
    Text Domain:       skywin-hub
    Domain Path:       /languages
    Copyright:         2021 Tåbbe
    License:           GNU General Public License v3.0
    License URI:       http://www.gnu.org/licenses/gpl-3.0.html
*/
//test commit
defined('ABSPATH') || exit;
$countries = [];
$clubs = [];
$genders = [];
$typelicenses = [];

if ( !defined( 'SW_PLUGIN_FILE' ) ) {
    define( 'SW_PLUGIN_FILE', __FILE__ );
}
if ( !defined( 'SW_ABSPATH' ) ) {
    define('SW_ABSPATH', dirname(SW_PLUGIN_FILE) . '/');
}
if ( !defined( 'SW_TEMPLATE_PATH' ) ) {
    define('SW_TEMPLATE_PATH', dirname(SW_PLUGIN_FILE) . '/templates');
}
if ( !defined( 'SW_PLUGIN' ) ) {
    define('SW_PLUGIN', plugin_basename(SW_PLUGIN_FILE));
}
if( !class_exists('Skywin_Hub') ){
    include_once dirname( SW_PLUGIN_FILE ) . '/includes/class-skywin-hub.php';
    function SKYWIN_HUB() {
        return Skywin_Hub::instance();
    }
    $GLOBALS['skywin_hub'] = SKYWIN_HUB();
}