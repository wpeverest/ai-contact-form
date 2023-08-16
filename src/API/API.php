<?php
/**
 * EverestForms AI API Class.
 *
 * @package EverestForms\AI\API\API
 * @since   1.0.0
 */

namespace EverestForms\AI\API;

defined( 'ABSPATH' ) || exit;

/**
 * Class API
 */
class API {

	/**
	 * API key.
	 *
	 * @var String
	 */
	private $api_key;

	/**
	 * API end point.
	 *
	 * @var string
	 */
	private $endpoint = 'https://api.openai.com/v1/';

	/**
	 * Timeout.
	 */
	const TIMEOUT = 30;

	/**
	 * SSL Verification
	 *
	 * @var boolean
	 */
	public $verify_ssl = true;

	/**
	 * Create a new instance
	 *
	 * @since 1.0.0
	 * @param string $api_key Your AI API key.
	 */
	public function __construct( $api_key ) {
		$this->api_key = $api_key;
	}

	/**
	 * Send a request to ChatGPT API.
	 *
	 * @since 1.0.0
	 * @param string $path API path.
	 * @param array  $data Data to be sent in the request.
	 * @param int    $max_tokens Maximum number of tokens in the response.
	 * @param string $method HTTP method.
	 * @return array|bool Response from the AI API.
	 */
	public function send_openai_request( $path, $data = array(), $max_tokens = 3000, $method = 'POST' ) {

		$api_url = $this->endpoint . $path;

		$default_data       = array(
			'model'       => 'gpt-3.5-turbo-0301',
			'messages'    => array(
				array(
					'role'    => 'system',
					'content' => 'You are a helpful assistant.',
				),
			),
			'temperature' => 0.7,
		);
		$data               = array_merge( $default_data, $data );
		$data['max_tokens'] = $max_tokens;
		$headers            = array(
			'content-type'  => 'application/json',
			'Authorization' => 'Bearer ' . $this->api_key,
		);

		$args = array(
			'method'    => $method,
			'headers'   => $headers,
			'body'      => wp_json_encode( $data ),
			'sslverify' => $this->verify_ssl,
			'timeout'   => self::TIMEOUT,
		);

		$response = wp_remote_request( $api_url, $args );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		return json_decode( $response['body'], true );
	}
	/**
	 * Authenticate using API key.
	 *
	 * @since 1.0.0
	 * @return bool|array True on success, or an error array.
	 */
	public function authentication() {
		$path    = 'models';
		$headers = array(
			'Authorization' => 'Bearer ' . $this->api_key,
		);

		$args = array(
			'method'    => 'GET',
			'headers'   => $headers,
			'sslverify' => $this->verify_ssl,
			'timeout'   => self::TIMEOUT,
		);

		$response = wp_remote_request( $this->endpoint . $path, $args );
		$code     = wp_remote_retrieve_response_code( $response );
		$body     = wp_remote_retrieve_body( $response );

		if ( is_wp_error( $response ) || 200 !== $code ) {
			$error_message = '';
			$body          = json_decode( $body, true );

			if ( is_array( $body ) && isset( $body['error']['message'] ) ) {
				$error_message = $body['error']['message'];
			}
			$code = is_int( $code ) ? $code : 500;
			return array(
				'code'    => $code,
				'message' => $error_message,
			);
		}

		$response_code = wp_remote_retrieve_response_code( $response );
		return 200 === $response_code;
	}
}
