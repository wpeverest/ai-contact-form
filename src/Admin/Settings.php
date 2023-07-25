<?php
/**
 * Open AI Settings.
 *
 * @package EverestForms\OpenAI\Admin
 * @since   1.0.0
 */

 namespace EverestForms\OpenAI\Admin;

 use EverestForms\OpenAI\API\API;
 defined( 'ABSPATH' ) || exit;

/**
 * Open AI Integration.
 */
class Settings extends \EVF_Integration {


	/**
	 * Client.
	 *
	 * @var object
	 */
	public $client;

	/**
	 * Init and hook in the integration.
	 */
	public function __construct() {
		$this->id                 = 'openai';
		$this->icon               = plugins_url( '/assets/images/twilio.png', EVF_OPENAI_PLUGIN_FILE );
		$this->method_title       = esc_html__( 'Open AI', 'everest-forms-openai' );
		$this->method_description = esc_html__( 'Open AI Integration with Everest Forms', 'everest-forms-openai' );
		$this->auth_code          = $this->get_option( 'api_key' );
		$this->integration        = $this->get_integration();
		$this->account_status     = empty( $this->auth_code ) ? 'disconnected' : 'connected';

		// Open AI API Authenticate.
		add_action( 'everest_forms_integration_account_connect_' . $this->id, array( $this, 'api_authenticate' ) );
		add_action( 'everest_forms_integration_account_disconnect_' . $this->id, array( $this, 'api_deauthenticate' ) );
	}


		/**
		 * Open AI authenticate.
		 *
		 * @param array $posted_data Posted client credentials.
		 */
	public function api_authenticate( $posted_data ) {

		$this->client_key = $this->get_field_value( 'api_key', array(), $posted_data );

		if ( empty( $this->client_key ) ) {
			wp_send_json_error(
				array(
					'error_msg' => esc_html__( 'Please fill the full details', 'everest-forms-openai' ),
				)
			);
		}

		try {
			$auth             = new API( $this->client_key );
			$is_authenticated = $auth->authentication();
			if ( isset( $is_authenticated['code'] ) && 200 !== $is_authenticated['code'] ) {
				wp_send_json_error(
					array(
						'error'     => esc_html__( 'Could not connect to the provider.', 'everest-forms-openai' ),
						'error_msg' => isset( $is_authenticated['message'] ) ? $is_authenticated['message'] : '',
					)
				);
			}
			$this->update_option( 'api_key', $this->client_key );
			wp_send_json_success(
				array(
					'button'      => esc_html__( 'Remove Authentication', 'everest-forms-openai' ),
					'description' => esc_html__( 'Open AI account authenticated.', 'everest-forms-openai' ),
				)
			);
		} catch ( \Exception $e ) {
			wp_send_json_error(
				array(
					'error'     => esc_html__( 'Could not connect to the provider.', 'everest-forms-openai' ),
					'error_msg' => esc_html__( 'Unauthorized Account sid or Auth token.', 'everest-forms-openai' ),
				)
			);
		}

	}

		/**
		 * Google Drive API authenticate.
		 *
		 * @param array $posted_data Posted client credentials.
		 */
	public function api_deauthenticate( $posted_data ) {
		$this->init_settings();
		if (
			empty( $posted_data['key'] )
			&& $this->id === $posted_data['source']
		) {
			update_option( $this->get_option_key(), array() );
			wp_send_json_success(
				array(
					'remove' => false,
				)
			);
		}
	}


	/**
	 * Facilitates Open AI integration. Evoked from extensibility.
	 */
	public function output_integration() {
		?>
		<div class="everest-forms-integration-content">
			<div class="integration-addon-detail">
				<div class="evf-integration-info-header">
					<figure class="evf-integration-logo">
						<img src="<?php echo esc_attr( $this->icon ); ?>" alt="<?php echo esc_attr( 'Open AI Icon' ); ?>">
					</figure>
					<div class="integration-info">
						<h3><?php echo $this->method_title; /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped */ ?></h3>
						<div class="integration-status  <?php echo esc_attr( $this->account_status ); ?>">
						<span class="toggle-switch <?php echo esc_attr( $this->account_status ); ?>">
								<?php if ( 'connected' === $this->account_status ) : ?>
									<?php esc_html_e( 'Connected', 'everest-forms-openai' ); ?>
								<?php endif; ?>
							</span>
						</div>
					</div>
				</div>
				<p><?php echo $this->method_description; /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped */ ?></p>
			</div>
			<div class="integration-connection-detail">
				<div class="evf-account-connect">
				<h3><?php esc_attr_e( 'Open AI Sms Setting', 'everest-forms-openai' ); ?></h3>
					<?php if ( empty( $this->auth_code ) ) : ?>
						<p><?php esc_html_e( 'Please fill out the fields to connect your Open AI account.', 'everest-forms-openai' ); ?></p>
					<?php else : ?>
						<p><?php esc_html_e( 'Open AI account authenticated.', 'everest-forms-openai' ); ?></p>
					<?php endif; ?>
					<form>
					<?php if ( empty( $this->auth_code ) ) : ?>
						<div class="evf-connection-form ">
							<div class="evf-connection-form everest-forms-openai">
							<strong><?php esc_html_e( 'Api Key', 'everest-forms-openai' ); ?></strong><br>
							<input type="text" name="<?php echo esc_attr( $this->get_field_key( 'api_key' ) ); ?>" id="<?php echo esc_attr( $this->get_field_key( 'api_key' ) ); ?>" class="<?php echo esc_attr( $this->get_field_key( 'api_key' ) ); ?>" placeholder="<?php esc_attr_e( 'Enter Open API Key', 'everest-forms-openai' ); ?>" value="">
							</div>
						</div>
							<a href="#" class="everest-forms-btn everest-forms-btn-primary everest-forms-integration-connect-account" data-source="<?php echo esc_attr( $this->id ); ?>">
								<?php esc_html_e( 'Authenticate with Open AI', 'everest-forms-openai' ); ?>
							</a>
						<?php else : ?>
							<a href="#" class="everest-forms-btn everest-forms-btn-secondary everest-forms-integration-disconnect-account" data-source="<?php echo esc_attr( $this->id ); ?>">
								<?php esc_html_e( 'Remove Authentication', 'everest-forms-openai' ); ?>
							</a>
						<?php endif; ?>
					</form>
				</div>
			</div>
		</div>
		<?php
	}
}
