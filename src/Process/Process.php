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
	}

	/**
	 * Process form after validation.
	 *
	 * @param mixed $form_fields Form Fields.
	 * @param mixed $entry Entry.
	 * @param mixed $form_data Form Data.
	 * @return mixed $form_fields Form Fields.
	 */
	public function process_filter( $form_fields, $entry, $form_data ) {
		foreach ( $form_data['form_fields'] as $key => $field ) {
			if ( array_key_exists( 'ai_input', $field ) ) {
					$ai_prompt = $field['ai_input'];
					preg_match_all( '/\{field_id="(.+?)"\}/', $ai_prompt, $ids );

				if ( ! empty( $ids[1] ) ) {
					foreach ( $ids[1] as $key => $field_id ) {
						$mixed_field_id = explode( '_', $field_id );
						if ( count( $mixed_field_id ) > 1 && ! empty( $form_fields[ $mixed_field_id[1] ] ) ) {
							$providers = get_option( 'everest_forms_openai_settings', array() );
							$api_key   = ! empty( $providers['api_key'] ) ? $providers['api_key'] : '';
							$response  = new API( $api_key );
							$data      = array(
								'messages'    => array(
									array(
										'role'    => 'user',
										'content' => apply_filters( 'everest_forms_process_smart_tags', $ai_prompt, $form_data, $form_fields ),
									),
								),
								'temperature' => 0.5,
							);
							$content   = $response->send_openai_request( 'chat/completions', $data );
							lg( $content );
							// $message   = isset( $content['choices'][0]['message']['content'] ) ? esc_html( $content['choices'][0]['message']['content'] ) :

						}
					}
				}
			}
		}
		return $form_fields;
	}


}
