<?php
defined('ABSPATH') || exit;
if ( !class_exists('Skywin_Hub_Google_Api') ) :
    //require_once __DIR__ . '/vendor/autoload.php';
    class Skywin_Hub_Google_Api {
        protected static $_instance = null;
        private  $client;
        private  $calendarService;
        public static function instance()
        {
            if ( is_null(self::$_instance ) ) {
                self::$_instance = new self();
            }
            return self::$_instance;
        }
        public function __construct()
        {
            $client_id = get_option('skywin_hub_google_api_client_id');
            $client_secret = get_option('skywin_hub_google_api_client_secret');
            $client_uri = get_option('skywin_hub_google_api_redirect_uri');
            
            $this->client = new Google_Client();
            $this->client->setClientId( $client_id );
            $this->client->setClientSecret( $client_secret );
            $this->client->setRedirectUri( $client_uri );
            $this->client->addScope(Google_Service_Calendar::CALENDAR);
            $this->client->setAccessType('offline');
            $this->client->setPrompt('consent');
            $this->calendarService = new Google_Service_Calendar($this->client);
        }
        public function refreshToken() {
            $refreshToken = $this->client->getRefreshToken();
            if ($refreshToken) {
                $newToken = $this->client->fetchAccessTokenWithRefreshToken($refreshToken);
                if (!isset($newToken['refresh_token'])) {
                    $newToken['refresh_token'] = $refreshToken;
                }
                update_option('skywin_hub_google_api_access_token', $newToken);
                return true;
            }
            return false;
        }
        public function authenticate()
        {
            if ( isset($_GET['code']) ) {
                $this->client->fetchAccessTokenWithAuthCode($_GET['code']);
                $token = $this->client->getAccessToken();
                update_option('skywin_hub_google_api_access_token', $token);
            } else {
                $token = get_option('skywin_hub_google_api_access_token');
                if ($token) {
                    $this->client->setAccessToken($token);
                    if ($this->client->isAccessTokenExpired()) {
                        if (!$this->refreshToken()) {
                            $authUrl = $this->client->createAuthUrl();
                            wp_redirect($authUrl);
                            exit();
                        }
                    }
                } else {
                    $authUrl = $this->client->createAuthUrl();
                    wp_redirect($authUrl);
                    exit();
                }
            }
        }
        public function isTokenValid()
        {
            $token = get_option('skywin_hub_google_api_access_token');
            if ($token) {
                $this->client->setAccessToken($token);
                if ($this->client->isAccessTokenExpired()) {
                    if (!$this->refreshToken()) {
                        return false;
                    }
                }
                return !$this->client->isAccessTokenExpired();
            }
            return false;
        }
        public function get_events( $calendarId = 'primary', $optParams = [] )
        {
            $items = [];
            if ( $this->isTokenValid() ) {
                $events = $this->calendarService->events->listEvents($calendarId, $optParams);
                $items = $events->getItems();
            }
            return $items;
        }
        public function get_calendar_list()
        {
            $calendars = [];
            if ( $this->isTokenValid() ) {
                $calendars = $this->calendarService->calendarList->listCalendarList();
            }
            return $calendars;
        }
        public function get_color_list()
        {
            $colors = [];
            if ( $this->isTokenValid() ) {
                $colors = $this->calendarService->colors->get();
            }
            return $colors;
        }

        public function revokeToken()
        {
            $token = get_option('skywin_hub_google_api_access_token');
            if ($token) {
                $this->client->setAccessToken($token);
                $this->client->revokeToken();
                delete_option('skywin_hub_google_api_access_token');
            }
        }

        public function getAccessToken()
        {
            return $this->client->getAccessToken();
        }
    }
    function skywin_hub_google_api(){
        return Skywin_Hub_Google_Api::instance();
    }
endif;