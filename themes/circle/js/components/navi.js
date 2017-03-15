import $ from 'jquery'

$(document).on('click', '[data-burger]', (e) => {
  e.preventDefault()
  if ($('[data-flyout-navi] #block-mainmenuen').length === 0) {
    $('[data-flyout-navi]').append([$('#block-mainmenuen').clone(), $('#block-headermeta').clone()])
  }
})

let $el = $('#block-mainmenu')[0] ? $('#block-mainmenu') : $('.area-navigation')
if ($el[0]) {
  setTimeout(() => {
    const $active = $el.find('.is-active')
    if ($active.offset().left + $active.outerWidth() > $el.width()) {
      const maxScroll = $el[0].scrollWidth - $el.width()
      $el.animate({
          scrollLeft: Math.min(maxScroll, $active.offset().left + $active.outerWidth() - $el.width())
      }, 500)
    }
  }, 800)
}