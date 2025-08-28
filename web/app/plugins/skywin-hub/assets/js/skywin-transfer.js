"use strict";
jQuery(document).ready(function($) {
	
	var search_member = function(term, callback) {
		jQuery
			.ajax({
				method: 'POST',
				dataType: 'json',
				url: ajax_get_skywin_accounts_params.ajax_url,
				data: {
					_ajax_nonce: ajax_get_skywin_accounts_params.nonce,
					action: ajax_get_skywin_accounts_params.action,
					terms: term,
				},
			})
	
			.done(function(response) {
				callback(response);
			})
	
			.fail(function(result) {
				console.log('Error', result);
			})
	
			.always(function(result) {});
	};
			
	jQuery('#transfer_account').autocomplete({
		delay: 800,
		minLength: 2,
		autoFocus: false,
		source: function (request, suggests) {
			
			if (!request.term.replace(/\s/g, '').length) {
				return suggests([]);
			}
			
			search_member(request.term, function(response){
				suggests(response.results);
			})
		},
		
		change: function(event, ui) {
			if (ui.item !== null) {
				jQuery('input[name="skywin_accountNo"]').val(ui.item.data.AccountNo);
			}
	
			if (ui.item === null) {
				jQuery('input[name="skywin_accountNo"]').val('');
				jQuery('input[name="transfer_account"]').val('');
			}
		},
		select: function(event, ui) {
			jQuery('input[name="skywin_accountNo"]').val(ui.item.data.AccountNo);
		},
		focus: function(event, ui) {
		},
		close: function(event, ui) {
		},
		response: function(event, ui) {
			if (!ui.content.length) {
				var noResult = { value: '', label: 'No results found' };
				ui.content.push(noResult);
			}
			
		},
	});
	
});