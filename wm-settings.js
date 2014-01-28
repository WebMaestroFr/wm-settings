jQuery(document).ready(function($) {
	$('.wm-settings-media', 'form').each(function() {
		var frame,
			select = $('.wm-select-media', this),
			remove = $('.wm-remove-media', this),
			input = $('input', this),
			preview = $('img', this),
			title = select.attr('title'),
			text = select.text();
		if ( input.val() == 0 ) {
			preview = $('<img class="attachment-medium">');
			preview.prependTo(this).hide();
			remove.hide();
		}
		select.click(function(e) {
			e.preventDefault();
			if (frame) {
				frame.open();
				return;
			}
			frame = wp.media.frames.frame = wp.media({
				title: title,
				button: { text: text },
				multiple: false
			});
			frame.on('select', function() {
				var thumb;
				attachment = frame.state().get('selection').first().toJSON();
				input.val(attachment.id);
				thumb = attachment.sizes.medium ? attachment.sizes.medium : attachment.sizes.full;
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
		remove.click(function(e) {
			e.preventDefault();
			input.val('');
			preview.hide();
			remove.hide();
		});
	});
});