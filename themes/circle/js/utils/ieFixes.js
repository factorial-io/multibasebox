const svg = document.querySelectorAll('[data-svg-container] svg');
const len = svg.length; 
for (let i=0; i<len; i++) {
  let viewBox = svg[i].getAttribute('viewBox')
  viewBox = viewBox.replace(/\s\s+/g, ' ')
  const w = viewBox.split(' ')[2]
  const h = viewBox.split(' ')[3]
  const x = h / w * 100
  svg[i].parentNode.setAttribute('style', 'padding-bottom:' + x +'%')
}
