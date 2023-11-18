<?php
/**
 * Main plugin class.
 *
 * @package EverestForms\AI
 * @since   1.0.0
 */

namespace EverestForms\AI;

use EverestForms\AI\API\API;
use EverestForms\AI\Process\Process;
use EverestForms\AI\Admin\Settings;

/**
 * Main plugin class.
 *
 * @since 1.0.0
 */
class AI {

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
		_doing_it_wrong( __FUNCTION__, esc_html__( 'Cloning is forbidden.', 'ai-contact-form' ), '1.0.0' );
	}

	/**
	 * Unserializing instances of this class is forbidden.
	 *
	 * @since 1.0.0
	 */
	public function __wakeup() {
		_doing_it_wrong( __FUNCTION__, esc_html__( 'Unserializing instances of this class is forbidden.', 'ai-contact-form' ), '1.0.0' );
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
	// Checks with Everest Forms is installed.
	if ( defined( 'EVF_VERSION' ) && version_compare( EVF_VERSION, '2.0.2', '>=' ) ) {

		add_action( 'init', array( $this, 'load_plugin_textdomain' ) );
		add_filter( 'plugin_action_links_' . plugin_basename( EVF_AI_PLUGIN_FILE ), array( $this, 'plugin_action_links' ) );
		add_filter( 'plugin_row_meta', array( $this, 'plugin_row_meta' ), 20, 2 );

				// Hooks.
				add_action( 'everest_forms_init', array( $this, 'openai_init' ) );
				// Hooks.
				add_action( 'everest_forms_init', array( $this, 'plugin_updater' ) );
				add_filter( 'everest_forms_fields', array( $this, 'form_fields' ) );
				add_filter( 'everest_forms_get_settings_pages', array( $this, 'load_settings_pages' ), 99, 1 );
				add_filter( 'show_everest_forms_setting_message', array( $this, 'ai_authentication' ), 10, 1 );

				// Enqueue Scripts.
				add_action( 'everest_forms_frontend_output', array( $this, 'frontend_enqueue_scripts' ) );
				add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
		} else {
			add_action( 'admin_notices', array( $this, 'everest_forms_missing_notice' ) );
		}
	}

	/**
	 * Authenticates using AI API key.
	 *
	 * @param bool $is_authenticated Current authentication status.
	 *
	 * @return bool Updated authentication status.
	 */
	public function ai_authentication( $is_authenticated ) {
		$api_key  = get_option( 'everest_forms_ai_api_key' );
		$response = new API( $api_key );
		$res      = $response->authentication();
		if ( isset( $res['code'] ) && 200 !== $res['code'] ) {
			$error_msg        = isset( $res['message'] ) ? $res['message'] : '';
			$is_authenticated = false;
			\EVF_Admin_Settings::add_error( esc_html( $error_msg ) );
			return $is_authenticated;
		}
		return $is_authenticated;
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
		wp_register_script( 'ai-contact-form', plugins_url( "/assets/js/frontend/ai-contact-form{$suffix}.js", EVF_AI_PLUGIN_FILE ), array( 'jquery' ), EVF_AI_VERSION, true );
		wp_enqueue_script( 'ai-contact-form' );
		wp_localize_script(
			'ai-contact-form',
			'everest_forms_ai_params',
			array(
				'ajax_url'               => admin_url( 'admin-ajax.php' ),
				'everest_forms_ai_nonce' => wp_create_nonce( 'everest_forms_ai' ),
				'field_id'               => $field_ids,
				'form_id'                => $form_id,
			)
		);
	}

		/**
	 * Plugin Updater.
	 *
	 * @since 1.0.0
	 */
	public function plugin_updater() {
		if ( class_exists( 'EVF_Plugin_Updater' ) ) {
			return \EVF_Plugin_Updater::updates( EVF_AI_PLUGIN_FILE, 252599, EVF_AI_VERSION );
		}
	}

	/**
	 * Admin Enqueue Scripts.
	 */
	public function admin_enqueue_scripts() {
		$screen    = get_current_screen();
		$screen_id = $screen ? $screen->id : '';
		$suffix    = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		// Register admin scripts.
		wp_register_script( 'ai-contact-form-settings', plugins_url( "/assets/js/admin/admin{$suffix}.js", EVF_AI_PLUGIN_FILE ), array( 'jquery' ), EVF_AI_VERSION, true );

		// Admin scripts for EVF settings page.
		if ( 'everest-forms_page_evf-builder' === $screen_id ) {
			wp_enqueue_script( 'ai-contact-form-settings' );
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
		self::$log->log( $level, $message, array( 'source' => 'ai' ) );
	}

	/**
	 * Init AI - Logic begins.
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
		if ( defined( 'EVF_VERSION' ) && version_compare( EVF_VERSION, '2.0.3', '>=' ) && ! empty( get_option( 'everest_forms_ai_api_key' ) ) ) {
			$key = array_search( 'EVF_Field_AI', $fields, true );
			if ( false !== $key ) {
				$fields[ $key ] = 'EverestForms\AI\Field\Field';
			}
		}

		return $fields;
	}



	/**
	 * Load Localization files.
	 *
	 * Note: the first-loaded translation file overrides any following ones if the same translation is present.
	 *
	 * Locales found in:
	 *      - WP_LANG_DIR/ai-contact-form/ai-contact-form-LOCALE.mo
	 *      - WP_LANG_DIR/plugins/ai-contact-form-LOCALE.mo
	 */
	public function load_plugin_textdomain() {
		$locale = apply_filters( 'plugin_locale', get_locale(), 'ai-contact-form' );

		load_textdomain( 'ai-contact-form', WP_LANG_DIR . '/ai-contact-form/ai-contact-form-' . $locale . '.mo' );
		load_plugin_textdomain( 'ai-contact-form', false, plugin_basename( dirname( EVF_AI_PLUGIN_FILE ) ) . '/languages' );
	}

	/**
	 * Register AI integration.
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
			'settings' => '<a href="' . admin_url( 'admin.php?page=evf-settings&tab=ai' ) . '" aria-label="' . esc_attr__( 'View Everest Forms AI Settings', 'ai-contact-form' ) . '">' . esc_html__( 'Settings', 'ai-contact-form' ) . '</a>',
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
		if ( plugin_basename( EVF_AI_PLUGIN_FILE ) === $plugin_file ) {
			$new_plugin_meta = array(
				'docs' => '<a href="' . esc_url( 'https://docs.everestforms.net/docs/ai/' ) . '" aria-label="' . esc_attr__( 'View Everest Forms AI documentation', 'ai-contact-form' ) . '">' . esc_html__( 'Docs', 'ai-contact-form' ) . '</a>',
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
		$all_plugins = get_plugins();
		if ( isset( $all_plugins['everest-forms/everest-forms.php'] ) ) {
			$plugin_path = 'everest-forms/everest-forms.php';
			$plugin_url  = wp_nonce_url(
				add_query_arg(
					array(
						'action' => 'activate',
						'plugin' => urlencode( $plugin_path ),
					),
					self_admin_url( 'plugins.php' )
				),
				'activate-plugin_' . $plugin_path
			);
			?>
			<div class="notice-warning notice">
				<p><?php esc_html_e( 'AI requires the Everest Forms Plugin.', 'ai-contact-form' ); ?></p>
				<p><a href="<?php echo esc_url( $plugin_url ); ?>" class="button-primary"><?php esc_html_e( 'Click here to activate the plugin', 'ai-contact-form' ); ?></a></p>
			</div>
			<?php
		} else {
			$plugin_url = wp_nonce_url(
				add_query_arg(
					array(
						'action' => 'install-plugin',
						'plugin' => 'everest-forms'
					),
					admin_url( 'update.php' )
				),
				'install-plugin_everest-forms'
			);

			?>
			<div class="notice-warning notice">
				<p><?php esc_html_e( 'AI requires the Everest Forms Plugin to be installed.', 'ai-contact-form' ); ?></p>
				<p ><a href="<?php echo esc_url( $plugin_url ); ?>" class="button-primary"><?php esc_html_e( 'Click here to install the plugin', 'ai-contact-form' ); ?></a></p>
			</div>
			<?php
		}
	}

}
