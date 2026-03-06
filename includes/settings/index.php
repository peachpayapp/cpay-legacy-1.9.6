<?php
/**
 * Index
 *
 * @author ConvesioPay - based on the Woosa WC integration
 */

namespace Woosa\Adyen;


//prevent direct access data leaks
defined( 'ABSPATH' ) || exit;


//init
Settings_Hook::init();
Settings_Hook_Dashboard::init();
Settings_Hook_Payments::init();

