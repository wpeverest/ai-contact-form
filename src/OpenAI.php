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
use EverestForms\OpenAI\Admin\Settings;

/**
 * Main plugin class.
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
				add_filter( 'everest_forms_get_settings_pages', array( $this, 'load_settings_pages' ), 99, 1 );

				// Enqueue Scripts.
				add_action( 'everest_forms_frontend_output', array( $this, 'frontend_enqueue_scripts' ) );
				add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );

				// Smart tags.
				add_filter( 'everest_forms_smart_tags', array( $this, 'email_smart_tags' ) );

			} else {
				add_action( 'admin_notices', array( $this, 'everest_forms_pro_missing_notice' ) );
			}
		} else {
			add_action( 'admin_notices', array( $this, 'everest_forms_missing_notice' ) );
		}
	}

	/**
	 * Email smart tags.
	 *
	 * @param mixed $tags Smart Tags.
	 *
	 * @since 1.0.0
	 */
	public function email_smart_tags( $tags ) {
		return array_merge(
			$tags,
			array(
				'ai_email_response' => esc_html__( 'AI Email Response', 'everest-forms-ai' ),
			)
		);
	}

	/**
	 * Frontend Enqueue scripts.
	 *
	 * @param array $form_data Form Data.
	 */
	public function frontend_enqueue_scripts( $form_data ) {
		$suffix    = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
		$form_id   = isset( $form_data['id'] ) ? absint( $form_data['id'] ) : 0;
		$field_ids = array();
		foreach ( $form_data['form_fields'] as $key => $field ) {
			if ( array_key_exists( 'ai_chatbot_input', $field ) ) {
					$ai_prompt = $field['ai_chatbot_input'];
					preg_match_all( '/\{field_id="(.+?)"\}/', $ai_prompt, $ids );
				foreach ( $ids[1] as $key => $field_id ) {
					$mixed_field_id = explode( '_', $field_id );
					$field_ids[]    = $mixed_field_id[1];

				}
			}
		}
		wp_register_script( 'everest-forms-openai', plugins_url( "/assets/js/frontend/everest-forms-openai{$suffix}.js", EVF_OPENAI_PLUGIN_FILE ), array( 'jquery' ), EVF_OPENAI_VERSION, true );
		wp_enqueue_script( 'everest-forms-openai' );
		wp_localize_script(
			'everest-forms-openai',
			'everest_forms_openai_params',
			array(
				'ajax_url'                   => admin_url( 'admin-ajax.php' ),
				'everest_forms_openai_nonce' => wp_create_nonce( 'everest_forms_openai' ),
				'field_id'                   => $field_ids,
				'form_id'                    => $form_id,
			)
		);
	}

	/**
	 * Admin Enqueue Scripts.
	 */
	public function admin_enqueue_scripts() {
		$screen    = get_current_screen();
		$screen_id = $screen ? $screen->id : '';
		$suffix    = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		// Register admin scripts.
		wp_register_script( 'everest-forms-openai-settings', plugins_url( "/assets/js/admin/admin{$suffix}.js", EVF_OPENAI_PLUGIN_FILE ), array( 'jquery' ), EVF_OPENAI_VERSION, true );

		// Admin scripts for EVF settings page.
		if ( 'everest-forms_page_evf-builder' === $screen_id ) {
			wp_enqueue_script( 'everest-forms-openai-settings' );
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
		new Ajax();
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
	 * @param array $settings List of Settings.
	 */
	public function load_settings_pages( $settings ) {
		$settings[] = new Settings();
		return $settings;
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
