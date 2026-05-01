<?php
/*
    @wordpress-plugin
    Plugin Name:       Jumprunner
    Plugin URI:        https://skycloud.nu
    Description:       Jumprunner
    Version:           1.0.0
    Author:            Tåbbe
    Author URI:        https://skycloud.nu
    Text Domain:       jumprunner
    Domain Path:       /languages
    Copyright:         2026 Tåbbe
    License:           GNU General Public License v3.0
    License URI:       http://www.gnu.org/licenses/gpl-3.0.html
*/

defined('ABSPATH') || exit;

if ( ! defined( 'JR_PLUGIN_FILE' ) ) {
    define( 'JR_PLUGIN_FILE', __FILE__ );
}
if ( ! defined( 'JR_ABSPATH' ) ) {
    define( 'JR_ABSPATH', dirname( JR_PLUGIN_FILE ) . '/' );
}
if ( ! defined( 'JR_TEMPLATE_PATH' ) ) {
    define( 'JR_TEMPLATE_PATH', dirname( JR_PLUGIN_FILE ) . '/templates' );
}
if ( ! defined( 'JR_PLUGIN' ) ) {
    define( 'JR_PLUGIN', plugin_basename( JR_PLUGIN_FILE ) );
}

if ( ! class_exists( 'Jumprunner' ) ) {
    include_once dirname( JR_PLUGIN_FILE ) . '/includes/class-jumprunner.php';
    function JUMPRUNNER() {
        return Jumprunner::instance();
    }
    $GLOBALS['jumprunner'] = JUMPRUNNER();
}
