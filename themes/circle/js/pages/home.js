import '../vendor/bs/collapse'
import lazyload from '../utils/lazyload'
import $ from 'jquery'
import {animate} from '../components/logo'
import {classanimateOut, classanimateIn} from '../components/classanimate'
import ScrollSpy, {SpyDirection, SpyState} from '../components/ScrollSpy'


$('#more-items').on('show.bs.collapse', (e) => {
  lazyload($('#more-items'), false)
})

const sel = '.image-with-text:visible h2, .image-with-text:visible .para, .image-with-text:visible .button, .image-with-text:visible img'
classanimateOut($(`${sel}`))
const spy = new ScrollSpy($(`${sel}`), SpyDirection.bottom)
spy.onIn(($el) => {
  classanimateIn($el)
})

animate()