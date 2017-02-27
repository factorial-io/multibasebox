import lazyload from '../utils/lazyload'
import $ from 'jquery'
import {classanimateOut, classanimateIn} from '../components/classanimate'
import ScrollSpy, {SpyDirection, SpyState} from '../components/ScrollSpy'

const sel = '.image-with-text:visible h2, .image-with-text:visible .para, .image-with-text:visible .button, .image-with-text:visible img, .news-teaser .article-teaser'
classanimateOut($(`${sel}`))

const spy = new ScrollSpy($(`${sel}`), SpyDirection.bottom)
spy.onIn(($el) => {
  lazyload($el)
  classanimateIn($el)
})