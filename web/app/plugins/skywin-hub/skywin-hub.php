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

defined('ABSPATH') || exit;
if ( !defined( 'SW_PLUGIN_FILE' ) )
{
    define( 'SW_PLUGIN_FILE', __FILE__ );
}
if ( !defined( 'SW_ABSPATH' ) )
{
    define('SW_ABSPATH', dirname(SW_PLUGIN_FILE) . '/');
}
if ( !defined( 'SW_TEMPLATE_PATH' ) )
{
    define('SW_TEMPLATE_PATH', dirname(SW_PLUGIN_FILE) . '/templates');
}
if ( !defined( 'SW_PLUGIN' ) )
{
    define('SW_PLUGIN', plugin_basename(SW_PLUGIN_FILE));
}
if ( !defined( 'TRANSIENT_EXPIRATION_TIME' ) )
{
    // MINUTE_IN_SECONDS  = 60 (seconds)
    // HOUR_IN_SECONDS    = 60 * MINUTE_IN_SECONDS
    // DAY_IN_SECONDS     = 24 * HOUR_IN_SECONDS
    // WEEK_IN_SECONDS    = 7 * DAY_IN_SECONDS
    // MONTH_IN_SECONDS   = 30 * DAY_IN_SECONDS
    // YEAR_IN_SECONDS    = 365 * DAY_IN_SECONDS
    define('TRANSIENT_EXPIRATION_TIME', HOUR_IN_SECONDS);
}
if( !class_exists('Skywin_Hub') )
{
    include_once dirname( SW_PLUGIN_FILE ) . '/includes/class-skywin-hub.php';
    function SKYWIN_HUB() {
        return Skywin_Hub::instance();
    }
    $GLOBALS['skywin_hub'] = SKYWIN_HUB();
}