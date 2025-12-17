<form class="" action="" method="post" enctype="multipart/form-data">
<div class="skywin-transfer alignwide">
	<input autocomplete="false" autofill="off" name="hidden" type="text" style="display:none;">
	<input type="hidden" name="skywin-transfer-nonce" value="<?php echo wp_create_nonce('skywin-transfer-nonce') ?>"/>
	<label for="transfer_amount" class="">
		<?php echo __('Amount', 'woo-skywin-hub'); ?>
		(<?php echo get_woocommerce_currency_symbol(); ?>)
	</label>

	<input type="text" autocomplete="false" autofill="off" class="skywin-input" name="transfer_amount" id="transfer_amount" placeholder="0" value="<?php echo (isset($_POST['transfer_amount']) && !empty($_POST['transfer_amount'])) ? esc_attr($_POST['transfer_amount']) : ''; ?>" required />

	<label for="transfer_account" class="">
		<?php echo __('Account', 'woo-skywin-hub'); ?>
	</label>
	
	<span style="display:flex;">
	
		<input type="text" autocomplete="false" autofill="off" class="skywin-input" name="transfer_account" id="transfer_account" placeholder="Search" value="<?php echo (isset($_POST['transfer_account']) && !empty($_POST['transfer_account'])) ? esc_attr($_POST['transfer_account']) : ''; ?>" required />
	
	</span>	
	
	<input type="hidden" id="skywin_accountNo" name="skywin_accountNo" value="<?php echo (isset($_POST['skywin_accountNo']) && !empty($_POST['skywin_accountNo'])) ? esc_attr($_POST['skywin_accountNo']) : ''; ?>">	
	<div>
		<button type="submit" name="skywin-transfer" value="submit"><?php echo __('Transfer', 'wc-skywin-hub') ?></button>
	</div>
</div>
</form>