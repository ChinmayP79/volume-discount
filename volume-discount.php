<?php
/*
Plugin Name: Volume Discount for WooCommerce
Description: Volume Discount for WooCommerce by Chinmay
Version: 1.0
Author: Chinmay
*/

defined( 'ABSPATH' ) || exit; // Exit if accessed directly

define('VD_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('VD_PLUGIN_URL', plugin_dir_url(__FILE__));
define('VD_DEFAULT_DISCOUNT_MATRIX', [
    ['threshold' => 5000, 'discount' => 2],
    ['threshold' => 10000, 'discount' => 5],
    ['threshold' => 20000, 'discount' => 10],
]);

// Register Custom Functions
require_once VD_PLUGIN_DIR . 'includes/common-functions.php';
require_once VD_PLUGIN_DIR . 'includes/woo-com-hook-functions.php';
require_once VD_PLUGIN_DIR . 'includes/profile-edit-functions.php';
require_once VD_PLUGIN_DIR . 'includes/product-edit-functions.php';
require_once VD_PLUGIN_DIR . 'includes/category-edit-functions.php';
require_once VD_PLUGIN_DIR . 'includes/settings-option-functions.php';
require_once VD_PLUGIN_DIR . 'includes/widget-functions.php';
