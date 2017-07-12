import $ from 'jquery'
import Swiper from 'swiper';

var swiper = new Swiper('.download-slidershow', {
  wrapperClass: 'download-slidershow-wrapper',
  slideClass: 'download-slidershow-slide',
  slideActiveClass: 'is-active',
  slidePrevClass: 'is-prev',
  slideNextClass: 'is-next',
  pagination: '.download-slidershow-pagination',
  nextButton: '.download-slidershow-button--next',
  prevButton: '.download-slidershow-button--prev',
  buttonDisabledClass: 'is-disabled',
  pagination: '.download-slidershow-pagination',
  paginationModifierClass: 'download-slidershow-pagination--',
  paginationClickable: true,
  paginationClickableClass: 'download-slidershow-pagination--clickable',
  bulletClass: 'download-slidershow-paginationBullet',
  bulletActiveClass: 'is-active',
  slidesPerView: 1,
  paginationClickable: true,
  spaceBetween: 24,
  loop: true
});


