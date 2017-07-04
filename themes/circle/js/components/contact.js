import $ from 'jquery'

$(document).on('click', '[data-contact-icon]', (e) => {
  const $a = $(e.currentTarget).parent().find('a')
  $a.trigger('mousedown')
  window.location.href = $a.attr('href')
})