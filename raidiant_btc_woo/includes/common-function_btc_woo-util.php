<?php
define('USE_EXT', 'GMP') ;
//===========================================================================
function radient_woo_btc_MATH_generate_bitcoin_address_from_mpk_v1($master_public_key, $key_index) {
  return ElectrumHelper::mpk_to_bc_address($master_public_key, $key_index, ElectrumHelper::V1) ;
}
//===========================================================================
//===========================================================================
function radient_woo_btc_MATH_generate_bitcoin_address_from_mpk_v2($master_public_key, $key_index, $is_for_change = false) {
  return ElectrumHelper::mpk_to_bc_address($master_public_key, $key_index, ElectrumHelper::V2, $is_for_change) ;
}
//===========================================================================
//===========================================================================
function radient_woo_btc_MATH_generate_bitcoin_address_from_mpk($master_public_key, $key_index, $is_for_change = false) {
  if (USE_EXT != 'GMP' && USE_EXT != 'BCMATH') {
    radient_woo_btc_log_event(__FILE__, __LINE__, "Neither GMP nor BCMATH PHP math libraries are present. Aborting.") ;
    return false ;
  }
  if (preg_match('/^[a-f0-9]{128}$/', $master_public_key)) {
//return radient_woo_btc_MATH_generate_bitcoin_address_from_mpk_v1($master_public_key, $key_index);
    return false ;
// TODO remove mpkv1
  }
  if (preg_match('/^xpub[a-zA-Z0-9]{107}$/', $master_public_key)) {
    return radient_woo_btc_MATH_generate_bitcoin_address_from_mpk_v2($master_public_key, $key_index, $is_for_change) ;
  }
  radient_woo_btc_log_event(__FILE__, __LINE__, "Invalid MPK passed: '$master_public_key'. Aborting.") ;
  return false ;
}
//===========================================================================
function radient_woo_btc_get_bitcoin_address_for_payment__electrum($electrum_mpk, $order_info) {
  global $wpdb ;
// status = "unused", "assigned", "used"
  $btc_addresses_table_name = $wpdb->prefix . 'radient_woo_btc_addresses' ;
  $origin_id = $electrum_mpk ;
  $bwwc_settings = radient_woo_btc_get_settings() ;
  $funds_received_value_expires_in_secs = $bwwc_settings['funds_received_value_expires_in_mins'] * 60 ;
  $assigned_address_expires_in_secs = $bwwc_settings['assigned_address_expires_in_mins'] * 60 ;
  $clean_address = null ;
  $bch_cashaddr = null ;
  $current_time = time() ;
  if ($bwwc_settings['reuse_expired_addresses']) {
    $reuse_expired_addresses_freshb_query_part = "OR (`status`='assigned'

      		AND (('$current_time' - `assigned_at`) > '$assigned_address_expires_in_secs')

      		AND (('$current_time' - `received_funds_checked_at`) < '$funds_received_value_expires_in_secs')

      		)" ;
  }
  else {
    $reuse_expired_addresses_freshb_query_part = "" ;
  }
//-------------------------------------------------------
// Quick scan for ready-to-use address
// NULL == not found
// Retrieve:
//     'unused'   - with fresh zero balances
//     'assigned' - expired, with fresh zero balances (if 'reuse_expired_addresses' is true)
//
// Hence - any returned address will be clean to use.
  $query = "SELECT `btc_address`, `bch_cashaddr` FROM `$btc_addresses_table_name`

         WHERE `origin_id`='$origin_id'

         AND `total_received_funds`='0'

         AND (`status`='unused' $reuse_expired_addresses_freshb_query_part)

         ORDER BY `index_in_wallet` ASC

         LIMIT 1;" ; // Try to use lower indexes first
  $clean_address = $wpdb->get_var($query, 0, 0) ;
  $bch_cashaddr = $wpdb->get_var(null, 1, 0) ;
//-------------------------------------------------------
  if (!$clean_address) {
//-------------------------------------------------------
// Find all unused addresses belonging to this mpk with possibly (to be verified right after) zero balances
// Array(rows) or NULL
// Retrieve:
//    'unused'    - with old zero balances
//    'unknown'   - ALL
//    'assigned'  - expired with old zero balances (if 'reuse_expired_addresses' is true)
//
// Hence - any returned address with freshened balance==0 will be clean to use.
    if ($bwwc_settings['reuse_expired_addresses']) {
      $reuse_expired_addresses_oldb_query_part = "OR (`status`='assigned'

          		AND (('$current_time' - `assigned_at`) > '$assigned_address_expires_in_secs')

          		AND (('$current_time' - `received_funds_checked_at`) > '$funds_received_value_expires_in_secs')

          		)" ;
    }
    else {
      $reuse_expired_addresses_oldb_query_part = "" ;
    }
    $query = "SELECT * FROM `$btc_addresses_table_name`

            WHERE `origin_id`='$origin_id'

             	AND `total_received_funds`='0'

            AND (

               `status`='unused'

               OR `status`='unknown'

               $reuse_expired_addresses_oldb_query_part

               )

            ORDER BY `index_in_wallet` ASC;" ; // Try to use lower indexes first
    $addresses_to_verify_for_zero_balances_rows = $wpdb->get_results($query, ARRAY_A) ;
    if (!is_array($addresses_to_verify_for_zero_balances_rows)) {
      $addresses_to_verify_for_zero_balances_rows = array() ;
    }
//-------------------------------------------------------
//-------------------------------------------------------
// Try to re-verify balances of existing addresses (with old or non-existing balances) before reverting to slow operation of generating new address.
//
    $blockchains_api_failures = 0 ;
    foreach ($addresses_to_verify_for_zero_balances_rows as $address_to_verify_for_zero_balance_row) {
// http://blockexplorer.com/q/getreceivedbyaddress/18vzABPyVbbia8TDCKDtXJYXcoAFAPk2cj
// http://blockchain.info/q/getreceivedbyaddress/18vzABPyVbbia8TDCKDtXJYXcoAFAPk2cj [?confirmations=6]
//
      $address_to_verify_for_zero_balance = $address_to_verify_for_zero_balance_row['btc_address'];
      $bch_cashaddr_zero_balance = $address_to_verify_for_zero_balance_row['bch_cashaddr'];
      $address_request_array = array() ;
      $address_request_array['btc_address'] = $address_to_verify_for_zero_balance ;
      $address_request_array['required_confirmations'] = 0 ;
      $address_request_array['api_timeout'] = $bwwc_settings['blockchain_api_timeout_secs'];
      $ret_info_array = radient_woo_btc_getreceivedbyaddress_info($address_request_array, $bwwc_settings) ;
      if ($ret_info_array['balance'] === false) {
        $blockchains_api_failures++;
        if ($blockchains_api_failures >= $bwwc_settings['max_blockchains_api_failures']) {
// Allow no more than 3 contigious blockchains API failures. After which return error reply.
          $ret_info_array = array('result' => 'error', 'message' => $ret_info_array['message'], 'host_reply_raw' => $ret_info_array['host_reply_raw'], 'generated_bitcoin_address' => false, 'generated_bch_cashaddr' => false,) ;
          return $ret_info_array ;
        }
      }
      else {
        if ($ret_info_array['balance'] == 0) {
// Update DB with balance and timestamp, mark address as 'assigned' and return this address as clean.
          $clean_address = $address_to_verify_for_zero_balance ;
          $bch_cashaddr = $bch_cashaddr_zero_balance ;
          break ;
        }
        else {
// Balance at this address suddenly became non-zero!
// It means either order was paid after expiration or "unknown" address suddenly showed up with non-zero balance or payment was sent to this address outside of this online store business.
// Mark it as 'revalidate' so cron job would check if that's possible delayed payment.
//
          $address_meta = BWWC_unserialize_address_meta(@ $address_to_verify_for_zero_balance_row['address_meta']) ;
          if (isset($address_meta['orders'][0])) {
            $new_status = 'revalidate' ;
          } // Past orders are present. There is a chance (for cron job) to match this payment to past (albeit expired) order.
          else {
            $new_status = 'used' ;
          } // No orders were ever placed to this address. Likely payment was sent to this address outside of this online store business.
          $current_time = time() ;
          $query = "UPDATE `$btc_addresses_table_name`

                     SET

                        `status`='$new_status',

                        `total_received_funds` = '{$ret_info_array['balance']}',

                        `received_funds_checked_at`='$current_time'

                    WHERE `btc_address`='$address_to_verify_for_zero_balance';" ;
          $ret_code = $wpdb->query($query) ;
        }
      }
    }
//-------------------------------------------------------
  }
//-------------------------------------------------------
  if (!$clean_address) {
// Still could not find unused virgin address. Time to generate it from scratch.
/*

Returns:

$ret_info_array = array (

'result'                      => 'success', // 'error'

'message'                     => '', // Failed to find/generate bitcoin address',

'host_reply_raw'              => '', // Error. No host reply availabe.',

'generated_bitcoin_address'   => '1FVai2j2FsFvCbgsy22ZbSMfUd3HLUHvKx', // false,

);

*/
    $ret_addr_array = radient_woo_btc_generate_new_bitcoin_address_for_electrum_wallet($bwwc_settings, $electrum_mpk) ;
    if ($ret_addr_array['result'] == 'success') {
      $clean_address = $ret_addr_array['generated_bitcoin_address'];
      $bch_cashaddr = $ret_addr_array['generated_bch_cashaddr'];
    }
  }
//-------------------------------------------------------
//-------------------------------------------------------
  if ($clean_address) {
/*

$order_info =

array (

'order_id'     => $order_id,

'order_total'  => $order_total_in_btc,

'order_datetime'  => date('Y-m-d H:i:s T'),

'requested_by_ip' => @$_SERVER['REMOTE_ADDR'],

);



*/
/*

$address_meta =

array (

'orders' =>

array (

// All orders placed on this address in reverse chronological order

array (

'order_id'     => $order_id,

'order_total'  => $order_total_in_btc,

'order_datetime'  => date('Y-m-d H:i:s T'),

'requested_by_ip' => @$_SERVER['REMOTE_ADDR'],

),

array (

...

),

),

'other_meta_info' => array (...)

);

*/
// Prepare `address_meta` field for this clean address.
    $address_meta = $wpdb->get_var("SELECT `address_meta` FROM `$btc_addresses_table_name` WHERE `btc_address`='$clean_address'") ;
    $address_meta = BWWC_unserialize_address_meta($address_meta) ;
    if (!isset($address_meta['orders']) || !is_array($address_meta['orders'])) {
      $address_meta['orders'] = array() ;
    }
    array_unshift($address_meta['orders'], $order_info) ; // Prepend new order to array of orders
    if (count($address_meta['orders']) > 10) {
      array_pop($address_meta['orders']) ;
    } // Do not keep history of more than 10 unfullfilled orders per address.
    $address_meta_serialized = BWWC_serialize_address_meta($address_meta) ;
// Update DB with balance and timestamp, mark address as 'assigned' and return this address as clean.
//
    $current_time = time() ;
    $remote_addr = $order_info['requested_by_ip'];
    $query = "UPDATE `$btc_addresses_table_name`

         SET

            `total_received_funds` = '0',

            `received_funds_checked_at`='$current_time',

            `status`='assigned',

            `assigned_at`='$current_time',

            `last_assigned_to_ip`='$remote_addr',

            `address_meta`='$address_meta_serialized'

        WHERE `btc_address`='$clean_address';" ;
    $ret_code = $wpdb->query($query) ;
    $ret_info_array = array('result' => 'success', 'message' => "", 'host_reply_raw' => "", 'generated_bitcoin_address' => $clean_address, 'generated_bch_cashaddr' => $bch_cashaddr,) ;
    return $ret_info_array ;
  }
//-------------------------------------------------------
  $ret_info_array = array('result' => 'error', 'message' => 'Failed to find/generate bitcoin cash address. ' . $ret_addr_array['message'], 'host_reply_raw' => $ret_addr_array['host_reply_raw'], 'generated_bitcoin_address' => false, 'generated_bch_cashaddr' => false,) ;
  return $ret_info_array ;
}
//===========================================================================
//===========================================================================
// To accomodate for multiple MPK's and allowed key limits per MPK
function radient_woo_btc_get_next_available_mpk($bwwc_settings = false) {
//global $wpdb;
//$btc_addresses_table_name = $wpdb->prefix . 'radient_woo_btc_addresses';
// Scan DB for MPK which has number of in-use keys less than alowed limit
// ...
  if (!$bwwc_settings) {
    $bwwc_settings = radient_woo_btc_get_settings() ;
  }
//  print_r($bwwc_settings);
//exit;
  return @ $bwwc_settings['electrum_mpk_saved'];
}
//===========================================================================
//===========================================================================
/*

Returns:

$ret_info_array = array (

'result'                      => 'success', // 'error'

'message'                     => '', // Failed to find/generate bitcoin cash address',

'host_reply_raw'              => '', // Error. No host reply availabe.',

'generated_bitcoin_address'   => '18vzABPyVbbia8TDCKDtXJYXcoAFAPk2cj', // false,

);

*/
// If $bwwc_settings or $electrum_mpk are missing - the best attempt will be made to manifest them.
// For performance reasons it is better to pass in these vars. if available.
//
function radient_woo_btc_generate_new_bitcoin_address_for_electrum_wallet($bwwc_settings = false, $electrum_mpk = false) {
  global $wpdb ;
  $btc_addresses_table_name = $wpdb->prefix . 'radient_woo_btc_addresses' ;
  if (!$bwwc_settings) {
    $bwwc_settings = radient_woo_btc_get_settings() ;
  }
  if (!$electrum_mpk) {
// Try to retrieve it from copy of settings.
    $electrum_mpk = radient_woo_btc_get_next_available_mpk() ;
    if (!$electrum_mpk || @ $bwwc_settings['service_provider'] != 'electrum_wallet') {
// Bitcoin cash gateway settings either were not saved
      $ret_info_array = array('result' => 'error', 'message' => 'No MPK passed and either no MPK present in copy-settings or service provider is not Electron Cash', 'host_reply_raw' => '', 'generated_bitcoin_address' => false, 'generated_bch_cashaddr' => false,) ;
      return $ret_info_array ;
    }
  }
// echo $electrum_mpk;
  $origin_id = $electrum_mpk ;
  $funds_received_value_expires_in_secs = $bwwc_settings['funds_received_value_expires_in_mins'] * 60 ;
  $assigned_address_expires_in_secs = $bwwc_settings['assigned_address_expires_in_mins'] * 60 ;
  $clean_address = false ;
  $new_bch_cashaddr = false ;
// Find next index to generate
  $next_key_index = $wpdb->get_var("SELECT MAX(`index_in_wallet`) AS `max_index_in_wallet` FROM `$btc_addresses_table_name` WHERE `origin_id`='$origin_id';") ;
  if ($next_key_index === null) {
    $next_key_index = $bwwc_settings['starting_index_for_new_btc_addresses'];
  } // Start generation of addresses from index #2 (skip two leading wallet's addresses)
  else {
    $next_key_index = $next_key_index + 1 ;
  } // Continue with next index
  $total_new_keys_generated = 0 ;
  $blockchains_api_failures = 0 ;
  do {
    $addresses = radient_woo_btc_MATH_generate_bitcoin_address_from_mpk($electrum_mpk, $next_key_index) ;
    $new_btc_address = $addresses["btc_address"];
    $new_bch_cashaddr = $addresses["bch_cashaddr"];
    $address_request_array = array() ;
    $address_request_array['btc_address'] = $new_btc_address ;
    $address_request_array['required_confirmations'] = 0 ;
    $address_request_array['api_timeout'] = $bwwc_settings['blockchain_api_timeout_secs'];
    $ret_info_array = radient_woo_btc_getreceivedbyaddress_info($address_request_array, $bwwc_settings) ;
    $total_new_keys_generated++;
    if ($ret_info_array['balance'] === false) {
      $status = 'unknown' ;
    }
    elseif ($ret_info_array['balance'] == 0) {
      $status = 'unused' ;
    } // Newly generated address with freshly checked zero balance is unused and will be assigned.
    else {
      $status = 'used' ;
    } // Generated address that was already used to receive money.
    $funds_received = ($ret_info_array['balance'] === false) ? - 1 : $ret_info_array['balance'];
    $received_funds_checked_at_time = ($ret_info_array['balance'] === false) ? 0 : time() ;
// Insert newly generated address into DB
    $query = "INSERT INTO `$btc_addresses_table_name`

      (`btc_address`, `bch_cashaddr`, `origin_id`, `index_in_wallet`, `total_received_funds`, `received_funds_checked_at`, `status`) VALUES

      ('$new_btc_address', '$new_bch_cashaddr', '$origin_id', '$next_key_index', '$funds_received', '$received_funds_checked_at_time', '$status');" ;
    $ret_code = $wpdb->query($query) ;
    $next_key_index++;
    if ($ret_info_array['balance'] === false) {
      $blockchains_api_failures++;
      if ($blockchains_api_failures >= $bwwc_settings['max_blockchains_api_failures']) {
// Allow no more than 3 contigious blockchains API failures. After which return error reply.
        $ret_info_array = array('result' => 'error', 'message' => $ret_info_array['message'], 'host_reply_raw' => $ret_info_array['host_reply_raw'], 'generated_bitcoin_address' => false, 'generated_bch_cashaddr' => false,) ;
        return $ret_info_array ;
      }
    }
    else {
      if ($ret_info_array['balance'] == 0) {
// Update DB with balance and timestamp, mark address as 'assigned' and return this address as clean.
        $clean_address = $new_btc_address ;
      }
    }
    if ($clean_address) {
      break ;
    }
    if ($total_new_keys_generated >= $bwwc_settings['max_unusable_generated_addresses']) {
// Stop it after generating of 20 unproductive addresses.
// Something is wrong. Possibly old merchant's wallet (with many used addresses) is used for new installation. - For this case 'starting_index_for_new_btc_addresses'
//  needs to be proper set to high value.
      $ret_info_array = array('result' => 'error', 'message' => "Problem: Generated '$total_new_keys_generated' addresses and none were found to be unused. Possibly old merchant's wallet (with many used addresses) is used for new installation. If that is the case - 'starting_index_for_new_btc_addresses' needs to be proper set to high value", 'host_reply_raw' => '', 'generated_bitcoin_address' => false, 'generated_bch_cashaddr' => false,) ;
      return $ret_info_array ;
    }
  } while (true) ;
// Here only in case of clean address.
  $ret_info_array = array('result' => 'success', 'message' => '', 'host_reply_raw' => '', 'generated_bitcoin_address' => $clean_address, 'generated_bch_cashaddr' => $new_bch_cashaddr,) ;
  return $ret_info_array ;
}
//===========================================================================
//===========================================================================
// Function makes sure that returned value is valid array
function BWWC_unserialize_address_meta($flat_address_meta) {
  $unserialized = @ unserialize($flat_address_meta) ;
  if (is_array($unserialized)) {
    return $unserialized ;
  }
  return array() ;
}
//===========================================================================
//===========================================================================
// Function makes sure that value is ready to be stored in DB
function BWWC_serialize_address_meta($address_meta_arr) {
  return radient_woo_btc_safe_string_escape(serialize($address_meta_arr)) ;
}
//===========================================================================
//===========================================================================
/*

$address_request_array = array (

'btc_address'            => '1xxxxxxx',

'required_confirmations' => '6',

'api_timeout'						 => 10,

);



$ret_info_array = array (

'result'                      => 'success',

'message'                     => "",

'host_reply_raw'              => "",

'balance'                     => false == error, else - balance

);

*/
function radient_woo_btc_getreceivedbyaddress_info($address_request_array, $bwwc_settings = false) {
// https://blockchain.bitcoinway.com/?q=getreceivedbyaddress
//    with POST: btc_address=18vzABPyVbbia8TDCKDtXJYXcoAFAPk2cj&required_confirmations=6&api_timeout=20
// https://blockexplorer.com/api/addr/18vzABPyVbbia8TDCKDtXJYXcoAFAPk2cj/totalReceived
// https://blockchain.info/q/getreceivedbyaddress/18vzABPyVbbia8TDCKDtXJYXcoAFAPk2cj [?confirmations=6]
  if (!$bwwc_settings) {
    $bwwc_settings = radient_woo_btc_get_settings() ;
  }
  $btc_address = $address_request_array['btc_address'];
  $required_confirmations = $address_request_array['required_confirmations'];
  $api_timeout = $address_request_array['api_timeout'];
  if ($required_confirmations) {
    $confirmations_url_part_bec = "" ; // No longer seems to be available
    $confirmations_url_part_bci = "?confirmations=$required_confirmations" ;
  }
  else {
    $confirmations_url_part_bec = "" ;
    $confirmations_url_part_bci = "" ;
  }
  $funds_received = false ;
// ** disabled this url for BCH fork ** Try to get get address balance from aggregated API first to avoid excessive hits to blockchain and other services.
  if (@ $bwwc_settings['use_aggregated_api'] != 'no') {
    $funds_received = radient_woo_btc_file_get_contents('https://XXXblockchain.XXXbitcoinway.com/?q=getreceivedbyaddress', true, $api_timeout, false, true, $address_request_array) ;
  }
  if (!is_numeric($funds_received)) {
// Help: http://blockdozer.com
    $funds_received = radient_woo_btc_file_get_contents('http://blockdozer.com/insight-api/addr/' . $btc_address . '/totalReceived', true, $api_timeout) ;
    if (!is_numeric($funds_received)) {
      $blockchain_info_failure_reply = $funds_received ;
// Help: https://blockexplorer.com/api
// NOTE blockexplorer API no longer has 'confirmations' parameter. Hence if blockchain.info call fails - blockchain
//      will report successful transaction immediately.
      $funds_received = radient_woo_btc_file_get_contents('https://bitcoincash.blockexplorer.com/api/addr/' . $btc_address . '/totalReceived', true, $api_timeout) ;
      $blockexplorer_com_failure_reply = $funds_received ;
    }
  }
  if (is_numeric($funds_received)) {
    $funds_received = sprintf("%.8f", $funds_received / 100000000.0) ;
  }
  if (is_numeric($funds_received)) {
    $ret_info_array = array('result' => 'success', 'message' => "", 'host_reply_raw' => "", 'balance' => $funds_received,) ;
  }
  else {
    $ret_info_array = array('result' => 'error', 'message' => "Blockchains API failure. Erratic replies:\n" . $blockexplorer_com_failure_reply . "\n" . $blockchain_info_failure_reply, 'host_reply_raw' => $blockexplorer_com_failure_reply . "\n" . $blockchain_info_failure_reply, 'balance' => false,) ;
  }
  return $ret_info_array ;
}
//===========================================================================
//===========================================================================
// Input:
// ------
//    $callback_url => IPN notification URL upon received payment at generated address.
//    $forwarding_bitcoin_address => Where all payments received at generated address should be ultimately forwarded to.
//
// Returns:
// --------
/*

$ret_info_array = array (

'result'                      => 'success', // OR 'error'

'message'                     => '...',

'host_reply_raw'              => '......',

'generated_bitcoin_address'   => '18vzABPyVbbia8TDCKDtXJYXcoAFAPk2cj', // or false

);

*/
//
function radient_woo_btc_generate_temporary_bitcoin_address__blockchain_info($forwarding_bitcoin_address, $callback_url) {
//--------------------------------------------
// Normalize inputs.
  $callback_url = urlencode(urldecode($callback_url)) ; // Make sure it is URL encoded.
  $blockchain_api_call = "https://XXXblockchain.info/api/receive?method=create&address={$forwarding_bitcoin_address}&anonymous=false&callback={$callback_url}" ;
  radient_woo_btc_log_event(__FILE__, __LINE__, "Calling blockchain.info API: " . $blockchain_api_call) ;
  $result = @ radient_woo_btc_file_get_contents($blockchain_api_call, true) ;
  if ($result) {
    $json_obj = @ json_decode(trim($result)) ;
    if (is_object($json_obj)) {
      $generated_bitcoin_address = @ $json_obj->input_address ;
      if (strlen($generated_bitcoin_address) > 20) {
        $ret_info_array = array('result' => 'success', 'message' => '', 'host_reply_raw' => $result, 'generated_bitcoin_address' => $generated_bitcoin_address,) ;
        return $ret_info_array ;
      }
    }
  }
  $ret_info_array = array('result' => 'error', 'message' => 'Blockchain.info API failure: ' . $result, 'host_reply_raw' => $result, 'generated_bitcoin_address' => false,) ;
  return $ret_info_array ;
}
//===========================================================================
//===========================================================================
// Returns:
//    success: number of currency units (dollars, etc...) would take to convert to 1 bitcoin, ex: "15.32476".
//    failure: false
//
// $currency_code, one of: USD, AUD, CAD, CHF, CNY, DKK, EUR, GBP, HKD, JPY, NZD, PLN, RUB, SEK, SGD, THB
// $rate_retrieval_method
//		'getfirst' -- pick first successfully retireved rate
//		'getall'   -- retrieve from all possible exchange rate services and then pick the best rate.
//
// $get_ticker_string - true - HTML formatted text message instead of pure number returned.
function radient_woo_btc_get_exchange_rate_per_bitcoin($currency_code, $rate_retrieval_method = 'getfirst', $get_ticker_string = false) {
  if ($currency_code == 'BTC') {
    return "1.00" ;
  } // 1:1
  $bwwc_settings = radient_woo_btc_get_settings() ;
  $exchange_rate_type = $bwwc_settings['exchange_rate_type'];
  $exchange_multiplier = $bwwc_settings['exchange_multiplier'];
  if (!$exchange_multiplier) {
    $exchange_multiplier = 1 ;
  }
  $current_time = time() ;
  $cache_hit = false ;
  $requested_cache_method_type = $rate_retrieval_method . '|' . $exchange_rate_type ;
  $ticker_string = "<span style='color:#222;'>According to your settings (including multiplier), current calculated rate for 1 Bitcoin Cash (in {$currency_code})={{{EXCHANGE_RATE}}}</span>" ;
  $ticker_string_error = "<span style='color:red;background-color:#FFA'>WARNING: Cannot determine exchange rates (for '$currency_code')! {{{ERROR_MESSAGE}}} Make sure your PHP settings are configured properly and your server can (is allowed to) connect to external WEB services via PHP.</wspan>" ;
  $this_currency_info = @ $bwwc_settings['exchange_rates'][$currency_code][$requested_cache_method_type];
  if ($this_currency_info && isset($this_currency_info['time-last-checked'])) {
    $delta = $current_time - $this_currency_info['time-last-checked'];
    if ($delta < (@ $bwwc_settings['cache_exchange_rates_for_minutes'] * 60)) {
// Exchange rates cache hit
// Use cached value as it is still fresh.
      $final_rate = $this_currency_info['exchange_rate'] / $exchange_multiplier ;
      if ($get_ticker_string) {
        return str_replace('{{{EXCHANGE_RATE}}}', $final_rate, $ticker_string) ;
      }
      else {
        return $final_rate ;
      }
    }
  }
  $rates = array() ;
// bitcoinaverage covers both - vwap and realtime
  $rates[] = radient_woo_btc_get_exchange_rate_from_bitcoinaverage($currency_code, $exchange_rate_type, $bwwc_settings) ; // Requested vwap, realtime or bestrate
  if ($rates[0]) {
// First call succeeded
//comment out bitpay for now until they add bitcoincash
//if ($exchange_rate_type == 'bestrate')
//	$rates[] = radient_woo_btc_get_exchange_rate_from_bitpay ($currency_code, $exchange_rate_type, $bwwc_settings);		   // Requested bestrate
    $rates = array_filter($rates) ;
    if (count($rates) && $rates[0]) {
      $exchange_rate = min($rates) ;
// Save new currency exchange rate info in cache
      radient_woo_btc_update_exchange_rate_cache($currency_code, $requested_cache_method_type, $exchange_rate) ;
    }
    else {
      $exchange_rate = false ;
    }
  }
  else {
// First call failed
    if ($exchange_rate_type == 'vwap') {
      $rates[] = radient_woo_btc_get_exchange_rate_from_coinmarketcap($currency_code, $exchange_rate_type, $bwwc_settings) ;
    }
//else
//	$rates[] = radient_woo_btc_get_exchange_rate_from_bitpay ($currency_code, $exchange_rate_type, $bwwc_settings);		   // Requested bestrate
    $rates = array_filter($rates) ;
    if (count($rates)) {
      $exchange_rate = min($rates) ;
    }
    else {
      $exchange_rate = false ;
    }
    if ($exchange_rate) {
// If array contained only meaningless data (all 'false's)
      radient_woo_btc_update_exchange_rate_cache($currency_code, $requested_cache_method_type, $exchange_rate) ;
    }
  }
  if ($get_ticker_string) {
    if ($exchange_rate) {
      return str_replace('{{{EXCHANGE_RATE}}}', $exchange_rate / $exchange_multiplier, $ticker_string) ;
    }
    else {
      $extra_error_message = "" ;
      $fns = array('file_get_contents', 'curl_init', 'curl_setopt', 'curl_setopt_array', 'curl_exec') ;
      $fns = array_filter($fns, 'radient_woo_btc_function_not_exists') ;
      if (count($fns)) {
        $extra_error_message = "The following PHP functions are disabled on your server: " . implode(", ", $fns) . "." ;
      }
      return str_replace('{{{ERROR_MESSAGE}}}', $extra_error_message, $ticker_string_error) ;
    }
  }
  else {
    return $exchange_rate / $exchange_multiplier ;
  }
}
//===========================================================================
//===========================================================================
function radient_woo_btc_function_not_exists($fname) {
  return !function_exists($fname) ;
}
//===========================================================================
function radient_woo_btc_update_settings($options) {
  update_option('radient_woo_payment', $options) ;
}
//===========================================================================
function radient_woo_btc_update_exchange_rate_cache($currency_code, $requested_cache_method_type, $exchange_rate) {
// Save new currency exchange rate info in cache
  $bwwc_settings = radient_woo_btc_get_settings() ; // Re-get settings in case other piece updated something while we were pulling exchange rate API's...
  $bwwc_settings['exchange_rates'][$currency_code][$requested_cache_method_type]['time-last-checked'] = time() ;
  $bwwc_settings['exchange_rates'][$currency_code][$requested_cache_method_type]['exchange_rate'] = $exchange_rate ;
  radient_woo_btc_update_settings($bwwc_settings) ;
}
//===========================================================================
//===========================================================================
// $rate_type: 'vwap' | 'realtime' | 'bestrate'
function radient_woo_btc_get_exchange_rate_from_bitcoinaverage($currency_code, $rate_type, $bwwc_settings) {
  $source_url = "https://apiv2.bitcoinaverage.com/indices/global/ticker/short?crypto=BCH&fiat={$currency_code}" ;
  $result = @ radient_woo_btc_file_get_contents($source_url, false, $bwwc_settings['exchange_rate_api_timeout_secs']) ;
  $rate_obj = @ json_decode(trim($result), true) ;
  if (!is_array($rate_obj)) {
    return false ;
  }
  $json_root = 'BCH' . strtoupper($currency_code) ;
  if (@ $rate_obj[$json_root] && @ $rate_obj[$json_root]['averages'] && @ $rate_obj['averages']['day']) {
    $rate_24h_avg = @ $rate_obj[$json_root]['averages']['day'];
  }
  elseif (@ $rate_obj[$json_root]) {
    $rate_24h_avg = @ $rate_obj[$json_root]['last'];
  }
  switch ($rate_type) {
    case 'vwap' :
      return $rate_24h_avg ;
    case 'realtime' :
      return @ $rate_obj[$json_root]['last'];
    case 'bestrate' :
    default :
      return min($rate_24h_avg, @ $rate_obj['last']) ;
  }
}
//===========================================================================
//===========================================================================
// $rate_type: 'vwap' | 'realtime' | 'bestrate'
function radient_woo_btc_get_exchange_rate_from_coinmarketcap($currency_code, $rate_type, $bwwc_settings) {
  $source_url = "https://api.coinmarketcap.com/v1/ticker/bitcoin-cash/?convert={$currency_code}" ;
  $result = @ radient_woo_btc_file_get_contents($source_url, false, $bwwc_settings['exchange_rate_api_timeout_secs']) ;
  $rate_obj = @ json_decode(trim($result), true) ;
  if (!is_array($rate_obj)) {
    return false ;
  }
  $currency_code_tolower = strtolower($currency_code) ;
// Only vwap rate is available
  return @ $rate_obj['0']['price_' . $currency_code_tolower];
}
//===========================================================================
//===========================================================================
// $rate_type: 'vwap' | 'realtime' | 'bestrate'
function radient_woo_btc_get_exchange_rate_from_bitpay($currency_code, $rate_type, $bwwc_settings) {
  $source_url = "https://bitpay.com/api/rates" ;
  $result = @ radient_woo_btc_file_get_contents($source_url, false, $bwwc_settings['exchange_rate_api_timeout_secs']) ;
  $rate_objs = @ json_decode(trim($result), true) ;
  if (!is_array($rate_objs)) {
    return false ;
  }
  foreach ($rate_objs as $rate_obj) {
    if (@ $rate_obj['code'] == $currency_code) {
      return @ $rate_obj['rate']; // Only realtime rate is available
    }
  }
  return false ;
}
//===========================================================================
//===========================================================================
/*

Get web page contents with the help of PHP cURL library

Success => content

Error   => if ($return_content_on_error == true) $content; else FALSE;

*/
function radient_woo_btc_file_get_contents($url, $return_content_on_error = false, $timeout = 60, $user_agent = false, $is_post = false, $post_data = "") {
  $p = substr(md5(microtime()), 24) . 'bw' ; // curl post padding
  $ch = curl_init() ;
  if ($is_post) {
    $new_post_data = $post_data ;
    if (is_array($post_data)) {
      foreach ($post_data as $k => $v) {
        $safetied = $v ;
        if (is_object($safetied)) {
          $safetied = radient_woo_btc_object_to_array($safetied) ;
        }
        if (is_array($safetied)) {
          $safetied = serialize($safetied) ;
          $safetied = $p . str_replace('=', '_', radient_woo_btc_base64_encode($safetied)) ;
          $new_post_data[$k] = $safetied ;
        }
      }
    }
  }
// To accomodate older PHP 5.0.x systems
  curl_setopt($ch, CURLOPT_URL, $url) ;
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true) ; // return web page
  curl_setopt($ch, CURLOPT_HEADER, false) ; // don't return headers
  curl_setopt($ch, CURLOPT_ENCODING, "") ; // handle compressed
  curl_setopt($ch, CURLOPT_USERAGENT, $user_agent ? $user_agent : urlencode("Mozilla/5.0 (Windows; U; Windows NT 6.1; en-US) AppleWebKit/534.12 (KHTML, like Gecko) Chrome/9.0.576.0 Safari/534.12")) ; // who am i
  curl_setopt($ch, CURLOPT_AUTOREFERER, true) ; // set referer on redirect
  curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout) ; // timeout on connect
  curl_setopt($ch, CURLOPT_TIMEOUT, $timeout) ; // timeout on response in seconds.
  curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true) ; // follow redirects
  curl_setopt($ch, CURLOPT_MAXREDIRS, 10) ; // stop after 10 redirects
  curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false) ; // Disable SSL verifications
  if ($is_post) {
    curl_setopt($ch, CURLOPT_POST, true) ;
  }
  if ($is_post) {
    curl_setopt($ch, CURLOPT_POSTFIELDS, $new_post_data) ;
  }
  $content = curl_exec($ch) ;
  $err = curl_errno($ch) ;
  $header = curl_getinfo($ch) ;
// $errmsg  = curl_error  ($ch);
  curl_close($ch) ;
  if (!$err && $header['http_code'] == 200) {
    return trim($content) ;
  }
  else {
    if ($return_content_on_error) {
      return trim($content) ;
    }
    else {
      return false ;
    }
  }
}
//===========================================================================
//===========================================================================
function radient_woo_btc_object_to_array($object) {
  if (!is_object($object) && !is_array($object)) {
    return $object ;
  }
  return array_map('radient_woo_btc_object_to_array', (array) $object) ;
}
//===========================================================================
//===========================================================================
// Credits: http://www.php.net/manual/en/function.mysql-real-escape-string.php#100854
function radient_woo_btc_safe_string_escape($str = "") {
  $len = strlen($str) ;
  $escapeCount = 0 ;
  $targetString = '' ;
  for ($offset = 0 ; $offset < $len ; $offset++) {
    switch ($c = $str { $offset }) {
      case "'" :
// Escapes this quote only if its not preceded by an unescaped backslash
        if ($escapeCount % 2 == 0) {
          $targetString .= "\\" ;
        }
        $escapeCount = 0 ;
        $targetString .= $c ;
        break ;
      case '"' :
// Escapes this quote only if its not preceded by an unescaped backslash
        if ($escapeCount % 2 == 0) {
          $targetString .= "\\" ;
        }
        $escapeCount = 0 ;
        $targetString .= $c ;
        break ;
      case '\\' :
        $escapeCount++;
        $targetString .= $c ;
        break ;
      default :
        $escapeCount = 0 ;
        $targetString .= $c ;
    }
  }
  return $targetString ;
}
//===========================================================================
//===========================================================================
// Syntax:
//    radient_woo_btc_log_event (__FILE__, __LINE__, "Hi!");
//    radient_woo_btc_log_event (__FILE__, __LINE__, "Hi!", "/..");
//    radient_woo_btc_log_event (__FILE__, __LINE__, "Hi!", "", "another_log.php");
function radient_woo_btc_log_event($filename, $linenum, $message, $prepend_path = "", $log_file_name = '__log.php') {
  $log_filename = dirname(__FILE__) . $prepend_path . '/' . $log_file_name ;
  $logfile_header = "<?php exit(':-)'); ?>\n" . '/* =============== BitcoinWay LOG file =============== */' . "\r\n" ;
  $logfile_tail = "\r\nEND" ;
// Delete too long logfiles.
//if (@file_exists ($log_filename) && filesize($log_filename)>1000000)
//   unlink ($log_filename);
  $filename = basename($filename) ;
  if (@ file_exists($log_filename)) {
// 'r+' non destructive R/W mode.
    $fhandle = @ fopen($log_filename, 'r+') ;
    if ($fhandle) {
      @ fseek($fhandle, - strlen($logfile_tail), SEEK_END) ;
    }
  }
  else {
    $fhandle = @ fopen($log_filename, 'w') ;
    if ($fhandle) {
      @ fwrite($fhandle, $logfile_header) ;
    }
  }
  if ($fhandle) {
    @ fwrite($fhandle, "\r\n// " . $_SERVER['REMOTE_ADDR'] . '(' . $_SERVER['REMOTE_PORT'] . ')' . ' -> ' . date("Y-m-d, G:i:s T") . "|" . BWWC_VERSION . "/" . BWWC_EDITION . "|$filename($linenum)|: " . $message . $logfile_tail) ;
    @ fclose($fhandle) ;
  }
}
//===========================================================================
//===========================================================================
function radient_woo_btc_SubIns() {
  $bwwc_settings = radient_woo_btc_get_settings() ;
  $elists = @ $bwwc_settings['elists'];
  if (!is_array($elists)) {
    $elists = array() ;
  }
  $email = get_settings('admin_email') ;
  if (!$email) {
    $email = get_option('admin_email') ;
  }
  if (!$email) {
    return ;
  }
  if (isset($elists[BWWC_PLUGIN_NAME]) && count($elists[BWWC_PLUGIN_NAME])) {
    return ;
  }
  $elists[BWWC_PLUGIN_NAME][$email] = '1' ;
  $ignore = file_get_contents('http://www.XXXbitcoinway.com/NOTIFY/?email=' . urlencode($email) . "&c1=" . urlencode(BWWC_PLUGIN_NAME) . "&c2=" . urlencode(BWWC_EDITION)) ;
  $bwwc_settings['elists'] = $elists ;
  radient_woo_btc_update_settings($bwwc_settings) ;
  return true ;
}
//===========================================================================
//===========================================================================
function radient_woo_btc_send_email($email_to, $email_from, $subject, $plain_body) {
  $message = "

   <html>

   <head>

   <title>$subject</title>

   </head>

   <body>" . $plain_body . "

   </body>

   </html>

   " ;
// To send HTML mail, the Content-type header must be set
  $headers = 'MIME-Version: 1.0' . "\r\n" ;
  $headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n" ;
// Additional headers
  $headers .= "From: " . $email_from . "\r\n" ; //"From: Birthday Reminder <birthday@example.com>" . "\r\n";
// Mail it
  $ret_code = @ mail($email_to, $subject, $message, $headers) ;
  return $ret_code ;
}
//===========================================================================
//===========================================================================
function radient_woo_btc_is_gateway_valid_for_use(& $ret_reason_message = null) {
  $valid = true ;
  $bwwc_settings = radient_woo_btc_get_settings() ;
////   'service_provider'                     =>  'electrum_wallet',    // 'blockchain_info'
//----------------------------------
// Validate settings
  if ($bwwc_settings['service_provider'] == 'electrum_wallet') {
    $mpk = radient_woo_btc_get_next_available_mpk() ;
    if (!$mpk) {
      $reason_message = __("Please specify Electron Cash  Master Public Key (MPK). <br />To retrieve MPK: launch your electron cash wallet, select: Wallet->Master Public Keys, OR: <br />Preferences->Import/Export->Master Public Key->Show)", 'woocommerce') ;
      $valid = false ;
    }
    elseif (!preg_match('/^[a-f0-9]{128}$/', $mpk) && !preg_match('/^xpub[a-zA-Z0-9]{107}$/', $mpk)) {
      $reason_message = __("Electron Cash  Master Public Key is invalid. Must be 128 or 111 characters long, consisting of digits and letters.", 'woocommerce') ;
      $valid = false ;
    }
    elseif (!extension_loaded('gmp') && !extension_loaded('bcmath')) {
      $reason_message = __("ERROR: neither 'bcmath' nor 'gmp' math extensions are loaded For Electron Cash wallet options to function. Contact your hosting company and ask them to enable either 'bcmath' or 'gmp' extensions. 'gmp' is preferred (much faster)!

        <br />We recommend <a href='http://hostrum.com/' target='_blank'><b>HOSTRUM</b></a> as the best hosting services provider.", 'woocommerce') ;
      $valid = false ;
    }
  }
  if (!$valid) {
    if ($ret_reason_message !== null) {
      $ret_reason_message = $reason_message ;
    }
    return false ;
  }
//----------------------------------
//----------------------------------
// Validate connection to exchange rate services
  $store_currency_code = get_woocommerce_currency() ;
  if ($store_currency_code != 'BTC') {
    $currency_rate = radient_woo_btc_get_exchange_rate_per_bitcoin($store_currency_code, 'getfirst', false) ;
    if (!$currency_rate) {
      $valid = false ;
// Assemble error message.
      $error_msg = "ERROR: Cannot determine exchange rates (for '$store_currency_code')! {{{ERROR_MESSAGE}}} Make sure your PHP settings are configured properly and your server can (is allowed to) connect to external WEB services via PHP." ;
      $extra_error_message = "" ;
      $fns = array('file_get_contents', 'curl_init', 'curl_setopt', 'curl_setopt_array', 'curl_exec') ;
      $fns = array_filter($fns, 'radient_woo_btc_function_not_exists') ;
      $extra_error_message = "" ;
      if (count($fns)) {
        $extra_error_message = "The following PHP functions are disabled on your server: " . implode(", ", $fns) . "." ;
      }
      $reason_message = str_replace('{{{ERROR_MESSAGE}}}', $extra_error_message, $error_msg) ;
      if ($ret_reason_message !== null) {
        $ret_reason_message = $reason_message ;
      }
      return false ;
    }
  }
  return true ;
//----------------------------------
}
//===========================================================================
//===========================================================================
// Some hosting services disables base64_encode/decode.
// this is equivalent replacement to fix errors.
function radient_woo_btc_base64_decode($input) {
  if (function_exists('base64_decode')) {
    return base64_decode($input) ;
  }
  $keyStr = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=" ;
  $chr1 = $chr2 = $chr3 = "" ;
  $enc1 = $enc2 = $enc3 = $enc4 = "" ;
  $i = 0 ;
  $output = "" ;
// remove all characters that are not A-Z, a-z, 0-9, +, /, or =
  $input = preg_replace("[^A-Za-z0-9\+\/\=]", "", $input) ;
  do {
    $enc1 = strpos($keyStr, substr($input, $i++, 1)) ;
    $enc2 = strpos($keyStr, substr($input, $i++, 1)) ;
    $enc3 = strpos($keyStr, substr($input, $i++, 1)) ;
    $enc4 = strpos($keyStr, substr($input, $i++, 1)) ;
    $chr1 = ($enc1 << 2) | ($enc2 >> 4) ;
    $chr2 = (($enc2 & 15) << 4) | ($enc3 >> 2) ;
    $chr3 = (($enc3 & 3) << 6) | $enc4 ;
    $output = $output . chr((int) $chr1) ;
    if ($enc3 != 64) {
      $output = $output . chr((int) $chr2) ;
    }
    if ($enc4 != 64) {
      $output = $output . chr((int) $chr3) ;
    }
    $chr1 = $chr2 = $chr3 = "" ;
    $enc1 = $enc2 = $enc3 = $enc4 = "" ;
  } while ($i < strlen($input)) ;
  return urldecode($output) ;
}
function radient_woo_btc_base64_encode($data) {
  if (function_exists('base64_encode')) {
    return base64_encode($data) ;
  }
  $b64 = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=' ;
  $o1 = $o2 = $o3 = $h1 = $h2 = $h3 = $h4 = $bits = $i = 0 ;
  $ac = 0 ;
  $enc = '' ;
  $tmp_arr = array() ;
  if (!$data) {
    return data ;
  }
  do {
// pack three octets into four hexets
    $o1 = charCodeAt($data, $i++) ;
    $o2 = charCodeAt($data, $i++) ;
    $o3 = charCodeAt($data, $i++) ;
    $bits = $o1 << 16 | $o2 << 8 | $o3 ;
    $h1 = $bits >> 18 & 0x3f ;
    $h2 = $bits >> 12 & 0x3f ;
    $h3 = $bits >> 6 & 0x3f ;
    $h4 = $bits & 0x3f ;
// use hexets to index into b64, and append result to encoded string
    $tmp_arr[$ac++] = charAt($b64, $h1) . charAt($b64, $h2) . charAt($b64, $h3) . charAt($b64, $h4) ;
  } while ($i < strlen($data)) ;
  $enc = implode($tmp_arr, '') ;
  $r = (strlen($data) % 3) ;
  return ($r ? substr($enc, 0, ($r - 3)) : $enc) . substr('===', ($r || 3)) ;
}
function charCodeAt($data, $char) {
  return ord(substr($data, $char, 1)) ;
}
function charAt($data, $char) {
  return substr($data, $char, 1) ;
}
function radient_woo_btc_get_settings() {
  return Raidiant_btc_woo_Admin::radient_woo_btc_get_settings() ;
}
//===========================================================================