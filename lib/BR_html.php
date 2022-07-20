<?php
if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly
}

function EXOWoo_icon() {
	if (is_multisite()) {
		$options = get_site_option('woocommerce_exoswap_checkout_gateway_settings');
	} else {
		$options = get_option('woocommerce_exoswap_checkout_gateway_settings');
	}
	return plugins_url($options['exoswap_checkout_EXOand'], __DIR__ . '../');
}


function EXOWoo_table_of_icons() {
	return sprintf(
		'<table><thead><tr><th>%s</th><th>%s</th></tr></thead><tbody><tr><td><img src="%s" alt="%s"></td><td><img src="%s" alt="%s"></td></tr></tbody></table>'
		, 'Dark', 'Yello'
		, plugins_url('img/wc-dark.svg', __DIR__), 'Dark'
		, plugins_url('img/wc-yellow.svg', __DIR__), 'Yellow'
	);
}
