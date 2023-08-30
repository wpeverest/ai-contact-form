/**
 * EverestFormsAI Admin JS
 */
(function ($) {
	var EverestFormsAI = {
        /**
         * Initialization.
         */
		init: function () {
			$(document).ready(EverestFormsAI.ready);

			$(document).ready(function() {
				var ai_chatbot_checkbox = $( '.everest-forms-field-option-row-ai_chatbot input[type="checkbox"]' );
				ai_chatbot_checkbox.each(function(index, el) {

				if( $(this).is(':checked') ) {
					$('.everest-forms-field-option-row-ai_type select').prop('disabled', false);
				} else {
					$('.everest-forms-field-option-row-ai_type select').prop('disabled', true);
				}
			});
			});
		},

		/**
		 * Document Ready
		 */
		ready: function () {
			$builder = $( '#everest-forms-builder' );
			$builder.on( 'change', '.everest-forms-field-option-row-ai_chatbot input', function( event ) {
				var id = $( this ).parent().data( 'field-id' );

				$( '#everest-forms-field-' + id ).toggleClass( 'ai_chatbot_input' );

				// Toggle "Parameter Name" option.
				if ( $( event.target ).is( ':checked' ) ) {
					$( '#everest-forms-field-option-row-' + id + '-ai_chatbot_input' ).show();
					$( '#everest-forms-field-option-row-' + id + '-ai_input' ).hide();
					$('#everest-forms-field-option-' + id + '-ai_type').empty();
					$('#everest-forms-field-option-' + id + '-ai_type').append([
						$('<option>', {value: 'textarea', text: 'TextArea'}),
						$('<option>', {value: 'html', text: 'HTML'}),
					]);
					$('#everest-forms-field-option-' + id + '-ai_type').prop('disabled', false);
				} else {
					$('#everest-forms-field-option-row-' + id + '-ai_chatbot_input').hide();
					$('#everest-forms-field-option-row-' + id + '-ai_input').show();

					$('#everest-forms-field-option-' + id + '-ai_type').empty();
					$('#everest-forms-field-option-' + id + '-ai_type').append([
						$('<option>', {value: 'hidden', text: 'Hidden'}),
					]);
					$('#everest-forms-field-option-' + id + '-ai_type').prop('disabled', true);
				}
			});

			$( '.evf-email-settings-wrapper' ).on( 'change', '.everest-forms-enable-email-prompt input', function () {
				if( $( this ).is( ':checked' ) ) {
					$( this ).closest( '.everest-forms-enable-email-prompt' ).next( '.evf-email-message-prompt' ).show();
				} else {
					$( this ).closest( '.everest-forms-enable-email-prompt' ).next( '.evf-email-message-prompt' ).hide();

				}
			});
		},

	};
	EverestFormsAI.init();
})(jQuery);
