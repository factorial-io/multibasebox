import $ from 'jquery'
import lazyload from '../utils/lazyload'
import TweenLite from 'gsap/TweenLite'
import 'gsap/CSSPlugin'
import 'gsap/EasePack'
import TimelineLite from 'gsap/TimelineLite'

$('[id^=H]').on('mouseover', (e) => {
  console.log($(e.currentTarget).attr('id'))
})

lazyload($('.interactive-area, .zoom-out-image')).then(() => {
  lazyload($('.circle-overlays'))
  const mainImage = $('.interactive-area img')[0]
  const zoomImage = $('.zoom-out-image img')[0]
  TweenLite.set(mainImage, {opacity: 0})
  TweenLite.set(mainImage, {opacity: 1, delay: 1})
  TweenLite.from(zoomImage, 0.3, {opacity: 0})
  TweenLite.to(zoomImage, 2.5, {scale: 2.25, rotation: -2, transformOrigin:"77% 87%", ease: Sine.easeOut, delay: 0.5})
  TweenLite.to(zoomImage, 0.8, {opacity: 0, delay: 2.9})
})