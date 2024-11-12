<?php
use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

final class WC_Chargily_Pay_Blocks extends AbstractPaymentMethodType {
    protected $name = 'chargily_pay';
    protected $settings;

    public function initialize() {
        $this->settings = get_option('woocommerce_chargily_pay_settings', []);
        $this->settings = array_map('sanitize_text_field', $this->settings);
    }

    public function is_active() {
        return isset($this->settings['enabled']) && 'yes' === $this->settings['enabled'];
    }

    public function get_payment_method_script_handles() {
        wp_register_script(
			'chargily-pay-blocks-integration',
			plugins_url('../../assets/js/checkout.js', __FILE__),
			['wc-blocks-registry', 'wc-settings', 'wp-element', 'wp-i18n'],
			filemtime(plugin_dir_path(__FILE__) . '../../assets/js/checkout.js'),
			true
		);
		wp_localize_script(
			'chargily-pay-blocks-integration',
			'chargilySettings',
			[
				'title'       => isset($this->settings['title']) ? esc_html($this->settings['title']) : '',
				'description' => isset($this->settings['description']) ? esc_html($this->settings['description']) : '',
				'show_payment_methods' => isset($this->settings['show_payment_methods']) ? esc_html($this->settings['show_payment_methods']) : '',
			]
		);
        return ['chargily-pay-blocks-integration'];
    }

	public function get_payment_method_data() {
		$title = isset($this->settings['title']) ? esc_html($this->settings['title']) : __('Chargily Pay™', 'chargilytextdomain');
		$description = isset($this->settings['description']) ? esc_html($this->settings['description']) : '';
		return [
			'title'       => $title,
			'description' => $description,
		];
	}
}
