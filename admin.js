jQuery(document).ready(function ($) {
    $('#woocommerce_monei_primary_color').wpColorPicker();
    $('#woocommerce_monei_card_supported').chosen();
    $('#woocommerce_monei_popup').change(function () {
        if (this.checked) {
            $('#woocommerce_monei_popup_config, #woocommerce_monei_popup_config + .form-table').show();
        } else {
            $('#woocommerce_monei_popup_config, #woocommerce_monei_popup_config + .form-table').hide();
        }
    }).change();
});

