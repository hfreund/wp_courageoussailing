<?php
/*
* Plugin Name: WooCommerce Product Bundles
* Plugin URI: http://woocommerce.com/products/product-bundles/
* Description: WooCommerce extension for creating simple product bundles, kits and assemblies.
* Version: 5.1.1
* Author: WooThemes
* Author URI: http://woocommerce.com/
* Developer: SomewhereWarm
* Developer URI: http://somewherewarm.net/
*
* Text Domain: woocommerce-product-bundles
* Domain Path: /languages/
*
* Requires at least: 4.1
* Tested up to: 4.7
*
* Copyright: © 2009-2015 Emmanouil Psychogyiopoulos.
* License: GNU General Public License v3.0
* License URI: http://www.gnu.org/licenses/gpl-3.0.html
*/

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/*
 * Required functions.
 */
if ( ! function_exists( 'woothemes_queue_update' ) ) {
	require_once( 'woo-includes/woo-functions.php' );
}

/*
 * Plugin updates.
 */
woothemes_queue_update( plugin_basename( __FILE__ ), 'fbca839929aaddc78797a5b511c14da9', '18716' );

/*
 * WC active check.
 */
if ( ! is_woocommerce_active() ) {
	return;
}

/**
 * Main plugin class.
 *
 * @class    WC_Bundles
 * @version  5.1.1
 */
class WC_Bundles {

	public $version  = '5.1.1';
	public $required = '2.4.0';

	/**
	 * The single instance of the class.
	 * @var WC_Bundles
	 *
	 * @since 4.11.4
	 */
	protected static $_instance = null;

	/**
	 * Main WC_Bundles instance. Ensures only one instance of WC_Bundles is loaded or can be loaded - @see 'WC_PB()'.
	 *
	 * @static
	 * @return WC_Bundles
	 * @since  4.11.4
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	/**
	 * Cloning is forbidden.
	 *
	 * @since 4.11.4
	 */
	public function __clone() {
		_doing_it_wrong( __FUNCTION__, __( 'Foul!', 'woocommerce-product-bundles' ), '4.11.4' );
	}

	/**
	 * Unserializing instances of this class is forbidden.
	 *
	 * @since 4.11.4
	 */
	public function __wakeup() {
		_doing_it_wrong( __FUNCTION__, __( 'Foul!', 'woocommerce-product-bundles' ), '4.11.4' );
	}

	/**
	 * Make stuff.
	 */
	protected function __construct() {
		// Entry point.
		add_action( 'plugins_loaded', array( $this, 'initialize_plugin' ), 9 );
	}

	/**
	 * Auto-load in-accessible properties.
	 *
	 * @param  mixed  $key
	 * @return mixed
	 */
	public function __get( $key ) {
		if ( in_array( $key, array( 'compatibility', 'cart', 'order', 'display' ) ) ) {
			$classname = 'WC_PB_' . ucfirst( $key );
			return call_user_func( array( $classname, 'instance' ) );
		}
	}

	/**
	 * Plugin URL getter.
	 *
	 * @return string
	 */
	public function plugin_url() {
		return untrailingslashit( plugins_url( '/', __FILE__ ) );
	}

	/**
	 * Plugin path getter.
	 *
	 * @return string
	 */
	public function plugin_path() {
		return untrailingslashit( plugin_dir_path( __FILE__ ) );
	}

	/**
	 * Plugin base path name getter.
	 *
	 * @return string
	 */
	public function plugin_basename() {
		return plugin_basename( __FILE__ );
	}

	/**
	 * Fire in the hole!
	 */
	public function initialize_plugin() {

		// WC version sanity check.
		if ( version_compare( WC()->version, $this->required ) < 0 ) {
			$notice = sprintf( __( 'WooCommerce Product Bundles requires at least WooCommerce %s in order to function. Please upgrade WooCommerce.', 'woocommerce-product-bundles' ), $this->required );
			require_once( 'includes/admin/class-wc-pb-admin-notices.php' );
			WC_PB_Admin_Notices::add_notice( $notice, 'error' );
			return false;
		}

		$this->define_constants();
		$this->includes();

		WC_PB_Compatibility::instance();
		WC_PB_Cart::instance();
		WC_PB_Order::instance();
		WC_PB_Display::instance();

		// Load translations hook.
		add_action( 'init', array( $this, 'load_translation' ) );
	}

	/**
	 * Constants.
	 */
	public function define_constants() {

		if ( 'yes' === get_option( 'woocommerce_product_bundles_debug_stock_cache', null ) ) {
			/**
			 * 'WC_PB_DEBUG_STOCK_CACHE' constant.
			 *
			 * Used to disable bundled product stock meta caching.
			 */
			define( 'WC_PB_DEBUG_STOCK_CACHE', true );
		}

		if ( 'yes' === get_option( 'woocommerce_product_bundles_debug_transients', null ) ) {
			/**
			 * 'WC_PB_DEBUG_TRANSIENTS' constant.
			 *
			 * Used to disable transients caching at plugin level.
			 */
			define( 'WC_PB_DEBUG_TRANSIENTS', true );
		}
	}

	/**
	 * Includes.
	 */
	public function includes() {

		// Core compatibility functions and hooks.
		require_once( 'includes/class-wc-pb-core-compatibility.php' );

		// Data classes.
		require_once( 'includes/data/class-wc-pb-data.php' );

		// Install.
		require_once( 'includes/class-wc-pb-install.php' );

		// Functions (incl deprecated).
		require_once( 'includes/wc-pb-functions.php' );
		require_once( 'includes/wc-pb-deprecated-functions.php' );

		// Helper functions and hooks.
		require_once( 'includes/class-wc-pb-helpers.php' );

		// Extenstions compatibility functions and hooks.
		require_once( 'includes/class-wc-pb-compatibility.php' );

		// Data syncing between products and bundled items.
		require_once( 'includes/class-wc-pb-db-sync.php' );

		// Product price filters and price-related functions.
		require_once( 'includes/class-wc-pb-product-prices.php' );

		// Bundled Item class.
		require_once( 'includes/class-wc-bundled-item.php' );

		// Product Bundle class.
		require_once( 'includes/class-wc-product-bundle.php' );

		// Stock mgr class.
		require_once( 'includes/class-wc-pb-stock-manager.php' );

		// Cart-related bundle functions and hooks.
		require_once( 'includes/class-wc-pb-cart.php' );

		// Order-related bundle functions and hooks.
		require_once( 'includes/class-wc-pb-order.php' );

		// Front-end filters and templates.
		require_once( 'includes/class-wc-pb-display.php' );

		// Front-end AJAX handlers.
		require_once( 'includes/class-wc-pb-ajax.php' );

		// REST API hooks.
		require_once( 'includes/class-wc-pb-rest-api.php' );

		// Admin includes.
		if ( is_admin() ) {
			$this->admin_includes();
		}
	}

	/**
	 * Admin & AJAX functions and hooks.
	 */
	public function admin_includes() {

		// Admin notices handling.
		require_once( 'includes/admin/class-wc-pb-admin-notices.php' );

		// Admin functions and hooks.
		require_once( 'includes/admin/class-wc-pb-admin.php' );
	}

	/**
	 * Load textdomain.
	 */
	public function load_translation() {
		load_plugin_textdomain( 'woocommerce-product-bundles', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
	}

	/*
	|--------------------------------------------------------------------------
	| Deprecated methods.
	|--------------------------------------------------------------------------
	*/

	public function woo_bundles_plugin_url() {
		_deprecated_function( __METHOD__ . '()', '5.0.0', __CLASS__ . '::plugin_url()' );
		return $this->plugin_url();
	}
	public function woo_bundles_plugin_path() {
		_deprecated_function( __METHOD__ . '()', '5.0.0', __CLASS__ . '::plugin_path()' );
		return $this->plugin_path();
	}
}

/**
 * Returns the main instance of WC_Bundles to prevent the need to use globals.
 *
 * @since  4.11.4
 * @return WC_Bundles
 */
function WC_PB() {
  return WC_Bundles::instance();
}

$GLOBALS[ 'woocommerce_bundles' ] = WC_PB();
