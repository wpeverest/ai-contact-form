/**
 * EverestFormsOpenAI Admin JS
 */
(function ($) {
	var EverestFormsOpenAI = {
        /**
         * Initialization.
         */
		init: function () {
			$(document).ready(EverestFormsOpenAI.ready);
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
				} else {
					$( '#everest-forms-field-option-row-' + id + '-ai_chatbot_input' ).hide();
					$( '#everest-forms-field-option-row-' + id + '-ai_input' ).show();
				}
			});
		},

	};
	EverestFormsOpenAI.init();
})(jQuery);
