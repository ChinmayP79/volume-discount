<?php
defined( 'ABSPATH' ) || exit; // Exit if accessed directly

// Check if user is eligible for volume discount
function vd_get_user_eligibility( $user_id = 0 ) {
    // Get current WP user ID if no user ID is provided
    if ( empty( $user_id ) ) {
        $current_user = wp_get_current_user(); // Get current WP user
        $user_id = $current_user->ID;
    }

    // Check user meta for eligibility
    $is_user_eligible = ( get_user_meta( $user_id, '_vd_user_eligible', true ) == 'yes' );

    return $is_user_eligible;
}

// Check if product or its categories are eligible for volume discount
function vd_get_product_eligibility( $product_id ) {
    $is_prod_eligible = $is_cat_eligible = false;

    // Get product object
    $product = wc_get_product( $product_id );

    // Check product eligibility
    $is_prod_eligible = ( $product->get_meta( '_vd_prod_eligible', true ) == 'yes' );

    // Check category eligibility
    $prod_category_ids = $product->get_category_ids();
    if ( ! empty( $prod_category_ids ) ) {
        $term_query = new WP_Term_Query( array(
            'taxonomy'   => 'product_cat',
            'include'    => $prod_category_ids,
            'meta_query' => array(
                array(
                    'key'     => '_vd_cat_eligible',
                    'value'   => 'yes',
                    'compare' => '=',
                ),
            ),
            'hide_empty' => false,
            'fields'     => 'ids',
        ) );

        // If any eligible categories found then mark as eligible
        $terms = $term_query->get_terms();
        if ( ! empty( $terms ) ) $is_cat_eligible = true;
    }

    return ( $is_prod_eligible || $is_cat_eligible );
}