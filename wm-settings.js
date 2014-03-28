jQuery(document).ready(function ($) {
  'use strict';
  $('.wm-settings-media', 'form').each(function () {
    var frame,
      select = $('.wm-select-media', this),
      remove = $('.wm-remove-media', this),
      input = $('input', this),
      preview = $('img', this),
      title = select.attr('title'),
      text = select.text();
    if (input.val() < 1) {
      preview = $('<img class="attachment-medium">');
      preview.prependTo(this).hide();
      remove.hide();
    }
    select.click(function (e) {
      e.preventDefault();
      if (frame) {
        frame.open();
        return;
      }
      frame = wp.media({
        title: title,
        button: { text: text },
        multiple: false
      });
      frame.on('select', function () {
        var attachment = frame.state().get('selection').first().toJSON(),
          thumb;
        input.val(attachment.id);
        thumb = attachment.sizes.medium || attachment.sizes.full;
        preview.attr({
          src: thumb.url,
          width: thumb.width,
          height: thumb.height
        });
        preview.show();
        remove.show();
      });
      frame.open();
    });
    remove.click(function (e) {
      e.preventDefault();
      input.val('');
      preview.hide();
      remove.hide();
    });
  });
  $('.wm-settings-action', 'form').each(function () {
    var submit = $('[type="button"]', this),
      spinner = $('<img>').attr({
        src: ajax.spinner,
        alt: 'loading'
      }).insertAfter(submit).hide(),
      messages = $('<div>').addClass('settings-error').insertBefore(submit).hide();
    submit.click(function (e) {
      e.preventDefault();
      messages.hide('fast');
      $.ajax({
        data: { action: submit.attr('id') },
        dataType: 'json',
        type: 'POST',
        url: ajax.url,
        beforeSend: function () {
          spinner.fadeIn('fast');
          submit.hide();
        },
        success: function (r) {
          var error, i;
          spinner.hide();
          submit.fadeIn('fast');
          if (typeof r === 'object' && r.hasOwnProperty('data')) {
            messages.removeClass('error updated').empty();
            if (r.hasOwnProperty('success') && r.success) {
              messages.addClass('updated').append('<p>' + r.data + '</p>');
            } else {
              messages.addClass('error');
              if (r.data.hasOwnProperty('errors')) {
                for (error in r.data.errors) {
                  if (r.data.errors.hasOwnProperty(error)) {
                    for (i = 0; i < r.data.errors[error].length; i += 1) {
                      messages.append('<p>' + r.data.errors[error][i] + '</p>');
                    }
                  }
                }
              }
            }
            messages.show('fast');
          }
        },
        error: function (jqXHR, textStatus, errorThrown) {
          console.log(jqXHR, textStatus, errorThrown);
        }
      });
    });
  });
});
