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
  percentageSpy.onIn(($el) => {
    const tweenObj = {percentage: 0}
    const valueMax = $el.attr('aria-valuemax')
    TweenLite.to(tweenObj, 2, {percentage: $el.data('sold-percentage'), onUpdate: () => {
      $el.css({width: `${tweenObj.percentage}%`})
      $el.find('[data-sold-value]').html(Math.round(valueMax * tweenObj.percentage / 100))
    }})
  })
}

if ($('[data-count-up]')[0]) {
  const countUpSpy = new ScrollSpy($('[data-count-up]'), SpyDirection.bottom)
  countUpSpy.onIn(($el) => {
    const countUp = $el.data('count-up')
    const tweenObj = {value: 0}
    TweenLite.to(tweenObj, 2, {value: countUp, onUpdate: () => {
      $el.html(Math.round(tweenObj.value))
    }})
  })
}