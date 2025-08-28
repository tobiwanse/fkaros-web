"use strict";
jQuery(document).ready(function () {
	var fetchData = function(params, callback) {
	jQuery
		.ajax({
			method: 'POST',
			dataType: 'json',
			url: params.ajax_url,
			data: {
				_ajax_nonce: params._ajax_nonce,
				action: params.action,
			},
		})
		.done(function(response) {
			callback(response);
		})
		.fail(function(result) {
			console.log(params.action  + ' Not connected');
			console.log('Error', result);
		})
		.always( function(result) {} );
	};
	fetchData( ajax_get_skywin_api_status_params, function(response){
		console.log(`${ajax_get_skywin_api_status_params.action} ${response}`);
	});
	fetchData( ajax_get_skywin_db_status_params, function(response){
		console.log(`${ajax_get_skywin_db_status_params.action} ${response}`);
	});
});