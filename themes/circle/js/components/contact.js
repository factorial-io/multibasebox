import $ from 'jquery'

$(document).on('click', '[data-contact-icon]', (e) => {
  window.location.href = $(e.currentTarget).parent().find('a').attr('href')
})