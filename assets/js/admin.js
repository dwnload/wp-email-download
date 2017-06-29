jQuery(function ($) {

  $.ajax({
    method: 'GET',
    url: EmailDownload.api.url,
    beforeSend: function (xhr) {
      xhr.setRequestHeader('X-WP-Nonce', EmailDownload.api.nonce);
    }
  }).then(function (r) {
    if (r.hasOwnProperty('industry')) {
      $('#industry').val(r.industry);
    }

    if (r.hasOwnProperty('amount')) {
      $('#amount').val(r.amount);
    }
  });

  $('#apex-form').on('submit', function (e) {
    e.preventDefault();
    var data = {
      amount: $('#amount').val(),
      industry: $('#industry').val()
    };

    $.ajax({
      method: 'POST',
      url: EmailDownload.api.url,
      beforeSend: function (xhr) {
        xhr.setRequestHeader('X-WP-Nonce', EmailDownload.api.nonce);
      },
      data: data
    }).then(function (r) {
      $('#feedback').html('<p>' + EmailDownload.strings.saved + '</p>');
    }).error(function (r) {
      var message = EmailDownload.strings.error;
      if (r.hasOwnProperty('message')) {
        message = r.message;
      }
      $('#feedback').html('<p>' + message + '</p>');

    })
  })
});