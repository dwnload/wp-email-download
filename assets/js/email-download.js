/* global jQuery, emailDownload */
(function ($) {
    "use strict";

    $(document).ready(function () {
        var $notice = $('.EmailDownload__notice > p');
        $('form.EmailDownload__form').on('submit', function (e) {
            e.preventDefault();
            var $this = $(this),
                $button = $this.find('button');

            $.ajax({
                method: "POST",
                url: emailDownload.root + emailDownload.namespace + emailDownload.route + $('input.EmailDownload__input').val(),
                data: $(this).serialize(),
                beforeSend: function (xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', emailDownload.nonce);
                    $button.attr('disabled', true);
                },
                success: function (response) {
                    if (typeof response.success !== 'undefined' && response.success) {
                        alert(emailDownload.success);
                        // $this.slideUp().remove();
                        document.location = response.url;
                    }
                    var text = typeof response.message !== 'undefined' ? response.message : '';
                    $notice.text(text).parent().show();
                    $button.attr('disabled', false);
                },
                fail: function (response) {
                    console.log(response);
                    alert(emailDownload.failure);
                }
            });
        });
    });
}(jQuery));