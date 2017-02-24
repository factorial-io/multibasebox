import $ from 'jquery'

$(document).on('click', '[data-burger]', (e) => {
  e.preventDefault()
  if ($('[data-flyout-navi] #block-mainmenuen').length === 0) {
    $('[data-flyout-navi]').append([$('#block-mainmenuen').clone(), $('#block-headermeta').clone()])
  }
})