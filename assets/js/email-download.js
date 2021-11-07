/* global jQuery, emailDownload */
(function ($) {
  'use strict'

  $(document).ready(function () {
    const $notice = $('.EmailDownload__notice > p')
    $('form.EmailDownload__form').on('submit', function (e) {
      e.preventDefault()
      const $this = $(this)
      const $button = $this.find('button')

      $.ajax({
        method: 'POST',
        url: emailDownload.root + emailDownload.namespace + emailDownload.route + $('input.EmailDownload__input').val(),
        data: $(this).serialize(),
        beforeSend: function (xhr) {
          xhr.setRequestHeader('X-WP-Nonce', emailDownload.nonce)
          $button.attr('disabled', true)
        },
        success: function (response) {
          if (typeof response.success !== 'undefined' && response.success) {
            $this.parent().slideUp().remove()
            document.location = response.url
          }
          var text = typeof response.message !== 'undefined' ? response.message : emailDownload.failure
          $notice.text(text).parent().show()
          $button.attr('disabled', false)
        },
        fail: function (response) {
          alert('Unknown Error')
        }
      })
    })
  })
}(jQuery))
