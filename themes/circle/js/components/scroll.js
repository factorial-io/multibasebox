import $ from 'jquery'

$(document).on('click', '[data-scroll-target]', (e) => {
  e.preventDefault()
  const $clicked = $(e.currentTarget)
  const $el = $($clicked.data('scroll-target'))
  scrollTo($el)
})

export const scrollTo = ($el) => {
  $('body, html').animate({scrollTop: $el.offset().top}, 1000)
}