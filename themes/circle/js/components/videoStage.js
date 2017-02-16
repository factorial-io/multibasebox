import Player from '@vimeo/player'
import $ from 'jquery'

$('[data-video-stage]').each((index, el) => {
  const player = new Player($('[data-video-player]', $(el))[0], {
    id: parseInt($(el).find('[data-vimeo-video-id]').first().data('vimeo-video-id'))
  })

  $('[data-vimeo-video-id]', $(el)).on('click', (e) => {
    player.loadVideo( parseInt( $(e.currentTarget).data('vimeo-video-id') ) ).then((id) => {
      console.log(id)
      player.play()
    }).catch((e) => {
      console.log(e);
    })
  })
})
