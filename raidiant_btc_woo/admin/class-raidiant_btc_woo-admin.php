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
			$this->$setting_key = $value;
		}

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
            //$bwwc_settings = BWWC__get_settings();
            $order = new WC_Order($order_id);

            // TODO: Implement CRM features within store admin dashboard
            $order_meta = array();
            $order_meta['bw_order'] = $order;
            $order_meta['bw_items'] = $order->get_items();
            $order_meta['bw_b_addr'] = $order->get_formatted_billing_address();
            $order_meta['bw_s_addr'] = $order->get_formatted_shipping_address();
            $order_meta['bw_b_email'] = $order->billing_email;
            $order_meta['bw_currency'] = $order->order_currency;
            $order_meta['bw_settings'] = $bwwc_settings;
            $order_meta['bw_store'] = plugins_url('', __FILE__);


            //-----------------------------------
            // Save bitcoin payment info together with the order.
            // Note: this code must be on top here, as other filters will be called from here and will use these values ...
            //
            // Calculate realtime bitcoin price (if exchange is necessary)

            $exchange_rate = BWWC__get_exchange_rate_per_bitcoin(get_woocommerce_currency(), 'getfirst');
            /// $exchange_rate = BWWC__get_exchange_rate_per_bitcoin (get_woocommerce_currency(), $this->exchange_rate_retrieval_method, $this->exchange_rate_type);
            if (!$exchange_rate) {
                $msg = 'ERROR: Cannot determine Bitcoin Cash exchange rate. Possible issues: store server does not allow outgoing connections, exchange rate servers are blocking incoming connections or down. ' .
                       'You may avoid that by setting store currency directly to Bitcoin Cash (BCH)';
                BWWC__log_event(__FILE__, __LINE__, $msg);
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
                'requested_by_srv'				=> BWWC__base64_encode(serialize($_SERVER)),
                );

            $ret_info_array = array();

            if ($this->service_provider == 'blockchain_info') {
                $bitcoin_addr_merchant = $this->bitcoin_addr_merchant;
                $secret_key = substr(md5(microtime()), 0, 16);	# Generate secret key to be validate upon receiving IPN callback to prevent spoofing.
                $callback_url = trailingslashit(home_url()) . "?wc-api=BWWC_Bitcoin&secret_key={$secret_key}&bitcoinway=1&src=bcinfo&order_id={$order_id}"; // http://www.example.com/?bitcoinway=1&order_id=74&src=bcinfo
            BWWC__log_event(__FILE__, __LINE__, "Calling BWWC__generate_temporary_bitcoin_address__blockchain_info(). Payments to be forwarded to: '{$bitcoin_addr_merchant}' with callback URL: '{$callback_url}' ...");

                // This function generates temporary bitcoin address and schedules IPN callback at the same
                $ret_info_array = BWWC__generate_temporary_bitcoin_address__blockchain_info($bitcoin_addr_merchant, $callback_url);

                /*
            $ret_info_array = array (
               'result'                      => 'success', // OR 'error'
               'message'										 => '...',
               'host_reply_raw'              => '......',
               'generated_bitcoin_address'   => '18vzABPyVbbia8TDCKDtXJYXcoAFAPk2cj', // or false
               );
                */
                $bitcoins_address = @$ret_info_array['generated_bitcoin_address'];
            } elseif ($this->service_provider == 'electrum_wallet') {
                // Generate bitcoin address for electron cash wallet provider.
                /*
            $ret_info_array = array (
               'result'                      => 'success', // OR 'error'
               'message'										 => '...',
               'host_reply_raw'              => '......',
               'generated_bitcoin_address'   => '18vzABPyVbbia8TDCKDtXJYXcoAFAPk2cj', // or false
               );
                */
                $ret_info_array = BWWC__get_bitcoin_address_for_payment__electrum(BWWC__get_next_available_mpk(), $order_info);
                $bitcoins_address = @$ret_info_array['generated_bitcoin_address'];
                $bch_cashaddr = @$ret_info_array['generated_bch_cashaddr'];
            }

            if (!$bitcoins_address) {
                $msg = "ERROR: cannot generate bitcoin cash address for the order: '" . @$ret_info_array['message'] . "'";
                BWWC__log_event(__FILE__, __LINE__, $msg);
                exit('<h2 style="color:red;">' . $msg . '</h2>');
            }

            BWWC__log_event(__FILE__, __LINE__, "     Generated unique bitcoin cash address: '{$bitcoins_address}' for order_id " . $order_id);

            if ($this->service_provider == 'blockchain_info') {
                update_post_meta(
                 $order_id, 			// post id ($order_id)
                 'secret_key', 	// meta key
                 $secret_key 		// meta value. If array - will be auto-serialized
                 );
            }

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

            /*
                        ///////////////////////////////////////
                        // timbowhite's suggestion:
                        // -----------------------
                        // Mark as pending (we're awaiting for bitcoins payment to arrive), not 'on-hold' since
                  // woocommerce does not automatically cancel expired on-hold orders. Woocommerce handles holding the stock
                  // for pending orders until order payment is complete.
                        $order->update_status('pending', __('Awaiting bitcoin payment to arrive', 'woocommerce'));

                        // Me: 'pending' does not trigger "Thank you" page and neither email sending. Not sure why.
                        //			Also - I think cancellation of unpaid orders needs to be initiated from cron job, as only we know when order needs to be cancelled,
                        //			by scanning "on-hold" orders through 'assigned_address_expires_in_mins' timeout check.
                        ///////////////////////////////////////
            */
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


	public function add_options_page(){

    include("partials/raidiant-settings.php");
   }

}
