<div class="skywin_hub-deposit-fields">
	<div>
		<label for="search_account">
			<h3>
				<?php echo __('Account', 'skywin-hub'); ?>
			</h3>
			<input type="text" class="skywin_hub-input skywin_hub-input-text" name="search_account" placeholder="Search"
			value="" autocomplete="off" required />
		</label>
	</div>
	<div class="add-to-cart-wrapper">
		<div class="quick-add-to-cart-wrapper">
			<div class="wp-block-button is-layout-flex wp-block-buttons-is-layout-flex">
				<?php foreach ($args['quickAmounts'] as $value): ?>
					<div class="wp-block-button">
						<button type="submit" name="add-to-cart" value="<?php echo esc_attr($args['product_id']) ?>"
							class="wp-element-button wp-block-button__link skywin_hub-button quick_add_to_cart_button"
							data-amount="<?php echo esc_attr($value) ?>"
							data-product_id="<?php echo esc_attr($args['product_id']) ?>" disabled>
							<?php echo esc_html($value) . ' ' . $args['currency']; ?>
						</button>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
		<div>
			<label for="amount">
				<h3>
					<?php echo __('Amount', 'skywin-hub'); ?>
					(<?php
					if (!empty($args['min_amount']) && !empty($args['max_amount'])) {
						echo $args['min_amount'] . '-' . $args['max_amount'];
					} elseif (!empty($args['min_amount'])) {
						echo 'min: ' . $args['min_amount'];
					} elseif (!empty($args['max_amount'])) {
						echo 'max: ' . $args['max_amount'];
					}
					echo $args['currency'];
					?>)
				</h3>
				<input type="text" class="skywin_hub-input skywin_hub-input-text" name="amount" placeholder="0"
				value="" autocomplete="off" required disabled />
			</label>
		</div>
		<div>
			<label for="remember-me" class="skywin_hub-checkbox">
				<h3>Remember me</h3>
				<input type="checkbox" id="remember-me" name="remember-me" class="" />
				<span class="skywin_hub-checkbox-box" aria-hidden="true"></span>
			</label>
		</div>
	</div>
	<input type="hidden" name="accountNo" value="" />
	<input type="hidden" name="emailAddress" value="" />
	<input type="hidden" name="product_id" value="<?php echo $args['product_id']; ?>" />
</div>