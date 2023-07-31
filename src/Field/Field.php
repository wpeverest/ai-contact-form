<?php
/**
 * OpenAI Field.
 *
 * @package EverestForms\OpenAI\Field
 * @since   1.0.0
 */

namespace EverestForms\OpenAI\Field;

use EverestForms\OpenAI\API\API;

defined( 'ABSPATH' ) || exit;

/**
 * Field Class
 *
 * @since 1.0.0
 */
/**
 * Field Class.
 *
 * @since 1.0.0
 */
class Field extends \EVF_Form_Fields {


	/**
	 * Primary class constructor.
	 */
	public function __construct() {
		$this->name     = esc_html__( 'AI', 'everest-forms-openai' );
		$this->type     = 'ai';
		$this->icon     = 'evf-icon evf-icon-ai';
		$this->order    = 240;
		$this->group    = 'advanced';
		$this->settings = array(
			'basic-options'    => array(
				'field_options' => array(
					'label',
					'meta',
					'description',
					'required',
					'required_field_message_setting',
					'required_field_message',
				),
			),
			'advanced-options' => array(
				'field_options' => array(
					'ai_chatbot',
					'ai_prompt',
					'ai_type',
					'label_hide',
					'css',
				),
			),
		);
		parent::__construct();
	}

	/**
	 * Hook in tabs.
	 */
	public function init_hooks() {
		add_filter( 'everest_forms_field_properties_' . $this->type, array( $this, 'field_properties' ), 5, 3 );

	}

	public function ai_chatbot( $field ) {
		$value             = ! empty( $field['ai_chatbot'] ) ? esc_attr( $field['ai_chatbot'] ) : '';
		$ai_prompt_chatbot = $this->field_element(
			'checkbox',
			$field,
			array(
				'slug'    => 'ai_chatbot',
				'value'   => $value,
				'desc'    => esc_html__( 'Enable Chatbot', 'everest-forms-openai' ),
				'tooltip' => esc_html__( 'Enable Chatbot', 'everest-forms-openai' ),
			),
			false
		);
		$ai_prompt_chatbot = $this->field_element(
			'row',
			$field,
			array(
				'slug'    => 'ai_chatbot',
				'content' => $ai_prompt_chatbot,
			),
			false
		);
		$args              = array(
			'slug'    => 'ai_chatbot',
			'content' => $ai_prompt_chatbot,
		);
		$this->field_element( 'row', $field, $args );

	}

	/**
	 * AI Prompt field option.
	 *
	 * @param array $field Field data.
	 */
	public function ai_prompt( $field ) {
		$ai_prompt        = ! empty( $field['ai_input'] ) ? sanitize_text_field( $field['ai_input'] ) : '';
		$ai_chatbot_input = ! empty( $field['ai_chatbot_input'] ) ? sanitize_text_field( $field['ai_chatbot_input'] ) : '';
		$ai_prompt_label  = $this->field_element(
			'label',
			$field,
			array(
				'slug'    => 'ai_input',
				'value'   => esc_html__( 'Prompt', 'everest-forms-openai' ),
				'tooltip' => esc_html__( 'Please Enter', 'everest-forms-openai' ),
			),
			false
		);
		$ai_prompt_input  = $this->field_element(
			'textarea',
			$field,
			array(
				'slug'        => 'ai_input',
				'value'       => $ai_prompt,
				'placeholder' => 'Please enter',
			),
			false
		);
		$ai_prompt_input .= '<a href="#" class="evf-toggle-smart-tag-display" data-type="fields"><span class="dashicons dashicons-editor-code"></span></a>';
		$ai_prompt_input .= '<div class="evf-smart-tag-lists" style="display: none">';
		$ai_prompt_input .= '<div class="smart-tag-title other-tag-title">Available fields</div><ul class="evf-fields"></ul></div>';
		$args             = array(
			'slug'    => 'ai_input',
			'content' => $ai_prompt_label . $ai_prompt_input,
			'class'   => isset( $field['ai_chatbot'] ) ? 'hidden' : '',
		);
		$this->field_element( 'row', $field, $args );

		$ai_prompt_label  = $this->field_element(
			'label',
			$field,
			array(
				'slug'    => 'ai_chatbot_input',
				'value'   => esc_html__( 'Filed Mapping', 'everest-forms-openai' ),
				'tooltip' => esc_html__( 'Field Mapping', 'everest-forms-openai' ),
			),
			false
		);
		$ai_prompt_input  = $this->field_element(
			'textarea',
			$field,
			array(
				'slug'        => 'ai_chatbot_input',
				'value'       => $ai_chatbot_input,
				'placeholder' => 'Please enter',
			),
			false
		);
		$ai_prompt_input .= '<a href="#" class="evf-toggle-smart-tag-display" data-type="fields"><span class="dashicons dashicons-editor-code"></span></a>';
		$ai_prompt_input .= '<div class="evf-smart-tag-lists" style="display: none">';
		$ai_prompt_input .= '<div class="smart-tag-title other-tag-title">Available fields</div><ul class="evf-fields"></ul></div>';
		$args             = array(
			'slug'    => 'ai_chatbot_input',
			'content' => $ai_prompt_label . $ai_prompt_input,
			'class'   => isset( $field['ai_chatbot'] ) ? '' : 'hidden',
		);
		$this->field_element( 'row', $field, $args );

	}

	/**
	 * Ai type field option.
	 *
	 * @param array $field Field data.
	 */
	public function ai_type( $field ) {
		$ai_type          = ! empty( $field['ai_type'] ) ? esc_attr( $field['ai_type'] ) : '';
		$ai_format_label  = $this->field_element(
			'label',
			$field,
			array(
				'slug'    => 'ai_type',
				'value'   => esc_html__( 'AI Type', 'everest-forms-open-ai' ),
				'tooltip' => esc_html__( 'Please select the ai type.', 'everest-forms-open-ai' ),
			),
			false
		);
		$ai_format_select = $this->field_element(
			'select',
			$field,
			array(
				'slug'    => 'ai_type',
				'value'   => $ai_type,
				'options' => array(
					'hidden'   => esc_html__( 'Hidden', 'everest-forms-openai' ),
					'textarea' => esc_html__( 'Textarea', 'everest-forms-openai' ),
					'html'     => esc_html__( 'HTML', 'everest-forms-openai' ),
				),
			),
			false
		);

		$args = array(
			'slug'    => 'ai_type',
			'content' => $ai_format_label . $ai_format_select,
		);
		$this->field_element( 'row', $field, $args );
	}

	/**
	 * Field preview inside the builder.
	 *
	 * @since 1.6.1
	 *
	 * @param array $field Field data and settings.
	 */
	public function field_preview( $field ) {

		// Define data.
		$default = ! empty( $field['default'] ) ? esc_attr( $field['default'] ) : '#000000';

		// Label.
		$this->field_preview_option( 'label', $field );

		// Primary input.
		echo '<div class="evf-color-picker-bg" style="background: ' . esc_attr( $default ) . ';"></div><input type="text" class="widefat colorpickpreview" disabled>';

		// Description.
		$this->field_preview_option( 'description', $field );
	}

	/**
	 * Define additional field properties.
	 *
	 * @since 1.0.0
	 *
	 * @param array $properties Field properties.
	 * @param array $field      Field settings.
	 * @param array $form_data  Form data and settings.
	 *
	 * @return array of additional field properties.
	 */
	public function field_properties( $properties, $field, $form_data ) {
		if ( isset( $field['ai_chatbot'] ) ) {
			$properties['inputs']['primary']['attr']['ai_chatbot'] = $field['ai_chatbot'];
		}
		return $properties;
	}

	/**
	 * Field display on the form front-end.
	 *
	 * @since 1.0.0
	 *
	 * @param array $field Field Data.
	 * @param array $field_atts Field attributes.
	 * @param array $form_data All Form Data.
	 */
	public function field_display( $field, $field_atts, $form_data ) {
		$value   = '';
		$primary = $field['properties']['inputs']['primary'];
		$ai_type = ! empty( $field['ai_type'] ) ? $field['ai_type'] : 'hidden';

		switch ( $ai_type ) {
			case 'hidden':
				printf(
					'<input type="hidden" %s>',
					evf_html_attributes( $primary['id'], $primary['class'], $primary['data'], $primary['attr'] )
				);
				break;
			case 'textarea':
				printf(
					'<textarea %s %s >%s</textarea>',
					evf_html_attributes( $primary['id'], $primary['class'], $primary['data'], $primary['attr'] ),
					esc_attr( $primary['required'] ),
					esc_html( $value )
				);
				break;
			case 'html':
				printf(
					'<div %s>%s</div>',
					evf_html_attributes( $primary['id'], $primary['class'], $primary['data'], $primary['attr'] ),
					esc_html( $value )
				);
				break;
		}
	}



}
