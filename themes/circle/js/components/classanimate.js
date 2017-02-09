export const classanimateIn = ($els) => {
  $els.removeClass('is-out').addClass('is-in-prepared animation')
  setTimeout(() => {
    $els.removeClass('is-in-prepared')
  }, 0)
}

export const classanimateOut = ($els) => {
  $els.addClass('is-out')
}