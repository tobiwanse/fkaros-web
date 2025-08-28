<?php
defined( 'ABSPATH' ) || exit;
if ( !class_exists('Skywin_Hub_Calendar') ):
class Skywin_Hub_Calendar {
    protected static $_instance = null;
    protected $colorlist;
    public static function instance() {
        if ( is_null(self::$_instance )) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }
    public function __construct(){
        $this->includes();
        $this->add_actions();
    }
    private function includes()
    {
        include_once SW_ABSPATH . 'includes/shortcodes/class-skywin-hub-shortcode-calendar.php';
    }
    private function add_actions()
    {
        add_action( 'wp_ajax_get_google_calendar_list', array($this, 'ajax_get_google_calendar_list'));
        add_action( 'wp_ajax_nopriv_get_google_calendar_list', array($this, 'ajax_get_google_calendar_list'));
        add_action( 'wp_ajax_get_google_calendar_events', array($this, 'ajax_get_google_calendar_events') );
        add_action( 'wp_ajax_nopriv_get_google_calendar_events', array($this, 'ajax_get_google_calendar_events') );
    }
    public function ajax_get_google_calendar_events()
    {
        check_ajax_referer( 'ajax_get_google_calendar_events_nonce', '_ajax_nonce' );
        if (!class_exists('Skywin_Hub_Google_Api')) {
            echo json_encode([]);
            wp_die();
        }
    
        $events = $this->get_google_calendar_events();
        wp_send_json($events);
        wp_die();
    }
    public function ajax_get_google_calendar_list()
    {
        check_ajax_referer( 'ajax_get_google_calendar_list_nonce', '_ajax_nonce' );
        if (!class_exists('Skywin_Hub_Google_Api')) {
            echo json_encode([]);
            wp_die();
        }
        $calendars = $this->get_google_calendar_list();
        wp_send_json($calendars);
        wp_die();
    }
    private function get_google_calendar_events()
    {
        if (!class_exists('Skywin_Hub_Google_Api')) {
            return [];
        }
        $calendars = skywin_hub_google_api()->get_calendar_list();
        if(!$calendars){
            return [];
        }
        $optParams = [
            //'timeMin' => $_REQUEST['start'],
            //'timeMax' => $_REQUEST['end'],
            // 'singleEvents' => true,
            // 'orderBy' => 'startTime',
        ];
        $events_array = [];
        foreach ($calendars->getItems() as $calendar) {
            $calendarId = $calendar->getId();
            $calendarColor = $this->get_calendar_color($calendar->getColorId());
            $events = skywin_hub_google_api()->get_events($calendarId, $optParams);
            $events_array[] = $events;

            foreach ($events as $event) {
                $id = $event->getId();
                $summary = $event->getSummary();
                $description = $event->getDescription();
                $location = $event->getLocation();
                $HtmlLink = $event->getHtmlLink();
                $anttendees = $event->getAttendees();
                $organizer = $event->getOrganizer();
                $color = $event->getColorId() ? $this->get_event_color($event->getColorId()) : $calendarColor;
                $start = $event->getStart()->getDateTime() ?? $event->getStart()->getDate();
                $end = $event->getEnd()->getDateTime() ?? $event->getEnd()->getDate();
                $allDay = $event->getStart()->getDate() ? true : false;
 
                $classNames = [];
                if((strtotime($end) < time())){
                    $classNames[] = 'past-event';
                }

                $events_array[] = [
                    'id' => $id,
                    'title' => $summary,
                    'start' => $start,
                    'end' => $end,
                    'allDay' => $allDay,
                    'display' => 'block',
                    'color' => $color['background'],
                    'backgroundColor' => $color['background'],
                    'textColor' => $color['foreground'],
                    'classNames' => $classNames,
                    'extendedProps' => array(),
                    'summary' => $summary,
                    'description' => $description,
                    'location' => $location,
                    'HtmlLink' => $HtmlLink,
                    'anttendees' => $anttendees,
                    'organizer' => $organizer,
                ];
            }
        }
        return $events_array;
    }
    private function get_google_calendar_list()
    {
        if (!class_exists('Skywin_Hub_Google_Api')) {
            return [];
        }
        $calendars = skywin_hub_google_api()->get_calendar_list();
        return $calendars;
    }
    private function get_calendar_color($colorId)
    {
        $colors = [
            '1' => ['background' => '#795548', 'foreground' => '#ffffff'], // Cocoa
            '2' => ['background' => '#E67C73', 'foreground' => '#ffffff'], // Flamingo
            '3' => ['background' => '#D50000', 'foreground' => '#ffffff'], // Tomato
            '4' => ['background' => '#F4511E', 'foreground' => '#ffffff'], // Tangerine
            '5' => ['background' => '#EF6C00', 'foreground' => '#ffffff'], // Pumpkin
            '6' => ['background' => '#F09300', 'foreground' => '#ffffff'], // Mango
            '7' => ['background' => '#009688', 'foreground' => '#ffffff'], // Eucalyptus
            '8' => ['background' => '#0B8043', 'foreground' => '#ffffff'], // Basil
            '9' => ['background' => '#7CB342', 'foreground' => '#ffffff'], // Pistachio
            '10' => ['background' => '#C0CA33', 'foreground' => '#ffffff'], // Avocado
            '11' => ['background' => '#E4C441', 'foreground' => '#000000'], // Citron
            '12' => ['background' => '#F6BF26', 'foreground' => '#000000'], // Banana
            '13' => ['background' => '#33B679', 'foreground' => '#ffffff'], // Sage
            '14' => ['background' => '#039BE5', 'foreground' => '#ffffff'], // Peacock
            '15' => ['background' => '#4285F4', 'foreground' => '#ffffff'], // Cobalt
            '16' => ['background' => '#3F51B5', 'foreground' => '#ffffff'], // Blueberry
            '17' => ['background' => '#7986CB', 'foreground' => '#ffffff'], // Lavender
            '18' => ['background' => '#B39DDB', 'foreground' => '#ffffff'], // Wisteria
            '19' => ['background' => '#616161', 'foreground' => '#ffffff'], // Graphite
            '20' => ['background' => '#A79B8E', 'foreground' => '#ffffff'], // Birch
            '21' => ['background' => '#AD1457', 'foreground' => '#ffffff'], // Radicchio
            '22' => ['background' => '#D81B60', 'foreground' => '#ffffff'], // Cherry Blossom
            '23' => ['background' => '#8E24AA', 'foreground' => '#ffffff'], // Grape
            '24' => ['background' => '#9E69AF', 'foreground' => '#ffffff'], // Amethyst
        ];
        return isset($colors[$colorId]) ? $colors[$colorId] : ['background' => '#000000', 'foreground' => '#ffffff'];
    }
    private function get_event_color($colorId)
    {
        $colors = [
            '1' => ['background' => '#7986CB', 'foreground' => '#ffffff'], // Lavender
            '2' => ['background' => '#33B679', 'foreground' => '#ffffff'], // Sage
            '3' => ['background' => '#8E24AA', 'foreground' => '#ffffff'], // Grape
            '4' => ['background' => '#E67C73', 'foreground' => '#ffffff'], // Flamingo
            '5' => ['background' => '#F6BF26', 'foreground' => '#000000'], // Banana
            '6' => ['background' => '#F4511E', 'foreground' => '#ffffff'], // Tangerine
            '7' => ['background' => '#039BE5', 'foreground' => '#ffffff'], // Peacock
            '8' => ['background' => '#616161', 'foreground' => '#ffffff'], // Graphite
            '9' => ['background' => '#3F51B5', 'foreground' => '#ffffff'], // Blueberry
            '10' => ['background' => '#0B8043', 'foreground' => '#ffffff'], // Basil
            '11' => ['background' => '#D50000', 'foreground' => '#ffffff'], // Tomato
        ];
        return isset($colors[$colorId]) ? $colors[$colorId] : ['background' => '#000000', 'foreground' => '#ffffff'];
    }
}
function skywin_hub_calendar() {
    return Skywin_Hub_Calendar::instance();
}
skywin_hub_calendar();
endif;