<?php
/**
 * Index
 *
 * @author ConvesioPay - based on the Woosa WC integration
 */

namespace Woosa\Adyen;


//prevent direct access data leaks
defined( 'ABSPATH' ) || exit;

require_once DIR_PATH . '/includes/cpay/class-settings-hook-cpay.php';
require_once DIR_PATH . '/includes/cpay/lib/stripe/stripe-loader.php';

// Only load Stripe SDK if it's not already loaded by another plugin
if (!class_exists('\Stripe\Stripe')) {
  require_once DIR_PATH . '/includes/cpay/lib/stripe/init.php';
}
// Authorize.net support removed - focusing on Stripe only for now

require_once DIR_PATH . '/includes/cpay/migration/class-gateway-migration.php';
require_once DIR_PATH . '/includes/cpay/migration/class-stripe-migration.php';
require_once DIR_PATH . '/includes/cpay/migration/class-authorizenet-migration.php';
require_once DIR_PATH . '/includes/cpay/migration/class-migration-hook.php';

//init
Cpay_Settings_Hook::init();

// Initialize migration system
\ConvesioPay\Gateway_Migration::init();
\ConvesioPay\AuthorizeNet_Migration::init();
\ConvesioPay\Migration_Hook::init();
\ConvesioPay\Migration_Hook::add_admin_hooks();