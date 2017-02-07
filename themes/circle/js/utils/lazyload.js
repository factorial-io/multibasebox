import $ from 'jquery'

const lazyload = ($els, visible = true) => {
    let $lazyEls = null
    const viSel = visible ? '[data-src]:visible' : '[data-src]'
    if ($els.is(viSel)) {
        $lazyEls = $els
    } else {
        $lazyEls = $els.find(viSel)
    }
    
    $lazyEls.each((index, el) => {
        $(el).attr('src', $(el).attr('data-src')).attr('srcset', $(el).attr('data-srcset')).attr('sizes', $(el).attr('data-sizes'))
        $(el).attr('data-src', null).attr('data-srcset', null).attr('data-sizes', null)
    })
}

export default lazyload