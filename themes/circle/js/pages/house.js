import Snap from 'snapsvg'
import lazyload from '../utils/lazyload'
import $ from 'jquery'

const syncFilterLinks = () => {
  $('[data-module-id]').each((index, el) => {
    const id = $(el).data('module-id')
    $(el).toggleClass('active', $(`[data-layer-id=${id}]`).is('.active'))
  })
}
syncFilterLinks()

$('[data-module-id]').on('click', (e) => {
  const id = $(e.currentTarget).data('module-id')
  const $toLoad = $(`[data-layer-id=${id}]`)
  $('[data-module-id]').not(e.currentTarget).removeClass('active')
  $('[data-layer-id]').not($toLoad)
  lazyload($toLoad).then(($el) => {
    $toLoad.toggleClass('active')
    syncFilterLinks()
  })
})

$('[data-svg-url]').each((index, el) => {
  const snap = Snap(el)
  const syncBuildingHeight = () => {
    $(el).find('svg').height($('.house-floors__table').height())
  }
  Snap.load($(el).data('svg-url'), (fragment) => {
    snap.append(fragment)
    $('[id^=Highlight]').hide()
    $(window).on('resize.houseHeight', () => {
      syncBuildingHeight()
    })
    syncBuildingHeight()
  })
})