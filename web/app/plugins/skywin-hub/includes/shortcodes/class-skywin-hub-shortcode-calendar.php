<?php
defined('ABSPATH') || exit;
if (!class_exists('Skywin_Hub_Shortcode_Calendar')):
    class Skywin_Hub_Shortcode_Calendar
    {
        public static function output($args)
        {
            wp_enqueue_style('skywin-calendar-css', plugin_dir_url(SW_PLUGIN_FILE) . 'assets/css/skywin-calendar.css');

            wp_enqueue_script('fullcalendar-js', plugin_dir_url(SW_PLUGIN_FILE) . 'assets/node_modules/fullcalendar-scheduler/index.global.min.js', array('jquery'), null, true);
            wp_enqueue_script('skywin-calendar-js', plugin_dir_url(SW_PLUGIN_FILE) . 'assets/js/skywin-calendar.js', array('jquery'), null, true);

            wp_localize_script('skywin-calendar-js', 'ajax_get_google_calendar_events_params', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'action' => 'get_google_calendar_events',
                '_ajax_nonce' => wp_create_nonce('ajax_get_google_calendar_events_nonce'),
            ));
            wp_localize_script('skywin-calendar-js', 'ajax_get_google_calendar_list_params', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'action' => 'get_google_calendar_list',
                '_ajax_nonce' => wp_create_nonce('ajax_get_google_calendar_list_nonce'),
            ));

            ob_start();
            load_template(SW_TEMPLATE_PATH . '/template-skywin-calendar.php', true, $args);
            return ob_get_clean();
        }
    }
endif;