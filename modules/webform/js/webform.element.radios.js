/**
 * @file
 * JavaScript behaviors for radio buttons.
 */

(function ($, Drupal) {

  'use strict';

  /**
   * Adds HTML5 validation to required radios buttons.
   *
   * @type {Drupal~behavior}
   *
   * @see Issue #2856795: If radio buttons are required but not filled form is nevertheless submitted.
   */
  Drupal.behaviors.webformRadiosRequired = {
    attach: function (context) {
      $('.js-webform-type-radios[required="required"], .js-form-type-webform-radios-other[required="required"]', context).each(function() {
        $(this).find('input[type="radio"]').attr({'required': 'required', 'aria-required': 'true'});
      });
    }
  };

})(jQuery, Drupal);
