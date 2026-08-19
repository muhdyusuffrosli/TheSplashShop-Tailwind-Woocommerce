<?php

/**
 * Template part for displaying the footer content
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/
 *
 * @package ShopChop
 */

// Check for Checkout, Order Received, Login/Register, and Lost Password
$is_minimal_page = is_checkout() ||
	is_wc_endpoint_url('order-received') ||
	(is_account_page() && !is_user_logged_in()) ||
	is_lost_password_page(); // Handles the reset password screen
?>

<footer id="colophon" class="<?php echo $is_minimal_page ? 'shopchop-footer-minimal' : 'shopchop-footer-normal'; ?>">
	<?php if ($is_minimal_page) : ?>
		<div class="text-center text-sm container mx-auto px-6 md:px-10 lg:px-16 pt-4 pb-8">
			&copy; <?php echo date("Y"); ?> <a class="underline! transition-all hover:no-underline! active:no-underline! focus:no-underline! text-primary-900 font-semibold" href="<?php echo esc_url(home_url('/')); ?>" rel="home"><?php bloginfo('name'); ?></a>. All Rights Reserved.<br>
			Website developed by <a class="underline! transition-all hover:no-underline! active:no-underline! focus:no-underline! text-primary-900 font-semibold" href="https://usoppii.my/" target="_blank" rel="noreferrer">Usoppii.my</a>.
		</div>
	<?php else : ?>
		<div class="container mx-auto px-6 md:px-10 lg:px-16 py-12">
			<div class="grid grid-cols-2 lg:grid-cols-4 gap-8 lg:gap-12">

				<div class="col-span-full lg:col-span-2">
					<div class="footer-brand">

						<?php 
						if (has_custom_logo()) {
							$custom_logo_id = get_theme_mod('custom_logo');
							$logo = wp_get_attachment_image_src($custom_logo_id, 'full');
							$logo_src = $logo[0];
						} else {
							$logo_src = get_stylesheet_directory_uri() . '/assets/images/logo.png';
						}
						?>

						<a href="<?php echo esc_url(home_url('/')); ?>" class="block">
							<img
								src="<?php echo esc_url($logo_src); ?>"
								alt="<?php bloginfo('name'); ?>"
								title="<?php bloginfo('name'); ?>"
								class="h-20 w-auto drop-shadow-background drop-shadow-sm mb-4">
						</a>

						<?php

						$shopchop_description = get_bloginfo('description', 'display');
						if ($shopchop_description || is_customize_preview()) :
						?>
							<p><?php echo esc_html( $shopchop_description ); ?></p>
						<?php endif; ?>
					</div>
					<?php if (is_active_sidebar('footer-content-0')) : ?>
						<?php dynamic_sidebar('footer-content-0'); ?>
					<?php endif; ?>
				</div>
				<div class="">
					<?php if (is_active_sidebar('footer-content-1')) : ?>
						<?php dynamic_sidebar('footer-content-1'); ?>
					<?php endif; ?>
				</div>
				<div class="">
					<?php if (is_active_sidebar('footer-content-2')) : ?>
						<?php dynamic_sidebar('footer-content-2'); ?>
					<?php endif; ?>
				</div>
			</div>
			<div class="border-t border-t-neutral-300 mt-6 pt-6 lg:mt-8 lg:pt-8 flex flex-wrap flex-col lg:flex-row justify-center lg:justify-between gap-8 lg:gap-12">
				<div class="legal-copyright">
					<?php if (is_active_sidebar('footer-content-3')) : ?>
						<?php dynamic_sidebar('footer-content-3'); ?>
					<?php endif; ?>
					<div class="text-center lg:text-start text-sm mt-2">
						&copy; <?php echo date("Y"); ?> <a class="font-semibold" href="<?php echo esc_url(home_url('/')); ?>" rel="home"><?php bloginfo('name'); ?></a>. All Rights Reserved.
						Website developed by <a class="font-semibold" href="https://usoppii.my/" target="_blank" rel="noreferrer">Usoppii.my</a>.
					</div>
				</div>

				<div class="flex gap-1.5 flex-wrap justify-center lg:justify-end">
					<img src="<?php echo get_template_directory_uri(); ?>/assets/icons/fpx-icon.jpg" alt="FPX" title="FPX" loading="lazy" class="w-12 h-6 rounded-sm shrink-0">
					<img src="<?php echo get_template_directory_uri(); ?>/assets/icons/duitnow-qr-icon.png" alt="DuitNow QR" title="DuitNow QR" loading="lazy" class="w-12 h-6 rounded-sm shrink-0">
					<img src="<?php echo get_template_directory_uri(); ?>/assets/icons/mastercard-icon.png" alt="MasterCard" title="MasterCard" loading="lazy" class="w-12 h-6 rounded-sm shrink-0">
					<img src="<?php echo get_template_directory_uri(); ?>/assets/icons/visa-new-icon.png" alt="Visa" title="Visa" loading="lazy" class="w-12 h-6 rounded-sm shrink-0">
				</div>
			</div>
		</div>
	<?php endif; ?>
</footer><!-- #colophon -->