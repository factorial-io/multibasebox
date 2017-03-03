import $ from 'jquery'

export const initHotspots = ($els) => {
  const pops = []
  $('[data-hotspot]', $els).each((index, el) => {
    const $pop = $(el).find('.popover')
    const $spot = $(el).find('.spot')
    
    pops.push($pop)
    
    $spot.on('click', () => {
      $.each(pops, (index, $p) => {
        if ($p != $pop) {
          $p.hide()
        }
      })
      $pop.toggle()
    })
  })
}