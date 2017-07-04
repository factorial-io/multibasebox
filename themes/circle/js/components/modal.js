import Modal from '../vendor/bs/modal'
import $ from 'jquery'
import track from '../utils/tracking'

const $modalEl = $('#site-modal')

const siteModal = new Modal($modalEl[0])

export const loadUrl = (url) => {
  $modalEl.find('.modal-dialog').load(url, () => {
    siteModal.show($modalEl[0])
    track('Modalbox', 'show', url)
  })
}

export default siteModal