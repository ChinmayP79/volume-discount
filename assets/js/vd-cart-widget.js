(function($){
    // JS to refresh the Volume Discount cart widget on cart/checkout updates
    function refreshVdCartWidget(){
        if ( typeof vd_cart_widget_params === 'undefined' ) return;
        let $ = jQuery;

        if ($('.vd-cart-widget-discount-info').length <= 0) return;

        setTimeout(function(){
            $.ajax({
                url: vd_cart_widget_params.ajax_url,
                method: 'POST',
                caches: false,
                data: {
                    action: 'vd_get_cart_discounts',
                    nonce: vd_cart_widget_params.nonce
                },
                success: function(response){
                    if (response && response.success && response.data.html) {
                        $('.vd-cart-widget-discount-info').each(function(){
                            var $this = $(this);
                            $this.replaceWith(response.data.html);
                        });
                    }
                }
            });
        }, 1500);
    }

    $(document, 'button').on('click', function(){
        refreshVdCartWidget();
    });

    $(document, 'input[type="number"]').on('click', function(){
        refreshVdCartWidget();
    });
})(jQuery);
