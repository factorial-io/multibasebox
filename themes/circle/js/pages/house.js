import Snap from 'snapsvg'
import $ from 'jquery'

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