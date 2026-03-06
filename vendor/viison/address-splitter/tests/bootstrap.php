<?php

require_once __DIR__ . '/../vendor/autoload.php';
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
