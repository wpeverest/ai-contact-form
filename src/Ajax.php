<?php
/**
 * OpenAI Ajax.
 *
 * @package EverestForms\OpenAI\Ajax
 * @since   1.0.0
 */

namespace EverestForms\OpenAI;

use EverestForms\OpenAI\API\API;

defined( 'ABSPATH' ) || exit;

/**
 * Ajax Class.
 *
 * @since 1.0.0
 */
class Ajax {

	/**
	 * Hook in tabs.
	 */
	public function __construct() {
		self::add_ajax_events();
	}

	/**
	 * Hook in methods - uses WordPress ajax handlers (admin-ajax)
	 *
	 * @since 1.0.0
	 */
	public static function add_ajax_events() {

		$ajax_events = array(
			'chat_bot' => true,
		);
		foreach ( $ajax_events as $ajax_event => $nopriv ) {

			add_action( 'wp_ajax_everest_forms_openai_' . $ajax_event, array( __CLASS__, $ajax_event ) );

			if ( $nopriv ) {

				add_action(
					'wp_ajax_nopriv_everest_forms_openai_' . $ajax_event,
					array(
						__CLASS__,
						$ajax_event,
					)
				);
			}
		}
	}

	/**
	 * Handle the chat bot functionality.
	 *
	 * @since 1.0.0
	 */
	public static function chat_bot() {

		if ( ! check_ajax_referer( 'everest_forms_openai', 'security', false ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'Nonce error, please reload.', 'everest-forms-openai' ),
				)
			);
		}
		$form_id    = isset( $_POST['form_id'] ) ? absint( $_POST['form_id'] ) : 0;
		$form_data  = json_decode( evf()->form->get( $form_id )->post_content, true );
		$form_field = is_array( $form_data ) && ! empty( $form_data['form_fields'] ) ? $form_data['form_fields'] : array();
		$providers  = get_option( 'everest_forms_openai_settings', array() );
		$api_key    = ! empty( $providers['api_key'] ) ? $providers['api_key'] : '';
		$chat_reply = isset( $_POST['chat'] ) ? $_POST['chat'] : ''; //phpcs:ignore.
		$response   = new API( $api_key );
		foreach ( $form_field as $field_id => $field_value ) {
			if ( isset( $field_value['ai_chatbot'] ) && '1' === $field_value['ai_chatbot'] ) {
				$field_type = isset( $field_value['ai_type'] ) ? $field_value['ai_type'] : 'html';
				$field_id   = isset( $field_value['id'] ) ? $field_value['id'] : '';
				$data       = array(
					'messages'    => array(
						array(
							'role'    => 'user',
							'content' => $chat_reply,
						),
					),
					'temperature' => 0.5,
				);
				$content    = $response->send_openai_request( 'chat/completions', $data );
				$message    = isset( $content['choices'][0]['message']['content'] ) ? wp_kses_post( $content['choices'][0]['message']['content'] ) : '';
				wp_send_json_success(
					array(
						'message'    => $message,
						'field_type' => $field_type,
						'field_id'   => $field_id,
					)
				);
			}
		}

	}
}
