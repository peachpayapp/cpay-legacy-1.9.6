<?php
/**
 * Index
 *
 * @author Woosa Team
 */

namespace Woosa\Adyen;


//prevent direct access data leaks
defined( 'ABSPATH' ) || exit;


//init
Module_Authorization_Hook::init();
Module_Authorization_Hook_AJAX::init();
Module_Authorization_Hook_Assets::init();
Module_Authorization_Hook_Settings::init();