import $ from 'jquery'
import TweenLite from 'gsap/TweenLite'
import 'gsap/CSSPlugin'

const $slider = $('[data-circle-slider]')

const setActive = (currentSlide) => {
  // $slider.find(`[data-slick-index]`).each((index, el) => {
//     if( $(el).data('slick-index') < currentSlide) {
//       // $(el).addClass('move-left')
//     } else if ($(el).data('slick-index') > currentSlide) {
//       // $(el).addClass('move-right')
//     } else {
//       $(el).addClass('active-circle')
//     }
//   })
  $('[data-circle-details]').html($slider.find('.slick-center .details__text').html()).animate({opacity: 1}, 300)
}
$slider.slick({
  responsive: [
      {
        breakpoint: 900,
        settings: {
          slidesToShow: 3
        }
      }
    ]
})
$slider.on('beforeChange', (event, slick, currentSlide, nextSlide) => {
  // $slider.find('.slide').removeClass('active-circle move-left move-right')
  // setActive(nextSlide)
  $('[data-circle-details]').animate({opacity: 0}, 300)
})
$slider.on('afterChange', (event, slick, currentSlide) => {
  setActive(currentSlide)
  // $('[data-circle-details]').html($slider.find('.slick-center .details__text').html()).animate({opacity: 1}, 0.5)
})
setActive(0)