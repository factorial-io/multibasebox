import $ from 'jquery'
import Tether from 'tether'

export const initHotspots = ($els) => {
  const pops = []
  $('[data-hotspot]', $els).each((index, el) => {
    const $pop = $(el).find('.popover')
    const $spot = $(el).find('.spot')
    pops.push($pop)
    const tether = new Tether({
      element: $pop[0],
      target: $spot[0],
      attachment: 'middle right',
      targetAttachment: 'middle left',
      constraints: [
        {
          to: $(el).parents('.discover-slides__fullwrap')[0],
          pin: true
        }
      ]
    })
    $spot.on('click', () => {
      $.each(pops, (index, $p) => {
        if ($p != $pop) {
          $p.hide()
        }
      })
      $pop.toggle()
      tether.position()
    })
  })
}