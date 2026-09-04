<?php
/**
 * Plugin Name: ConvesioPay
 * Plugin URI: https://convesiopay.com
 * Description: Allows WooCommerce to take payments via ConvesioPay platform.
 * Version: 1.9.7-rc-1
 * Author: ConvesioPay
 * Author URI:  https://convesiopay.com
 * Text Domain: wc-convesiopay
 * Domain Path: /languages
 * Network: false
 *
 * WC requires at least: 3.5.0
 * WC tested up to: 10.2.2
 *
 * @author ConvesioPay - based on the Woosa WC integration
 */

namespace Woosa\Adyen;


//prevent direct access data leaks
defined( 'ABSPATH' ) || exit;


define(__NAMESPACE__ . '\PREFIX', 'adn');

define(__NAMESPACE__ . '\VERSION', '1.9.7-rc-1');

define(__NAMESPACE__ . '\NAME', 'ConvesioPay');

define(__NAMESPACE__ . '\DIR_URL', untrailingslashit(plugin_dir_url(__FILE__)));

define(__NAMESPACE__ . '\DIR_PATH', untrailingslashit(plugin_dir_path(__FILE__)));

define(__NAMESPACE__ . '\DIR_NAME', plugin_basename(DIR_PATH));

define(__NAMESPACE__ . '\FILE_NAME', __FILE__);

define(__NAMESPACE__ . '\DIR_BASENAME', DIR_NAME . '/'.basename(__FILE__));

define(__NAMESPACE__ . '\SETTINGS_TAB_ID', 'convesiopay');

define(__NAMESPACE__ . '\SETTINGS_TAB_NAME', 'ConvesioPay');

define(__NAMESPACE__ . '\SETTINGS_URL', admin_url('/admin.php?page=' . SETTINGS_TAB_ID));

define(__NAMESPACE__ . '\DEBUG', get_option(PREFIX . '_debug') === 'yes' ? true:false);

define(__NAMESPACE__ . '\DEBUG_FILE', DIR_PATH . '/debug.log');


//include files
require_once DIR_PATH . '/vendor/autoload.php';
require_once DIR_PATH . '/includes/cpay/index.php';

/* DEV BUILD - Enable Test Mode  */
update_option(PREFIX . '_testmode', 'yes');
/* DEV BUILD - Enable Test Mode  */
require_once DIR_PATH . '/kernl-update-checker/kernl-update-checker.php';
$ConvesioPay_Update_Check = \Puc_v4_Factory::buildUpdateChecker(
    'https://kernl.us/api/v1/updates/6696ea12113359c03cd20a8c/',
    __FILE__,
    'convesiopay'
);

//init
Module_Core_Hook::init();