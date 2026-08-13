<?php

/**
 * Cart Page
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/cart/cart.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 11.0.0
 */

defined('ABSPATH') || exit; ?>

<div class="classic-cart">

	<?php do_action('woocommerce_before_cart'); ?>


	<form class="woocommerce-cart-form" action="<?php echo esc_url(wc_get_cart_url()); ?>" method="post">

		<div class="cart-table-wrapper">
			<?php do_action('woocommerce_before_cart_table'); ?>
			<table class="shop_table shop_table_responsive cart woocommerce-cart-form__contents" cellspacing="0">
				<?php /* We remove the thead entirely!
				<thead>
					<tr>
						<th class="product-remove"><span class="screen-reader-text"><?php esc_html_e('Remove item', 'woocommerce'); ?></span></th>
						<th class="product-thumbnail"><span class="screen-reader-text"><?php esc_html_e('Thumbnail image', 'woocommerce'); ?></span></th>
						<th scope="col" class="product-name"><?php esc_html_e('Product', 'woocommerce'); ?></th>
						<th scope="col" class="product-price"><?php esc_html_e('Price', 'woocommerce'); ?></th>
						<th scope="col" class="product-quantity"><?php esc_html_e('Quantity', 'woocommerce'); ?></th>
						<th scope="col" class="product-subtotal"><?php esc_html_e('Subtotal', 'woocommerce'); ?></th>
					</tr>
				</thead>
				*/ ?>
				<tbody>
					<?php do_action('woocommerce_before_cart_contents'); ?>

					<?php
					foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
						$_product   = apply_filters('woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key);
						$product_id = apply_filters('woocommerce_cart_item_product_id', $cart_item['product_id'], $cart_item, $cart_item_key);
						/**
						 * Filter the product name.
						 *
						 * @since 2.1.0
						 * @param string $product_name Name of the product in the cart.
						 * @param array $cart_item The product in the cart.
						 * @param string $cart_item_key Key for the product in the cart.
						 */
						$product_name = apply_filters('woocommerce_cart_item_name', $_product->get_name(), $cart_item, $cart_item_key);

						if ($_product instanceof WC_Product && $_product->exists() && $cart_item['quantity'] > 0 && apply_filters('woocommerce_cart_item_visible', true, $cart_item, $cart_item_key)) {
							$product_permalink = apply_filters('woocommerce_cart_item_permalink', $_product->is_visible() ? $_product->get_permalink($cart_item) : '', $cart_item, $cart_item_key);
					?>
							<tr class="woocommerce-cart-form__cart-item <?php echo esc_attr(apply_filters('woocommerce_cart_item_class', 'cart_item', $cart_item, $cart_item_key)); ?>">

								<td class="cart-image-wrapper" data-title="<?php esc_attr_e('Product Image', 'woocommerce'); ?>">
									<div class="product-thumbnail">
										<?php
										/**
										 * Filter the product thumbnail displayed in the WooCommerce cart.
										 *
										 * This filter allows developers to customize the HTML output of the product
										 * thumbnail. It passes the product image along with cart item data
										 * for potential modifications before being displayed in the cart.
										 *
										 * @param string $thumbnail     The HTML for the product image.
										 * @param array  $cart_item     The cart item data.
										 * @param string $cart_item_key Unique key for the cart item.
										 *
										 * @since 2.1.0
										 */
										$thumbnail = apply_filters('woocommerce_cart_item_thumbnail', $_product->get_image(), $cart_item, $cart_item_key);

										if (! $product_permalink) {
											echo $thumbnail; // PHPCS: XSS ok.
										} else {
											printf('<a href="%s">%s</a>', esc_url($product_permalink), $thumbnail); // PHPCS: XSS ok.
										}
										?>
									</div>
								</td>
								<td class="cart-product-wrapper" data-title="<?php esc_attr_e('Product Detail', 'woocommerce'); ?>">
									<div class="product-name">
										<?php
										// Get the Base Name only (e.g., "Album")
										// If it's a variation, get_title() stops the dash from appearing
										$display_name = $_product->is_type('variation') ? $_product->get_title() : $_product->get_name();

										if (! $product_permalink) {
											echo '<span class="cart-product-title">' . esc_html($display_name) . '</span>';
										} else {
											echo sprintf(
												'<a href="%s" class="cart-product-title">%s</a>',
												esc_url($product_permalink),
												esc_html($display_name)
											);
										}

										// Variations (Your custom output)
										if (isset($cart_item['variation']) && ! empty($cart_item['variation'])) {
											$variation_values = [];
											foreach ($cart_item['variation'] as $value) {
												if ($value) {
													$variation_values[] = esc_html($value);
												}
											}

											if (! empty($variation_values)) {
												echo '<div class="cart-item-variations">' . implode(', ', $variation_values) . '</div>';
											}
										}
										// Backorder notification.
										if ($_product->backorders_require_notification() && $_product->is_on_backorder($cart_item['quantity'])) {
											echo wp_kses_post(apply_filters('woocommerce_cart_item_backorder_notification', '<p class="backorder_notification">' . esc_html__('Available on backorder', 'woocommerce') . '</p>', $product_id));
										}

										do_action('woocommerce_after_cart_item_name', $cart_item, $cart_item_key);
										?>
									</div>
									<div class="product-remove">
										<?php
										echo apply_filters( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
											'woocommerce_cart_item_remove_link',
											sprintf(
												'<a role="button" href="%s" class="remove" aria-label="%s" data-product_id="%s" data-product_sku="%s"><svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 256 256" aria-hidden="true"><path d="M205.66,194.34a8,8,0,0,1-11.32,11.32L128,139.31,61.66,205.66a8,8,0,0,1-11.32-11.32L116.69,128,50.34,61.66A8,8,0,0,1,61.66,50.34L128,116.69l66.34-66.35a8,8,0,0,1,11.32,11.32L139.31,128Z"></path></svg></a>',
												esc_url(wc_get_cart_remove_url($cart_item_key)),
												/* translators: %s is the product name */
												esc_attr(sprintf(__('Remove %s from cart', 'woocommerce'), wp_strip_all_tags($product_name))),
												esc_attr($product_id),
												esc_attr($_product->get_sku())
											),
											$cart_item_key
										);
										?>
									</div>
									<div class="product-stock-status">
										<?php
											$stock_status = $_product->get_stock_status();
											$stock_text   = array(
												'instock'     => __( 'In Stock', 'shopchop' ),
												'outofstock'  => __( 'Out of Stock', 'shopchop' ),
												'pre_order'   => __( 'Pre-Order', 'shopchop' ),
												'coming_soon' => __( 'Coming Soon', 'shopchop' ),
											);

											if ( isset( $stock_text[ $stock_status ] ) ) {
												$qty        = $_product->managing_stock() ? $_product->get_stock_quantity() : null;
												$qty_suffix = '';

												if ( null !== $qty ) {
													$qty_suffix = ' &middot; ' . sprintf(
														/* translators: %d: remaining stock quantity. */
														esc_html( _n( '%d item left', '%d items left', $qty, 'shopchop' ) ),
														$qty
													);
												}

												printf(
													'<div class="cart-item-stock"><span class="cart-stock %s">%s</span>%s</div>',
													esc_attr( str_replace( '_', '-', $stock_status ) ),
													esc_html( $stock_text[ $stock_status ] ),
													wp_kses( $qty_suffix, array() )
												);
											}
										?>
									</div>
									<div class="product-quantity">
										<?php
										if ($_product->is_sold_individually()) {
											$min_quantity = 1;
											$max_quantity = 1;
										} else {
											$min_quantity = 0;
											$max_quantity = $_product->get_max_purchase_quantity();
										}

										$product_quantity = woocommerce_quantity_input(
											array(
												'input_name'   => "cart[{$cart_item_key}][qty]",
												'input_value'  => $cart_item['quantity'],
												'max_value'    => $max_quantity,
												'min_value'    => $min_quantity,
												'product_name' => $product_name,
											),
											$_product,
											false
										);

										echo apply_filters('woocommerce_cart_item_quantity', $product_quantity, $cart_item_key, $cart_item); // PHPCS: XSS ok.
										?>
									</div>
									<div class="product-price">
										<?php
										echo apply_filters('woocommerce_cart_item_price', WC()->cart->get_product_price($_product), $cart_item, $cart_item_key); // PHPCS: XSS ok.
										echo ' ' . esc_html__( 'each', 'shopchop' ); ?>
									</div>
									<div class="product-subtotal">
										<?php
										echo apply_filters('woocommerce_cart_item_subtotal', WC()->cart->get_product_subtotal($_product, $cart_item['quantity']), $cart_item, $cart_item_key); // PHPCS: XSS ok.
										?>
									</div>
								</td>
							</tr>
					<?php
						}
					}
					?>
				</tbody>
			</table>
			<?php do_action('woocommerce_after_cart_table'); ?>
		</div>

		<?php do_action('woocommerce_cart_contents'); ?>
		<?php /* We move the coupon field to new wrapper, outside table */ ?>
		<div class="cart-coupon-wrapper">
			<?php if (wc_coupons_enabled()) { ?>
				<div class="coupon">
					<label for="coupon_code" class="screen-reader-text"><?php esc_html_e('Coupon:', 'woocommerce'); ?></label> <input type="text" name="coupon_code" class="input-text" id="coupon_code" value="" placeholder="<?php esc_attr_e('Coupon code', 'woocommerce'); ?>" /> <button type="submit" class="button<?php echo esc_attr(wc_wp_theme_get_element_class_name('button') ? ' ' . wc_wp_theme_get_element_class_name('button') : ''); ?>" name="apply_coupon" value="<?php esc_attr_e('Apply coupon', 'woocommerce'); ?>"><?php esc_html_e('Apply coupon', 'woocommerce'); ?></button>
					<?php do_action('woocommerce_cart_coupon'); ?>
				</div>
			<?php } ?>

			<button type="submit" class="button<?php echo esc_attr(wc_wp_theme_get_element_class_name('button') ? ' ' . wc_wp_theme_get_element_class_name('button') : ''); ?>" name="update_cart" value="<?php esc_attr_e('Update cart', 'woocommerce'); ?>"><?php esc_html_e('Update cart', 'woocommerce'); ?></button>

			<?php do_action('woocommerce_cart_actions'); ?>

			<?php wp_nonce_field('woocommerce-cart', 'woocommerce-cart-nonce'); ?>
		</div>
		<?php do_action('woocommerce_after_cart_contents'); ?>
	</form>

	<?php do_action('woocommerce_before_cart_collaterals'); ?>

	<div class="cart-collaterals">
		<?php
		/**
		 * Cart collaterals hook.
		 *
		 * @hooked woocommerce_cross_sell_display
		 * @hooked woocommerce_cart_totals - 10
		 */
		do_action('woocommerce_cart_collaterals');
		?>
	</div>

	<?php do_action('woocommerce_after_cart'); ?>

</div>