(function($){
	var file_frame;
	
	jQuery('.media-uploader-button').click(function(e) {
		e.preventDefault();

		if ( file_frame ) {
			file_frame.open();
			return;
		}

		file_frame = wp.media.frames.file_frame = wp.media({
			title: 'Add Icon',
			button: {
				text: 'Select icon',
			},
			multiple: false
		});

		file_frame.on( 'select', function() {
			attachment = file_frame.state().get('selection').first().toJSON();
			console.log(attachment);
			if ( attachment ) {

				var url;

				if ( attachment.sizes.cptg_icon ) {
					url = attachment.sizes.cptg_icon.url;
				} else {
					url = attachment.url;
				}
				jQuery('#cptg_icon_url').val( url );
				jQuery('.current-cptg-icon').html('<img src="'+url+'" height="16" width="16" />');
				jQuery('.remove-cptg-icon').show();
				jQuery('.media-uploader-button').html('Edit icon');

			}
		});

		file_frame.open();
		return false;
	});

	jQuery('.remove-cptg-icon').click(function(e) {
		e.preventDefault();
		jQuery('#cptg_icon_url').val('');
		jQuery('.current-cptg-icon').html('');
		jQuery('.remove-cptg-icon').hide();
		jQuery('.media-uploader-button').html('Add icon');
	});
})(jQuery);
