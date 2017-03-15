import $ from 'jquery'
import mousewheelFactory from 'jquery-mousewheel'
import Hammer from 'hammerjs'
import TweenLite from 'gsap/TweenLite'
import 'gsap/CSSPlugin'
import TimelineLite from 'gsap/TimelineLite'
import {animate, stop} from '../components/logo'
import lazyload from '../utils/lazyload'
import {initHotspots} from '../components/hotspots'
import Mustache from '../utils/mustache'

class Discover {
  constructor() {
    this.$slides = $('.discover-slides__slide:not([data-preloader])')
    this.page = -1
    this.blocked = false
    this.totalPages = this.$slides.length
    
    if ($('.page-node-type-discover-page')[0]) {
      this.load()
    }
  }
  load() {
    const $preloader = $('[data-preloader]')
    if (animate) {
      animate()
    }
    const sel = '[data-bg-desk]'
    const total = $(sel).length
    let loaded = 0
    $(sel).each((index, el) => {
      $('<img>').attr('src', $(el).data('bg-desk')).on('load', () => {
        $(el).css('background-image', `url(${ $(el).data('bg-desk') })`)
        loaded += 1
        const percentage = loaded/total * 100
        $('.progress-bar').css({width: `${percentage}%`}).attr('aria-valuenow', percentage)
        if (loaded >= total) {
          this.gotoPage(0, () => {
            stop()
            $preloader.remove()
            this.init()
          })
        }
      })
    })
  }
  init() {
    mousewheelFactory($)
    const hammertime = new Hammer($('body')[0])
    hammertime.get('swipe').set({ direction: Hammer.DIRECTION_VERTICAL })
    hammertime.on('swipe', (ev) => {
    	if (ev.deltaY > 0) {
        this.previousPage()
      } else {
        this.nextPage()
      }
    })
    $('body').on('mousewheel', this.scrollHandler.bind(this))
    $(document).on('click', '.discover-slides__nav li', (e) => {
      this.gotoPage($('.discover-slides__nav li').index(e.currentTarget))
    })
  }
  
  scrollHandler(e) {
    e.preventDefault()
    if (e.deltaY === 1 || e.deltaY === -1) {
      this.blocked = false
    }
    if (this.blocked) {
      return
    }
    if (e.deltaY < -3) {
      this.blocked = true
      this.nextPage()
    }
    if (e.deltaY > 3) {
      this.blocked = true
      this.previousPage()
    }
  }
  
  gotoPage(page, cb) {
    let $page = this.$slides.removeClass('active').eq(page).addClass('active')
    $('.discover-slides__nav li').removeClass('active').eq(page).addClass('active')
    
    if (page === this.page || (page === 0 && this.page === 0)) {
      return
    }
    
    if (this.page > -1) { //special case preloader
      this.$slides.removeClass('was-active transitioning').eq(this.page).addClass('was-active')
    }
    
    /**
    * Title Animation
    **/
    if (this.titleTimeline) {
      this.titleTimeline.kill()
      $('.title-ani').remove()
    }
    if (page > 0 || this.page > 1) {
      let title = null
      if (this.page !== 0) {
        title = $page.find('[data-title]').data('title')
      }
      $page.append(Mustache.render($('#title-ani').html(), {title: title}))
    
      const titleEl = $('.title-ani h3')[0]
      const overlay = $('.title-ani')[0]
      this.titleTimeline = new TimelineLite({
        onComplete: () => {
          $('.title-ani').remove()
        }
      })
      TweenLite.set(overlay, {opacity: 0})
      TweenLite.set(titleEl, {scale: 0.5})
      this.titleTimeline.to(titleEl, .5, {scale: 1})
      this.titleTimeline.to(overlay, .5, {opacity: 1}, '-=0.5')
      this.titleTimeline.to(overlay, 1, {opacity: 0}, '+=1.5')
      this.titleTimeline.play()
    }
    /**
    * End Title Animation
    **/
    
    
    /**
    * Mask Animation
    **/
    $page.addClass('transitioning')
    
    const $mask = $('#circle-shape')
    let tweenObj = {scale: 1}
    const maskWidth = 175 // Cannot use getBBox() because of FF
    const maskHeight = 184 // Cannot use getBBox() because of FF

    const update = () => {
      $mask[0].setAttribute('transform', `translate(${$page.width()/2 - (maskWidth * tweenObj.scale) / 2 }, ${$page.height()/2 - (maskHeight * tweenObj.scale) / 2}) scale(${tweenObj.scale})`)

      const el = $page[0]
      el.style.display = 'none' //Force rerendering in Safari
      el.offsetHeight
      el.style.display = 'block'
    }
    
    const scaleRef = Math.max($page.width(), $page.height())
    let scale = (scaleRef / maskWidth) * 2

    TweenLite.to(tweenObj, 2, {
      scale: scale, 
      onUpdate: update, 
      onComplete : () => {
        $page.removeClass('transitioning')
        tweenObj = {scale: 1}
        update()
        lazyload($page, false)
        initHotspots($page)
        this.$slides.attr('style', null)
        if (cb) {
          cb()
        }
      }
    })
    
    if (!window.CSS || !CSS.supports('clip-path', 'url(#svg)')) {
      TweenLite.set($page[0], {opacity: 0})
      TweenLite.to($page[0], 2, {opacity: 1})
    }
    /**
    * End Mask Animation
    **/

    
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