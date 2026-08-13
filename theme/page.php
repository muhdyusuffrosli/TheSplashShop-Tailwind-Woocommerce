<?php

/**
 * The template for displaying all pages
 *
 * This is the template that displays all pages by default. Please note that
 * this is the WordPress construct of pages: specifically, posts with a post
 * type of `page`.
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/
 *
 * @package ShopChop
 */

get_header();
?>

<section id="primary" class="page-main-template">
	<main id="main">

		<?php
		/* Start the Loop */
		while (have_posts()) :
			the_post();

			if (is_front_page()) {
				// Homepage
				get_template_part('template-parts/woocommerce/content', 'homepage');

			} elseif (is_account_page()) {
				// WooCommerce My Account
				get_template_part('template-parts/woocommerce/content', 'account');

			} elseif (is_checkout()) {
				// Both Checkout Page and Order Received (Thank You) Page
				get_template_part('template-parts/woocommerce/content', 'simple');

			} elseif (is_cart()) {
				// WooCommerce Cart Page
				get_template_part('template-parts/woocommerce/content', 'simple');

			} else {
				// Default page content
				if (function_exists('woocommerce_breadcrumb')) {
					woocommerce_breadcrumb();
				}

				get_template_part('template-parts/content/content', 'page');

			}

			// If comments are open, or we have at least one comment, load
			// the comment template.
			if (comments_open() || get_comments_number()) {
				comments_template();
			}

		endwhile; // End of the loop.
		?>

	</main><!-- #main -->
</section><!-- #primary -->

<?php
get_footer();
