jQuery(document).ready(function($){
	'use strict';
	if(bt_edd_ajax.hasOwnProperty('delay')) {
		setTimeout(function() {
			if( bt_edd_ajax.tags !== null ) {
				var data = {
					'action'	 : 'apply_tags',
					'tags'		 : bt_edd_ajax.tags
				};
				$.post(bt_edd_ajax.ajaxurl, data);
			}
			if( bt_edd_ajax.remove !== null ) {
				var data = {
					'action'	 : 'remove_tags',
					'tags'		 : bt_edd_ajax.remove
				};
				$.post(bt_edd_ajax.ajaxurl, data);
			}
		}, bt_edd_ajax.delay);
	}

	$(document).on('mousedown', '[data-apply-tags]', function(e) {
		if( e.which <= 2 ) {
			var tags = $(this).attr('data-apply-tags');
			var data = {
				'action'	 : 'apply_tags',
				'tags'		 : tags.split(',')
			};
			$.post(bt_edd_ajax.ajaxurl, data);
		}
	});

	$(document).on('mousedown', '[data-remove-tags]', function(e) {
		if( e.which <= 2 ) {
			var tags = $(this).attr('data-remove-tags');
			var data = {
				'action'	 : 'remove_tags',
				'tags'		 : tags.split(',')
			};
			$.post(bt_edd_ajax.ajaxurl, data);
		}
	});
});