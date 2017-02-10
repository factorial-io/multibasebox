import pannellum from 'pannellum'
import $ from 'jquery'

$('[data-panorama-url]').each((index, el) => {
  pannellum.viewer(el, {
    type: 'equirectangular',
    panorama: $(el).data('panorama-url'),
    mouseZoom: false,
    showControls: false,
    preview: decodeURI($(el).data('panorama-preview-url'))
  })
})