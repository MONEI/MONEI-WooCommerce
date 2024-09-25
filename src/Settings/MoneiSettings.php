<?php

class MoneiSettings extends WC_Settings_Page
{
    public function __construct()
    {
        $this->id = 'monei_settings';
        $this->label = __('Monei Settings', 'monei');
        parent::__construct();
    }

    public function get_settings()
    {
        $settings = array(
            array(
                'title' => __('Monei Settings', 'monei'),
                'type' => 'title',
                'id' => 'monei_settings_title'
            ),
            array(
                'title' => __('Account ID *', 'monei'),
                'type' => 'text',
                'desc' => __('Enter your Monei Account ID here.', 'monei'),
                'desc_tip' => true,
                'id' => 'monei_accountid',
                'default' => '',
            ),
            array(
                'title' => __('API Key *', 'monei'),
                'type' => 'text',
                'desc' => wp_kses_post(
                    __(
                        'You can find your API key in <a href="https://dashboard.monei.com/settings/api" target="_blank">Monei Dashboard</a>.<br/>Account ID and API key for the test mode are different from the live mode and can only be used for testing purposes.',
                        'monei'
                    )
                ),
                'desc_tip' => __('Your Monei API Key. It can be found in your Monei Dashboard.', 'monei'),
                'id' => 'monei_apikey',
                'default' => '',
            ),
            array(
                'title' => __('Debug Log', 'monei'),
                'type' => 'checkbox',
                'label' => __('Enable logging', 'monei'),
                'default' => 'no',
                'desc' => __('Log Monei events inside WooCommerce > Status > Logs > Select Monei Logs.', 'monei'),
                'desc_tip' => __('Enable logging to track events such as notifications requests.', 'monei'),
                'id' => 'monei_debug',
            ),
            array(
                'type' => 'sectionend',
                'id' => 'monei_settings_sectionend'
            )
        );

        return apply_filters('woocommerce_get_settings_' . $this->id, $settings);
    }

    public function output()
    {
        $settings = $this->get_settings();
        WC_Admin_Settings::output_fields($settings);
    }

    public function save()
    {
        $settings = $this->get_settings();
        WC_Admin_Settings::save_fields($settings);
    }
}