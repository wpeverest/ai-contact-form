<?php
/**
 * OpenAI Process.
 *
 * @package EverestForms\OpenAI\Process
 * @since   1.0.0
 */

namespace EverestForms\OpenAI\Process;

use EverestForms\OpenAI\API\API;

defined( 'ABSPATH' ) || exit;

/**
 * Process Class.
 *
 * @since 1.0.0
 */
class Process {


	/**
	 * Primary class constructor.
	 */
	public function __construct() {
		add_filter( 'everest_forms_process_filter', array( $this, 'process_filter' ), 10, 3 );
		add_action( 'everest_forms_entry_email_atts', array( $this, 'process_message' ), 10, 4 );
	}


	/**
	 * Process Message.
	 *
	 * @since 1.0.0
	 *
	 * @param array $email     Emails.
	 * @param array $fields    Fields for the Form.
	 * @param array $entry     Form Entry.
	 * @param array $form_data Form Data object.
	 */
	public function process_message( $email, $fields, $entry, $form_data ) {
		$email['message']   = apply_filters( 'everest_forms_process_smart_tags', $email['message'], $form_data, $fields );
		$emailMessage       = $email['message'];
		$emailPrompt        = apply_filters( 'everest_forms_process_smart_tags', $email['message_ai_prompt'], $form_data, $fields );
		$providers          = get_option( 'everest_forms_open_ai_api_key' );
		$api_key            = ! empty( $providers ) ? $providers : '';
		$response           = new API( $api_key );
		$analysis_data      = array(
			'messages'    => array(
				array(
					'role'    => 'user',
					'content' => "Analyze the following prompt and provide a suitable response.\n\nPrompt:\n\"" . $emailPrompt . '"',
				),
			),
			'temperature' => 0.5,
		);
		$analysis_content   = $response->send_openai_request( 'chat/completions', $analysis_data );
		$generated_analysis = isset( $analysis_content['choices'][0]['message']['content'] ) ? wp_strip_all_tags( $analysis_content['choices'][0]['message']['content'] ) : '';
		if ( preg_match( '/\{ai_email_response\}/', $emailMessage ) ) {
			$email['message'] = str_replace( '{ai_email_response}', $generated_analysis, $emailMessage );
		}
		return $email;
	}


	/**
	 * Process form after validation.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $form_fields Form Fields.
	 * @param mixed $entry Entry.
	 * @param mixed $form_data Form Data.
	 * @return mixed $form_fields Form Fields.
	 */
	public function process_filter( $form_fields, $entry, $form_data ) {
		foreach ( $form_data['form_fields'] as $key => $field ) {
			if ( array_key_exists( 'ai_input', $field ) ) {
					$ai_prompt                    = $field['ai_input'];
					$providers                    = get_option( 'everest_forms_open_ai_api_key' );
					$api_key                      = ! empty( $providers ) ? $providers : '';
					$response                     = new API( $api_key );
					$data                         = array(
						'messages'    => array(
							array(
								'role'    => 'user',
								'content' => apply_filters( 'everest_forms_process_smart_tags', $ai_prompt, $form_data, $form_fields ),
							),
						),
						'temperature' => 0.5,
					);
					$content                      = $response->send_openai_request( 'chat/completions', $data );
					$message                      = isset( $content['choices'][0]['message']['content'] ) ? esc_html( $content['choices'][0]['message']['content'] ) : '';
					$form_fields[ $key ]['value'] = $message;
			}
		}
		return $form_fields;
	}
}
