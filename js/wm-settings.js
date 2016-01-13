jQuery(document).ready(function ($) {
    'use strict';

    $('.wm-settings-page').each(function () {
        var page = this;

        $('.wm-settings-tabs', page).each(function () {
            var $nav = $(this),
                $sections = $('.wm-settings-sections', page).children(),
                active = null,
                prepareTabs = function () {
                    var index = 0;
                    $sections.hide().each(function () {
                        var $el = $(this);
                        if ($el.is('h2')) {
                            index += 1;
                            var title = $el.text();
                            $('<a>').text(title).attr({
                                href: '.tab-' + index,
                                class: 'nav-tab'
                            }).appendTo($nav);
                            $el.remove();
                        } else {
                            $el.addClass('tab-' + index);
                        }
                    });
                },
                switchTab = function (e) {
                    var $tab = $(this),
                        target = $tab.attr('href');
                    e.preventDefault();
                    if (target !== active) {
                        $sections.hide()
                        $('.nav-tab-active', $nav).removeClass('nav-tab-active');
                        $tab.addClass('nav-tab-active');
                        active = target;
                        $sections.filter(target).fadeIn('fast');
                    }
                };
            $.when(prepareTabs()).done(function() {
                $('.nav-tab', $nav).each(function () {
                    var $tab = $(this),
                        target = $tab.attr('href'),
                        $section = $sections.filter(target);
                    if ($section.length) {
                        if (null === active) {
                            active = target;
                            $tab.addClass('nav-tab-active');
                            $section.show();
                        }
                        $tab.click(switchTab);
                    } else {
                        $tab.remove();
                    }
                });
                $nav.show();
            });
        });

        $('.wm-settings-media', page).each(function () {
            var frame,
                $input = $('input', this),
                $select = $('.wm-select-media', this),
                $remove = $('.wm-remove-media', this).toggle(!!$input.val()),
                $preview = $('.wm-preview-media', this),
                title = $select.attr('title'),
                text = $select.text(),
                type = $(this).data('type');
            $select.click(function (e) {
                e.preventDefault();
                if (frame) {
                    frame.open();
                    return;
                }
                var media = {
                    title: title,
                    button: { text: text },
                    multiple: false
                };
                if ( type === 'image' ) {
                    media.library = { type: 'image' };
                }
                frame = wp.media(media);
                frame.on('select', function () {
                    var attachment = frame.state().get('selection').first().toJSON();
                    $input.val(type === 'media' ? attachment.id : attachment.url);
                    $preview.attr({ src: attachment.sizes
                        ? attachment.sizes.full.url
                        : attachment.icon
                    }).show();
                    $remove.show();
                });
                frame.open();
            });
            $remove.click(function (e) {
                e.preventDefault();
                $input.val('');
                $preview.hide();
                $remove.hide();
            });
        });

        $('.wm-settings-action', page).each(function () {
            var $submit = $('[type="button"]', this),
                $spinner = $('<img>').attr({
                    src: ajax.spinner,
                    alt: 'loading'
                }).insertAfter($submit).hide(),
                $notice = $('<div>').insertBefore($submit).hide(),
                showNotice = function (msg, noticeClass) {
                    $notice.html('<p>' + String(msg) + '</p>').addClass(noticeClass).show('fast');
                },
                action = {
                    data: {
                        action: 'wm_settings',
                        name: $submit.data('action')
                    },
                    dataType: 'json',
                    type: 'post',
                    url: ajax.url,
                    beforeSend: function () {
                        $spinner.fadeIn('fast');
                        $submit.hide();
                    },
                    success: function (r) {
                        var noticeClass = 'error';
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
                                        showNotice(r.data.message, noticeClass);
                                    }
                                } else {
                                    showNotice(r.data, noticeClass);
                                }
                            }
                        } else if (r) {
                            showNotice(r);
                        }
                        $spinner.hide();
                        $submit.fadeIn('fast');
                        console.log(r);
                    },
                    error: function (jqXHR, textStatus, errorThrown) {
                        showNotice(jqXHR.responseText, 'error');
                        console.log(jqXHR, textStatus, errorThrown);
                    }
                };
            $submit.click(function (e) {
                e.preventDefault();
                $notice.hide('fast', function () {
                    $notice.removeClass('error updated').empty();
                    $.ajax(action);
                });
            });
        });

        $('.wm-settings-color', page).wpColorPicker();

    });
});
