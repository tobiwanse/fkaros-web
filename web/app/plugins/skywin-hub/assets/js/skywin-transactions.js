"use strict";
jQuery(document).ready(function($) {
	
	startDate = moment(startDate);
	endDate = moment(endDate);

	var paged = 1;

	var order = 'desc';

	var orderby = 'LastUpd';

	var list_table = function() {
		var url = ajax_list_table_params.ajax_url;
				
		var data = {
			_ajax_nonce: ajax_list_table_params.nonce,

			action: ajax_list_table_params.action,
						
			startDate: startDate.format('YYYY-MM-DD'),

			endDate: endDate.format('YYYY-MM-DD'),

			paged: paged,

			order: order,

			orderby: orderby,
		};
				
		jQuery
			.ajax({ method: 'POST', url: url, data: data })

			.done(function(response) {

				var parser = new DOMParser();

				var doc = parser.parseFromString(response.html, 'text/html');

				var list_table = doc.getElementById('list-table');

				var tablenav_top = doc.getElementById('tablenav-top');

				var tablenav_bottom = doc.getElementById('tablenav-bottom');
				
				if (list_table !== null) {
					jQuery('#list-table').html(list_table.innerHTML);
				}

				if (tablenav_top !== null) {
					jQuery('#tablenav-top').html(tablenav_top.innerHTML);
				} else {
					jQuery('#tablenav-top').html('');
				}

				if (tablenav_bottom !== null) {
					jQuery('#tablenav-bottom').html(tablenav_bottom.innerHTML);
				} else {
					jQuery('#tablenav-bottom').html('');
				}
			})

			.fail(function(result) {
				console.log('Error', result);
			})

			.always(function(result) {
				console.log('always')
			});
	};

	jQuery(document).on('click', '.tablenav-pages a', function(event) {
		event.preventDefault();

		const q = this.search.substring(1);

		paged = __query(q, 'paged') || '';

		list_table();
	});

	jQuery(document).on('click', '#list-table th.sortable a', function(event) {
		event.preventDefault();
		
		const q = this.search.substring(1);

		paged = 1;
		order = __query(q, 'order') || '';
		orderby = __query(q, 'orderby') || '';

		list_table();
	});

	var cb = function(startDate, endDate) {
		
		var html = startDate.format('MMMM D, YYYY') + ' - ' + endDate.format('MMMM D, YYYY');
	
		jQuery(document).find('#reportrange span').html(html);
	
	};

	jQuery(document)
		.find('#reportrange')
		.daterangepicker(
			{
				opens: 'left',
				autoApply: true,
				startDate: startDate,
				endDate: endDate,
				ranges: {
					Today: [moment(), moment()],
					Yesterday: [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
					'Last 7 Days': [moment().subtract(6, 'days'), moment()],
					'Last 30 Days': [moment().subtract(29, 'days'), moment()],
					'This Month': [moment().startOf('month'), moment().endOf('month')],
					'Last Month': [
						moment()
							.subtract(1, 'month')
							.startOf('month'),
						moment()
							.subtract(1, 'month')
							.endOf('month'),
					],
					'This Year': [moment().startOf('year'), moment().endOf('year')],
					'Last Year': [
						moment()
							.subtract(1, 'years')
							.startOf('year'),
						moment()
							.subtract(1, 'years')
							.endOf('year'),
					],
				},
			},
			cb	
		);

	cb(startDate, endDate);

	jQuery(document)
		
		.find('#reportrange')
		
		.on('apply.daterangepicker', function(event, picker) {
			
			paged = 1;
		
			startDate = picker.startDate;
		
			endDate = picker.endDate;

			list_table();
		
		});

	var __query = function(query, variable) {
		var vars = query.split('&');
		for (var i = 0; i < vars.length; i++) {
			var pair = vars[i].split('=');
			if (pair[0] == variable) return pair[1];
		}
		return false;
	};
});
