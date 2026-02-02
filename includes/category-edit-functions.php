<?php
defined( 'ABSPATH' ) || exit; // Exit if accessed directly

// Add admin fields to product category add page to set `_vd_cat_eligible` term meta
add_action( 'product_cat_add_form_fields', 'vd_product_cat_add_form_field', 10, 2 );
function vd_product_cat_add_form_field( $taxonomy ) {
    ?>
    <div class="form-field term-vd-cat-eligible-wrap">
        <label for="vd_cat_eligible"><?php esc_html_e( 'Eligible for Volume Discount', 'volume-discount' ); ?></label>
        <input name="vd_cat_eligible" id="vd_cat_eligible" type="checkbox" value="yes" />
        <?php esc_html_e( 'Enable to grant this category volume discount eligibility', 'volume-discount' ); ?>
        <p class="description"><?php esc_html_e( 'Check to mark this category as eligible for the volume discount.', 'volume-discount' ); ?></p>
    </div>
    <?php
}

// Add admin fields to product category edit page to set `_vd_cat_eligible` term meta
add_action( 'product_cat_edit_form_fields', 'vd_product_cat_edit_form_field', 10, 2 );
function vd_product_cat_edit_form_field( $term, $taxonomy ) {
    $value = get_term_meta( $term->term_id, '_vd_cat_eligible', true );
    ?>
    <tr class="form-field term-vd-cat-eligible-wrap">
        <th scope="row"><label for="vd_cat_eligible"><?php esc_html_e( 'Eligible for Volume Discount', 'volume-discount' ); ?></label></th>
        <td>
            <input name="vd_cat_eligible" id="vd_cat_eligible" type="checkbox" value="yes" <?php checked( $value, 'yes' ); ?> />
            <?php esc_html_e( 'Enable to grant this category volume discount eligibility', 'volume-discount' ); ?>
            <p class="description"><?php esc_html_e( 'Check to mark this category as eligible for the volume discount.', 'volume-discount' ); ?></p>
        </td>
    </tr>
    <?php
}

// Save term meta when category is created or edited
add_action( 'created_product_cat', 'vd_save_product_cat_meta', 10, 2 );
add_action( 'edited_product_cat', 'vd_save_product_cat_meta', 10, 2 );
function vd_save_product_cat_meta( $term_id ) {
    if ( ! current_user_can( 'manage_categories' ) ) return;

    $value = isset( $_POST['vd_cat_eligible'] ) ? sanitize_text_field( wp_unslash( $_POST['vd_cat_eligible'] ) ) : 'no';
    update_term_meta( $term_id, '_vd_cat_eligible', ( 'yes' === $value ) ? 'yes' : 'no' );
}

