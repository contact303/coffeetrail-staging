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

wp_enqueue_script('mylisting-accordions');
?>

<div class="<?php echo esc_attr($block->get_wrapper_classes()) ?>" 
     id="<?php echo esc_attr($block->get_wrapper_id()) ?>"
     data-work-hours="<?php echo esc_attr(wp_json_encode($work_hours_data)) ?>"
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
                <div class="timing-today"  data-js-today-schedule>
                    <?php
                    $todays_schedule = $schedule->get_todays_schedule();
                    
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
                    ?>
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
                    <!-- Static weekly schedule -->
                    <?php
                    // Reorder the weekdays array to start with Sunday.
                    $weekdays = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

                    // Get the schedule sorted by the reordered weekdays.
                    $schedule_sorted = array_replace(array_flip($weekdays), $schedule->get_schedule());

                    foreach ($schedule_sorted as $weekday): ?>
                        <li>
                            <p class="item-attr"><?php echo esc_html($weekday['day_l10n']) ?></p>
                            <p class="item-property"><?php echo $schedule->get_day_schedule($weekday['day']) ?></p>
                        </li>
                    <?php endforeach ?>

                    <!-- Dynamic timezone display -->
                    <?php if (!empty($work_hours['timezone'])): ?>
                        <p class="work-hours-timezone" data-js-timezone>
                            <em></em>
                        </p>
                    <?php endif ?>
                </ul>
            </div>
        </div>
    </div>
</div>

<script>
/**
 * Complete Work Hours Manager - Replicates MyListing\Src\Work_Hours exactly
 */
document.addEventListener('DOMContentLoaded', function() {
    // Single manager for all work hours blocks
    class WorkHoursManager {
        constructor() {
            this.blocks = new Map();
            this.updateInterval = null;
            this.isVisible = true;
            this.init();
        }

        init() {
            document.querySelectorAll('[data-work-hours]').forEach(block => {
                try {
                    const data = JSON.parse(block.getAttribute('data-work-hours'));
                    const listingId = block.getAttribute('data-listing-id');
                    
                    if (data && listingId) {
                        this.blocks.set(listingId, { block, data });
                    }
                } catch (error) {
                    console.warn('WorkHours: Invalid data for block', block);
                }
            });

            if (this.blocks.size > 0) {
                this.startUpdates();
                this.setupVisibilityOptimization();
            }
        }

        startUpdates() {
            this.updateAllBlocks();
            this.updateInterval = setInterval(() => {
                if (this.isVisible) {
                    this.updateAllBlocks();
                }
            }, 60000);
        }

        setupVisibilityOptimization() {
            document.addEventListener('visibilitychange', () => {
                this.isVisible = !document.hidden;
                if (this.isVisible) {
                    this.updateAllBlocks();
                }
            });
        }

        updateAllBlocks() {
            this.blocks.forEach(({ block, data }) => {
                try {
                    const workHours = new ClientWorkHours(data);
                    const result = workHours.parse();
                    this.updateUI(block, result, data);
                } catch (error) {
                    console.error('WorkHours: Update error:', error);
                }
            });
        }

        updateUI(block, result, data) {
            const statusElement = block.querySelector('[data-js-status]');
            const scheduleElement = block.querySelector('[data-js-today-schedule]');
            const timezoneElement = block.querySelector('[data-js-timezone]');
            
            if (statusElement) {
                statusElement.className = `work-hours-status ${result.status}`;
                statusElement.textContent = result.message;
            }
            
            if (scheduleElement) {
                // Preserve the tooltip element
                const tooltip = scheduleElement.querySelector('.tooltip-element');
                scheduleElement.innerHTML = result.todaySchedule;
                if (tooltip) {
                    scheduleElement.appendChild(tooltip);
                }
            }
        }

        getCurrentTime(timezone) {
            if (timezone) {
                try {
                    return new Date(new Date().toLocaleString("en-US", {timeZone: timezone}));
                } catch (error) {
                    console.warn('WorkHours: Invalid timezone', timezone);
                }
            }
            return new Date();
        }

        formatDateTime(date, timeFormat, dateFormat) {
            // Simple format conversion - in real implementation you'd want more robust formatting
            const options = {
                year: 'numeric',
                month: '2-digit', 
                day: '2-digit',
                hour: '2-digit',
                minute: '2-digit'
            };
            return date.toLocaleDateString([], options) + ' ' + date.toLocaleTimeString([], {
                hour: '2-digit',
                minute: '2-digit'
            });
        }
    }

    /**
     * Client-side Work Hours Calculator
     * Exact replication of MyListing\Src\Work_Hours PHP logic
     */
    class ClientWorkHours {
        constructor(data) {
            this.rawHours = data.raw_hours;
            this.timezone = data.timezone;
            this.messages = data.messages;
            this.timeFormat = data.time_format;
            this.phpDayNames = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
        }

        getCurrentTime() {
            if (this.timezone) {
                try {
                    return new Date(new Date().toLocaleString("en-US", {timeZone: this.timezone}));
                } catch (error) {
                    console.warn('WorkHours: Invalid timezone', this.timezone);
                }
            }
            return new Date();
        }

        parse() {
            const now = this.getCurrentTime();
            
            // Convert JS Sunday=0 to PHP Monday=0 system
            const todayIndex = (now.getDay() + 6) % 7;
            const todayName = this.phpDayNames[todayIndex];
            const yesterdayName = this.phpDayNames[(todayIndex + 6) % 7];
            
            const todayHours = this.rawHours[todayName];
            const yesterdayHours = this.rawHours[yesterdayName];
            
            let activeDay = todayName;
            let result = null;
            
            // Try today first (exact same logic as PHP parse())
            if (todayHours) {
                result = this.parseDay(todayHours, now, false);
                if (result) {
                    return {
                        ...result,
                        activeDay,
                        todaySchedule: this.getTodaysSchedule(todayName)
                    };
                }
            }
            
            // Then try yesterday (for businesses open past midnight)
            if (yesterdayHours) {
                result = this.parseDay(yesterdayHours, now, true);
                if (result) {
                    activeDay = yesterdayName;
                    return {
                        ...result,
                        activeDay,
                        todaySchedule: this.getTodaysSchedule(yesterdayName)
                    };
                }
            }
            
            // Default fallback
            return {
                status: 'not-available',
                message: this.messages.not_available,
                activeDay: todayName,
                todaySchedule: this.messages.todays_schedule_na
            };
        }

        parseDay(dayHours, currentTime, yesterdayFlag = false) {
            // Handle special statuses (only for today, not yesterday - exact PHP logic)
            if (!yesterdayFlag && dayHours.status) {
                if (dayHours.status === 'open-all-day') {
                    return { status: 'open', message: this.messages.open };
                }
                if (dayHours.status === 'closed-all-day') {
                    return { status: 'closed', message: this.messages.closed };
                }
                if (dayHours.status === 'by-appointment-only') {
                    return { status: 'appointment-only', message: this.messages.appointment_only };
                }
            }

            // Parse time ranges - filter out 'status' key like PHP does
            const ranges = [];
            for (const key in dayHours) {
                if (key !== 'status' && dayHours[key] && 
                    typeof dayHours[key] === 'object' && 
                    dayHours[key].from && dayHours[key].to) {
                    ranges.push(dayHours[key]);
                }
            }

            if (ranges.length === 0) {
                if (!yesterdayFlag) {
                    return { status: 'not-available', message: this.messages.not_available };
                }
                return null;
            }

            const currentMinutes = currentTime.getHours() * 60 + currentTime.getMinutes();

            for (const range of ranges) {
                const startTime = this.parseTime(range.from);
                const endTime = this.parseTime(range.to);
                
                if (!startTime || !endTime) continue;

                let startMinutes = startTime.hours * 60 + startTime.minutes;
                let endMinutes = endTime.hours * 60 + endTime.minutes;

                // Handle overnight ranges - exact MyListing PHP logic
                // If end time is <= start time, it means the end time belongs to tomorrow (e.g. 17:00 - 03:00)
                if (endMinutes <= startMinutes) {
                    endMinutes += 24 * 60;
                }

                // For yesterday check, skip ranges that don't cross midnight
                if (yesterdayFlag) {
                    // Only process overnight ranges for yesterday
                    if (endTime.hours >= startTime.hours) {
                        continue; // Skip non-overnight ranges
                    }
                }

                // Currently open - exact PHP logic
                if (currentMinutes >= startMinutes && currentMinutes < endMinutes) {
                    const minutesToClose = endMinutes - currentMinutes;
                    
                    if (minutesToClose <= 5) {
                        return { 
                            status: 'closing', 
                            message: this.messages.closing_few 
                        };
                    } else if (minutesToClose <= 30) {
                        // Exact same rounding as PHP: round($time_until_closes / 5) * 5
                        const roundedMinutes = Math.round(minutesToClose / 5) * 5;
                        return { 
                            status: 'closing', 
                            message: this.messages.closing_minutes.replace('%d', roundedMinutes)
                        };
                    } else {
                        return { 
                            status: 'open', 
                            message: this.messages.open 
                        };
                    }
                }

                // Opening soon (only for today, not yesterday)
                if (!yesterdayFlag && currentMinutes < startMinutes) {
                    const minutesToOpen = startMinutes - currentMinutes;
                    
                    if (minutesToOpen <= 5) {
                        return { 
                            status: 'opening', 
                            message: this.messages.opening_few 
                        };
                    } else if (minutesToOpen <= 30) {
                        const roundedMinutes = Math.round(minutesToOpen / 5) * 5;
                        return { 
                            status: 'opening', 
                            message: this.messages.opening_minutes.replace('%d', roundedMinutes)
                        };
                    } else {
                        return { 
                            status: 'closed', 
                            message: this.messages.closed 
                        };
                    }
                }
            }

            // Default behavior
            if (!yesterdayFlag) {
                return { status: 'closed', message: this.messages.closed };
            }
            
            return null;
        }

        parseTime(timeString) {
            const parts = timeString.split(':');
            if (parts.length !== 2) return null;
            
            const hours = parseInt(parts[0], 10);
            const minutes = parseInt(parts[1], 10);
            
            if (isNaN(hours) || isNaN(minutes) || hours < 0 || hours > 23 || minutes < 0 || minutes > 59) {
                return null;
            }
            
            return { hours, minutes };
        }

        getTodaysSchedule(activeDay) {
            const dayHours = this.rawHours[activeDay];
            
            if (!dayHours) {
                return this.messages.todays_schedule_na;
            }

            // Handle special statuses
            if (dayHours.status === 'open-all-day') {
                return this.messages.open_24h_today;
            }
            if (dayHours.status === 'closed-all-day') {
                return this.messages.closed_today;
            }
            if (dayHours.status === 'by-appointment-only') {
                return this.messages.appointment_today;
            }

            // Parse time ranges
            const ranges = [];
            for (const key in dayHours) {
                if (key !== 'status' && dayHours[key] && 
                    typeof dayHours[key] === 'object' && 
                    dayHours[key].from && dayHours[key].to) {
                    ranges.push(dayHours[key]);
                }
            }

            if (ranges.length === 0) {
                return this.messages.todays_schedule_na;
            }

            // Format time ranges in REVERSED order to match PHP display (end - start)
            const formattedRanges = ranges.map(range => {
                return this.formatTime(range.to) + ' - ' + this.formatTime(range.from);
            }).reverse();

            // Return only the times without prefix or wrapper (to match PHP format)
            return formattedRanges.join(' ,');
        }

        formatTime(timeString) {
            // Simple time formatting - matches WordPress default
            const time = this.parseTime(timeString);
            if (!time) return timeString;
            
            const date = new Date();
            date.setHours(time.hours, time.minutes);
            
            // Format according to WordPress time format (simplified)
            return date.toLocaleTimeString([], {
                hour: '2-digit',
                minute: '2-digit',
                hour12: false
            });
        }
    }

    // Initialize the manager
    if (!window.workHoursManager) {
        window.workHoursManager = new WorkHoursManager();
    }
});
</script>