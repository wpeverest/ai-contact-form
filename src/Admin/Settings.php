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
class Settings extends \EVF_Settings_Page {
	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id    = 'ai';
		$this->label = esc_html__( 'AI', 'everest-forms-ai' );
		parent::__construct();
	}

		/**
		 * Get settings array.
		 *
		 * @return array
		 */
	public function get_settings() {
		$settings = apply_filters(
			'everest_forms_geolocation_settings',
			array(
				array(
					'title' => esc_html__( 'Everest Forms AI', 'everest-forms-ai' ),
					'type'  => 'title',
					/* translators: %1$s - Ai docs url */
					'desc'  => sprintf( __( '<p>AI provides.</p><p><a href="%1$s" target="_blank">Read our documentation</a> for step-by-step instructions.</p>', 'everest-forms-geolocation' ), 'https://docs.wpeverest.com/everest-forms/docs/geolocation/' ),
					'id'    => 'everest_forms_ai_options',
				),
				array(
					'title'    => esc_html__( 'OpenAI API Key', 'everest-forms-ai' ),
					'type'     => 'text',
					/* translators: %1$s - Google API docs url */
					'desc'     => sprintf( esc_html__( 'Please enter your API key of OpenAI. <a href="%1$s" target="_blank">Learn More</a>', 'everest-forms' ), esc_url( 'https://docs.wpeverest.com/everest-forms/docs/geolocation/' ) ),
					'id'       => 'everest_forms_open_ai_api_key',
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
		EVF_Admin_Settings::save_fields( $settings );
	}
}
