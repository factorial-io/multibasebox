import $ from 'jquery'

$(document).on('click', '[data-video-autoplay]', (e) => {
  e.currentTarget.play()
})