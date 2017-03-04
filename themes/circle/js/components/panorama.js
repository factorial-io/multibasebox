import pannellum from 'pannellum'
import {getString} from '../utils/strings'
import $ from 'jquery'

$('[data-panorama-url]').each((index, el) => {
  const $close = $(el).parent().find('[data-panorama-close]')
  const viewer = pannellum.viewer(el, {
    type: 'equirectangular',
    panorama: $(el).data('panorama-url'),
    mouseZoom: false,
    showControls: false,
    preview: $(el).data('panorama-preview-url'),
    default: {
      loadButtonLabel: getString('panorama-loadtext')
    }
  })
  viewer.on('load', () => {
    $close.show()
  })
  $close.on('click', (e) => {
    viewer.standby()
    $close.hide()
  })
})