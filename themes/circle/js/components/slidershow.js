import $ from 'jquery'
import Swiper from 'swiper';

const slidershowContainer = $('.slidershow-container');
if (slidershowContainer.length) {
  const swiper = new Swiper('.slidershow-container', {
    wrapperClass: 'slidershow-wrapper',
    slideClass: 'slidershow-slide',
    slideActiveClass: 'is-active',
    slidePrevClass: 'is-prev',
    slideNextClass: 'is-next',
    pagination: '.slidershow-pagination',
    nextButton: '.slidershow-button--next',
    prevButton: '.slidershow-button--prev',
    buttonDisabledClass: 'is-disabled',
    pagination: '.slidershow-pagination',
    paginationModifierClass: 'slidershow-pagination--',
    paginationClickable: true,
    paginationClickableClass: 'slidershow-pagination--clickable',
    bulletClass: 'slidershow-paginationBullet',
    bulletActiveClass: 'is-active',
    slidesPerView: slidershowContainer.data('slides-per-view'),
    paginationClickable: true,
    spaceBetween: slidershowContainer.data('space'),
    loop: true
  });
}
