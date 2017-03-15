import $ from 'jquery'
import TweenLite from 'gsap/TweenLite'

// Fix rendering problem in iOS and Android
setTimeout(() => {
  const $els = $('#bubble, #legend')
  $.each($els, (index, el) => {
    TweenLite.set(el, {x: 1, y: 1})
  })
}, 1000)