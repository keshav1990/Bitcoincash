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

	private $radient_settings;

	static $radient_settingsStatic;



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

	public function __construct( $plugin_name='raidiant_btc_woo', $version='1.1' ) {



		$this->plugin_name = 'raidiant_btc_woo';

		$this->version = $version;

       	$this->id = $this->plugin_name;

		$this->method_title = __('Bitcoin Cash','raidiant_btc_woo');

		$this->title = __('Bitcoin Cash','raidiant_btc_woo');

		$this->has_fields = true;

		$this->init_form_fields();



		// load time variable setting

		$this->init_settings();



		// Turn these settings into variables we can use

		foreach ( $this->settings as $setting_key => $value ) {

			$this->radient_settings[$setting_key] = $value;

			$this->$setting_key = $value;

		}

        $this->radient_settings['funds_received_value_expires_in_mins'] = 60;

        $this->radient_settings['assigned_address_expires_in_mins'] = 60;

        $this->radient_settings['starting_index_for_new_btc_addresses'] = 2;

        $this->radient_settings['max_unusable_generated_addresses'] = 20;

        $this->radient_settings['max_blockchains_api_failures'] = 6;

        $this->radient_settings['blockchain_api_timeout_secs'] = 60;

        $this->radient_settings['reuse_expired_addresses'] = 0;

        self::$radient_settingsStatic   = $this->radient_settings;
        if(!has_action('woocommerce_thankyou_raidiant_btc_woo')){

        add_action('woocommerce_thankyou_raidiant_btc_woo', array($this, 'custom_thankyou_page')); // hooks into the thank you page after payment

        }
        add_filter('woocommerce_thankyou_order_received_text', array($this, 'isa_order_received_text'), 10, 2 );



		// further check of SSL if you want

	  //	add_action( 'admin_notices', array( $this,	'do_ssl_check' ) );



		// Save settings



        ///this is used to update the form settings

       // add_action('woocommerce_update_options_payment_gateways_'.$this->id, array($this, 'add_options_page'));

       if ( is_admin() ) {

        add_action('woocommerce_update_options_payment_gateways_'.$this->id, array($this, 'process_admin_options'));

        }

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



	/*

	 *	2.

	*/

	function radiant_wc_action_links($links, $file)

	{

		static $this_plugin;



		if (!class_exists('WC_Payment_Gateway')) return $links;



		if (false === isset($this_plugin) || true === empty($this_plugin)) {

			$this_plugin = plugin_basename(__FILE__);

		}



		if ($file == $this_plugin) {

			$settings_link = '<a href="'.admin_url('admin.php?page=wc-settings&tab=checkout&section=raidiant_btc_woo').'">'.__( 'Settings', 'raidiant_btc_woo' ).'</a>';

			array_unshift($links, $settings_link);



		}



		return $links;

	}

   	public function init_form_fields(){

				$this->form_fields = array(

					'enabled' => array(

					'title' 		=> __( 'Enable/Disable', 'raidiant_btc_woo' ),

					'type' 			=> 'checkbox',

					'label' 		=> __( 'BTC', 'raidiant_btc_woo' ),

					'default' 		=> 'no'

					),

					'title' => array(

						'title' 		=> __( 'Method Title', 'raidiant_btc_woo' ),

						'type' 			=> 'text',

						'description' 	=> __( 'This controls the title', 'raidiant_btc_woo' ),

						'default'		=> __( 'BTC', 'raidiant_btc_woo' ),

						'desc_tip'		=> true,

					),

					'service_provider' => array(

						'title' 		=> __( 'Service Provider', 'raidiant_btc_woo' ),

						'type' 			=> 'select',

						'description' 	=> __( 'Your own Electron Cash wallet for BTC cash', 'raidiant_btc_woo' ),

						'default'		=> 'electrum_wallet',

                        'options' => array(



                               'Select Service Provider'        => __( 'Select Service Provider', 'woocommerce' ),



                              'electrum_wallet'       => __( 'Your own Electron Cash wallet', 'woocommerce' )



                            ),

						'desc_tip'		=> true,

					),

					'electrum_mpk_saved' => array(

						'title' => __( 'Electron Cash Master Public Key (MPK)', 'raidiant_btc_woo' ),

						'type' => 'textarea',

						'css' => 'width:500px;',

						'default' => '',

						'description' 	=> __( '<ol> <li>

                  Launch Electron Cash wallet and get Master Public Key value from:

                  Wallet -> Master Public Key, or:

                  <br />older version of Electron Cash: Preferences -> Import/Export -> Master Public Key -> Show.

                </li>

                <li>

                  Copy long number string and paste it in this field.

                </li>

                <li>

                  Change "gap limit" value to bigger value (to make sure youll see the total balance on your wallet):

                  <br />Click on "Console" tab and run this command: <tt>wallet.storage.put(\'gap_limit\',100)</tt>

                </li>

                <li>

                  Restart Electron Cash wallet to activate new gap limit. You may do it later at any time - gap limit does not affect functionlity of your online store.

                  <br />If your online store receives lots of orders in bitcoin cash - you might need to set gap limit to even bigger value.

                </li></ol>

                ', 'raidiant_btc_woo' ),

					),

					'confs_num' => array(

						'title' 		=> __( 'Number of confirmations required before accepting payment', 'raidiant_btc_woo' ),

						'type' 			=> 'text',

						'description' 	=> __( '<p>After a transaction is broadcast to the Bitcoin Cash network, it may be included in a block that is published

              to the network. When that happens it is said that one <a href="https://en.bitcoin.it/wiki/Confirmation"><b>confirmation</b></a> has occurred for the transaction.

              With each subsequent block that is found, the number of confirmations is increased by one. To protect against double spending, a transaction should not be considered as confirmed until a certain number of blocks confirm, or verify that transaction.

              6 is considered very safe number of confirmations, although it takes longer to confirm.</p>', 'raidiant_btc_woo' ),

						'default'		=> 1,

						'desc_tip'		=> true,

					),	'exchange_rate_type' => array(

						'title' 		=> __( 'Bitcoin Cash Exchange rate calculation type', 'raidiant_btc_woo' ),

						'type' 			=> 'select',

						'description' 	=> __( '<p class="description">

              Weighted Average (recommended): <a href="http://en.wikipedia.org/wiki/Volume-weighted_average_price">weighted average</a> rates polled from a number of exchange services

              <br />Real time: the most recent transaction rates polled from a number of exchange services.

              <br />Most profitable: pick better exchange rate of all indicators (most favorable for merchant). Calculated as: MIN (Weighted Average, Real time)

            </p>', 'raidiant_btc_woo' ),

						'default'		=> 'vwap',

                        'options' => array(



                               'vwap'        => __( 'Weighted Average', 'woocommerce' ),

                               'realtime'        => __( 'Real Time', 'woocommerce' ),



                              'bestrate'       => __( 'Most profitable', 'woocommerce' )



                            ),

						'desc_tip'		=> true,

					),'exchange_multiplier' => array(

						'title' 		=> __( 'Exchange Multiplier', 'raidiant_btc_woo' ),

						'type' 			=> 'text',

						'description' 	=> __( '<ol><li>Extra multiplier to apply to convert store default currency to bitcoin cash price.

              <br />Example: 1.05 - will add extra 5% to the total price in bitcoin cash.

            May be useful to compensate for market volatility or for merchant\'s loss to fees when converting bitcoin cash to local currency,

              or to encourage customer to use bitcoin cash for purchases (by setting multiplier to < 1.00 values).</li></ol>', 'raidiant_btc_woo' ),

						'default'		=> __( '1', 'raidiant_btc_woo' ),

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







    public static function radient_woo_btc_get_settings(){

        return self::$radient_settingsStatic;

    }



        //-------------------------------------------------------------------

        /**

         * Process the payment and return the result

         *

         * @access public

         * @param int $order_id

         * @return array

         */

        public function process_payment($order_id)

        {

            //$bwwc_settings = radient_woo_btc_get_settings();

            $order = new WC_Order($order_id);



            // TODO: Implement CRM features within store admin dashboard

            $order_meta = array();

            $order_meta['bw_order'] = $order;

            $order_meta['bw_items'] = $order->get_items();

            $order_meta['bw_b_addr'] = $order->get_formatted_billing_address();

            $order_meta['bw_s_addr'] = $order->get_formatted_shipping_address();

            $order_meta['bw_b_email'] = $order->billing_email;

            $order_meta['bw_currency'] = $order->order_currency;

            $order_meta['bw_settings'] = $this->radient_settings;

            $order_meta['bw_store'] = plugins_url('', __FILE__);





            //-----------------------------------

            // Save bitcoin payment info together with the order.

            // Note: this code must be on top here, as other filters will be called from here and will use these values ...

            //

            // Calculate realtime bitcoin price (if exchange is necessary)



            $exchange_rate = radient_woo_btc_get_exchange_rate_per_bitcoin(get_woocommerce_currency(), 'getfirst');

            /// $exchange_rate = radient_woo_btc_get_exchange_rate_per_bitcoin (get_woocommerce_currency(), $this->exchange_rate_retrieval_method, $this->exchange_rate_type);

            if (!$exchange_rate) {

                $msg = 'ERROR: Cannot determine Bitcoin Cash exchange rate. Possible issues: store server does not allow outgoing connections, exchange rate servers are blocking incoming connections or down. ' .

                       'You may avoid that by setting store currency directly to Bitcoin Cash (BCH)';

                radient_woo_btc_log_event(__FILE__, __LINE__, $msg);

                exit('<h2 style="color:red;">' . $msg . '</h2>');

            }



            $order_total_in_btc   = ($order->get_total() / $exchange_rate);

            if (get_woocommerce_currency() != 'BTC') {

                // Apply exchange rate multiplier only for stores with non-bitcoin default currency.

                $order_total_in_btc = $order_total_in_btc;

            }



            $order_total_in_btc   = sprintf("%.8f", $order_total_in_btc);



            $bitcoins_address = false;

            $bch_cashaddr = false;



            $order_info =

            array(

                'order_meta'							=> $order_meta,

                'order_id'								=> $order_id,

                'order_total'			    	 	=> $order_total_in_btc,  // Order total in BTC

                'order_datetime'  				=> date('Y-m-d H:i:s T'),

                'requested_by_ip'					=> @$_SERVER['REMOTE_ADDR'],

                'requested_by_ua'					=> @$_SERVER['HTTP_USER_AGENT'],

                'requested_by_srv'				=> radient_woo_btc_base64_encode(serialize($_SERVER)),

                );



               /// print_r($this->service_provider);

            $ret_info_array = array();



            if ($this->service_provider == 'electrum_wallet') {

                // Generate bitcoin address for electron cash wallet provider.

                /*

            $ret_info_array = array (

               'result'                      => 'success', // OR 'error'

               'message'										 => '...',

               'host_reply_raw'              => '......',

               'generated_bitcoin_address'   => '18vzABPyVbbia8TDCKDtXJYXcoAFAPk2cj', // or false

               );

                */

               //$test =  radient_woo_btc_generate_new_bitcoin_address_for_electrum_wallet();

              // print_r($test);

              // exit;

                $ret_info_array = radient_woo_btc_get_bitcoin_address_for_payment__electrum(radient_woo_btc_get_next_available_mpk(), $order_info);

                $bitcoins_address = @$ret_info_array['generated_bitcoin_address'];

                $bch_cashaddr = @$ret_info_array['generated_bch_cashaddr'];

            }



            if (!$bitcoins_address) {

                $msg = "ERROR: cannot generate bitcoin cash address for the order: '" . @$ret_info_array['message'] . "'";

                radient_woo_btc_log_event(__FILE__, __LINE__, $msg);

                exit('<h2 style="color:red;">' . $msg . '</h2>');

            }



            radient_woo_btc_log_event(__FILE__, __LINE__, "     Generated unique bitcoin cash address: '{$bitcoins_address}' for order_id " . $order_id);



            update_post_meta(

             $order_id, 			// post id ($order_id)

             'order_total_in_btc', 	// meta key

             $order_total_in_btc 	// meta value. If array - will be auto-serialized

             );

            update_post_meta(

             $order_id, 			// post id ($order_id)

             'bitcoins_address',	// meta key

             $bitcoins_address 	// meta value. If array - will be auto-serialized

             );

            update_post_meta(

             $order_id, 			// post id ($order_id)

             'bch_cashaddr',	// meta key

             $bch_cashaddr 	// meta value. If array - will be auto-serialized

             );

            update_post_meta(

             $order_id, 			// post id ($order_id)

             'bitcoins_paid_total',	// meta key

             "0" 	// meta value. If array - will be auto-serialized

             );

            update_post_meta(

             $order_id, 			// post id ($order_id)

             'bitcoins_refunded',	// meta key

             "0" 	// meta value. If array - will be auto-serialized

             );

            update_post_meta(

             $order_id, 				// post id ($order_id)

             '_incoming_payments',	// meta key. Starts with '_' - hidden from UI.

             array()					// array (array('datetime'=>'', 'from_addr'=>'', 'amount'=>''),)

             );

            update_post_meta(

             $order_id, 				// post id ($order_id)

             '_payment_completed',	// meta key. Starts with '_' - hidden from UI.

             0					// array (array('datetime'=>'', 'from_addr'=>'', 'amount'=>''),)

             );

            //-----------------------------------





            // The bitcoin gateway does not take payment immediately, but it does need to change the orders status to on-hold

            // (so the store owner knows that bitcoin payment is pending).

            // We also need to tell WooCommerce that it needs to redirect to the thankyou page – this is done with the returned array

            // and the result being a success.

            //

            global $woocommerce;



            //	Updating the order status:



            // Mark as on-hold (we're awaiting for bitcoins payment to arrive)

            $order->update_status('on-hold', __('Awaiting bitcoin cash payment to arrive', 'woocommerce'));



            // Remove cart

            $woocommerce->cart->empty_cart();



            // Empty awaiting payment session

            unset($_SESSION['order_awaiting_payment']);



            // Return thankyou redirect

            if (version_compare(WOOCOMMERCE_VERSION, '2.1', '<')) {

                return array(

                    'result' 	=> 'success',

                    'redirect'	=> add_query_arg('key', $order->order_key, add_query_arg('order', $order_id, get_permalink(woocommerce_get_page_id('thanks'))))

                );

            } else {

                return array(

                        'result' 	=> 'success',

                        'redirect'	=> add_query_arg('key', $order->order_key, add_query_arg('order', $order_id, $this->get_return_url($order)))

                    );

            }

        }











    public function payment_fields(){







    if($this->hide_text_box !== 'yes'){

	    ?>



		<fieldset>

			<p class="form-row form-row-wide">

				<label for="<?php echo $this->id; ?>-admin-note"><?php echo esc_attr($this->description); ?> <span class="required">*</span></label>

				<textarea id="<?php echo $this->id; ?>-admin-note" class="input-text" type="text" name="<?php echo $this->id; ?>-admin-note"></textarea>

			</p>

			<div class="clear"></div>

		</fieldset>

		<?php

		}

	}

         /**
 * Custom text on the receipt page.
 */
function isa_order_received_text( $text, $order ) {
$orderId = $order->id;
 if(( get_post_meta($order->id, '_payment_method', true) != 'raidiant_btc_woo' )) {
        return;
       }
 return __('Please send your Bitcoin Cash  payment as follows:','woocommerce') ;
}


   public function custom_thankyou_page($order_id){

       $order = new WC_Order($order_id);
       //echo get_post_meta($order->id, '_payment_method', true) ;
   //exit;
    if(( get_post_meta($order->id, '_payment_method', true) != 'raidiant_btc_woo' )) {
        return;
       }

           //     exit;

       ob_start();

       include(radient_path."admin/partials/raidiant-settings.php");

       $instructions = ob_get_clean();
         //    echo $instructions;
            // Assemble detailed instructions.

            $order_total_in_btc   = get_post_meta($order->id, 'order_total_in_btc', true); // set single to true to receive properly unserialized array

            $bitcoins_address = get_post_meta($order->id, 'bitcoins_address', true); // set single to true to receive properly unserialized array

            $bch_cashaddr = get_post_meta($order->id, 'bch_cashaddr', true); // set single to true to receive properly unserialized array





            $instructions = $instructions;

            $instructions = str_replace('[[value]]', $order_total_in_btc, $instructions);

            $instructions = str_replace('{{{BITCOINS_ADDRESS}}}', $bitcoins_address, $instructions);

            $instructions = str_replace('[[address]]', $bch_cashaddr, $instructions);

            $instructions = str_replace('[[address_safe]]', urlencode($bch_cashaddr), $instructions);

            $instructions =

                str_replace(

                    '{{{EXTRA_INSTRUCTIONS}}}',



                    $this->instructions_multi_payment_str,

                    $instructions

                    );

            $order->add_order_note(__("Order instructions: price=&#3647;{$order_total_in_btc}, incoming account:{$bitcoins_address}, cashddr: {$bch_cashaddr}", 'woocommerce'));



            echo wpautop(wptexturize($instructions));

   }

	public function add_options_page(){



    include("partials/raidiant-settings.php");

   }



}

