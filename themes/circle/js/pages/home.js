import '../vendor/bs/collapse'
import lazyload from '../utils/lazyload'
import $ from 'jquery'
import {animate} from '../components/logo'

if ($('.page-node-type-front-page')[0]) {
  $('#more-items').on('show.bs.collapse', (e) => {
    lazyload($('#more-items'), false)
  })

  animate()
}

$(".article-teaser").click((event) => {
  event.preventDefault()
  let url = $(event.currentTarget).find("a").attr("href")
  window.location = url
})
