const track = (cat, action, label) => {
  if (window.ga) {
    ga('send', 'event', {
      eventCategory: cat,
      eventAction: action,
      eventLabel: label,
      transport: 'beacon'
    })
  }
}

export default track