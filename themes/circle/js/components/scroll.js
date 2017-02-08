import $ from 'jquery'

$(document).on('click', '[data-scroll-target]', (e) => {
  e.preventDefault()
  const $clicked = $(e.currentTarget)
  const $el = $($clicked.data('scroll-target'))
  $('body, html').animate({scrollTop: $el.offset().top}, 1000)
})