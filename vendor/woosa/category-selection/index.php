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
Module_Category_Selection_Hook_Assets::init();
Module_Category_Selection_Hook_AJAX::init();