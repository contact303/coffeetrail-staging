/**
 * CoffeeTrail — Grow Payment Wallet
 *
 * Manages the Grow SDK wallet lifecycle on the PRO payment step:
 *  1. Makes a wp_ajax (ct_grow_init) call to the server to create the
 *     Grow payment process and receive authCode.
 *  2. Calls growPayment.renderPaymentOptions(authCode) to display the wallet.
 *  3. On payment success (SDK event or successUrl redirect), sets a global flag
 *     and triggers a custom jQuery event so ct-wizard.js can advance the step.
 *
 * Entry point: listens for the 'ct:stepLoaded' custom event fired by ct-wizard.js
 * so the wallet re-initialises every time the payment step is injected, including
 * after Back/Next navigation.  The old document.ready approach failed because
 * #ct-grow-wallet-container is absent on every other step.
 *
 * Race-condition safety: ct-grow-wallet.js is output before ct-wizard.js in the
 * HTML (script dependencies differ).  When Grow redirects back with
 * ?ct_grow_result=success both document.ready callbacks fire, but grow fires
 * first.  markPaidAndSubmit() therefore ALSO sets window.ctGrowPaid = true so
 * ct-wizard.js can check the flag when its own init() runs a moment later.
 *
 * Data object injected by CT_Flow_Fixes::enqueue_assets() as ctGrowData:
 *   ajaxUrl  {string}  WordPress AJAX endpoint
 *   nonce    {string}  wp_create_nonce('ct_grow_init')
 *   jobId    {number}  Listing post ID (updated inline in payment.php)
 *
 * @requires jQuery
 * @requires growPayment  (Grow client-side SDK loaded inline in the template)
 */
(function ($) {
	'use strict';

	if (typeof ctGrowData === 'undefined') {
		return;
	}

	var initialized = false;

	// -------------------------------------------------------------------------
	// Step-loaded entry point (fires on every payment step load, including
	// Back/Next navigation and initial page-load via ct-wizard.js init()).
	// -------------------------------------------------------------------------

	$(document).on('ct:stepLoaded', function (e, data) {
		if (!data || data.step !== 'payment') {
			return;
		}

		var $container = $('#ct-grow-wallet-container');
		if (!$container.length) {
			return;
		}

		// Reset so the wallet can re-render after Back navigation.
		initialized = false;

		var $loader   = $('#ct-grow-loader');
		var $errorBox = $('#ct-grow-error');

		// If Grow redirected back to this page after wallet completion,
		// the successUrl contains ct_grow_result=success.  Auto-advance.
		var params = new URLSearchParams(window.location.search);
		if (params.get('ct_grow_result') === 'success') {
			markPaidAndSubmit();
			return;
		}

		initWallet($container, $loader, $errorBox);
	});

	// -------------------------------------------------------------------------
	// Step 1: fetch authCode from WordPress via AJAX
	// -------------------------------------------------------------------------

	function initWallet($container, $loader, $errorBox) {
		if (initialized) {
			return;
		}
		initialized = true;

		showLoader($loader, true);
		hideError($errorBox);

		$.ajax({
			url:  ctGrowData.ajaxUrl,
			type: 'POST',
			data: {
				action: 'ct_grow_init',
				nonce:  ctGrowData.nonce,
				job_id: ctGrowData.jobId,
			},
			success: function (res) {
				showLoader($loader, false);
				if (!res.success) {
					var msg = (res.data && res.data.message)
						? res.data.message
						: 'שגיאה ביצירת תהליך התשלום.';
					showError($errorBox, msg);
					return;
				}
				renderWallet(res.data.authCode, $container, $loader, $errorBox);
			},
			error: function () {
				showLoader($loader, false);
				showError($errorBox, 'שגיאת תקשורת — אנא רעננו את הדף ונסו שוב.');
			}
		});
	}

	// -------------------------------------------------------------------------
	// Step 2: hand authCode to the Grow SDK
	// -------------------------------------------------------------------------

	function renderWallet(authCode, $container, $loader, $errorBox) {
		if (typeof growPayment === 'undefined') {
			showError($errorBox, 'רכיב התשלום לא נטען. אנא רעננו את הדף.');
			return;
		}

		if (typeof growPayment.onWalletChange === 'function') {
			growPayment.onWalletChange(function (state) {
				if (state === 'loading') {
					showLoader($loader, true);
					$container.hide();
				} else if (state === 'loaded') {
					showLoader($loader, false);
					$container.show();
				}
			});
		}

		growPayment.renderPaymentOptions(authCode);

		if (typeof growPayment.onPaymentSuccess === 'function') {
			growPayment.onPaymentSuccess(function () {
				markPaidAndSubmit();
			});
		}

		if (typeof growPayment.onPaymentFail === 'function') {
			growPayment.onPaymentFail(function (data) {
				var msg = (data && data.message) ? data.message : 'התשלום נדחה. אנא נסו שנית עם כרטיס אחר.';
				showError($errorBox, msg);
			});
		}
	}

	// -------------------------------------------------------------------------
	// Step 3: notify ct-wizard.js to advance to the success step
	//
	// Sets window.ctGrowPaid as a flag so ct-wizard.js can detect a completed
	// payment even if it reads the flag after this function runs (race condition
	// where grow fires before wizard's document.ready).
	// -------------------------------------------------------------------------

	function markPaidAndSubmit() {
		window.ctGrowPaid = true;
		$(document).trigger('ct:grow:payment_success');
	}

	// -------------------------------------------------------------------------
	// UI helpers
	// -------------------------------------------------------------------------

	function showLoader($loader, visible) {
		if ($loader && $loader.length) {
			$loader.toggle(visible);
		}
	}

	function showError($errorBox, msg) {
		if ($errorBox && $errorBox.length) {
			$errorBox.text(msg).show();
		}
	}

	function hideError($errorBox) {
		if ($errorBox && $errorBox.length) {
			$errorBox.hide().text('');
		}
	}

}(jQuery));
