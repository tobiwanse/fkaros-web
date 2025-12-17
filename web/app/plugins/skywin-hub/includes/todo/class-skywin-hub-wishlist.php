<?php
defined('ABSPATH') || exit;

if (!class_exists('Skywin_Hub_Wishlist')):
	class Skywin_Hub_Wishlist
	{
        protected static $_instance = null;
        private $name;
        private $title;
        public static function instance()
        {
            if (is_null(self::$_instance)) {
                self::$_instance = new self();
            }
            return self::$_instance;
        }
        public function __construct()
        {
            $this->name = 'skywin-hub-wishlist';
            $this->title = 'Skywin Hub Wishlist';
            $this->includes();
        }
        private function includes()
		{
			include_once SW_ABSPATH . 'includes/shortcodes/class-skywin-hub-shortcode-wishlist.php';
		}
    }
    function skywin_hub_wishlist() {
        return Skywin_Hub_Wishlist::instance();
    }
    skywin_hub_wishlist();
endif;