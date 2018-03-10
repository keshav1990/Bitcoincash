<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://www.neowebsolution.com
 * @since      1.0.0
 *
 * @package    Raidiant_btc_woo
 * @subpackage Raidiant_btc_woo/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    Raidiant_btc_woo
 * @subpackage Raidiant_btc_woo/includes
 * @author     Raidiant <info@neowebsolution.com>
 */
class Raidiant_btc_woo{

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      Raidiant_btc_woo_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		if ( defined( 'PLUGIN_NAME_VERSION' ) ) {
			$this->version = PLUGIN_NAME_VERSION;
		} else {
			$this->version = '1.0.0';
		}
		$this->plugin_name = 'raidiant_btc_woo';
    add_action( 'plugins_loaded', 	   array($this,'make_woocommerce_data') );
	$this->load_dependencies();



	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - Raidiant_btc_woo_Loader. Orchestrates the hooks of the plugin.
	 * - Raidiant_btc_woo_i18n. Defines internationalization functionality.
	 * - Raidiant_btc_woo_Admin. Defines all hooks for the admin area.
	 * - Raidiant_btc_woo_Public. Defines all hooks for the public side of the site.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies() {

		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-raidiant_btc_woo-loader.php';

		/**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-raidiant_btc_woo-i18n.php';



// This loads necessary modules and selects best math library
require_once(plugin_dir_path( dirname( __FILE__ ) ) . 'includes/libs/util/bcmath_Utils.php');
#require_once(plugin_dir_path( dirname( __FILE__ ) ) . 'includes/libs/util/gmp_Utils.php');
require_once(plugin_dir_path( dirname( __FILE__ ) ) . 'includes/libs/CurveFp.php');
require_once(plugin_dir_path( dirname( __FILE__ ) ) . 'includes/libs/Point.php');
require_once(plugin_dir_path( dirname( __FILE__ ) ) . 'includes/libs/NumberTheory.php');
require_once(plugin_dir_path( dirname( __FILE__ ) ) . 'includes/libs/ElectrumHelper.php');
/*
require_once(dirname(__FILE__) . '/bwwc-cron.php');
require_once(dirname(__FILE__) . '/bwwc-mpkgen.php');
require_once(dirname(__FILE__) . '/bwwc-utils.php');
require_once(dirname(__FILE__) . '/bwwc-admin.php');
require_once(dirname(__FILE__) . '/bwwc-render-settings.php');
require_once(dirname(__FILE__) . '/bwwc-bitcoin-gateway.php');*/

//Load cashaddr libs
        require_once(plugin_dir_path( dirname( __FILE__ ) ) . 'includes/libs/cashaddr/Base32.php');
        require_once(plugin_dir_path( dirname( __FILE__ ) ) . 'includes/libs/cashaddr/CashAddress.php');
        require_once(plugin_dir_path( dirname( __FILE__ ) ) . 'includes/libs/cashaddr/Exception/Base32Exception.php');
        require_once(plugin_dir_path( dirname( __FILE__ ) ) . 'includes/libs/cashaddr/Exception/CashAddressException.php');
        require_once(plugin_dir_path( dirname( __FILE__ ) ) . 'includes/libs/cashaddr/Exception/InvalidChecksumException.php');
        require_once(plugin_dir_path( dirname( __FILE__ ) ) . 'includes/common-function_btc_woo-util.php');
		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */

		/**
		 * The class responsible for defining all actions that occur in the public-facing
		 * side of the site.
		 */
           	require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-raidiant_btc_woo-public.php';
		$this->loader = new Raidiant_btc_woo_Loader();
         	$this->set_locale();

		$this->define_public_hooks();
	}
    	/*
	 *	10. Add GoUrl gateway
	 */
	function raidiant_btc_woo_wc_gateway_add( $methods )
	{
		if (!in_array('Raidiant_btc_woo_Admin', $methods)) {
			$methods[] = 'Raidiant_btc_woo_Admin';
		}
		return $methods;
	}

      public function make_woocommerce_data(){
               	require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-raidiant_btc_woo-admin.php';
                add_filter( 'woocommerce_payment_gateways',array($this,'raidiant_btc_woo_wc_gateway_add') );
            	$this->define_admin_hooks();
      }
	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the Raidiant_btc_woo_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_locale() {

		$plugin_i18n = new Raidiant_btc_woo_i18n();

		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );

	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_admin_hooks() {

		$plugin_admin = new Raidiant_btc_woo_Admin( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );

	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_public_hooks() {

		$plugin_public = new Raidiant_btc_woo_Public( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_styles' );
		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_scripts' );

	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     1.0.0
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     1.0.0
	 * @return    Raidiant_btc_woo_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}

}
