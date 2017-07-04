import $ from 'jquery'

$(document).on('click', '[data-burger]', (e) => {
  e.preventDefault()
  if ($('[data-flyout-navi] #block-mainmenuen').length === 0) {
    $('[data-flyout-navi]').append([$('#block-mainmenuen').clone(), $('#block-headermeta').clone()])
  }
})

$(window).scroll(function() {
  let header = $('.site-header').height();
  let scroll = $(document).scrollTop();
  let nav = $('.area-navigation');
  let navHeight = nav.height();
  let row = $('.area-row');
  if (scroll < header) {
    nav.removeClass('fixed');
    row.css('margin-top', '');
  }
  else {
    nav.addClass('fixed');
    row.css('margin-top', navHeight);
  }
})

let $el = $('#block-mainmenu')[0] ? $('#block-mainmenu') : $('.area-navigation')
if ($el[0]) {
  setTimeout(() => {
    const $active = $el.find('.is-active')
    if ($active[0] && $active.offset().left + $active.outerWidth() > $el.width()) {
      const maxScroll = $el[0].scrollWidth - $el.width()
      $el.animate({
          scrollLeft: Math.min(maxScroll, $active.offset().left + $active.outerWidth() - $el.width())
      }, 500)
    }
  }, 800)
}

$('a[href^=http]').attr('target', '_blank')
