<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://www.neowebsolution.com
 * @since      1.0.0
 *
 * @package    Raidiant_btc_woo
 * @subpackage Raidiant_btc_woo/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Raidiant_btc_woo
 * @subpackage Raidiant_btc_woo/admin
 * @author     Raidiant <info@neowebsolution.com>
 */
class Raidiant_btc_woo_Admin  extends WC_Payment_Gateway{

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;
       	$this->id = $this->plugin_name;
		$this->method_title = __('Bitcoin Cash','raidiant_btc_woo');
		$this->title = __('Bitcoin Cash','raidiant_btc_woo');
		$this->has_fields = true;
		$this->init_form_fields();
		$this->init_settings();
		$this->enabled = $this->get_option('enabled');
		$this->title = $this->get_option('title');
		$this->description = $this->get_option('description');
        ///this is used to update the form settings
        add_action('woocommerce_update_options_payment_gateways_'.$this->id, array($this, 'add_options_page'));
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Raidiant_btc_woo_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Raidiant_btc_woo_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/raidiant_btc_woo-admin.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Raidiant_btc_woo_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Raidiant_btc_woo_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/raidiant_btc_woo-admin.js', array( 'jquery' ), $this->version, false );

	}

   	public function init_form_fields(){
				$this->form_fields = array(
					'enabled' => array(
					'title' 		=> __( 'Enable/Disable', 'raidiant_btc_woo' ),
					'type' 			=> 'checkbox',
					'label' 		=> __( 'BTC', 'raidiant_btc_woo' ),
					'default' 		=> 'yes'
					),
					'title' => array(
						'title' 		=> __( 'Method Title', 'raidiant_btc_woo' ),
						'type' 			=> 'text',
						'description' 	=> __( 'This controls the title', 'raidiant_btc_woo' ),
						'default'		=> __( 'BTC', 'raidiant_btc_woo' ),
						'desc_tip'		=> true,
					),
					'description' => array(
						'title' => __( 'Customer Message', 'raidiant_btc_woo' ),
						'type' => 'textarea',
						'css' => 'width:500px;',
						'default' => 'Are you new to BTC need any help click on Red icon on sidebar',
						'description' 	=> __( 'The message which you want it to appear to the customer in the checkout page.', 'raidiant_btc_woo' ),
					),
					'hide_text_box' => array(
						'title' 		=> __( 'Hide The Payment Field', 'raidiant_btc_woo' ),
						'type' 			=> 'checkbox',
						'label' 		=> __( 'Hide', 'raidiant_btc_woo' ),
						'default' 		=> 'no',
						'description' 	=> __( 'If you do not need to show the text box for customers at all, enable this option.', 'raidiant_btc_woo' ),
					),

			 );
	}
	
	public function add_options_page(){

    include("partials/raidiant-settings.php");
   }

}
