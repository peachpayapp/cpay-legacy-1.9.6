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
Module_Tools_Hook::init();
Module_Tools_Hook_AJAX::init();
Module_Tools_Hook_Assets::init();
Module_Tools_Hook_Settings::init();