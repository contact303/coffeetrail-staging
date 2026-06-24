/**
 * ct-terms-scroll.js
 *
 * Scroll-to-unlock behaviour for the terms agreement step:
 *
 *  1. Checkboxes are disabled until the user has scrolled to the bottom of
 *     .ct-terms-scroll-box (or the box bottom becomes visible in the viewport).
 *
 *  2. Once unlocked, the Next button enables only when ALL checkboxes with
 *     the class .ct-terms-unlock-required are checked.
 *
 * Two scroll strategies run in parallel:
 *  - Internal div scroll  (#ct-terms-scroll-box has overflow-y: auto / height).
 *  - Window scroll fallback (handles cases where a parent layout makes the box
 *    expand to full content height and the page scrolls instead of the box).
 *
 * Listens for the 'ct:stepLoaded' custom event fired by ct-wizard.js so it
 * re-initialises every time the terms step is injected into the DOM, including
 * on Back/Next navigation.  The old document.ready approach exited immediately
 * because #ct-terms-scroll-box is absent on every other step.
 */
/* global jQuery */
( function ( $ ) {
	'use strict';

	$( document ).on( 'ct:stepLoaded', function ( e, data ) {

		if ( ! data || data.step !== 'terms' ) {
			return;
		}

		var SCROLL_THRESHOLD = 30; // px tolerance when deciding "reached the end"

		// Re-query all DOM elements on each load so state is clean.
		var $box   = $( '#ct-terms-scroll-box' );
		var $hint  = $( '#ct-terms-scroll-hint' );
		var $btn   = $( '#ct-next-btn' );
		var $hint2 = $( '#ct-terms-submit-hint' );

		if ( ! $box.length ) {
			return;
		}

		var scrollUnlocked = false;

		// ------------------------------------------------------------------ //
		// Checkbox lock / unlock
		// ------------------------------------------------------------------ //

		function lockCheckboxes() {
			$( '.ct-terms-unlock-required' ).prop( 'disabled', true );
		}

		function unlockCheckboxes() {
			$( '.ct-terms-unlock-required' ).prop( 'disabled', false );
		}

		// ------------------------------------------------------------------ //
		// Unlock gate
		// ------------------------------------------------------------------ //

		function doUnlock() {
			if ( scrollUnlocked ) { return; }
			scrollUnlocked = true;
			$hint.fadeOut( 300 );
			unlockCheckboxes();
			updateNextButton();
		}

		// ------------------------------------------------------------------ //
		// Next-button state
		// ------------------------------------------------------------------ //

		function updateNextButton() {
			if ( ! scrollUnlocked ) {
				$btn.addClass( 'ct-wizard-btn--disabled' ).attr( 'aria-disabled', 'true' );
				return;
			}

			var allChecked = true;
			$( '.ct-terms-unlock-required' ).each( function () {
				if ( ! $( this ).is( ':checked' ) ) {
					allChecked = false;
					return false; // break
				}
			} );

			if ( allChecked ) {
				$btn.removeClass( 'ct-wizard-btn--disabled' ).removeAttr( 'aria-disabled' );
			} else {
				$btn.addClass( 'ct-wizard-btn--disabled' ).attr( 'aria-disabled', 'true' );
			}

			if ( $hint2.length ) {
				$hint2.toggle( ! allChecked );
			}
		}

		// ------------------------------------------------------------------ //
		// Scroll strategy 1: internal div scroll
		// ------------------------------------------------------------------ //

		function checkBoxScroll() {
			var el           = $box[0];
			var scrollBottom = el.scrollHeight - el.scrollTop - el.clientHeight;
			if ( scrollBottom <= SCROLL_THRESHOLD ) {
				doUnlock();
			}
		}

		// ------------------------------------------------------------------ //
		// Scroll strategy 2: window scroll fallback
		// ------------------------------------------------------------------ //

		function checkWindowScroll() {
			var rect = $box[0].getBoundingClientRect();
			if ( rect.bottom <= window.innerHeight + SCROLL_THRESHOLD ) {
				doUnlock();
			}
		}

		// ------------------------------------------------------------------ //
		// Initialise
		// ------------------------------------------------------------------ //

		lockCheckboxes();
		updateNextButton();

		// Unlock immediately if the box content is too short to scroll.
		setTimeout( function () {
			var el = $box[0];
			if ( el.scrollHeight <= el.clientHeight + SCROLL_THRESHOLD ) {
				doUnlock();
			}
		}, 150 );

		$box.on( 'scroll.termsScroll', checkBoxScroll );
		$( window ).on( 'scroll.termsScroll', checkWindowScroll );

		// Clean up scroll handlers when navigating away from terms step
		// so they do not fire after the DOM element has been replaced.
		$( document ).one( 'ct:stepLoaded', function () {
			$box.off( 'scroll.termsScroll' );
			$( window ).off( 'scroll.termsScroll' );
		} );

		$( document ).on( 'change.termsScroll', '.ct-terms-unlock-required', updateNextButton );

		// Clean up change handler similarly.
		$( document ).one( 'ct:stepLoaded', function () {
			$( document ).off( 'change.termsScroll' );
		} );

	} );

} )( jQuery );
