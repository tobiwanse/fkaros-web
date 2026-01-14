<?php
/** no direct access **/
defined('MECEXEC') or die();

$settings = $this->main->get_settings();
$event_id = get_the_ID();
$start_hour = get_post_meta($event_id, 'mec_start_time_hour', true);
$start_minutes_meta = get_post_meta($event_id, 'mec_start_time_minutes', true);
$start_min = ($start_minutes_meta < '10') ? '0' . $start_minutes_meta : $start_minutes_meta;
$start_ampm = get_post_meta($event_id, 'mec_start_time_ampm', true);
$end_hour = get_post_meta($event_id, 'mec_end_time_hour', true);
$end_minutes_meta = get_post_meta($event_id, 'mec_end_time_minutes', true);
$end_min = ($end_minutes_meta < '10') ? '0' . $end_minutes_meta : $end_minutes_meta;
$end_ampm = get_post_meta($event_id, 'mec_end_time_ampm', true);

$today = date('Y-m-d', current_time('timestamp'));
$next_occurrences = $this->getRender()->dates($event_id, null, 1, $today);
$next_occurrence_start = $next_occurrences[0]['start'] ?? [];
$occurrence_timestamp = $next_occurrence_start['timestamp'] ?? null;

if (!$occurrence_timestamp && !empty($next_occurrence_start['date'])) $occurrence_timestamp = strtotime($next_occurrence_start['date']);
if (!$occurrence_timestamp)
{
    $fallback_date = get_post_meta($event_id, 'mec_start_date', true);
    $occurrence_timestamp = $fallback_date ? strtotime($fallback_date) : null;
}

$time = (get_post_meta($event_id, 'mec_allday', true) == '1')
        ? $this->main->m('all_day', esc_html__('All Day', 'mec'))
        : $start_hour . ':' . $start_min . ' ' . $start_ampm . ' - ' . $end_hour . ':' . $end_min . ' ' . $end_ampm;
?>
<article class="mec-search-bar-result">
    <div class="mec-event-list-search-bar-date mec-color">
        <span class="mec-date-day">
            <?php if ($occurrence_timestamp) echo esc_html($this->main->date_i18n('d', $occurrence_timestamp)); ?>
        </span>
        <?php if ($occurrence_timestamp) echo esc_html($this->main->date_i18n('F', $occurrence_timestamp)); ?>
    </div>
    <div class="mec-event-image">
        <a href="<?php the_permalink(); ?>" target="_blank"><?php the_post_thumbnail('thumbnail'); ?></a>
    </div>
    <div class="mec-event-time mec-color">
        <i class="mec-sl-clock-o"></i><?php echo esc_html($time); ?>
    </div>
    <h4 class="mec-event-title">
        <a class="mec-color-hover" href="<?php the_permalink(); ?>" target="_blank"><?php the_title(); ?></a>
    </h4>
    <div class="mec-event-detail">
        <?php
            $id = get_post_meta($event_id, 'mec_location_id', true);
			$term = get_term($id, 'mec_location');
			echo esc_html($term->name);
        ?>
    </div>
</article>
