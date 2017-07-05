import Snap from 'snapsvg'
import lazyload from '../utils/lazyload'
import $ from 'jquery'
import Mustache from '../utils/mustache'

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
  $('[data-layer-id]').not($toLoad).removeClass('active')
  lazyload($toLoad).then(($el) => {
    $toLoad.toggleClass('active')
    syncFilterLinks()
  })
})

if ($('.page-node-type-rental-house')[0]) {
  $('[data-svg-url]').each((index, el) => {
    const snap = Snap(el)
    const syncBuildingHeight = () => {
      $(el).find('svg').height($('.house-floors__table tbody').height())
    }
    Snap.load($(el).data('svg-url'), (fragment) => {
      snap.append(fragment)
      $(window).on('resize.houseHeight', () => {
        syncBuildingHeight()
      })
      syncBuildingHeight()
      
      $('.house-floors__table tr').on('mouseover', (e) => {
        const floorNo = $(e.currentTarget).find('[data-floor-no]').data('floor-no')
        $(`#Highlight_${floorNo}`).addClass('active')
      }).on('mouseout', (e) => {
        $(`[id^="Highlight_"]`).removeClass('active')
      }).on('click', (e) => {
        const link = $(e.currentTarget).find('a');
        if (link.length) {
          window.location.href = link.attr('href');
        }
      })
    })
  })

  const cleanFlags = () => {
    $('.flag').remove()
  }

  $('[id^=H]').on('mouseover', (e) => {
    cleanFlags()
    const id = $(e.currentTarget).data('node-id')
    $('#house-overlay g').not(e.currentTarget).addClass('inactive')
  
    if (id) {
      const title = $(`[data-drupal-link-system-path="node/${id}"]`).text()
      $('.floor-images__wrapper').append(Mustache.render($('#house-flag').html(), {title: title}))
    }
  }).on('mouseout', () => {
    cleanFlags()
    $('#house-overlay g').removeClass('inactive')
  }).on('mousemove', (e) => {
    if ($('.flag')[0]) {
      const offset = $('.floor-images__wrapper').offset()
      TweenLite.set($('.flag')[0], {x: e.pageX - offset.left, y: e.pageY - offset.top})
    }
  })
}
