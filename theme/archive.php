<?php
/**
 * The template for displaying archive pages
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/
 *
 * @package ShopChop
 */

get_header();
?>

<section id="primary" class="archive-main-template">
	<main id="main">

			<?php
			if ( function_exists( 'woocommerce_breadcrumb' ) ) {
				woocommerce_breadcrumb();
			}
			?>

			<?php if ( have_posts() ) : ?>

				<header class="archive-page-header">
					<?php
					the_archive_title( '<h1 class="archive-page-title">', '</h1>' );
					the_archive_description( '<p class="archive-page-description">', '</p>' );
					?>
				</header>

				<div class="archive-posts-grid">
					<?php while ( have_posts() ) : the_post(); ?>
						<?php get_template_part( 'template-parts/content/content', 'excerpt' ); ?>
					<?php endwhile; ?>
				</div>

				<nav class="archive-pagination">
					<?php
					the_posts_pagination( array(
						'mid_size'  => 2,
						'prev_text' => '&lsaquo;',
						'next_text' => '&rsaquo;',
					) );
					?>
				</nav>

			<?php else : ?>

				<?php get_template_part( 'template-parts/content/content', 'none' ); ?>

			<?php endif; ?>

	</main><!-- #main -->
</section><!-- #primary -->

<?php
get_footer();
