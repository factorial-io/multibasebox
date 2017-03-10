import $ from 'jquery'

const lazyload = ($els, visible = true) => {
    let $lazyEls = null
    const viSel = visible ? '[data-src]:visible' : '[data-src]'
    if ($els.is(viSel)) {
        $lazyEls = $els
    } else {
        $lazyEls = $els.find(viSel)
    }
    
    let loaded = 0
    return new Promise((resolve, reject) => {
      if ($lazyEls.length === 0) {
        return resolve($lazyEls)
      }
      
      $lazyEls.each((index, el) => {
        $(el).on('load', () => {
          loaded += 1
          
          if (loaded >= $lazyEls.length) {
            resolve($lazyEls)
          }
        })
        
        $(el).on('error', () => {
          reject($(el))
        })
        
        $(el).attr('src', $(el).attr('data-src')).attr('srcset', $(el).attr('data-srcset')).attr('sizes', $(el).attr('data-sizes'))
        $(el).attr('data-src', null).attr('data-srcset', null).attr('data-sizes', null)
        
        if (window.picturefill) {
          window.picturefill();
        }
      })
    })
}

export default lazyload