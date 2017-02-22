import Snap from 'snapsvg'
import $ from 'jquery'

$('[data-svg-url]').each((index, el) => {
  const snap = Snap(el)
  Snap.load($(el).data('svg-url'), (fragment) => {
    snap.append(fragment)
    $('[id^=Highlight]').hide()
  })
})