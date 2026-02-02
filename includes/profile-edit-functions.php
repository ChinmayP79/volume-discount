<?php
defined( 'ABSPATH' ) || exit; // Exit if accessed directly

// Add volume-discount eligibility field to user profile pages
add_action( 'show_user_profile', 'vd_user_profile_field' );
add_action( 'edit_user_profile', 'vd_user_profile_field' );
function vd_user_profile_field( $user ) {
    $meta = get_user_meta( $user->ID, '_vd_user_eligible', true );
    if ( $meta !== 'yes' ) $meta = 'no';
    ?>
    <h2><?php esc_html_e( 'Volume Discount', 'volume-discount' ); ?></h2>
    <table class="form-table">
        <tr>
            <th><label for="vd_user_eligible"><?php esc_html_e( 'Eligible for volume discount', 'volume-discount' ); ?></label></th>
            <td>
                <label for="vd_user_eligible" style="display:flex;align-items:center;gap:8px;">
                    <input type="checkbox" id="vd_user_eligible" name="vd_user_eligible" value="yes" <?php checked( $meta, 'yes' ); ?> />
                    <?php esc_html_e( 'Enable to grant this user volume discount eligibility', 'volume-discount' ); ?>
                </label>
                <p class="description"><?php esc_html_e( 'Check the box to enable.', 'volume-discount' ); ?></p>
            </td>
        </tr>
    </table>
    <?php
}

// Save the volume-discount eligibility when profile is updated
add_action( 'personal_options_update', 'vd_save_user_profile_field' );
add_action( 'edit_user_profile_update', 'vd_save_user_profile_field' );
function vd_save_user_profile_field( $user_id ) {
    if ( ! current_user_can( 'edit_user', $user_id ) ) return false;

    $value = isset( $_POST['vd_user_eligible'] ) ? sanitize_text_field( wp_unslash( $_POST['vd_user_eligible'] ) ) : 'no';
    update_user_meta( $user_id, '_vd_user_eligible', ( 'yes' === $value ) ? 'yes' : 'no' );
}
