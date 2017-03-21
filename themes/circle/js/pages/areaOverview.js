import $ from 'jquery'
import lazyload from '../utils/lazyload'
import TweenLite from 'gsap/TweenLite'
import 'gsap/CSSPlugin'
import 'gsap/EasePack'
import TimelineLite from 'gsap/TimelineLite'
import Mustache from '../utils/mustache'

if ($('.page-node-type-rental-overview')[0]) {
  const cleanFlags = () => {
    $('.flag').remove()
  }

  $('[id^=H]').on('mouseover', (e) => {
    cleanFlags()
    const id = $(e.currentTarget).data('node-id')
    $('#house-overlay g').not(e.currentTarget).addClass('inactive')
  
    if (id) {
      const title = $(`[data-drupal-link-system-path="node/${id}"]`).text()
      $('.interactive-area').append(Mustache.render($('#house-flag').html(), {title: title}))
    }
  }).on('mouseout', () => {
    cleanFlags()
    $('#house-overlay g').removeClass('inactive')
  }).on('mousemove', (e) => {
    if ($('.flag')[0]) {
      const offset = $('.interactive-area').offset()
      TweenLite.set($('.flag')[0], {x: e.pageX - offset.left, y: e.pageY - offset.top})
    }
  })
  
  $('#house-overlay').hide()
  
  lazyload($('.interactive-area, .zoom-out-image')).then(() => {
    lazyload($('.circle-layer'))
    const mainImage = $('.interactive-area img')[0]
    const zoomImage = $('.zoom-out-image img')[0]
    TweenLite.set(mainImage, {opacity: 0})
    TweenLite.set(mainImage, {opacity: 1, delay: 1})
    TweenLite.from(zoomImage, 0.3, {opacity: 0})
    TweenLite.to(zoomImage, 2.3, {scale: 2.23, rotation: 0, x: '-35.1%', y: '-44.46%', ease: Sine.easeOut, delay: 0.5, onComplete: () => {
      $('#house-overlay').show()
    }})
    TweenLite.to(zoomImage, 0.8, {opacity: 0, delay: 2.7})
  })
}