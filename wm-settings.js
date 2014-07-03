jQuery(document).ready(function ($) {
  'use strict';
  var tabs = $('.wm-settings-tab', 'form'),
    tabsHeader,
    tabsContent,
    active = 'wm-settings-tab-active',
    current = $('#wm-settings-current-tab');
  if (tabs.length) {
    tabsHeader = $('<div>').addClass('wm-settings-tabs-header').insertBefore('form .submit:first');
    tabsContent = $('<div>').addClass('wm-settings-tabs-content').insertAfter(tabsHeader);
    tabs.each(function(i, el) {
      var title = $(el).prev('h3').appendTo(tabsHeader),
        nextAll = $(el).nextAll(),
        tab = $('<div>').appendTo(tabsContent).hide();
      nextAll.each(function() {
        var tag = $(this).prop('tagName');
        if (tag === 'H3' || tag === 'INPUT') {
          return false;
        }
        $(this).appendTo(tab);
      });
      title.click(function(e) {
        e.preventDefault();
        if (!title.hasClass(active)) {
          current.val(i);
          $('.' + active, tabsContent).fadeOut('fast', function() {
            $('.' + active, 'form').removeClass(active);
            title.addClass(active);
            tab.fadeIn('fast').addClass(active);
          });
        }
      });
    });
    tabsHeader.children().eq(current.val()).addClass(active);
    tabsContent.children().eq(current.val()).show().addClass(active);
  }
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
      msg = $('<div>').addClass('settings-error').insertBefore(submit).hide(),
      displayErrors = function (errors) {
        var e, i;
        for (e in errors) {
          if (errors.hasOwnProperty(e)) {
            for (i = 0; i < errors[e].length; i += 1) {
              msg.append('<p>' + errors[e][i] + '</p>');
            }
          }
        }
      },
      action = {
        data: { action: submit.attr('id') },
        dataType: 'json',
        type: 'POST',
        url: ajax.url,
        beforeSend: function () {
          spinner.fadeIn('fast');
          submit.hide();
        },
        success: function (r) {
          if (typeof r === 'object' && r.hasOwnProperty('data')) {
            if (r.hasOwnProperty('success') && r.success) {
              if (r.data.hasOwnProperty('reload') && r.data.reload) {
                location.reload();
                return;
              }
              msg.addClass('updated').append('<p>' + String(r.data) + '</p>');
            } else {
              msg.addClass('error');
              if (r.data.hasOwnProperty('errors')) {
                displayErrors(r.data.errors);
              } else {
                msg.append('<p>' + r.data + '</p>');
              }
            }
          } else {
            msg.addClass('error').append('<p>' + String(r) + '</p>');
          }
          spinner.hide();
          submit.fadeIn('fast');
          msg.show('fast');
        },
        error: function (jqXHR, textStatus, errorThrown) {
          msg.addClass('error').append('<p>' + jqXHR.responseText + '</p>').show('fast');
          console.log(textStatus, jqXHR);
        }
      };
    submit.click(function (e) {
      e.preventDefault();
      msg.hide('fast', function () {
        msg.removeClass('error updated').empty();
        $.ajax(action);
      });
    });
  });
});
