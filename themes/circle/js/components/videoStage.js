import Player from '@vimeo/player'
import {classanimateIn, classanimateOut} from './classanimate'
import {scrollTo} from './scroll'
import $ from 'jquery'

$('[data-video-stage]').each((index, el) => {
  let currentId = 0

  const activateThumbWithId = (id) => {
    const $thumb = $(`[data-vimeo-video-id=${id}]`, $(el))
    classanimateOut($('[data-vimeo-video-id]', $(el)))
    classanimateIn($thumb)
    return $thumb
  }

  const getIdFromThumb = ($thumb) => {
    return $thumb.data('vimeo-video-id')
  }

  const player = new Player($('[data-video-player]', $(el))[0], {
    id: getIdFromThumb($(el).find('[data-vimeo-video-id]').first())
  })

  player.on('loaded', (e) => {
    currentId = e.id
    activateThumbWithId(e.id)
  })
  
  const playingClass = 'playing'
  let playTimeout = null
  const stoppedPlaying = () => {
    if (playTimeout) {
      clearTimeout(playTimeout)
      playTimeout = null
    }
    $(el).removeClass(playingClass)
  }
  player.on('play', (e) => {
    playTimeout = setTimeout(() => {
      $(el).addClass(playingClass)
    }, 5000)
  })
  
  player.on('pause', (e) => {
    stoppedPlaying()
  })
  
  player.on('ended', (e) => {
    stoppedPlaying()
  })
  
  $('[data-vimeo-video-id]', $(el)).on('click', (e) => {
    const lastId = currentId
    const newId = getIdFromThumb( $(e.currentTarget) )

    if (lastId != newId) {
      player.loadVideo( newId ).then((id) => {
        player.play()
      }).catch((e) => {
        console.log(e);
      })
    } else {
      player.play()
    }
    scrollTo($('[data-video-player]', $(el)))
  })

  $('[data-video-thumbs]', $(el)).slick({
    slidesToShow: 3,
    slidesToSlide: 1,
    prevArrow: $(el).find('[data-swipe-prev]'),
    nextArrow: $(el).find('[data-swipe-next]')
  })
})
