import $ from 'jquery'
import {loadUrl} from './modal'
import {getUrl} from '../utils/urls'

$(document).on('click', '[data-action=open_cam]', (e) => {
  e.preventDefault()
  loadUrl(getUrl(`livecam`))
})