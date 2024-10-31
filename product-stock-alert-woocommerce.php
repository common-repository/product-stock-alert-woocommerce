<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://dhrubokinfotech.com/
 * @since             1.0.0
 * @package           Product_Stock_Alert_Woocommerce
 *
 * @wordpress-plugin
 * Plugin Name:       Product Stock Alert WooCommerce
 * Plugin URI:        wordpress.org/plugins/product-stock-alert-woocommerce
 * Description:       This is a short description of what the plugin does. It's displayed in the WordPress admin area.
 * Version:           1.0.0
 * Author:            Dhrubok Infotech
 * Author URI:        https://dhrubokinfotech.com/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       product-stock-alert-woocommerce
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'PRODUCT_STOCK_ALERT_WOOCOMMERCE_VERSION', '1.0.0' );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-product-stock-alert-woocommerce-activator.php
 */
function activate_product_stock_alert_woocommerce() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-product-stock-alert-woocommerce-activator.php';
	Product_Stock_Alert_Woocommerce_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-product-stock-alert-woocommerce-deactivator.php
 */
function deactivate_product_stock_alert_woocommerce() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-product-stock-alert-woocommerce-deactivator.php';
	Product_Stock_Alert_Woocommerce_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_product_stock_alert_woocommerce' );
register_deactivation_hook( __FILE__, 'deactivate_product_stock_alert_woocommerce' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-product-stock-alert-woocommerce.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_product_stock_alert_woocommerce() {

	$plugin = new Product_Stock_Alert_Woocommerce();
	$plugin->run();

}
run_product_stock_alert_woocommerce();




// Include CSS - Front End
add_action( 'init','Product_stock_alert_include_css');
function Product_stock_alert_include_css() {
    wp_register_style('Product_stock_alert_css', plugins_url('assets/css/instock-email-alert.css',__FILE__ ));
    if ( get_option('Product_stock_option_css') != 'on' ) {
        wp_enqueue_style('Product_stock_alert_css');
    }
}

// Include CSS / JS - Admin
add_action( 'admin_enqueue_scripts', 'Product_stock_alert_include_admin_css' );
function Product_stock_alert_include_admin_css() {
    wp_register_style('Product_stock_alert_admin_css', plugins_url('assets/css/instock-email-alert-admin.css',__FILE__ ), false, '1.0');
    wp_enqueue_style( 'Product_stock_alert_admin_css');
    wp_enqueue_script('jquery');
    wp_enqueue_script( 'Product_stock_alert_admin_js', plugins_url('assets/js/instock-email-alert-admin.js',__FILE__ ), false, '1.0');
}
 
// DB - Create table on first activation 
register_activation_hook( __FILE__, 'Product_stock_alert_install' );

// DB - Versioning
global $Product_stock_alert_db_version;
$Product_stock_alert_db_version = '2.0';

function Product_stock_alert_install () {
    global $wpdb;
    global $Product_stock_alert_db_version;
    $table_name = $wpdb->prefix . "Product_stock_alert";
    $charset_collate = $wpdb->get_charset_collate();
    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        date MEDIUMTEXT NOT NULL,
        user_email MEDIUMTEXT NOT NULL,
        product_id MEDIUMINT(9) NOT NULL,
        status TINYINT(1) DEFAULT NULL,
        UNIQUE KEY id (id)
    ) $charset_collate;";
    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta( $sql );
    add_option( 'Product_stock_alert_db_version', $Product_stock_alert_db_version );
}

// DB - Update
add_action( 'plugins_loaded', 'Product_stock_alert_update_db_check' );
function Product_stock_alert_update_db_check() {
    global $Product_stock_alert_db_version;
    if ( get_site_option( 'Product_stock_alert_db_version' ) != $Product_stock_alert_db_version ) {
        Product_stock_alert_install();
    }
}

// Email Notifications - Send Email
add_action('woocommerce_product_set_stock_status', 'Product_stock_alert_check_status', 10, 2);
function Product_stock_alert_check_status($productId, $status) {
    if ($status == "instock"){	
        // Product details
        $prod_title = get_the_title($productId);
        $prod_link = get_the_permalink($productId);
        // Options - Sender
        if ( get_option('Product_stock_option_sender') ) {
            $options_sender = get_option('Product_stock_option_sender');
        } else {
            $options_sender = get_option('blogname');
        }
        // Options - From
        if ( get_option('Product_stock_option_from') ) {
            $options_from = get_option('Product_stock_option_from');
        } else {
            $options_from = get_option('admin_email');
        }
        // Options - Subject
        if ( get_option('Product_stock_option_subject') ) {
            $options_subject = get_option('Product_stock_option_subject');
        } else {
            $options_subject = 'Your product is on stock now!';
        }
		$options_subject = str_replace('%product_name%', $prod_title, $options_subject);
		$options_subject = str_replace('%product_link%', $prod_link, $options_subject);
        // Options - Message
        if ( get_option('Product_stock_option_message') ) {
            $options_message = get_option('Product_stock_option_message');
        } else {
            $options_message = 'Hello, The product %product_name% is on stock. You can purchase it here: %product_link%';
        }
        $options_message = str_replace('%product_name%', $prod_title, $options_message);
        $options_message = str_replace('%product_link%', $prod_link, $options_message);
        // If out of stock
        $users = array();
        global $wpdb;
        $table_name = $wpdb->prefix . "Product_stock_alert";
        // Grab all the user emails for this product
        $emails = $wpdb->get_results("SELECT * FROM `".$table_name."` WHERE product_id = '$productId' AND status = 0");
        foreach ( $emails as $email ) {
            $user_email = $email->user_email;
            $headers = 'From: '.$options_sender.' <'.$options_from.'>' . "\r\n";
            wp_mail( $user_email, $options_subject, $options_message, $headers);
            // Set status
            $status = $wpdb->get_results("UPDATE `".$table_name."` SET status = 1 WHERE product_id = '$productId' AND status = 0 AND user_email = '$user_email'");
        }
    }
}

// Email Notifications - Save to DB
function Product_stock_alert_save_email($email, $productid){
    global $wpdb;
    $table_name = $wpdb->prefix . "Product_stock_alert";
    $date = date('d-m-Y h:i:s');
    $wpdb->insert( $table_name, array( 'date' => $date, 'user_email' => $email, 'product_id' => $productid, 'status' => 0), array( '%s', '%s', '%d', '%d' ) );
}

if ( isset($_POST['alert_email']) && !empty($_POST['alert_email']) ) {
    $the_email = $_POST['alert_email'];
    $id = $_POST['alert_id'];
    if ( filter_var($the_email, FILTER_VALIDATE_EMAIL) && is_numeric($id) ) {
        Product_stock_alert_save_email($the_email, $id);
        add_filter( 'woocommerce_single_product_summary', 'Product_stock_alert_save_sent', 80 );
    } else {
        add_filter( 'woocommerce_single_product_summary', 'Product_stock_alert_save_error', 80 );
    }
}

function Product_stock_alert_save_error(){
	$options_error = get_option('Product_stock_option_error');
	echo '<div class="instock_message error">' . $options_error . '</div>';
}

function Product_stock_alert_save_sent(){
	$options_success = get_option('Product_stock_option_success');
	echo '<div class="instock_message sent">' . $options_success . '</div>';
}

// Email Notifications - Remove from DB
if ( !empty($_POST) && isset($_POST['remove_date']) && isset($_POST['remove_email']) && isset($_POST['remove_product'])) {
    $date = $_POST['remove_date'];
    $email = $_POST['remove_email'];
    $productid = $_POST['remove_product'];
    global $wpdb;
    $table_name = $wpdb->prefix . "Product_stock_alert";
    $wpdb->delete ( $table_name, array('date' => $date, 'user_email' => $email, 'product_id' => $productid), array( '%s', '%s', '%d' ) );
}

// Add notification form
add_filter( 'woocommerce_single_product_summary', 'Product_stock_alert_form', 70 );
function Product_stock_alert_form($type = NULL){
    global $product;
    $stock = $product->get_total_stock();
    if ( !$stock > 0  && !$product->is_in_stock() ) {
        if ( get_option('Product_stock_option_placeholder') ) {
            $placeholder = get_option('Product_stock_option_placeholder');
        } else {
            $placeholder = 'Email address';
        }
        if ( get_option('Product_stock_option_submit') ) {
            $submit_value = get_option('Product_stock_option_submit');
        } else {
            $submit_value = 'Notify me when in stock';
        }
        $form = '
            <form action="" method="post" class="alert_wrapper">
                <input type="email" name="alert_email" id="alert_email" placeholder="' . $placeholder . '" />
                <input type="hidden" name="alert_id" id="alert_id" value="' . get_the_ID() . '"/>
                <input type="submit" value="' . $submit_value . '" class="" />
            </form> 
        ';
        if ($type == 'get') {
            return $form;
        } else {
            if ( get_option('Product_stock_option_shortcode') != 'on' ) {
                echo $form;
            }
        }
    }
}

// Add Options Page
add_action('admin_menu', 'Product_stock_alert_create_menu');
function Product_stock_alert_create_menu() {
    add_options_page(__('Product Stock Alert','menu-instock'), __('Product Stock Alert','menu-instock'), 'manage_options', 'instocksettings', 'Product_stock_alert_options');
}

// Options Page
function Product_stock_alert_options() {
    ?>
    <div id="instock_alert_options">
        <h1>Product Alert Settings</h1>
        <table class="form-table">
            <form method="post" action="options.php">
            <?php settings_fields('Product_stock_option_settings'); ?>
            <?php do_settings_sections('Product_stock_option_settings'); ?>
                <tr valign="top" class="title"><th colspan="2"><h2>Email settings</h2></th></tr>
                <tr valign="top">
                    <th scope="row">Email Sender:</th>
                    <td><input type="text" name="Product_stock_option_sender" id="Product_stock_option_sender" value="<?php if (get_option('Product_stock_option_sender')) {echo get_option('Product_stock_option_sender'); } else { echo get_option('blogname'); } ?>"/></td>
                </tr>
                <tr valign="top">
                    <th scope="row">Email From:</th>
                    <td><input type="text" name="Product_stock_option_from" id="Product_stock_option_from" value="<?php if (get_option('Product_stock_option_from')) { echo get_option('Product_stock_option_from'); } else {  echo get_option('admin_email'); } ?>"/></td>
                </tr>
                <tr valign="top">
                    <th scope="row">Email Subject:</th>
                    <td><input type="text" name="Product_stock_option_subject" id="Product_stock_option_subject" value="<?php if (get_option('Product_stock_option_subject')) { echo get_option('Product_stock_option_subject'); } else { echo 'Your product is on stock now!'; } ?>"/></td>
                </tr>
                <tr valign="top">
                    <th scope="row">Email Message:</th>
                    <td><textarea name="Product_stock_option_message" id="Product_stock_option_message"><?php if (get_option('Product_stock_option_message')) {echo get_option('Product_stock_option_message'); } else { echo 'Hello, The product %product_name% is on stock. You can purchase it here: %product_link%'; } ?></textarea></td>
                </tr>
                <tr valign="top">
                    <th scope="row">Tags to use (email only):</th>
                    <td><ul><li>Get the product title: <strong>%product_name%</strong></li><li>Show a link to the product: <strong>%product_link%</strong></li></ul></td>
                </tr>
                <tr valign="top" class="title"><th colspan="2"><h2>Form settings</h2></th></tr>
                <tr valign="top">
                    <th scope="row">Input placeholder:</th>
                    <td><input type="text" name="Product_stock_option_placeholder" id="Product_stock_option_placeholder" value="<?php if (get_option('Product_stock_option_placeholder')) { echo get_option('Product_stock_option_placeholder'); } else { echo 'Email address'; } ?>"/></td>
                </tr>
                <tr valign="top">
                    <th scope="row">Submit value:</th>
                    <td><input type="text" name="Product_stock_option_submit" id="Product_stock_option_submit" value="<?php if (get_option('Product_stock_option_submit')) { echo get_option('Product_stock_option_submit');  } else { echo 'Notify me when in stock'; } ?>"/></td>
                </tr>
                <tr valign="top">
                    <th scope="row">Error Message:</th>
                    <td><textarea name="Product_stock_option_error" id="Product_stock_option_error"><?php if (get_option('Product_stock_option_error')) { echo get_option('Product_stock_option_error'); } else {  echo 'Invalid email address.'; } ?></textarea></td>    					</tr>
                <tr valign="top">
                    <th scope="row">Success Message:</th>
                    <td><textarea name="Product_stock_option_success" id="Product_stock_option_success"><?php if (get_option('Product_stock_option_success')) { echo get_option('Product_stock_option_success'); } else { echo 'Thank you. We will notify you when the product is in stock.'; } ?></textarea></td>
                </tr>
                <tr valign="top">
                    <th scope="row">Disable CSS</th>
                    <td><input type="checkbox" name="Product_stock_option_css" id="Product_stock_option_css" <?php if (get_option('Product_stock_option_css')) { echo 'checked'; } ?> /></td>
                </tr>
                <tr valign="top" class="title"><th colspan="2"><h2>Misc settings</h2></th></tr>
                <tr valign="top">
                    <th scope="row">Disable Form (use shortcode instead)</th>
                    <td><input type="checkbox" name="Product_stock_option_shortcode" id="Product_stock_option_shortcode" <?php if (get_option('Product_stock_option_shortcode')) {  echo 'checked'; } ?> /></td>
                </tr>
                <tr valign="top">
                    <th scope="row">Shortcode</th>
                    <td><ul><li>Use the shortcode <strong>[instock]</strong> to display the form. It must be on the single woocommerce template.</li></ul></td>
                </tr>
                <tr valign="top">
                    <th scope="row"></th>
                    <td><?php submit_button(); ?></td>
                </tr>
           </form>
            <tr valign="top">
                <td colspan="2">
                    <div class="filters">
                        <span>Filters</span>
                        <input type="radio" name="filter" id="filter_all" class="filter" checked /><label for="filter_all">Show All</label>
                        <input type="radio" name="filter" id="filter_waiting" class="filter" /><label for="filter_waiting">Waiting</label>
                        <input type="radio" name="filter" id="filter_sent" class="filter" /><label for="filter_sent">Sent</label>
                    </div>
                    <ul id="subscribed_list">
                         <li class="header">
                            <div class="date">Date</div>
                            <div class="email">Email Address</div>
                            <div class="product">Product</div>
                            <div class="status">Status</div>
                            <div class="remove">Remove</div>
                        </li>
                        <?php
                        global $wpdb;
                        $table_name = $wpdb->prefix . "Product_stock_alert";
                        $users = $wpdb->get_results("SELECT * FROM `".$table_name."`");
                        foreach ($users as $user) {
                            $prod_title = get_the_title($user->product_id);
                            $prod_link = get_the_permalink($user->product_id);
                        ?>
                            <li class="user <?php echo $user->status == 1 ? 'sent' : 'waiting'; ?>">
                                <div class="date"><?php echo $user->date; ?></div>
                                <div class="email"><?php echo $user->user_email; ?></div>
                                <div class="product"><a href="<?php echo $prod_link; ?>" title="<?php echo $prod_title; ?>" target="_blank"><?php echo $prod_title; ?></a></div>
                                <div class="status">    <?php echo $user->status == 1 ? 'Sent' : 'Waiting'; ?></div>
                                <div class="remove">
                                    <form action="" method="POST">
                                        <input type="hidden" name="remove_date" value="<?php echo $user->date; ?>" />
                                        <input type="hidden" name="remove_email" value="<?php echo $user->user_email; ?>" />
                                        <input type="hidden" name="remove_product" value="<?php echo $user->product_id; ?>" />
                                        <input type="submit" name="remove_entry" value="remove" />
                                    </form>
                                </div>
                            </li>
                        <?php } ?>
                    </ul>
                    <div class="expand"><span>Show More</span></div>
                </td>
            </tr>
        </table>
    </div>
    <div style="clear:both;"></div>
    
    <?php
}

// Register Settings
add_action( 'admin_init', 'update_Product_stock_options' );
function update_Product_stock_options() {
    register_setting( 'Product_stock_option_settings', 'Product_stock_option_sender' );
    register_setting( 'Product_stock_option_settings', 'Product_stock_option_from' );
    register_setting( 'Product_stock_option_settings', 'Product_stock_option_subject' );
    register_setting( 'Product_stock_option_settings', 'Product_stock_option_message' );
    register_setting( 'Product_stock_option_settings', 'Product_stock_option_placeholder' );
    register_setting( 'Product_stock_option_settings', 'Product_stock_option_submit' );
    register_setting( 'Product_stock_option_settings', 'Product_stock_option_error' );
    register_setting( 'Product_stock_option_settings', 'Product_stock_option_success' );
    register_setting( 'Product_stock_option_settings', 'Product_stock_option_css' );
    register_setting( 'Product_stock_option_settings', 'Product_stock_option_shortcode' );
}

// Shortcode
add_shortcode( 'instock', 'Product_stock_alert_shortcode' );
function Product_stock_alert_shortcode() {
    $form = Product_stock_alert_form('get');
    echo $form;
}

// Add settings link on plugin page
$plugin = plugin_basename(__FILE__);
add_filter("plugin_action_links_$plugin", 'Product_stock_alert_settings_link' );
function Product_stock_alert_settings_link($links) {
  $settings_link = '<a href="options-general.php?page=instocksettings.php">Settings</a>';
  array_unshift($links, $settings_link);
  return $links;
}

