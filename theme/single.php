<?php

/**
 * The template for displaying all single posts
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/#single-post
 *
 * @package ShopChop
 */

get_header();
?>
<section id="primary" class="single-main-template">
	<main id="main">

		<?php
		if (function_exists('woocommerce_breadcrumb')) {
			woocommerce_breadcrumb();
		}

		/* Start the Loop */
		while (have_posts()) :
			the_post();
			get_template_part('template-parts/content/content', 'single');

			if (is_singular('post')) {
				$prev_post = get_adjacent_post(false, '', true);
				$next_post = get_adjacent_post(false, '', false);

				if ($prev_post || $next_post) : ?>
					<nav class="post-navigation" aria-label="<?php esc_attr_e('Post navigation', 'shopchop'); ?>">
						<div class="nav-links">
							<?php if ($prev_post) :
								$prev_thumb = get_the_post_thumbnail($prev_post, 'thumbnail');
							?>
								<a href="<?php echo esc_url(get_permalink($prev_post)); ?>" class="nav-post nav-prev group">
									<div class="nav-post-image">
										<?php if ($prev_thumb) : ?>
											<?php echo $prev_thumb; ?>
										<?php endif; ?>
									</div>
									<div class="nav-post-content">
										<span class="nav-post-label"><?php esc_html_e('Previous Post', 'shopchop'); ?></span>
										<span class="nav-post-title"><?php echo esc_html(get_the_title($prev_post)); ?></span>
									</div>
								</a>
							<?php endif; ?>

							<?php if ($next_post) :
								$next_thumb = get_the_post_thumbnail($next_post, 'thumbnail');
							?>
								<a href="<?php echo esc_url(get_permalink($next_post)); ?>" class="nav-post nav-next group">
									<div class="nav-post-image">
										<?php if ($next_thumb) : ?>
											<?php echo $next_thumb; ?>
										<?php endif; ?>
									</div>
									<div class="nav-post-content">
										<span class="nav-post-label"><?php esc_html_e('Next Post', 'shopchop'); ?></span>
										<span class="nav-post-title"><?php echo esc_html(get_the_title($next_post)); ?></span>
									</div>
								</a>
							<?php endif; ?>
						</div>
					</nav>
		<?php endif;
			}

			// If comments are open, or we have at least one comment, load
			// the comment template.
			if (comments_open() || get_comments_number()) {
				comments_template();
			}

		// End the loop.
		endwhile;
		?>

	</main><!-- #main -->
</section><!-- #primary -->

<?php
get_footer();
