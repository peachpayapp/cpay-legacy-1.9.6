<?php
/**
 * Payment API host follows adn_testmode: yes → api-qa, otherwise production api.
 *
 * php tests/check-payment-api-url.php
 */

define( 'ABSPATH', __DIR__ . '/' );
define( 'Woosa\\Adyen\\PREFIX', 'adn' );

$GLOBALS['adn_testmode'] = 'no';

function get_option( $name, $default = false ) {
	if ( 'adn_testmode' === $name ) {
		return $GLOBALS['adn_testmode'];
	}
	return $default;
}

require dirname( __DIR__ ) . '/includes/service/class-service-util.php';

$GLOBALS['adn_testmode'] = 'yes';
$qa                      = Woosa\Adyen\Service_Util::get_payment_api_url( 'payment/v1/wc-plugin/payments' );
if ( 'https://api-qa.convesiopay.com/payment/v1/wc-plugin/payments' !== $qa ) {
	fwrite( STDERR, "FAIL test mode: $qa\n" );
	exit( 1 );
}

$GLOBALS['adn_testmode'] = 'no';
$live                    = Woosa\Adyen\Service_Util::get_payment_api_url( 'payment/v1/wc-plugin/payments' );
if ( 'https://api.convesiopay.com/payment/v1/wc-plugin/payments' !== $live ) {
	fwrite( STDERR, "FAIL live mode: $live\n" );
	exit( 1 );
}

echo "OK\n";
