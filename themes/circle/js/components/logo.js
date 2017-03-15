import Snap from 'snapsvg'
import $ from 'jquery'

let animate = null
let stopped = false

const init = () => {
  const s = Snap('#logo')
  const path1 = s.select('.st0')
  const path2 = s.select('.st1')
  const path3 = s.select('.st2')
  const path4 = s.select('.st3')
  const path5 = s.select('.st4')
  const path6 = s.select('.st5')
  const path7 = s.select('.st6')

  let back = false
  const time = 14000
  const ease = mina.easeinout

  const path1d = path1.attr('d')
  const path2d = path2.attr('d')
  const path3d = path3.attr('d')
  const path4d = path4.attr('d')
  const path5d = path5.attr('d')
  const path6d = path6.attr('d')
  const path7d = path7.attr('d')

  animate = () => {
    if (stopped) {
      return
    }
    
    if (back) {
      path1.animate({
        d: path1d
      }, time, ease, () => {
        animate()
      })
        
      path2.animate({
        d: path2d
      }, time, ease)
        
      path3.animate({
        d: path3d
      }, time, ease)
        
      path4.animate({
        d: path4d
      }, time, ease)
        
      path5.animate({
        d: path5d
      }, time, ease)
        
      path6.animate({
        d: path6d
      }, time, ease)
        
      path7.animate({
        d: path7d
      }, time, ease)
    } else {
      // Put values from target SVG here!
      path1.animate({
        d: `M93.4,16.2C64,7.6,15,11.1,8.1,49c-7,38,28.8,104,58.3,112.6c29.4,8.6,83.2-23,90.1-61
        C163.4,62.7,122.9,24.8,93.4,16.2z M154.8,99.1c-6.6,36.2-60.1,68.6-88.5,60.4C38,151.2,4.3,85.8,10.9,49.6s54-39.4,82.3-31.2
        C121.5,26.7,161.5,62.9,154.8,99.1z`
      }, time, ease, () => {
        animate()
      })
        
      path2.animate({
        d: `M153.5,138.4c6.4-28.8,10.8-60.8-25.4-92.4C92,14.3,39.5,12.2,19,36.3c-20.4,24.2-7.6,69.3,28.4,100.9
        C83.6,168.8,143.3,184.2,153.5,138.4z M50,136.4C14.3,106.3,1.2,62.8,20.7,39c19.4-23.7,70.4-22.5,106,7.6s31.3,64.9,24.5,91.9
        C140.2,182.1,85.5,166.5,50,136.4z`
      }, time, ease)
        
      path3.animate({
        d: `M161.2,41.1c-19-28.7-86.2-42-115.4-22.4c-44.4,21-38.5,96.1-19.5,124.8c19,28.5,88.5,42.2,117.8,22.6
        C173.4,146.6,180.2,69.7,161.2,41.1z M27.4,141.6C10,113.2,4.5,40.1,52.5,17.3c30.8-14.6,91.8-1.5,109.3,26.8s8.6,103.1-20.5,121
        S45,170,27.4,141.6z`
      }, time, ease)
        
      path4.animate({
        d: `M153.3,50.3C128.4,12.2,70.9,11.4,43.1,28.8S0.1,91.4,25,129.6c24.9,38.1,90.5,64.4,118.3,47.1
        C171.1,159.2,178.2,88.5,153.3,50.3z M143.2,172.9c-26,18.2-87.9-5-114.1-40.7C2.9,96.6,14.3,51.2,40.3,33s83.2-20,109.4,15.7
        S169.2,154.8,143.2,172.9z`
      }, time, ease)
        
      path5.animate({
        d: `M85.8,3.7C53.9,0,20.9,24.6,13.6,66.6c-9.4,40,3,80.1,19.8,97.2c32,31.5,103.1-22.2,113-54.4
        C156.6,76.6,128.4,8.5,85.8,3.7z M143.8,111c-11.4,31.2-77,78.1-108.5,51.4C16.5,144.5,5.8,105.7,15.9,66C23.2,28.6,58.3-2,88.4,6.9
        C130.8,15.6,155.2,79.8,143.8,111z`
      }, time, ease)
        
      path6.animate({
        d: `M172.7,44.8c-1.9-28.7-46.9-21.2-81-19.9c-34.1,1.3-67.6,42.3-65.8,71c1.9,28.7,90.2,69.8,119.1,54.7
        C175.8,134.6,174.6,73.5,172.7,44.8z M143.9,148.9c-28.1,14-114.4-26.1-115.6-53.7C27,67.6,60,28,92.8,27.2s78-8.6,79.2,19
        C173.2,73.9,173.7,134,143.9,148.9z`
      }, time, ease)
        
      path7.animate({
        d: `M153.2,53.8c-17.6-27.9-54-41.3-84.6-29.8S10.4,68.8,23,99.3c18.7,45.2,70.5,61,97.4,49.9
        C149.2,137.3,170.8,81.8,153.2,53.8z M117,147.5c-30.2,9.4-79.6-8.1-92.6-51c-9-29.9,18.9-62,49.2-71.4c30.2-9.4,66.5,5.4,80.9,33.1
        C166.3,84.2,147.3,138.1,117,147.5z`
      }, time, ease)
    }
    back = !back
  }
}

if ($('#logo')[0]) {
  init()
}

export const stop = () => {
  stopped = true
}

export const resume = () => {
  stopped = false
  animate()
}

export {animate}