import $ from 'jquery'
import {loadUrl} from './modal'
import {getUrl} from '../utils/urls'

$('[data-newsletter-teaser]').on('submit', (e) => {
  e.preventDefault()
  loadUrl(getUrl(`newsletter-register/${$(e.currentTarget).find('[type=email]').val()}`))
})