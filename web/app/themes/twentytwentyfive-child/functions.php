<?php
function convert_absolute_to_relative_urls($url) {
    // Only convert URLs from the current site
    $home_url = home_url('/');
    return str_replace($home_url, '/', $url);
}
// Convert media URLs
//add_filter('wp_get_attachment_url', 'convert_absolute_to_relative_urls');
// Convert content URLs
add_filter('wp_get_attachment_image_src', function($image) {
    //$image[0] = convert_absolute_to_relative_urls($image[0]);
    return $image;

});
// Convert uploads in content
add_filter('the_content', function($content) {
    //return convert_absolute_to_relative_urls($content);
    return $content;
});




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
}
add_action( 'wp_enqueue_scripts', 'enqueue_parent_styles', 9999);

function add_file_types_to_uploads($file_types){
	$new_filetypes = array();
	$new_filetypes['svg'] = 'image/svg+xml';
	$file_types = array_merge($file_types, $new_filetypes );
	return $file_types;
}
add_filter( 'big_image_size_threshold', '__return_false' );
add_filter('upload_mimes', 'add_file_types_to_uploads');