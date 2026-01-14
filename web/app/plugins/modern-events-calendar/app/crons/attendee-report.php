<?php
function mec_find_wordpress_base_path_ar()
{
    $dir = dirname(__FILE__);

    do
    {
        if (
            (file_exists($dir . '/wp-load.php') || is_link($dir . '/wp-load.php')) &&
            (file_exists($dir . '/wp-settings.php') || is_link($dir . '/wp-settings.php'))
        ) return $dir;
    }
    while ($dir = realpath($dir . '/..'));

    return null;
}

define('BASE_PATH', mec_find_wordpress_base_path_ar() . '/');
if (!defined('WP_USE_THEMES')) define('WP_USE_THEMES', false);

global $wp, $wp_query, $wp_the_query, $wp_rewrite, $wp_did_header;
require BASE_PATH . 'wp-load.php';

$main = MEC::getInstance('app.libraries.main');

// Blogs
$blogs = [1];

// Current Blog ID
$multisite = function_exists('is_multisite') && is_multisite();
$current_blog_id = get_current_blog_id();

// Database
$db = $main->getDB();

// Multisite
if ($multisite) $blogs = $db->select("SELECT `blog_id` FROM `#__blogs`", 'loadColumn');

$sent_reports = 0;
$now = current_time('Y-m-d H:00');

foreach ($blogs as $blog)
{
    // Switch to Blog
    if ($multisite) switch_to_blog($blog);

    // MEC Notifications
    $notifications = $main->get_notifications();

    // MEC Settings
    $settings = $main->get_settings();

    // Booking is disabled
    if (!isset($settings['booking_status']) || !$settings['booking_status']) continue;

    // Status
    $status = isset($notifications['attendee_report']['status']) && $notifications['attendee_report']['status'];

    // Attendee Report Email is disabled
    if (!$status) continue;

    // Hours
    $hours = isset($notifications['attendee_report']['hours']) ? explode(',', trim($notifications['attendee_report']['hours'], ', ')) : [];

    // Hours are invalid
    if (!is_array($hours) || !count($hours)) continue;

    // Check Last Run Date & Time
    $latest_run = get_option('mec_attendee_report_last_run_datetime', null);
    if ($latest_run && strtotime($latest_run) > strtotime('-1 Hour', strtotime($now))) continue;

    /**
     * Notification Sender Library
     */
    $notif = $main->getNotifications();

    foreach ($hours as $hour)
    {
        $hour = (int) trim($hour, ', ');

        // Hour is not accepted as a valid value for hours
        if ($hour <= 0) continue;

        // It's time of the hour that we're going to check
        $time = strtotime('+' . $hour . ' hours', strtotime($now));
        $tstart = floor($time / 3600) * 3600;
        $tend = $time + 3600;

        $mec_dates = $db->select("SELECT `post_id`, `tstart`, `tend` FROM `#__mec_dates` WHERE `tstart` >= $tstart AND `tstart` < $tend", 'loadObjectList');
        if (!count($mec_dates)) continue;

        foreach ($mec_dates as $mec_date)
        {
            if (!get_post($mec_date->post_id)) continue;

            $result = $notif->attendee_report($mec_date->post_id, $mec_date->tstart . ':' . $mec_date->tend);
            if ($result) $sent_reports++;
        }
    }

    // Last Run
    update_option('mec_attendee_report_last_run_datetime', $now, 'no');
}

// Switch to Current Blog
if ($multisite) switch_to_blog($current_blog_id);

echo sprintf(esc_html__('%s notification(s) sent.', 'mec'), $sent_reports);
exit;
