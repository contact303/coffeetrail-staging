<?php
/**
 * Template for rendering a `work_hours` block in single listing page.
 * Client-side approach: Server provides data, JS calculates status
 *
 * @since 1.0
 */
if (!defined('ABSPATH')) {
    exit;
}

// get work hours
$work_hours = $listing->get_field('work_hours');
$schedule = new MyListing\Src\Work_Hours($work_hours);
$block->add_wrapper_classes('open-now sl-zindex');

// validate
if (!$work_hours || $schedule->is_empty()) {
    return;
}

// Prepare complete data for JavaScript
$work_hours_data = [
    'raw_hours' => $work_hours,
    'timezone' => $work_hours['timezone'] ?? '',
    'server_time' => [
        'current' => date('Y-m-d H:i:s'),
        'timezone' => date_default_timezone_get()
    ],
    'time_format' => get_option('time_format'),
    'date_format' => get_option('date_format'),
    'messages' => [
        'open' => __('Open', 'my-listing'),
        'closed' => __('Closed', 'my-listing'),
        'closing_few' => __('Closes in a few minutes', 'my-listing'),
        'closing_minutes' => __('Closes in %d minutes', 'my-listing'),
        'opening_few' => __('Opens in a few minutes', 'my-listing'),
        'opening_minutes' => __('Opens in %d minutes', 'my-listing'),
        'appointment_only' => __('By appointment only', 'my-listing'),
        'not_available' => __('Not Available', 'my-listing'),
        'open_24h_today' => __('Open 24h today', 'my-listing'),
        'closed_today' => __('Closed today', 'my-listing'),
        'appointment_today' => __('Open hours today: By appointment only', 'my-listing'),
        'todays_schedule_na' => __('Today\'s work schedule is not available', 'my-listing'),
        'open_hours_today' => __('Open hours today:', 'my-listing'),
        'local_time' => __('%s local time', 'my-listing')
    ]
];

// Enqueue scripts only on single listing pages
wp_enqueue_script('mylisting-accordions');
wp_enqueue_script(
    'work-hours-client',
    get_stylesheet_directory_uri() . '/assets/js/work-hours.js',
    ['mylisting-accordions'],
    '1.0.0',
    true
);

// Pass data to JavaScript
wp_localize_script('work-hours-client', 'workHoursConfig', $work_hours_data);
?>

<div class="<?php echo esc_attr($block->get_wrapper_classes()) ?>" 
     id="<?php echo esc_attr($block->get_wrapper_id()) ?>"
     data-work-hours="true"
     data-listing-id="<?php echo esc_attr($listing->get_id()) ?>">
    <div class="element work-hours-block">
        <div class="pf-head" data-component="mylisting-accordion"
             data-target="#<?php echo esc_attr($block->get_unique_id() . '-toggle') ?>">
            <div class="title-style-1">
                <i class="<?php echo esc_attr($block->get_icon()) ?>"></i>
                <h5>
                    <!-- Initial server-rendered status, then updated by JS -->
                    <span class="<?php echo esc_attr($schedule->get_status()) ?> work-hours-status" data-js-status>
                        <?php echo esc_html($schedule->get_message()) ?>
                    </span>
                </h5>
                <div class="timing-today">
                    <!-- Initial server-rendered schedule, then updated by JS -->
                    <span data-js-today-schedule><?php 
                        $todays_schedule = $schedule->get_todays_schedule();
                        
                        // Apply your existing time reversal logic
                        if (preg_match_all('/(\d{2}:\d{2}) - (\d{2}:\d{2})/', $todays_schedule, $matches, PREG_SET_ORDER)) {
                            $reversed_schedules = [];
                            foreach ($matches as $match) {
                                // Reverse the schedule order (end time - start time)
                                $reversed_schedules[] = $match[2] . ' - ' . $match[1];
                            }
                            echo esc_html(implode(' ,', array_reverse($reversed_schedules)));
                        } else {
                            echo esc_html($todays_schedule);
                        }
                    ?></span>
                    <span class="tooltip-element center-flex">
                        <span class="mi expand_more"></span>
                        <span class="tooltip-container"><?php esc_attr_e('Toggle weekly schedule', 'my-listing') ?></span>
                    </span>
                </div>
            </div>
        </div>
        <div class="open-hours-wrapper pf-body collapse <?php echo $block->get_prop('collapse') ? 'in' : '' ?>"
             id="<?php echo esc_attr($block->get_unique_id() . '-toggle') ?>">
            <div id="open-hours">
                <ul class="extra-details no-list-style">
                    <!-- Static weekly schedule - reorder to start with Sunday -->
                    <?php 
                    $weekdays = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
                    $schedule_sorted = array_replace(array_flip($weekdays), $schedule->get_schedule());
                    
                    foreach ($schedule_sorted as $weekday): ?>
                        <li>
                            <p class="item-attr"><?php echo esc_html($weekday['day_l10n']) ?></p>
                            <p class="item-property"><?php echo $schedule->get_day_schedule($weekday['day']) ?></p>
                        </li>
                    <?php endforeach ?>

                    <!-- Dynamic timezone display -->
                    <?php if (!empty($work_hours['timezone'])): ?>
                        <p class="work-hours-timezone" data-js-timezone style="display: none;">
                            <em></em>
                        </p>
                    <?php endif ?>
                </ul>
            </div>
        </div>
    </div>
</div>