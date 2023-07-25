<?php
/**
 * Plugin Name: Everest Forms - OpenAI
 * Plugin URI: https://wpeverest.com/wordpress-plugins/everest-forms/openai/
 * Description: Everest Forms OpenAI addon allows you to add OpenAI into your forms.
 * Version: 1.0.0
 * Author: WPEverest
 * Author URI: https://wpeverest.com
 * Text Domain: everest-forms-openai
 * Domain Path: /languages/
 * Requires at least: 5.0
 * Requires PHP: 5.6.20
 *
 * EVF requires at least: 1.8.6
 * EVF tested up to: 1.8.6
 *
 * @package EverestForms\OpenAI
 */

defined( 'ABSPATH' ) || exit;

// Define plugin version.
if ( ! defined( 'EVF_OPENAI_VERSION' ) ) {
	define( 'EVF_OPENAI_VERSION', '1.0.0' );
}

// Define plugin root file.
if ( ! defined( 'EVF_OPENAI_PLUGIN_FILE' ) ) {
	define( 'EVF_OPENAI_PLUGIN_FILE', __FILE__ );
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
				esc_html__( 'Your installation of the Everest Forms - OpenAI plugin is incomplete. Please run %1$s within the %2$s directory.', 'everest-forms-openai' ),
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
						esc_html__( 'Your installation of the Everest Forms - OpenAI plugin is incomplete. Please run %1$s within the %2$s directory.', 'everest-forms-openai' ),
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
add_action( 'plugins_loaded', array( 'EverestForms\\OpenAI\\OpenAI', 'instance' ) );
