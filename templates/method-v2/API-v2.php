<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Check if WooCommerce is activated
if ( ! function_exists( 'is_woocommerce_activated' ) ) {
    function is_woocommerce_activated() {
        return class_exists( 'WooCommerce' );
    }
}
add_action( 'plugins_loaded', 'chargily_check_woocommerce' );

function chargily_check_woocommerce() {
    if ( ! is_woocommerce_activated() ) {
        add_action( 'admin_notices', 'chargily_woocommerce_not_active' );
        return;
    } else {
    	add_action( 'plugins_loaded', 'wc_chargily_pay_init', 11 );
    }
}

function chargily_woocommerce_not_active() {
    echo '<div class="notice notice-error"><p>';
    _e( 'Chargily Pay requires WooCommerce to be installed and activated.', 'chargilytextdomain' );
    echo '</p></div>';
}

// Add the gateway to WC Available Gateways
function wc_chargilyv2_add_to_gateways( $gateways ) {
    $gateways[] = 'WC_chargily_pay';
    return $gateways;
}
add_filter( 'woocommerce_payment_gateways', 'wc_chargilyv2_add_to_gateways' );

function wc_chargily_pay_init() {

    class WC_chargily_pay extends WC_Payment_Gateway {

        public function __construct() {
            $this->id                 = 'chargily_pay';
            $this->icon               = apply_filters('woocommerce_chargilyv2_icon', '');
            $this->has_fields         = false;
            $this->method_title       = __( 'Chargily Pay™', '' );
            $this->method_description = __( 'Allow your customers to make payments using their Edahabia and CIB cards using Chargily Pay™ V2.', '' );

            // Load the settings.
            $this->init_form_fields();
            $this->init_settings();

            // Define user set variables
            $this->title        = $this->get_option( 'title' );
            $this->description  = $this->get_option( 'description' );
            
            // Actions
            add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
            add_action( 'woocommerce_receipt_' . $this->id, array( $this, 'receipt_page' ) );
            // Customer Emails
            add_action( 'woocommerce_email_before_order_table', array( $this, 'email_instructions' ), 10, 3 );
            // admin api notices
            add_action('admin_notices', array($this, 'display_chargily_admin_notices'));
			
            add_action('woocommerce_update_options_payment_gateways_chargily_pay', array($this, 'update_chargily_pay_settings'));
        }

        public function init_form_fields() {
            $this->form_fields = array(
                'enabled' => array(
			'title'       => __('Enable/Disable', 'chargilytextdomain'),
			'label'       => __('Enable Chargily Pay', 'chargilytextdomain'),
			'type'        => 'checkbox',
			'description' => '',
			'default'     => 'no'
			),
			'test_mode' => array(
			'title'       => __('Test mode', 'chargilytextdomain'),
			'label'       => __('Enable Test Mode', 'chargilytextdomain'),
			'type'        => 'checkbox',
			'description' => __('If enabled, you will use Chargily Pay in Test Mode.', 'chargilytextdomain'),
			'default'     => 'yes',
			'desc_tip'    => true,
			),
			'Chargily_Gateway_api_key_v2_test' => array(
			'title'       => __('Test Public key', 'chargilytextdomain'),
			'type'        => 'password',
			'description' => __('Enter your Chargily Test API key.', 'chargilytextdomain'),
			'default'     => '',
			'desc_tip'    => true,
			),
			'Chargily_Gateway_api_secret_v2_test' => array(
			'title'       => __('Test Secret key', 'chargilytextdomain'),
			'type'        => 'password',
			'description' => __('Enter your Chargily Test API secret.', 'chargilytextdomain'),
			'default'     => '',
			'desc_tip'    => true,
			),
			'Chargily_Gateway_api_authorization_v2_test' => array(
			'title'       => __('Check API keys', 'chargilytextdomain'),
			'type'        => 'button',
			'description' => __('Check your API keys.', 'chargilytextdomain'),
			'default'     => __('Check connection', 'chargilytextdomain'),
			'desc_tip'    => true,
			),
			'Chargily_Gateway_api_key_v2_live' => array(
			'title'       => __('Live Public key', 'chargilytextdomain'),
			'type'        => 'password',
			'description' => __('Enter your Chargily Live API key.', 'chargilytextdomain'),
			'default'     => '',
			'desc_tip'    => true,
			),
			'Chargily_Gateway_api_secret_v2_live' => array(
			'title'       => __('Live Secret key', 'chargilytextdomain'),
			'type'        => 'password',
			'description' => __('Enter your Chargily Live API secret.', 'chargilytextdomain'),
			'default'     => '',
			'desc_tip'    => true,
			),
			'Chargily_Gateway_api_authorization_v2_live' => array(
			'title'       => __('Check API keys', 'chargilytextdomain'),
			'type'        => 'button',
			'description' => __('Check your API keys.', 'chargilytextdomain'),
			'default'     => __('Check connection', 'chargilytextdomain'),
			'desc_tip'    => true,
			),
			'title' => array(
			'title'       => __('Title', 'chargilytextdomain'),
			'type'        => 'text',
			'description' => __('This controls the title which the user sees during checkout.', 'chargilytextdomain'),
			'default'     => __('Chargily Pay™', 'chargilytextdomain'),
			'desc_tip'    => true,
			),
			'description' => array(
			'title'       => __('Description', 'chargilytextdomain'),
			'type'        => 'textarea',
			'description' => __('This controls the description which the user sees during checkout.', 'chargilytextdomain'),
			'default'     => __('🔒 Secure e-payment gateway.', 'chargilytextdomain'),
			'desc_tip'    => true,
			),
			'instructions' => array(
			'title'       => __('On the thanks page', 'chargilytextdomain'),
			'type'        => 'textarea',
			'placeholder' => __('thank you, the product will come soon.', 'chargilytextdomain'),
			'description' => __('Place the message you want to appear on the thank you page after completing the purchase of the product.', 'chargilytextdomain'),
			'default'     => __('', 'chargilytextdomain'),
			'desc_tip'    => true,
			),
			'chargily_pay_fees_allocation' => array(
			'title'       => __('Fees allocation', 'chargilytextdomain'),
			'type'        => 'select',
			'options'     => array(
				'customer'  => __('The customer will pay the fees.', 'chargilytextdomain'),
				'merchant'    => __('The store will pay the fees.', 'chargilytextdomain'),
				'split' => __('Splitted between the store and the customer.', 'chargilytextdomain')
			),
			'description' => __('Choose who is going to pay Chargily Pay fees.', 'chargilytextdomain'),
			'default'     => 'customer',
			'desc_tip'    => true,
			),
			'collect_shipping_address' => array(
			'title'       => __('Collect Shipping Address', 'chargilytextdomain'),
			'label'       => __('Collect shipping address on checkout page.', 'chargilytextdomain'),
			'type'        => 'checkbox',
			'description' => __('If enabled, shipping address fields will appear and should be filled on checkout page.', 'chargilytextdomain'),
			'default'     => 'yes'
			),
			'fix_for_compatibility_plugins' => array(
			'title'       => __('Force Chargily Pay plugin styling', 'chargilytextdomain'),
			'label'       => __('Fix styling compatibility.', 'chargilytextdomain'),
			'type'        => 'checkbox',
			'description' => __('If the style of the Chargily Pay plugin is compromised due to a styling modification by another plugin, activating this option will rectify the issue.', 'chargilytextdomain'),
			'default'     => 'no'
			),
			'languages_type' => array(
			'title'       => __('Select the language of the payment page', 'chargilytextdomain'),
			'type'        => 'select',
			'options'     => array(
				'en'  => __('English', 'chargilytextdomain'),
				'ar'    => __('Arabic', 'chargilytextdomain'),
				'fr' => __('French', 'chargilytextdomain')
			),
			'description' => __('The language that will appear on chargily payment page', 'chargilytextdomain'),
			'default'     => 'en',
			'desc_tip'    => true,
			),
			'response_type' => array(
			'title'       => __('Confirmation status', 'chargilytextdomain'),
			'type'        => 'select',
			'options'     => array(
				'completed'  => __('completed', 'chargilytextdomain'),
				'on-hold'    => __('on hold', 'chargilytextdomain'),
				'processing' => __('processing', 'chargilytextdomain')
			),
			'description' => __('This status will be set when the payment succeeds.', 'chargilytextdomain'),
			'default'     => 'completed',
			'desc_tip'    => true,
			),
			'show_payment_methods' => array(
			'title'       => __('Show payment methods', 'chargilytextdomain'),
			'label'       => __('Show or hide the payment methods in checkout page.', 'chargilytextdomain'),
			'type'        => 'checkbox',
			'description' => __('When enabled, the payment methods (Edahabia, CIB, and QR Code) will be displayed prominently for user selection, taking up additional space on the checkout page.', 'chargilytextdomain'),
			'default'     => 'yes'
			),	
			'webhook_rewrite_rule' => array(
			'title'       => __('Webhook Type', 'chargilytextdomain'),
			'label'       => __('Enable this option if your server support .htaccess file rewriting', 'chargilytextdomain'),
			'type'        => 'checkbox',
			'description' => sprintf(
			__('If enabled, Webhook will use the .htaccess rewrite rule method. Please re-save the Permalink settings again <a href="%s" target="_blank">Permalink</a>.', 'chargilytextdomain'),
			'/wp-admin/options-permalink.php'
			),
			'default'     => 'no'
			),
		// END
	    );
	}
		
		 public function admin_options() {
        		?>
			 <div style=" margin: 24px auto 0px; max-width: 1032px;">
				 <link rel="stylesheet" href="/wp-content/plugins/chargily-pay/assets/css/css-back.css?v=1.0">
				 <div class="css-q70wzv et1p4me2" style="display: flex;flex-flow: column;margin-bottom: 24px;  flex-direction: row;">
					 <div style="float: left; width: 30%;">
						 <div class="css-1p8kjge et1p4me1">
							 <h2><?php echo esc_html__( 'General', 'chargilytextdomain' ); ?></h2>
							 <p><?php echo esc_html__( 'Activate or deactivate Chargily Pay on your store, input your API keys, and activate test mode to simulate purchases without real money.', 'chargilytextdomain' ); ?></p>
							 <p><a class="components-external-link" href="https://dev.chargily.com/pay-v2/api-keys" target="_blank" rel="external noreferrer noopener">
								 <?php echo esc_html__( 'Find out where to find your API keys', 'chargilytextdomain' ); ?>
								 <span data-wp-c16t="true" data-wp-component="VisuallyHidden" class="components-visually-hidden css-0 e19lxcc00" style="">
								 (<?php echo esc_html__( 'opens in a new tab', 'chargilytextdomain' ); ?>)
								 </span>
								 <img src="/wp-content/plugins/chargily-pay/assets/img/link-out.svg" alt="link">
							 </a></p>
							 <p><a class="components-external-link" href="https://dev.chargily.com/pay-v2/test-and-live-mode" target="_blank" rel="external noreferrer noopener">
								<?php echo esc_html__( 'Understand Test and Live modes', 'chargilytextdomain' ); ?>
								 <span data-wp-c16t="true" data-wp-component="VisuallyHidden" class="components-visually-hidden css-0 e19lxcc00" style="">
								 (<?php echo esc_html__( 'opens in a new tab', 'chargilytextdomain' ); ?>)
								 </span>
								 <img src="/wp-content/plugins/chargily-pay/assets/img/link-out.svg" alt="link">
							 </a></p>
							 <p><a class="components-external-link" href="https://chargi.link/WaPay" target="_blank" rel="external noreferrer noopener">
								 <?php echo esc_html__( 'Get support', 'chargilytextdomain' ); ?>
								 <span data-wp-c16t="true" data-wp-component="VisuallyHidden" class="components-visually-hidden css-0 e19lxcc00" style="">
								 (<?php echo esc_html__( 'opens in a new tab', 'chargilytextdomain' ); ?>)
								 </span>
								 <img src="/wp-content/plugins/chargily-pay/assets/img/link-out.svg" alt="link">
							 </a></p>
						 </div>
					 </div>
					 <div style="float: right; width: 90%;">
						 <div class="css-mkkf9p et1p4me0">
							 <div class="components-surface components-card css-cn3xcj e1ul4wtb1 css-1pd4mph e19lxcc00">
								 <div class="css-10klw3m e19lxcc00">
									 <div class="components-card__body components-card-body css-hqx46f eezfi080 css-188a3xf e19lxcc00">
										 <h2><?php _e('Chargily Pay™ Settings', 'chargilytextdomain'); ?></h2>
										 <table class="form-table">
                    									<?php $this->generate_settings_html(); ?>
                    								</table>
									 </div>
								 </div>
							 </div>
						 </div>	
					 </div>
				 </div>
			 </div>
		<?php
			 if ( is_admin() ) {
				if (current_user_can('administrator') || current_user_can('shop_manager')) {
					$test_mode = $this->get_option('test_mode') === 'yes';
					$live_api_key_present = !empty($this->get_option('Chargily_Gateway_api_key_v2_live'));
					$live_api_secret_present = !empty($this->get_option('Chargily_Gateway_api_secret_v2_live'));
					$test_api_key_present = !empty($this->get_option('Chargily_Gateway_api_key_v2_test'));
					$test_api_secret_present = !empty($this->get_option('Chargily_Gateway_api_secret_v2_test'));

					$data = array(
						'testMode' => $test_mode,
						'liveApiKeyPresent' => $live_api_key_present,
						'liveApiSecretPresent' => $live_api_secret_present,
						'testApiKeyPresent' => $test_api_key_present,
						'testApiSecretPresent' => $test_api_secret_present,
					);

					$file_path = plugin_dir_path(__FILE__) . 'chargily_data.json';
					file_put_contents($file_path, json_encode($data));
				}
			}
		}
		
		public function payment_fields() {
			$test_mode = $this->get_option('test_mode') === 'yes';	
			$live_api_key = $this->get_option('Chargily_Gateway_api_key_v2_live');
			$live_api_secret = $this->get_option('Chargily_Gateway_api_secret_v2_live');
			$test_api_key = $this->get_option('Chargily_Gateway_api_key_v2_test');
			$test_api_secret = $this->get_option('Chargily_Gateway_api_secret_v2_test');
			
			$fix_for_compatibility_plugins = $this->get_option('fix_for_compatibility_plugins') === 'yes';
			if ($fix_for_compatibility_plugins) {
				$fix_for_compatibility_label = "display: flex; justify-content: space-between; position: relative; align-items: center; grid-gap: 20px; padding: 0 20px; ";
			} else {
				$fix_for_compatibility_label = "";
			}
			
			$show_payment_methods = $this->get_option('show_payment_methods') === 'yes';

			echo '<div class="Chargily-container">';

			if ($test_mode) {
			    // We are in test mode
			    if (empty($test_api_key) || empty($test_api_secret)) {
			        // Test API keys are missing
			        echo '<div class="">
			                <p>' . __('You are in Test Mode but your Test API keys are missing.', 'chargilytextdomain') . ' 
			                <a href="/wp-admin/admin.php?page=wc-settings&tab=checkout&section=chargily_pay">' . __('Enter your Test API keys.', 'chargilytextdomain') . '</a></p>
			              </div>';
			    } else {
			        // Test API keys are present
			        echo '<div class=""><p>' . __('Chargily Pay™: Test Mode is enabled.', 'chargilytextdomain') . '</p></div>';
			        // Display payment options
					if ($show_payment_methods) {
			echo '
			<div class="Chargily-option">
			  <input type="radio" name="chargilyv2_payment_method" id="chargilyv2_edahabia" value="EDAHABIA" checked="checked" onclick="updateCookieValue(this)">
			  <label for="chargilyv2_edahabia" aria-label="royal" class="Chargily" style="' . $fix_for_compatibility_label .'">
			  <span style="display: flex; align-items: center;"></span>
			  <div class="Chargily-card-text" style="">' . __('EDAHABIA', 'chargilytextdomain') . '</div>
			  <img class="edahabiaCardImage" src="/wp-content/plugins/chargily-pay/assets/img/edahabia-card.svg" alt="EDAHABIA" style="border-radius: 4px; margin-inline-start: auto;"></img>
			  </label>
			</div>
			
			<div class="Chargily-option">
			  <input type="radio" name="chargilyv2_payment_method" id="chargilyv2_cib" value="CIB" onclick="updateCookieValue(this)">
			  <label for="chargilyv2_cib" aria-label="Silver" class="Chargily" style="' . $fix_for_compatibility_label .'">
			  <span style="display: flex; align-items: center;"></span>
			  <div class="Chargily-card-text" style="">chargily_cib Card</div>
			  <img class="cibCardImage" src="/wp-content/plugins/chargily-pay/assets/img/cib-card.svg" alt="CIB" style="margin-inline-start: auto;"></img>
			  </label>
			</div>
			
			<div class="Chargily-option">
			  <input type="radio" name="chargilyv2_payment_method" id="chargilyv2_app" value="chargily_app" onclick="updateCookieValue(this)">
			  <label for="chargilyv2_app" aria-label="Silver" class="Chargily" style="' . $fix_for_compatibility_label .'">
			  <span style="display: flex; align-items: center;"></span>
			  <div class="Chargily-card-text" style="">QR Code</div>
			  <img class="appCardImage" src="/wp-content/plugins/chargily-pay/assets/img/qr-code.svg" alt="APP" style="margin-inline-start: auto;"></img>
			  </label>
			</div>
			';
			} else {
				echo '<div class="Chargily-option-no-show">
				<label for="chargilyv2_no-show" class="Chargily" style="display: flex !important; justify-content: flex-start !important;">
				<img class="edahabiaCardImage-no" src="https://demo.civitaic.com/wp-content/plugins/chargily-pay/assets/img/edahabia-card.svg" alt="EDAHABIA" style="border-radius: 4px;">
				<img class="cibCardImage-no" src="https://demo.civitaic.com/wp-content/plugins/chargily-pay/assets/img/cib-card.svg" alt="CIB Card" style="border-radius: 4px;">
				<img class="appCardImage-no" src="https://demo.civitaic.com/wp-content/plugins/chargily-pay/assets/img/qr-code.svg" alt="QR Code" style="border-radius: 4px;">
				</label>
				</div>';
			}
			echo '
			<br>
			<div class="Chargily-logo-z" style="display: flex;flex-wrap: nowrap;align-items: center;align-content: center;">
			<p> ' . __('🔒 Secure E-Payment provided by', 'chargilytextdomain') . '</p>
			<a href="https://chargily.com/business/pay" target="_blank" style="/*font-weight:bold;*/ color:black;"> 
			<img src="/wp-content/plugins/chargily-pay/assets/img/logo.svg" alt="chargily" style="/*width:42px;height:42px;*/">
			</a>
			</div>
			';
			}
			} else {
			    // We are in live mode
			    if (empty($live_api_key) || empty($live_api_secret)) {
			        // Live API keys are missing
			        echo '<div class="">
			                <p>' . __('You are in Live Mode but your Live API keys are missing.', 'chargilytextdomain') . ' 
			                <a href="/wp-admin/admin.php?page=wc-settings&tab=checkout&section=chargily_pay">' . __('Enter your Live API keys.', 'chargilytextdomain') . '</a></p>
			              </div>';
			    } else {
			        // Live API keys are present
			        // Display payment options
					if ($show_payment_methods) {
			       echo '
			<div class="Chargily-option">
			  <input type="radio" name="chargilyv2_payment_method" id="chargilyv2_edahabia" value="EDAHABIA" checked="checked" onclick="updateCookieValue(this)">
			  <label for="chargilyv2_edahabia" aria-label="royal" class="Chargily" style="' . $fix_for_compatibility_label .'">
			  <span style="display: flex; align-items: center;"></span>
			  <div class="Chargily-card-text" style="">' . __('EDAHABIA', 'chargilytextdomain') . '</div>
			  <img class="edahabiaCardImage" src="/wp-content/plugins/chargily-pay/assets/img/edahabia-card.svg" alt="EDAHABIA" style="border-radius: 4px; margin-inline-start: auto;"></img>
			  </label>
			</div>
			
			<div class="Chargily-option">
			  <input type="radio" name="chargilyv2_payment_method" id="chargilyv2_cib" value="CIB" onclick="updateCookieValue(this)">
			  <label for="chargilyv2_cib" aria-label="Silver" class="Chargily" style="' . $fix_for_compatibility_label .'">
			  <span style="display: flex; align-items: center;"></span>
			  <div class="Chargily-card-text" style="">' . __('CIB Card', 'chargilytextdomain') . '</div>
			  <img class="cibCardImage" src="/wp-content/plugins/chargily-pay/assets/img/cib-card.svg" alt="CIB" style="margin-inline-start: auto;"></img>
			  </label>
			</div>
			
			<div class="Chargily-option">
			  <input type="radio" name="chargilyv2_payment_method" id="chargilyv2_app" value="chargily_app" onclick="updateCookieValue(this)">
			  <label for="chargilyv2_app" aria-label="Silver" class="Chargily" style="' . $fix_for_compatibility_label .'">
			  <span style="display: flex; align-items: center;"></span>
			  <div class="Chargily-card-text" style="">QR Code</div>
			  <img class="appCardImage" src="/wp-content/plugins/chargily-pay/assets/img/qr-code.svg" alt="APP" style="margin-inline-start: auto;"></img>
			  </label>
			</div>
			';
			} else {
				echo '<div class="Chargily-option-no-show">
				<label for="chargilyv2_no-show" class="Chargily" style="display: flex !important; justify-content: flex-start !important;">
				<img class="edahabiaCardImage-no" src="https://demo.civitaic.com/wp-content/plugins/chargily-pay/assets/img/edahabia-card.svg" alt="EDAHABIA" style="border-radius: 4px;">
				<img class="cibCardImage-no" src="https://demo.civitaic.com/wp-content/plugins/chargily-pay/assets/img/cib-card.svg" alt="CIB Card" style="border-radius: 4px;">
				<img class="appCardImage-no" src="https://demo.civitaic.com/wp-content/plugins/chargily-pay/assets/img/qr-code.svg" alt="QR Code" style="border-radius: 4px;">
				</label>
				</div>';
			}
			echo '
			<br>
			<div class="Chargily-logo-z" style="display: flex;flex-wrap: nowrap;align-items: center;align-content: center;">
			<p> ' . __('🔒 Secure E-Payment provided by', 'chargilytextdomain') . '</p>
			<a href="https://chargily.com/business/pay" target="_blank" style="/*font-weight:bold;*/ color:black;"> 
			<img src="/wp-content/plugins/chargily-pay/assets/img/logo.svg" alt="chargily" style="/*width:42px;height:42px;*/">
			</a>
			</div>

			';
			}
			}
			
			echo '</div>';
		}
		
		private function get_api_credentials() {
			$test_mode = $this->get_option('test_mode') === 'yes';
			if ($test_mode) {
				return array(
					'api_key' => $this->get_option('Chargily_Gateway_api_key_v2_test'),
					'api_secret' => $this->get_option('Chargily_Gateway_api_secret_v2_test')
				);
			} else {
				return array(
					'api_key' => $this->get_option('Chargily_Gateway_api_key_v2_live'),
					'api_secret' => $this->get_option('Chargily_Gateway_api_secret_v2_live')
				);
			}
		}
		

		private function encrypt($data, $key) {
			$iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-cbc'));
			$encrypted = openssl_encrypt($data, 'aes-256-cbc', $key, 0, $iv);
			return base64_encode($encrypted . '::' . $iv);
		}
		
		private function decrypt($data, $key) {
			list($encrypted_data, $iv) = array_pad(explode('::', base64_decode($data), 2), 2, null);
			if($iv === null) {
				throw new Exception('The IV is missing from the encrypted data!');
			}
			return openssl_decrypt($encrypted_data, 'aes-256-cbc', $key, 0, $iv);
		}

		private function get_encryption_key() {
			$secret_key = get_option('chargily_customers_secret_key');
			if (empty($secret_key)) {
				$secret_key = bin2hex(openssl_random_pseudo_bytes(32));
				update_option('chargily_customers_secret_key', $secret_key);
			}
			return $secret_key;
		}

		public function process_payment( $order_id ) {
			$credentials = $this->get_api_credentials();
			$order = wc_get_order( $order_id );

			$test_mode = $this->get_option('test_mode') === 'yes';	
			if ($test_mode) {
				$order_type ='Test';
				$order->update_meta_data( 'chargily_order_type', $order_type );
				$order->save();
			} else {
				$order_type ='Live';
				$order->update_meta_data( 'chargily_order_type', $order_type );
				$order->save();
			}

			$languages_type = $this->get_option('languages_type');
			if ($languages_type === 'en') {
			    $languages_use = 'en';
			} elseif ($languages_type === 'ar') {
			    $languages_use = 'ar';
			} elseif ($languages_type === 'fr') {
			    $languages_use = 'fr';
			} else {
			    $languages_use = 'en';
			}
			
			if (isset($_COOKIE['chargily_payment_method'])) {
				$selected_payment_method = isset($_COOKIE['chargily_payment_method']) ? wc_clean($_COOKIE['chargily_payment_method']) : 'EDAHABIA';
				$payment_method = $selected_payment_method;
			} else {
				$payment_method = 'EDAHABIA';
			}
			
			$chargily_pay_fees_allocation_settings = $this->get_option('chargily_pay_fees_allocation');
			
			$encryption_key = $this->get_encryption_key();

			function filter_empty_values($value) {
				if (is_array($value)) {
					return array_filter($value, 'filter_empty_values');
				}
				return ($value !== null && $value !== '');
			}

			if ( is_user_logged_in() ) {
				
				$user_id = get_current_user_id();
				$is_test_mode = $this->get_option('test_mode') === 'yes';
				$meta_key = $is_test_mode ? 'chargily_customers_id_test' : 'chargily_customers_id_live';
				$chargily_customers_id = get_user_meta($user_id, $meta_key, true);
				if (!$this->customer_exists($chargily_customers_id, $user_id)) {
						// إنشاء بيانات العميل لإرسالها إلى API
						$address = array_filter(array(
							"country" => $order->get_billing_country(),
							"state" => $order->get_billing_state(),
							"city" => $order->get_billing_city(),
							"postcode" => $order->get_billing_postcode(),
							"address_1" => $order->get_billing_address_1(),
							"address_2" => $order->get_billing_address_2()
						), 'filter_empty_values');

					    $user_data = array();
					
					    if (!empty($order->get_billing_first_name())) {
					        $user_data["name"] = $order->get_billing_first_name();
					    }
					
					    if (!empty($order->get_billing_email())) {
					        $user_data["email"] = $order->get_billing_email();
					    }
					
					    if (!empty($order->get_billing_phone())) {
					        $user_data["phone"] = $order->get_billing_phone();
					    }
					
					    if (!empty($address)) {
					        $user_data["address"] = $address;
					    }
					
					    if (empty($user_data)) {
					        // No data to send
					        return;
					    }
					
						$user_id = get_current_user_id();
						$chargily_customers_id = $this->create_chargily_customer($user_data, $user_id);
						if (is_wp_error($chargily_customers_id)) {
							wc_add_notice($chargily_customers_id->get_error_message(), 'error');
							return;
						}
					// end
				}
			} else {
				// العميل هو زائر
				if (isset($_COOKIE['chargily_customers_id'])) {
					$decrypted_customer_id = $this->decrypt($_COOKIE['chargily_customers_id'], $encryption_key);
					$chargily_customers_id = $decrypted_customer_id;
				} else {
					// إنشاء بيانات العميل لإرسالها إلى API
					$address = array_filter(array(
						"country" => $order->get_billing_country(),
						"state" => $order->get_billing_state(),
						"city" => $order->get_billing_city(),
						"postcode" => $order->get_billing_postcode(),
						"address_1" => $order->get_billing_address_1(),
						"address_2" => $order->get_billing_address_2()
					), 'filter_empty_values');

					    $user_data = array();
					
					    if (!empty($order->get_billing_first_name())) {
					        $user_data["name"] = $order->get_billing_first_name();
					    }
					
					    if (!empty($order->get_billing_email())) {
					        $user_data["email"] = $order->get_billing_email();
					    }
					
					    if (!empty($order->get_billing_phone())) {
					        $user_data["phone"] = $order->get_billing_phone();
					    }
					
					    if (!empty($address)) {
					        $user_data["address"] = $address;
					    }
					
					    if (empty($user_data)) {
					        // No data to send
					        return;
					    }

					$chargily_customers_id = $this->create_chargily_customer($user_data);

					if (!is_wp_error($chargily_customers_id)) {
						$encrypted_customer_id = $this->encrypt($chargily_customers_id, $encryption_key);
						setcookie('chargily_customers_id', $encrypted_customer_id, time() + (365 * 24 * 60 * 60), "/");
					}
				}
			}
			
			$is_webhook_rewrite_rule = $this->get_option('webhook_rewrite_rule') === 'yes';
			if (isset($is_webhook_rewrite_rule['webhook_rewrite_rule']) && $is_webhook_rewrite_rule['webhook_rewrite_rule'] === 'yes') {
			$baseURL = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'];
			$webhookEndpoint = $baseURL . '/chargilyv2-webhook/';
			} else {
			$baseURL = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'];
			$webhookEndpoint = $baseURL . '/wp-content/plugins/chargily-pay/templates/method-v2/API-v2_webhook.php';
			}
			
			$collect_shipping_address = $this->get_option('collect_shipping_address') === 'yes';
			if ($collect_shipping_address) {
				$collect_shipping_address_is = '1';
			} else {
				$collect_shipping_address_is = '0';
			}


			$show_payment_methods = $this->get_option('show_payment_methods') === 'yes';
			if ($show_payment_methods) {
				$payload = array(
					"locale" => $languages_use,
					"metadata" => array("woocommerce_order_id" => (string)$order_id),
					'amount'          => $order->get_total(),
					'currency'        => 'dzd',
					'payment_method'  => $payment_method,
					'customer_id'  => $chargily_customers_id,
					'collect_shipping_address'  => $collect_shipping_address_is,
					'chargily_pay_fees_allocation'  => $chargily_pay_fees_allocation_settings,
					'success_url'     => $this->get_return_url( $order ),
					'failure_url'     => $order->get_cancel_order_url(),
					'webhook_endpoint' => $webhookEndpoint,
				);
			} else {
				$payload = array(
					"locale" => $languages_use,
					"metadata" => array("woocommerce_order_id" => (string)$order_id),
					'amount'          => $order->get_total(),
					'currency'        => 'dzd',
					'customer_id'  => $chargily_customers_id,
					'collect_shipping_address'  => $collect_shipping_address_is,
					'chargily_pay_fees_allocation'  => $chargily_pay_fees_allocation_settings,
					'success_url'     => $this->get_return_url( $order ),
					'failure_url'     => $order->get_cancel_order_url(),
					'webhook_endpoint' => $webhookEndpoint,
				);
			}
			
			$response = $this->create_chargilyv2_checkout($payload);

			if (is_wp_error($response)) {
				wc_add_notice($response->get_error_message(), 'error');
				return;
			}

			$body = json_decode(wp_remote_retrieve_body($response), true);
			if (isset($body['checkout_url'])) {
				$order->update_status('pending', __('Awaiting Chargily payment', 'chargilytextdomain'));
				wc_reduce_stock_levels($order_id);
				WC()->cart->empty_cart();

				return array(
					'result'   => 'success',
					'redirect' => $body['checkout_url']
				);
			} else {
				$error_message = isset($body['message']) ? $body['message'] : __(
					'An error occurred while processing your payment. Please try again.', 'chargilytextdomain');
				wc_add_notice($error_message, 'error');
				return;
			}
		}

	    
		private function customer_exists($chargily_customers_id, $user_id) {
			if (empty($chargily_customers_id)) {
				$chargily_customers_id = "0000000099999";
			}
		    $credentials = $this->get_api_credentials();
		    $is_test_mode = $this->get_option('test_mode') === 'yes';
		    $api_url = $is_test_mode
		        ? 'https://pay.chargily.net/test/api/v2/customers/' . $chargily_customers_id
		        : 'https://pay.chargily.net/api/v2/customers/' . $chargily_customers_id;
		
		    $headers = array(
		        'Authorization' => 'Bearer ' . $credentials['api_secret'],
		        'Content-Type'  => 'application/json',
		    );
		
		    $response = wp_remote_get($api_url, array(
		        'headers'   => $headers,
		        'timeout'   => 45,
		        'sslverify' => false,
		    ));
		
		    if (is_wp_error($response)) {
		        return false;
		    }
		
		    $response_code = wp_remote_retrieve_response_code($response);
		    if ($response_code >= 200 && $response_code <= 205) {
		        return true; // Status code 200 means the customer exists.
		    } else if ($response_code >= 400 && $response_code <= 499) {
		        if ($user_id) {
		            // Adjust the meta key based on the mode (test or live)
		            $meta_key = $is_test_mode ? 'chargily_customers_id_test' : 'chargily_customers_id_live';
		            delete_user_meta($user_id, $meta_key);
		        }
		        return false;
		    }
		    return true;
		}
	    
		
		private function create_chargily_customer($user_data, $user_id = null) {
		    $test_mode = $this->get_option('test_mode') === 'yes';
		    $chargily_customers_meta_key = $test_mode ? 'chargily_customers_id_test' : 'chargily_customers_id_live';
		
		    $chargily_customers_id = isset($user_data[$chargily_customers_meta_key]) ? $user_data[$chargily_customers_meta_key] : null;
		    if ($chargily_customers_id && !$this->customer_exists($chargily_customers_id, $user_id)) {
		        // الرقم التعريفي لا يوجد في الـ API وتم حذفه من قاعدة البيانات. يمكنك الآن إنشاء مستخدم جديد
		    }
		    $credentials = $this->get_api_credentials();
		    $api_url = $test_mode
		        ? 'https://pay.chargily.net/test/api/v2/customers'
		        : 'https://pay.chargily.net/api/v2/customers';
		    $headers = array(
		        'Authorization' => 'Bearer ' . $credentials['api_secret'],
		        'Content-Type'  => 'application/json',
		    );
		
		    $response = wp_remote_post($api_url, array(
		        'method'    => 'POST',
		        'headers'   => $headers,
		        'body'      => json_encode($user_data),
		        'timeout'   => 45,
		        'sslverify' => false,
		    ));
		    if (is_wp_error($response)) {
		        return $response;
		    }
		
		    $body = json_decode(wp_remote_retrieve_body($response), true);
		    if (isset($body['id'])) {
		        if ($user_id) {
		            update_user_meta($user_id, $chargily_customers_meta_key, $body['id']);
		        }
		        return $body['id'];
		    } else {
		        $error_message = __('Failed to create customer in Chargily.', 'chargilytextdomain');
		        if (isset($body['message']) && isset($body['errors'])) {
		            $error_message = $body['message'] . "\n";
		            foreach ($body['errors'] as $field => $messages) {
		                foreach ($messages as $msg) {
		                    $error_message .= $field . ' : ' . $msg . "\n";
		                }
		            }
		        }
		        return new WP_Error('chargily_customer_creation_failed', $error_message);
		    }
		}
		
		private function product_exists($chargily_product_id, $product_id, $product_total, $attributes_in) {
			if (empty($chargily_product_id)) {
				$chargily_product_id = "0000000099999";
			}
		    $credentials = $this->get_api_credentials();
		    $is_test_mode = $this->get_option('test_mode') === 'yes';
		    $api_url = $is_test_mode
		        ? 'https://pay.chargily.net/test/api/v2/products/' . $chargily_product_id
		        : 'https://pay.chargily.net/api/v2/products/' . $chargily_product_id;
		
		    $headers = array(
		        'Authorization' => 'Bearer ' . $credentials['api_secret'],
		        'Content-Type'  => 'application/json',
		    );
		
		    $response = wp_remote_get($api_url, array(
		        'headers'   => $headers,
		        'timeout'   => 45,
		        'sslverify' => false,
		    ));
		
		    if (is_wp_error($response)) {
		        return false;
		    }
		
		    $response_code = wp_remote_retrieve_response_code($response);
		    if ($response_code >= 200 && $response_code <= 205) {
		        return true; // Status code 200 means the product exists.
		    } else if ($response_code >= 400 && $response_code <= 499) {
		        // Adjust the meta key based on the mode (test or live)
				if ($product_id) {
					$chargily_product_meta_key = $is_test_mode ? 'chargily_product_id_test_' : 'chargily_product_id_live_';
					$chargily_product_meta_key_in = $chargily_product_meta_key . $attributes_in;
					delete_post_meta($product_id, $chargily_product_meta_key_in);
				}
		        return false;
		    }
		    return true;
		}

		private function create_chargily_product($product_data, $product_id = null, $product_total = null, $attributes_in = null) {
			$credentials = $this->get_api_credentials();
			$test_mode = $this->get_option('test_mode') === 'yes';
		    $chargily_product_meta_key = $test_mode ? 'chargily_product_id_test_' : 'chargily_product_id_live_';
			$chargily_product_meta_key_in = $chargily_product_meta_key . $attributes_in;
		
		    $chargily_product_id = isset($product_data[$chargily_product_meta_key_in]) ? $product_data[$chargily_product_meta_key_in] : null;
		    if ($chargily_product_id && !$this->product_exists($chargily_product_id, $product_id, $product_total, $attributes_in)) {
		        // الرقم التعريفي لا يوجد في الـ API وتم حذفه من قاعدة البيانات. يمكنك الآن إنشاء منتج جديد
		    }
			
			$existing_product_id = get_post_meta($product_id, $chargily_product_meta_key_in, true);
			if ($existing_product_id) {
				return $existing_product_id;
			}

			$api_url = $this->get_option('test_mode') === 'yes'
				? 'https://pay.chargily.net/test/api/v2/products'
				: 'https://pay.chargily.net/api/v2/products';

			$credentials = $this->get_api_credentials();

			$headers = array(
				'Authorization' => 'Bearer ' . $credentials['api_secret'],
				'Content-Type'  => 'application/json',
			);

			$response = wp_remote_post($api_url, array(
				'method'    => 'POST',
				'headers'   => $headers,
				'body'      => json_encode($product_data),
				'timeout'   => 45,
				'sslverify' => false,
			));

			if (is_wp_error($response)) {
				return $response;
			}

			$body = json_decode(wp_remote_retrieve_body($response), true);
			if (isset($body['id'])) {
				update_post_meta($product_id, $chargily_product_meta_key_in, $body['id']);
				return $body['id'];
			} else {
				return new WP_Error('chargily_product_creation_failed', __('Failed to create product in Chargily.', 'chargilytextdomain'));
			}
		}
		
		private function product_price_exists($chargily_product_price_id, $product_id, $product_total, $attributes_in) {
			if (empty($chargily_product_price_id)) {
				$chargily_product_price_id = "0000000099999";
			}
		    $credentials = $this->get_api_credentials();
		    $is_test_mode = $this->get_option('test_mode') === 'yes';
		    $api_url = $is_test_mode
		        ? 'https://pay.chargily.net/test/api/v2/prices/' . $chargily_product_price_id
		        : 'https://pay.chargily.net/api/v2/prices/' . $chargily_product_price_id;
		
		    $headers = array(
		        'Authorization' => 'Bearer ' . $credentials['api_secret'],
		        'Content-Type'  => 'application/json',
		    );
		
		    $response = wp_remote_get($api_url, array(
		        'headers'   => $headers,
		        'timeout'   => 45,
		        'sslverify' => false,
		    ));
		
		    if (is_wp_error($response)) {
		        return false;
		    }
		
		    $response_code = wp_remote_retrieve_response_code($response);
		    if ($response_code >= 200 && $response_code <= 205) {
		        return true; // Status code 200 means the product price exists.
		    } else if ($response_code >= 400 && $response_code <= 499) {
		        // Adjust the meta key based on the mode (test or live)
				if ($product_id) {
					$chargily_product_price_meta_key = $is_test_mode ? 'chargily_product_price_id_test_' : 'chargily_product_price_id_live_';
					$chargily_product_price_meta_key_in = $chargily_product_price_meta_key . $attributes_in . $product_total;
					delete_post_meta($product_id, $chargily_product_price_meta_key_in);
				}
		        return false;
		    }
		    return true;
		}

		
		private function create_chargily_product_price($price_data, $product_id = null, $product_total = null, $attributes_in = null) {
			$test_mode = $this->get_option('test_mode') === 'yes';
			$chargily_product_price_meta_key = $test_mode ? 'chargily_product_price_id_test_' : 'chargily_product_price_id_live_';
			$chargily_product_price_meta_key_in = $chargily_product_price_meta_key . $attributes_in . $product_total;
		
			$chargily_product_price_id = isset($price_data[$chargily_product_price_meta_key_in]) ? $price_data[$chargily_product_price_meta_key_in] : null;
			if ($chargily_product_price_id && !$this->product_price_exists($chargily_product_price_id, $product_id, $product_total, $attributes_in)) {
				// الرقم التعريفي لا يوجد في الـ API وتم حذفه من قاعدة البيانات. يمكنك الآن إنشاء سعر جديد
			}
			
			$existing_price_id = get_post_meta($product_id, $chargily_product_price_meta_key_in, true);
			if ($existing_price_id) {
				return $existing_price_id;
			}

			$api_url = $this->get_option('test_mode') === 'yes'
				? 'https://pay.chargily.net/test/api/v2/prices'
				: 'https://pay.chargily.net/api/v2/prices';

			$credentials = $this->get_api_credentials();

			$headers = array(
				'Authorization' => 'Bearer ' . $credentials['api_secret'],
				'Content-Type'  => 'application/json',
			);

			$response = wp_remote_post($api_url, array(
				'method'    => 'POST',
				'headers'   => $headers,
				'body'      => json_encode($price_data),
				'timeout'   => 45,
				'sslverify' => false,
			));

			if (is_wp_error($response)) {
				return $response;
			}

			$body = json_decode(wp_remote_retrieve_body($response), true);
			if (isset($body['id'])) {
				update_post_meta($product_id, $chargily_product_price_meta_key_in, $body['id']);
				return $body['id'];
			} else {
				return new WP_Error('chargily_price_creation_failed', __('Failed to create price in Chargily.', 'chargilytextdomain'));
			}
		}
		
	    private function create_chargilyv2_checkout( $payload ) {
			
			$credentials = $this->get_api_credentials();
			$api_url = $this->get_option( 'test_mode' ) === 'yes'
			? 'https://pay.chargily.net/test/api/v2/checkouts'
			: 'https://pay.chargily.net/api/v2/checkouts';
	    
			$headers = array(
				'Authorization' => 'Bearer ' . $credentials['api_secret'],
				'Content-Type'  => 'application/json',
			);
	
			$response = wp_remote_post( $api_url, array(
				'method'    => 'POST',
				'headers'   => $headers,
				'body'      => json_encode( $payload ),
				'timeout'   => 45,
				'sslverify' => false,
			) );
			return $response;
		}
			
	    public function receipt_page( $order ) {
			echo '<p>' . __( 'Thank you for your order, please click the button below to pay with Chargily.', 'chargilytextdomain' ) . '</p>';
		}
	
	    public function thankyou_page() {
		    if ( $this->instructions ) {
			    echo wpautop( wptexturize( $this->instructions ) );
		    }
	    }
	
	    public function email_instructions( $order, $sent_to_admin, $plain_text = false ) {
			if ( $this->instructions && ! $sent_to_admin && $this->id === $order->get_payment_method() && $order->has_status( 'pending' ) ) {
				echo wpautop( wptexturize( $this->instructions ) ) . PHP_EOL;
			}
	    }

		public function display_chargily_admin_notices() {
			
			if ((!empty($this->get_option('Chargily_Gateway_api_key_v2_live')) 
				 && !empty($this->get_option('Chargily_Gateway_api_secret_v2_live'))) ||
				(!empty($this->get_option('Chargily_Gateway_api_key_v2_test')) 
				 && !empty($this->get_option('Chargily_Gateway_api_secret_v2_test')))) {
				//
				} else {
					echo '<div class="notice notice-error">
					<p>' . __('Just one more step to complete the setup of Chargily Pay™ and begin accepting payments.', 'chargilytextdomain') . ' <a href="/wp-admin/admin.php?page=wc-settings&tab=checkout&section=chargily_pay">' . __('Enter your API keys.', 'chargilytextdomain') . '</a></p></div>';
				}

			// Check for test mode
			if ($this->get_option('test_mode') === 'yes') {
				echo '<div class="notice notice-warning"><p>
				' . __('Chargily Pay™: Test Mode is enabled.', 'chargilytextdomain') . '
				</p></div>';
			}
		}
		
		function update_chargily_pay_settings() {
			if ( is_admin() ) {
				if (current_user_can('administrator') || current_user_can('shop_manager')) {
					$options = get_option('woocommerce_chargily_pay_settings');

					$test_mode = isset($options['test_mode']) && 'yes' === $options['test_mode'];
					$live_api_key_present = !empty($options['Chargily_Gateway_api_key_v2_live']);
					$live_api_secret_present = !empty($options['Chargily_Gateway_api_secret_v2_live']);
					$test_api_key_present = !empty($options['Chargily_Gateway_api_key_v2_test']);
					$test_api_secret_present = !empty($options['Chargily_Gateway_api_secret_v2_test']);

					$data = array(
						'testMode' => $test_mode,
						'liveApiKeyPresent' => $live_api_key_present,
						'liveApiSecretPresent' => $live_api_secret_present,
						'testApiKeyPresent' => $test_api_key_present,
						'testApiSecretPresent' => $test_api_secret_present,
					);

					$file_path = plugin_dir_path(__FILE__) . 'chargily_data.json';
					file_put_contents($file_path, json_encode($data));
				}
			}
		}
		// END WC Chargily V2
    }
}
// The class itself

function chargilyv2_admin_inline_scripts() {
	if ( is_admin() ) {
		if (current_user_can('administrator') || current_user_can('shop_manager')) {
			$screen = get_current_screen();
			if ($screen->id === 'woocommerce_page_wc-settings') {
				?>
				<script type="text/javascript">
					jQuery(document).ready(function($) {
						toggleApiFields($('#woocommerce_chargily_pay_test_mode').is(':checked'));

						$('#woocommerce_chargily_pay_test_mode').on('change', function() {
							toggleApiFields($(this).is(':checked'));
						});

						$('input[type="password"]').each(function() {
							var $passwordField = $(this);
							$passwordField.after('<button type="button" class="button toggle-password" data-toggle="' + $passwordField.attr('id') + '" aria-label="<?php esc_attr_e('Show password', 'woocommerce'); ?>"><span class="dashicons dashicons-visibility"></span></button>');
						});

						// Toggle password visibility
						$('body').on('click', '.toggle-password', function(e) {
							e.preventDefault();
							var $this = $(this),
								$password_field = $('#' + $this.data('toggle'));

							if ($password_field.attr('type') === 'password') {
								$password_field.attr('type', 'text');
								$this.find('.dashicons').removeClass('dashicons-visibility').addClass('dashicons-hidden');
							} else {
								$password_field.attr('type', 'password');
								$this.find('.dashicons').removeClass('dashicons-hidden').addClass('dashicons-visibility');
							}
						});

						function toggleApiFields(isTestMode) {
							$('.form-table tr').each(function() {
								var row = $(this);
								if (
							row.find('input, select').attr('id') === 'woocommerce_chargily_pay_Chargily_Gateway_api_key_v2_test' ||
							row.find('input, select').attr('id') === 'woocommerce_chargily_pay_Chargily_Gateway_api_secret_v2_test' || 
							row.find('input, select').attr('id') === 'woocommerce_chargily_pay_Chargily_Gateway_api_authorization_v2_test'
								   ) {
									isTestMode ? row.show() : row.hide();
								}
								if (
							row.find('input, select').attr('id') === 'woocommerce_chargily_pay_Chargily_Gateway_api_key_v2_live' ||
							row.find('input, select').attr('id') === 'woocommerce_chargily_pay_Chargily_Gateway_api_secret_v2_live' || 
							row.find('input, select').attr('id') === 'woocommerce_chargily_pay_Chargily_Gateway_api_authorization_v2_live'

								   ) {
									isTestMode ? row.hide() : row.show();
								}
							});
						}

						$('#woocommerce_chargily_pay_Chargily_Gateway_api_authorization_v2_test').on('click', function() {
							var token = $('#woocommerce_chargily_pay_Chargily_Gateway_api_secret_v2_test').val();
							checkConnection(token, 'test');
						});

						$('#woocommerce_chargily_pay_Chargily_Gateway_api_authorization_v2_live').on('click', function() {
							var token = $('#woocommerce_chargily_pay_Chargily_Gateway_api_secret_v2_live').val();
							checkConnection(token, 'live');
						});

						function checkConnection(token, mode) {
							var url = mode === 'test' ? 'https://pay.chargily.net/test/api/v2/balance' : 'https://pay.chargily.net/api/v2/balance';
							$.ajax({
								url: ajaxurl,
								type: 'POST',
								data: {
									'action': 'check_chargily_connection',
									'token': token,
									'mode': mode
								},
								success: function(response) {
									if (response.success && response.data && response.data.message) {
										alert(response.data.message);
									} else {
										alert('Unknown response, try later');
									}
								},
								error: function(jqXHR, textStatus, errorThrown) {
									alert('خطأ في الاتصال بالخادم: ' + textStatus);
								}
							});
						}

						var inputElement = document.getElementById('woocommerce_chargily_pay_title');

						//inputElement.setAttribute('readonly', true);
						
						var button_authorization_v2_test = document.getElementById(
							'woocommerce_chargily_pay_Chargily_Gateway_api_authorization_v2_test');
						if (button_authorization_v2_test) {
						  button_authorization_v2_test.value = 'Check connection';
						}
						
						var button_authorization_v2_live = document.getElementById(
							'woocommerce_chargily_pay_Chargily_Gateway_api_authorization_v2_live');
						if (button_authorization_v2_live) {
						  button_authorization_v2_live.value = 'Check connection';
						}
						
						var button_delete_chargily_customer_ids = document.getElementById(
							'woocommerce_chargily_pay_delete_chargily_customer_ids');
						if (button_delete_chargily_customer_ids) {
						  button_delete_chargily_customer_ids.value = 'Update the database';
						}
					});
				</script>
				<style>
				 
				</style>

				<script type="text/javascript">
				jQuery(document).ready(function( $ ) {
					$('#woocommerce_chargily_pay_delete_chargily_customer_ids').on('click', function(e) {
						e.preventDefault();
						if (confirm(' Are you sure you want to update the database? Use this procedure only if you have encountered a problem in previous versions.')) {
							var data = {
								'action': 'delete_chargily_customer_ids',
							};

							$.post(ajaxurl, data, function(response) {
								alert(response.data);
							});
						}
					});
				});
				</script>
				<?php
			}
		}
	}
}
add_action('admin_footer', 'chargilyv2_admin_inline_scripts');

add_action('init', 'register_custom_order_status');
function register_custom_order_status() {
    register_post_status('wc-expired', array(
        'label'                     => _x('Expired', 'Order status', 'chargilytextdomain'),
        'public'                    => true,
        'exclude_from_search'       => false,
        'show_in_admin_all_list'    => true,
        'show_in_admin_status_list' => true,
        'label_count'               => _n_noop('Expired (%s)', 'Expired (%s)', 'chargilytextdomain')
    ));
}

add_filter('wc_order_statuses', 'add_custom_order_status');
function add_custom_order_status($order_statuses) {
    $new_order_statuses = array();
    foreach ($order_statuses as $key => $status) {
        $new_order_statuses[$key] = $status;
        if ('wc-processing' === $key) {
            $new_order_statuses['wc-expired'] = _x('Expired', 'Order status', 'chargilytextdomain');
        }
    }
    return $new_order_statuses;
}

add_filter('bulk_actions-edit-shop_order', 'custom_dropdown_bulk_actions_shop_order');
function custom_dropdown_bulk_actions_shop_order($actions) {
    $actions['mark_expired'] = __('Change status to expired', 'chargilytextdomain');
    return $actions;
}

function chargilyv2_enqueue_payment_scripts() {
    if (is_checkout()) {
        ?>
        <script type="text/javascript">
            jQuery(function($) {
                $('form.checkout').on('submit', function(e) {
                    var selectedv2_payment_method = $('input[name="chargilyv2_payment_method"]:checked').val();
                    $('<input>').attr({
                        type: 'hidden',
                        name: 'chargilyv2_payment_method',
                        value: selectedv2_payment_method
                    }).appendTo('form.checkout');
                });
            });
        </script>
        <?php
    }
}
add_action('wp_footer', 'chargilyv2_enqueue_payment_scripts');


add_action('wp_ajax_check_chargily_connection', 'check_chargily_connection_callback');
function check_chargily_connection_callback() {
    if ( is_admin() ) {
		if (current_user_can('administrator') || current_user_can('shop_manager')) {
			$token = isset($_POST['token']) ? sanitize_text_field($_POST['token']) : '';
			$mode = isset($_POST['mode']) ? sanitize_text_field($_POST['mode']) : 'test';
			$url = $mode === 'test' ? 'https://pay.chargily.net/test/api/v2/balance' : 'https://pay.chargily.net/api/v2/balance';

			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_HTTPHEADER, array(
				'Authorization: Bearer ' . $token
			));

			$response = curl_exec($ch);
			$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
			curl_close($ch);

			if ($httpcode == 200) {
				wp_send_json_success(array('message' => 'Correct API keys.'));
			} elseif ($httpcode == 401) {
				wp_send_json_success(array('message' => 'Wrong API keys.'));
			} else {
				wp_send_json_error(array('message' => 'Unknown response, try later'));
			}
		}
	}
}


add_action('woocommerce_update_options_payment_gateways_chargily_pay', 'update_chargily_pay_settingss');
function update_chargily_pay_settingss() {
	if ( is_admin() ) {
		if (current_user_can('administrator') || current_user_can('shop_manager')) {
			    $test_mode = 'yes' === get_option('woocommerce_chargily_pay_settings')['test_mode'];
			    $live_api_key_present = !empty(get_option('woocommerce_chargily_pay_settings')['Chargily_Gateway_api_key_v2_live']);
			    $live_api_secret_present = !empty(get_option('woocommerce_chargily_pay_settings')['Chargily_Gateway_api_secret_v2_live']);
			    $test_api_key_present = !empty(get_option('woocommerce_chargily_pay_settings')['Chargily_Gateway_api_key_v2_test']);
			    $test_api_secret_present = !empty(get_option('woocommerce_chargily_pay_settings')['Chargily_Gateway_api_secret_v2_test']);
			
			    $data = array(
			        'testMode' => $test_mode,
			        'liveApiKeyPresent' => $live_api_key_present,
			        'liveApiSecretPresent' => $live_api_secret_present,
			        'testApiKeyPresent' => $test_api_key_present,
			        'testApiSecretPresent' => $test_api_secret_present,
			    );
			
			    $file_path = plugin_dir_path(__FILE__) . 'chargily_data.json';
			    file_put_contents($file_path, json_encode($data));
		}
	}
}

function custom_override_checkout_fields( $fields ) {
    $fields['billing']['billing_phone']['validate'] = array( 'phone' );
    $fields['billing']['billing_phone']['custom_attributes'] = array(
        'pattern' => '[0-9]{8,20}',
        'maxlength' => '20',
        'minlength' => '8',
        'oninput' => "this.setCustomValidity(this.validity.patternMismatch ? 'رقم الهاتف يجب أن يكون بين 8 إلى 20 رقمًا.' : '');",
    );
    return $fields;
}
add_filter( 'woocommerce_checkout_fields' , 'custom_override_checkout_fields' );

function custom_checkout_phone_validation_script() {
    if ( is_checkout() ) {
		if ( is_user_logged_in() ) {
			if ( isset( $_COOKIE['chargily_customers_id'] ) ) {unset( $_COOKIE['chargily_customers_id'] );}
			if ( isset( $_COOKIE['chargily_customers_id_test'] ) ) {unset( $_COOKIE['chargily_customers_id_test'] );}
			if ( isset( $_COOKIE['chargily_customers_id_live'] ) ) {unset( $_COOKIE['chargily_customers_id_live'] );}
		}
		?>
		<script>
		jQuery(document).ready(function($) {
			$('#billing_phone').on('change', function(){
				var phone = $(this).val();
				if ( phone.length < 8 || phone.length > 20 || !$.isNumeric(phone) ) {
					$(this).get(0).setCustomValidity('رقم الهاتف يجب أن يكون بين 8 إلى 20 رقمًا.');
				} else {
					$(this).get(0).setCustomValidity('');
				}
			});
		});
		function deleteCookie(name) {
		  document.cookie = name + '=; Path=/; Expires=Thu, 01 Jan 1970 00:00:01 GMT;';
		}

		window.onload = function() {
		  deleteCookie('chargily_customers_id');
		  deleteCookie('chargily_customers_id_test');
		  deleteCookie('chargily_customers_id_live');
		};
		</script>
		<?php
    }
}
add_action( 'wp_footer', 'custom_checkout_phone_validation_script' );

add_filter( 'manage_edit-shop_order_columns', 'chargily_order_items_column' );
add_filter( 'manage_woocommerce_page_wc-orders_columns', 'chargily_order_items_column' );
function chargily_order_items_column( $columns ) {
	$columns = array_slice( $columns, 0, 4, true ) 
	+ array( 'chargily_order_type' => 'Order Type' ) 
	+ array_slice( $columns, 4, NULL, true );
	return $columns;
}

add_action( 'manage_shop_order_posts_custom_column', 'chargily_type_order_items_column', 25, 2 );
add_action( 'manage_woocommerce_page_wc-orders_custom_column', 'chargily_type_order_items_column', 25, 2 );
function chargily_type_order_items_column( $column_name, $order_or_order_id ) {
	$order = $order_or_order_id instanceof WC_Order ? $order_or_order_id : wc_get_order( $order_or_order_id );
	if( 'chargily_order_type' === $column_name ) {
		foreach ( $order->get_meta_data() as $meta ) {
			if ( $meta->key === 'chargily_order_type' ) { 
				echo $meta->value;
				break; 
			}
		}
	}
}
