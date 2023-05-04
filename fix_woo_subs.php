<?php

/*
 *	@snippet		Fix WooCommerce Subscriptions
 *	@description	Runs daily to check for and fetch a missing 
 * 					'Stripe Customer ID' or 'Stripe Source ID' in 
 *					Subscriptions.
 *					
 */

add_action('run_snippet_daily', 'ff_fix_woo_subs');

function ff_fix_woo_subs(){

	// Get an array of subscriptions
	$subs = wcs_get_subscriptions( array('subscriptions_per_page' => -1) );

	foreach($subs as $sub){

		// Get the customer email
		$order = wc_get_order($sub->id);
		$billing_email = $order->get_billing_email();

		// Check for customer & payment method IDs
		$saved_customer_id = get_post_meta($sub->id, '_stripe_customer_id', true );
		$saved_payment_id = get_post_meta($sub->id, '_stripe_source_id', true );

		// If either is missing, fetch 'em
		if ( !$saved_customer_id || !$saved_payment_id ){

			// Get missing IDs from stripe
			$ids = ff_get_stripe_ids_from_email($billing_email);

			// Save the IDs to post_meta for the subscription order
			if (is_array($ids)){
				update_post_meta($sub->id, '_stripe_customer_id', $ids['customer_id'] );
				update_post_meta($sub->id, '_stripe_source_id', $ids['payment_id'] );
			}
		}			
	}
}

function ff_get_stripe_ids_from_email($email = false){

	if (!$email) return false;

	// Request headers
	$args = array(
		'headers' => array(
			'Content-Type' => 'application/json',
			'Authorization' => 'Basic ' . base64_encode(' API_KEY_GOES_HERE ')
		)
	);

	// Get customer from Stripe
	$url = "https://api.stripe.com/v1/customers?limit=1&email=" . $email;
	$response = wp_remote_get($url, $args);
	$res = json_decode( wp_remote_retrieve_body($response), true );

	if ($response['response']['message'] == "OK") {

		// Get customer ID & Payment ID
		if ( isset($res['data'][0]['id']) ){
			$customer_id = $res['data'][0]['id'];

			// The default payment method saved in stripe is the one we use for subscriptions
			$payment_id = $res['data'][0]['invoice_settings']['default_payment_method'];

			// If there's no default payment, get the last used instead
			if (!$payment_id){

				$payment_intent_url = "https://api.stripe.com/v1/payment_intents?limit=1&customer=" . $customer_id;
				$payment_intent_response = wp_remote_get($payment_intent_url, $args);
				$payment_intent_res = json_decode( wp_remote_retrieve_body($payment_intent_response), true );

				// Last used payment method
				if ( isset($payment_intent_res['data'][0]['id']) ){
					$payment_id = $payment_intent_res['data'][0]['payment_method'];
				}
			}

			return array("customer_id" => $customer_id, "payment_id" => $payment_id);
		}
	}
	return false;
}