/* global jQuery, emailDownload */
(function ($) {
    "use strict";

    $(document).ready(function () {
        $('form.EmailDownload__form').on('submit', function (e) {
            e.preventDefault();

            $.ajax({
                method: "POST",
                url: emailDownload.root + emailDownload.namespace +
                emailDownload.route + $('input.EmailDownload__input').val(),
                data: $(this).serialize(),
                beforeSend: function (xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', emailDownload.nonce);
                },
                success: function (response) {
                    console.log(response);
                    alert(emailDownload.success);
                },
                fail: function (response) {
                    console.log(response);
                    alert(emailDownload.failure);
                }
            });
        });
    });
}(jQuery));