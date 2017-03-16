import $ from 'jquery'

$(document).on('click', '[data-scroll-target]', (e) => {
  e.preventDefault()
  const $clicked = $(e.currentTarget)
  const $el = $($clicked.data('scroll-target'))
  scrollTo($el)
})

const scroll = (pos) => {
  $('body, html').animate({scrollTop: pos}, 1000)
}

export const scrollTo = ($el) => {
  scroll($el.offset().top)
}

export const scrollToTop = () => {
  scroll(0)
}