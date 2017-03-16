import $ from 'jquery'
import {scrollToTop} from './scroll'
import ScrollSpy, {SpyDirection, SpyState} from './ScrollSpy'

if ( $('[data-to-top]')[0] ) {
  const toggleToTop = (show = true) => {
    $('[data-to-top]').toggle(show)
  }
  
  const checkToggle = () => {
    if ($('.pre-footer').data('spystate') === SpyState.inside) {
      toggleToTop(false)
    } else if (
      $(document).height() / $(window).height() > 2.5 && 
      $(window).scrollTop() > 2 * $(window).height()
      ) {
      toggleToTop(true)
    } else {
      toggleToTop(false)
    }
  }

  $(window).on('scroll.totop', (e) => {
    checkToggle()
  })

  $(document).on('click', '[data-to-top]', () => {
    scrollToTop()
  })

  const scrollSpy = new ScrollSpy($('.pre-footer'), SpyDirection.bottom)
  scrollSpy.onAll(($el) => {
    checkToggle()
  })
}