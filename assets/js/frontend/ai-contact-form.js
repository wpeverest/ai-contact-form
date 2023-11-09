/* global everest_forms_ai */

"use strict";

/**
 * Everest Forms AI Frontend JS.
 *
 * @since 1.0.0
 */
var EverestFormsAI =
  window.EverestFormsAI ||
  (function (document, window, $) {
    const app = {
      /**
       * Initialize.
       */
      init: function () {
        $(app.ready);
      },

      /**
       * Document ready.
       */
      ready: function () {
        $(document).ready(function () {
          if ($("textarea[ai_chatbot='1']").length > 0) {
            $(".evf-submit-container").hide();
          } else if ($("div[ai_chatbot='1']").length > 0) {
            $(".evf-submit-container").hide();
          }
        });
        $(document).ready(function () {
          var fieldIds = everest_forms_ai_params.field_id;
          var selectedElements = $();

          fieldIds.forEach(function (id) {
            var name = "everest_forms[form_fields][" + id + "]";
            var elements = $("input[name^='" + name + "']");
            selectedElements = selectedElements.add(elements);
          });

          selectedElements.on("keydown", function (event) {
            if (event.which === 13 || event.keyCode === 13) {
              event.preventDefault();
              app.evfChatBot($(this));
            }
          });
        });
      },
      evfChatBot: function ($this) {
        var chat = $this.val();
		var loadingText = '<span class="loading-text">...</span>';
		$(loadingText).insertAfter($this);
        var data = {
          action: "everest_forms_ai_chat_bot",
          security: everest_forms_ai_params.everest_forms_ai_nonce,
          chat: chat,
          form_id: everest_forms_ai_params.form_id,
        };
        $.ajax({
          url: everest_forms_ai_params.ajax_url,
          type: "POST",
          data: data,
        })
          .done(function (xhr, textStatus, errorThrown) {
            if (true === xhr.success) {
              var targetFieldName =
                "everest_forms[form_fields][" + xhr.data.field_id + "]";
              if (xhr.data.field_type === "html") {
				console.log(xhr.data.message);
                $('div[name="' + targetFieldName + '"]').text(xhr.data.message);
              } else if (xhr.data.field_type === "textarea") {
                $('textarea[name="' + targetFieldName + '"]').val(
                  xhr.data.message
                );
              }
            }
          })
          .fail(function () {})
          .always(function (xhr) {
			$(".loading-text").remove();
		  });
      },
    };

    return app;
  })(document, window, jQuery);

// Initialize.
EverestFormsAI.init();
