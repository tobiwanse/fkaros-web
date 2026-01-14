<?php
/** no direct access **/
defined('MECEXEC') or die();

/** @var MEC_skin_map $this */

// MEC Settings
$settings = $this->main->get_settings();
$settings['view_mode'] = $this->atts['location_view_mode'] ?? 'normal';
$settings['view_mode'] = $this->atts['sk-options']['map']['view_mode'] ?? $settings['view_mode'];

$settings['map'] = $settings['default_maps_view'] ?? 'google';

// Return the data if called by AJAX
if(isset($this->atts['return_items']) and $this->atts['return_items'])
{
    echo json_encode(array('markers' => $this->render->markers($this->events, $this->style)));
    exit;
}

$events_data = $this->render->markers($this->events, $this->style);
if(count($this->events))
{
    // Include Map Assets such as JS and CSS libraries
    $this->main->load_map_assets(true, $settings);

    $javascript = '<script>
    var mecMapAttempts = 0;
    var maxAttempts = 50; // Maximum 5 seconds (50 * 100ms)
    var mapType = "'.esc_js($settings['map']).'";
    
    function checkScriptLoaded() {
        var scripts = document.getElementsByTagName("script");
        var mapScriptFound = false;
        
        for (var i = 0; i < scripts.length; i++) {
            if (scripts[i].src) {
                if (mapType === "openstreetmap" && scripts[i].src.includes("openstreetmap.js")) {
                    mapScriptFound = true;
                    break;
                } else if (mapType === "google" && scripts[i].src.includes("googlemap.js")) {
                    mapScriptFound = true;
                    break;
                }
            }
        }
        
    }
    
    function initMecMap() {
        mecMapAttempts++;
        
        // Check scripts on first attempt
        if (mecMapAttempts === 1) {
            checkScriptLoaded();
        }
        
        // After max attempts, try to initialize anyway
        if (mecMapAttempts >= maxAttempts) {
            checkScriptLoaded(); // Check again before forcing
            initializeMap();
            return;
        }
        
        // Check if jQuery is loaded
        if (typeof jQuery === "undefined") {
            setTimeout(initMecMap, 100);
            return;
        }
        
        // Check based on map type
        if (mapType === "openstreetmap") {
            // Check if mecOpenstreetMaps plugin is loaded
            if (typeof jQuery.fn.mecOpenstreetMaps === "undefined") {
                setTimeout(initMecMap, 100);
                return;
            }
        } else {
            // Check if mecGoogleMaps plugin is loaded
            if (typeof jQuery.fn.mecGoogleMaps === "undefined") {
                setTimeout(initMecMap, 100);
                return;
            }
            
            // Check if Google Maps API is loaded (only for Google maps)
            if (typeof google === "undefined") {
                setTimeout(initMecMap, 100);
                return;
            }
        }
        
        initializeMap();
    }
    
    function initializeMap() {
        try {
            if (mapType === "openstreetmap") {
                // Initialize OpenStreetMap
                jQuery("#mec_map_canvas'.esc_js($this->id).'").mecOpenstreetMaps(
                {
                    show_on_openstreetmap_text: "'.__('Show on OpenstreetMap', 'mec-map').'",
                    id: "'.esc_js($this->id).'",
                    atts: "'.http_build_query(array('atts' => $this->atts), '', '&').'",
                    zoom: '.(isset($settings['google_maps_zoomlevel']) ? esc_js($settings['google_maps_zoomlevel']) : 14).',
                    scrollwheel: '.((isset($settings['default_maps_scrollwheel']) and $settings['default_maps_scrollwheel']) ? 'true' : 'false').',
                    markers: '.json_encode($events_data).',
                    HTML5geolocation: "'.esc_js($this->geolocation).'",
                    ajax_url: "'.admin_url('admin-ajax.php', NULL).'",
                    sf:
                    {
                        container: "'.($this->sf_status ? '#mec_search_form_'.esc_js($this->id) : '').'",
                    },
                    latitude: "",
                    longitude: "",
                    fields: '.json_encode(array()).'
                });
            } else {
                // Initialize Google Maps
                jQuery("#mec_map_canvas'.esc_js($this->id).'").mecGoogleMaps(
                {
                    id: "'.esc_js($this->id).'",
                    atts: "'.http_build_query(array('atts' => $this->atts), '', '&').'",
                    zoom: '.(isset($settings['google_maps_zoomlevel']) ? esc_js($settings['google_maps_zoomlevel']) : 14).',
                    icon: "'.apply_filters('mec_marker_icon', $this->main->asset('img/m-04.png')).'",
                    styles: '.((isset($settings['google_maps_style']) and trim($settings['google_maps_style']) != '') ? $this->main->get_googlemap_style($settings['google_maps_style']) : "''").',
                    fullscreen_button: '.((isset($settings['google_maps_fullscreen_button']) and trim($settings['google_maps_fullscreen_button'])) ? 'true' : 'false').',
                    markers: '.json_encode($events_data).',
                    geolocation: '.esc_js($this->geolocation).',
                    geolocation_focus: '.esc_js($this->geolocation_focus).',
                    clustering_images: "'.esc_js($this->main->asset('img/cluster1/m')).'",
                    getDirection: 0,
                    ajax_url: "'.admin_url('admin-ajax.php', NULL).'",
                    sf:
                    {
                        container: "'.($this->sf_status ? '#mec_search_form_'.esc_js($this->id) : '').'",
                        reset: '.($this->sf_reset_button ? 1 : 0).',
                        refine: '.($this->sf_refine ? 1 : 0).',
                    },
                });
            }
        } catch (error) {
        }
    }
    
    jQuery(document).ready(function() {
        initMecMap();
    });
    </script>';

    $javascript = apply_filters('mec_map_load_script', $javascript, $this, $settings,true);

    // Include javascript code into the page
    if($this->main->is_ajax() or $this->main->preview()) echo MEC_kses::full($javascript);
    else $this->factory->params('footer', $javascript);
}

do_action('mec_start_skin', $this->id);
do_action('mec_map_skin_head');
?>
<?php if($settings['view_mode'] == 'normal') : ?>
<div class="mec-wrap mec-skin-map-container <?php echo esc_attr($this->html_class); ?>" id="mec_skin_<?php echo esc_attr($this->id); ?>">

    <?php if($this->sf_status) echo MEC_kses::full($this->sf_search_form()); ?>
    <?php do_action('mec_map_skin_before_form', $settings); ?>

    <?php if(count($this->events)): ?>
    <div class="mec-googlemap-skin" id="mec_map_canvas<?php echo esc_attr($this->id); ?>" style="height: 500px;">
        <?php do_action('mec_map_inner_element_tools', $settings); ?>
    </div>
    <?php else: ?>
    <p class="mec-error"><?php esc_html_e('No events found!', 'mec'); ?></p>
    <?php endif; ?>

</div>
<?php else: ?>
<div class="mec-wrap mec-skin-map-container">
    <div class="row">
        <div class="col-sm-12">
            <div class="<?php echo esc_attr($this->html_class); ?>" id="mec_skin_<?php echo esc_attr($this->id); ?>">
                <?php if($this->sf_status) echo MEC_kses::full($this->sf_search_form()); ?>
            </div>
        </div>
    </div>
    <div class="row mec-map-events-wrap">
        <div class="col-sm-7">
            <?php if(count($this->events)): ?>
                <div class="mec-googlemap-skin" id="mec_map_canvas<?php echo esc_attr($this->id); ?>" style="height: 600px;">
                    <?php do_action('mec_map_inner_element_tools', $settings); ?>
                </div>
            <?php else: ?>
                <p class="mec-error"><?php esc_html_e('No events found!', 'mec'); ?></p>
            <?php endif; ?>
        </div>
        <div class="col-sm-5" id="mec-map-skin-side-<?php echo esc_attr($this->id); ?>"></div>
    </div>
</div>
<?php endif; ?>
<?php echo $this->display_credit_url();
