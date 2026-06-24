<?php
/**
 * CoffeeTrail Flow -- Bootstrap
 *
 * Single entry point required from functions.php. Defines constants,
 * loads all class files, and initialises every module.
 *
 * @package CoffeeTrail
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'CT_FLOW_DIR',     __DIR__ );
define( 'CT_FLOW_URL',     get_stylesheet_directory_uri() . '/includes/ct-flow' );
define( 'CT_FLOW_VERSION', '1.0.3' );

// ---------------------------------------------------------------------------
// WooCommerce product IDs — change here if they ever shift.
// ---------------------------------------------------------------------------
define( 'CT_FLOW_FREE_PRODUCT_ID', 24 );
define( 'CT_FLOW_PRO_PRODUCT_ID',  25 );

// ---------------------------------------------------------------------------
// Load class files
// ---------------------------------------------------------------------------
require_once CT_FLOW_DIR . '/class-fixes.php';
require_once CT_FLOW_DIR . '/class-registration-hooks.php';
require_once CT_FLOW_DIR . '/class-locked-fields.php';
require_once CT_FLOW_DIR . '/class-dashboard-hooks.php';
require_once CT_FLOW_DIR . '/class-selective-approval.php';
require_once CT_FLOW_DIR . '/class-admin-panel.php';
require_once CT_FLOW_DIR . '/class-auto-save.php';
require_once CT_FLOW_DIR . '/class-email-notifications.php';
require_once CT_FLOW_DIR . '/class-grow-payment.php';
require_once CT_FLOW_DIR . '/class-grow-webhook.php';
require_once CT_FLOW_DIR . '/class-wizard-controller.php';
require_once CT_FLOW_DIR . '/class-wizard-page.php';

// class-terms-step.php is retained for the edit-listing flow (existing listings).
// The wizard's own terms.php template handles the submission flow instead.
require_once CT_FLOW_DIR . '/class-terms-step.php';

// ---------------------------------------------------------------------------
// Initialise all modules
// ---------------------------------------------------------------------------
CT_Flow_Fixes::init();
CT_Flow_Registration::init();
CT_Flow_Terms_Step::init();
CT_Flow_Locked_Fields::init();
CT_Flow_Dashboard::init();
CT_Flow_Selective_Approval::init();
CT_Flow_Admin_Panel::init();
CT_Flow_Auto_Save::init();
CT_Flow_Email_Notifications::init();
CT_Grow_Payment::init();
CT_Grow_Webhook::init();
CT_Flow_Wizard_Controller::init();
CT_Flow_Wizard_Page::init();
