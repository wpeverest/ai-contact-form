/* global everest_forms_openai */

"use strict";

/**
 * Everest Forms OpenAI Frontend JS.
 *
 * @since 1.0.0
 */
var EverestFormsOpenAI =
  window.EverestFormsOpenAI ||
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
          var fieldIds = everest_forms_openai_params.field_id;
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
        var data = {
          action: "everest_forms_openai_chat_bot",
          security: everest_forms_openai_params.everest_forms_openai_nonce,
          chat: chat,
          form_id: everest_forms_openai_params.form_id,
        };
        $.ajax({
          url: everest_forms_openai_params.ajax_url,
          type: "POST",
          data: data,
        })
          .done(function (xhr, textStatus, errorThrown) {
            if (true === xhr.success) {
              var targetFieldName =
                "everest_forms[form_fields][" + xhr.data.field_id + "]";
              if (xhr.data.field_type === "html") {
                $('div[name="' + targetFieldName + '"]').text(xhr.data.message);
              } else if (xhr.data.field_type === "textarea") {
                $('textarea[name="' + targetFieldName + '"]').val(
                  xhr.data.message
                );
              }
            }
          })
          .fail(function () {})
          .always(function (xhr) {});
      },
    };

    return app;
  })(document, window, jQuery);

// Initialize.
EverestFormsOpenAI.init();
