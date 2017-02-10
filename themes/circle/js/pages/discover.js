import $ from 'jquery'
import mousewheelFactory from 'jquery-mousewheel'
import '../components/panorama'

class Discover {
  constructor() {
    this.$slides = $('.discover-slides__slide')
    this.page = 0
    this.blocked = false
    this.totalPages = this.$slides.length
    
    if ($('.page-node-type-discover-page')[0]) {
      this.init()
    }
  }
  
  init() {
    mousewheelFactory($)
    $('body').on('mousewheel', this.scrollHandler.bind(this))
    this.gotoPage(this.page)
  }
  
  scrollHandler(e) {
    e.preventDefault()
    if (e.deltaY === 1 || e.deltaY === -1) {
      this.blocked = false
    }
    if (this.blocked) {
      return
    }
    if (e.deltaY < -10) {
      this.blocked = true
      this.nextPage()
    }
    if (e.deltaY > 10) {
      this.blocked = true
      this.previousPage()
    }
  }
  
  gotoPage(page) {
    this.$slides.css('zIndex', 0).eq(page).css('zIndex', 5)
    $('.discover-slides__nav li').removeClass('active').eq(page).addClass('active')
  }
  
  nextPage() {
    if (this.page < this.totalPages - 1) {
      this.page += 1
      this.gotoPage(this.page)
    }
  }
  
  previousPage() {
    if (this.page > 0) {
      this.page -= 1
      this.gotoPage(this.page)
    }
  }
}

new Discover()