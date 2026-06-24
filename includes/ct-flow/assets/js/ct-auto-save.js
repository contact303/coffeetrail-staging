/**
 * ct-auto-save.js
 *
 * Auto-saves text / select / textarea field values in the add-listing or
 * edit-listing form.  Debounces user input so the server is only hit after
 * 3 seconds of inactivity, preventing excessive AJAX calls while typing.
 *
 * Files and galleries are excluded — they are handled by the form's native
 * multipart submit.
 *
 * Depends on: jQuery, ctAutoSave (wp_localize_script)
 * Enqueued by: CT_Flow_Fixes::enqueue_assets() (add-listing page only)
 */
/* global ctAutoSave, jQuery */
(function ( $ ) {
	'use strict';

	if ( ! ctAutoSave ) { return; }

	var DEBOUNCE_MS  = 3000;
	var FORM_SEL     = '#submit-job-form';
	var STATUS_SEL   = '.ct-autosave-status';
	var SKIP_TYPES   = [ 'file', 'button', 'submit', 'reset', 'image', 'hidden', 'checkbox', 'radio' ];
	var SKIP_CLASSES = [ 'ct-terms-', 'ct-locked', 'ct-upgrade' ];

	var $form        = $( FORM_SEL );
	var $status      = $( STATUS_SEL );
	var debounceTimer = null;
	var isSaving      = false;
	var isDirty       = false;

	if ( ! $form.length ) { return; }

	// Show status bar once we start auto-saving.
	$status.show();

	// -------------------------------------------------------------------------
	// Collect saveable field values
	// -------------------------------------------------------------------------
	function collectFields() {
		var fields = {};
		var jobId  = $form.find( '[name="job_id"]' ).val() || '0';
		var step   = $form.find( '[name="step"]' ).val()   || '0';

		$form.find( 'input, textarea, select' ).each( function () {
			var $el  = $( this );
			var name = $el.attr( 'name' );
			var type = ( $el.attr( 'type' ) || '' ).toLowerCase();

			if ( ! name ) { return; }
			if ( SKIP_TYPES.indexOf( type ) !== -1 ) { return; }

			// Skip fields with ct-specific class prefixes.
			for ( var i = 0; i < SKIP_CLASSES.length; i++ ) {
				if ( $el.closest( '[class*="' + SKIP_CLASSES[i] + '"]' ).length ) { return; }
			}

			// Skip location / map inputs (they contain multiple sub-fields).
			if ( $el.closest( '.field-type-location' ).length ) { return; }

			// Skip gallery / file fields.
			if ( $el.closest( '.field-type-file' ).length ) { return; }

			fields[ name ] = $el.val();
		} );

		return {
			job_id       : jobId,
			step         : step,
			listing_type : $form.find( '[name="listing_type"]' ).val() || '',
			listing_package : $form.find( '[name="listing_package"]' ).val() || '',
			fields       : fields
		};
	}

	// -------------------------------------------------------------------------
	// Send the AJAX request
	// -------------------------------------------------------------------------
	function doSave() {
		if ( isSaving ) { return; }

		isSaving = true;
		isDirty  = false;
		setStatus( ctAutoSave.i18n.saving );

		var data      = collectFields();
		data.action   = 'ct_autosave';
		data.nonce    = ctAutoSave.nonce;

		$.post( ctAutoSave.ajaxUrl, data )
			.done( function ( res ) {
				if ( res && res.success ) {
					// Update the hidden job_id input so subsequent auto-saves
					// and the final submit use the right post ID.
					if ( res.data.job_id && res.data.job_id !== '0' ) {
						var $jobId = $form.find( '[name="job_id"]' );
						if ( $jobId.val() === '0' || ! $jobId.val() ) {
							$jobId.val( res.data.job_id );
						}
					}
					setStatus( ctAutoSave.i18n.saved + ' (' + res.data.saved_at_f + ')' );
				} else {
					setStatus( ctAutoSave.i18n.unsaved );
				}
			} )
			.fail( function () {
				setStatus( ctAutoSave.i18n.unsaved );
			} )
			.always( function () {
				isSaving = false;
				if ( isDirty ) {
					scheduleAutoSave();
				}
			} );
	}

	// -------------------------------------------------------------------------
	// Debounce scheduling
	// -------------------------------------------------------------------------
	function scheduleAutoSave() {
		clearTimeout( debounceTimer );
		debounceTimer = setTimeout( doSave, DEBOUNCE_MS );
	}

	function setStatus( msg ) {
		$status.text( msg ).show();
	}

	// -------------------------------------------------------------------------
	// Bind events
	// -------------------------------------------------------------------------
	$form.on( 'input change', 'input, textarea, select', function () {
		var $el  = $( this );
		var type = ( $el.attr( 'type' ) || '' ).toLowerCase();

		if ( SKIP_TYPES.indexOf( type ) !== -1 ) { return; }
		if ( $el.closest( '.field-type-location, .field-type-file' ).length ) { return; }

		isDirty = true;
		setStatus( ctAutoSave.i18n.unsaved );
		scheduleAutoSave();
	} );

	// Do not auto-save on manual submit (avoid race).
	$form.on( 'submit', function () {
		clearTimeout( debounceTimer );
	} );

	// File fields: show informational message.
	$form.on( 'change', 'input[type="file"]', function () {
		setStatus( ctAutoSave.i18n.files );
	} );

} )( jQuery );
