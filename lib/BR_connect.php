<?php
if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly
}

require_once 'EXO_token.php';

/**
 * @return string
 */
function EXOWoo_api_endpoint() {
	return 'https://api.exohood.com/api/v2';
}

/**
 * @return string
 */
function EXOWoo_endpoint() {
	return 'https://exohood.com';
}

/**
 * @return string
 */
function EXOWoo_generateID() {
	return md5(mktime());
}

/**
 * @param $params
 * @return string
 */
function EXOWoo_generate_private_hash($params) {
	return md5($params['amount']);
}

/**
 * @param $data
 * @param $secret
 * @param $timestamp
 * @return string
 */
function _EXOWoo_generate_hash($data, $secret, $timestamp) {
	return md5($data['amount'] . $data['currency'] . $data['shop_order_id'] . 'woocommerce' . $secret . $timestamp);
}

/**
 * @param $id
 * @param $key
 * @return string
 */
function _EXOWoo_generate_redirect_url($id, $key) {
	return EXOWoo_endpoint() . "/pg-invoice/?" . http_build_query(
			array(
				'id' => $id,
				'key' => $key
			)
		);
}

/**
 * @param $data
 * @return string
 * @throws Exception
 */
function EXOWoo_create_redirect_url($data) {

	$public = EXOWoo_public_token();
	$secret = EXOWoo_private_token();

	$timestamp = time();
	$id = md5($timestamp);

	$hash = _EXOWoo_generate_hash($data, $secret, $timestamp);

	$params = array_merge(
		$data,
		array(
			'timestamp' => $timestamp,
		)
	);

	$body = array(
		'jsonrpc' => '2.0',
		'id' => $id,
		'method' => 'payment-gateway.order.create',
		'params' => $params
	);

	$request = array(
		'headers' => array(
			'Content-Type' => 'application/json',
			'Public-Key' => $public,
			'Signature' => $hash,
		),
		'body' => json_encode($body),
		'method' => 'POST',
	);

	// @TODO check settings
	// throw new Exception(__('ExoSwap gateways unavailable.', 'wc-ExoSwap'));

	$result = wp_remote_post(
		EXOWoo_api_endpoint(), $request
	);

	if (!key_exists('response', $result)
		|| !key_exists('code', $result['response'])
		|| $result['response']['code'] !== 200) {
		throw new Exception(
			key_exists('message', $result['response'])
				? $result['response']['message']
				: 'Something went wrong'
		);
	}

	if (key_exists('body', $result)) {

		$responseBody = json_decode($result['body'], true);

		if (key_exists('error', $responseBody)) {
			throw new Exception(
				key_exists('message', $responseBody['error'])
					? $responseBody['error']['message']
					: 'Something went wrong'
			);
		}

		if (key_exists('result', $responseBody) && key_exists('order_uuid', $responseBody['result'])) {
			return _EXOWoo_generate_redirect_url($responseBody['result']['order_uuid'], $public);
		}

	}

	throw new Exception(
		key_exists('message', $result['response'])
			? $result['response']['message']
			: 'Something went wrong'
	);

}
