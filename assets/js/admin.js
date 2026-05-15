/*!
 * Apqrinu Job Board - Admin script
 * - Media picker for the company logo metabox
 * - Color picker for the Settings page
 */
(function ($) {
	'use strict';

	$(function () {
		// ---- Color picker ----
		// wpColorPicker turns each input into a colored swatch button
		// that opens an Iris picker (HSL gradient + presets). The
		// `palettes` option populates the quick-pick row underneath.
		if ($.fn.wpColorPicker) {
			$('.apqrinu-color-field').each(function () {
				var $input = $(this);
				$input.wpColorPicker({
					palettes: [
						'#4f46e5',
						'#10b981',
						'#f59e0b',
						'#ef4444',
						'#0ea5e9',
						'#8b5cf6',
						'#1f2937',
						'#ffffff',
					],
					change: function (event, ui) {
						$input.val(ui.color.toString()).trigger('change');
					},
					clear: function () {
						$input.val('');
					},
				});
			});
		}

		// ---- Company logo media picker ----
		var frame;

		$(document).on('click', '.apqrinu-media-pick', function (e) {
			e.preventDefault();
			var wrap = $(this).closest('.apqrinu-media-control');
			var input = wrap.find('.apqrinu-media-id');

			frame = wp.media({
				title:
					(window.wp &&
						window.wp.i18n &&
						window.wp.i18n.__ &&
						window.wp.i18n.__('Choose Image', 'apqrinu-job-board')) ||
					'Choose Image',
				button: { text: 'Use this image' },
				library: { type: 'image' },
				multiple: false,
			});

			frame.on('select', function () {
				var attachment = frame
					.state()
					.get('selection')
					.first()
					.toJSON();
				input.val(attachment.id);

				wrap.find('img').remove();
				if (attachment.sizes && attachment.sizes.thumbnail) {
					wrap.prepend(
						'<img src="' +
							attachment.sizes.thumbnail.url +
							'" alt="" style="max-width:80px;height:auto;display:block;margin-bottom:6px;" />'
					);
				} else if (attachment.url) {
					wrap.prepend(
						'<img src="' +
							attachment.url +
							'" alt="" style="max-width:80px;height:auto;display:block;margin-bottom:6px;" />'
					);
				}
			});

			frame.open();
		});

		$(document).on('click', '.apqrinu-media-clear', function (e) {
			e.preventDefault();
			var wrap = $(this).closest('.apqrinu-media-control');
			wrap.find('.apqrinu-media-id').val('0');
			wrap.find('img').remove();
		});
	});
})(jQuery);
