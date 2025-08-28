<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}
function tec_exclude_events_category( $repository_args, $context, $view ) {
    $hide_in_views = ['list'];
    if ( in_array( $view->get_slug(), $hide_in_views, true ) ) {
        $excluded_categories = [
            'hoppning',
        ];
        $repository_args['category_not_in'] = $excluded_categories;
    }
    return $repository_args;
}
add_filter( 'tribe_events_views_v2_view_repository_args', 'tec_exclude_events_category', 10, 3 );
