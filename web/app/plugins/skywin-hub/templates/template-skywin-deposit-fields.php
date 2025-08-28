
<div class="skywin-deposit-fields">
	<input autocomplete="false" autofill="off" name="hidden" type="text" style="display:none;" />
	<div>
		<label for="deposit_amount">
			<h3>
				<?php echo __('Amount', 'skywin-hub'); ?>
				(<?php echo get_woocommerce_currency_symbol(); ?>)
			</h3>
		</label>
	</div>
	<div>
		<input type="text" autocomplete="false" autofill="off" class="skywin-input input-text" name="deposit_amount"
			id="deposit_amount" placeholder="0" value="<?php echo $args['amount'] ?>" required />
	</div>
	<div>
		<label for="deposit_account">
			<h3>
				<?php echo __('Account', 'skywin-hub'); ?>
			</h3>
		</label>
	</div>
	<div>
		<input type="text" autocomplete="false" autofill="off" class="skywin-input input-text" name="skywin_account"
			id="skywin_account" placeholder="Search" value="<?php echo $args['account'] ?>" required />
	</div>
	<input type="hidden" id="skywin_accountNo" name="skywin_accountNo" value="<?php echo $args['accountNo']; ?>" />
	<input type="hidden" id="product_id" name="product_id" value="<?php echo $args['product']->get_id(); ?>" />
	<input type="hidden" id="nonce" name="nonce" value="<?php echo $args['nonce'] ?>" />
</div>
