<?php defined( 'ABSPATH' ) || exit; ?>
	<style>
		body{
			font-family: "New Times Roman";
		}
		.header{
			font-size:12px;
			width:100%;
			padding: 20px 20px;
			margin:20px 0px;
		}
		.intro{
			width:80%;
			padding: 20px 0px;
		}
		.intro_fat{
			font-size:18px;
			font-weight: bold;
		}
		.intro_body{
			padding-top: 20px;
		}

		.info{
			width:60%;
			font-size: 14px;
			border: 1px solid black;
			margin: 20px 0px;
		}
		.info td{
			padding: 10px 10px 10px 10px;
		}
		.devider_wrapper{
			width:80%;
			padding: 20px 0px;		
		}
		.devider{
			border-style: dashed;
			width: 80%;
		}
		.product_info{
			
			padding: 20px 0px;
		}
		.product_image{
			padding-bottom: 20px;
		}
		.gc_label{
			font-size:14px;
		}
		.gc_value{
			font-size:16px;
			font-weight: bold;
		}
		.expire_date{
			font-size:14px;
			font-style: italic;
		}
		.qr_code{
		}
		.footer{
			width:100%;
			padding: 20px 0px 0px 0px;
		}
		
		@media only screen and (max-width: 768px){
			table{
				width:98% !important;
			}
		}
		@media print{
			table{
				width:100% !important;
			}
		}
		
	</style>
	<table class="header" border="0" cellpadding="0" cellspacing="0" width="100%">
		<tr>
			<td align="right">
				<?php esc_html_e("Office: +46 8 82 63 36") ?><br>
				<?php esc_html_e("Mobile: +46 739 71 79 80") ?><br>
				<?php esc_html_e("Web: www.fallskarmscenter.se") ?><br>
			</td>
		</tr>
	</table>
	<table class="intro" border="0" cellpadding="0" cellspacing="0" width="100%">
		<tr>
			<td class="intro_fat" align="left">
				<p><?php esc_html_e("Tack för din beställning!") ?></p>
			</td>
		</tr>
		<tr>
			<td class="intro_body" align="left">
				<p><?php esc_html_e("Dina/ditt presentkort finns bifogat som PDF.") ?></p>
				<p><?php esc_html_e("Med vänliga hälsningar Stockholm/Västerås Fallskärmscenter!") ?></p>
			</td>
		</tr>
	</table>
	<br><br>
