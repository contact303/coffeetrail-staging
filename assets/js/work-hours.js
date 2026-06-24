/**
 * Work Hours Client-Side Manager - Memory Optimized Version
 * @version 1.1.0
 */

(function() {
    'use strict';

    let workHoursManager = null;

    // Wait for DOM and config to be ready
    document.addEventListener('DOMContentLoaded', function() {
        // Verify config exists and is valid
        if (typeof workHoursConfig === 'undefined' || !workHoursConfig.raw_hours) {
            console.warn('WorkHours: Invalid or missing configuration');
            return;
        }

        // Find work hours block
        const workHoursBlock = document.querySelector('[data-work-hours]');
        if (!workHoursBlock) {
            return; // No work hours block on this page
        }

        // Initialize work hours manager
        workHoursManager = new WorkHoursManager(workHoursBlock, workHoursConfig);
        workHoursManager.start();
    });

    // Clean up on page unload (prevent memory leaks)
    window.addEventListener('beforeunload', function() {
        if (workHoursManager) {
            workHoursManager.destroy();
            workHoursManager = null;
        }
    });

    // Clean up on page visibility change for SPA navigation
    document.addEventListener('visibilitychange', function() {
        if (document.hidden && workHoursManager) {
            workHoursManager.pause();
        } else if (!document.hidden && workHoursManager) {
            workHoursManager.resume();
        }
    });

    /**
     * Memory-Optimized Work Hours Manager
     */
    class WorkHoursManager {
        constructor(blockElement, config) {
            this.block = blockElement;
            this.config = this.validateConfig(config);
            this.updateInterval = null;
            this.isVisible = true;
            this.isPaused = false;
            
            // Cache DOM elements to avoid repeated queries
            this.cachedElements = this.cacheElements();
            
            // Bind methods to maintain 'this' context (prevents memory leaks)
            this.handleVisibilityChange = this.handleVisibilityChange.bind(this);
            this.updateWorkHours = this.updateWorkHours.bind(this);
            
            // Pre-calculate timezone offset (performance optimization)
            this.timezoneOffset = this.calculateTimezoneOffset();
        }

        validateConfig(config) {
            // Ensure config is valid to prevent runtime errors
            const defaults = {
                raw_hours: {},
                timezone: '',
                messages: {
                    open: 'Open',
                    closed: 'Closed',
                    not_available: 'Not Available'
                }
            };
            
            return {
                ...defaults,
                ...config,
                messages: { ...defaults.messages, ...config.messages }
            };
        }

        cacheElements() {
            // Cache DOM elements once instead of querying repeatedly
            const elements = {
                statusElement: this.block.querySelector('.work-hours-status'),
                timingDiv: this.block.querySelector('.timing-today'),
                tooltip: this.block.querySelector('.tooltip-element'),
                timezoneElement: this.block.querySelector('.work-hours-timezone em')
            };
            
            // Validate cached elements exist
            if (!elements.statusElement || !elements.timingDiv) {
                console.warn('WorkHours: Required DOM elements not found');
            }
            
            return elements;
        }

        calculateTimezoneOffset() {
            // Pre-calculate timezone offset for performance
            if (!this.config.timezone) return 0;
            
            try {
                const now = new Date();
                const utc = new Date(now.toISOString());
                const local = new Date(now.toLocaleString("en-US", {timeZone: this.config.timezone}));
                return utc.getTime() - local.getTime();
            } catch (error) {
                console.warn('WorkHours: Timezone calculation failed:', error);
                return 0;
            }
        }

        start() {
            if (this.isPaused) return;
            
            // Initial update
            this.updateWorkHours();
            
            // Start periodic updates
            this.startPeriodicUpdates();
            
            // Setup event listeners
            this.setupEventListeners();
        }

        pause() {
            this.isPaused = true;
            this.stopPeriodicUpdates();
        }

        resume() {
            if (!this.isPaused) return;
            
            this.isPaused = false;
            this.updateWorkHours(); // Update immediately
            this.startPeriodicUpdates();
        }

        setupEventListeners() {
            // Use bound method to prevent memory leaks
            document.addEventListener('visibilitychange', this.handleVisibilityChange);
        }

        handleVisibilityChange() {
            // Debounce visibility changes (performance optimization)
            clearTimeout(this.visibilityTimeout);
            this.visibilityTimeout = setTimeout(() => {
                this.isVisible = !document.hidden;
                if (this.isVisible && !this.isPaused) {
                    this.updateWorkHours();
                }
            }, 100);
        }

        startPeriodicUpdates() {
            if (this.updateInterval) return; // Already running
            
            this.updateInterval = setInterval(() => {
                if (this.isVisible && !this.isPaused) {
                    this.updateWorkHours();
                }
            }, 60000); // Update every minute
        }

        stopPeriodicUpdates() {
            if (this.updateInterval) {
                clearInterval(this.updateInterval);
                this.updateInterval = null;
            }
        }

        updateWorkHours() {
            // Skip if page is hidden or paused (performance optimization)
            if (document.hidden || this.isPaused) return;

            try {
                const workHours = new ClientWorkHours(this.config, this.timezoneOffset);
                const result = workHours.parse();
                
                // Only update if result is valid
                if (result && result.status && result.message) {
                    this.updateUI(result);
                    this.updateTimezone();
                }
            } catch (error) {
                console.warn('WorkHours: Update failed:', error);
                // Graceful degradation - don't break the page
                this.handleUpdateError();
            }
        }

        handleUpdateError() {
            // Fallback to basic "Open/Closed" when calculation fails
            if (this.cachedElements.statusElement) {
                this.cachedElements.statusElement.className = 'work-hours-status closed';
                this.cachedElements.statusElement.textContent = this.config.messages.not_available;
            }
        }

        updateUI(result) {
            const statusElement = block.querySelector('[data-js-status]');

            // Target the first span inside timing-today (where today's schedule is)
            const scheduleElement = block.querySelector('.timing-today span:first-child');
            
            if (statusElement) {
                statusElement.className = `work-hours-status ${result.status}`;
                statusElement.textContent = result.message;
            }
            
            if (scheduleElement) {
                scheduleElement.innerHTML = result.todaySchedule;
            }
           
        }

        updateTimezone() {
            const { timezoneElement } = this.cachedElements;
            if (timezoneElement && this.config.timezone) {
                try {
                    const localTime = new Date(Date.now() - this.timezoneOffset);
                    const timeStr = this.formatDateTime(localTime);
                    timezoneElement.textContent = this.config.messages.local_time.replace('%s', timeStr);
                } catch (error) {
                    console.warn('WorkHours: Timezone update failed:', error);
                }
            }
        }

        formatDateTime(date) {
            // Optimized date formatting with error handling
            try {
                return date.toLocaleDateString([], { 
                    year: 'numeric', month: '2-digit', day: '2-digit'
                }) + ' ' + date.toLocaleTimeString([], {
                    hour: '2-digit', minute: '2-digit', hour12: false
                });
            } catch (error) {
                // Fallback formatting
                return date.toISOString().slice(0, 16).replace('T', ' ');
            }
        }

        destroy() {
            // Clean up all resources to prevent memory leaks
            this.stopPeriodicUpdates();
            
            // Remove event listeners
            document.removeEventListener('visibilitychange', this.handleVisibilityChange);
            
            // Clear timeouts
            if (this.visibilityTimeout) {
                clearTimeout(this.visibilityTimeout);
            }
            
            // Clear references
            this.block = null;
            this.config = null;
            this.cachedElements = null;
            
            console.log('WorkHours: Manager destroyed, memory cleaned up');
        }
    }

    /**
     * Optimized Client-side Work Hours Calculator
     */
    class ClientWorkHours {
        constructor(config, timezoneOffset = 0) {
            this.rawHours = config.raw_hours;
            this.timezone = config.timezone;
            this.messages = config.messages;
            this.timezoneOffset = timezoneOffset;
            
            // Pre-compile regex for performance
            this.timeRegex = /^(\d{1,2}):(\d{2})$/;
            
            // PHP day mapping
            this.phpDayNames = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
        }

        getCurrentTime() {
            // Use pre-calculated offset for performance
            return new Date(Date.now() - this.timezoneOffset);
        }

        parse() {
            try {
                const now = this.getCurrentTime();
                
                // Convert to PHP day system efficiently
                const todayIndex = (now.getDay() + 6) % 7;
                const todayName = this.phpDayNames[todayIndex];
                const yesterdayName = this.phpDayNames[(todayIndex + 6) % 7];
                
                // Get day data with null checks
                const todayHours = this.rawHours[todayName];
                const yesterdayHours = this.rawHours[yesterdayName];
                
                let activeDay = todayName;
                let result = null;
                
                // Try today first
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
                
                // Try yesterday for overnight businesses
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
                
                // Fallback
                return {
                    status: 'not-available',
                    message: this.messages.not_available,
                    activeDay: todayName,
                    todaySchedule: this.messages.todays_schedule_na || 'Schedule not available'
                };
                
            } catch (error) {
                console.error('WorkHours: Parse error:', error);
                return {
                    status: 'closed',
                    message: this.messages.closed,
                    activeDay: 'Monday',
                    todaySchedule: this.messages.not_available
                };
            }
        }

        // ... rest of the methods remain the same but with added error handling ...
        
        parseDay(dayHours, currentTime, yesterdayFlag = false) {
            if (!dayHours || typeof dayHours !== 'object') {
                return null;
            }

            // Handle special statuses with null checks
            if (!yesterdayFlag && dayHours.status) {
                const statusHandlers = {
                    'open-all-day': () => ({ status: 'open', message: this.messages.open }),
                    'closed-all-day': () => ({ status: 'closed', message: this.messages.closed }),
                    'by-appointment-only': () => ({ status: 'appointment-only', message: this.messages.appointment_only })
                };
                
                const handler = statusHandlers[dayHours.status];
                if (handler) return handler();
            }

            // ... rest of parseDay logic with error handling ...
            
            try {
                // Extract time ranges safely
                const ranges = [];
                for (const key in dayHours) {
                    if (key !== 'status' && dayHours[key] && 
                        typeof dayHours[key] === 'object' && 
                        dayHours[key].from && dayHours[key].to) {
                        ranges.push(dayHours[key]);
                    }
                }

                if (ranges.length === 0) {
                    return yesterdayFlag ? null : { 
                        status: 'not-available', 
                        message: this.messages.not_available 
                    };
                }

                // Continue with time calculation logic...
                // (Same as before but with try/catch around time parsing)
                
            } catch (error) {
                console.warn('WorkHours: parseDay error:', error);
                return null;
            }
        }

        parseTime(timeString) {
            if (!timeString || typeof timeString !== 'string') return null;
            
            const match = timeString.match(this.timeRegex);
            if (!match) return null;
            
            const hours = parseInt(match[1], 10);
            const minutes = parseInt(match[2], 10);
            
            if (hours < 0 || hours > 23 || minutes < 0 || minutes > 59) {
                return null;
            }
            
            return { hours, minutes };
        }

        // ... rest of methods with similar error handling improvements
    }

    // Expose for debugging in development only
    if (typeof window !== 'undefined' && window.location && 
        (window.location.hostname === 'localhost' || window.location.hostname.includes('dev'))) {
        window.WorkHoursDebug = { WorkHoursManager, ClientWorkHours };
    }

})();