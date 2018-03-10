<?php

/**
 * Fired during plugin activation
 *
 * @link       https://www.neowebsolution.com
 * @since      1.0.0
 *
 * @package    Raidiant_btc_woo
 * @subpackage Raidiant_btc_woo/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    Raidiant_btc_woo
 * @subpackage Raidiant_btc_woo/includes
 * @author     Raidiant <info@neowebsolution.com>
 */
class Raidiant_btc_woo_Activator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function activate() {
      global $wpdb;
        $btc_addresses_table_name             = $wpdb->prefix . 'radient_woo_btc_addresses';

    if ($wpdb->get_var("SHOW TABLES LIKE '$btc_addresses_table_name'") != $btc_addresses_table_name) {


      $query = "CREATE TABLE IF NOT EXISTS `$btc_addresses_table_name` (
    `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    `btc_address` char(36) NOT NULL,
    `bch_cashaddr` char(80),
    `origin_id` char(128) NOT NULL DEFAULT '',
    `index_in_wallet` bigint(20) NOT NULL DEFAULT '0',
    `status` char(16)  NOT NULL DEFAULT 'unknown',
    `last_assigned_to_ip` char(16) NOT NULL DEFAULT '0.0.0.0',
    `assigned_at` bigint(20) NOT NULL DEFAULT '0',
    `total_received_funds` DECIMAL( 16, 8 ) NOT NULL DEFAULT '0.00000000',
    `received_funds_checked_at` bigint(20) NOT NULL DEFAULT '0',
    `address_meta` MEDIUMBLOB NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `btc_address` (`btc_address`),
    UNIQUE KEY `bch_cashaddr` (`bch_cashaddr`),
    KEY `index_in_wallet` (`index_in_wallet`),
    KEY `origin_id` (`origin_id`),
    KEY `status` (`status`)
    );";
    $wpdb->query($query);
	}
	}

}
