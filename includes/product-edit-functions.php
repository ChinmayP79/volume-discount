<?php
defined( 'ABSPATH' ) || exit; // Exit if accessed directly

// Product edit metabox to set '_vd_prod_eligible' meta
add_action( 'add_meta_boxes', 'vd_add_product_eligible_metabox' );
function vd_add_product_eligible_metabox() {
    add_meta_box(
        'vd_prod_eligible',
        'Volume Discount',
        'vd_product_eligible_metabox_callback',
        'product',
        'side',
        'default'
    );
}

// Metabox callback to display '_vd_prod_eligible' checkbox
function vd_product_eligible_metabox_callback( $post ) {
    $product = wc_get_product( $post->ID );
    $value = $product->get_meta( '_vd_prod_eligible', true );
    $checked = ( $value === 'yes' ) ? 'checked' : '';
    ?>
    <p>
        <label for="vd_prod_eligible_field">
            <input type="checkbox" id="vd_prod_eligible_field" name="vd_prod_eligible" value="yes" <?php echo $checked; ?> />
            <?php esc_html_e( 'Eligible for volume discount', 'volume-discount' ); ?>
        </label>
    </p>
    <?php
}

// Save metabox data for '_vd_prod_eligible' meta
add_action( 'save_post', 'vd_save_product_eligible_meta', 10, 2 );
function vd_save_product_eligible_meta( $post_id, $post ) {
    if ( $post->post_type !== 'product' ) return;
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
    if ( ! current_user_can( 'edit_post', $post_id ) ) return;

    $value = ( isset( $_POST['vd_prod_eligible'] ) && 'yes' === $_POST['vd_prod_eligible'] ) ? 'yes' : 'no';

    $product = wc_get_product( $post->ID );
    $product->update_meta_data( '_vd_prod_eligible', $value );
    $product->save(); // Ensure product data is saved
}
