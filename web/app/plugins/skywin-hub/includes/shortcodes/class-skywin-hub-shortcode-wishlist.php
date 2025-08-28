<?php
defined('ABSPATH') || exit;
if (!class_exists('Skywin_Hub_Shortcode_Wishlist')):
	class Skywin_Hub_Shortcode_Wishlist
	{
		public static function output($args){
            $args['items'] = skywin_hub_db()->get_wishlist();
            ob_start();
			load_template(SW_TEMPLATE_PATH . '/template-skywin-wishlist.php', true, $args);
			$html = ob_get_clean();
			return $html;
        }
    }

endif;