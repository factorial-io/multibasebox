import '../vendor/bs/collapse'
import lazyload from '../utils/lazyload'
import $ from 'jquery'

$('#more-items').on('show.bs.collapse', (e) => {
  lazyload($('#more-items'), false)
})