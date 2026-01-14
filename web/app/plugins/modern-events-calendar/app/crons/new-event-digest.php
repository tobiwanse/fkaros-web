<?php
/**
 *  WordPress initializing
 */
function mec_find_wordpress_base_path_ned()
{
    $dir = dirname(__FILE__);

    do
    {
        if(
            (file_exists($dir.'/wp-load.php') || is_link($dir.'/wp-load.php')) &&
            (file_exists($dir.'/wp-settings.php') || is_link($dir.'/wp-settings.php'))
        ) return $dir;
    }
    while($dir = realpath($dir.'/..'));

    return NULL;
}

define('BASE_PATH', mec_find_wordpress_base_path_ned().'/');
if(!defined('WP_USE_THEMES')) define('WP_USE_THEMES', false);

global $wp, $wp_query, $wp_the_query, $wp_rewrite, $wp_did_header;
require BASE_PATH.'wp-load.php';

/** @var $main MEC_main **/

// MEC libraries
$main = MEC::getInstance('app.libraries.main');

// Blogs
$blogs = array(1);

// Current Blog ID
$multisite = function_exists('is_multisite') && is_multisite();
$current_blog_id = get_current_blog_id();

// Database
$db = $main->getDB();

// Multisite
if($multisite) $blogs = $db->select("SELECT `blog_id` FROM `#__blogs`", 'loadColumn');

$sent_notifications = 0;

foreach($blogs as $blog)
{
    // Switch to Blog
    if($multisite) switch_to_blog($blog);

    // MEC notifications
    $notifications = $main->get_notifications();

    if(!isset($notifications['new_event']['status']) || !$notifications['new_event']['status'])
    {
        if($multisite) restore_current_blog();
        continue;
    }

    /**
     * Notification Sender Library
     * @var $notif MEC_notifications
     */
    $notif = $main->getNotifications();

    $sent_notifications += $notif->send_new_event_daily_digest();

    if($multisite) restore_current_blog();
}

// Switch to Current Blog
if($multisite) switch_to_blog($current_blog_id);

echo sprintf(esc_html__('%s notification(s) sent.', 'mec'), $sent_notifications);
exit;
