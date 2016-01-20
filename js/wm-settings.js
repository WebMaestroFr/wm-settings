jQuery(document).ready(function ($) {
    'use strict';

    var $page = $('.wm-settings-page');

    if ($page.hasClass('tabs')) {
        (function () {
            var sections = [];
            var $sectionsWrapper = $('.wm-settings-sections', $page);
            var $els = $sectionsWrapper.children();
            var $tabsWrapper = $('<h2>').addClass('nav-tab-wrapper wm-settings-tabs').appendTo($sectionsWrapper);
            var switchTab = function (e) {
                var $tab = $(this);
                e.preventDefault();
                if (!$tab.hasClass('nav-tab-active')) {
                    var target = $tab.attr('href');
                    $('.nav-tab-active', $tabsWrapper).removeClass('nav-tab-active');
                    $('.wm-settings-section', $sectionsWrapper).hide();
                    $(target, $sectionsWrapper).fadeIn('fast');
                    $tab.addClass('nav-tab-active');
                }
            };
            $els.each(function () {
                var $el = $(this);
                if ($el.is('h2')) {
                    var id = 'tab-' + sections.length;
                    var href = '#' + id;
                    var active = (window.location.hash === href);
                    var $section = $('<div>').attr({
                        id: id,
                        class: 'wm-settings-section'
                    }).appendTo($sectionsWrapper).toggle(active);
                    var title = $el.text();
                    $('<a>').text(title).attr({
                        href: href,
                        class: 'nav-tab'
                    }).appendTo($tabsWrapper).click(switchTab).toggleClass('nav-tab-active', active);
                    sections.push($section);
                    $el.remove();
                } else if (sections.length) {
                    $el.appendTo(sections[sections.length - 1]);
                }
            });
            if (!$('.nav-tab-active', $tabsWrapper).length) {
                $('.nav-tab', $tabsWrapper).first().addClass('nav-tab-active');
                $('.wm-settings-section', $sectionsWrapper).first().show();
            }
        }());
    }

    $('.wm-settings-media, .wm-settings-image').each(function () {
        var frame;
        var $input = $('.wm-settings-media-input', this);
        var $select = $('.wm-settings-media-select', this);
        var $remove = $('.wm-settings-media-remove', this).toggle(!!$input.val());
        var $preview = $('.wm-settings-media-preview', this).toggle(!!$input.val());
        var $image = $('.wm-settings-media-image', $preview);
        var $title = $('.wm-settings-media-title', $preview);
        $select.add($preview).click(function (e) {
            e.preventDefault();
            if (frame) {
                frame.open();
                return;
            }
            var data = $input.data('frame');
            frame = wp.media(data);
            frame.on('select', function () {
                var attachment = frame.state().get('selection').first().toJSON();
                $input.val(attachment.id).trigger('change');
                $image.attr({
                    src: attachment.sizes
                        ? attachment.sizes.full.url
                        : attachment.icon,
                    alt: attachment.title
                });
                $title.text(attachment.title);
                $preview.show();
                $remove.show();
            });
            frame.open();
        });
        $remove.click(function (e) {
            e.preventDefault();
            $input.val('').trigger('change');
            $preview.hide('fast');
            $remove.fadeOut('fast');
        });
    });

    $('.wm-settings-action').each(function () {
        var $button = $('.wm-settings-action-button', this);
        var $spinner = $('.wm-settings-action-spinner', this).hide();
        var $notice = $('.wm-settings-notice', this).hide();
        var displayNotice = function (msg, noticeClass) {
            $notice.html('<p>' + String(msg) + '</p>').addClass(noticeClass).show('fast');
        };
        $button.click(function (e) {
            e.preventDefault();
            $notice.hide('fast', function () {
                $notice.removeClass('error success').empty();
                $.ajax({
                    data: {
                        action: $button.data('action')
                    },
                    dataType: 'json',
                    type: 'post',
                    url: wmAjaxUrl,
                    beforeSend: function () {
                        $spinner.fadeIn('fast');
                        $button.hide();
                    },
                    success: function (response) {
                        var noticeClass = 'error';
                        if (typeof response === 'object') {
                            if (response.hasOwnProperty('success') && response.success) {
                                noticeClass = 'success';
                            }
                            if (response.hasOwnProperty('data') && response.data) {
                                if (typeof response.data === 'object') {
                                    if (response.data.hasOwnProperty('message') && response.data.message) {
                                        displayNotice(response.data.message, noticeClass);
                                    }
                                } else {
                                    displayNotice(response.data, noticeClass);
                                }
                            }
                        } else if (response) {
                            displayNotice(response);
                        }
                        $spinner.hide();
                        $button.fadeIn('fast');
                        window.console.log(response);
                    },
                    error: function (jqXHR, textStatus, errorThrown) {
                        displayNotice(jqXHR.responseText, 'error');
                        window.console.log(jqXHR, textStatus, errorThrown);
                    }
                });
            });
        });
    });

    $('.wm-settings-color').each(function () {
        var $input = $('.wm-settings-color-input', this);
        var options = $input.data('picker');
        options.change = function (e, ui) {
            e.preventDefault();
            var color = ui.color.toString();
            $input.val(color).trigger('change');
        };
        $input.wpColorPicker(options);
    });
});
