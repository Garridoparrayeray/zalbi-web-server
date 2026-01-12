function setUpDropdown(dropdown) {
  var dropdownContentContainer = dropdown.querySelector('[data-selected-dropdown-item-id]')
  if (dropdownContentContainer) {
    var selectedItemId = dropdownContentContainer.getAttribute('data-selected-dropdown-item-id')

    if (selectedItemId !== 'false') {
      var itemContent = dropdown.querySelector('[data-dropdown-item-id="' + selectedItemId + '"]').textContent

      dropdownContentContainer.textContent = itemContent
    }
  } else {
    console.error('Could not find the "data-selected-dropdown-item-id" attribute in a custom dropdown')
  }
}

function toggleCustomDropdown(event) {
  var currentDropdown = event.target.closest('.custom-dropdown')

  document.querySelectorAll('.custom-dropdown').forEach(function (dropdown) {
    if (currentDropdown !== dropdown)
      dropdown.classList.remove('custom-dropdown--show')
  })

  currentDropdown.classList.toggle('custom-dropdown--show')
}

function handleULClick(event) {
  event.stopPropagation()

  var clickedItem = event.target.closest('[data-dropdown-item-id]')
  var newSelectedId = clickedItem.getAttribute('data-dropdown-item-id')

  var dropdown = event.target.closest('.custom-dropdown')
  var dropdownContentContainer = dropdown.querySelector('[data-selected-dropdown-item-id]')

  /* set a fixed width for the dropdown */
  var textElement = dropdown.querySelector('.custom-dropdown-toggle__text')
  textElement.style.width = textElement.offsetWidth + 'px'

  /* update the dropdown text and selected id */
  var itemContent = clickedItem.textContent
  dropdownContentContainer.setAttribute('data-selected-dropdown-item-id', newSelectedId)
  dropdownContentContainer.textContent = itemContent

  /* remove previously selected item */
  var previouslySelectedItem = dropdown.querySelector('.custom-dropdown__li--selected')
  if (previouslySelectedItem) previouslySelectedItem.classList.remove('custom-dropdown__li--selected')

  /* highlight the selected item */
  clickedItem.classList.add('custom-dropdown__li--selected')

  /* close the dropdown on selection */
  dropdown.classList.remove('custom-dropdown--show')
}

window.onclick = function (event) {
  if (!event.target.closest('.custom-dropdown')) {
    document.querySelectorAll('.custom-dropdown').forEach(function (dropdown) {
      dropdown.classList.remove('custom-dropdown--show')
    })
  }
}

document.querySelectorAll('.custom-dropdown').forEach(function (dropdown) {
  setUpDropdown(dropdown)
})