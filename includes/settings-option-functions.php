<?php
defined( 'ABSPATH' ) || exit; // Exit if accessed directly

// Admin settings: allow storing the discount matrix in an option (JSON-editable)
if ( is_admin() ) {
    add_action( 'admin_menu', 'vd_add_settings_page' );
    add_action( 'admin_init', 'vd_register_settings' );
}

// Add settings page under Settings menu
function vd_add_settings_page() {
    add_options_page( 'Volume Discount', 'Volume Discount', 'manage_options', 'vd-volume-discount', 'vd_render_settings_page' );
}

// Register settings
function vd_register_settings() {
    register_setting( 'vd_settings_group', 'vd_discount_matrix', 'vd_sanitize_matrix' );
}

// Sanitize and validate the discount matrix input
function vd_sanitize_matrix( $input ) {
    $default = VD_DEFAULT_DISCOUNT_MATRIX;

    // Accept JSON string or array
    $data = ( is_string( $input ) ) ? json_decode( $input, true ) : $input;

    if ( ! is_array( $data ) ) return $default;

    $out = [];
    foreach ( $data as $row ) {
        if ( ! is_array( $row ) ) continue;
        $threshold = isset( $row['threshold'] ) ? floatval( $row['threshold'] ) : null;
        $discount  = isset( $row['discount'] )  ? floatval( $row['discount'] )  : null;
        if ( $threshold === null || $discount === null ) continue;
        $out[] = [ 'threshold' => $threshold, 'discount' => $discount ];
    }

    if ( empty( $out ) ) return $default;

    // ensure sorting by threshold
    usort( $out, function( $a, $b ) { return floatval( $a['threshold'] ) <=> floatval( $b['threshold'] ); } );
    return $out;
}

// Render the settings page
function vd_render_settings_page() {
    if ( ! current_user_can( 'manage_options' ) ) return;

    $default = VD_DEFAULT_DISCOUNT_MATRIX;

    $opt = get_option( 'vd_discount_matrix', $default );
    if ( ! is_array( $opt ) ) {
        // If option was stored as JSON string, try decode
        if ( is_string( $opt ) ) {
            $decoded = json_decode( $opt, true );
            $opt = is_array( $decoded ) ? $decoded : $default;
        } else {
            $opt = $default;
        }
    }

    ?>
    <div class="wrap">
        <h1>Volume Discount Settings</h1>
        <form method="post" action="options.php">
            <?php settings_fields( 'vd_settings_group' ); ?>

            <p>Manage the discount tiers. Rows are applied by ascending threshold.</p>

            <style>
                #vd-discount-table input[type="number"] {
                    width: 100%;
                }
            </style>

            <table class="widefat fixed" id="vd-discount-table" style="max-width:720px">
                <thead>
                    <tr>
                        <th style="width:55%">Threshold (price)</th>
                        <th style="width:30%">Discount (%)</th>
                        <th style="width:15%">&nbsp;</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ( $opt as $i =>$row ) :
                    $threshold = isset( $row['threshold'] ) ? $row['threshold'] : '';
                    $discount  = isset( $row['discount'] )  ? $row['discount']  : '';
                ?>
                    <tr data-id="<?php echo esc_attr( $i ); ?>">
                        <td>
                            <input type="number" step="0.01" min="0" name="vd_discount_matrix[<?php echo esc_attr( $i ); ?>][threshold]" value="<?php echo esc_attr( $threshold ); ?>" class="regular-text" />
                        </td>
                        <td>
                            <input type="number" step="0.01" min="0" name="vd_discount_matrix[<?php echo esc_attr( $i ); ?>][discount]" value="<?php echo esc_attr( $discount ); ?>" class="regular-text" />
                        </td>
                        <td>
                            <button type="button" class="button vd-remove-row">Remove</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>

            <p>
                <button type="button" id="vd-add-row" class="button">Add row</button>
            </p>

            <?php submit_button(); ?>
        </form>
    </div>

    <script>
    jQuery(function($){
        // Function to create a new table row
        function createRow() {
            let cnt = $('#vd-discount-table tbody tr:last').data('id') || 0;
            let $tr = $('<tr>');
            let $td1 = $('<td>').append(
                $('<input>', { type: 'number', step: '0.01', min: '0', name: `vd_discount_matrix[${cnt+1}][threshold]`, value: '', 'class': 'regular-text' })
            );
            let $td2 = $('<td>').append(
                $('<input>', { type: 'number', step: '0.01', min: '0', name: `vd_discount_matrix[${cnt+1}][discount]`, value: '', 'class': 'regular-text' })
            );
            let $td3 = $('<td>').append(
                $('<button>', { type: 'button', 'class': 'button vd-remove-row', text: 'Remove' })
            );
            $tr.append($td1, $td2, $td3);
            return $tr;
        }

        // Add new row
        $('#vd-add-row').on('click', function(e){
            e.preventDefault();
            $('#vd-discount-table tbody').append( createRow() );
        });

        // Remove row
        $('#vd-discount-table').on('click', '.vd-remove-row', function(e){
            e.preventDefault();
            $(this).closest('tr').remove();
        });
    });
    </script>
    <?php
}


