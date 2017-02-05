import Modal from '../vendor/bs/modal'
import $ from 'jquery'

const $modalEl = $('#site-modal')

const siteModal = new Modal($modalEl[0])

export const loadUrl = (url) => {
  $modalEl.find('.modal-dialog').load(url, () => {
    siteModal.show($modalEl[0])
  })
}

export default siteModal