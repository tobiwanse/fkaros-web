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
            <div class="skyview-last-updated" aria-live="polite">Senast uppdaterad: -</div>
            <div class="skyview-crew">
                <span class="skyview-crew-pilot"></span>
                <span class="skyview-crew-jumpleader"></span>
            </div>
            <div class="skyview-tools">
                <div class="skyview-toolbar-left">
                    <button class="skyview-queue-badge" type="button">0 i kön</button>
                    <div class="skyview-date-actions">
                        <button class="skyview-date-button" type="button" aria-label="Välj datum">
                            <svg class="skyview-calendar-icon" viewBox="0 0 20 20" aria-hidden="true">
                                <path fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5zM7 1v4M13 1v4M3 8h14"></path>
                            </svg>
                            <span class="skyview-date-selected">
                                <button class="skyview-date-clear" type="button" title="Rensa datum" aria-label="Rensa datum">✕</button>
                            </span>
                            
                        </button>
                        
                    </div>
                    <div class="skyview-settings-wrapper">
                        <button class="skyview-settings-button" type="button" title="Inställningar" aria-label="Visa inställningar">
                            <svg class="skyview-settings-icon" viewBox="0 0 20 20" aria-hidden="true">
                                <path fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" d="M8.5 2h3l.4 2.2a5.5 5.5 0 0 1 1.8 1l2.1-.7 1.5 2.6-1.7 1.5a5.6 5.6 0 0 1 0 2.1l1.7 1.5-1.5 2.6-2.1-.7a5.5 5.5 0 0 1-1.8 1L11.5 18h-3l-.4-2.2a5.5 5.5 0 0 1-1.8-1l-2.1.7-1.5-2.6 1.7-1.5a5.6 5.6 0 0 1 0-2.1L2.7 7.8l1.5-2.6 2.1.7a5.5 5.5 0 0 1 1.8-1z"></path>
                                <circle cx="10" cy="10" r="2.3" fill="none" stroke="currentColor" stroke-width="1.8"></circle>
                            </svg>
                        </button>
                    </div>
                </div>

            </div>
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