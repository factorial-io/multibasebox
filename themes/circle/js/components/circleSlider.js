import $ from 'jquery'
import Responsive, {RESKEYS} from '../utils/Responsive'
import TweenLite from 'gsap/TweenLite'
import 'gsap/CSSPlugin'

const $slider = $('[data-circle-slider]')

const setActive = (currentSlide) => {
  $('[data-circle-details]').html($slider.find('.slick-center .details__text').html()).animate({opacity: 1}, 300)
}

$slider.slick({
  prevArrow: $slider.parent().find('[data-swipe-prev]'),
  nextArrow: $slider.parent().find('[data-swipe-next]'),
  responsive: [
      {
        breakpoint: Responsive.BREAKPOINTS[RESKEYS.md],
        settings: {
          slidesToShow: 3
        }
      },
      {
        breakpoint: Responsive.BREAKPOINTS[RESKEYS.sm],
        settings: {
          slidesToShow: 1
        }
      }
    ]
})
$slider.on('beforeChange', (event, slick, currentSlide, nextSlide) => {
  $('[data-circle-details]').animate({opacity: 0}, 300)
})
$slider.on('afterChange', (event, slick, currentSlide) => {
  setActive(currentSlide)
})
setActive(0)