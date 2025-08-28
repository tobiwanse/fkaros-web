jQuery(document).ready(function($) {
	var checkin = {
		
		init: function() {
			$('.tooltip').tooltip({
				tooltipClass: 'tooltip',
			});

			$(window).keydown(function(event){
				if(event.keyCode == 13) {
					event.preventDefault();
					return false;
				}
			});

			$('select[name="countryCode"]').on('change', function(event) {});

			$('input[name="repackDate"]').daterangepicker({
					singleDatePicker: true,
					showDropdowns: true,
					autoApply: true,
					autoUpdateInput: false,

					minDate: moment()
						.add(-2, 'years')
						.format('YYYY-MM-DD'),
					maxDate: moment()
						.add(2, 'years')
						.format('YYYY-MM-DD'),
					locale: {
						format: 'YYYY-MM-DD',
						cancelLabel: 'Clear',
					},
				},
				function(start, end, label) {}
			);
						
			$('input[name="repackDate"]').on('apply.daterangepicker', function(ev, picker) {

				$(this).val(picker.startDate.format('YYYY-MM-DD'));

			});
			
			$('input[name="birthday"]').daterangepicker({
					singleDatePicker: true,
					showDropdowns: true,
					autoApply: true,
					autoUpdateInput: false,
					maxDate: moment(),
					locale: {
						format: 'YYYY-MM-DD',
						cancelLabel: 'Clear',
					},
				},
				function(start, end, label) {}
			);
						
			$('input[name="birthday"]').on('apply.daterangepicker', function(ev, picker) {
			
				$(this).val(picker.startDate.format('YYYY-MM-DD'));
			
			});

			$('input[name="emailAddress"]').on('change', function(event){

				if ( this.value.length ) {
					checkin.get_skywin_account(this.value);
				}
				
			})
			
		},
		
		get_skywin_account: function( email ){
			
			var params = get_account_by_email_params;
			
			jQuery
				.ajax({
					method: 'POST',
					dataType: 'json',
					url: params.ajax_url,
					data: {
						nonce: params.nonce,
						action: params.action,
						email: email,
					},
				})
	
				.done(function(response) {
					
					if (response === false) {
						
						$('input[name="emailAddress"]').css("box-shadow", "0 0 0px 2px #ff0000 inset");
					
					} else {
						
						$('input[name="emailAddress"]').css("box-shadow", "none");
						
					}
										
				})
	
				.fail(function(result) {
					console.log('Error', result);
				})

				.always(function(result) {});

		},
		
	};
	
	checkin.init();
});
