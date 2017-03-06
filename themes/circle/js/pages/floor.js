import $ from 'jquery'
import lazyload from '../utils/lazyload'

if ($('.page-node-type-rental-floor')[0]) {
  const $normalImg = $('.area-stage__images img').first()
  const $interiorImg = $('.area-stage__images img').last()
  const getState = () => {
   return $interiorImg.is(':visible') 
  }
  $interiorImg.hide()
  
  $('[data-interior-switch]').on('click', (e) => {
    if (getState()) {
      $interiorImg.hide()
    } else {
      $interiorImg.show()
      lazyload($('.area-stage'))
    }
    $(e.currentTarget).toggleClass('active', getState())
  })
}