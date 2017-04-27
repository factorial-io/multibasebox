const track = (cat, action, label, value) => {
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