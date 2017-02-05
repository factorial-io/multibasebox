const Loader = {
  startCbs: [],
  completeCbs: [],
  start: ($container = $('body')) {
    // TODO: add Loader El
    for (cb of Loader.startCbs) {
      cb($container)
    }
  },
  complete: ($el, $container = $('body')) {
    // TODO: remove Loader El
    
    for (cb of Loader.complete) {
      cb($el, $container)
    }
  },
  onStart: (cb) => {
    Loader.startCbs.push(cb)
  },
  onComplete: (cb) => {
    Loader.completeCbs.push(cb)
  }
}

export default Loader