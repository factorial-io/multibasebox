import $ from 'jquery'

$('[id^=H]').on('mouseover', (e) => {
  console.log($(e.currentTarget).attr('id'))
})