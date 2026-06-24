/**
 * ct-wizard.js
 *
 * Multi-step wizard frontend for the CoffeeTrail listing submission flow.
 *
 * Responsibilities:
 *  - Step navigation (Next / Back) via AJAX — no full-page reload.
 *  - Delayed validation: errors only shown AFTER the user clicks Next,
 *    not on focus/blur (per designer spec).
 *  - Image upload pipeline: HEIC→JPG, compression, crop UI, async queue.
 *  - Save & Exit modal + AJAX persist.
 *  - Help modal.
 *  - Draft resume detection.
 *
 * Depends on globals injected by wp_localize_script:
 *   ctWizard.ajaxUrl, ctWizard.nonce, ctWizard.myListingsUrl, ctWizard.i18n
 *
 * Depends on window globals from CDN scripts:
 *   imageCompression  (browser-image-compression)
 *   heic2any          (heic2any)
 *   Cropper           (cropperjs)
 *
 * @package CoffeeTrail
 */

/* global ctWizard, imageCompression, heic2any, Cropper, jQuery */

(function ($) {
    'use strict';

    // =========================================================================
    // State
    // =========================================================================

    const WizardState = {
        currentStep: '',
        listingPackage: 'free',
        jobId: 0,
        validationTriggered: false,   // true once user has clicked Next once on this step
        cropperInstances: {},         // field_key => Cropper instance
        uploadQueue: [],
        processingQueue: false,
    };

    // =========================================================================
    // DOM selectors
    // =========================================================================

    const SEL = {
        container:       '#ct-wizard-container',
        stepContent:     '#ct-wizard-step-content',
        nextBtn:         '#ct-next-btn',
        footerMessage:   '#ct-footer-message',
        helpTrigger:     '#ct-help-trigger',
        saveExitTrigger: '.js-save-exit-trigger',
        helpModal:       '#ct-help-modal',
        saveExitModal:   '#ct-save-exit-modal',
        saveExitConfirm: '#ct-save-exit-confirm',
        modalClose:      '[data-close-modal]',
        uploadZone:      '.ct-upload-zone',
        uploadInput:     '.ct-upload-input',
        cropContainer:   '.ct-crop-container',
        processingSpinner: '#ct-upload-processing',
    };

    // =========================================================================
    // Initialisation
    // =========================================================================

    function init() {
        const $container = $(SEL.container);
        if (!$container.length) {
            return;
        }

        WizardState.currentStep     = $container.data('step')    || '';
        WizardState.listingPackage  = $container.data('package') || 'free';
        WizardState.jobId           = parseInt($container.data('job-id'), 10) || 0;

        bindNavigation();
        bindModals();
        bindUploadZones();
        bindUploadRemove();
        bindCardSelections();
        bindCheckboxToggles();
        bindPhoneAutofill();
        bindHoursToggle();
        bindKosherToggle();
        bindMenuTypeToggle();
        bindFieldStates();
        bindFloatingButtonClearance();

        // Handle Grow payment success — check the flag first (race: grow wallet fires
        // before this ready callback) then listen for the event (normal flow).
        if (window.ctGrowPaid) {
            saveStepAndAdvance();
        }
        $(document).on('ct:grow:payment_success', function () {
            if (WizardState.currentStep === 'payment') {
                saveStepAndAdvance();
            }
        });

        // Fire for the initial step so ct-terms-scroll.js / ct-grow-wallet.js
        // can initialize if the page loaded directly onto their step (draft resume).
        $(document).trigger('ct:stepLoaded', { step: WizardState.currentStep });
    }

    // =========================================================================
    // Third-party floating button clearance
    // =========================================================================

    /**
     * Lift third-party floating buttons (translate widget, WPConsent cookie bar)
     * above the wizard's fixed footer.
     *
     * CSS alone is unreliable here: these plugins often (a) set `bottom` inline via
     * JS — beating any stylesheet rule, and/or (b) make a WRAPPER the positioned
     * element while the id we target is a static child, so setting `bottom` on the
     * child does nothing. So we walk up to the nearest fixed/absolute ancestor and
     * set `bottom` inline with `important`, and re-apply via a MutationObserver
     * because the buttons are injected/repositioned asynchronously.
     */
    function bindFloatingButtonClearance() {
        const SELECTORS = [
            '#tr-button-mobile', '.tr-button',
            '#wpconsent-consent-floating', '.wpconsent-consent-floating-button',
        ];
        const GAP = 105; // px above the viewport bottom (clears the wizard footer)

        function liftOne(el) {
            // Find the nearest positioned ancestor (or the element itself).
            let node   = el;
            let target = el;
            while (node && node !== document.body) {
                const pos = window.getComputedStyle(node).position;
                if (pos === 'fixed' || pos === 'absolute') { target = node; break; }
                node = node.parentElement;
            }
            if (target.style.getPropertyValue('bottom') !== GAP + 'px') {
                target.style.setProperty('bottom', GAP + 'px', 'important');
            }
        }

        function applyAll() {
            SELECTORS.forEach(function (sel) {
                document.querySelectorAll(sel).forEach(liftOne);
            });
        }

        applyAll();

        // Re-apply when the plugins inject or move their buttons. Debounced so we
        // don't thrash on unrelated DOM churn.
        let pending = null;
        const observer = new MutationObserver(function () {
            if (pending) { return; }
            pending = window.setTimeout(function () {
                pending = null;
                applyAll();
            }, 200);
        });
        observer.observe(document.body, { childList: true, subtree: true });
    }

    // =========================================================================
    // Navigation
    // =========================================================================

    function bindNavigation() {
        $(document).on('click', SEL.nextBtn, function () {
            if ($(this).hasClass('ct-wizard-btn--disabled') ||
                $(this).attr('aria-disabled') === 'true') {
                return;
            }
            WizardState.validationTriggered = true;
            handleNext();
        });

        $(document).on('click', '[data-action="back"]', function () {
            const prevStep = $(this).data('prev-step');
            if (prevStep) {
                loadStep(prevStep);
            }
        });
    }

    function handleNext() {
        // Clear the previous validation pass first, THEN validate so the fresh
        // field-level red flares set by validateCurrentStep() survive (showStepErrors
        // no longer wipes them).
        clearStepErrors();

        const errors = validateCurrentStep();

        if (errors.length > 0) {
            showStepErrors(errors);
            return;
        }

        saveStepAndAdvance();
    }

    // =========================================================================
    // Validation (client-side, mirrors PHP)
    // =========================================================================

    /**
     * Validate the current step's fields.
     * Only called when user clicks Next — never on blur/focus.
     *
     * @returns {string[]} Array of error messages (empty = valid).
     */
    function validateCurrentStep() {
        const step   = WizardState.currentStep;
        const errors = [];

        switch (step) {

            case 'basics': {
                const cartType = $('input[name="cart_type"]:checked').val();
                if (!cartType) {
                    errors.push('יש לבחור סוג עגלה.');
                    $('.ct-radio-rows').addClass('ct-field--error');
                    setFieldError($('input[name="cart_type"]').first(), 'יש לבחור סוג עגלה');
                }
                const $title = $('[name="job_title"]');
                if (!$title.val().trim()) {
                    errors.push('שם העגלה הוא שדה חובה.');
                    setFieldError($title, 'זהו שדה חובה');
                }
                break;
            }

            case 'contact': {
                const $phone    = $('[name="phone"]');
                const $whatsapp = $('[name="whatsapp"]');
                const phoneVal    = $phone.val().trim();
                const whatsappVal = $whatsapp.val().trim();
                if (!phoneVal && !whatsappVal) {
                    errors.push('יש להזין לפחות אמצעי קשר אחד ללקוחות.');
                    setFieldError($phone, 'יש להזין לפחות אמצעי קשר אחד');
                    setFieldError($whatsapp, 'יש להזין לפחות אמצעי קשר אחד');
                } else {
                    if (phoneVal && !isValidPhone($phone)) {
                        errors.push('מספר הטלפון אינו תקין.');
                        setFieldError($phone, 'מספר טלפון לא תקין');
                    }
                    if (whatsappVal && !isValidPhone($whatsapp)) {
                        errors.push('מספר ה-WhatsApp אינו תקין.');
                        setFieldError($whatsapp, 'מספר לא תקין');
                    }
                }
                const $adminPhone = $('[name="ct_admin_phone"]');
                if (!$adminPhone.val().trim()) {
                    errors.push('מספר הטלפון לקופיטרייל הוא שדה חובה.');
                    setFieldError($adminPhone, 'זהו שדה חובה');
                } else if (!isValidPhone($adminPhone)) {
                    errors.push('מספר הטלפון לקופיטרייל אינו תקין.');
                    setFieldError($adminPhone, 'מספר טלפון לא תקין');
                }
                break;
            }

            case 'location': {
                // Field names match the OSM/Leaflet widget inputs (.latitude-input / .longitude-input).
                const lat  = $('[name="lat"]').val().trim();
                const lng  = $('[name="lng"]').val().trim();
                if (!lat || !lng) {
                    errors.push('יש לבחור מיקום על המפה.');
                    setFieldError($('.address-input').first(), 'יש לבחור מיקום על המפה');
                }
                break;
            }

            case 'images': {
                const coverId  = $('[name="cover_image"]').val();
                const galleryIds = $('[name="gallery[]"]').map(function () {
                    return $(this).val();
                }).get().filter(Boolean);

                if (!coverId) {
                    errors.push('יש להעלות תמונת קאבר.');
                }
                if (galleryIds.length < 3) {
                    errors.push('יש להעלות לפחות 3 תמונות נוספות (הועלו ' + galleryIds.length + '/3).');
                }
                break;
            }

            case 'terms': {
                const $terms = $('[name="ct_listing_terms"]');
                if (!$terms.is(':checked')) {
                    errors.push('יש לאשר את תנאי השימוש.');
                    $terms.closest('.ct-checkbox-item').addClass('ct-field--error');
                }
                if (WizardState.listingPackage === 'pro') {
                    const $cancel = $('[name="ct_cancellation_fee"]');
                    if (!$cancel.is(':checked')) {
                        errors.push('יש לאשר את תנאי הביטול.');
                        $cancel.closest('.ct-checkbox-item').addClass('ct-field--error');
                    }
                }
                break;
            }
        }

        // Mark error fields with CSS class when validation was triggered.
        if (errors.length > 0) {
            markErrorFields();
        }

        return errors;
    }

    function markErrorFields() {
        // Mark required empty fields on the current step. Targets .ct-required (added
        // in the step templates) rather than the HTML [required] attribute, which in
        // practice only job_title carried.
        $('.ct-field.ct-required').each(function () {
            const $f = $(this);
            if (!$f.val() || !$f.val().trim()) {
                $f.addClass('ct-field--error');
            }
        });
        $('.ct-phone-field.ct-required').each(function () {
            const $wrap = $(this);
            const val   = ($wrap.find('.ct-phone-input').val() || '').trim();
            if (!val) {
                $wrap.addClass('ct-field--error');
            }
        });
    }

    function showStepErrors(errors) {
        // Show the footer summary message near the Next button. Field-level red flares
        // are applied by validateCurrentStep() / the server-error path — do NOT clear
        // them here (handleNext already cleared the previous pass before validating).
        $(SEL.footerMessage).text(errors[0]).show();
        scrollToFirstError();
    }

    /**
     * Scroll the first flagged field into view and focus it — standard form-error UX
     * so the user is taken straight to what needs fixing.
     */
    function scrollToFirstError() {
        const $first = $(SEL.stepContent)
            .find('.ct-field--error, .ct-phone-field.ct-field--error, .ct-radio-rows.ct-field--error, .ct-checkbox-item.ct-field--error')
            .first();
        if (!$first.length) { return; }

        const node = $first[0];
        if (node.scrollIntoView) {
            node.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
        // Focus the actual control for keyboard users (skip non-focusable wrappers).
        const $focusable = $first.is('input, select, textarea')
            ? $first
            : $first.find('input, select, textarea').first();
        if ($focusable.length) {
            $focusable.trigger('focus');
        }
    }

    function bindFieldStates() {
        // Auto-clear error as soon as the user starts typing in an errored field
        $(document).on('input change', '.ct-field', function () {
            if ($(this).hasClass('ct-field--error') && $(this).val().trim()) {
                clearFieldError($(this));
            }
        });

        // Mark email fields valid on blur when the value passes basic format check
        $(document).on('blur', 'input[type="email"].ct-field', function () {
            const $el = $(this);
            const val = $el.val().trim();
            if (!val) { return; }
            if (/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(val)) {
                $el.removeClass('ct-field--error').addClass('ct-field--valid');
                clearFieldError($el);
            }
        });

        // On blur: mark a phone field green when valid, red when empty-but-required or
        // when the entered number is malformed (symmetric red/green feedback).
        $(document).on('blur', '.ct-phone-input', function () {
            const $el   = $(this);
            const $wrap = $el.closest('.ct-phone-field');
            if (!($el.val() || '').trim()) {
                if ($wrap.hasClass('ct-required')) {
                    $wrap.removeClass('ct-field--valid').addClass('ct-field--error');
                }
                return;
            }
            if (isValidPhone($el)) {
                $wrap.removeClass('ct-field--error').addClass('ct-field--valid');
                clearFieldError($el);
            } else {
                $wrap.removeClass('ct-field--valid').addClass('ct-field--error');
            }
        });

        // Generic required text/url fields: red when empty on blur, green when filled.
        // (Email has its own format-aware handler above; skip it here.)
        $(document).on('blur', '.ct-field.ct-required', function () {
            const $el = $(this);
            if ($el.is('[type="email"]')) { return; }
            if (($el.val() || '').trim()) {
                $el.removeClass('ct-field--error').addClass('ct-field--valid');
                clearFieldError($el);
            } else {
                $el.removeClass('ct-field--valid').addClass('ct-field--error');
            }
        });

        // Re-validate when the prefix changes (keeps valid/error state in sync)
        $(document).on('change', '.ct-phone-prefix-select', function () {
            $(this).closest('.ct-phone-field').find('.ct-phone-input').trigger('blur');
        });

        // Auto-clear a phone field's error state as the user edits it
        $(document).on('input', '.ct-phone-input', function () {
            const $wrap = $(this).closest('.ct-phone-field');
            if ($wrap.hasClass('ct-field--error')) {
                $wrap.removeClass('ct-field--error');
                clearFieldError($(this));
            }
        });

        // Clear the business-type (cart_type) error as soon as one is picked
        $(document).on('change', 'input[name="cart_type"]', function () {
            const $rows = $('.ct-radio-rows').removeClass('ct-field--error');
            $rows.closest('.ct-field-group').find('> .ct-field-error').remove();
        });

        // Clear a checkbox-item error (e.g. terms agreement) once it is checked.
        $(document).on('change', '.ct-checkbox-item input[type="checkbox"]', function () {
            if ($(this).is(':checked')) {
                $(this).closest('.ct-checkbox-item').removeClass('ct-field--error');
            }
        });
    }

    function clearStepErrors() {
        $(SEL.footerMessage).text('').hide();
        $('.ct-field--error').removeClass('ct-field--error');
        $('.ct-field--valid').removeClass('ct-field--valid');
        $('.ct-field-error').remove();
        $('.ct-field-valid-msg').remove();
    }

    /**
     * Mark a specific field as invalid and insert an inline error message
     * directly below it inside its .ct-field-group container.
     * Phone fields (`.ct-phone-field`) receive the error class on the wrapper.
     */
    function setFieldError($field, message) {
        const $wrapper = $field.closest('.ct-phone-field');
        if ($wrapper.length) {
            $wrapper.addClass('ct-field--error');
        } else {
            $field.addClass('ct-field--error');
        }
        const $group = $field.closest('.ct-field-group');
        const $target = $group.length ? $group : $field.parent();
        if (!$target.find('> .ct-field-error').length) {
            $('<span class="ct-field-error" role="alert">')
                .text(message)
                .appendTo($target);
        }
    }

    /**
     * Remove the error state and inline message for a single field.
     */
    function clearFieldError($field) {
        $field.closest('.ct-phone-field').removeClass('ct-field--error');
        $field.removeClass('ct-field--error ct-field--valid');
        $field.closest('.ct-field-group').find('> .ct-field-error').remove();
    }

    // =========================================================================
    // AJAX: Save step and load next
    // =========================================================================

    function saveStepAndAdvance() {
        const fields = collectStepFields();

        setLoading(true);

        $.ajax({
            url:    ctWizard.ajaxUrl,
            method: 'POST',
            data: {
                action:  'ct_wizard_save_step',
                nonce:   ctWizard.nonce,
                step:    WizardState.currentStep,
                package: WizardState.listingPackage,
                job_id:  WizardState.jobId,
                fields:  fields,
            },
        })
        .done(function (response) {
            if (response.success) {
                WizardState.jobId           = response.data.job_id;
                WizardState.validationTriggered = false;
                loadStep(response.data.next_step);
            } else {
                const errors = response.data.errors || ['אירעה שגיאה. אנא נסו שוב.'];
                showStepErrors(errors);
            }
        })
        .fail(function () {
            showStepErrors(['שגיאת תקשורת. אנא בדקו את החיבור לאינטרנט ונסו שוב.']);
        })
        .always(function () {
            setLoading(false);
        });
    }

    /**
     * Sync the persistent wizard header buttons with the newly loaded step.
     *
     * The header is rendered once server-side and is NOT replaced by AJAX step
     * loads (only #ct-wizard-step-content is swapped). Without this function,
     * navigating away from the landing step leaves the header showing "יציאה"
     * with no "שאלות?" button.
     *
     * @param {string} stepKey  The step key just loaded (e.g. 'landing', 'basics').
     */
    function updateHeaderForStep(stepKey) {
        const isLanding = (stepKey === 'landing');
        $('#ct-save-exit-main').toggle(!isLanding);
        $('#ct-exit-only').toggle(isLanding);
        $('#ct-help-trigger').toggle(!isLanding);
    }

    function loadStep(stepKey) {
        setLoading(true);

        $.ajax({
            url:    ctWizard.ajaxUrl,
            method: 'POST',
            data: {
                action:  'ct_wizard_load_step',
                nonce:   ctWizard.nonce,
                step:    stepKey,
                package: WizardState.listingPackage,
                job_id:  WizardState.jobId,
            },
        })
        .done(function (response) {
            if (response.success) {
                WizardState.currentStep = stepKey;
                $(SEL.container).data('step', stepKey);
                $(SEL.stepContent).html(response.data.html);

                // Update the persistent header with the new step's label and progress.
                $('.ct-wizard-header__step-label').text(response.data.label || '');
                $('.ct-wizard-progress__bar').css('width', (response.data.progress || 0) + '%');

                // Swap header buttons between landing ("יציאה") and all other steps
                // ("שמירה ויציאה" + "שאלות?"). The header is rendered once server-side
                // and never replaced by AJAX, so JS must sync it on each step change.
                updateHeaderForStep(stepKey);

                // Re-initialise per-step bindings.
                reinitStep();
                // Scroll to top.
                $('html, body').animate({ scrollTop: 0 }, 200);
            } else {
                showStepErrors([response.data.message || 'שגיאה בטעינת השלב.']);
            }
        })
        .fail(function () {
            showStepErrors(['שגיאת תקשורת בטעינת השלב.']);
        })
        .always(function () {
            setLoading(false);
        });
    }

    /** Collect all serialisable field values from the current step. */
    function collectStepFields() {
        const fields  = {};
        const $form   = $(SEL.stepContent);

        $form.find('input, select, textarea').each(function () {
            const $el  = $(this);
            const name = $el.attr('name');
            if (!name) { return; }

            const type = ($el.attr('type') || '').toLowerCase();

            if (type === 'checkbox') {
                if ($el.is(':checked')) {
                    // Strip '[]' suffix (same as hidden inputs below) so the key stored in
                    // state matches what templates read back (e.g. 'menu_categories' not 'menu_categories[]').
                    const cleanName = name.endsWith('[]') ? name.slice(0, -2) : name;
                    if (Array.isArray(fields[cleanName])) {
                        fields[cleanName].push($el.val());
                    } else if (fields[cleanName] !== undefined) {
                        fields[cleanName] = [fields[cleanName], $el.val()];
                    } else {
                        fields[cleanName] = $el.val();
                    }
                }
                return;
            }

            if (type === 'radio') {
                if ($el.is(':checked')) {
                    fields[name] = $el.val();
                }
                return;
            }

            if (type === 'file') {
                return; // Handled by upload AJAX separately.
            }

            // Accumulate array-named hidden inputs (e.g. gallery[]) instead of overwriting.
            // Strip the '[]' suffix so the key sent to PHP is 'gallery' not 'gallery[]' —
            // jQuery would otherwise encode the brackets into the param name and PHP would
            // receive $_POST['fields']['gallery[]'] instead of $_POST['fields']['gallery'].
            if (type === 'hidden' && name.endsWith('[]')) {
                const cleanName = name.slice(0, -2);
                if (Array.isArray(fields[cleanName])) {
                    fields[cleanName].push($el.val());
                } else if (fields[cleanName] !== undefined) {
                    fields[cleanName] = [fields[cleanName], $el.val()];
                } else {
                    fields[cleanName] = [$el.val()];
                }
                return;
            }

            fields[name] = $el.val() || '';
        });

        // Collect hours day-toggle state from .ct-hours-toggle checkboxes
        // (checkboxes without a value don't appear in serialized form data).
        $form.find('.ct-hours-toggle').each(function () {
            var day = $(this).data('day');
            if (day) {
                fields['day_active[' + day + ']'] = $(this).is(':checked') ? '1' : '';
            }
        });

        // Phone fields: combine the prefix <select> with the local digits into a
        // single value (e.g. 050 + 1234567 -> 0501234567) under the input's name.
        $form.find('.ct-phone-input').each(function () {
            const $el  = $(this);
            const name = $el.attr('name');
            if (name) {
                fields[name] = combinePhone($el);
            }
        });

        return fields;
    }

    /**
     * Combine a phone field's prefix dropdown with its local digits.
     * Returns '' when no local digits were entered so empty fields stay empty
     * for validation. Strips a re-typed prefix to avoid duplication.
     *
     * @param   {jQuery}  $field  The .ct-phone-input element.
     * @returns {string}
     */
    function combinePhone($field) {
        const $wrap  = $field.closest('.ct-phone-field');
        const prefix = $wrap.find('.ct-phone-prefix-select').val() || '';
        let   digits = ($field.val() || '').replace(/\D/g, '');
        if (prefix && digits.indexOf(prefix) === 0) {
            digits = digits.slice(prefix.length);
        }
        return digits ? (prefix + digits) : '';
    }

    /**
     * Validate an Israeli phone number from a .ct-phone-input (prefix + local).
     * Accepts a leading 0 followed by 8–9 digits (landline 9, mobile 10 total).
     *
     * @param   {jQuery}  $field
     * @returns {boolean}
     */
    function isValidPhone($field) {
        return /^0\d{8,9}$/.test(combinePhone($field));
    }

    // =========================================================================
    // Re-initialise per-step bindings after step HTML replacement
    // =========================================================================

    function reinitStep() {
        WizardState.validationTriggered = false;
        WizardState.cropperInstances   = {};

        // initLocationPicker needs a live DOM element — must re-run per step.
        // All other bind* functions use $(document).on() delegation and are
        // registered once in init(); calling them again would duplicate handlers.
        initLocationPicker();

        // Gallery sortable binds to a fresh #ct-gallery-previews element each load.
        gallerySortable = null;
        initGallerySortable();

        // Notify step-specific scripts (ct-terms-scroll.js, ct-grow-wallet.js).
        $(document).trigger('ct:stepLoaded', { step: WizardState.currentStep });
    }

    // =========================================================================
    // Gallery drag-to-reorder (SortableJS)
    // =========================================================================

    let gallerySortable = null;

    function initGallerySortable() {
        const el = document.getElementById('ct-gallery-previews');
        if (!el || typeof Sortable === 'undefined' || gallerySortable) {
            return;
        }
        // Reorder persists automatically: collectStepFields() reads gallery[]
        // hidden inputs (nested inside each preview) in DOM order.
        gallerySortable = Sortable.create(el, {
            animation: 150,
            draggable: '.ct-upload-preview',
        });
    }

    // =========================================================================
    // Modals
    // =========================================================================

    function bindModals() {
        // Help modal.
        $(document).on('click', SEL.helpTrigger, function () {
            openModal(SEL.helpModal);
        });

        // Save & Exit trigger.
        $(document).on('click', SEL.saveExitTrigger, function () {
            openModal(SEL.saveExitModal);
        });

        // Confirm save & exit.
        $(document).on('click', SEL.saveExitConfirm, function () {
            doSaveExit();
        });

        // Close modal (backdrop or close button).
        $(document).on('click', SEL.modalClose, function () {
            closeAllModals();
        });

        // ESC key.
        $(document).on('keydown', function (e) {
            if (e.key === 'Escape') {
                closeAllModals();
            }
        });
    }

    function openModal(selector) {
        $(selector).removeAttr('hidden').attr('aria-hidden', 'false');
        $('body').addClass('ct-modal-open');
    }

    function closeAllModals() {
        $('.ct-modal').attr('hidden', '').attr('aria-hidden', 'true');
        $('body').removeClass('ct-modal-open');
    }

    function doSaveExit() {
        const fields = collectStepFields();

        setLoading(true);
        closeAllModals();

        $.ajax({
            url:    ctWizard.ajaxUrl,
            method: 'POST',
            data: {
                action:  'ct_wizard_save_exit',
                nonce:   ctWizard.nonce,
                step:    WizardState.currentStep,
                package: WizardState.listingPackage,
                job_id:  WizardState.jobId,
                fields:  fields,
            },
        })
        .done(function (response) {
            if (response.success && response.data.redirect) {
                window.location.href = response.data.redirect;
            } else {
                window.location.href = ctWizard.myListingsUrl;
            }
        })
        .fail(function () {
            window.location.href = ctWizard.myListingsUrl;
        });
    }

    // =========================================================================
    // Image Upload Pipeline
    // =========================================================================

    function bindUploadZones() {
        // Drag-over feedback.
        $(document).on('dragover dragenter', SEL.uploadZone, function (e) {
            e.preventDefault();
            $(this).addClass('ct-upload-zone--dragover');
        });

        $(document).on('dragleave dragexit drop', SEL.uploadZone, function (e) {
            e.preventDefault();
            $(this).removeClass('ct-upload-zone--dragover');
        });

        // Drop event.
        $(document).on('drop', SEL.uploadZone, function (e) {
            e.preventDefault();
            const files      = e.originalEvent.dataTransfer.files;
            const $zone      = $(this);
            const fieldKey   = $zone.data('field-key') || 'gallery';
            const isCrop     = !!$zone.data('crop');
            const cropRatio  = parseFloat($zone.data('crop-ratio')) || 1;
            processFiles(files, $zone, fieldKey, isCrop, cropRatio);
        });

        // Click-to-upload via hidden file input.
        $(document).on('click', SEL.uploadZone, function () {
            const $zone  = $(this);
            const $input = $zone.find(SEL.uploadInput);
            if ($input.length) {
                $input.trigger('click');
            }
        });

        $(document).on('change', SEL.uploadInput, function () {
            const $input    = $(this);
            const $zone     = $input.closest(SEL.uploadZone);
            const fieldKey  = $zone.data('field-key') || 'gallery';
            const isCrop    = !!$zone.data('crop');
            const cropRatio = parseFloat($zone.data('crop-ratio')) || 1;
            processFiles(this.files, $zone, fieldKey, isCrop, cropRatio);
            // Reset so same file can be re-selected.
            $input.val('');
        });
    }

    /**
     * Entry point for file processing pipeline.
     *
     * @param {FileList}  files
     * @param {jQuery}    $zone
     * @param {string}    fieldKey
     * @param {boolean}   cropFirst   Show crop UI before compression.
     * @param {number}    cropAspect  Crop aspect ratio (1 = square, 1.333 = 4:3).
     */
    function processFiles(files, $zone, fieldKey, cropFirst, cropAspect) {
        const fileArray = Array.from(files);

        fileArray.forEach(function (file) {
            WizardState.uploadQueue.push({ file, $zone, fieldKey, cropFirst, cropAspect });
        });

        if (!WizardState.processingQueue) {
            drainUploadQueue();
        }
    }

    function drainUploadQueue() {
        if (WizardState.uploadQueue.length === 0) {
            WizardState.processingQueue = false;
            hideProcessingSpinner();
            return;
        }

        WizardState.processingQueue = true;

        // Process up to 3 files concurrently.
        const batch = WizardState.uploadQueue.splice(0, 3);

        showProcessingSpinner();

        const promises = batch.map(function (item) {
            return convertAndCompress(item.file)
                .then(function (processedBlob) {
                    if (item.cropFirst) {
                        return showCropUI(processedBlob, item.$zone, item.cropAspect);
                    }
                    return processedBlob;
                })
                .then(function (finalBlob) {
                    return uploadFile(finalBlob, item.fieldKey, item.$zone);
                })
                .catch(function (err) {
                    console.error('[CT Wizard] Upload error:', err);
                    showUploadError(item.$zone, ctWizard.i18n.uploadError);
                });
        });

        Promise.all(promises).then(function () {
            drainUploadQueue();
        });
    }

    /**
     * HEIC detection → heic2any conversion → browser-image-compression.
     *
     * @param   {File}  file
     * @returns {Promise<Blob>}  Compressed JPG blob.
     */
    async function convertAndCompress(file) {
        let blob = file;

        // Detect HEIC/HEIF by extension or MIME type.
        const isHeic = /\.(heic|heif)$/i.test(file.name) ||
                       file.type === 'image/heic' ||
                       file.type === 'image/heif';

        if (isHeic && typeof heic2any === 'function') {
            blob = await heic2any({ blob: file, toType: 'image/jpeg', quality: 0.9 });
            // heic2any can return array on multi-frame HEIC.
            if (Array.isArray(blob)) { blob = blob[0]; }
        }

        // Skip compression for PDFs.
        if (blob.type === 'application/pdf') {
            return blob;
        }

        if (typeof imageCompression === 'function') {
            blob = await imageCompression(blob, {
                maxWidthOrHeight: 1920,
                maxSizeMB:        0.8,
                useWebWorker:     true,
                fileType:         'image/jpeg',
            });
        }

        return blob;
    }

    /**
     * Show Cropper.js UI, wait for user to confirm, return cropped blob.
     *
     * @param   {Blob}    blob
     * @param   {jQuery}  $zone
     * @param   {number}  aspectRatio
     * @returns {Promise<Blob>}
     */
    function showCropUI(blob, $zone, aspectRatio) {
        return new Promise(function (resolve, reject) {
            const url   = URL.createObjectURL(blob);
            const $wrap = $zone.closest('.ct-upload-field');

            // Build crop overlay.
            const $overlay = $('<div class="ct-crop-overlay">').appendTo('body');
            const $img     = $('<img src="' + url + '" alt="">').appendTo(
                $('<div class="ct-crop-inner">').appendTo($overlay)
            );

            $('<div class="ct-crop-actions">')
                .append(
                    $('<button type="button" class="ct-wizard-btn ct-wizard-btn--next">').text('חתוך ושמור')
                        .on('click', function () {
                            const canvas = cropper.getCroppedCanvas({
                                imageSmoothingQuality: 'high',
                            });
                            canvas.toBlob(function (croppedBlob) {
                                $overlay.remove();
                                URL.revokeObjectURL(url);
                                resolve(croppedBlob);
                            }, 'image/jpeg', 0.9);
                        })
                )
                .append(
                    $('<button type="button" class="ct-wizard-btn ct-wizard-btn--back">').text('ביטול')
                        .on('click', function () {
                            $overlay.remove();
                            URL.revokeObjectURL(url);
                            reject(new Error('crop cancelled'));
                        })
                )
                .appendTo($overlay.find('.ct-crop-inner'));

            const cropper = new Cropper($img[0], {
                aspectRatio: aspectRatio,
                viewMode:    1,
                autoCropArea: 0.8,
            });
        });
    }

    /**
     * Upload processed blob via AJAX to the server-side handler.
     *
     * @param   {Blob}    blob
     * @param   {string}  fieldKey
     * @param   {jQuery}  $zone
     * @returns {Promise}
     */
    function uploadFile(blob, fieldKey, $zone) {
        return new Promise(function (resolve, reject) {
            const formData = new FormData();
            formData.append('action',    'ct_wizard_upload_file');
            formData.append('nonce',     ctWizard.nonce);
            formData.append('field_key', fieldKey);
            formData.append('job_id',    WizardState.jobId);
            formData.append('file',      blob, 'upload.jpg');

            $.ajax({
                url:         ctWizard.ajaxUrl,
                method:      'POST',
                data:        formData,
                processData: false,
                contentType: false,
            })
            .done(function (response) {
                if (response.success) {
                    handleUploadSuccess(response.data, fieldKey, $zone);
                    resolve(response.data);
                } else {
                    showUploadError($zone, response.data.message || ctWizard.i18n.uploadError);
                    reject(new Error(response.data.message));
                }
            })
            .fail(function () {
                showUploadError($zone, ctWizard.i18n.uploadError);
                reject(new Error('Network error'));
            });
        });
    }

    /**
     * After successful upload: insert a preview thumbnail with its hidden input
     * nested inside it. Gallery previews go into the horizontal, reorderable
     * #ct-gallery-previews container; single-image fields (cover, logo) keep
     * their preview inside the upload zone. Removal is handled by a single
     * delegated handler (bindUploadRemove).
     */
    function handleUploadSuccess(data, fieldKey, $zone) {
        const attachmentId = data.attachment_id;
        const thumbUrl     = data.thumb_url || data.url;
        const isGallery    = (fieldKey === 'gallery');
        const inputName    = isGallery ? 'gallery[]' : fieldKey;

        const $preview = $('<div class="ct-upload-preview">')
            .append($('<img>').attr({ src: thumbUrl, alt: 'תמונה שהועלתה' }))
            .append($('<button type="button" class="ct-upload-remove" aria-label="הסר תמונה">').html('&times;'))
            .append($('<input type="hidden">').attr('name', inputName).val(attachmentId));

        if (isGallery) {
            $('#ct-gallery-previews').append($preview);
            initGallerySortable();
        } else {
            $zone.find('.ct-upload-zone__placeholder').hide();
            $zone.append($preview);
        }

        updateGalleryCounter();
        updateNextButtonState();
    }

    /**
     * Delegated remove handler for all image previews (dynamic + server-rendered).
     * The hidden input lives inside the preview, so removing the preview removes
     * the stored attachment id too.
     */
    function bindUploadRemove() {
        $(document).on('click', '.ct-upload-remove', function (e) {
            e.stopPropagation();
            const $preview = $(this).closest('.ct-upload-preview');
            const $zone    = $preview.closest('.ct-upload-zone');
            $preview.remove();
            // Restore the placeholder when a single-image zone becomes empty.
            if ($zone.length && $zone.find('.ct-upload-preview').length === 0) {
                $zone.find('.ct-upload-zone__placeholder').show();
            }
            updateGalleryCounter();
            updateNextButtonState();
        });
    }

    function showUploadError($zone, message) {
        $zone.find('.ct-upload-error').remove();
        $zone.append('<p class="ct-upload-error ct-field-error">' + message + '</p>');
    }

    function updateGalleryCounter() {
        const count  = $('[name="gallery[]"]').length;
        const $ctr   = $('.ct-gallery-counter');
        if ($ctr.length) {
            $ctr.text(count + '/15 תמונות הועלו' + (count < 3 ? ' — נדרשות לפחות 3' : ''));
        }
        updateNextButtonState();
    }

    // =========================================================================
    // Next button enable/disable (silent — per UX spec)
    // =========================================================================

    function updateNextButtonState() {
        const step = WizardState.currentStep;
        let enabled = true;

        if (step === 'images') {
            const hasCover   = !!$('[name="cover_image"]').val();
            const galleryCount = $('[name="gallery[]"]').length;
            enabled = hasCover && galleryCount >= 3;

            // Show/update footer message silently (no error style).
            if (!enabled) {
                const msg = !hasCover
                    ? 'כדי להמשיך, צריך להוסיף תמונת קאבר ולפחות 3 תמונות נוספות'
                    : 'כדי להמשיך, צריך להוסיף לפחות ' + (3 - galleryCount) + ' תמונות נוספות';
                $(SEL.footerMessage).text(msg).show().removeClass('ct-footer-message--error');
            } else {
                $(SEL.footerMessage).text('').hide();
            }
        }

        const $btn = $(SEL.nextBtn);
        if (enabled) {
            $btn.removeClass('ct-wizard-btn--disabled').removeAttr('aria-disabled');
        } else {
            $btn.addClass('ct-wizard-btn--disabled').attr('aria-disabled', 'true');
        }
    }

    // =========================================================================
    // Card selection (cart-type, menu-type)
    // =========================================================================

    function bindCardSelections() {
        $(document).on('click', '.ct-card-option', function (e) {
            const $card  = $(this);
            const $radio = $card.find('input[type="radio"]');
            const $checkbox = $card.find('input[type="checkbox"]');

            if ($checkbox.length) {
                // Multi-select: toggle individual card.
                e.preventDefault();
                const newState = !$checkbox.prop('checked');
                $checkbox.prop('checked', newState);
                $card.toggleClass('ct-card-option--selected', newState);
                updateNextButtonState();
                return;
            }

            if ($radio.length) {
                // Single-select: deselect all in group, select this one.
                const $group = $card.closest('.ct-card-group');
                $group.find('.ct-card-option').removeClass('ct-card-option--selected');
                $card.addClass('ct-card-option--selected');
                $radio.prop('checked', true).trigger('change');
                updateNextButtonState();
            }
        });
    }

    // =========================================================================
    // Checkbox-to-field toggles (contact methods, etc.)
    // =========================================================================

    function bindCheckboxToggles() {
        // Show/hide input fields based on paired checkbox.
        $(document).on('change', '.ct-checkbox-toggle', function () {
            const target = $(this).data('toggle-target');
            if (target) {
                $(target).toggleClass('ct-hidden', !$(this).is(':checked'));
            }
        });
    }

    // =========================================================================
    // Admin phone autofill
    // =========================================================================

    function bindPhoneAutofill() {
        $(document).on('change', '#ct-autofill-admin-phone', function () {
            const $admin     = $('[name="ct_admin_phone"]');
            const $adminWrap = $admin.closest('.ct-phone-field');
            if ($(this).is(':checked')) {
                // Prefer the phone field, fall back to WhatsApp.
                let $src = $('[name="phone"]');
                if (!($src.val() || '').trim()) {
                    $src = $('[name="whatsapp"]');
                }
                const $srcWrap = $src.closest('.ct-phone-field');
                $adminWrap.find('.ct-phone-prefix-select')
                    .val($srcWrap.find('.ct-phone-prefix-select').val());
                $admin.val($src.val());
            } else {
                $admin.val('');
            }
        });
    }

    // =========================================================================
    // Hours screen: day toggle
    // =========================================================================

    function bindHoursToggle() {
        $(document).on('change', '.ct-hours-toggle', function () {
            var day    = $(this).data('day');
            var active = $(this).is(':checked');
            var $row   = $('.ct-hours-row[data-day="' + day + '"]');
            $row.toggleClass('is-open', active);
            $row.find('.ct-hours-row__times').toggleClass('ct-hidden', !active);
            $row.find('.ct-hours-row__closed').toggleClass('ct-hidden',  active);
        });
    }

    // =========================================================================
    // Kosher: show/hide kosher type + certificate upload
    // =========================================================================

    function bindKosherToggle() {
        $(document).on('change', '[name="is_kosher"]', function () {
            const isKosher = $(this).val() === 'yes';
            $('.ct-kosher-details').toggleClass('ct-hidden', !isKosher);
        });
    }

    // =========================================================================
    // Menu upload: image vs PDF toggle
    // =========================================================================

    function bindMenuTypeToggle() {
        $(document).on('change', '[name="menu_type"]', function () {
            const val = $(this).val();
            $('.ct-menu-upload-image').toggleClass('ct-hidden', val !== 'image');
            $('.ct-menu-upload-pdf').toggleClass('ct-hidden', val !== 'pdf');
        });
    }

    // =========================================================================
    // Map initialisation (Location step) — OSM / Leaflet
    // =========================================================================

    /**
     * Initialise the theme's OSM/Leaflet location picker for the location step.
     *
     * This is the same widget MyListing renders when editing a listing. Rather
     * than hand-rolling a Leaflet map, we let the theme build it:
     *
     *   1. new MyListing.Maps.Map(el) constructs the Leaflet map, reads its centre
     *      from the element's data-options (defaultLat/defaultLng/zoom), and
     *      self-registers in MyListing.Maps.instances keyed by the element id
     *      ("location-picker-map").
     *   2. Firing maps:loaded runs openstreetmap.js's picker handler, which finds
     *      #location-picker-map, drops a marker, and wires click-to-place,
     *      reverse-geocoding, manual lat/lng entry, and the Nominatim autocomplete
     *      on the .address-input / .form-location-autocomplete field — all writing
     *      to .latitude-input / .longitude-input.
     *
     * Called from reinitStep() each time the location step is loaded (initial
     * render or AJAX navigation), because the map must bind to a live DOM node.
     *
     * @param {number} [attempt] internal retry counter while the maps script loads.
     */
    function initLocationPicker(attempt) {
        const mapEl = document.getElementById('location-picker-map');
        if (!mapEl) { return; }

        // openstreetmap.js (mylisting-openstreetmap) provides MyListing.Maps.Map.
        // Both it and this script load in the footer, so on a fast jump straight
        // to the location step the class may not be defined yet — retry briefly.
        if (!window.MyListing || !MyListing.Maps || typeof MyListing.Maps.Map !== 'function') {
            attempt = attempt || 0;
            if (attempt < 20) {
                setTimeout(function () { initLocationPicker(attempt + 1); }, 150);
            }
            return;
        }

        // Drop any stale instance registered under this id from a previous visit
        // to the step (the old DOM node was discarded when AJAX replaced the step).
        if (Array.isArray(MyListing.Maps.instances)) {
            MyListing.Maps.instances = MyListing.Maps.instances.filter(function (entry) {
                return !entry || entry.id !== 'location-picker-map';
            });
        }

        // Build the theme's map (auto-registers under the element id) and let
        // openstreetmap.js's picker + autocomplete handlers attach on maps:loaded.
        new MyListing.Maps.Map(mapEl);
        $(document).trigger('maps:loaded');

        // Personalise the picker marker (cart-logo pin), guarantee it is draggable,
        // and wire dragend → lat/lng + reverse-geocode. Runs after the picker handler
        // has dropped its marker.
        customizeLocationMarker(mapEl, 0);
    }

    /**
     * Replace the location picker's marker with the cart logo (when one was
     * uploaded), ensure the marker is draggable, and bind a dragend handler.
     *
     * The theme's OSM picker (openstreetmap.js) only wires reverse-geocode on map
     * CLICK and address autocomplete — it does NOT bind anything to the marker's
     * dragend. So without this, dragging the pin updates neither the lat/lng inputs
     * nor the address field. We mirror the picker's click handler on dragend using
     * the same MyListing.Geocoder / MyListing.Maps.LatLng APIs.
     *
     * @param {HTMLElement} mapEl
     * @param {number}      attempt
     */
    function customizeLocationMarker(mapEl, attempt) {
        attempt = attempt || 0;
        const logoUrl = mapEl.getAttribute('data-logo-pin');

        // Find the Leaflet map for this picker via the theme's instance registry.
        let leaflet = null;
        if (window.MyListing && MyListing.Maps && Array.isArray(MyListing.Maps.instances)) {
            const inst = MyListing.Maps.instances.find(function (e) {
                return e && e.id === 'location-picker-map';
            });
            leaflet = (inst && inst.map) ? inst.map : null;
        }

        // Locate the picker's marker among the map layers.
        let marker = null;
        if (leaflet && typeof L !== 'undefined') {
            leaflet.eachLayer(function (layer) {
                if (layer instanceof L.Marker) { marker = layer; }
            });
        }

        if (!leaflet || !marker) {
            if (attempt < 20) {
                setTimeout(function () { customizeLocationMarker(mapEl, attempt + 1); }, 150);
            }
            return;
        }

        // Recompute size in case the container was measured before CSS applied.
        if (typeof leaflet.invalidateSize === 'function') {
            leaflet.invalidateSize();
        }

        // Guarantee draggability (the theme sets this, but make it explicit).
        if (marker.dragging && typeof marker.dragging.enable === 'function') {
            marker.dragging.enable();
        }

        // Wire dragend → lat/lng inputs + reverse-geocoded address, mirroring the
        // theme's map-click handler. Guard with a flag so the retry loop / repeat
        // step visits don't stack duplicate handlers on the same marker.
        if (!marker._ctDragendBound) {
            marker._ctDragendBound = true;
            marker.on('dragend', function () {
                const ll    = marker.getLatLng();
                const $wrap = $(mapEl).closest('.location-field-wrapper');
                $wrap.find('.latitude-input').val(ll.lat);
                $wrap.find('.longitude-input').val(ll.lng);

                if (window.MyListing && MyListing.Geocoder && MyListing.Maps) {
                    const latlng = new MyListing.Maps.LatLng(ll.lat, ll.lng);
                    MyListing.Geocoder.geocode(latlng.toGeocoderFormat(), function (res) {
                        if (res && res.address) {
                            $wrap.find('.address-input').val(res.address);
                        }
                    });
                }
            });
        }

        // Swap to the cart logo icon when one was uploaded.
        if (logoUrl) {
            marker.setIcon(L.icon({
                iconUrl:     logoUrl,
                iconSize:    [44, 44],
                iconAnchor:  [22, 44],
                popupAnchor: [0, -44],
                className:   'ct-map-pin',
            }));
        }
    }

    // =========================================================================
    // Spinner helpers
    // =========================================================================

    function showProcessingSpinner() {
        let $spinner = $(SEL.processingSpinner);
        if (!$spinner.length) {
            $spinner = $('<div id="ct-upload-processing" class="ct-upload-spinner" aria-live="polite">')
                .text(ctWizard.i18n.processing)
                .appendTo('body');
        }
        $spinner.show();
    }

    function hideProcessingSpinner() {
        $(SEL.processingSpinner).hide();
    }

    function setLoading(isLoading) {
        const $container = $(SEL.container);
        $container.toggleClass('ct-wizard--loading', isLoading);
        $(SEL.nextBtn).prop('disabled', isLoading);
    }

    // =========================================================================
    // Boot
    // =========================================================================

    $(document).ready(init);

}(jQuery));
