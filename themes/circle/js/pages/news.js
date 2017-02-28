import $ from 'jquery'
import getGrid, {checkForLoad} from '../components/grid'

const loadMoreSelect = '[data-news-view] [data-load-more]'
const $loadMore = $(loadMoreSelect)
const $realPager = $('[data-news-view] [data-pager]')

const getNextUrl = () => {
  return $realPager.find('[rel=next]').attr('href')
}

if (!getNextUrl()) {
  $loadMore.remove()
}

$(document).on('click', loadMoreSelect, (e) => {
  e.preventDefault()
  const nextUrl = getNextUrl()

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

      const $pager = $(data).find('[data-pager]')
      if (!$pager.find('[data-pager]')[0]) {
        $loadMore.remove()
      }
      $realPager.replaceWith($pager)
    })
  }
})

$(document).on('click', '.article-teaser', (event) => {
  event.preventDefault()
  let url = $(event.currentTarget).find('h3 a').attr('href')
  window.location = url
})
