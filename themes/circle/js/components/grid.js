import $ from 'jquery'
import Masonry from 'masonry-layout'
import imagesLoaded from 'imagesloaded'

let grid = null
const $grid = $('.grid')

export const checkForLoad = () => {
  if (!grid) {
    return
  }
  
  imagesLoaded($grid).progress( () => {
    grid.layout()
  })
  
  imagesLoaded($grid, () => {
    setTimeout(() => {
        grid.layout()
    }, 500)
  })
}

if ($grid[0]) {
  grid = new Masonry($grid[0], {
    itemSelector: '.grid__element'
  })
  checkForLoad()
}