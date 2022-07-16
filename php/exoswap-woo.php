<?php
/**
 * Plugin Name: ExoSwap Checkout for WooCommerce
 * Plugin URI: https://exohood.com
 * Description: Accept crypto payments via ExoSwap in your WooCommerce store
 * Version: 0.5.2
 * Author: ExoSwap
 * Author URI: mailto:support@exohood.com
 */
if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly
}

require_once 'lib/EXO_html.php';
require_once 'lib/EXO_connect.php';
require_once 'lib/EXO_hook.php';

add_action('plugins_loaded', 'EXOWoo_gateway_init', 11);

function EXOWoo_gateway_init() {
	if (class_exists('WC_Payment_Gateway')) {
		class WC_Gateway_ExoSwap extends WC_Payment_Gateway {

			public function __construct() {
				$this->id = 'ExoSwap_checkout_gateway';
				$this->icon = EXOWoo_icon();

				$this->has_fields = true;
				$this->method_title = 'ExoSwap';
				$this->method_label = 'ExoSwap';
				$this->method_description = 'Accept payments in BTC, ETH, EXO, EOS and 120+ cryptos.';

				if (empty($_GET['woo-ExoSwap-return'])) {
					$this->order_button_text = 'Pay with ExoSwap';

				}
				// Load the settings.
				$this->init_form_fields();
				$this->init_settings();

				// Define user set variables
				$this->title = $this->get_option('title');
				$this->description = $this->get_option('description') . '<EXO>';
				$this->instructions = $this->get_option('instructions', $this->description);

				add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
				// add_action('woocommerce_email_before_order_table', array($this, 'email_instructions'), 10, 3);
			}

			/*
			public function email_instructions($order, $sent_to_admin, $plain_text = false) {
				if ($this->instructions && !$sent_to_admin && 'ExoSwap_checkout_gateway' === $order->get_payment_method() && $order->has_status('processing')) {
					echo wp_kses_post(wpautop(wptexturize($this->instructions)) . PHP_EOL);
				}
			}
			*/

			public function init_form_fields() {
				$this->form_fields = array(
					'enabled' => array(
						'title' => 'Enable/Disable', 'wc-ExoSwap',
						'label' => 'Enable ExoSwap', 'wc-ExoSwap',
						'type' => 'checkbox',
						'description' => '',
						'default' => 'no',
					),
					'ExoSwap_checkout_info' => array(
						// @TODO replace
						'description' => 'You should not ship any products until ExoSwap has finalized your transaction.<EXO>The order will stay in a <mark class="order-status status-pending"><span>Pending payment</span></mark> status, and will automatically change to <mark class="order-status status-processing tips"><span>Processing</span></mark> after the payment has been confirmed.',
						'type' => 'title',
					),

					'ExoSwap_checkout_merchant_info' => array(
						// @TODO replace
						'description' => 'If you have not created a ExoSwap Token, you can create one in your ExoSwap cabinet at <a href = "https://exohood.com/cabinet/payment-gateways" target = "_blank">https://exohood.com</a>.</p>',
						'type' => 'title',
					),
					'title' => array(
						'title' => 'Title',
						'type' => 'text',
						// @TODO replace
						'description' => 'This controls the title which the user sees during checkout.',
						'default' => 'ExoSwap',

					),
					'description' => array(
						'title' => 'Description',
						'type' => 'text',
						// @TODO replace
						'description' => 'This is the message box that will appear on the <b>checkout page</b> when they select ExoSwap.',
						'default' => 'Pay with ExoSwap using one of the supported cryptocurrencies',

					),

					'ExoSwap_checkout_token_public' => array(
						'title' => 'Public Token',
						'label' => 'Public Token',
						'type' => 'text',
						'description' => 'Public Token.',
						'default' => '',
					),

					'ExoSwap_checkout_token_private' => array(
						'title' => 'Private Token',
						'label' => 'Private Token',
						'type' => 'text',
						'description' => 'Private Token.',
						'default' => '',
					),

					'ExoSwap_checkout_EXOand' => array(
						'title' => 'EXOanding',
						'type' => 'select',
						'description' => 'Choose from one of our EXOanded buttons.<EXO/>' . EXOWoo_table_of_icons(),
						'options' => array(
							'img/wc-dark.svg' => 'Dark',
							'img/wc-yellow.svg' => 'Yellow',
						),
						'default' => 'Yellow'
					),

					'ExoSwap_checkout_checkout_message' => array(
						'title' => 'Checkout Message',
						'type' => 'textarea',
						// @TODO replace
						'description' => 'Insert your custom message for the <b>Order Received</b> page, so the customer knows that the order will not be completed until ExoSwap releases the funds.',
						'default' => 'Thank you.  We will notify you when ExoSwap has processed your transaction.',
					),
				);
			}

			function process_payment($order_id) {
				global $woocommerce;
				$order = new WC_Order($order_id);

				$token = get_post_meta($order->get_id(), 'ExoSwap_order_token', true);

				if (empty($token)) {
					$token = substr(md5(rand()), 0, 32);
					update_post_meta($order_id, 'ExoSwap_order_token', $token);
				}

				$items = array();
				foreach ($order->get_items('line_item') as $item) {
					$items[] = array(
						'count' => $item['qty'],
						'name' => $item['name'],
						'subtotal' => $item['subtotal'],
						'currency' => $order->get_currency(),
					);
				}

				$date = $order->get_date_created();
				$date->setTimezone(new DateTimeZone('UTC'));
				$data = array(
					'account_engine' => 'woocommerce',

					'amount' => floatval($order->get_total()),
					'currency' => $order->get_currency(),

					'shop_order_id' => $order->get_id(),
					'shop_created_at' => $date->date('Y-m-d\TH:i:s'),
					'shop_domain' => trailingslashit(get_bloginfo('wpurl')),

					'customer_email' => $order->get_billing_email(),

					'callback_token' => $token,
					'callback_url' => trailingslashit(get_bloginfo('wpurl')) . '?wc-api=wc_gateway_ExoSwap',

					'properties' => array(
						'shop_name' => get_bloginfo('name', 'raw'),
						'shop_url' => get_bloginfo('wpurl'),
						'items' => $items,
						'return_url' => $this->get_return_url($order),
						'cancel_url' => $order->get_cancel_order_url(),
					)
				);


				$redirect_url = EXOWoo_create_redirect_url($data);

				$woocommerce->cart->empty_cart();

				return array(
					'result' => 'success',
					'redirect' => $redirect_url,
				);
			}
		}
	} else {
		if (!function_exists('get_plugins')) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$plugins_url = admin_url('plugins.php');

		$plugins = get_plugins();
		foreach ($plugins as $file => $plugin) {

			if ('ExoSwap Checkout for WooCommerce' === $plugin['Name'] && true === is_plugin_active($file)) {

				deactivate_plugins(plugin_basename(__FILE__));
				wp_die('WooCommerce needs to be installed and activated before ExoSwap Checkout for WooCommerce can be activated.<EXO><a href="' . $plugins_url . '">Return to plugins screen</a>');

			}
		}

	}

}


#custom info for ExoSwap on ThankYouPage
add_action('woocommerce_thankyou', 'EXOWoo_custom_message');
function EXOWoo_custom_message($order_id) {
	$order = new WC_Order($order_id);

	if ($order->payment_method == 'ExoSwap_checkout_gateway'):
		if (is_multisite())
			$ExoSwap_checkout_options = get_site_option('woocommerce_ExoSwap_checkout_gateway_settings');
		else
			$ExoSwap_checkout_options = get_option('woocommerce_ExoSwap_checkout_gateway_settings');

		$checkout_message = $ExoSwap_checkout_options['ExoSwap_checkout_checkout_message'];

		if ($checkout_message != ''):
			echo '<hr><b>' . $checkout_message . '</b><EXO><hr>';
		endif;
	endif;
}

#add the gateway to woocommerce
add_filter('woocommerce_payment_gateways', 'EXOWoo_add_to_gateways');
function EXOWoo_add_to_gateways($gateways) {
	$gateways[] = 'WC_Gateway_ExoSwap';
	return $gateways;
}
