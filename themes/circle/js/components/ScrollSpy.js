import $ from 'jquery'

export const SpyDirection = {
  top: 0, // Top of window
  bottom: 1 // Bottom of window
}

export const SpyState =  {
  inside: 0,
  outside: 1
}

/**
 * Class to check if elements leave or enter the window
 */
class ScrollSpy {

  /**
   * @param  $els: JQuery             Elements to monitor
   * @param  direction: SpyDirection On which side does the element enter or leave the window
   */
  constructor($els, direction) {
    this.uniqueId = Date.now().toString(10)
    this.outCbs = []
    this.inCbs = []
    this.allCbs = []
    
    this.$els = $els
    
    this.spyState = SpyState.outside
    this.direction = direction

    $(window).on(`scroll.${this.uniqueId}`, (e) => {
      this.checkStates()
    })
  }
  checkStates() {
    $.each(this.$els, (index, el) => {
      const $el = $(el)
      const offset = $el.offset()
      const height = $el.height()

      if (this.direction === SpyDirection.top) {
        if (offset.top + height < $(window).scrollTop()) {
          this.out($el)
        } else {
          this.in($el)
        }
      } else if (this.direction === SpyDirection.bottom) {
        if (offset.top > $(window).scrollTop() + $(window).height()) {
          this.out($el)
        } else {
          this.in($el)
        }
      }
    })
  }
  destroy() {
    $(window).off(`scroll.${this.uniqueId}`)
  }
  setSpyState($el, state) {
    $el.data('spystate', state)
  }
  getSpyState($el) {
    return $el.data('spystate')
  }
  onAll(fn) {
    this.allCbs.push(fn)
    this.checkStates()
  }
  onOut(fn) {
    this.outCbs.push(fn)
    this.checkStates()
  }
  onIn(fn) {
    this.inCbs.push(fn)
    this.checkStates()
  }
  out($el) {
    if (this.getSpyState($el) === SpyState.outside) {
      return
    }

    this.setSpyState($el, SpyState.outside)

    for (let cb of this.outCbs) {
      cb($el)
    }
    for (let cb of this.allCbs) {
      cb($el)
    }
  }
  in($el) {
    if (this.getSpyState($el) === SpyState.inside) {
      return
    }
    this.setSpyState($el, SpyState.inside)

    for (let cb of this.inCbs) {
      cb($el)
    }
    for (let cb of this.allCbs) {
      cb($el)
    }
  }
}

export default ScrollSpy
