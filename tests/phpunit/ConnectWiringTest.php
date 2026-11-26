<?php
/**
 * The WordPress wiring of "Connect with MONEI" and of the callback verify.
 *
 * ConnectServiceTest and CallbackVerifierTest cover the rules. These source tests catch wiring that skips them:
 * a route without its admin check, a check after the write, a handler that processes the request body. Playwright
 * covers the same routes on a running shop. Every test names the bug it catches.
 *
 * @package Monei
 */

namespace Monei\Tests;

use PHPUnit\Framework\TestCase;

class ConnectWiringTest extends TestCase {

	use SourceCode;

	const HANDLER  = 'src/Settings/ConnectHandler.php';
	const SETTINGS = 'src/Settings/MoneiSettings.php';
	const IPN      = 'includes/class-wc-monei-ipn.php';
	const PLUGIN   = 'class-woocommerce-gateway-monei.php';

	/**
	 * Body of a method in comment- and whitespace-free source, up to the next method.
	 */
	private function method( string $file, string $name ): string {
		$code  = $this->code( $file );
		$start = strpos( $code, 'function' . $name . '(' );
		$this->assertNotFalse( $start, "$name not found in $file" );
		$end = preg_match( '/(public|private|protected)(static)?function/', $code, $match, PREG_OFFSET_CAPTURE, $start + 1 ) ? $match[0][1] : strlen( $code );
		return substr( $code, $start, $end - $start );
	}

	private function assertBefore( string $first, string $then, string $haystack, string $message ) {
		$first_at = strpos( $haystack, $first );
		$then_at  = strpos( $haystack, $then );
		$this->assertNotFalse( $first_at, "$message: $first not found" );
		$this->assertNotFalse( $then_at, "$message: $then not found" );
		$this->assertLessThan( $then_at, $first_at, $message );
	}

	/**
	 * Bug: the order changes from the request body instead of the payment the verifier proved or fetched.
	 */
	public function test_the_ipn_processes_only_the_verifier_payment() {
		$body = $this->method( self::IPN, 'check_ipn_request' );

		$this->assertStringContainsString( '$payload=$decision[\'payment\'];', $body );
		$this->assertStringContainsString( 'if(null===$decision[\'payment\']){', $body );
		$this->assertStringContainsString( '$this->handle_valid_ipn($payload,$decision[\'model\']);do_action(\'woocommerce_monei_handle_valid_ipn\',$payload);', $body );
		$this->assertStringNotContainsString( 'json_decode(', $body );
		$this->assertStringNotContainsString( 'verifySignature(', $this->code( self::IPN ) );
	}

	/**
	 * Bug: the IPN answers 200 to a refused callback. MONEI then never retries a failed fetch or a used-up quota
	 * (503), and a forged or malformed request (401, 400) looks accepted.
	 */
	public function test_the_ipn_answers_the_verifier_status() {
		$body   = $this->method( self::IPN, 'check_ipn_request' );
		$branch = function ( string $start ) use ( $body ): string {
			$code = strstr( $body, $start );
			$this->assertNotFalse( $code, "$start not found" );
			return (string) strstr( $code, 'exit;', true );
		};

		$this->assertStringContainsString( 'http_response_code(401);', $branch( 'if(401===$decision[\'status\']){' ) );
		$this->assertStringContainsString( 'http_response_code($decision[\'status\']);', $branch( 'if(null===$decision[\'payment\']){' ) );
	}

	/**
	 * Bug: an AUTHORIZED payment with another amount than the order goes on hold as a normal authorization, and
	 * Processing captures it. Since the fetch path no longer checks the amount, handle_valid_ipn checks it for
	 * AUTHORIZED as for SUCCEEDED, before anything marks the payment for capture or completes the order.
	 */
	public function test_authorized_and_succeeded_check_the_amount_first() {
		$body       = $this->method( self::IPN, 'handle_valid_ipn' );
		$authorized = substr( $body, (int) strpos( $body, 'if(\'AUTHORIZED\'===$status){' ) );
		$succeeded  = substr( $body, (int) strpos( $body, 'if(\'SUCCEEDED\'===$status){' ) );
		$check      = 'if($this->hold_on_amount_mismatch($order,$amount)){return;}';

		$this->assertStringStartsWith( 'if(\'AUTHORIZED\'===$status){' . $check, $authorized );
		$this->assertStringStartsWith( 'if(\'SUCCEEDED\'===$status){' . $check, $succeeded );
	}

	/**
	 * Bug: IPN debug logging writes the whole payment, with its payment token and customer data.
	 */
	public function test_the_ipn_debug_log_keeps_only_allowlisted_fields() {
		$this->assertStringContainsString(
			'$this->logging&&WC_Monei_Logger::logDebug(array_intersect_key($payload,array_flip(array(\'id\',\'orderId\',\'status\',\'statusCode\',\'amount\',\'currency\',\'livemode\'))));',
			$this->method( self::IPN, 'check_ipn_request' )
		);
	}

	/**
	 * Bug: the IPN fetches the payment a second time for its payment method display, which doubles the API calls of
	 * the fetch path and bypasses its quota.
	 */
	public function test_the_ipn_reuses_the_fetched_payment_for_the_display() {
		$this->assertStringContainsString( '$payment=$payment??$this->moneiPaymentServices->get_payment($monei_id);', $this->method( self::IPN, 'handle_valid_ipn' ) );
	}

	/**
	 * Bug: a payment changes outside the IPN (a pre-auth capture or void, a subscription renewal) and the stored
	 * status stays behind, so the fetch path takes a SUCCEEDED of a later cancelled order for a forward move.
	 */
	public function test_captures_voids_and_renewals_store_the_monei_status() {
		$store = '$order->update_meta_data(\'_payment_order_status_monei\',$result->getStatus());$order->save_meta_data();';
		$this->assertStringContainsString( $store, $this->method( 'includes/class-wc-monei-pre-auth.php', 'capture_payment_when_pre_auth' ) );
		$this->assertStringContainsString( $store, $this->method( 'includes/class-wc-monei-pre-auth.php', 'cancel_payment_when_pre_auth' ) );
		foreach ( array( 'src/Features/Subscriptions/WooCommerceSubscriptionsHandler.php', 'src/Features/Subscriptions/YithSubscriptionPluginHandler.php' ) as $handler ) {
			$this->assertStringContainsString(
				'$renewal_order->update_meta_data(\'_payment_order_status_monei\',$payment->getStatus());$renewal_order->payment_complete($payment->getId());',
				$this->method( $handler, 'scheduled_subscription_payment' ),
				$handler
			);
		}
	}

	/**
	 * Bug: the order lookup of the fetch path hides the stored MONEI status (an unsigned request replays a SUCCEEDED
	 * of a cancelled order), whether the order is paid, or the gateway (an order of another gateway is fetched).
	 */
	public function test_the_ipn_order_lookup_gives_the_stored_status_paid_and_the_gateway() {
		$constructor = $this->method( self::IPN, '__construct' );

		$this->assertStringContainsString( '\'paymentStatus\'=>(string)$order->get_meta(\'_payment_order_status_monei\',true),', $constructor );
		$this->assertStringContainsString( '\'paid\'=>$order->is_paid(),', $constructor );
		$this->assertStringContainsString( '\'monei\'=>false!==strpos($order->get_payment_method(),\'monei\'),', $constructor );
	}

	/**
	 * Bug: a connected shop refuses a callback without MONEI-Signature (an account whose v2 read failed sends
	 * none), or a manual shop stops refusing it.
	 */
	public function test_the_missing_signature_401_applies_only_without_a_scoped_key() {
		$body = $this->method( self::IPN, 'check_ipn_request' );

		$this->assertSame( 1, preg_match( '/if\(!isset\(\$_SERVER\[\'HTTP_MONEI_SIGNATURE\'\]\)&&!\$this->callbackVerifier->any_mode_connected\(\)\)\{[^}]*http_response_code\(401\)/', $body ) );
	}

	/**
	 * Bug: a 401 of the old key keeps MONEI hidden at checkout for up to a day after the connect.
	 */
	public function test_the_switch_deletes_the_payment_methods_backoff() {
		$callback = $this->method( self::HANDLER, 'callback' );

		$this->assertStringContainsString( 'if($result[\'switched\']){$this->after_switch($result[\'switched\']);}', $callback );
		$this->assertStringContainsString( 'delete_option(PaymentMethodsRepository::BACKOFF_OPTION);', $this->method( self::HANDLER, 'after_switch' ) );
	}

	/**
	 * Bug: a caller who is not an admin of the shop stores a state, then consents with an attacker's MONEI account.
	 */
	public function test_the_start_checks_the_admin_before_it_stores_a_state() {
		$start = $this->method( self::HANDLER, 'start' );

		$this->assertBefore( 'if(!current_user_can(\'manage_woocommerce\')){wp_die(', 'check_admin_referer(\'monei_connect_start\');', $start, 'capability before nonce' );
		$this->assertBefore( 'check_admin_referer(\'monei_connect_start\');', '->begin(', $start, 'nonce before the state write' );
	}

	/**
	 * Bug: a forged disconnect from a signed-in admin's browser (a GET link or a cross-site POST) clears the
	 * credentials and stops checkout.
	 */
	public function test_the_disconnect_checks_post_admin_and_nonce_before_it_clears() {
		$disconnect = $this->method( self::HANDLER, 'disconnect' );

		$this->assertBefore( '\'POST\'!==$_SERVER[\'REQUEST_METHOD\']){wp_die(', 'current_user_can(\'manage_woocommerce\')', $disconnect, 'POST before capability' );
		$this->assertBefore( 'if(!current_user_can(\'manage_woocommerce\')){wp_die(', 'check_admin_referer(\'monei_disconnect\',\'_monei_disconnect_nonce\');', $disconnect, 'capability before nonce' );
		$this->assertBefore( 'check_admin_referer(\'monei_disconnect\',\'_monei_disconnect_nonce\');', '->disconnect();', $disconnect, 'nonce before the clear' );
	}

	/**
	 * Bug: the Disconnect button sits in the WooCommerce settings form as its default button, so Enter in a key
	 * field disconnects.
	 */
	public function test_the_disconnect_button_submits_a_separate_form() {
		$section = $this->method( self::SETTINGS, 'render_connect_section' );
		$form    = $this->method( self::SETTINGS, 'print_disconnect_form' );

		$this->assertStringContainsString( '<button type="submit" id="monei-disconnect" class="button" form="<?php echoesc_attr(ConnectHandler::DISCONNECT_FORM_ID);?>">', $section );
		$this->assertStringContainsString( '<form id="<?php echoesc_attr(ConnectHandler::DISCONNECT_FORM_ID);?>" method="post"', $form );
		$this->assertStringContainsString( 'wp_nonce_field(\'monei_disconnect\',\'_monei_disconnect_nonce\');', $form );
		$this->assertStringNotContainsString( '<form', $section );
		$this->assertStringContainsString( 'add_action(\'admin_footer\',array($this,\'print_disconnect_form\'));', $this->method( self::SETTINGS, '__construct' ) );
	}

	/**
	 * Bug: a route answers without a session, or never registers on admin-post.php (which never builds the
	 * settings pages).
	 */
	public function test_the_routes_are_admin_post_hooks_added_in_includes() {
		foreach ( array( self::PLUGIN, self::HANDLER, self::SETTINGS, self::IPN ) as $file ) {
			$this->assertStringNotContainsString( 'admin_post_nopriv_', $this->code( $file ), $file );
		}
		$includes = $this->method( self::PLUGIN, 'includes' );
		$routes   = 'if($this->is_request(\'admin\')){$connect_handler=newConnectHandler();'
			. 'add_action(\'admin_post_monei_connect_start\',array($connect_handler,\'start\'));'
			. 'add_action(\'admin_post_monei_connect\',array($connect_handler,\'callback\'));'
			. 'add_action(\'admin_post_monei_disconnect\',array($connect_handler,\'disconnect\'));'
			. 'add_filter(\'woocommerce_get_settings_pages\',';
		$this->assertStringContainsString( $routes, $includes );
	}

	/**
	 * Bug: the callback runs for a non-admin, tests a new key on the cached client of the old key, or loses a
	 * working switch to an Apple Pay error.
	 */
	public function test_the_callback_checks_the_admin_and_tests_on_a_fresh_client() {
		$callback = $this->method( self::HANDLER, 'callback' );
		$switch   = $this->method( self::HANDLER, 'after_switch' );

		$this->assertBefore( 'if(!current_user_can(\'manage_woocommerce\')){wp_die(', '->complete(', $callback, 'capability before the connect' );
		$this->assertStringNotContainsString( 'get_client()', $this->code( self::HANDLER ) );
		$this->assertStringContainsString( 'self::client_factory()->client_for_key($api_key)->paymentMethods->getAllowed();', $this->method( self::HANDLER, 'connect_service' ) );
		$this->assertStringContainsString( 'return$this->build_client($api_key,newConfiguration());', $this->method( 'src/Services/sdk/MoneiSdkClientFactory.php', 'client_for_key' ) );
		$this->assertSame( 1, preg_match( '/try\{MoneiApplePayVerificationService::register_domain\(self::client_factory\(\)->client_for_key\(\$keys\[\'apiKey\'\]\)\);\}catch\(Throwable\$e\)/', $switch ) );
	}

	/**
	 * Bug: the code in the callback URL leaves in a Referer from the result page.
	 */
	public function test_the_callback_sends_no_referrer_first() {
		$this->assertStringStartsWith( 'functioncallback():void{header(\'Referrer-Policy: no-referrer\');', $this->method( self::HANDLER, 'callback' ) );
	}

	/**
	 * Bug: a manual key change keeps the signing secret of the replaced key; or a tab rendered before a connect
	 * saves its old key fields over the new keys, because the form carries no fingerprints or the save ignores them.
	 */
	public function test_the_settings_save_runs_the_secret_rule() {
		$save = $this->method( self::SETTINGS, 'save' );

		$this->assertStringContainsString( 'name="monei_key_fingerprint[<?php echoesc_attr($mode);?>]"', $this->method( self::SETTINGS, 'render_connect_section' ) );
		$this->assertBefore( '$changed=$connect->modes_changed_since($fingerprints);', 'WC_Admin_Settings::save_fields($settings);', $save, 'changed modes found before the save' );
		$this->assertBefore( '$connect->scoped_modes_left_empty($posted_keys)', 'WC_Admin_Settings::save_fields($settings);', $save, 'empty connected key fields found before the save' );
		$this->assertBefore( 'return!in_array($field[\'id\']??\'\',$skipped,true);', 'WC_Admin_Settings::save_fields($settings);', $save, 'their fields dropped before the save' );
		$this->assertStringContainsString( 'if($changed){$skipped[]=\'monei_apikey_mode\';', $save, 'a stale save keeps the mode too' );

		$this->assertBefore( '$keys_before=$connect->api_keys();', 'WC_Admin_Settings::save_fields($settings);', $save, 'keys read before the save' );
		$this->assertBefore( 'WC_Admin_Settings::save_fields($settings);', '$connect->forget_secrets_of_changed_keys($keys_before);', $save, 'rule after the save' );
	}

	/**
	 * Bug: the tab shows the button for a callback URL the broker refuses (an http shop), or the start sends
	 * another URL than the tab checked.
	 */
	public function test_the_tab_and_the_start_share_the_checked_callback_url() {
		$section = $this->method( self::SETTINGS, 'render_connect_section' );
		$this->assertStringContainsString( '$can_connect=null!==ConnectHandler::redirect_uri();', $section );
		$this->assertSame( 1, preg_match( '/<\?php if\(\$can_connect\):\?>\s*<a id="monei-connect"/', $section ) );
		$this->assertStringContainsString( '$redirect_uri=self::redirect_uri();', $this->method( self::HANDLER, 'start' ) );
		$this->assertStringContainsString(
			'try{$uri=Connect::canonicalRedirectUri(self::callback_url());}catch(InvalidArgumentException$e){returnnull;}returnnull===Connect::callbackProblem(ConnectService::CLIENT,$uri)?$uri:null;',
			$this->method( self::HANDLER, 'redirect_uri' )
		);
	}

	/**
	 * Bug: a refused connect hides what to do: cap must say to revoke unused keys, not_approved that Live comes
	 * after approval and the shop stays in test mode.
	 */
	public function test_the_cap_and_not_approved_messages_say_what_to_do() {
		$messages = $this->method( self::HANDLER, 'problem_message' );

		$this->assertSame( 1, preg_match( '/\'cap\'=>__\(\'[^\']*Revoke unused keys in the MONEI Dashboard/', $messages ) );
		$this->assertSame( 1, preg_match( '/\'not_approved\'=>__\(\'Live keys come after MONEI approves your account\. The shop stays in test mode\.\'/', $messages ) );
	}
}
