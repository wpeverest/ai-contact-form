<?php
/**
 * Plugin Name: AI Contact Form.
 * Plugin URI: https://everestforms.net/features/ai/
 * Description: Everest Forms AI offers Chatbot functionality, Email Prompt assistance, and the capability for field analysis.
 * Version: 1.0.1
 * Author: WPEverest
 * Author URI: https://wpeverest.com
 * Text Domain: ai-contact-form
 * Domain Path: /languages/
 * Requires at least: 5.2
 * Requires PHP: 7.2.0
 *
 * EVF requires at least: 2.0.3
 * EVF tested up to: 2.0.3
 *
 * @package EverestForms\AI
 */

defined( 'ABSPATH' ) || exit;

// Define plugin version.
if ( ! defined( 'EVF_AI_VERSION' ) ) {
	define( 'EVF_AI_VERSION', '1.0.1' );
}

// Define plugin root file.
if ( ! defined( 'EVF_AI_PLUGIN_FILE' ) ) {
	define( 'EVF_AI_PLUGIN_FILE', __FILE__ );
}

/**
 * Autoload packages.
 *
 * We want to fail gracefully if `composer install` has not been executed yet, so we are checking for the autoloader.
 * If the autoloader is not present, let's log the failure and display a nice admin notice.
 */
$autoloader = __DIR__ . '/vendor/autoload.php';
if ( is_readable( $autoloader ) ) {
	require $autoloader;
} else {
	if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
		error_log( // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			sprintf(
				/* translators: 1: composer command. 2: plugin directory */
				esc_html__( 'Your installation of the Everest Forms - AI plugin is incomplete. Please run %1$s within the %2$s directory.', 'ai-contact-form' ),
				'`composer install`',
				'`' . esc_html( str_replace( ABSPATH, '', __DIR__ ) ) . '`'
			)
		);
	}

	/**
	 * Outputs an admin notice if composer install has not been ran.
	 */
	add_action(
		'admin_notices',
		function() {
			?>
			<div class="notice notice-error">
				<p>
					<?php
					printf(
						/* translators: 1: composer command. 2: plugin directory */
						esc_html__( 'Your installation of the Everest Forms - AI plugin is incomplete. Please run %1$s within the %2$s directory.', 'ai-contact-form' ),
						'<code>composer install</code>',
						'<code>' . esc_html( str_replace( ABSPATH, '', __DIR__ ) ) . '</code>'
					);
					?>
				</p>
			</div>
			<?php
		}
	);
	return;
}

// Initialize the plugin.
add_action( 'plugins_loaded', array( 'EverestForms\\AI\\AI', 'instance' ) );
