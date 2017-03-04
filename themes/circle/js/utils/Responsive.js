let responsiveCbs = []

export const RESKEYS = {
  xs: 'xs',
  sm: 'sm',
  md: 'md',
  lg: 'lg',
  xl: 'xl'
}

let Responsive = {
  BREAKPOINTS: {
  },
  KEYS: [],
  addCallback(fn) {
    responsiveCbs.push(fn)
  },
  greaterThan: (res) => {
    let index = Responsive.KEYS.indexOf(res)
    let currIndex = Responsive.KEYS.indexOf(Responsive.currentRes)
    return index <= currIndex
  },
  smallerThan: (res) => {
    let index = Responsive.KEYS.indexOf(res)
    let currIndex = Responsive.KEYS.indexOf(Responsive.currentRes)
    return index >= currIndex
  }
}

Responsive.BREAKPOINTS[RESKEYS.xs] = 0
Responsive.BREAKPOINTS[RESKEYS.sm] = 576
Responsive.BREAKPOINTS[RESKEYS.md] = 768
Responsive.BREAKPOINTS[RESKEYS.lg] = 992
Responsive.BREAKPOINTS[RESKEYS.xl] = 1200

Responsive.KEYS = Object.keys(Responsive.BREAKPOINTS)

let matchers = {}
for (let res in Responsive.BREAKPOINTS) {
  if (Responsive.BREAKPOINTS.hasOwnProperty(res)) {
    matchers[res] = window
      .matchMedia(`(min-width:${Responsive.BREAKPOINTS[res]}px)`)

    matchers[res].addListener( (matcher) => {
      if (matcher.matches) {
        Responsive.currentRes = res
      } else {
        let index = Responsive.KEYS.indexOf(res)
        index--
        Responsive.currentRes = Responsive.KEYS[index]
      }
      for (let cb of responsiveCbs) {
        cb.call(this, Responsive)
      }
    })

    if (matchers[res].matches) {
      Responsive.currentRes = res
    }
  }
}

export default Responsive