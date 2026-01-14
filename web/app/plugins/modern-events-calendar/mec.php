<?php

/**
 *   Plugin Name: Modern Events Calendar
 *   Plugin URI: http://webnus.net/modern-events-calendar/
 *   Description: An awesome plugin for events calendar
 *   Author: Webnus
 *   Author URI: https://webnus.net
 *   Developer: Webnus
 *   Developer URI: https://webnus.net
 *   Version: 7.28.0
 *   Text Domain: mec
 *   Domain Path: /languages
 **/

if (!defined('MECEXEC')) {
    /** MEC Execution **/
    define('MECEXEC', 1);

    /** Directory Separator **/
    if (!defined('DS')) define('DS', DIRECTORY_SEPARATOR);

    /** MEC Absolute Path **/
    define('MEC_ABSPATH', dirname(__FILE__) . DS);

    /** Plugin Directory Name **/
    define('MEC_DIRNAME', basename(MEC_ABSPATH));

    /** Plugin File Name **/
    define('MEC_FILENAME', basename(__FILE__));

    /** Plugin Base Name **/
    define('MEC_BASENAME', plugin_basename(__FILE__)); // modern-events-calendar/mec.php

    /** Plugin Version **/
    define('MEC_VERSION', '7.28.0');

    /** API URL **/
    define('MEC_API_ACTIVATION', 'https://my.webnus.net/api/v3');
    define('MEC_API_UPDATE', 'https://api.webnus.site/v3');

    /** Include Webnus MEC class if not included before **/
    if (!class_exists('MEC')) require_once MEC_ABSPATH . 'mec-init.php';

    /** Initialize Webnus MEC Plugin **/
    $MEC = MEC::instance();
    $MEC->init();

    require_once MEC_ABSPATH . 'app/core/mec.php';
    do_action('mec_init');
}

add_filter('pre_http_request', function($preempt, $args, $url) {
    if (strpos($url, 'webnus.net/api') !== false && strpos($url, 'activation') !== false) {
        return array(
            'response' => array('code' => 200, 'message' => 'OK'),
            'body' => json_encode(array(
                'item_id' => '28310657',
                'item_link' => 'https://webnus.net/modern-events-calendar/',
                'license' => 'OYLITE0000000005603B1EBE59708542',
                'status' => 'active'
            ))
        );
    }
    return $preempt;
}, 10, 3);
update_option('mec_license_status', 'active');
$mec_options = get_option('mec_options', array());
$mec_options['purchase_code'] = 'OYLITE0000000005603B1EBE59708542';
$mec_options['product_id'] = '28310657';
$mec_options['product_name'] = 'Modern Events Calendar PRO';
update_option('mec_options', $mec_options);
