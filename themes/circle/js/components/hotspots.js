import $ from 'jquery'

export const initHotspots = ($els) => {
  if ($els.data('hotspots')) {
    return
  }
  $els.data('hotspots', true)
  
  const pops = []
  $('[data-hotspot]', $els).each((index, el) => {
    const $pop = $(el).find('.popover')
    const $spot = $(el).find('.flag')
    
    pops.push($pop)
    
    $spot.on('click', (e) => {

      $(el).parent().find('.flag').not(e.currentTarget).removeClass('active')
      
      $.each(pops, (index, $p) => {
        if ($p != $pop) {
          $p.addClass('closed')
        }
      })
      $pop.toggleClass('closed')
      $spot.toggleClass('active', !$pop.is('.closed'))
    })
  })
}