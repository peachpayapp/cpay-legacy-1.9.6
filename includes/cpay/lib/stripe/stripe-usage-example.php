<?php
/**
 * Example: How to use Stripe SDK directly in your migration code
 * 
 * This shows the simple approach - no Composer, no interfaces, just direct usage
 */

// Example usage in your migration code:
function example_stripe_usage() {
    // Create a Stripe client directly
    $stripe = new Stripe\StripeClient('sk_test_your_key_here');
    
    // Get a subscription
    $subscription = $stripe->subscriptions->retrieve('sub_1234567890');
    
    // Get the payment method
    $payment_method = $stripe->paymentMethods->retrieve($subscription->default_payment_method);
    
    // Get the last 4 digits of the card
    $last4 = $payment_method->card->last4;
    
    return $last4;
}

// That's it! Simple, direct, no complex dependencies
