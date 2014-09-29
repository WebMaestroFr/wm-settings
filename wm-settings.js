jQuery(document).ready(function ($) {
  'use strict';
  var page = $('input[name="option_page"]').val(),
    tabs = $('.wm-settings-tab', 'form'),
    tabsHeader,
    tabsContent,
    active = 'wm-settings-tab-active',
    current = parseInt(sessionStorage.getItem(page + '_current_tab'), 10) || 0;
  if (tabs.length) {
    tabsHeader = $('<div>').addClass('wm-settings-tabs-header').insertBefore('.submit:first');
    tabsContent = $('<div>').addClass('wm-settings-tabs-content').insertAfter(tabsHeader);
    tabs.each(function (i, el) {
      var title = $(el).prev('h3').appendTo(tabsHeader),
        nextAll = $(el).nextAll(),
        tab = $('<div>').appendTo(tabsContent).hide();
      nextAll.each(function () {
        var tag = $(this).prop('tagName');
        if (tag === 'H3' || tag === 'INPUT') {
          return false;
        }
        $(this).appendTo(tab);
      });
      title.click(function (e) {
        e.preventDefault();
        if (!title.hasClass(active)) {
          $('.' + active, tabsContent).fadeOut('fast', function () {
            $('.' + active, 'form').removeClass(active);
            title.addClass(active);
            tab.fadeIn('fast').addClass(active);
          });
          sessionStorage.setItem(page + '_current_tab', i);
        }
      });
      if (current === i) {
        title.addClass(active);
        tab.show().addClass(active);
      }
    });
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
      notice = $('<div>').addClass('settings-error').insertBefore(submit).hide(),
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
          var noticeClass = 'error',
            showNotice = function (msg) {
              notice.html('<p>' + String(msg) + '</p>').addClass(noticeClass).show();
            };
          if (typeof r === 'object') {
            if (r.hasOwnProperty('success') && r.success) {
              noticeClass = 'updated';
            }
            if (r.hasOwnProperty('data') && r.data) {
              if (typeof r.data === 'object') {
                if (r.data.hasOwnProperty('reload') && r.data.reload) {
                  document.location.reload();
                  return;
                }
                if (r.data.hasOwnProperty('message') && r.data.message) {
                  showNotice(r.data.message);
                }
              } else {
                showNotice(r.data);
              }
            }
          } else if (r) {
            showNotice(r);
          }
          spinner.hide();
          submit.fadeIn('fast');
          notice.show('fast');
        },
        error: function (jqXHR, textStatus, errorThrown) {
          notice.addClass('error').append('<p>' + jqXHR.responseText + '</p>').show('fast');
          console.log(textStatus, jqXHR, errorThrown);
        }
      };
    submit.click(function (e) {
      e.preventDefault();
      notice.hide('fast', function () {
        notice.removeClass('error updated').empty();
        $.ajax(action);
      });
    });
  });
});
