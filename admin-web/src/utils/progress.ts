let timer: number | null = null
let hideTimer: number | null = null
let maxTimer: number | null = null
let percent = 0

function bar() {
  let element = document.getElementById('top-progress')

  if (!element) {
    element = document.createElement('div')
    element.id = 'top-progress'
    document.body.appendChild(element)
  }

  return element
}

export function startProgress() {
  percent = 8
  const element = bar()

  if (hideTimer) {
    window.clearTimeout(hideTimer)
    hideTimer = null
  }

  if (timer) {
    window.clearInterval(timer)
  }

  if (maxTimer) {
    window.clearTimeout(maxTimer)
  }

  element.style.opacity = '1'
  element.style.transform = `scaleX(${percent / 100})`

  timer = window.setInterval(() => {
    percent = Math.min(percent + Math.random() * 10, 88)
    element.style.transform = `scaleX(${percent / 100})`
  }, 180)

  maxTimer = window.setTimeout(() => {
    finishProgress()
  }, 3000)
}

export function finishProgress() {
  const element = bar()

  if (timer) {
    window.clearInterval(timer)
    timer = null
  }

  if (maxTimer) {
    window.clearTimeout(maxTimer)
    maxTimer = null
  }

  element.style.transform = 'scaleX(1)'

  if (hideTimer) {
    window.clearTimeout(hideTimer)
  }

  hideTimer = window.setTimeout(() => {
    element.style.opacity = '0'
    element.style.transform = 'scaleX(0)'
    hideTimer = null
  }, 160)
}
