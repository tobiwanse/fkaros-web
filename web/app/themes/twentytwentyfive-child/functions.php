<?php
function login_logo() {
    $custom_logo_id = get_theme_mod( 'custom_logo' );
    $logo_url = $custom_logo_id ? wp_get_attachment_image_url( $custom_logo_id, 'full' ) : '';
    if ( $logo_url ) {
        echo '<style>
            .login h1 a {
                background-image: url("' . esc_url( $logo_url ) . '")!important;
                background-size: contain!important;
                background-repeat: no-repeat!important;
                width: 100%!important;
                height: 100px!important;
            }
        </style>';
    }
}
add_action( 'login_enqueue_scripts', 'login_logo');
add_filter( 'login_headerurl', function() {
    return home_url();
});
add_filter( 'login_headertext', function() {
    return get_bloginfo( 'name' );
});
function enqueue_parent_styles() {
	wp_enqueue_style( 'parent-style', get_stylesheet_directory_uri() . '/style.css' );
	$um_css = array( 
		'um_default_css',
		'um_responsive',
		'um_styles',
		'select2',
		'um_profile',
		'um_account',
		'um_members',
		'um_tipsy',
		'um_datetime_time',
		'um_datetime_date',
		'um_datetime',
		'um_scrollbar',
		'um_fileupload',
		'um_fonticons_fa',
		'um_fonticons_ii',
		'um_raty',
		'um_crop',
		'um_modal',
		'um_misc',
		'um_old_default_css',
		'um_old_css',
	); 
	//wp_dequeue_style( 'select2' );
	foreach ( $um_css as $css ) :
		//wp_dequeue_style( $css );
	endforeach;
	
}
add_action( 'wp_enqueue_scripts', 'enqueue_parent_styles', 9999);

function add_file_types_to_uploads($file_types){
	$new_filetypes = array();
	$new_filetypes['svg'] = 'image/svg+xml';
	$file_types = array_merge($file_types, $new_filetypes );
	return $file_types;
}
add_filter( 'big_image_size_threshold', '__return_false' );
add_filter( 'image_resize_dimensions', '__return_false' );
//add_filter( 'wp_calculate_image_srcset', '__return_false' );
//add_filter( 'wp_calculate_image_sizes', '__return_false' );

add_filter('upload_mimes', 'add_file_types_to_uploads');

add_filter( 'show_admin_bar', function ( $show ) {
	if ( is_page( 'skyview-full' ) ) {
		return false;
	}
	return $show;
} );

add_action( 'wp_head', function () {
	if ( is_page( 'skyview-full' ) ) {
		echo '<style>html,body{scrollbar-width:none!important;-ms-overflow-style:none!important;overscroll-behavior-y:none!important}html::-webkit-scrollbar,body::-webkit-scrollbar{display:none!important}</style>';
		$manifest_url = plugins_url( 'skywin-hub/assets/skyview-manifest.json', WP_PLUGIN_DIR . '/skywin-hub' );
		echo '<meta name="apple-mobile-web-app-capable" content="yes">';
		echo '<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">';
		echo '<meta name="mobile-web-app-capable" content="yes">';
		echo '<meta name="theme-color" content="#0d1b2a">';
		echo '<link rel="manifest" href="' . esc_url( $manifest_url ) . '">';
	}
} );