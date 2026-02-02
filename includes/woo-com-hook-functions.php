<?php
defined( 'ABSPATH' ) || exit; // Exit if accessed directly

// Apply volume discount to specific products based on user eligibility and product or category eligibility
add_action( 'woocommerce_before_calculate_totals', 'vd_custom_discount_specific_products', 10, 1 );
function vd_custom_discount_specific_products( WC_Cart $cart ) {
    if ( is_admin() && ! defined( 'DOING_AJAX' ) ) return;

    if ( !vd_get_user_eligibility() ) return; // No discount applied if user is not eligible

    // Discount parameters
    $discount_matrix = get_option( 'vd_discount_matrix', VD_DEFAULT_DISCOUNT_MATRIX );
    $discount_percentage_ary = array_column($discount_matrix, 'discount'); // get discounts
    $volume_threshold_price_ary = array_column($discount_matrix, 'threshold'); // get thresholds

    // Calculate total eligible price
    $total_eligible_price = 0;
    $discounted_products = [];
    foreach ( $cart->get_cart() as $cart_item ) {
        // If either product or category is eligible, include in discount calculation
        if ( vd_get_product_eligibility( $cart_item['product_id'] ) ) {
            $discounted_products[] = $cart_item['product_id'];
            $total_eligible_price += $cart_item['data']->get_price() * $cart_item['quantity'];
        }
    }

    // Apply discount if total eligible price meets any threshold
    if ( $total_eligible_price >= min($volume_threshold_price_ary) ) {
        $selected_index = 0;
        foreach ( $volume_threshold_price_ary as $index => $threshold ) {
            if ( $total_eligible_price >= $threshold ) {
                $selected_index = $index;
            }
        }

        $discount_percentage = $discount_percentage_ary[$selected_index]; // get discount

        foreach ( $cart->get_cart() as $cart_item_key => $cart_item ) {
            if ( in_array( $cart_item['product_id'], $discounted_products ) ) {
                $original_price = !empty($cart_item['data']->get_sale_price()) ? $cart_item['data']->get_sale_price() : $cart_item['data']->get_regular_price();
                $cart_item['data']->set_price( $original_price * ( 1 - ( $discount_percentage / 100 ) ) );

                // Attach metadata to the cart item so we can identify discounted items later
                $cart->cart_contents[ $cart_item_key ]['vd_meta']['vd_discount_applied'] = true;
                $cart->cart_contents[ $cart_item_key ]['vd_meta']['vd_original_price'] = $original_price;
                $cart->cart_contents[ $cart_item_key ]['vd_meta']['vd_discount_percentage'] = $discount_percentage;
            }
        }
    } else { // Reset prices if below threshold
        foreach ( $cart->get_cart() as $cart_item_key => $cart_item ) {
            if ( in_array( $cart_item['product_id'], $discounted_products ) ) {
                $original_price = !empty($cart_item['data']->get_sale_price()) ? $cart_item['data']->get_sale_price() : $cart_item['data']->get_regular_price();
                $cart_item['data']->set_price( $original_price ); // reset to original price

                // Remove any metadata we added when discount was applied
                if ( isset( $cart->cart_contents[ $cart_item_key ]['vd_meta'] ) ) {
                    unset( $cart->cart_contents[ $cart_item_key ]['vd_meta'] );
                }
            }
        }
    }
}

// Show discount info in cart and checkout line items
add_filter( 'woocommerce_get_item_data', 'vd_show_discount_in_cart', 10, 2 );
function vd_show_discount_in_cart( $item_data, $cart_item ) {
    if ( isset( $cart_item['vd_meta']['vd_discount_applied'] ) && $cart_item['vd_meta']['vd_discount_applied'] ) {
        $item_data[] = array(
            'name'  => __( 'Volume Discount', 'volume-discount' ),
            'value' => wc_price($cart_item['vd_meta']['vd_original_price']) . esc_html( ' - ' . $cart_item['vd_meta']['vd_discount_percentage'] . '%' ),
        );
    }
	return $item_data;
}

// Copy cart item discount meta into order line items at checkout so it persists
add_action( 'woocommerce_checkout_create_order_line_item', 'vd_add_discount_meta_to_order_items', 10, 4 );
function vd_add_discount_meta_to_order_items( $item, $cart_item_key, $values, $order ) {
    if ( empty( $values['vd_meta'] ) || empty( $values['vd_meta']['vd_discount_applied'] ) ) return;

    $item->add_meta_data( 'Original Price', isset( $values['vd_meta']['vd_original_price'] ) ? wc_price( $values['vd_meta']['vd_original_price'] ) : '', true );
    $item->add_meta_data( 'Volume Discount Applied', sprintf( __( 'Yes, of %s%% only.', 'volume-discount' ), $values['vd_meta']['vd_discount_percentage']), true );
}
