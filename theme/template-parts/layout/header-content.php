<?php

/**
 * Template part for displaying the header content
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/
 *
 * @package ShopChop
 */


// Check for Checkout, Order Received, Login/Register, and Lost Password
$is_minimal_page = is_checkout() || is_wc_endpoint_url('order-received') || (is_account_page() && !is_user_logged_in()) || is_lost_password_page(); // Handles the reset password screen
?>

<header id="masthead" class="<?php echo $is_minimal_page ? 'shopchop-header-minimal' : 'shopchop-header-normal'; ?>">
	<?php if ($is_minimal_page) : ?>
		<div class="container mx-auto px-6 md:px-10 lg:px-16 py-4">
			<div class="header-minimal-content">
				<div class="shopchop-logo-meta">
					<?php shopchop_render_logo( 'h-16 w-auto shrink-0' ); ?>
				</div>
				<div class="shopchop-tagline-desc max-w-48">
					<?php

					$shopchop_description = get_bloginfo('description', 'display');
					if ($shopchop_description || is_customize_preview()) :
					?>
						<p class="text-sm italic text-end"><?php echo esc_html( $shopchop_description ); ?></p>
					<?php endif; ?>
				</div>
			</div><!-- header-minimal-content end -->

		<?php else : ?>
			<!-- Demo Store Wrapper Row, Visible on larger screens and higher -->
			<?php do_action('shopchop_demo_store_wrapper'); ?>
			<?php $is_logged_in = is_user_logged_in(); ?>

			<!-- Main Normal Content Wrapper -->
			<div class="container mx-auto px-6 md:px-10 lg:px-16">
				<div class="header-normal-content flex flex-col">

					<!-- Search Bar Row: Logo, Search Bar, Account Controls -->
					<div class="search-bar-row flex flex-wrap items-center w-full lg:gap-6 py-4">
						<div class="shopchop-logo-meta lg:shrink-0">
							<?php shopchop_render_logo( 'h-16 lg:h-20 w-auto shrink-0' ); ?>
						</div>

						<!-- Mobile Menu Cluster Buttons -->
						<div class="mobile-menu-btns">
							<!-- Mobile Menu Search -->
							<button id="mobile-search-toggle" class="shopchop-menu-item" aria-expanded="false" aria-controls="mobile-search" aria-label="<?php esc_attr_e( 'Search', 'shopchop' ); ?>">
								<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 256 256" aria-hidden="true"><path d="M229.66,218.34l-50.07-50.06a88.11,88.11,0,1,0-11.31,11.31l50.06,50.07a8,8,0,0,0,11.32-11.32ZM40,112a72,72,0,1,1,72,72A72.08,72.08,0,0,1,40,112Z"></path></svg>
							</button>

							<!-- Mobile Cart -->
							<button id="mobile-cart-toggle" class="shopchop-menu-item" aria-expanded="false" aria-controls="mobile-mini-cart" aria-label="<?php esc_attr_e( 'Cart', 'shopchop' ); ?>">
								<?php echo do_shortcode('[shopchop_mobile_cart_icon_display]'); ?>
							</button>

							<!-- Mobile Menu Hamburger -->
							<button id="mobile-menu-toggle" class="shopchop-menu-item" aria-expanded="false" aria-controls="mobile-panel" aria-label="<?php esc_attr_e( 'Menu', 'shopchop' ); ?>">
								<span class="toggle-open">
									<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 256 256" aria-hidden="true"><path d="M224,128a8,8,0,0,1-8,8H40a8,8,0,0,1,0-16H216A8,8,0,0,1,224,128ZM40,72H216a8,8,0,0,0,0-16H40a8,8,0,0,0,0,16ZM216,184H40a8,8,0,0,0,0,16H216a8,8,0,0,0,0-16Z"></path></svg>
								</span>
								<span class="toggle-close" style="display:none;">
									<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 256 256" aria-hidden="true"><path d="M205.66,194.34a8,8,0,0,1-11.32,11.32L128,139.31,61.66,205.66a8,8,0,0,1-11.32-11.32L116.69,128,50.34,61.66A8,8,0,0,1,61.66,50.34L128,116.69l66.34-66.35a8,8,0,0,1,11.32,11.32L139.31,128Z"></path></svg>
								</span>
							</button>
						</div>

						<!-- Search Bar -->
						<?php echo do_shortcode('[shopchop_search_bar context="desktop"]'); ?>

						<!-- Account Controls. Visible for lg screens -->
						<div class="shopchop-account-controls">

							<!-- Wishlist button - Display in heart shape -->
							<div class="shopchop-wishlist-wrapper">
								<a href="<?php echo esc_url(home_url('/wishlist')); ?>" class="shopchop-wishlist-btn" aria-label="Wishlist" title="Wishlist">
									<svg class="wishlist-icon" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 256 256" aria-hidden="true"><path d="M178,40c-20.65,0-38.73,8.88-50,23.89C116.73,48.88,98.65,40,78,40a62.07,62.07,0,0,0-62,62c0,70,103.79,126.66,108.21,129a8,8,0,0,0,7.58,0C136.21,228.66,240,172,240,102A62.07,62.07,0,0,0,178,40ZM128,214.8C109.74,204.16,32,155.69,32,102A46.06,46.06,0,0,1,78,56c19.45,0,35.78,10.36,42.6,27a8,8,0,0,0,14.8,0c6.82-16.67,23.15-27,42.6-27a46.06,46.06,0,0,1,46,46C224,155.61,146.24,204.15,128,214.8Z"></path></svg>
									<span class="sr-only"><?php esc_html_e( 'Wishlist', 'shopchop' ); ?></span>
								</a>
							</div>

							<!-- Mini Cart -->
							<?php echo do_shortcode('[shopchop_mini_cart]'); ?>

							<!-- Account Control -->
							<div class="shopchop-account-wrapper">
								<a href="<?php echo esc_url(wc_get_account_endpoint_url('dashboard')); ?>" class="shopchop-account-trigger" aria-label="Account Menu" aria-expanded="false">
									<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 256 256" class="user-icon"><path d="M230.92,212c-15.23-26.33-38.7-45.21-66.09-54.16a72,72,0,1,0-73.66,0C63.78,166.78,40.31,185.66,25.08,212a8,8,0,1,0,13.85,8c18.84-32.56,52.14-52,89.07-52s70.23,19.44,89.07,52a8,8,0,1,0,13.85-8ZM72,96a56,56,0,1,1,56,56A56.06,56.06,0,0,1,72,96Z"></path></svg>
									<span class="account-label">
										<?php
										if ($is_logged_in) {
											$current_user = wp_get_current_user();
											$user_name = $current_user->display_name;
											echo esc_html($user_name);
										} else {
											esc_html_e( 'Log In / Register', 'shopchop' );
										}
										?>
									</span>
								</a>

								<div class="shopchop-account-dropdown" style="display: none;">
									<div class="account-dropdown-content">
										<?php if ($is_logged_in) : ?>
											<?php
											$current_user = wp_get_current_user();
											$user_name = $current_user->display_name;
											?>

											<!-- Logged In View -->
											<div class="account-header">
												<div class="user-greeting">
													<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 256 256"><path d="M230.92,212c-15.23-26.33-38.7-45.21-66.09-54.16a72,72,0,1,0-73.66,0C63.78,166.78,40.31,185.66,25.08,212a8,8,0,1,0,13.85,8c18.84-32.56,52.14-52,89.07-52s70.23,19.44,89.07,52a8,8,0,1,0,13.85-8ZM72,96a56,56,0,1,1,56,56A56.06,56.06,0,0,1,72,96Z"></path></svg>
													<span>Hello, <strong><?php echo esc_html($user_name); ?></strong></span>
												</div>
											</div>

											<div class="account-menu-items">
												<a href="<?php echo esc_url(wc_get_account_endpoint_url('dashboard')); ?>" class="account-menu-item">
													<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 256 256"><path d="M144,157.68a68,68,0,1,0-71.9,0c-20.65,6.76-39.23,19.39-54.17,37.17a8,8,0,1,0,12.24,10.3C50.25,181.19,77.91,168,108,168s57.75,13.19,77.87,37.15a8,8,0,0,0,12.26-10.3C183.18,177.07,164.6,164.44,144,157.68ZM56,100a52,52,0,1,1,52,52A52.06,52.06,0,0,1,56,100Zm196.25,43.07-4.66-2.69a23.6,23.6,0,0,0,0-8.76l4.66-2.69a8,8,0,1,0-8-13.86l-4.67,2.7a23.92,23.92,0,0,0-7.58-4.39V108a8,8,0,0,0-16,0v5.38a23.92,23.92,0,0,0-7.58,4.39l-4.67-2.7a8,8,0,1,0-8,13.86l4.66,2.69a23.6,23.6,0,0,0,0,8.76l-4.66,2.69a8,8,0,0,0,8,13.86l4.67-2.7a23.92,23.92,0,0,0,7.58,4.39V164a8,8,0,0,0,16,0v-5.38a23.92,23.92,0,0,0,7.58-4.39l4.67,2.7a7.92,7.92,0,0,0,4,1.07,8,8,0,0,0,4-14.93ZM216,136a8,8,0,1,1,8,8A8,8,0,0,1,216,136Z"></path></svg>
													<span>My Account</span>
												</a>
												<a href="<?php echo esc_url(wc_get_account_endpoint_url('orders')); ?>" class="account-menu-item">
													<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 256 256"><path d="M223.68,66.15,135.68,18a15.88,15.88,0,0,0-15.36,0l-88,48.17a16,16,0,0,0-8.32,14v95.64a16,16,0,0,0,8.32,14l88,48.17a15.88,15.88,0,0,0,15.36,0l88-48.17a16,16,0,0,0,8.32-14V80.18A16,16,0,0,0,223.68,66.15ZM128,32l80.34,44-29.77,16.3-80.35-44ZM128,120,47.66,76l33.9-18.56,80.34,44ZM40,90l80,43.78v85.79L40,175.82Zm176,85.78h0l-80,43.79V133.82l32-17.51V152a8,8,0,0,0,16,0V107.55L216,90v85.77Z"></path></svg>
													<span>Orders</span>
												</a>
												<a href="<?php echo esc_url(wc_get_account_endpoint_url('edit-address')); ?>" class="account-menu-item">
													<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 256 256"><path d="M112,80a16,16,0,1,1,16,16A16,16,0,0,1,112,80ZM64,80a64,64,0,0,1,128,0c0,59.95-57.58,93.54-60,94.95a8,8,0,0,1-7.94,0C121.58,173.54,64,140,64,80Zm16,0c0,42.2,35.84,70.21,48,78.5,12.15-8.28,48-36.3,48-78.5a48,48,0,0,0-96,0Zm122.77,67.63a8,8,0,0,0-5.54,15C213.74,168.74,224,176.92,224,184c0,13.36-36.52,32-96,32s-96-18.64-96-32c0-7.08,10.26-15.26,26.77-21.36a8,8,0,0,0-5.54-15C29.22,156.49,16,169.41,16,184c0,31.18,57.71,48,112,48s112-16.82,112-48C240,169.41,226.78,156.49,202.77,147.63Z"></path></svg>
													<span>Addresses</span>
												</a>
												<a href="<?php echo esc_url(wc_get_account_endpoint_url('edit-account')); ?>" class="account-menu-item">
													<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 256 256"><path d="M227.32,73.37,182.63,28.69a16,16,0,0,0-22.63,0L36.69,152A15.86,15.86,0,0,0,32,163.31V208a16,16,0,0,0,16,16H216a8,8,0,0,0,0-16H115.32l112-112A16,16,0,0,0,227.32,73.37ZM92.69,208H48V163.31l88-88L180.69,120ZM192,108.69,147.32,64l24-24L216,84.69Z"></path></svg>
													<span>Account Details</span>
												</a>
											</div>

											<div class="account-menu-footer">
												<a href="<?php echo esc_url(wp_logout_url(home_url())); ?>" class="logout-link">
													<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 256 256" class="log-out-icon"><path d="M120,216a8,8,0,0,1-8,8H48a8,8,0,0,1-8-8V40a8,8,0,0,1,8-8h64a8,8,0,0,1,0,16H56V208h56A8,8,0,0,1,120,216Zm109.66-93.66-40-40a8,8,0,0,0-11.32,11.32L204.69,120H112a8,8,0,0,0,0,16h92.69l-26.35,26.34a8,8,0,0,0,11.32,11.32l40-40A8,8,0,0,0,229.66,122.34Z"></path></svg>
													<span>Log Out</span>
												</a>
											</div>

										<?php else : ?>

											<!-- Not Logged In View -->
											<div class="account-login-prompt">
												<div class="login-icon">
													<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 256 256" class="user-icon"><path d="M230.92,212c-15.23-26.33-38.7-45.21-66.09-54.16a72,72,0,1,0-73.66,0C63.78,166.78,40.31,185.66,25.08,212a8,8,0,1,0,13.85,8c18.84-32.56,52.14-52,89.07-52s70.23,19.44,89.07,52a8,8,0,1,0,13.85-8ZM72,96a56,56,0,1,1,56,56A56.06,56.06,0,0,1,72,96Z"></path></svg>
												</div>
												<h3>Sign in to <?php bloginfo('name'); ?></h3>
												<p>Access your account for order management and more</p>
												<a href="<?php echo esc_url(wc_get_page_permalink('myaccount')); ?>" class="login-button">
													Sign In
												</a>
												<div class="register-prompt">
													Don't have an account?
													<a href="<?php echo esc_url(wc_get_page_permalink('myaccount')); ?>">Register</a>
												</div>
											</div>

										<?php endif; ?>
									</div>
								</div>
							</div>
						</div>
					</div>

					<!-- Menu Bar Row -->
					<div class="menu-bar-row">
						<nav id="site-navigation" aria-label="<?php esc_attr_e('Main Navigation', 'shopchop'); ?>">
							<button class="sr-only" aria-controls="main-header-menu-primary" aria-expanded="false"><?php esc_html_e('Primary Menu', 'shopchop'); ?></button>

							<?php
							wp_nav_menu(
								array(
									'theme_location' => 'menu-1',
									'menu_id'        => 'main-header-menu-primary',
									'items_wrap'     => '<ul id="%1$s" class="%2$s">%3$s</ul>',
								)
							);
							?>
						</nav><!-- #site-navigation -->
					</div>

				</div><!-- header-normal-content end -->
			<?php endif; ?>
			</div><!-- container end -->

<!-- Mobile Search Bar -->
<div id="mobile-search">
	<div class="mobile-search-header">
		<h3 class=""><?php esc_html_e( 'Search Items', 'shopchop' ); ?></h3>
		<button id="search-close" aria-label="<?php esc_attr_e('Close search', 'shopchop'); ?>">
			<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 256 256" aria-hidden="true"><path d="M205.66,194.34a8,8,0,0,1-11.32,11.32L128,139.31,61.66,205.66a8,8,0,0,1-11.32-11.32L116.69,128,50.34,61.66A8,8,0,0,1,61.66,50.34L128,116.69l66.34-66.35a8,8,0,0,1,11.32,11.32L139.31,128Z"></path></svg>
		</button>
	</div>
	<div class="mobile-search-content">
		<?php echo do_shortcode('[shopchop_search_bar context="mobile"]'); ?>
	</div>
</div>

<!-- Mobile Cart Display -->
<div id="mobile-mini-cart">
	<?php echo do_shortcode('[shopchop_mobile_cart_details_display]'); ?>
</div>

<!-- Account Controls & Main Menus -->
<div id="mobile-panel">
	<div class="mobile-panel-header">
		<h3 class=""><?php esc_html_e( 'Menu', 'shopchop' ); ?></h3>
		<button id="menu-close" aria-label="<?php esc_attr_e('Close menu', 'shopchop'); ?>">
			<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 256 256" aria-hidden="true"><path d="M205.66,194.34a8,8,0,0,1-11.32,11.32L128,139.31,61.66,205.66a8,8,0,0,1-11.32-11.32L116.69,128,50.34,61.66A8,8,0,0,1,61.66,50.34L128,116.69l66.34-66.35a8,8,0,0,1,11.32,11.32L139.31,128Z"></path></svg>
		</button>
	</div>

	<div class="mobile-panel-content">
		<div class="shopchop-account-info-mobile">
			<?php
			$current_user = wp_get_current_user();
			?>
			<a href="<?php echo esc_url(wc_get_account_endpoint_url('dashboard')); ?>" class="account-block-wrapper">
				<div class="account-meta">
					<?php if ( is_user_logged_in() ) { ?>
						<div class="account-image">
							<?php echo get_avatar( $current_user->ID, 40 ); ?>
						</div>
						<div class="account-details">
							<h5 class="user-name"><?php echo esc_html( $current_user->display_name ) ?></h5>
							<p class="user-actions"><?php esc_html_e( 'View My Account', 'shopchop' ); ?></p>
						</div>
					<?php } else { ?>
						<div class="account-image">
							<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 256 256"><path d="M230.92,212c-15.23-26.33-38.7-45.21-66.09-54.16a72,72,0,1,0-73.66,0C63.78,166.78,40.31,185.66,25.08,212a8,8,0,1,0,13.85,8c18.84-32.56,52.14-52,89.07-52s70.23,19.44,89.07,52a8,8,0,1,0,13.85-8ZM72,96a56,56,0,1,1,56,56A56.06,56.06,0,0,1,72,96Z"></path></svg>
						</div>
						<div class="account-details">
							<h5 class="user-name"><?php esc_html_e( 'Sign In or Register', 'shopchop' ); ?></h5>
							<p class="user-actions"><?php esc_html_e( 'Access orders & account details', 'shopchop' ); ?></p>
						</div>
					<?php } ?>
				</div>
				<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 256 256"><path d="M181.66,133.66l-80,80a8,8,0,0,1-11.32-11.32L164.69,128,90.34,53.66a8,8,0,0,1,11.32-11.32l80,80A8,8,0,0,1,181.66,133.66Z"></path></svg>
			</a>
		</div>
		<div class="shopchop-menu-mobile">
			<h5 class="menu-heading"><?php esc_html_e( 'Shop', 'shopchop' ); ?></h5>
			<nav id="site-navigation-mobile" aria-label="<?php esc_attr_e('Mobile Navigation', 'shopchop'); ?>">

				<?php
				wp_nav_menu(
					array(
						'theme_location' => 'menu-1',
						'menu_id'        => 'main-header-menu-mobile',
						'items_wrap'     => '<ul id="%1$s" class="%2$s">%3$s</ul>',
					)
				);
				?>
			</nav><!-- #site-navigation-mobile -->
		</div>
		<?php if ( is_user_logged_in() ) { ?>
		<div class="shopchop-account-actions-mobile">
			<h5 class="account-heading"><?php esc_html_e( 'Account', 'shopchop' ); ?></h5>
			<a href="<?php echo esc_url(wc_get_account_endpoint_url('orders')); ?>" class="control-item">
				<svg class="me-4 text-neutral-700" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 256 256"><path d="M223.68,66.15,135.68,18a15.88,15.88,0,0,0-15.36,0l-88,48.17a16,16,0,0,0-8.32,14v95.64a16,16,0,0,0,8.32,14l88,48.17a15.88,15.88,0,0,0,15.36,0l88-48.17a16,16,0,0,0,8.32-14V80.18A16,16,0,0,0,223.68,66.15ZM128,32l80.34,44-29.77,16.3-80.35-44ZM128,120,47.66,76l33.9-18.56,80.34,44ZM40,90l80,43.78v85.79L40,175.82Zm176,85.78h0l-80,43.79V133.82l32-17.51V152a8,8,0,0,0,16,0V107.55L216,90v85.77Z"></path></svg>
				<?php esc_html_e( 'My Orders', 'shopchop' ); ?>
			</a>
			<a href="<?php echo esc_url(home_url('/wishlist')); ?>" class="control-item">
				<svg class="me-4 text-neutral-700" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 256 256"><path d="M178,40c-20.65,0-38.73,8.88-50,23.89C116.73,48.88,98.65,40,78,40a62.07,62.07,0,0,0-62,62c0,70,103.79,126.66,108.21,129a8,8,0,0,0,7.58,0C136.21,228.66,240,172,240,102A62.07,62.07,0,0,0,178,40ZM128,214.8C109.74,204.16,32,155.69,32,102A46.06,46.06,0,0,1,78,56c19.45,0,35.78,10.36,42.6,27a8,8,0,0,0,14.8,0c6.82-16.67,23.15-27,42.6-27a46.06,46.06,0,0,1,46,46C224,155.61,146.24,204.15,128,214.8Z"></path></svg>
				<?php esc_html_e( 'Wishlist', 'shopchop' ); ?>
			</a>
			<a href="<?php echo esc_url(wp_logout_url(home_url())); ?>" class="control-item logout">
				<svg class="me-4 text-error-700" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 256 256"><path d="M120,216a8,8,0,0,1-8,8H48a8,8,0,0,1-8-8V40a8,8,0,0,1,8-8h64a8,8,0,0,1,0,16H56V208h56A8,8,0,0,1,120,216Zm109.66-93.66-40-40a8,8,0,0,0-11.32,11.32L204.69,120H112a8,8,0,0,0,0,16h92.69l-26.35,26.34a8,8,0,0,0,11.32,11.32l40-40A8,8,0,0,0,229.66,122.34Z"></path></svg>
				<?php esc_html_e( 'Log Out', 'shopchop' ); ?>
			</a>
		</div>
		<?php } ?>
	</div>
</div>

<!-- Backdrop -->
<div id="backdrop"></div>

</header><!-- #masthead -->