<?php

/**
 * Pay for order form
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/checkout/form-pay.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 10.9.0
 */

defined('ABSPATH') || exit;

$totals = $order->get_order_item_totals(); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
?>
<form class="order-pay-form" id="order_review" method="post">

	<table class="shop_table">
		<?php /*
		<thead>
			<tr>
				<th class="product-name"><?php esc_html_e( 'Product', 'woocommerce' ); ?></th>
				<th class="product-quantity"><?php esc_html_e( 'Qty', 'woocommerce' ); ?></th>
				<th class="product-total"><?php esc_html_e( 'Totals', 'woocommerce' ); ?></th>
			</tr>
		</thead> */ ?>
		<tbody>
			<?php if (count($order->get_items()) > 0) : ?>
				<?php
				foreach ($order->get_items() as $item_id => $item) :
					// Get the product object from the order item
					$_product = $item->get_product();

					if (! apply_filters('woocommerce_order_item_visible', true, $item)) {
						continue;
					}
				?>
					<tr class="<?php echo esc_attr(apply_filters('woocommerce_order_item_class', 'order_item', $item, $order)); ?>">
						<td class="product-name">
							<div class="flex items-center gap-4">
								<div class="shopchop-item-image shrink-0">
									<?php
									// Display image if product exists
									echo $_product ? $_product->get_image(array(96, 96), array('class' => 'rounded-lg object-cover')) : '';
									?>
								</div>

								<div class="shopchop-item-content">
									<?php
									// Handle naming logic (Parent name for variations)
									$base_name = $item->get_name();
									if ($_product && $_product->is_type('variation')) {
										$parent_product = wc_get_product($_product->get_parent_id());
										$base_name = $parent_product->get_name();
									}

									// Render Name
									echo wp_kses_post(apply_filters('woocommerce_order_item_name', '<span class="item-title font-bold block">' . $base_name . '</span>', $item, false));

									// Render Variation Meta (attributes like Color/Size)
									if ($_product && $_product->is_type('variation')) {
										$variation_list = wc_get_formatted_variation($_product->get_variation_attributes(), true);
										echo '<span class="variation-meta text-sm text-gray-500">' . esc_html($variation_list) . '</span>';
									}

									// Item Meta Data (standard WC data/custom fields)
									do_action('woocommerce_order_item_meta_start', $item_id, $item, $order, false);
									wc_display_item_meta($item);
									do_action('woocommerce_order_item_meta_end', $item_id, $item, $order, false);

									// Quantity Display
									echo apply_filters('woocommerce_order_item_quantity_html', ' <div class="shopchop-item-qty text-sm text-gray-600">' . sprintf('&times; %s', esc_html($item->get_quantity())) . '</div>', $item);
									?>
								</div>
							</div>
						</td>

						<td class="product-total text-right align-top pt-4">
							<?php echo $order->get_formatted_line_subtotal($item); ?>
						</td>
					</tr>
				<?php endforeach; ?>
			<?php endif; ?>
		</tbody>
		<tfoot>
			<?php if ($totals) : ?>
				<?php foreach ($totals as $total) : ?>
					<tr>
						<th scope="row" colspan="2"><?php echo $total['label']; ?></th><?php // @codingStandardsIgnoreLine 
																						?>
						<td class="product-total"><?php echo $total['value']; ?></td><?php // @codingStandardsIgnoreLine 
																						?>
					</tr>
				<?php endforeach; ?>
			<?php endif; ?>
		</tfoot>
	</table>

	<?php
	/**
	 * Triggered from within the checkout/form-pay.php template, immediately before the payment section.
	 *
	 * @since 8.2.0
	 */
	do_action('woocommerce_pay_order_before_payment');
	?>

	<div id="payment">
		<?php if ($order->needs_payment()) : ?>
			<ul class="wc_payment_methods payment_methods methods" aria-label="<?php esc_attr_e( 'Payment methods', 'woocommerce' ); ?>">
				<?php
				if (! empty($available_gateways)) {
					foreach ($available_gateways as $gateway) {
						wc_get_template('checkout/payment-method.php', array('gateway' => $gateway));
					}
				} else {
					echo '<li>';
					wc_print_notice(apply_filters('woocommerce_no_available_payment_methods_message', esc_html__('Sorry, it seems that there are no available payment methods for your location. Please contact us if you require assistance or wish to make alternate arrangements.', 'woocommerce')), 'notice'); // phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment
					echo '</li>';
				}
				?>
			</ul>
		<?php endif; ?>
		<div class="form-row">
			<input type="hidden" name="woocommerce_pay" value="1" />

			<?php wc_get_template('checkout/terms.php'); ?>

			<?php do_action('woocommerce_pay_order_before_submit'); ?>

			<?php echo apply_filters('woocommerce_pay_order_button_html', '<button type="submit" class="button alt' . esc_attr(wc_wp_theme_get_element_class_name('button') ? ' ' . wc_wp_theme_get_element_class_name('button') : '') . '" id="place_order" value="' . esc_attr($order_button_text) . '" data-value="' . esc_attr($order_button_text) . '">' . esc_html($order_button_text) . '</button>'); // @codingStandardsIgnoreLine 
			?>

			<?php do_action('woocommerce_pay_order_after_submit'); ?>

			<?php wp_nonce_field('woocommerce-pay', 'woocommerce-pay-nonce'); ?>
		</div>
	</div>
</form>