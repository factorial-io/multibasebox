import $ from 'jquery'
import '../vendor/slick'

const initSliders = ($el) => {
  $('[data-lazy]', $el).each((index, el) => {
    $(el).attr('data-lazy', $(el).attr('data-src'))
  })
  $('[data-slider]', $el).each((index, el) => {
    $(el).slick({
      slide: '.slide',
      prevArrow: $(el).find('[data-swipe-prev]'),
      nextArrow: $(el).find('[data-swipe-next]'),
      dots: true,
      appendDots: $(el).find('[data-swipe-pages]')
    })
  })
}

initSliders($(document))