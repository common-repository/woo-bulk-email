<?php
/**
 * The plugin main file.
 *
 * @link              https://profiles.wordpress.org/jaydeep-rami/
 * @since             1.0.0
 * @package           Woo_Bulk_Email
 *
 * @wordpress-plugin
 * Plugin Name:       WooCommerce Bulk Email
 * Plugin URI:
 * Description:       WooCommerce Bulk Email plugin allowed an admin to send bulk confirmation email to the customers.
 * Version:           1.0.0
 * Author:            Jaydeep Rami
 * Author URI:        https://profiles.wordpress.org/jaydeep-rami/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       woo-bulk-email
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

if ( ! class_exists( 'Woo_Bulk_Email' ) ) :

	/**
	 * Woo_Bulk_Email Class
	 *
	 * @package Woo_Bulk_Email
	 * @since   1.0.0
	 */
	final class Woo_Bulk_Email {

		/**
		 * Holds the instance
		 *
		 * Ensures that only one instance of Woo_Bulk_Email exists in memory at any one
		 * time and it also prevents needing to define globals all over the place.
		 *
		 * TL;DR This is a static property property that holds the singleton instance.
		 *
		 * @var object
		 * @static
		 */
		private static $instance;

		/**
		 * Woo Bulk Email Admin Object.
		 *
		 * @since  1.0.0
		 * @access public
		 *
		 * @var    Woo_Bulk_Email_Admin object.
		 */
		public $plugin_admin;

		/**
		 * Get the instance and store the class inside it. This plugin utilises
		 * the PHP singleton design pattern.
		 *
		 * @since     1.0.0
		 * @static
		 * @staticvar array $instance
		 * @access    public
		 *
		 * @see       Woo_Bulk_Email();
		 *
		 * @uses      Woo_Bulk_Email::hooks() Setup hooks and actions.
		 * @uses      Woo_Bulk_Email::includes() Loads all the classes.
		 *
		 * @return object self::$instance Instance
		 */
		public static function get_instance() {

			if ( ! isset( self::$instance ) && ! ( self::$instance instanceof Woo_Bulk_Email ) ) {
				self::$instance = new Woo_Bulk_Email();
				self::$instance->setup();
			}

			return self::$instance;
		}

		/**
		 * Setup Woo Bulk Email.
		 *
		 * @since  1.0.0
		 * @access private
		 */
		private function setup() {
			self::$instance->setup_constants();

			add_action( 'woocommerce_init', array( $this, 'init' ), 10 );
			add_action( 'plugins_loaded', array( $this, 'check_environment' ), 999 );
		}

		/**
		 * Setup constants.
		 *
		 * @since   1.0.0
		 * @access  private
		 */
		private function setup_constants() {
			if ( ! defined( 'WOO_BULK_EMAIL_VERSION' ) ) {
				define( 'WOO_BULK_EMAIL_VERSION', '1.0.0' );
			}
			if ( ! defined( 'WOO_BULK_EMAIL_MIN_WOOCOMMERCE_VER' ) ) {
				define( 'WOO_BULK_EMAIL_MIN_WOOCOMMERCE_VER', '2.2.0' );
			}
			if ( ! defined( 'WOO_BULK_EMAIL_SLUG' ) ) {
				define( 'WOO_BULK_EMAIL_SLUG', 'woo-bulk-email' );
			}
			if ( ! defined( 'WOO_BULK_EMAIL_PLUGIN_FILE' ) ) {
				define( 'WOO_BULK_EMAIL_PLUGIN_FILE', __FILE__ );
			}
			if ( ! defined( 'WOO_BULK_EMAIL_PLUGIN_DIR' ) ) {
				define( 'WOO_BULK_EMAIL_PLUGIN_DIR', dirname( WOO_BULK_EMAIL_PLUGIN_FILE ) );
			}
			if ( ! defined( 'WOO_BULK_EMAIL_PLUGIN_URL' ) ) {
				define( 'WOO_BULK_EMAIL_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
			}
			if ( ! defined( 'WOO_BULK_EMAIL_BASENAME' ) ) {
				define( 'WOO_BULK_EMAIL_BASENAME', plugin_basename( __FILE__ ) );
			}
		}

		/**
		 * Init Woo Bulk Email.
		 *
		 * Sets up hooks, licensing and includes files.
		 *
		 * @since  1.0.0
		 * @access public
		 *
		 * @return void
		 */
		public function init() {

			if ( ! self::$instance->check_environment() ) {
				return;
			}

			self::$instance->hooks();
			self::$instance->includes();
		}

		/**
		 * Check plugin environment.
		 *
		 * @since  1.0.0
		 * @access public
		 *
		 * @return bool
		 */
		public function check_environment() {
			// Load plugin helper functions.
			if ( ! function_exists( 'deactivate_plugins' ) || ! function_exists( 'is_plugin_active' ) ) {
				require_once ABSPATH . '/wp-admin/includes/plugin.php';
			}

			// Flag to check whether deactivate plugin or not.
			$is_deactivate_plugin = false;

			// Verify dependency cases.
			switch ( true ) {
				case doing_action( 'woocommerce_init' ):
					if ( defined( 'WOOCOMMERCE_VERSION' ) && version_compare( WOOCOMMERCE_VERSION, WOO_BULK_EMAIL_MIN_WOOCOMMERCE_VER, '<' ) ) {
						/* Min. WooCommerce. plugin version. */

						// Show admin notice.
						$message = sprintf( __( '<strong>Activation Error:</strong> You must have <a href="%1$s" target="_blank">WooCommerce</a> core version %2$s+ for the WooCommerce Bulk Email add-on to activate.', 'woo-bulk-email' ), 'https://wordpress.org/plugins/woocommerce/', WOO_BULK_EMAIL_MIN_WOOCOMMERCE_VER );

						$class = 'notice notice-error';
						printf( '<div class="%1$s"><p>%2$s</p></div>', $class, $message );

						$is_deactivate_plugin = true;
					}

					break;

				case doing_action( 'plugins_loaded' ) && ! did_action( 'woocommerce_init' ):
					/* Check to see if WooCommerce is activated, if it isn't deactivate and show a banner. */

					// Check for if WooCommerce plugin activate or not.
					$is_woocommerce_active = defined( 'WC_PLUGIN_BASENAME' ) ? is_plugin_active( WC_PLUGIN_BASENAME ) : false;

					if ( ! $is_woocommerce_active ) {

						$message = sprintf( __( '<strong>Activation Error:</strong> You must have the <a href="%s" target="_blank">WooCommerce</a> plugin installed and activated for WooCommerce Bulk Email to activate.', 'woo-bulk-email' ), 'https://wordpress.org/plugins/woocommerce/' );

						$class = 'notice notice-error';
						printf( '<div class="%1$s"><p>%2$s</p></div>', $class, $message );

						$is_deactivate_plugin = true;
					}

					break;
			}// End switch().

			// Don't let this plugin activate.
			if ( $is_deactivate_plugin ) {

				// Deactivate plugin.
				deactivate_plugins( WOO_BULK_EMAIL_BASENAME );

				if ( isset( $_GET['activate'] ) ) {
					unset( $_GET['activate'] );
				}

				return false;
			}

			return true;
		}

		/**
		 * Throw error on object clone.
		 *
		 * The whole idea of the singleton design pattern is that there is a single
		 * object therefore, we don't want the object to be cloned.
		 *
		 * @since  1.0.0
		 * @access protected
		 *
		 * @return void
		 */
		public function __clone() {
			// Cloning instances of the class is forbidden.
			_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'woo-bulk-email' ), '1.0' );
		}

		/**
		 * Disable Unserialize of the class.
		 *
		 * @since  1.0.0
		 * @access protected
		 *
		 * @return void
		 */
		public function __wakeup() {
			// Unserialize instances of the class is forbidden.
			_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'woo-bulk-email' ), '1.0' );
		}

		/**
		 * Constructor Function.
		 *
		 * @since  1.0.0
		 * @access protected
		 */
		public function __construct() {
			self::$instance = $this;
		}

		/**
		 * Reset the instance of the class
		 *
		 * @since  1.0.0
		 * @access public
		 */
		public static function reset() {
			self::$instance = null;
		}

		/**
		 * Includes.
		 *
		 * @since  1.0.0
		 * @access private
		 */
		private function includes() {
			/**
			 * The class responsible for defining all actions that occur in the admin area.
			 */
			require_once( WOO_BULK_EMAIL_PLUGIN_DIR . '/admin/class-woo-bulk-email-admin.php' );

			self::$instance->plugin_admin = new Woo_Bulk_Email_Admin();

		}

		/**
		 * Hooks.
		 *
		 * @since  1.0.0
		 * @access private
		 */
		private function hooks() {
			add_action( 'init', array( $this, 'load_textdomain' ) );
		}

		/**
		 * Load Plugin Text Domain
		 *
		 * Looks for the plugin translation files in certain directories and loads
		 * them to allow the plugin to be localised
		 *
		 * @since  1.0.0
		 * @access public
		 *
		 * @return bool True on success, false on failure.
		 */
		public function load_textdomain() {
			// Traditional WordPress plugin locale filter.
			$locale = apply_filters( 'plugin_locale', get_locale(), 'woo-bulk-email' );
			$mofile = sprintf( '%1$s-%2$s.mo', 'woo-bulk-email', $locale );

			// Setup paths to current locale file.
			$mofile_local = trailingslashit( plugin_dir_path( __FILE__ ) . 'languages' ) . $mofile;

			if ( file_exists( $mofile_local ) ) {
				// Look in the /wp-content/plugins/woo-bulk-email/languages/ folder.
				load_textdomain( 'woo-bulk-email', $mofile_local );
			} else {
				// Load the default language files.
				load_plugin_textdomain( 'woo-bulk-email', false, trailingslashit( plugin_dir_path( __FILE__ ) . 'languages' ) );
			}

			return false;
		}
	} // End Woo_Bulk_Email Class.

endif;

/**
 * Loads a single instance of WooCommerce Bulk Email
 *
 * This follows the PHP singleton design pattern.
 *
 * Use this function like you would a global variable, except without needing
 * to declare the global.
 *
 * @example <?php $woo_bulk_email = woo_bulk_email(); ?>
 *
 * @since   1.0.0
 *
 * @see     Woo_Bulk_Email::get_instance()
 *
 * @return object Woo_Bulk_Email Returns an instance of the  class
 */
function woo_bulk_email() {
	return Woo_Bulk_Email::get_instance();
}

woo_bulk_email();
