/**
 * @file
 * JavaScript behaviors for jQuery UI tooltip integration.
 *
 * Please Note:
 * jQuery UI's tooltip implementation is not very responsive or adaptive.
 *
 * @see https://www.drupal.org/node/2207383
 */

(function ($, Drupal) {

  'use strict';

  var tooltipDefaultOptions = {
    // @see https://stackoverflow.com/questions/18231315/jquery-ui-tooltip-html-with-links
    show: null,
    close: function (event, ui) {
      ui.tooltip.hover(
        function () {
          $(this).stop(true).fadeTo(400, 1);
        },
        function () {
          $(this).fadeOut("400", function () {
            $(this).remove();
          })
        });
    }
  };

  // @see http://api.jqueryui.com/tooltip/
  Drupal.webform = Drupal.webform || {};

  Drupal.webform.tooltipElement = Drupal.webform.tooltipElement || {};
  Drupal.webform.tooltipElement.options = Drupal.webform.tooltipElement.options || tooltipDefaultOptions;

  Drupal.webform.tooltipLink = Drupal.webform.tooltipLink || {};
  Drupal.webform.tooltipLink.options = Drupal.webform.tooltipLink.options || tooltipDefaultOptions;

  /**
   * Initialize jQuery UI tooltip element support.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.webformTooltipElement = {
    attach: function (context) {
      $(context).find('.js-webform-tooltip-element').once('webform-tooltip-element').each(function () {
        var $element = $(this);

        // Checkboxes, radios, buttons, toggles, etc... use fieldsets.
        // @see \Drupal\webform\Plugin\WebformElement\OptionsBase::prepare
        var $description;
        if ($element.is('fieldset')) {
          $description = $element.find('> .fieldset-wrapper > .field-suffix .description.visually-hidden');
        }
        else {
          $description = $element.children('.description.visually-hidden');
        }

        var has_visible_input = $element.find(':input:not([type=hidden])').length;
        var has_checkboxes_or_radios = $element.find(':checkbox, :radio').length;
        var is_composite = $element.hasClass('form-composite');
        var is_custom = $element.is('.js-form-type-webform-signature, .js-form-type-webform-image-select, .js-form-type-webform-mapping, .js-form-type-webform-rating, .js-form-type-datelist, .js-form-type-datetime');

        var items;
        if (has_visible_input && !has_checkboxes_or_radios && !is_composite && !is_custom) {
          items = ':input';
        }
        else {
          items = $element;
        }

        var options = $.extend({
          items: items,
          content: $description.html()
        }, Drupal.webform.tooltipElement.options);

        $element.tooltip(options);
      });
    }
  };

  /**
   * Initialize jQuery UI tooltip link support.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.webformTooltipLink = {
    attach: function (context) {
      $(context).find('.js-webform-tooltip-link').once('webform-tooltip-link').each(function () {
        var $link = $(this);

        var options = $.extend({}, Drupal.webform.tooltipLink.options);

        $link.tooltip(options);
      });
    }
  };

})(jQuery, Drupal);
