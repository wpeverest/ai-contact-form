<?php
/**
 * Open AI Settings.
 *
 * @package EverestForms\AI\Admin
 * @since   1.0.0
 */

 namespace EverestForms\AI\Admin;

 use EverestForms\AI\API\API;
 use EVF_Admin_Settings;
 defined( 'ABSPATH' ) || exit;

/**
 * Open AI Integration.
 */
class Settings extends \EVF_Settings_Page {
	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id    = 'ai';
		$this->label = esc_html__( 'OpenAI', 'ai-contact-form' );
		parent::__construct();
	}

		/**
		 * Get settings array.
		 *
		 * @return array
		 */
	public function get_settings() {
		$settings = apply_filters(
			'everest_forms_ai_settings',
			array(
				array(
					'title' => esc_html__( 'OpenAI', 'ai-contact-form' ),
					'type'  => 'title',
					/* translators: %1$s - Ai docs url */
					'desc'  => sprintf( __( '<p>Everest Forms AI offers Chatbot functionality, Email Prompt assistance, and the capability for field analysis.</p><p>Get detailed documentation on  integrating<a href="%1$s" target="_blank"> OpenAI.</a></p>', 'ai-contact-form' ), 'https://docs.everestforms.net/docs/ai/' ),
					'id'    => 'everest_forms_ai_options',
				),
				array(
					'title'    => esc_html__( 'OpenAI API Key', 'ai-contact-form' ),
					'type'     => 'text',
					/* translators: %1$s - Google API docs url */
					'desc'     => sprintf( esc_html__( 'Please enter your API key of OpenAI. <a href="%1$s" target="_blank">Learn More</a>', 'ai-contact-form' ), esc_url( 'https://docs.everestforms.net/docs/ai/' ) ),
					'id'       => 'everest_forms_ai_api_key',
					'default'  => '',
					'desc_tip' => true,
				),
				array(
					'type' => 'sectionend',
					'id'   => 'everest_forms_ai_options',
				),
			)
		);

		return apply_filters( 'everest_forms_get_settings_' . $this->id, $settings );
	}

	/**
	 * Save settings.
	 */
	public function save() {
		$settings = $this->get_settings();
		\EVF_Admin_Settings::save_fields( $settings );
	}
}
