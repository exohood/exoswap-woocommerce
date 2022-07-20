<?php
if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly
}

add_action('woocommerce_api_wc_gateway_exoswap', 'EXOWoo_payment_callback');

function EXOWoo_payment_callback() {
	$request = $_REQUEST;
	global $woocommerce;

	header('Content-Type: application/json');

	$return = json_encode(
		array(
			'success' => true
		)
	);

	try {

		$order_id = $request['shop_order_id'];

		$order = new WC_Order($order_id);

		if (!$order || !$order->get_id()) {
			throw new Exception('Order #' . $order_id . ' does not exists', 0);
		}

		$token = get_post_meta($order->get_id(), 'exoswap_order_token', true);

		if (empty($token) || strcmp($request['callback_token'], $token) !== 0) {
			throw new Exception('Callback token does not match', 1);
		}

		$message = $request['message'];
		$paid = empty($request['paid']) ? false : ($request['paid'] === true || $request['paid'] === 'true');


		if (empty($message)) {
			throw new Exception('There are no message or status', 2);
		}

		$order->add_order_note('[exoswap] : ' . $message);

		if ($paid) {
			$order->payment_complete();
		}

	} catch (Exception $e) {
		$return = json_encode(
			array(
				'success' => false,
				'error' => array(
					'code' => $e->getCode(),
					'message' => $e->getMessage()
				)
			)
		);
	}

	wp_die($return);
}
