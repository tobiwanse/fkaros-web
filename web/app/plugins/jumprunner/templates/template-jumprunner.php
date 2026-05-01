<?php
/**
 * Jumprunner front-end template.
 *
 * @var array $args {
 *     @type string $title
 *     @type string $lat
 *     @type string $lng
 *     @type int    $zoom
 * }
 */
defined( 'ABSPATH' ) || exit;
?>
<div class="jumprunner">
    <?php if ( ! empty( $args['title'] ) ) : ?>
        <h2 class="jumprunner__title"><?php echo esc_html( $args['title'] ); ?></h2>
    <?php endif; ?>

    <div class="jumprunner-map-wrap">
        <gmp-map-3d
            class="jumprunner-map"
            data-lat="<?php echo esc_attr( $args['lat'] ); ?>"
            data-lng="<?php echo esc_attr( $args['lng'] ); ?>"
            data-zoom="<?php echo (int) $args['zoom']; ?>"
            mode="hybrid"
        ></gmp-map-3d>

        <div class="jumprunner-controls">
            <button type="button" class="jumprunner-compass-toggle" aria-pressed="true" title="Dölj kompass">Kompass</button>
        </div>
    </div>
</div>
