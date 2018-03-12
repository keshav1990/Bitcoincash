<?php



/**

 * The plugin bootstrap file

 *

 * This file is read by WordPress to generate the plugin information in the plugin

 * admin area. This file also includes all of the dependencies used by the plugin,

 * registers the activation and deactivation functions, and defines a function

 * that starts the plugin.

 *

 * @link              https://www.neowebsolution.com

 * @since             1.0.0

 * @package           Raidiant_btc_woo

 *

 * @wordpress-plugin

 * Plugin Name:       Woocommerce Bitcoin Cash Payment Plugin

 * Plugin URI:        https://neowebsolution.com/ai/

 * Description:       This is a short description of what the plugin does. It's displayed in the WordPress admin area.

 * Version:           1.0.0

 * Author:            Raidiant

 * Author URI:        https://www.neowebsolution.com

 * License:           GPL-2.0+

 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt

 * Text Domain:       raidiant_btc_woo

 * Domain Path:       /languages

 */



// If this file is called directly, abort.

if ( ! defined( 'WPINC' ) ) {

	die;

}
  define('radient_path',plugin_dir_path( __FILE__ ));
  define('radient_URL',plugins_url()."/raidiant_btc_woo/");
 $active_plugins = apply_filters('active_plugins', get_option('active_plugins'));

if(in_array('woocommerce/woocommerce.php', $active_plugins)){

/**

 * Currently plugin version.

 * Start at version 1.0.0 and use SemVer - https://semver.org

 * Rename this for your plugin and update it as you release new versions.

 */

define( 'PLUGIN_NAME_VERSION', '1.0.0' );



/**

 * The code that runs during plugin activation.

 * This action is documented in includes/class-raidiant_btc_woo-activator.php

 */

function activate_raidiant_btc_woo() {

	require_once plugin_dir_path( __FILE__ ) . 'includes/class-raidiant_btc_woo-activator.php';

	Raidiant_btc_woo_Activator::activate();

}



/**

 * The code that runs during plugin deactivation.

 * This action is documented in includes/class-raidiant_btc_woo-deactivator.php

 */

function deactivate_raidiant_btc_woo() {

	require_once plugin_dir_path( __FILE__ ) . 'includes/class-raidiant_btc_woo-deactivator.php';

	Raidiant_btc_woo_Deactivator::deactivate();

}



register_activation_hook( __FILE__, 'activate_raidiant_btc_woo' );

register_deactivation_hook( __FILE__, 'deactivate_raidiant_btc_woo' );



/**

 * The core plugin class that is used to define internationalization,

 * admin-specific hooks, and public-facing site hooks.

 */

require plugin_dir_path( __FILE__ ) . 'includes/class-raidiant_btc_woo.php';



/**

 * Begins execution of the plugin.

 *

 * Since everything within the plugin is registered via hooks,

 * then kicking off the plugin from this point in the file does

 * not affect the page life cycle.

 *

 * @since    1.0.0

 */

function run_raidiant_btc_woo() {



	$plugin = new Raidiant_btc_woo();

	$plugin->run();



}

run_raidiant_btc_woo();





}