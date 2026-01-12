function closeNotification(e) {
  var customNotification
  if (e.classList && e.classList.contains('custom-notification')) {
    customNotification = e
  } else {
    customNotification = e.target.closest('.custom-notification')
  }

  customNotification.classList.add('custom-notification--close')
}

function removeNotification(e) {
  var customNotification = e.target

  if (customNotification.classList.contains('custom-notification--close')) {
    customNotification.parentNode.remove()
  }
}

function notify(options) {
  var html = `
    <div onanimationend="removeNotification(event)" class="custom-notification ${options.type && options.type === 'error' ? 'custom-notification--error' : ''}">
      <span class="custom-notification__icon">
        ${ options.type && options.type === 'error' ? `<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>` : `<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" fill="none" viewBox="0 0 24 24"stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>`}
      </span>
      <div class="custom-notification__content">
        <p class="custom-notification__heading">`+ options.heading + `</p>
        <p class="custom-notification__text">`+ options.text + `</p>
      </div>
      <button onclick="closeNotification(event)" class="custom-notification__close-btn">
        <svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" fill="none" viewBox="0 0 24 24"
          stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
        </svg>
      </button>
    </div>
  `
  var div = document.createElement('div')
  div.classList.add('custom-notification-container')
  div.innerHTML = html

  document.body.appendChild(div)

  if (options.autoCloseAfter) {
    setTimeout(function () {
      closeNotification(div.firstElementChild)
    }, options.autoCloseAfter);
  }
}