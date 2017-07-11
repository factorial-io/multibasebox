import $ from 'jquery'
import getGrid, {checkForLoad} from '../components/grid'

const loadMoreSelect = '[data-downloads-view] [data-load-more]'
const $loadMore = $(loadMoreSelect)
let $realPager = $('[data-downloads-view] [data-pager]')

const getNextUrl = ($pager) => {
  return $pager.find('[rel=next]').attr('href')
}

if (!getNextUrl($realPager)) {
  $loadMore.remove()
}

$(document).on('click', loadMoreSelect, (e) => {
  e.preventDefault()
  const nextUrl = getNextUrl($realPager)

  if (nextUrl) {
    $loadMore.addClass('loading')
    $.get(nextUrl).then((data) => {
      $loadMore.removeClass('loading')

      const grid = getGrid()
      const $els = $(data).find('.grid__element')

      $('.grid').append($els)
      grid.appended($els)
      checkForLoad()

      if (window.history) {
        history.pushState({}, null, nextUrl)
      }

      const $newPager = $(data).find('[data-pager]')
      if (!getNextUrl($newPager)) {
        $loadMore.remove()
      }
      $realPager.replaceWith($newPager)
      $realPager = $newPager
    })
  }
})

$(document).on('click', '.downloads-teaser .article-teaser', (event) => {
  event.preventDefault()
  let url = $(event.currentTarget).find('h3 a').attr('href')
  window.location = url
})

$(document).on('click', '[data-popup-link]', function(e) {
  e.preventDefault()
  window.open(this.href, '', 'menubar=no,toolbar=no,resizable=yes,scrollbars=yes,height=450,width=600')
})
