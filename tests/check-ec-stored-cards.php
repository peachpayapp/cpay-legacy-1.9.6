<?php
/**
 * PROD-8156: get_ec_stored_cards must skip null supportedShopperInteractions.
 *
 * php tests/check-ec-stored-cards.php
 */

define( 'ABSPATH', __DIR__ . '/' );

require dirname( __DIR__ ) . '/includes/service/class-service.php';
require dirname( __DIR__ ) . '/includes/service/class-service-checkout.php';

class Service_Checkout_Stored_Cards_Stub extends Woosa\Adyen\Service_Checkout {
	public $stored = [];

	public function __construct() {}

	public function get_stored_payment_methods( $country = null, int $amount = 0 ) {
		return $this->stored;
	}
}

$checkout         = new Service_Checkout_Stored_Cards_Stub();
$checkout->stored = [
	[ 'lastFour' => '1234', 'supportedShopperInteractions' => null ],
	[ 'lastFour' => '5678', 'supportedShopperInteractions' => [ 'Ecommerce' ] ],
	[ 'lastFour' => '0000', 'supportedShopperInteractions' => [ 'ContAuth' ] ],
	[ 'supportedShopperInteractions' => [ 'Ecommerce' ] ],
];

$cards = $checkout->get_ec_stored_cards();

if ( 1 !== count( $cards ) || '5678' !== $cards[0]['lastFour'] ) {
	fwrite( STDERR, "FAIL: expected only Ecommerce card 5678, got " . json_encode( $cards ) . "\n" );
	exit( 1 );
}

echo "OK\n";
