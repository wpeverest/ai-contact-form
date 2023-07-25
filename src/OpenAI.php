<?php
/**
 * Main plugin class.
 *
 * @package EverestForms\OpenAI
 * @since   1.0.0
 */

namespace EverestForms\OpenAI;

use EverestForms\OpenAI\API\API;
use EverestForms\OpenAI\Process\Process;
/**
 * Main plugin class.F
 *
 * @since 1.0.0
 */
class OpenAI {

	/**
	 * The single instance of the class.
	 *
	 * @var object
	 *
	 * @since 1.0.0
	 */
	protected static $instance;

	/**
	 * Logger Instance
	 *
	 * @var object
	 */
	public static $log = false;

	/**
	 * Cloning is forbidden.
	 *
	 * @since 1.0.0
	 */
	public function __clone() {
		_doing_it_wrong( __FUNCTION__, esc_html__( 'Cloning is forbidden.', 'everest-forms-openai' ), '1.0.0' );
	}

	/**
	 * Unserializing instances of this class is forbidden.
	 *
	 * @since 1.0.0
	 */
	public function __wakeup() {
		_doing_it_wrong( __FUNCTION__, esc_html__( 'Unserializing instances of this class is forbidden.', 'everest-forms-openai' ), '1.0.0' );
	}

	/**
	 * Main plugin class instance.
	 *
	 * @since 1.0.0
	 *
	 * Ensures only one instance of the plugin is loaded or can be loaded.
	 *
	 * @return object Main instance of the class.
	 */
	final public static function instance() {
		if ( is_null( static::$instance ) ) {
			static::$instance = new static();
		}
		return static::$instance;
	}

	/**
	 * Plugin Constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		add_action( 'init', array( $this, 'load_plugin_textdomain' ) );
		add_filter( 'everest_forms_integrations', array( $this, 'add_integration' ) );
		add_filter( 'plugin_action_links_' . plugin_basename( EVF_OPENAI_PLUGIN_FILE ), array( $this, 'plugin_action_links' ) );
		add_filter( 'plugin_row_meta', array( $this, 'plugin_row_meta' ), 20, 2 );

		// Checks with Everest Forms is installed.
		if ( defined( 'EVF_VERSION' ) && version_compare( EVF_VERSION, '1.9.3', '>=' ) ) {

			// Checks with Everest Forms Pro is installed.
			if ( defined( 'EFP_VERSION' ) && version_compare( EFP_VERSION, '1.5.8', '>=' ) ) {
				// Hooks.
				add_action( 'everest_forms_init', array( $this, 'plugin_updater' ) );
				add_action( 'everest_forms_init', array( $this, 'openai_init' ) );
				add_filter( 'everest_forms_fields', array( $this, 'form_fields' ) );

			} else {
				add_action( 'admin_notices', array( $this, 'everest_forms_pro_missing_notice' ) );
			}
		} else {
			add_action( 'admin_notices', array( $this, 'everest_forms_missing_notice' ) );
		}
	}

	/**
	 * Logging method.
	 *
	 * @param string $message Log message.
	 * @param string $level Optional. Default 'info'. Possible values:
	 *                      emergency|alert|critical|error|warning|notice|info|debug.
	 */
	public static function log( $message, $level = 'info' ) {
		if ( empty( self::$log ) ) {
			self::$log = evf_get_logger();
		}
		self::$log->log( $level, $message, array( 'source' => 'openai' ) );
	}

	/**
	 * Init OpenAI - Logic begins.
	 */
	public function openai_init() {
		new Process();
	}

	/**
	 * Load ai fields available in the addon.
	 *
	 * @param  array $fields Registered form fields.
	 * @return array
	 */
	public function form_fields( $fields ) {
		if ( defined( 'EVF_VERSION' ) && version_compare( EVF_VERSION, '1.7.8', '>=' ) ) {
			$key = array_search( 'EVF_Field_AI', $fields, true );
			if ( false !== $key ) {
				$fields[ $key ] = 'EverestForms\OpenAI\Field\Field';
			}
		}

		return $fields;
	}

	/**
	 * Plugin Updater.
	 *
	 * @since 1.0.0
	 */
	public function plugin_updater() {
		if ( class_exists( 'EVF_Plugin_Updater' ) ) {
			return \EVF_Plugin_Updater::updates( EVF_OPENAI_PLUGIN_FILE, 226646, EVF_OPENAI_VERSION );
		}
	}

	/**
	 * Load Localization files.
	 *
	 * Note: the first-loaded translation file overrides any following ones if the same translation is present.
	 *
	 * Locales found in:
	 *      - WP_LANG_DIR/everest-forms-openai/everest-forms-openai-LOCALE.mo
	 *      - WP_LANG_DIR/plugins/everest-forms-openai-LOCALE.mo
	 */
	public function load_plugin_textdomain() {
		$locale = apply_filters( 'plugin_locale', get_locale(), 'everest-forms-openai' );

		load_textdomain( 'everest-forms-openai', WP_LANG_DIR . '/everest-forms-openai/everest-forms-openai-' . $locale . '.mo' );
		load_plugin_textdomain( 'everest-forms-openai', false, plugin_basename( dirname( EVF_OPENAI_PLUGIN_FILE ) ) . '/languages' );
	}

	/**
	 * Register OpenAI integration.
	 *
	 * @param array $integrations List of integrations.
	 */
	public function add_integration( $integrations ) {
		$integrations[] = 'EverestForms\OpenAI\Admin\Settings';
		return $integrations;
	}

	/**
	 * Display action links in the Plugins list table.
	 *
	 * @param  array $actions Plugin Action links.
	 * @return array
	 */
	public function plugin_action_links( $actions ) {
		$new_actions = array(
			'settings' => '<a href="' . admin_url( 'admin.php?page=evf-settings&tab=integration&section=openai' ) . '" aria-label="' . esc_attr__( 'View Everest Forms OpenAI Settings', 'everest-forms-openai' ) . '">' . esc_html__( 'Settings', 'everest-forms-openai' ) . '</a>',
		);

		return array_merge( $new_actions, $actions );
	}

	/**
	 * Display row meta in the Plugins list table.
	 *
	 * @since 1.0.0
	 *
	 * @param  array  $plugin_meta Plugin Row Meta.
	 * @param  string $plugin_file Plugin Base file.
	 * @return array
	 */
	public function plugin_row_meta( $plugin_meta, $plugin_file ) {
		if ( plugin_basename( EVF_OPENAI_PLUGIN_FILE ) === $plugin_file ) {
			$new_plugin_meta = array(
				'docs' => '<a href="' . esc_url( 'https://docs.wpeverest.com/docs/everest-forms/everest-forms-add-ons/openai/' ) . '" aria-label="' . esc_attr__( 'View Everest Forms OpenAI documentation', 'everest-forms-openai' ) . '">' . esc_html__( 'Docs', 'everest-forms-openai' ) . '</a>',
			);

			return array_merge( $plugin_meta, $new_plugin_meta );
		}

		return (array) $plugin_meta;
	}

	/**
	 * Everest Forms fallback notice.
	 *
	 * @since 1.0.0
	 */
	public function everest_forms_missing_notice() {
		/* translators: %s: everest-forms version */
		echo '<div class="error notice is-dismissible"><p>' . sprintf( esc_html__( 'Everest Forms - OpenAI requires at least %s or later to work!', 'everest-forms-openai' ), '<a href="https://wpeverest.com/wordpress-plugins/everest-forms/" target="_blank">' . esc_html__( 'Everest Forms 1.9.3', 'everest-forms-openai' ) . '</a>' ) . '</p></div>';
	}

	/**
	 * Everest Forms Pro fallback notice.
	 *
	 * @since 1.0.0
	 */
	public function everest_forms_pro_missing_notice() {
		/* translators: %s: everest-forms-OpenAI version */
		echo '<div class="error notice is-dismissible"><p>' . sprintf( esc_html__( 'Everest Forms - OpenAI depends on the last version of %s or later to work!', 'everest-forms-openai' ), '<a href="https://wpeverest.com/wordpress-plugins/everest-forms/" target="_blank">' . esc_html__( 'Everest Forms Pro 1.5.8', 'everest-forms-openai' ) . '</a>' ) . '</p></div>';
	}
}
