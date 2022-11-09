<?php
/**
 * Plugin Name:       Sale price as order discount for WooCommerce
 * Plugin URI:        https://wordpress.org/plugins/sale-discount-for-woocommerce/
 * Description:       Stores the regular price of products in the order data so that the customer sees the discount they received in email/account/invoice
 * Version:           1.1.1
 * Requires at least: 5.0
 * Requires PHP:      7.3
 * Author:            WP Overnight
 * Author URI:        https://wpovernight.com
 * License:           GPLv3
 * License URI:       http://www.gnu.org/licenses/gpl-3.0
 * Text Domain:       sale-discount-for-woocommerce
 * Domain Path:       /languages
 *
 * WC requires at least: 4.0
 * WC tested up to:      7.1
 */

defined( 'ABSPATH' ) || exit;

class WPO_WC_SPAD {

	/**
	 * The plugin version.
	 *
	 * @var string
	 */
	public $version = '1.1.1';

	/**
	 * Whether to recalculate checkout order totals (when an order contained a sale product).
	 *
	 * @var bool
	 */
	public $recalculate_checkout_totals = false;

	/**
	 * Contains load errors.
	 *
	 * @var array
	 */
	public $errors = [];

	/**
	 * Instance of singleton.
	 *
	 * @var WPO_WC_SPAD
	 */
	protected static $_instance = null;

	/**
	 * Main Plugin Instance
	 *
	 * Ensures only one instance of the plugin is loaded or can be loaded.
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	/**
	 * Constructor
	 */
	private function __construct() {
		$this->define( 'WPO_WC_SPAD_VERSION', $this->version );
		$this->define( 'WPO_WC_SPAD_MIN_PHP_VER', '7.0' );
		$this->define( 'WPO_WC_SPAD_MIN_WC_VER',  '4.0' );
		$this->define( 'WPO_WC_SPAD_MIN_WP_VER',  '5.0' );

		add_action( 'plugins_loaded', [ $this, 'load_textdomain' ], 8 );
		add_action( 'admin_notices', [ $this, 'admin_notices' ], 8 );
		add_action( 'plugins_loaded', [ $this, 'load' ], 8 );
	}

	/**
	 * Loads plugin.
	 */
	public function load() {
		if ( $this->check() ) {
			add_action( 'before_woocommerce_init', [ $this, 'declare_wc_hpos_compatibility' ] );
			add_filter( 'woocommerce_ajax_order_item', [ $this, 'ajax_order_item' ], 10, 4 );
			add_filter( 'woocommerce_checkout_create_order_line_item', [ $this, 'checkout_create_order_line_item' ], 10, 4 );
			add_filter( 'woocommerce_checkout_create_order', [ $this, 'maybe_recalculate_order' ], 10, 2 );
		}
	}

	/**
	 * Set the regular price as item subtotal when adding products in the backend.
	 *
	 * @param WC_Order_Item $item    Order item object.
	 * @param int           $item_id Item ID.
	 * @param WC_Order      $order   Order object (since WC3.7).
	 * @param WC_Product    $product Product object (since WC3.7).
	 *
	 * @return WC_Order_Item_Product
	 */
	public function ajax_order_item( $item, $item_id, $order = null, $product = null ) {
		if ( apply_filters( 'wpo_wc_sale_discount_enable_for_backend', true ) ) {
			$item = $this->set_regular_price_as_subtotal( $item, $order, current_action() );
		}
		return $item;
	}

	/**
	 * Set the regular price as item subtotal when adding products in the backend.
	 *
	 * @param WC_Order_Item $item          Order item object.
	 * @param string        $cart_item_key Cart item key.
	 * @param array         $values        Cart item data.
	 * @param WC_Order      $order         Order object.
	 *
	 * @return void
	 */
	public function checkout_create_order_line_item( $item, $cart_item_key, $values, $order ) {
		if ( apply_filters( 'wpo_wc_sale_discount_enable_for_checkout', true ) ) {
			$this->set_regular_price_as_subtotal( $item, $order, current_action() );
		}
	}

	/**
	 * Triggers recalculation of order totals after checkout the order
	 * contained a sale product and we made changes to the item subtotal
	 */
	public function maybe_recalculate_order( $order, $data ) {
		if( $this->recalculate_checkout_totals ) {
			$order->calculate_totals( $and_taxes = false );
		}
	}

	/**
	 * Unified function to set the regular price as item subtotal (and subtotal taxes for checkout)
	 *
	 * @param WC_Order_Item $item   Order item object.
	 * @param WC_Order      $order  Order object.
	 * @param string        $action The action hooked to this method.
	 *
	 * @return WC_Order_Item_Product
	 */
	public function set_regular_price_as_subtotal( $item, $order, $action ) {
		if ( empty( $order ) ) {
			return $item;
		}
		if ( is_callable( [ $item, 'get_product' ] ) ) {
			$product = $item->get_product();

			// bail if the product is not on sale
			if ( false === apply_filters( 'wpo_wc_sale_discount_apply_to_item', ( is_callable( [ $product, 'is_on_sale' ] ) && $product->is_on_sale() ), $item, $order ) ) {
				return $item;
			}

			// we only apply if the product has a regular price and a subtotal
			if ( is_callable( [ $product, 'get_regular_price' ] ) && is_callable( [ $item, 'get_subtotal' ] ) ) {
				// get regular price excluding tax
				$regular_price = wc_get_price_excluding_tax( $product, [
					'qty'   => $item->get_quantity(),
					'price' => $product->get_regular_price(),
				] );

				// set regular price as before-discount item price
				$item->set_subtotal( $regular_price );

				if ( $action == 'woocommerce_ajax_order_item' ) {
					// save updated item
					$item->save();
					// inject item back into the order (passed by reference)
					$order->add_item( $item );
				}
				if ( $action == 'woocommerce_checkout_create_order_line_item' ) {
					// set item tax
					$item_taxes = $item->get_taxes();
					if ( array_key_exists( 'subtotal', $item_taxes ) ) {
						$tax_rates = WC_Tax::get_rates( $item->get_tax_class() );
						$item_taxes['subtotal'] = WC_Tax::calc_tax( $item->get_subtotal(), $tax_rates );
						$item->set_taxes($item_taxes);
					}
					// we'll need to recalculate the totals to make sure the total discount is stored
					$this->recalculate_checkout_totals = true;
				}
			}
		}
		return $item;
	}


	/**
	 * Checks if the plugin should load.
	 *
	 * @return bool
	 */
	public function check() {
		$passed = true;

		$plugin_name = __( 'Sale price as order discount for WooCommerce', 'sale-discount-for-woocommerce' );
		/* translators: plugin name */
		$inactive_text = sprintf( __( '<strong>%s</strong> is <strong>inactive</strong>.', 'sale-discount-for-woocommerce' ), $plugin_name );

		if ( version_compare( phpversion(), WPO_WC_SPAD_MIN_PHP_VER, '<' ) ) {
			/* translators: min PHP version */
			$this->errors[] = $inactive_text . ' ' . sprintf( __( 'The plugin requires PHP version %s or newer.', 'sale-discount-for-woocommerce' ), WPO_WC_SPAD_MIN_PHP_VER );
			$passed         = false;
		} elseif ( ! $this->is_woocommerce_version_ok() ) {
			/* translators: min WooCommerce version */
			$this->errors[] = $inactive_text . ' ' . sprintf( __( 'The plugin requires WooCommerce version %s or newer.', 'sale-discount-for-woocommerce' ), WPO_WC_SPAD_MIN_WC_VER );
			$passed         = false;
		} elseif ( ! $this->is_wp_version_ok() ) {
			/* translators: min WordPress version */
			$this->errors[] = $inactive_text . ' ' . sprintf( __( 'The plugin requires WordPress version %s or newer.', 'sale-discount-for-woocommerce' ), WPO_WC_SPAD_MIN_WP_VER );
			$passed         = false;
		}

		return $passed;
	}

	/**
	 * Checks if the installed WooCommerce version is ok.
	 *
	 * @return bool
	 */
	public function is_woocommerce_version_ok() {
		if ( ! function_exists( 'WC' ) ) {
			return false;
		}
		if ( ! WPO_WC_SPAD_MIN_WC_VER ) {
			return true;
		}
		return version_compare( WC()->version, WPO_WC_SPAD_MIN_WC_VER, '>=' );
	}

	/**
	 * Declares compatibility with WooCommerce HPOS.
	 *
	 * @return void
	 */
	public function declare_wc_hpos_compatibility() {
		if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
		}
	}

	/**
	 * Checks if the installed WordPress version is ok.
	 *
	 * @return bool
	 */
	public function is_wp_version_ok() {
		global $wp_version;
		if ( ! WPO_WC_SPAD_MIN_WP_VER ) {
			return true;
		}
		return version_compare( $wp_version, WPO_WC_SPAD_MIN_WP_VER, '>=' );
	}

	/**
	* Load plugin textdomain.
	*
	* @return void
	*/
	public function load_textdomain() {
		load_plugin_textdomain( 'sale-discount-for-woocommerce', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
	}

	/**
	 * Displays any errors as admin notices.
	 */
	public function admin_notices() {
		if ( empty( $this->errors ) ) {
			return;
		}
		echo '<div class="notice notice-error"><p>';
		echo wp_kses_post( implode( '<br>', $this->errors ) );
		echo '</p></div>';
	}

	/**
	 * Define constant if not already set
	 * @param  string $name
	 * @param  string|bool $value
	 */
	private function define( $name, $value ) {
		if ( ! defined( $name ) ) {
			define( $name, $value );
		}
	}

	/**
	 * Get the plugin url.
	 * @return string
	 */
	public function plugin_url() {
		return untrailingslashit( plugins_url( '/', __FILE__ ) );
	}

	/**
	 * Get the plugin path.
	 * @return string
	 */
	public function plugin_path() {
		return untrailingslashit( plugin_dir_path( __FILE__ ) );
	}
}

/**
 * Access the plugin singleton with this.
 *
 * @return WPO_WC_SPAD
 */
function WPO_WC_SPAD() {
	return WPO_WC_SPAD::instance();
}

WPO_WC_SPAD();
