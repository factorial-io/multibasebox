import $ from 'jquery'

$(document).ready(function() {
  $('.area-request__form__wrap').hide();
  $('.area-request__form__headline').on('click', (e) => {
    e.preventDefault();
    const target = $(e.target);
    const container = target.parent();
    if (container.hasClass('active')) {
      container.removeClass('active');
    }
    else {
      container.addClass('active');
    }
    container.find('.area-request__form__wrap').slideToggle( "slow", function() {});
  });
});
