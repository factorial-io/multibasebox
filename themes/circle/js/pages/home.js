import '../vendor/bs/collapse'
import lazyload from '../utils/lazyload'
import $ from 'jquery'
import {animate} from '../components/logo'


$('#more-items').on('show.bs.collapse', (e) => {
  lazyload($('#more-items'), false)
})

animate()