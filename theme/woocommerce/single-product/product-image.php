<?php
/**
 * Single Product Image — Swiper gallery override
 *
 * @see     https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 10.5.0
 */

use Automattic\WooCommerce\Enums\ProductType;

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'wc_get_gallery_image_html' ) ) {
	return;
}

global $product;

$columns           = apply_filters( 'woocommerce_product_thumbnails_columns', 4 );
$post_thumbnail_id = $product->get_image_id();
$gallery_image_ids = $product->get_gallery_image_ids();

/**
 * Lets a plugin (e.g. splash product video) declare that it's about to
 * inject extra slides via JS after this template renders, so the thumbnail
 * strip + nav arrows shell still gets rendered even for a single-image
 * product that would otherwise get neither.
 */
$has_extra_gallery_slides = apply_filters( 'shopchop_gallery_has_extra_slides', false, $product );
$show_thumbs_and_nav      = ! empty( $gallery_image_ids ) || $has_extra_gallery_slides;
$wrapper_classes   = apply_filters(
	'woocommerce_single_product_image_gallery_classes',
	array(
		'woocommerce-product-gallery',
		'woocommerce-product-gallery--' . ( $post_thumbnail_id ? 'with-images' : 'without-images' ),
		'woocommerce-product-gallery--columns-' . absint( $columns ),
		'images',
	)
);
?>
<div class="<?php echo esc_attr( implode( ' ', array_map( 'sanitize_html_class', $wrapper_classes ) ) ); ?>" data-columns="<?php echo esc_attr( $columns ); ?>" style="opacity: 0; transition: opacity .25s ease-in-out;">

	<div class="splashshop-gallery-main swiper">
		<div class="swiper-wrapper">
			<?php if ( $post_thumbnail_id ) : ?>

				<div class="swiper-slide">
					<?php echo apply_filters( 'woocommerce_single_product_image_thumbnail_html', wc_get_gallery_image_html( $post_thumbnail_id, true ), $post_thumbnail_id ); // phpcs:disable WordPress.XSS.EscapeOutput.OutputNotEscaped ?>
				</div>

				<?php foreach ( $gallery_image_ids as $attachment_id ) : ?>
					<div class="swiper-slide">
						<?php echo apply_filters( 'woocommerce_single_product_image_thumbnail_html', wc_get_gallery_image_html( $attachment_id, true ), $attachment_id ); // phpcs:disable WordPress.XSS.EscapeOutput.OutputNotEscaped ?>
					</div>
				<?php endforeach; ?>

			<?php else : ?>

				<?php
				$wrapper_classname = $product->is_type( ProductType::VARIABLE ) && ! empty( $product->get_visible_children() ) && '' !== $product->get_price()
					? 'woocommerce-product-gallery__image woocommerce-product-gallery__image--placeholder'
					: 'woocommerce-product-gallery__image--placeholder';
				?>
				<div class="swiper-slide">
					<div class="<?php echo esc_attr( $wrapper_classname ); ?>">
						<img src="<?php echo esc_url( wc_placeholder_img_src( 'woocommerce_single' ) ); ?>" alt="<?php echo esc_attr( $product->get_name() ); ?>" class="wp-post-image" />
					</div>
				</div>

			<?php endif; ?>
		</div>

		<?php if ( $post_thumbnail_id && $show_thumbs_and_nav ) : ?>
			<div class="swiper-button-prev" role="button" aria-label="<?php esc_attr_e( 'Previous product image', 'shopchop' ); ?>"></div>
			<div class="swiper-button-next" role="button" aria-label="<?php esc_attr_e( 'Next product image', 'shopchop' ); ?>"></div>
		<?php endif; ?>
	</div>

	<?php if ( $post_thumbnail_id && $show_thumbs_and_nav ) : ?>
		<div class="splashshop-gallery-thumbs swiper">
			<div class="swiper-wrapper">
				<div class="swiper-slide">
					<?php echo wp_get_attachment_image( $post_thumbnail_id, 'woocommerce_gallery_thumbnail' ); ?>
				</div>
				<?php foreach ( $gallery_image_ids as $thumb_id ) : ?>
					<div class="swiper-slide">
						<?php echo wp_get_attachment_image( $thumb_id, 'woocommerce_gallery_thumbnail' ); ?>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
	<?php endif; ?>

</div>
