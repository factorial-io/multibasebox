import $ from 'jquery'
import mousewheelFactory from 'jquery-mousewheel'
import '../components/panorama'
import 'gsap/CSSPlugin'
import TweenLite from 'gsap/TweenLite'

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
    $(document).on('click', '.discover-slides__nav li', (e) => {
      this.gotoPage($('.discover-slides__nav li').index(e.currentTarget))
    })
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
    let $page = this.$slides.removeClass('active').eq(page).addClass('active')
    $('.discover-slides__nav li').removeClass('active').eq(page).addClass('active')
    
    if (page === this.page || (page === 0 && this.page === 0)) {
      return
    }
    
    this.$slides.removeClass('was-active transitioning').eq(this.page).addClass('was-active')
    $page.addClass('transitioning')
    
    const $mask = $('#circle-shape')
    let tweenObj = {scale: 1}
    const maskWidth = 175 // Cannot use getBBox() because of FF
    const maskHeight = 184 // Cannot use getBBox() because of FF

    const update = () => {
      $mask[0].setAttribute('transform', `translate(${$page.width()/2 - (maskWidth * tweenObj.scale) / 2 }, ${$page.height()/2 - (maskHeight * tweenObj.scale) / 2}) scale(${tweenObj.scale})`)

      const el = $page[0]
      el.style.display = 'none'
      el.offsetHeight
      el.style.display = 'block'
    }
    
    let scale = ($page.width() / maskWidth) * 2

    TweenLite.to(tweenObj, 2, {
      scale: scale, 
      onUpdate: update, 
      onComplete : () => {
        $page.removeClass('transitioning')
        tweenObj = {scale: 1}
        update()
        this.$slides.attr('style', null)
      }
    })
    
    this.page = page
  }
  
  nextPage() {
    if (this.page < this.totalPages - 1) {
      this.gotoPage(this.page + 1)
    }
  }
  
  previousPage() {
    if (this.page > 0) {
      this.gotoPage(this.page - 1)
    }
  }
}

new Discover()