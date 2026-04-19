<?php
/**
 * SkyView front-end template.
 *
 * @var array $args {
 *     @type string $endpoint
 *     @type string $title
 *     @type string $date
 *     @type int    $refresh
 *     @type string $logged_in
 *     @type string $sw
 *     @type string $vapid
 *     @type string $push_endpoint
 *     @type string $login_url
 *     @type string $logout_url
 *     @type string $queue_endpoint
 * }
 */
defined( 'ABSPATH' ) || exit;
?>
<div class="skyview-page"
     data-skyview-endpoint="<?php echo esc_attr( $args['endpoint'] ); ?>"
     data-skyview-title="<?php echo esc_attr( $args['title'] ); ?>"
     data-skyview-date="<?php echo esc_attr( $args['date'] ); ?>"
     data-skyview-refresh="<?php echo (int) $args['refresh']; ?>"
     data-skyview-logged-in="<?php echo esc_attr( $args['logged_in'] ); ?>"
     data-skyview-sw="<?php echo esc_attr( $args['sw'] ); ?>"
     data-skyview-vapid="<?php echo esc_attr( $args['vapid'] ); ?>"
     data-skyview-push-endpoint="<?php echo esc_attr( $args['push_endpoint'] ); ?>"
     data-skyview-login-url="<?php echo esc_attr( $args['login_url'] ); ?>"
     data-skyview-logout-url="<?php echo esc_attr( $args['logout_url'] ); ?>"
     data-skyview-queue-endpoint="<?php echo esc_attr( $args['queue_endpoint'] ); ?>">
    <header class="skyview-header">
        <div class="skyview-header-row">
            <div class="skyview-clock"></div>
            <div class="skyview-crew">
                <span class="skyview-crew-pilot"></span>
                <span class="skyview-crew-jumpleader"></span>
            </div>
            <div class="skyview-tools"></div>
        </div>
    </header>
    <main class="skyview-main">
        <div class="skyview-messages"></div>
        <div class="skyview-loads"></div>
    </main>
    <div class="skyview-modal-overlay">
        <div class="skyview-modal"></div>
    </div>
</div>