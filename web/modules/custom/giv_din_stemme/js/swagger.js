/**
 * @file
 * Provides Swagger integration.
 */

(function ($, Drupal, drupalSettings) {

  Drupal.behaviors.swaggerui = {
    attach: function (context, settings) {
      function SwaggerUIHideTopbarPlugin() {
        return {
          components: {
            Topbar: function() { return null }
          }
        }
      }
      let swaggerWrapper = document.getElementById("swagger-ui");
      let config = {
        url: swaggerWrapper.getAttribute('data-swagger-def'),
        dom_id: '#swagger-ui',
        presets: [
          SwaggerUIBundle.presets.apis,
          SwaggerUIStandalonePreset
        ],
        plugins: [
          SwaggerUIBundle.plugins.DownloadUrl,
          SwaggerUIHideTopbarPlugin
        ],
        layout: "StandaloneLayout"
      }

      window.ui = SwaggerUIBundle(config);
    }
  }
})(jQuery, Drupal, drupalSettings);
