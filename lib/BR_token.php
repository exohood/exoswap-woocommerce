<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

function EXOWoo_private_token() {
	if (is_multisite())
		$options = get_site_option('woocommerce_exoswap_checkout_gateway_settings');
	else
		$options = get_option('woocommerce_exoswap_checkout_gateway_settings');
	return $options['exoswap_checkout_token_private'];
}

function EXOWoo_public_token() {
	if (is_multisite())
		$options = get_site_option('woocommerce_exoswap_checkout_gateway_settings');
	else
		$options = get_option('woocommerce_exoswap_checkout_gateway_settings');
	return $options['exoswap_checkout_token_public'];
}
