document.addEventListener('DOMContentLoaded', function () {
  const forms = document.querySelectorAll('.wp-block-dwnload-wp-email-download form')

  forms.forEach(function (form) {
    form.addEventListener('submit', function (e) {
      e.preventDefault()

      const emailInput = form.querySelector('input[name="email"]')
      const listIdInput = form.querySelector('input[name="list_id"]')
      const fileIdInput = form.querySelector('input[name="file_id"]')
      const submitButton = form.querySelector('button.EmailDownload__button')
      const notice = document.querySelector('.EmailDownload__notice')
      const nonce = document.querySelector('input[name="_wpnonce"]')

      // Clear previous messages
      if (notice.childNodes.length > 0) {
        notice.innerHTML = ''
      }

      // Validate email
      const email = emailInput.value.trim()
      if (!email || !isValidEmail(email)) {
        showMessage(notice, 'Please enter a valid email address.', 'error')
        return
      }

      // Set loading state
      submitButton.disabled = true
      submitButton.innerHTML = '<span class="spinner"></span> Checking...'

      // Prepare request data
      const data = {
        email: email,
        list_id: listIdInput.value,
        file_id: fileIdInput.value
      }

      // Send AJAX request
      fetch('/wp-json/dwnload/v1/user/' + email, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-WP-Nonce': nonce.value,
        },
        body: JSON.stringify(data)
      })
        .then(function (response) {
          if (!response.ok) {
            return response.json().then(function (errorData) {
              throw new Error(errorData.message || 'Failed')
            })
          }
          return response.json()
        })
        .then(function (result) {
          if (typeof result.success !== 'undefined' && result.success) {
            // Success.
            showMessage(notice, result.message || 'Thanks for subscribing!', 'success')
            emailInput.value = ''
          }

          showMessage(notice, result.message, 'warning')

          // Reset button after delay.
          setTimeout(function () {
            submitButton.disabled = false
            submitButton.textContent = 'Download'
          }, 3000)
        })
        .catch(function (error) {
          // Error.
          showMessage(notice, error.message || 'An error occurred. Please try again.', 'error')

          // Reset button.
          submitButton.disabled = false
          submitButton.textContent = 'Download'
        })
    })
  })

  function isValidEmail (email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
    return re.test(email)
  }

  function showMessage (notice, message, type) {
    const element = document.createElement('p')
    element.className = 'form-message ' + type
    element.textContent = message
    element.setAttribute('role', type === 'error' ? 'alert' : 'status')
    element.setAttribute('aria-live', 'polite')

    notice.appendChild(element)
    notice.style.display = 'block'

    // Remove message after 5 seconds for success
    if (type === 'success') {
      setTimeout(function () {
        element.remove()
      }, 5000)
    }
  }
})
