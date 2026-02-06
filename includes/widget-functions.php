<?php
defined( 'ABSPATH' ) || exit; // Exit if accessed directly

function vd_cart_widget_discounts_display() {
    if ( !vd_get_user_eligibility() ) return; // No discount applied if user is not eligible

    // Start HTML
    ?><div class="vd-cart-widget-discount-info"><?php

    // Get the cart object
    $cart = WC()->cart;
    //var_dump($cart); exit;

    // Discount parameters
    $discount_matrix = get_option( 'vd_discount_matrix', VD_DEFAULT_DISCOUNT_MATRIX );
    $discount_percentage_ary = array_column($discount_matrix, 'discount'); // get discounts
    $volume_threshold_price_ary = array_column($discount_matrix, 'threshold'); // get thresholds

    $total_eligible_price = 0;
    $discounted_products = [];
    foreach ( $cart->get_cart() as $cart_item ) {
        // If either product or category is eligible, include in discount calculation
        if ( vd_get_product_eligibility( $cart_item['product_id'] ) ) {
            $discounted_products[] = $cart_item['product_id'];
            $original_price = !empty($cart_item['data']->get_sale_price()) ? $cart_item['data']->get_sale_price() : $cart_item['data']->get_regular_price();
            $total_eligible_price += $original_price * $cart_item['quantity'];
        }
    }

    // Only display if there are eligible products in the cart
    if ( !empty( $discounted_products ) ) {

        // Display the discount information
        if ( $total_eligible_price >= min($volume_threshold_price_ary) ) {
            $selected_index = 0;
            foreach ( $volume_threshold_price_ary as $index => $threshold ) {
                if ( $total_eligible_price >= $threshold ) {
                    $selected_index = $index;
                }
            }

            $discount_percentage = $discount_percentage_ary[$selected_index]; // get discount

            ?>
            <div class="vd-cart-applied-discount-info">
                <h3><?php echo esc_html__( 'Volume Discount Applied!', 'volume-discount' ); ?></h3>
                <p><?php echo sprintf( esc_html__( 'You have received a %s%% discount on eligible products.', 'volume-discount' ), esc_html( $discount_percentage ) ); ?></p>
            </div>
            <?php
        }

        // Show next discount info
        $next_threshold_index = null;
        foreach ( $volume_threshold_price_ary as $index => $threshold ) {
            if ( $total_eligible_price < $threshold ) {
                $next_threshold_index = $index;
                break;
            }
        }

        if ( !is_null( $next_threshold_index ) ) {
            $next_threshold = $volume_threshold_price_ary[$next_threshold_index];
            $next_discount = $discount_percentage_ary[$next_threshold_index];
            $amount_needed = $next_threshold - $total_eligible_price;

            // Determine segmented progress values based on thresholds
            $max_threshold = (float) end( $volume_threshold_price_ary );
            if ( $max_threshold <= 0 ) $fill_percentage = 0;
            else $fill_percentage = min( 100, ( $total_eligible_price / $max_threshold ) * 100 );

            ?>
            <div class="vd-cart-next-discount-info">
                <h3><?php echo esc_html__( 'Increase Your Savings!', 'volume-discount' ); ?></h3>
                <p><?php echo sprintf( esc_html__( 'Spend %s more to get a %s%% discount on eligible products.', 'volume-discount' ), wc_price( $amount_needed ), esc_html( $next_discount ) ); ?></p>

                <div class="vd-segmented-progress" aria-hidden="true">
                    <div class="vd-track">
                        <div class="vd-fill" style="width:<?php echo esc_attr( $fill_percentage ); ?>%;"></div>
                        <div class="vd-node" style="left:<?php echo esc_attr( 0 ); ?>%;">
                            <span class="vd-node-circle vd-node-reached"></span>
                            <span class="vd-node-label">0%</span>
                        </div>
                        <?php foreach ( $volume_threshold_price_ary as $index => $threshold ) {
                            $pos = 0;
                            if ( $max_threshold > 0 ) $pos = ( (float) $threshold / $max_threshold ) * 100;
                            $reached = ( $total_eligible_price >= $threshold );
                            ?>
                            <div class="vd-node" style="left:<?php echo esc_attr( $pos ); ?>%;">
                                <span class="vd-node-circle<?php echo $reached ? ' vd-node-reached' : ''; ?>"></span>
                                <span class="vd-node-label"><?php echo esc_html( $discount_percentage_ary[$index] ); ?>%</span>
                            </div>
                        <?php } ?>
                    </div>
                </div>
            </div>
            <?php
        }
    }

    // End HTML
    ?></div><?php
}

// Shortcode wrapper: captures the output of the display function and returns it
function vd_cart_widget_discounts_shortcode() {
    ob_start();
    vd_cart_widget_discounts_display();
    return ob_get_clean();
}

// Register the shortcode on init
add_action( 'init', 'vd_register_cart_discounts_shortcode' );
function vd_register_cart_discounts_shortcode() {
    add_shortcode( 'vd_cart_discounts', 'vd_cart_widget_discounts_shortcode' );
}

// AJAX endpoint: return the shortcode HTML for refreshing the widget via JS
add_action( 'wp_ajax_vd_get_cart_discounts', 'vd_get_cart_discounts_ajax' );
add_action( 'wp_ajax_nopriv_vd_get_cart_discounts', 'vd_get_cart_discounts_ajax' );
function vd_get_cart_discounts_ajax() {
    check_ajax_referer( 'vd_cart_widget_nonce', 'nonce' );

    // Return the rendered shortcode HTML
    $html = do_shortcode( '[vd_cart_discounts]' );

    wp_send_json_success( array( 'html' => $html ) );
}

// Enqueue frontend script that refreshes the shortcode on cart changes
add_action( 'wp_enqueue_scripts', 'vd_enqueue_cart_widget_script' );
function vd_enqueue_cart_widget_script() {
    if ( is_admin() ) return;

    // Enqueue widget script
    $handle = 'vd-cart-widget';
    $src = VD_PLUGIN_URL . 'assets/js/vd-cart-widget.js';
    wp_enqueue_script( $handle, $src, array( 'jquery' ), time() );

    // Enqueue widget styles
    $css_handle = 'vd-cart-widget-style';
    $css_src = VD_PLUGIN_URL . 'assets/css/vd-cart-widget.css';
    wp_enqueue_style( $css_handle, $css_src, array(), time() );

    // Localize script with AJAX parameters
    wp_localize_script( $handle, 'vd_cart_widget_params', array(
        'ajax_url' => admin_url( 'admin-ajax.php' ),
        'nonce'    => wp_create_nonce( 'vd_cart_widget_nonce' ),
    ) );
}

