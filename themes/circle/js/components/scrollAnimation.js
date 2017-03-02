import lazyload from '../utils/lazyload'
import $ from 'jquery'
import {classanimateOut, classanimateIn} from '../components/classanimate'
import ScrollSpy, {SpyDirection, SpyState} from '../components/ScrollSpy'
import TweenLite from 'gsap/TweenLite'

const sel = '.image-with-text:visible h2, .image-with-text:visible .para, .image-with-text:visible .button, .image-with-text:visible img, .news-teaser .article-teaser'
classanimateOut($(`${sel}`))

const spy = new ScrollSpy($(`${sel}`), SpyDirection.bottom)
spy.onIn(($el) => {
  lazyload($el)
  classanimateIn($el)
})

if ($('[data-sold-percentage]')[0]) {
  const percentageSpy = new ScrollSpy($('[data-sold-percentage]'), SpyDirection.bottom)
  const tweenObj = {percentage: 0}
  percentageSpy.onIn(($el) => {
    const valueMax = $el.attr('aria-valuemax')
    TweenLite.to(tweenObj, 2, {percentage: $el.data('sold-percentage'), onUpdate: () => {
      $el.css({width: `${tweenObj.percentage}%`})
      $el.find('[data-sold-value]').html(Math.round(valueMax * tweenObj.percentage / 100))
    }})
  })
}