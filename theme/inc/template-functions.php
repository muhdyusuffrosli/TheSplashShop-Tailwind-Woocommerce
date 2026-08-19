<?php

/**
 * ShopChop – Custom Theme Functions
 *
 * Hooks, filters, and helpers that extend the base _Underscores theme and
 * integrate tightly with WooCommerce.
 *
 * Table of Contents
 * ─────────────────────────────────────────────────────────────────────────────
 *  § 1  Core WordPress Hooks          (pingback, comments, archive titles …)
 *  § 2  WooCommerce Layout            (wrapper override, styles, wrappers)
 *  § 3  Product Display               (price, loop structure, category listing)
 *  § 4  Variation Swatches            (pill/radio replacement for <select>)
 *  § 5  Checkout & Address Fields     (field labels, ordering, Select2 removal)
 *  § 6  Reviews & Ratings             (custom star display, meta, date)
 *  § 7  Orders                        (order number format, status notes)
 *  § 8  My Account                    (content titles, auth wrapper, redirects)
 *  § 9  Demo Store & Checkout Layout  (notice relocation, payment hook)
 *  § 10 Authentication                (generic error messages)
 *  § 11 Thank-You Page                (next-steps section)
 *  § 12 AJAX Search                   (product search + category endpoints)
 *  § 13 Search Bar Shortcode          ([shopchop_search_bar])
 *  § 14 Mini Cart AJAX                (get cart, remove item, fragments)
 *  § 15 Mini Cart Shortcodes          ([shopchop_mini_cart] etc.)
 *  § 16 Custom Stock Statuses         (Pre-Order, Coming Soon)
 *  § 17 Recently Viewed Products      (cookie-based, single product pages)
 *  § 18 WhatsApp Floating Button      (single product pages)
 *  § 19 Admin Login Page Styling      (custom CSS, same WP auth)
 * ─────────────────────────────────────────────────────────────────────────────
 *
 * @package ShopChop
 */



/* =============================================================================
	§ 1  Core WordPress Hooks
   ============================================================================= */

/**
 * Emit a pingback auto-discovery header for singular, pingable posts.
 */
function shopchop_pingback_header() {
	if ( is_singular() && pings_open() ) {
		printf( '<link rel="pingback" href="%s">', esc_url( get_bloginfo( 'pingback_url' ) ) );
	}
}
add_action( 'wp_head', 'shopchop_pingback_header' );



/**
 * Reduce the comment textarea height to 5 rows.
 *
 * @param array $defaults Default comment-form arguments.
 * @return array Modified arguments.
 */
function shopchop_comment_form_defaults( $defaults ) {
	$defaults['comment_field'] = preg_replace( '/rows="\d+"/', 'rows="5"', $defaults['comment_field'] );
	return $defaults;
}
add_filter( 'comment_form_defaults', 'shopchop_comment_form_defaults' );



/**
 * Replace the default archive title with a labelled, translated version.
 *
 * @return string Translated archive title with a <span>-wrapped term/date.
 */
function shopchop_get_the_archive_title() {
	if ( is_category() ) {
		$title = __( 'Category Archives: ', 'shopchop' ) . '<span>' . single_term_title( '', false ) . '</span>';
	} elseif ( is_tag() ) {
		$title = __( 'Tag Archives: ', 'shopchop' ) . '<span>' . single_term_title( '', false ) . '</span>';
	} elseif ( is_author() ) {
		$title = __( 'Author Archives: ', 'shopchop' ) . '<span>' . get_the_author_meta( 'display_name' ) . '</span>';
	} elseif ( is_year() ) {
		$title = __( 'Yearly Archives: ', 'shopchop' ) . '<span>' . get_the_date( _x( 'Y', 'yearly archives date format', 'shopchop' ) ) . '</span>';
	} elseif ( is_month() ) {
		$title = __( 'Monthly Archives: ', 'shopchop' ) . '<span>' . get_the_date( _x( 'F Y', 'monthly archives date format', 'shopchop' ) ) . '</span>';
	} elseif ( is_day() ) {
		$title = __( 'Daily Archives: ', 'shopchop' ) . '<span>' . get_the_date() . '</span>';
	} elseif ( is_post_type_archive() ) {
		$cpt   = get_post_type_object( get_queried_object()->name );
		$title = sprintf(
			/* translators: %s: Post type singular name */
			esc_html__( '%s Archives', 'shopchop' ),
			$cpt->labels->singular_name
		);
	} elseif ( is_tax() ) {
		$tax   = get_taxonomy( get_queried_object()->taxonomy );
		$title = sprintf(
			/* translators: %s: Taxonomy singular name */
			esc_html__( '%s Archives', 'shopchop' ),
			$tax->labels->singular_name
		);
	} else {
		$title = __( 'Archives:', 'shopchop' );
	}

	return $title;
}
add_filter( 'get_the_archive_title', 'shopchop_get_the_archive_title' );



/**
 * Return true when a post thumbnail may safely be displayed.
 *
 * @return bool
 */
function shopchop_can_show_post_thumbnail() {
	return apply_filters(
		'shopchop_can_show_post_thumbnail',
		! post_password_required() && ! is_attachment() && has_post_thumbnail()
	);
}



/**
 * Return the avatar size (px) used throughout the theme.
 *
 * @return int
 */
function shopchop_get_avatar_size() {
	return 60;
}



/**
 * Build the "Continue reading" link appended to excerpts and content.
 *
 * @param string $more_string The default more string.
 * @return string Modified more string with a permalink anchor.
 */
function shopchop_continue_reading_link( $more_string ) {
	if ( ! is_admin() ) {
		$continue_reading = sprintf(
			/* translators: %s: Name of current post. */
			wp_kses( __( 'Continue reading %s', 'shopchop' ), array( 'span' => array( 'class' => array() ) ) ),
			the_title( '<span class="sr-only">"', '"</span>', false )
		);
		$more_string = '<a href="' . esc_url( get_permalink() ) . '">' . $continue_reading . '</a>';
	}
	return $more_string;
}
add_filter( 'excerpt_more',          'shopchop_continue_reading_link' );
add_filter( 'the_content_more_link', 'shopchop_continue_reading_link' );



/**
 * Render a single comment in HTML5 format.
 *
 * Overrides WordPress core output to inject the Tailwind Typography class.
 * Based on `html5_comment()` in WordPress core.
 *
 * @param WP_Comment $comment Comment to display.
 * @param array      $args    Comment-list arguments.
 * @param int        $depth   Nesting depth of the current comment.
 */
function shopchop_html5_comment( $comment, $args, $depth ) {
	$tag = ( 'div' === $args['style'] ) ? 'div' : 'li';

	$commenter          = wp_get_current_commenter();
	$show_pending_links = ! empty( $commenter['comment_author'] );

	$moderation_note = $commenter['comment_author_email']
		? __( 'Your comment is awaiting moderation.', 'shopchop' )
		: __( 'Your comment is awaiting moderation. This is a preview; your comment will be visible after it has been approved.', 'shopchop' );
	?>
	<<?php echo esc_attr( $tag ); ?> id="comment-<?php comment_ID(); ?>" <?php comment_class( $comment->has_children ? 'parent' : '', $comment ); ?>>
		<article id="div-comment-<?php comment_ID(); ?>" class="comment-body">
			<footer class="comment-meta">
				<div class="author-avatar">
					<?php if ( 0 !== $args['avatar_size'] ) echo get_avatar( $comment, $args['avatar_size'] ); ?>
				</div>
				<div class="author-comment-metadata">
					<div class="author-name">
						<?php
						$comment_author = get_comment_author_link( $comment );
						if ( '0' === $comment->comment_approved && ! $show_pending_links ) {
							$comment_author = get_comment_author( $comment );
						}
						printf(wp_kses_post( $comment_author ));
						?>
					</div>
					<div class="author-time">
						<?php
						printf(
							'<a href="%s"><time datetime="%s">%s</time></a>',
							esc_url( get_comment_link( $comment, $args ) ),
							esc_attr( get_comment_time( 'c' ) ),
							esc_html(
								sprintf(
									/* translators: 1: Comment date, 2: Comment time. */
									__( '%1$s at %2$s', 'shopchop' ),
									get_comment_date( '', $comment ),
									get_comment_time()
								)
							)
						);
						edit_comment_link( __( 'Edit', 'shopchop' ), ' <span class="edit-link">', '</span>' );
						?>
					</div>
				</div><!-- .comment-metadata -->

				<?php if ( '0' === $comment->comment_approved ) : ?>
					<em class="comment-awaiting-moderation"><?php echo esc_html( $moderation_note ); ?></em>
				<?php endif; ?>
			</footer><!-- .comment-meta -->

			<div <?php shopchop_content_class( 'comment-content' ); ?>>
				<?php comment_text(); ?>
			</div><!-- .comment-content -->

			<?php
			if ( '1' === $comment->comment_approved || $show_pending_links ) {
				comment_reply_link( array_merge( $args, array(
					'add_below' => 'div-comment',
					'depth'     => $depth,
					'max_depth' => $args['max_depth'],
					'before'    => '<div class="reply">',
					'after'     => '</div>',
				) ) );
			}
			?>
		</article><!-- .comment-body -->
	<?php
}



/* =============================================================================
	§ 2  WooCommerce Layout
   ============================================================================= */

// Suppress the WordPress admin bar site-wide.
add_filter( 'show_admin_bar', '__return_false' );

// Remove default WooCommerce stylesheet (theme ships its own styles).
add_filter( 'woocommerce_enqueue_styles', '__return_false' );

// Replace WooCommerce's default <div> wrappers with the theme's <main> tag.
remove_action( 'woocommerce_before_main_content', 'woocommerce_output_content_wrapper',     10 );
remove_action( 'woocommerce_after_main_content',  'woocommerce_output_content_wrapper_end', 10 );
add_action( 'woocommerce_before_main_content', 'shopchop_wc_wrapper_start' );
add_action( 'woocommerce_after_main_content',  'shopchop_wc_wrapper_end'   );

function shopchop_wc_wrapper_start() { ?>
	<main id="primary" class="shopchop-wrapper shopchop-woocommerce woocommerce" role="primary">
<?php }

function shopchop_wc_wrapper_end() { ?>
	</main><!-- #primary -->
<?php }



/**
 * Wrap the result-count and ordering controls in a single utility bar div.
 */
add_action( 'woocommerce_before_shop_loop', 'shopchop_before_listing_start', 15 );
function shopchop_before_listing_start() {
	echo '<div class="shop-utility-wrapper">';
}

add_action( 'woocommerce_before_shop_loop', 'shopchop_before_listing_end', 35 );
function shopchop_before_listing_end() {
	echo '</div>';
}



/* =============================================================================
	§ 3  Product Display
   ============================================================================= */

/**
 * Override WooCommerce's price HTML to include discount percentages.
 *
 * Regular price  → <span class="price-normal">
 * Sale price     → <span class="discount-price"> with a -%% badge
 *
 * @param string     $price   Default price HTML.
 * @param WC_Product $product Current product.
 * @return string Modified price HTML.
 */
add_filter( 'woocommerce_get_price_html', 'shopchop_price_display', 10, 2 );
function shopchop_price_display( $price, $product ) {

	if ( ! $product->is_on_sale() ) {
		$regular_price = (float) $product->get_regular_price();
		if ( ! $regular_price ) return $price;

		return sprintf(
			'<span class="price-normal"><span class="regular">%s</span></span>',
			wc_price( $regular_price )
		);
	}

	$regular_price = (float) $product->get_regular_price();
	$sale_price    = (float) $product->get_sale_price();
	if ( ! $regular_price || ! $sale_price ) return $price;

	$discount = round( ( ( $regular_price - $sale_price ) / $regular_price ) * 100 );

	return sprintf(
		'<span class="discount-price">
			<del class="regular">%s</del>
			<span class="discount">-%d%%</span>
			<span class="sale">%s</span>
		</span>',
		wc_price( $regular_price ),
		$discount,
		wc_price( $sale_price )
	);
}



/**
 * Category listing – replace the default link-open hook with a custom wrapper
 * that adds a CSS group class and opens the image container.
 */
remove_action( 'woocommerce_before_subcategory',          'woocommerce_template_loop_category_link_open', 10 );
remove_action( 'woocommerce_shop_loop_subcategory_title', 'woocommerce_template_loop_category_title',     10 );

add_action( 'woocommerce_before_subcategory',          'shopchop_category_link_wrapper', 10 );
add_action( 'woocommerce_shop_loop_subcategory_title', 'shopchop_category_title_custom', 10 );

function shopchop_category_link_wrapper( $category ) { ?>
	<a href="<?php echo esc_url( get_term_link( $category ) ); ?>" class="group">
		<div class="category-meta-image">
<?php }

function shopchop_category_title_custom( $category ) { ?>
		</div><!-- .category-meta-image -->
		<div class="category-meta-details">
			<h2 class="woocommerce-loop-category__title"><?php echo esc_html( $category->name ); ?></h2>
			<span class="cat-product-count"><?php echo esc_html( sprintf( _n( '%d product', '%d products', $category->count, 'shopchop' ), $category->count ) ); ?></span>
		</div><!-- .category-meta-details -->
<?php }



/**
 * Product loop – inject structural wrappers around thumbnail, details, and
 * action areas so each card can be styled independently.
 *
 * Priority map:
 *   before_shop_loop_item      @5  → open <a>
 *   before_shop_loop_item_title @5  → open .product-image-wrapper (+ OOS badge)
 *   before_shop_loop_item_title @15 → close image, open .product-details-wrapper
 *   after_shop_loop_item       @5  → close .product-details-wrapper + </a>
 *   after_shop_loop_item       @9  → open .product-actions
 *   after_shop_loop_item       @11 → close .product-actions
 */
remove_action( 'woocommerce_before_shop_loop_item', 'woocommerce_template_loop_product_link_open', 10 );
remove_action( 'woocommerce_after_shop_loop_item',  'woocommerce_template_loop_product_link_close', 5 );

add_action( 'woocommerce_before_shop_loop_item', function () {
	global $product;
	echo '<a href="' . esc_url( get_permalink( $product->get_id() ) ) . '" class="woocommerce-LoopProduct-link woocommerce-loop-product__link group">';
}, 5 );

add_action( 'woocommerce_before_shop_loop_item_title', function () {
	echo '<div class="product-image-wrapper">';
	global $product;
	if ( ! $product->is_in_stock() ) {
		echo '<span class="out-of-stock-badge" aria-hidden="true">' . esc_html__( 'Out of Stock', 'woocommerce' ) . '</span>';
	}
}, 5 );

add_action( 'woocommerce_before_shop_loop_item_title', function () {
	echo '</div>'; // .product-image-wrapper
	echo '<div class="product-details-wrapper">';
}, 15 );

add_action( 'woocommerce_after_shop_loop_item', function () {
	echo '</div>'; // .product-details-wrapper
	echo '</a>';   // main product link
}, 5 );

add_action( 'woocommerce_after_shop_loop_item', function () {
	echo '<div class="product-actions">';
}, 9 );

add_action( 'woocommerce_after_shop_loop_item', function () {
	echo '</div>'; // .product-actions
}, 11 );



/* =============================================================================
	§ 4  Variation Swatches
   ============================================================================= */

/**
 * Replace the variation <select> with pill/button swatches.
 *
 * The original <select> is hidden but kept in the DOM so WooCommerce's own
 * variation-matching JavaScript continues to work. The pill buttons sync back
 * to the hidden select via the JS in script.js (ShopChop.PillSwatches).
 *
 * @param string $html  Default select HTML.
 * @param array  $args  Variation attribute arguments.
 * @return string Hidden select + pill container.
 */
add_filter( 'woocommerce_dropdown_variation_attribute_options_html', 'shopchop_variation_swatch_pill', 10, 2 );
function shopchop_variation_swatch_pill( $html, $args ) {
	$options   = $args['options'];
	$product   = $args['product'];
	$attribute = $args['attribute'];
	$selected  = $args['selected'];

	if ( empty( $options ) || ! $product ) return $html;

	$container = '<div class="pill-swatches-container" data-attribute_name="attribute_' . esc_attr( sanitize_title( $attribute ) ) . '">';
	foreach ( $options as $option ) {
		$active    = ( $selected === $option ) ? 'active' : '';
		$container .= sprintf(
			'<button type="button" class="pill-swatch %s" data-value="%s">%s</button>',
			$active,
			esc_attr( $option ),
			esc_html( $option )
		);
	}
	$container .= '</div>';

	return '<div style="display:none;">' . $html . '</div>' . $container;
}

// Raise the AJAX variation threshold so pill availability checks work reliably
// on products with many variation combinations.
add_filter( 'woocommerce_ajax_variation_threshold', function () {
	return 100;
} );



/* =============================================================================
	§ 5  Checkout & Address Fields
   ============================================================================= */

/**
 * Remove Select2 (we use native <select> elements styled via CSS).
 *
 * @param array $enqueue_styles Registered WooCommerce styles.
 * @return array Unchanged (removal happens via wp_dequeue_*).
 */
add_filter( 'woocommerce_enqueue_styles', 'shopchop_disable_select2', 9999 );
function shopchop_disable_select2( $enqueue_styles ) {
	wp_dequeue_style( 'select2' );
	wp_deregister_style( 'select2' );
	wp_dequeue_script( 'selectWoo' );
	wp_deregister_script( 'selectWoo' );
	return $enqueue_styles;
}



/**
 * Customise default address fields:
 * – Remove last_name and address_2.
 * – Relabel remaining fields for a Malaysian audience.
 * – Reorder fields for a cleaner checkout flow.
 *
 * @param array $fields Default address fields.
 * @return array Modified fields.
 */
add_filter( 'woocommerce_default_address_fields', 'shopchop_override_address_fields' );
function shopchop_override_address_fields( $fields ) {
	unset( $fields['last_name'] );
	unset( $fields['address_2'] );

	// Labels
	$fields['first_name']['label'] = __( 'Name',     'woocommerce' );
	$fields['country']['label']    = __( 'Country',  'woocommerce' );
	$fields['address_1']['label']  = __( 'Address',  'woocommerce' );
	$fields['city']['label']       = __( 'City',     'woocommerce' );
	$fields['state']['label']      = __( 'State',    'woocommerce' );
	$fields['postcode']['label']   = __( 'Postcode', 'woocommerce' );
	$fields['phone']['label']      = __( 'Phone',    'woocommerce' );
	$fields['email']['label']      = __( 'Email',    'woocommerce' );

	// Autocomplete hint
	$fields['first_name']['autocomplete'] = 'name';

	// Placeholders (Malaysia-specific examples)
	$fields['first_name']['placeholder'] = __( 'Name',                                   'woocommerce' );
	$fields['address_1']['placeholder']  = __( '3, Jalan Pembangunan, Taman Perumahan',  'woocommerce' );
	$fields['city']['placeholder']       = __( 'Johor Bahru',                            'woocommerce' );
	$fields['postcode']['placeholder']        = __( '80000', 'woocommerce' );
	$fields['phone']['placeholder']           = __( '+60123456789', 'woocommerce' );
	$fields['email']['placeholder']           = __( 'mail@example.com', 'woocommerce' );
	$fields['postcode']['custom_attributes']  = [ 'maxlength' => '5', 'inputmode' => 'numeric', 'pattern' => '[0-9]{5}' ];
	$fields['phone']['custom_attributes']     = [ 'inputmode' => 'tel' ];

	return $fields;
}



/**
 * Remove last_name from the required-fields list on the Edit Account screen.
 *
 * @param array $fields Required account fields.
 * @return array Modified fields.
 */
add_filter( 'woocommerce_save_account_details_required_fields', 'shopchop_remove_last_name_field' );
function shopchop_remove_last_name_field( $fields ) {
	unset( $fields['account_last_name'] );
	return $fields;
}



/**
 * Reorder address fields so postcode appears before city.
 *
 * @param array $fields Address fields.
 * @return array Fields with updated priorities.
 */
add_filter( 'woocommerce_default_address_fields', 'shopchop_reorder_fields' );
function shopchop_reorder_fields( $fields ) {
	$fields['first_name']['priority'] = 10;
	$fields['address_1']['priority']  = 20;
	$fields['postcode']['priority']   = 30;
	$fields['city']['priority']       = 40;
	$fields['state']['priority']      = 50;
	$fields['country']['priority']    = 60;
	return $fields;
}



/**
 * Rename cart/checkout shipping package label from WC core's default
 * "Shipment" to "Shipping".
 *
 * @param string $shipping_package_name Default package name.
 * @return string Renamed package name.
 */
add_filter( 'woocommerce_shipping_package_name', 'shopchop_rename_shipping_package', 10, 1 );
function shopchop_rename_shipping_package( $shipping_package_name ) {
	return __( 'Shipping', 'shopchop' );
}



/* =============================================================================
	§ 6  Reviews & Ratings
   ============================================================================= */

/**
 * Replace WooCommerce's default star rating with a custom SVG star row
 * that also displays the numeric score (e.g. "4.0 / 5").
 */
remove_action( 'woocommerce_review_before_comment_meta', 'woocommerce_review_display_rating', 10 );
add_action(    'woocommerce_review_before_comment_meta', 'shopchop_custom_review_rating',     10 );

function shopchop_custom_review_rating() {
	global $comment;
	$rating = intval( get_comment_meta( $comment->comment_ID, 'rating', true ) );

	if ( ! $rating || ! wc_review_ratings_enabled() ) return;
	?>
	<div class="shopchop-rating-wrapper">
		<div class="shopchop-stars" role="img" aria-label="Rated <?php echo $rating; ?> out of 5">
			<?php for ( $i = 1; $i <= 5; $i++ ) : ?>
				<?php if ( $i <= $rating ) : ?>
					<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 256 256" class="shopchop-star is-filled" aria-hidden="true"><path d="M234.29,114.85l-45,38.83L203,211.75a16.4,16.4,0,0,1-24.5,17.82L128,198.49,77.47,229.57A16.4,16.4,0,0,1,53,211.75l13.76-58.07-45-38.83A16.46,16.46,0,0,1,31.08,86l59-4.76,22.76-55.08a16.36,16.36,0,0,1,30.27,0l22.75,55.08,59,4.76a16.46,16.46,0,0,1,9.37,28.86Z"></path></svg>
				<?php else : ?>
					<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 256 256" class="shopchop-star is-empty" aria-hidden="true"><path d="M239.18,97.26A16.38,16.38,0,0,0,224.92,86l-59-4.76L143.14,26.15a16.36,16.36,0,0,0-30.27,0L90.11,81.23,31.08,86a16.46,16.46,0,0,0-9.37,28.86l45,38.83L53,211.75a16.38,16.38,0,0,0,24.5,17.82L128,198.49l50.53,31.08A16.4,16.4,0,0,0,203,211.75l-13.76-58.07,45-38.83A16.43,16.43,0,0,0,239.18,97.26Zm-15.34,5.47-48.7,42a8,8,0,0,0-2.56,7.91l14.88,62.8a.37.37,0,0,1-.17.48c-.18.14-.23.11-.38,0l-54.72-33.65a8,8,0,0,0-8.38,0L69.09,215.94c-.15.09-.19.12-.38,0a.37.37,0,0,1-.17-.48l14.88-62.8a8,8,0,0,0-2.56-7.91l-48.7-42c-.12-.1-.23-.19-.13-.5s.18-.27.33-.29l63.92-5.16A8,8,0,0,0,103,91.86l24.62-59.61c.08-.17.11-.25.35-.25s.27.08.35.25L153,91.86a8,8,0,0,0,6.75,4.92l63.92,5.16c.15,0,.24,0,.33.29S224,102.63,223.84,102.73Z"></path></svg>
				<?php endif; ?>
			<?php endfor; ?>
		</div>
		<span class="shopchop-rating-number"><?php echo number_format( $rating, 1 ); ?> / 5</span>
	</div>
	<?php
}



/**
 * Replace the default review meta (date/author line) with a custom layout
 * that shows the author name and a verified-owner badge inline.
 *
 * @param WP_Comment $comment Current comment.
 */
remove_action( 'woocommerce_review_meta', 'woocommerce_review_display_meta', 10 );
add_action(    'woocommerce_review_meta', 'shopchop_hooked_review_meta',     10 );

function shopchop_hooked_review_meta( $comment ) {
	$verified = wc_review_is_from_verified_owner( $comment->comment_ID );

	if ( '0' === $comment->comment_approved ) {
		echo '<strong class="woocommerce-review__author">' . esc_html( get_comment_author() ) . '</strong><br>';
		echo '<em class="woocommerce-review__awaiting-approval">Your review is awaiting approval</em>';
	} else {
		echo '<strong class="woocommerce-review__author">' . esc_html( get_comment_author() ) . '</strong> ';
		if ( 'yes' === get_option( 'woocommerce_review_rating_verification_label' ) && $verified ) {
			echo '<em class="woocommerce-review__verified verified">(verified owner)</em>';
		}
	}
}



/**
 * Output the review date below the review body.
 *
 * @param WP_Comment $comment Current comment.
 */
add_action( 'woocommerce_review_after_comment_text', 'shopchop_hooked_review_date', 20 );
function shopchop_hooked_review_date( $comment ) { ?>
	<time class="shopchop-review-date"><?php echo esc_html( get_comment_date( wc_date_format() ) ); ?></time>
<?php }



/* =============================================================================
	§ 7  Orders
   ============================================================================= */

/**
 * Format the public-facing order number as TSS-YYMMDD-NNNNN.
 *
 * Example: TSS-250401-00042
 *
 * @param int      $order_id Raw WooCommerce order ID.
 * @param WC_Order $order    Order object.
 * @return string Formatted order number.
 */
add_filter( 'woocommerce_order_number', 'shopchop_professional_order_format', 1, 2 );
function shopchop_professional_order_format( $order_id, $order ) {
	$prefix         = 'TSS';
	$date_created   = $order->get_date_created();
	$formatted_date = $date_created ? $date_created->date( 'ymd' ) : date( 'ymd' );
	$padded_id      = str_pad( $order_id, 5, '0', STR_PAD_LEFT );

	return $prefix . '-' . $formatted_date . '-' . $padded_id;
}



/**
 * Append an automatic customer-facing note when an order is cancelled.
 *
 * @param int      $order_id Order ID.
 * @param WC_Order $order    Order object.
 */
add_action( 'woocommerce_order_status_cancelled', 'shopchop_auto_cancelled_note', 10, 2 );

function shopchop_auto_cancelled_note( $order_id, $order ) {
	$order->add_order_note(
		__( 'This order was cancelled automatically due to a payment timeout or system cancellation.', 'woocommerce' ),
		true // visible to customer
	);
}



/**
 * Append an automatic customer-facing note when an order is completed.
 *
 * @param int      $order_id Order ID.
 * @param WC_Order $order    Order object.
 */
add_action( 'woocommerce_order_status_completed', 'shopchop_auto_completed_note', 10, 2 );

function shopchop_auto_completed_note( $order_id, $order ) {
	$order->add_order_note(
		__( 'Your order is ready! It has been dispatched to the courier for delivery.', 'woocommerce' ),
		true // visible to customer
	);
}



/* =============================================================================
	§ 8  My Account
   ============================================================================= */

/**
 * Insert Wishlist between Dashboard and Orders in My Account nav.
 */
add_filter( 'woocommerce_account_menu_items', function ( $items ) {
	$new_items = array();
	foreach ( $items as $key => $label ) {
		$new_items[ $key ] = $label;
		if ( 'dashboard' === $key ) {
			$new_items['wishlist'] = __( 'Wishlist', 'shopchop' );
		}
	}
	return $new_items;
} );

add_filter( 'woocommerce_get_endpoint_url', function ( $url, $endpoint ) {
	if ( 'wishlist' === $endpoint && function_exists( 'YITH_WCWL' ) ) {
		$page_id = YITH_WCWL()->get_wishlist_page_id();
		if ( $page_id ) {
			$url = get_permalink( $page_id );
		}
	}
	return $url;
}, 10, 2 );

/**
 * Insert a contextual <h1> title at the top of every My Account content area.
 */
add_action( 'woocommerce_account_content', 'shopchop_account_content_title', 1 );
function shopchop_account_content_title() {
	$endpoint_titles = array(
		'orders'       => 'Orders',
		'downloads'    => 'Downloads',
		'view-order'   => 'Order Details',
		'edit-address'  => 'Addresses',
		'edit-account'  => 'Account Details',
		'pool-profile'  => 'Pool Profile',
	);
	
	$title = 'Dashboard'; // default
	foreach ( $endpoint_titles as $endpoint => $label ) {
		if ( is_wc_endpoint_url( $endpoint ) ) {
			$title = $label;
			break;
		}
	}

	echo '<h1 class="account-content-title">' . esc_html( $title ) . '</h1>';
}



/**
 * Wrap all authentication forms (login, register, lost/reset password)
 * in a shared <div class="wc-auth-wrapper"> for consistent styling.
 */
function shopchop_auth_wrapper_start() { echo '<div class="wc-auth-wrapper">'; }
function shopchop_auth_wrapper_end()   { echo '</div>'; }

add_action( 'woocommerce_before_customer_login_form',          'shopchop_auth_wrapper_start', 1 );
add_action( 'woocommerce_after_customer_login_form',           'shopchop_auth_wrapper_end'     );
add_action( 'woocommerce_before_lost_password_form',           'shopchop_auth_wrapper_start', 1 );
add_action( 'woocommerce_after_lost_password_form',            'shopchop_auth_wrapper_end'     );
add_action( 'woocommerce_before_reset_password_form',          'shopchop_auth_wrapper_start', 1 );
add_action( 'woocommerce_after_reset_password_form',           'shopchop_auth_wrapper_end'     );
add_action( 'woocommerce_before_lost_password_confirmation_message', 'shopchop_auth_wrapper_start', 1 );
add_action( 'woocommerce_after_lost_password_confirmation_message',  'shopchop_auth_wrapper_end'     );



/**
 * Redirect bare /login and /register slugs to the WooCommerce My Account page.
 * Only fires for non-logged-in users.
 */
add_action( 'template_redirect', function () {
	if ( is_user_logged_in() ) return;

	$path = trim( parse_url( $_SERVER['REQUEST_URI'], PHP_URL_PATH ), '/' );
	if ( in_array( $path, array( 'login', 'register' ), true ) ) {
		wp_safe_redirect( wc_get_page_permalink( 'myaccount' ) );
		exit;
	}
} );



/* =============================================================================
	§ 9  Demo Store Notice & Checkout Layout
   ============================================================================= */

/**
 * Move the WooCommerce demo-store banner from wp_footer / wp_body_open to a
 * custom action hook (`shopchop_demo_store_wrapper`) placed inside the header.
 */
remove_action( 'wp_footer',    'woocommerce_demo_store', 10 );
remove_action( 'wp_body_open', 'woocommerce_demo_store', 10 );
add_action(    'shopchop_demo_store_wrapper', 'woocommerce_demo_store', 10 );



/**
 * Move the checkout payment block out of the default order-review section and
 * into a custom action hook (`shopchop_checkout_payment`) so the theme can
 * position it freely in the checkout template.
 */
remove_action( 'woocommerce_checkout_order_review', 'woocommerce_checkout_payment', 20 );
add_action(    'shopchop_checkout_payment',          'woocommerce_checkout_payment', 20 );



/* =============================================================================
	§ 10 Authentication
   ============================================================================= */

/**
 * Return a generic error message on failed login to avoid username enumeration.
 *
 * @param WP_User|WP_Error $user     Authentication result.
 * @param string           $username Submitted username.
 * @param string           $password Submitted password.
 * @return WP_User|WP_Error
 */
add_filter( 'authenticate', 'shopchop_remove_login_errors', 20, 3 );
function shopchop_remove_login_errors( $user, $username, $password ) {
	if ( ! empty( $username ) && ! empty( $password ) && is_wp_error( $user ) ) {
		return new WP_Error(
			'authentication_failed',
			__( '<strong>Error</strong>: Invalid username or password. Please try again.' )
		);
	}
	return $user;
}



/* =============================================================================
	§ 11 Thank-You Page
   ============================================================================= */

/**
 * Append a "What to do Next?" section below the standard order confirmation.
 *
 * @param int $order_id The newly placed order's ID.
 */
add_action( 'woocommerce_thankyou', 'shopchop_add_next_steps', 10 );
function shopchop_add_next_steps( $order_id ) {
	$order = wc_get_order( $order_id );
	if ( ! $order ) return;
	?>
	<section class="wc-next-steps-order">
		<h2 class="wc-next-steps-title">Next Steps</h2>
		<ul class="space-y-2">
			<li class="flex items-start gap-2">
				<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 256 256" aria-hidden="true" class="shrink-0 mt-0.5"><path d="M128,96a32,32,0,1,0,32,32A32,32,0,0,0,128,96Zm0,48a16,16,0,1,1,16-16A16,16,0,0,1,128,144Z"></path></svg>
				<span><strong>Order Confirmation:</strong> We'll email you when your order ships.</span>
			</li>
			<li class="flex items-start gap-2">
				<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 256 256" aria-hidden="true" class="shrink-0 mt-0.5"><path d="M128,96a32,32,0,1,0,32,32A32,32,0,0,0,128,96Zm0,48a16,16,0,1,1,16-16A16,16,0,0,1,128,144Z"></path></svg>
				<span><strong>Track Your Package:</strong> We'll email your tracking number once we dispatch.</span>
			</li>
			<li class="flex items-start gap-2">
				<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 256 256" aria-hidden="true" class="shrink-0 mt-0.5"><path d="M128,96a32,32,0,1,0,32,32A32,32,0,0,0,128,96Zm0,48a16,16,0,1,1,16-16A16,16,0,0,1,128,144Z"></path></svg>
				<span><strong>Need help?</strong> Reach us on
				<a href="<?php echo esc_url( 'https://wa.me/' . shopchop_get_whatsapp_number() . '?text=' . rawurlencode( 'Hi, I need help with my order: #' . $order->get_order_number() ) ); ?>" class="underline! font-bold text-primary-900" target="_blank" rel="noreferrer">WhatsApp</a>
				or
				<a href="mailto:<?php echo esc_attr( shopchop_get_shop_email() ); ?>" class="underline! font-bold text-primary-900" rel="noreferrer"><?php echo esc_html( shopchop_get_shop_email() ); ?></a>
				with Order ID <strong><?php echo esc_html( $order->get_order_number() ); ?></strong></span>
			</li>
		</ul>
	</section>
	<?php
}



/* =============================================================================
	§ 12 AJAX Search
   ============================================================================= */

/**
 * Search products by title, description, short description, and meta fields.
 *
 * The handler runs two WP_Query passes:
 *   1. Native `s` search (covers post_title + post_content) with a filter that
 *      also checks post_excerpt (short description).
 *   2. A meta_query covering SKU and other registered meta keys.
 *
 * Results from both passes are deduped, capped at 10, then fetched in a final
 * query that preserves relevance order.
 *
 * Accepts: POST action=wc_search_products, search_term, category, nonce.
 */
function shopchop_search_products() {
	check_ajax_referer( 'wc_ajax_search_nonce', 'nonce', true );

	$search_term = isset( $_POST['search_term'] ) ? sanitize_text_field( $_POST['search_term'] ) : '';
	$category    = isset( $_POST['category'] )    ? sanitize_text_field( $_POST['category'] )    : '';

	if ( empty( $search_term ) || mb_strlen( $search_term ) < 2 ) {
		wp_send_json_success( array( 'products' => array() ) );
		return;
	}

	// Shared tax query (empty when category = 'all').
	$tax_query = array();
	if ( ! empty( $category ) && $category !== 'all' ) {
		$tax_query = array( array(
			'taxonomy' => 'product_cat',
			'field'    => 'slug',
			'terms'    => $category,
		) );
	}

	// ── Pass 1: title + content + excerpt ────────────────────────────────────
	$extend_excerpt = function ( $search, $wp_query ) use ( $search_term ) {
		global $wpdb;
		if ( ! $wp_query->is_search() ) return $search;
		$like    = '%' . $wpdb->esc_like( $search_term ) . '%';
		$search .= $wpdb->prepare( " OR ({$wpdb->posts}.post_excerpt LIKE %s)", $like );
		return $search;
	};
	add_filter( 'posts_search', $extend_excerpt, 10, 2 );

	$content_query = new WP_Query( array(
		'post_type'      => 'product',
		'post_status'    => 'publish',
		'posts_per_page' => 20,
		'fields'         => 'ids',
		's'              => $search_term,
		'tax_query'      => $tax_query,
	) );

	remove_filter( 'posts_search', $extend_excerpt, 10 );
	$ids_content = $content_query->posts;

	// ── Pass 2: meta fields (SKU etc.) ────────────────────────────────────────
	$meta_keys = apply_filters( 'shopchop_search_meta_keys', array(
		'_sku',
		'_short_description',
		'short_description',
	) );

	$meta_clauses = array( 'relation' => 'OR' );
	foreach ( $meta_keys as $key ) {
		$meta_clauses[] = array(
			'key'     => $key,
			'value'   => $search_term,
			'compare' => 'LIKE',
		);
	}

	$meta_query = new WP_Query( array(
		'post_type'      => 'product',
		'post_status'    => 'publish',
		'posts_per_page' => 20,
		'fields'         => 'ids',
		'meta_query'     => $meta_clauses,
		'tax_query'      => $tax_query,
	) );
	$ids_meta = $meta_query->posts;

	// ── Merge, dedup, cap ─────────────────────────────────────────────────────
	$all_ids = array_slice( array_unique( array_merge( $ids_content, $ids_meta ) ), 0, 10 );

	if ( empty( $all_ids ) ) {
		wp_send_json_success( array( 'products' => array() ) );
		return;
	}

	// ── Final fetch ───────────────────────────────────────────────────────────
	$final_query = new WP_Query( array(
		'post_type'      => 'product',
		'post_status'    => 'publish',
		'posts_per_page' => count( $all_ids ),
		'post__in'       => $all_ids,
		'orderby'        => 'post__in',
	) );

	$products = array();
	if ( $final_query->have_posts() ) {
		while ( $final_query->have_posts() ) {
			$final_query->the_post();
			$product = wc_get_product( get_the_ID() );
			if ( ! $product ) continue;

			$products[] = array(
				'id'    => get_the_ID(),
				'title' => get_the_title(),
				'url'   => get_permalink(),
				'image' => get_the_post_thumbnail_url( get_the_ID(), 'thumbnail' ),
				'price' => $product->get_price_html(),
			);
		}
		wp_reset_postdata();
	}

	wp_send_json_success( array( 'products' => $products ) );
}
add_action( 'wp_ajax_wc_search_products',        'shopchop_search_products' );
add_action( 'wp_ajax_nopriv_wc_search_products', 'shopchop_search_products' );



/**
 * Return top-level product categories for the search-bar dropdown.
 *
 * Accepts: POST action=wc_get_categories, nonce.
 */
function shopchop_search_get_cat() {
	check_ajax_referer( 'wc_ajax_search_nonce', 'nonce', true );

	$categories = get_terms( array(
		'taxonomy'   => 'product_cat',
		'hide_empty' => true,
		'parent'     => 0,
	) );

	$cats = array();
	if ( ! empty( $categories ) && ! is_wp_error( $categories ) ) {
		foreach ( $categories as $cat ) {
			$cats[] = array( 'slug' => $cat->slug, 'name' => $cat->name );
		}
	}

	wp_send_json_success( array( 'categories' => $cats ) );
}
add_action( 'wp_ajax_wc_get_categories',        'shopchop_search_get_cat' );
add_action( 'wp_ajax_nopriv_wc_get_categories', 'shopchop_search_get_cat' );



/* =============================================================================
	§ 13 Search Bar Shortcode  [shopchop_search_bar]
   ============================================================================= */

/**
 * Render the search bar HTML.
 *
 * Attribute:
 *   context  "default" | "mobile"  – appended to element IDs to prevent
 *            duplicates when the shortcode is used more than once per page.
 *
 * @param array $atts Shortcode attributes.
 * @return string Shortcode HTML.
 */
function shopchop_search_bar_shortcode( $atts ) {
	$atts = shortcode_atts( array( 'context' => 'default' ), $atts, 'shopchop_search_bar' );

	$input_id  = 'shopchop-search-input__' . esc_attr( $atts['context'] );
	$select_id = 'shopchop-cat-select__'   . esc_attr( $atts['context'] );

	ob_start(); ?>
	<div class="shopchop-search-wrapper">
		<div class="shopchop-search-bar">
			<input type="text" class="shopchop-search-input" id="<?php echo $input_id; ?>" placeholder="Search Here..." autocomplete="off">
			<select class="shopchop-cat-select" id="<?php echo $select_id; ?>">
				<option value="all">All Products</option>
			</select>
		</div>
		<div class="shopchop-search-results" role="listbox" aria-label="<?php esc_attr_e( 'Search results', 'shopchop' ); ?>" style="display:none;"></div>
	</div>
	<?php return ob_get_clean();
}
add_shortcode( 'shopchop_search_bar', 'shopchop_search_bar_shortcode' );



/* =============================================================================
	§ 14 Mini Cart AJAX
   ============================================================================= */

/**
 * Return the current mini-cart HTML, count, and totals.
 *
 * Accepts: POST action=shopchop_get_mini_cart, nonce.
 */
function shopchop_get_mini_cart() {
	check_ajax_referer( 'wc_ajax_search_nonce', 'nonce', true );

	ob_start();
	woocommerce_mini_cart();
	$mini_cart = str_replace( "\xEF\xBB\xBF", '', trim( ob_get_clean() ) );

	wp_send_json_success( array(
		'cart_html'     => $mini_cart,
		'cart_count'    => WC()->cart->get_cart_contents_count(),
		'cart_total'    => WC()->cart->get_cart_total(),
		'cart_subtotal' => WC()->cart->get_cart_subtotal(),
		'cart_is_empty' => WC()->cart->is_empty(),
	) );
}
add_action( 'wp_ajax_shopchop_get_mini_cart',        'shopchop_get_mini_cart' );
add_action( 'wp_ajax_nopriv_shopchop_get_mini_cart', 'shopchop_get_mini_cart' );



/**
 * Remove a single item from the cart and return the refreshed mini-cart.
 *
 * Accepts: POST action=shopchop_remove_cart_item, cart_item_key, nonce.
 */
function shopchop_remove_cart_item() {
	check_ajax_referer( 'shopchop_cart_nonce', 'nonce', true );

	$cart_item_key = isset( $_POST['cart_item_key'] ) ? sanitize_text_field( $_POST['cart_item_key'] ) : '';

	if ( empty( $cart_item_key ) ) {
		wp_send_json_error( array( 'message' => 'Invalid cart item' ) );
		return;
	}

	WC()->cart->remove_cart_item( $cart_item_key );

	ob_start();
	woocommerce_mini_cart();
	$mini_cart = str_replace( "\xEF\xBB\xBF", '', trim( ob_get_clean() ) );

	wp_send_json_success( array(
		'cart_html'     => $mini_cart,
		'cart_count'    => WC()->cart->get_cart_contents_count(),
		'cart_total'    => WC()->cart->get_cart_total(),
		'cart_is_empty' => WC()->cart->is_empty(),
		'message'       => 'Item removed from cart',
	) );
}
add_action( 'wp_ajax_shopchop_remove_cart_item',        'shopchop_remove_cart_item' );
add_action( 'wp_ajax_nopriv_shopchop_remove_cart_item', 'shopchop_remove_cart_item' ); // guests can have a cart session



/**
 * Push cart-count fragments so WooCommerce's fragment system keeps
 * the badge and item-count text in sync after any cart mutation.
 *
 * @param array $fragments Existing fragments.
 * @return array Updated fragments.
 */
function shopchop_cart_fragments( $fragments ) {
	$count = WC()->cart->get_cart_contents_count();
	$word  = $count === 1 ? 'item' : 'items';

	ob_start(); ?>
	<span class="cart-count-badge" aria-live="polite" aria-atomic="true" aria-label="<?php echo esc_attr( sprintf( _n( '%d item in cart', '%d items in cart', $count, 'shopchop' ), $count ) ); ?>"><?php echo $count >= 0 ? $count : ''; ?></span>
	<?php $fragments['.cart-count-badge'] = ob_get_clean();

	ob_start(); ?>
	<span class="cart-items-count"><span class="count-number"><?php echo $count; ?></span> <?php echo $word; ?></span>
	<?php $fragments['.cart-items-count'] = ob_get_clean();

	return $fragments;
}
add_filter( 'woocommerce_add_to_cart_fragments', 'shopchop_cart_fragments' );



/* =============================================================================
	§ 15 Mini Cart Shortcodes
   ============================================================================= */

/**
 * [shopchop_mini_cart]
 * Renders the desktop cart trigger button and dropdown shell.
 * Cart contents are injected via AJAX (see ShopChop.CartDropdown in script.js).
 *
 * @return string Shortcode HTML.
 */
function shopchop_mini_cart_shortcode() {
	$count = WC()->cart->get_cart_contents_count();
	$word  = $count === 1 ? 'item' : 'items';

	ob_start(); ?>
	<div class="shopchop-cart-wrapper">
		<a href="<?php echo wc_get_cart_url(); ?>" class="shopchop-cart-trigger" aria-label="Shopping Cart" aria-expanded="false">
			<div class="cart-icon-wrapper">
				<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 256 256" class="cart-icon"><path d="M230.14,58.87A8,8,0,0,0,224,56H62.68L56.6,22.57A8,8,0,0,0,48.73,16H24a8,8,0,0,0,0,16h18L67.56,172.29a24,24,0,0,0,5.33,11.27,28,28,0,1,0,44.4,8.44h45.42A27.75,27.75,0,0,0,160,204a28,28,0,1,0,28-28H91.17a8,8,0,0,1-7.87-6.57L80.13,152h116a24,24,0,0,0,23.61-19.71l12.16-66.86A8,8,0,0,0,230.14,58.87ZM104,204a12,12,0,1,1-12-12A12,12,0,0,1,104,204Zm96,0a12,12,0,1,1-12-12A12,12,0,0,1,200,204Zm4-74.57A8,8,0,0,1,196.1,136H77.22L65.59,72H214.41Z"></path></svg>
				<span class="cart-count-badge"><?php echo $count >= 0 ? $count : ''; ?></span>
			</div>
			<span class="cart-label">Cart</span>
		</a>

		<div class="shopchop-cart-dropdown" style="display:none;">
			<div class="cart-dropdown-header">
				<h3>Shopping Cart</h3>
				<span class="cart-items-count"><span class="count-number"><?php echo $count; ?></span> <?php echo $word; ?></span>
			</div>
			<div class="cart-dropdown-content">
				<div class="cart-loading">Loading cart...</div>
			</div>
		</div>
	</div>
	<?php return ob_get_clean();
}
add_shortcode( 'shopchop_mini_cart', 'shopchop_mini_cart_shortcode' );



/**
 * [shopchop_mobile_cart_icon_display]
 * Renders only the cart icon + badge (used in the mobile header bar).
 *
 * @return string Shortcode HTML.
 */
function shopchop_mobile_cart_icon() {
	$count = WC()->cart->get_cart_contents_count();
	ob_start(); ?>
	<div class="cart-icon-wrapper">
		<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 256 256" class="cart-icon" aria-hidden="true"><path d="M230.14,58.87A8,8,0,0,0,224,56H62.68L56.6,22.57A8,8,0,0,0,48.73,16H24a8,8,0,0,0,0,16h18L67.56,172.29a24,24,0,0,0,5.33,11.27,28,28,0,1,0,44.4,8.44h45.42A27.75,27.75,0,0,0,160,204a28,28,0,1,0,28-28H91.17a8,8,0,0,1-7.87-6.57L80.13,152h116a24,24,0,0,0,23.61-19.71l12.16-66.86A8,8,0,0,0,230.14,58.87ZM104,204a12,12,0,1,1-12-12A12,12,0,0,1,104,204Zm96,0a12,12,0,1,1-12-12A12,12,0,0,1,200,204Zm4-74.57A8,8,0,0,1,196.1,136H77.22L65.59,72H214.41Z"></path></svg>
		<span class="cart-count-badge"><?php echo $count >= 0 ? $count : ''; ?></span>
	</div>
	<?php return ob_get_clean();
}
add_shortcode( 'shopchop_mobile_cart_icon_display', 'shopchop_mobile_cart_icon' );



/**
 * [shopchop_mobile_cart_details_display]
 * Renders the mobile cart drawer header + content shell.
 * Cart contents are injected via AJAX (see ShopChop.MobileCart in script.js).
 *
 * @return string Shortcode HTML.
 */
function shopchop_mobile_cart_details() {
	$count = WC()->cart->get_cart_contents_count();
	$word  = $count === 1 ? 'item' : 'items';

	ob_start(); ?>
	<div class="mobile-cart-header">
		<h3>Cart
			<span>(<span class="cart-items-count"><span class="count-number"><?php echo $count; ?></span> <?php echo $word; ?></span>)</span>
		</h3>
		<button id="cart-close" aria-label="Close cart">
			<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 256 256" aria-hidden="true"><path d="M205.66,194.34a8,8,0,0,1-11.32,11.32L128,139.31,61.66,205.66a8,8,0,0,1-11.32-11.32L116.69,128,50.34,61.66A8,8,0,0,1,61.66,50.34L128,116.69l66.34-66.35a8,8,0,0,1,11.32,11.32L139.31,128Z"></path></svg>
		</button>
	</div>
	<div class="mobile-cart-content">
		<div class="cart-loading">Loading cart...</div>
	</div>
	<?php return ob_get_clean();
}
add_shortcode( 'shopchop_mobile_cart_details_display', 'shopchop_mobile_cart_details' );



/* =============================================================================
	§ 16 Custom Stock Statuses
   ============================================================================= */

/**
 * Register "Pre-Order" and "Coming Soon" stock statuses and remove "On Backorder".
 *
 * @param array $statuses Registered stock statuses.
 * @return array Modified statuses.
 */
add_filter( 'woocommerce_product_stock_status_options', 'shopchop_custom_stock_status' );
function shopchop_custom_stock_status( $statuses ) {
	unset( $statuses['onbackorder'] );
	$statuses['pre_order']   = __( 'Pre-Order',   'shopchop' );
	$statuses['coming_soon'] = __( 'Coming Soon', 'shopchop' );
	return $statuses;
}



/**
 * Variable products: WooCommerce's own stock-status sync only understands
 * instock/outofstock/onbackorder, so a variable product whose variations are
 * all "Pre-Order" (or "Coming Soon") gets its parent stamped "outofstock" —
 * core has no idea those custom statuses exist. Recompute the parent's
 * status live from its variations instead of trusting the stored meta.
 *
 * Priority when variations disagree: instock > pre_order > coming_soon > outofstock.
 *
 * @param string     $status  Stored stock status.
 * @param WC_Product $product Current product.
 * @return string
 */
add_filter( 'woocommerce_product_get_stock_status', 'shopchop_variable_stock_status_from_children', 10, 2 );
function shopchop_variable_stock_status_from_children( $status, $product ) {
	if ( ! $product->is_type( 'variable' ) ) {
		return $status;
	}

	$children_statuses = array();
	foreach ( $product->get_children() as $variation_id ) {
		$variation = wc_get_product( $variation_id );
		if ( $variation ) {
			$children_statuses[] = $variation->get_stock_status();
		}
	}

	if ( empty( $children_statuses ) ) {
		return $status;
	}

	foreach ( array( 'instock', 'pre_order', 'coming_soon' ) as $priority_status ) {
		if ( in_array( $priority_status, $children_statuses, true ) ) {
			return $priority_status;
		}
	}

	return 'outofstock';
}



/**
 * Replace the frontend stock HTML for custom statuses.
 *
 * @param string     $html    Default stock HTML.
 * @param WC_Product $product Current product.
 * @return string Modified stock HTML.
 */
add_filter( 'woocommerce_get_stock_html', 'shopchop_custom_stock_status_display', 10, 2 );
function shopchop_custom_stock_status_display( $html, $product ) {
	$status = $product->get_stock_status();

	if ( $status === 'pre_order' ) {
		return '<p class="stock pre-order">'   . __( 'Pre-Order',   'shopchop' ) . '</p>';
	}
	if ( $status === 'coming_soon' ) {
		return '<p class="stock coming-soon">' . __( 'Coming Soon', 'shopchop' ) . '</p>';
	}

	return $html;
}



/**
 * Block purchase for Coming Soon products.
 *
 * @param bool       $purchasable Current purchasable state.
 * @param WC_Product $product     Current product.
 * @return bool
 */
add_filter( 'woocommerce_is_purchasable', 'shopchop_custom_status_purchasable', 10, 2 );
function shopchop_custom_status_purchasable( $purchasable, $product ) {
	return ( $product->get_stock_status() === 'coming_soon' ) ? false : $purchasable;
}



/**
 * On the single product page, hide the default Add to Cart button and show
 * a "Coming Soon" label instead.
 */
add_action( 'woocommerce_single_product_summary', 'shopchop_hide_add_to_cart_coming_soon', 1 );
function shopchop_hide_add_to_cart_coming_soon() {
	global $product;
	if ( $product && $product->get_stock_status() === 'coming_soon' ) {
		remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_add_to_cart', 30 );
	}
}



add_action( 'woocommerce_single_product_summary', 'shopchop_coming_soon_button', 31 );
function shopchop_coming_soon_button() {
	global $product;
	if ( $product->get_stock_status() === 'coming_soon' ) {
		echo '<p class="stock coming-soon">' . __( 'Coming Soon', 'shopchop' ) . '</p>';
	}
}



// Replace the loop Add to Cart link with a Coming Soon label in product grids.
add_filter( 'woocommerce_loop_add_to_cart_link', function ( $html, $product ) {
	if ( $product->get_stock_status() === 'coming_soon' ) {
		return '<p class="stock coming-soon">' . __( 'Coming Soon', 'shopchop' ) . '</p>';
	}
	return $html;
}, 10, 2 );



/**
 * Show a notice on checkout if the cart contains any Pre-Order items.
 */
add_action( 'woocommerce_before_checkout_form', 'shopchop_preorder_checkout_notice' );
function shopchop_preorder_checkout_notice() {
	foreach ( WC()->cart->get_cart() as $cart_item ) {
		$product = $cart_item['data'];
		if ( $product && $product->get_stock_status() === 'pre_order' ) {
			wc_print_notice(
				__( 'Your cart contains a Pre-Order item. Pre-order items will be shipped as soon as stock is available.', 'shopchop' ),
				'notice'
			);
			break;
		}
	}
}



/**
 * Append a Pre-Order badge to item names in order emails and My Account order details.
 *
 * $is_visible = false in email context; true when product link is rendered on frontend.
 *
 * @param string                $name       Item name.
 * @param WC_Order_Item_Product $item       Order item.
 * @param bool                  $is_visible Whether a product link is being rendered.
 * @return string
 */
add_filter( 'woocommerce_order_item_name', 'shopchop_preorder_item_name_badge', 10, 3 );
function shopchop_preorder_item_name_badge( $name, $item, $is_visible ) {
	$product = $item->get_product();
	if ( ! $product || $product->get_stock_status() !== 'pre_order' ) {
		return $name;
	}

	$badge = '<span class="preorder-badge">'
		. __( 'Pre-Order', 'shopchop' )
		. '</span>';

	return $name . $badge;
}



/**
 * Admin product list customizations (stock status HTML + list CSS) moved to
 * the shopchop-theme-settings plugin: includes/class-admin-product-columns.php
 */



/* =============================================================================
   § 17 — Recently Viewed Products (single product pages)
   ============================================================================= */

define( 'SHOPCHOP_RECENTLY_VIEWED_MAX', 6 );
define( 'SHOPCHOP_RECENTLY_VIEWED_COOKIE', 'shopchop_recently_viewed' );

/**
 * Write current product ID into the recently-viewed cookie on every product page visit.
 */
add_action( 'template_redirect', 'shopchop_track_recently_viewed' );
function shopchop_track_recently_viewed() {
	if ( ! is_product() ) {
		return;
	}

	$product_id = get_the_ID();
	$viewed     = isset( $_COOKIE[ SHOPCHOP_RECENTLY_VIEWED_COOKIE ] )
		? array_map( 'absint', explode( '|', sanitize_text_field( wp_unslash( $_COOKIE[ SHOPCHOP_RECENTLY_VIEWED_COOKIE ] ) ) ) )
		: array();

	// Move current ID to front, remove duplicates, cap at max.
	$viewed = array_filter( $viewed, fn( $id ) => $id !== $product_id );
	array_unshift( $viewed, $product_id );
	$viewed = array_slice( $viewed, 0, SHOPCHOP_RECENTLY_VIEWED_MAX );

	setcookie(
		SHOPCHOP_RECENTLY_VIEWED_COOKIE,
		implode( '|', $viewed ),
		time() + ( 30 * DAY_IN_SECONDS ),
		COOKIEPATH,
		COOKIE_DOMAIN,
		is_ssl(),
		false
	);
}

/**
 * Render the Recently Viewed section on single product pages.
 * Hooked between tabs (10) and related products (20).
 */
add_action( 'woocommerce_after_single_product_summary', 'shopchop_recently_viewed_section', 15 );
function shopchop_recently_viewed_section() {
	if ( ! isset( $_COOKIE[ SHOPCHOP_RECENTLY_VIEWED_COOKIE ] ) ) {
		return;
	}

	$viewed = array_map( 'absint', explode( '|', sanitize_text_field( wp_unslash( $_COOKIE[ SHOPCHOP_RECENTLY_VIEWED_COOKIE ] ) ) ) );

	// Exclude the product currently being viewed.
	$viewed = array_filter( $viewed, fn( $id ) => $id !== get_the_ID() );
	$viewed = array_values( array_slice( $viewed, 0, 4 ) );

	if ( empty( $viewed ) ) {
		return;
	}

	$args = array(
		'post_type'           => 'product',
		'post__in'            => $viewed,
		'orderby'             => 'post__in',
		'posts_per_page'      => 4,
		'post_status'         => 'publish',
		'ignore_sticky_posts' => true,
	);

	$query = new WP_Query( $args );

	if ( ! $query->have_posts() ) {
		return;
	}
	?>
	<section class="shopchop-recently-viewed">
		<h2 class="recently-viewed-heading">
			<?php esc_html_e( 'Recently Viewed', 'shopchop' ); ?>
		</h2>
		<?php
		woocommerce_product_loop_start();

		while ( $query->have_posts() ) {
			$query->the_post();
			wc_get_template_part( 'content', 'product' );
		}

		wp_reset_postdata();

		woocommerce_product_loop_end();
		?>
	</section>
	<?php
}



/* =============================================================================
   § 18 — WhatsApp Floating Button (single product pages)
   ============================================================================= */

/**
 * Get the shop's WhatsApp number from ShopChop general settings.
 */
function shopchop_get_whatsapp_number() {
	if ( class_exists( 'ShopChop_General_Settings' ) ) {
		$phone = ShopChop_General_Settings::get( 'shop_phone' );
		if ( $phone ) {
			return $phone;
		}
	}
	return '60123456789';
}

/**
 * Get the shop's contact email from ShopChop general settings.
 */
function shopchop_get_shop_email() {
	if ( class_exists( 'ShopChop_General_Settings' ) ) {
		$email = ShopChop_General_Settings::get( 'shop_email' );
		if ( $email ) {
			return $email;
		}
	}
	return get_option( 'woocommerce_email_from_address' );
}

/**
 * Inject the floating WhatsApp button on single product pages.
 * Hooked late into wp_footer so it renders after all page content.
 */
add_action( 'wp_footer', 'shopchop_whatsapp_button', 5 );
function shopchop_whatsapp_button() {
	if ( ! is_product() ) {
		return;
	}

	global $product;
	if ( ! $product ) {
		return;
	}

	$name    = get_the_title();
	if ( $product->is_type( 'variable' ) ) {
		$min = $product->get_variation_price( 'min' );
		$max = $product->get_variation_price( 'max' );
		$price = html_entity_decode( wp_strip_all_tags( wc_price( $min ) ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
		if ( $min !== $max ) {
			$price .= ' - ' . html_entity_decode( wp_strip_all_tags( wc_price( $max ) ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
		}
	} else {
		$price = html_entity_decode( wp_strip_all_tags( wc_price( (float) $product->get_price() ) ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
	}
	$url     = get_permalink();
	$message = "Hi, I'm interested in {$name} ({$price})" . "\n" . $url;

	$wa_url = 'https://wa.me/' . shopchop_get_whatsapp_number() . '?text=' . rawurlencode( $message );
	?>
	<a
		id="shopchop-whatsapp-btn"
		href="<?php echo esc_attr( $wa_url ); ?>"
		target="_blank"
		rel="noopener noreferrer"
		aria-label="<?php esc_attr_e( 'Chat on WhatsApp', 'shopchop' ); ?>"
		title="<?php esc_attr_e( 'Chat on WhatsApp', 'shopchop' ); ?>"
		data-wa-number="<?php echo esc_attr( shopchop_get_whatsapp_number() ); ?>"
		data-product-name="<?php echo esc_attr( $name ); ?>"
		data-product-url="<?php echo esc_attr( $url ); ?>"
	>
		<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
			<path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
		</svg>
		<span><?php esc_html_e( 'Chat', 'shopchop' ); ?></span>
	</a>
	<?php
}



/* =============================================================================
   § 19 — Admin Login Page Styling
   ============================================================================= */

/**
 * Enqueue custom CSS on the WordPress login screen.
 * Same WP auth — purely cosmetic override.
 */
add_action( 'login_enqueue_scripts', 'shopchop_login_styles' );
function shopchop_login_styles() {
	$logo_url = get_template_directory_uri() . '/assets/images/logo.png';
	?>
	<style>
		/* ── Page ── */
		body.login {
			background: #f8fafc;
			font-family: 'Manrope', sans-serif;
		}

		/* ── Logo area ── */
		#login h1 a {
			background-image: url(<?php echo esc_url( $logo_url ); ?>);
			background-color: transparent;
			background-repeat: no-repeat;
			background-position: center;
			background-size: contain;
			width: 200px;
			height: 80px;
		}

		/* ── Card ── */
		#loginform,
		#lostpasswordform,
		#registerform {
			background: #ffffff;
			border: 1px solid #e2e8f0;
			border-radius: 12px;
			box-shadow: 0 4px 24px rgba(0,0,0,.06);
			padding: 32px 36px;
			margin-top: 16px;
		}

		/* ── Labels ── */
		#loginform label,
		#lostpasswordform label {
			font-family: 'Manrope', sans-serif;
			font-size: .8125rem;
			font-weight: 600;
			color: #475569;
			text-transform: uppercase;
			letter-spacing: .04em;
		}

		/* ── Inputs ── */
		#loginform input[type="text"],
		#loginform input[type="password"],
		#lostpasswordform input[type="text"] {
			font-family: 'Manrope', sans-serif;
			border: 1.5px solid #e2e8f0;
			border-radius: 8px;
			padding: 10px 14px;
			font-size: .9375rem;
			color: #0f172a;
			box-shadow: none;
			transition: border-color .15s ease;
		}
		#loginform input[type="text"]:focus,
		#loginform input[type="password"]:focus,
		#lostpasswordform input[type="text"]:focus {
			border-color: #3b82f6;
			box-shadow: 0 0 0 3px rgba(59,130,246,.15);
			outline: none;
		}

		/* ── Submit button ── */
		#loginform .button-primary,
		#lostpasswordform .button-primary {
			font-family: 'Manrope', sans-serif;
			font-weight: 700;
			font-size: .9375rem;
			background: #0f172a;
			border-color: #0f172a;
			border-radius: 8px;
			padding: 10px 20px;
			height: auto;
			line-height: 1.4;
			box-shadow: none;
			text-shadow: none;
			transition: background .15s ease, border-color .15s ease;
		}
		#loginform .button-primary:hover,
		#loginform .button-primary:focus,
		#lostpasswordform .button-primary:hover,
		#lostpasswordform .button-primary:focus {
			background: #1e293b;
			border-color: #1e293b;
			box-shadow: none;
		}

		/* ── Remember me ── */
		#loginform .forgetmenot label {
			font-size: .875rem;
			font-weight: 500;
			text-transform: none;
			letter-spacing: 0;
			color: #64748b;
		}

		/* ── Back / nav links ── */
		#nav a,
		#backtoblog a {
			font-family: 'Manrope', sans-serif;
			font-size: .8125rem;
			color: #64748b;
		}
		#nav a:hover,
		#backtoblog a:hover {
			color: #0f172a;
		}

		/* ── Error / success notices ── */
		#login_error,
		.message {
			font-family: 'Manrope', sans-serif;
			border-radius: 8px;
			border-left-width: 4px;
			font-size: .875rem;
		}
	</style>
	<link rel="preconnect" href="https://fonts.bunny.net">
	<link rel="stylesheet" href="https://fonts.bunny.net/css?family=manrope:400,600,700,800">
	<?php
}

/** Point the login logo link back to the site home. */
add_filter( 'login_headerurl', fn() => home_url() );

/** Update the logo link title attribute. */
add_filter( 'login_headertext', fn() => get_bloginfo( 'name' ) );
