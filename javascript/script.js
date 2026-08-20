/**
 * ShopChop – Main JavaScript
 *
 * Refactored & optimised for WooCommerce.
 *
 * Key improvements:
 *  - Single IIFE wrapping the entire file → one jQuery alias, no global leaks
 *  - `ShopChop` namespace keeps every module discoverable & tree-shakeable
 *  - `Utils`   – shared debounce + labels helper
 *  - `CartAPI` – single source of truth for all mini-cart AJAX calls
 *  - `createDropdown` factory – eliminates duplicated account / cart dropdown code
 *  - `createMiniCart`  factory – eliminates duplicated desktop / mobile cart logic
 *  - `const` / `let` throughout; no `var`
 *  - All 16 modules boot from a single `$(function () { … })` call
 */

(function ($) {
	'use strict';

	/* =========================================================================
        Namespace
    ========================================================================= */
	window.ShopChop = window.ShopChop || {};

	/* =========================================================================
        Utils
    ========================================================================= */
	const Utils = {
		/**
		 * Delay `fn` until `delay` ms after the last call.
		 * @param {Function} fn
		 * @param {number}   delay  ms
		 * @returns {Function}
		 */
		debounce(fn, delay) {
			let timer;
			return function (...args) {
				clearTimeout(timer);
				timer = setTimeout(() => fn.apply(this, args), delay);
			};
		},

		/**
		 * Build the "N item(s)" label used in cart headers.
		 * @param {number} count
		 * @returns {string}
		 */
		itemsLabel(count) {
			const word = count === 1 ? 'item' : 'items';
			return `<span class="count-number">${count}</span> ${word}`;
		},

		escapeHtml(str) {
			return String(str)
				.replace(/&/g, '&amp;')
				.replace(/</g, '&lt;')
				.replace(/>/g, '&gt;')
				.replace(/"/g, '&quot;')
				.replace(/'/g, '&#39;');
		},
	};

	/* =========================================================================
        CartAPI – shared AJAX helpers
    ========================================================================= */
	const CartAPI = {
		/**
		 * Fetch current mini-cart HTML + count.
		 * @param {object} callbacks  jQuery AJAX callbacks (beforeSend, success, error)
		 */
		getMiniCart(callbacks = {}) {
			return $.ajax({
				url: shopchopDynamicSearch.ajax_url,
				type: 'POST',
				data: {
					action: 'shopchop_get_mini_cart',
					nonce: shopchopDynamicSearch.nonce,
				},
				...callbacks,
			});
		},

		/**
		 * Remove a single item from the cart.
		 * @param {string} cartItemKey
		 * @param {object} callbacks
		 */
		removeItem(cartItemKey, callbacks = {}) {
			return $.ajax({
				url: shopchopDynamicSearch.ajax_url,
				type: 'POST',
				data: {
					action: 'shopchop_remove_cart_item',
					cart_item_key: cartItemKey,
					nonce: shopchopDynamicSearch.cart_nonce,
				},
				...callbacks,
			});
		},
	};

	/* =========================================================================
        createDropdown – factory
        Shared hover / click / keyboard / outside-click logic used by both the
        Account dropdown and the Cart dropdown.

        @param {object} opts
        .wrapper   {jQuery}    outermost element (hover target)
        .trigger   {jQuery}    button that toggles the dropdown
        .dropdown  {jQuery}    panel to show/hide
        .onShow    {Function?} called before the panel fades in (optional)
    ========================================================================= */
	function createDropdown({ wrapper, trigger, dropdown, onShow }) {
		const DELAY_IN = 200; // ms before opening on hover
		const DELAY_OUT = 300; // ms before closing after mouse leaves

		let hoverTimer;
		let isHovering = false;

		function show() {
			if (onShow) onShow();
			dropdown.fadeIn(200);
			trigger.attr('aria-expanded', 'true');
			wrapper.addClass('dropdown-active');
		}

		function hide() {
			dropdown.fadeOut(200);
			trigger.attr('aria-expanded', 'false');
			wrapper.removeClass('dropdown-active');
		}

		function toggle() {
			dropdown.is(':visible') ? hide() : show();
		}

		// ── Hover (desktop) ───────────────────────────────────────────────
		wrapper.on('mouseenter', () => {
			isHovering = true;
			clearTimeout(hoverTimer);
			hoverTimer = setTimeout(() => {
				if (isHovering) show();
			}, DELAY_IN);
		});

		wrapper.on('mouseleave', () => {
			isHovering = false;
			clearTimeout(hoverTimer);
			hoverTimer = setTimeout(() => {
				if (!isHovering) hide();
			}, DELAY_OUT);
		});

		// ── Click → navigate to href (hover already handles open/close) ──
		// Do nothing — let the <a> href fire naturally.

		// ── Close when clicking outside ───────────────────────────────────
		$(document).on('click', (e) => {
			if (!$(e.target).closest(wrapper).length) hide();
		});

		// ── Close on Escape ───────────────────────────────────────────────
		$(document).on('keydown', (e) => {
			if (e.key === 'Escape') hide();
		});

		return { show, hide, toggle, isHovering: () => isHovering };
	}

	/* =========================================================================
        createMiniCart – factory
        Shared AJAX load + remove logic used by both the desktop dropdown cart
        and the mobile drawer cart.

        @param {object} opts
        .contentEl      {jQuery}    element that receives the cart HTML
        .onCountUpdate  {Function}  called with (count) after every refresh
    ========================================================================= */
	function createMiniCart({ contentEl, onCountUpdate }) {
		let isLoaded = false;

		function updateCount(count) {
			onCountUpdate(count);
		}

		function attachRemoveHandlers() {
			contentEl
				.find('.remove, .remove_from_cart_button')
				.off('click.shopchop')
				.on('click.shopchop', function (e) {
					e.preventDefault();
					const $link = $(this);
					const key =
						$link.data('cart_item_key') ||
						$link.attr('data-cart_item_key');
					if (key)
						removeItem(
							key,
							$link.closest(
								'.woocommerce-mini-cart-item, .mini_cart_item'
							)
						);
				});
		}

		function load() {
			CartAPI.getMiniCart({
				beforeSend() {
					contentEl.html(
						'<div class="cart-loading">Loading cart…</div>'
					);
				},
				success(response) {
					if (!response.success) return;
					contentEl.html(response.data.cart_html);
					updateCount(response.data.cart_count);
					attachRemoveHandlers();
					isLoaded = true;
				},
				error() {
					contentEl.html(
						'<div class="cart-error">Error loading cart. Please refresh.</div>'
					);
				},
			});
		}

		function removeItem(key, $el) {
			CartAPI.removeItem(key, {
				beforeSend() {
					$el.addClass('removing').css('opacity', '0.5');
				},
				success(response) {
					if (!response.success) return;
					contentEl.html(response.data.cart_html);
					updateCount(response.data.cart_count);
					attachRemoveHandlers();
					// Keep WooCommerce fragment system in sync
					$(document.body)
						.trigger('wc_fragment_refresh')
						.trigger('removed_from_cart');
				},
				error() {
					$el.removeClass('removing').css('opacity', '1');
					ShopChop.Toast.show('Failed to remove item. Please try again.', 'error');
				},
			});
		}

		return {
			load,
			markStale() {
				isLoaded = false;
			},
			getIsLoaded() {
				return isLoaded;
			},
		};
	}

	/* =========================================================================
        1. Product Slider  (Elementor + SwiperJS)
    ========================================================================= */
	ShopChop.ProductSlider = {
		init($scope) {
			if (typeof Swiper === 'undefined') return;
			$scope.find('.shopchop-product-slider').each(function () {
				new Swiper(this, {
					slidesPerView: 2,
					spaceBetween: 20,
					breakpoints: {
						768: { slidesPerView: 3 },
						1024: { slidesPerView: 4 },
					},
					scrollbar: {
						el: this.querySelector('.swiper-scrollbar'),
						draggable: true,
					},
				});
			});
		},
	};

	// Register with Elementor outside the main ready block
	$(window).on('elementor/frontend/init', () => {
		elementorFrontend.hooks.addAction(
			'frontend/element_ready/shopchop_products_list.default',
			ShopChop.ProductSlider.init
		);
	});

	/* =========================================================================
        2A. Hero Carousel  (SwiperJS)
    ========================================================================= */
	ShopChop.HeroCarousel = {
		init() {
			if (typeof Swiper === 'undefined') return;
			document.querySelectorAll('.shopchop-hero-swiper[data-swiper]').forEach((el) => {
				try {
					const config = JSON.parse(el.dataset.swiper);
					new Swiper(el, config);
				} catch (e) {
					console.warn('ShopChop HeroCarousel: invalid config', e);
				}
			});
		},
	};

	/* =========================================================================
        2B. Products Carousel  (SwiperJS)
    ========================================================================= */
	ShopChop.ProductsCarousel = {
		init() {
			if (typeof Swiper === 'undefined') return;
			document.querySelectorAll('.shopchop-products-swiper[data-swiper]').forEach((el) => {
				try {
					const config = JSON.parse(el.dataset.swiper);
					// Pagination/nav live in .products-carousel-footer, a sibling of
					// .swiper, not a descendant — Swiper's selector-string resolution
					// only looks inside the swiper container, so resolve real elements
					// here and pass them directly instead.
					const scope = el.closest('.shopchop-products-carousel') || el.parentElement;

					if (config.pagination && scope) {
						const paginationEl = scope.querySelector('.swiper-pagination');
						if (paginationEl) config.pagination.el = paginationEl;
					}

					if (config.navigation && scope) {
						const nextEl = scope.querySelector('.swiper-button-next');
						const prevEl = scope.querySelector('.swiper-button-prev');
						if (nextEl) config.navigation.nextEl = nextEl;
						if (prevEl) config.navigation.prevEl = prevEl;
					}

					new Swiper(el, config);
				} catch (e) {
					console.warn('ShopChop ProductsCarousel: invalid config', e);
				}
			});
		},
	};

	/* =========================================================================
        2C. Testimonials Carousel  (SwiperJS)
    ========================================================================= */
	ShopChop.TestimonialsCarousel = {
		init() {
			if (typeof Swiper === 'undefined') return;
			document.querySelectorAll('.shopchop-testimonials-swiper[data-swiper]').forEach((el) => {
				try {
					const config = JSON.parse(el.dataset.swiper);
					new Swiper(el, config);
				} catch (e) {
					console.warn('ShopChop TestimonialsCarousel: invalid config', e);
				}
			});
		},
	};

	/* =========================================================================
        2D. Product Gallery  (SwiperJS)
    ========================================================================= */
	ShopChop.ProductGallery = {
		init() {
			const galleryEl = document.querySelector(
				'.woocommerce-product-gallery'
			);
			if (!galleryEl) return;

			// Gallery starts at opacity:0 (inline style) to avoid a flash of
			// unstyled slides before Swiper mounts. If Swiper never loads/inits,
			// reveal it anyway so the image isn't permanently invisible.
			if (typeof Swiper === 'undefined') {
				galleryEl.style.opacity = '1';
				return;
			}

			const mainEl = galleryEl.querySelector('.splashshop-gallery-main');
			const thumbsEl = galleryEl.querySelector(
				'.splashshop-gallery-thumbs'
			);
			if (!mainEl) {
				galleryEl.style.opacity = '1';
				return;
			}

			let thumbsSwiper = null;
			if (thumbsEl) {
				thumbsSwiper = new Swiper(thumbsEl, {
					slidesPerView: 'auto',
					spaceBetween: 8,
					watchSlidesProgress: true,
					freeMode: true,
				});
			}

			new Swiper(mainEl, {
				spaceBetween: 0,
				navigation: thumbsEl
					? {
							nextEl: mainEl.querySelector(
								'.swiper-button-next'
							),
							prevEl: mainEl.querySelector(
								'.swiper-button-prev'
							),
						}
					: false,
				thumbs: thumbsSwiper ? { swiper: thumbsSwiper } : undefined,
				on: {
					afterInit() {
						galleryEl.style.opacity = '1';
					},
				},
			});
		},
	};

	/* =========================================================================
        3. Add-to-Cart Button States
    ========================================================================= */
	ShopChop.CartButton = {
		init() {
			$(document.body)
				.on('adding_to_cart', (e, $btn) => {
					$btn.addClass('loading')
						.text('Adding…')
						.prop('disabled', true);
				})
				.on('added_to_cart', (e, fragments, hash, $btn) => {
					$btn.removeClass('loading')
						.addClass('added')
						.text('Added to Cart!')
						.prop('disabled', false);

					const $card = $btn.closest('li.product');
					const productName = $card.length
						? $card
								.find('.woocommerce-loop-product__title')
								.first()
								.text()
								.trim()
						: $('.product_title').first().text().trim();

					ShopChop.Toast.show(
						productName
							? `Item “<strong>${Utils.escapeHtml(
									productName
								)}</strong>” added into cart.`
							: 'Item added into cart.',
						'success'
					);
				})
				.on('added_to_wishlist', () => {
					ShopChop.Toast.show(
						'Item added to your wishlist.',
						'success'
					);
				});
		},
	};

	/* =========================================================================
        4. Reviews AJAX Pagination
    ========================================================================= */
	ShopChop.ReviewsPagination = {
		init() {
			$(document).on(
				'click',
				'#reviews .woocommerce-pagination a',
				function (e) {
					e.preventDefault();

					const $dynamic = $('#reviews-dynamic');
					const url = $(this).attr('href');

					$dynamic
						.addClass('is-loading')
						.load(
							`${url} #reviews-dynamic > *`,
							(response, status) => {
								if (status !== 'success') return;
								$dynamic.removeClass('is-loading');
								$('html, body').animate(
									{
										scrollTop:
											$('#reviews').offset().top - 100,
									},
									300
								);
								$(document.body).trigger('init_reviews');
							}
						);
				}
			);
		},
	};

	/* =========================================================================
        5. Shop / Archive AJAX Pagination
    ========================================================================= */
	ShopChop.ShopPagination = {
		init() {
			$(document).on('click', '.woocommerce-pagination a', function (e) {
				// Only intercept on pages that have a product grid
				if (!$('.products').length) return;
				e.preventDefault();

				const $container = $('#primary');
				const url = $(this).attr('href');

				$container
					.css('opacity', '0.5')
					.load(`${url} #primary > *`, (response, status) => {
						if (status === 'error') return;
						$container.css('opacity', '1');
						$('html, body').animate(
							{ scrollTop: $container.offset().top - 50 },
							300
						);
						$(document.body).trigger('post-load');
					});
			});
		},
	};

	/* =========================================================================
        5b. Cart Page – AJAX Remove Item (no page reload)
        Reuses the existing wc_get_cart_remove_url() link/nonce — just loads
        it via AJAX instead of a full navigation, then swaps in the fresh
        cart table + totals. Same partial-reload pattern as ShopPagination.
    ========================================================================= */
	ShopChop.CartItemRemove = {
		init() {
			$(document).on(
				'click',
				'.woocommerce-cart-form .cart-table-wrapper a.remove',
				function (e) {
					e.preventDefault();

					const $link = $(this);
					const $row = $link.closest('tr');
					const href = $link.attr('href');
					const $cartWrapper = $('.classic-cart');

					if (!href || !$cartWrapper.length) return;

					$row.css({ opacity: '0.5', pointerEvents: 'none' });

					$cartWrapper.load(
						`${href} .classic-cart > *`,
						(response, status) => {
							if (status === 'error') {
								$row.css({
									opacity: '1',
									pointerEvents: 'auto',
								});
								ShopChop.Toast.show(
									'Failed to remove item. Please try again.',
									'error'
								);
								return;
							}

							$(document.body)
								.trigger('wc_fragment_refresh')
								.trigger('removed_from_cart');
						}
					);
				}
			);
		},
	};

	/* =========================================================================
        6. Variable Product – Price display & Add-to-Cart availability
        (Previously two separate event-binding blocks; merged into one.)
    ========================================================================= */
	ShopChop.VariableProduct = {
		init() {
			const $form = $('.variations_form');
			if (!$form.length) return;

			const $mainPrice = $('.summary .price');
			const $addBtn = $('.single_add_to_cart_button');

			// Snapshot the default price so we can restore it on reset
			$form.data('price_hold', $mainPrice.html());

			$form
				.on('show_variation', (e, variation) => {
					// Update displayed price
					if (variation.price_html)
						$mainPrice.html(variation.price_html);

					// Update button state
					const purchasable =
						variation.is_purchasable && variation.is_in_stock;
					$addBtn
						.prop('disabled', !purchasable)
						.toggleClass(
							'disabled wc-variation-is-unavailable',
							!purchasable
						);
				})
				.on('reset_data', () => {
					// Restore original price
					const originalPrice = $form.data('price_hold');
					if (originalPrice) $mainPrice.html(originalPrice);

					// Disable button until a valid variation is chosen again
					$addBtn
						.prop('disabled', true)
						.removeClass('wc-variation-is-unavailable');
				})
				.on('hide_variation', () => {
					$addBtn
						.prop('disabled', true)
						.removeClass('wc-variation-is-unavailable');
				});
		},
	};

	/* =========================================================================
        6b. Single Product – AJAX Add to Cart (no page reload)
        Mirrors WC core's archive-page AJAX flow (wc-add-to-cart.js) for the
        single-product form, which core does not AJAX-ify on its own.
        Grouped-product forms (multiple quantity[child_id] fields) route to
        our own shopchop_add_to_cart_grouped endpoint, since WC's own
        wc-ajax=add_to_cart only accepts a single product_id + quantity.
        Falls back to a native submit on error or when data is incomplete.
    ========================================================================= */
	ShopChop.SingleAddToCart = {
		init() {
			if (typeof wc_add_to_cart_params === 'undefined') return;

			$(document).on('submit', 'form.cart', function (e) {
				const $form = $(this);
				const $button = $form.find('.single_add_to_cart_button');
				if (!$button.length || $button.prop('disabled')) return;

				const isGrouped = $form.hasClass('grouped_form');
				let endpoint = 'add_to_cart';
				let data;

				if (isGrouped) {
					const quantities = $form
						.find('input[name^="quantity["]')
						.filter(function () {
							return parseFloat(this.value) > 0;
						});
					if (!quantities.length) return;

					endpoint = 'shopchop_add_to_cart_grouped';
					data = $form.serializeArray().reduce((acc, field) => {
						acc[field.name] = field.value;
						return acc;
					}, {});
				} else {
					let productId;
					if ($form.hasClass('variations_form')) {
						const variationId = $form
							.find('input[name="variation_id"]')
							.val();
						if (!variationId || variationId === '0') return;
						productId = variationId;
					} else {
						productId =
							$button.val() || $form.data('product_id');
					}
					if (!productId) return;

					data = $form.serializeArray().reduce((acc, field) => {
						acc[field.name] = field.value;
						return acc;
					}, {});
					data.product_id = productId;

					// A stray hidden "add-to-cart" field (variation
					// template) would otherwise also trip WC core's
					// classic wp_loaded add-to-cart handler on this same
					// request, double-adding the item.
					delete data['add-to-cart'];
				}

				e.preventDefault();

				$(document.body).trigger('adding_to_cart', [$button, data]);

				$.ajax({
					type: 'POST',
					url: wc_add_to_cart_params.wc_ajax_url
						.toString()
						.replace('%%endpoint%%', endpoint),
					data,
					dataType: 'json',
					success(response) {
						if (!response) return;

						if (response.error && response.product_url) {
							window.location = response.product_url;
							return;
						}

						if (
							wc_add_to_cart_params.cart_redirect_after_add ===
							'yes'
						) {
							window.location = wc_add_to_cart_params.cart_url;
							return;
						}

						$(document.body).trigger('added_to_cart', [
							response.fragments,
							response.cart_hash,
							$button,
						]);
					},
					error() {
						// Fall back to a real submit (bypasses this handler —
						// native .submit() does not dispatch the jQuery
						// 'submit' event).
						$form[0].submit();
					},
				});
			});
		},
	};

	/* =========================================================================
        6c. Wishlist Table – AJAX Add to Cart (no page reload)
        The wishlist page's "Add to cart" link is a plain
        ?add-to-cart={id} URL (YITH Wishlist's own markup), not
        intercepted by anything — clicking it does a full navigation.
        This is a generic delegated handler keyed off the URL pattern,
        not any YITH-specific class/DOM structure, so it keeps working
        even if the plugin's internal markup changes on update. Skips
        (falls back to normal navigation) when the link also carries
        YITH's "remove from wishlist after add to cart" param, since
        replicating that side effect ourselves isn't worth the coupling
        risk to YITH's internals.
    ========================================================================= */
	ShopChop.WishlistAddToCart = {
		init() {
			if (typeof wc_add_to_cart_params === 'undefined') return;

			$(document).on(
				'click',
				'.wishlist_table a[href*="add-to-cart="]',
				function (e) {
					const href = $(this).attr('href');
					if (href.indexOf('remove_from_wishlist_after_add_to_cart') !== -1) {
						return; // let YITH handle its own side effect natively
					}

					let productId;
					try {
						productId = new URL(href, window.location.origin)
							.searchParams.get('add-to-cart');
					} catch (err) {
						return;
					}
					if (!productId) return;

					e.preventDefault();

					const $button = $(this);
					$(document.body).trigger('adding_to_cart', [$button]);

					$.ajax({
						type: 'POST',
						url: wc_add_to_cart_params.wc_ajax_url
							.toString()
							.replace('%%endpoint%%', 'add_to_cart'),
						data: { product_id: productId, quantity: 1 },
						dataType: 'json',
						success(response) {
							if (!response) return;

							if (response.error && response.product_url) {
								window.location = response.product_url;
								return;
							}

							$(document.body).trigger('added_to_cart', [
								response.fragments,
								response.cart_hash,
								$button,
							]);
						},
						error() {
							window.location = href;
						},
					});
				}
			);
		},
	};

	/* =========================================================================
        7. Pill / Swatch Variation Selector
    ========================================================================= */
	ShopChop.PillSwatches = {
		init() {
			const $form = $('.variations_form');
			if (!$form.length) return;

			$(document).on('click', '.pill-swatch', function (e) {
				e.preventDefault();
				const $btn = $(this);
				if ($btn.hasClass('disabled')) return;

				// Sync to the hidden <select>
				$btn.closest('.pill-swatches-container')
					.prev('div')
					.find('select')
					.val($btn.attr('data-value'))
					.trigger('change');

				$btn.addClass('active').siblings().removeClass('active');
				ShopChop.PillSwatches.updateAvailability($form);
			});

			$form.on('reset_data', () =>
				$('.pill-swatch').removeClass('active disabled out-of-stock')
			);
		},

		/**
		 * Enable / disable each pill based on whether it can form a valid variation
		 * with the currently selected attributes.
		 */
		updateAvailability($form) {
			const allVariations = $form.data('product_variations');
			const selected = {};

			$form.find('.pill-swatches-container').each(function () {
				const attr = $(this).data('attribute_name');
				const val = $(this).find('.pill-swatch.active').attr('data-value');
				if (val) selected[attr] = val;
			});

			$form.find('.pill-swatch').each(function () {
				const $pill = $(this);
				const attr = $pill
					.closest('.pill-swatches-container')
					.data('attribute_name');
				const testSel = { ...selected, [attr]: $pill.attr('data-value') };

				const isPossible = allVariations.some((variation) => {
					if (!variation.is_purchasable) return false;
					return Object.entries(testSel).every(([key, val]) => {
						const varAttr = variation.attributes[key];
						// Empty string means "any value" in WooCommerce
						return !varAttr || varAttr === '' || varAttr === val;
					});
				});

				$pill.toggleClass('disabled', !isPossible);
			});
		},
	};

	/* =========================================================================
        8. Quantity +/− Buttons
    ========================================================================= */
	ShopChop.QuantityButtons = {
		init() {
			$(document).on('click', '.qty-btn', function () {
				const $input = $(this)
					.closest('.quantity-nav')
					.find('input.qty');
				const current = parseFloat($input.val());
				const max = parseFloat($input.attr('max'));
				const min = parseFloat($input.attr('min'));
				const step = parseFloat($input.attr('step'));

				if ($(this).hasClass('plus')) {
					if (!max || current < max)
						$input.val(current + step).trigger('change');
				} else {
					if (current > min)
						$input.val(current - step).trigger('change');
				}
			});
		},
	};

	/* =========================================================================
        8b. Pool Profile – Auto-calc Volume from L x W x H (cm or ft toggle)
    ========================================================================= */
	ShopChop.PoolSizeCalculator = {
		metresPerUnit: {
			m: 1,
			ft: 0.3048,
		},

		calc($l, $w, $h, $volume, unit) {
			const factor = ShopChop.PoolSizeCalculator.metresPerUnit[unit];
			const l = parseFloat($l.val());
			const w = parseFloat($w.val());
			const h = parseFloat($h.val());

			if (l > 0 && w > 0 && h > 0) {
				const litres = l * factor * (w * factor) * (h * factor) * 1000;
				$volume.val(Math.round(litres));
			}
		},

		init() {
			const $l = $('#pool_size_l');
			const $w = $('#pool_size_w');
			const $h = $('#pool_size_h');
			const $volume = $('#pool_volume');
			const $options = $('.pool-size-unit-option');
			const $unit = $('#pool_size_unit');

			if (!$l.length || !$w.length || !$h.length || !$volume.length || !$options.length) return;

			const recalc = () => {
				const unit = $options.filter('.is-active').data('unit') || 'm';
				ShopChop.PoolSizeCalculator.calc($l, $w, $h, $volume, unit);
			};

			[$l, $w, $h].forEach(($field) => $field.on('input', recalc));

			$options.on('click', function () {
				$options.removeClass('is-active');
				$(this).addClass('is-active');
				$unit.val($(this).data('unit'));
				recalc();
			});
		},
	};

	/* =========================================================================
        8c. Pool Profile – Photo Dropzone (preview + drag & drop)
    ========================================================================= */
	ShopChop.PoolPhotoDropzone = {
		showPreview(file, $preview) {
			const reader = new FileReader();
			reader.onload = (e) => {
				$preview.html(`<img src="${e.target.result}" data-full="${e.target.result}" alt="" />`);
			};
			reader.readAsDataURL(file);
		},

		init() {
			const $dropzone = $('#pool_photo_dropzone');
			const $input = $('#pool_photo_dropzone .pool-photo-input');
			const $preview = $('#pool_photo_preview');

			if (!$dropzone.length || !$input.length) return;

			$input.on('change', function () {
				if (this.files && this.files[0]) {
					ShopChop.PoolPhotoDropzone.showPreview(this.files[0], $preview);
				}
			});

			$dropzone.on('dragover', (e) => {
				e.preventDefault();
				$dropzone.addClass('is-dragover');
			});

			$dropzone.on('dragleave drop', () => {
				$dropzone.removeClass('is-dragover');
			});

			$dropzone.on('drop', (e) => {
				e.preventDefault();
				const files = e.originalEvent.dataTransfer.files;
				if (files && files[0]) {
					$input[0].files = files;
					$input.trigger('change');
				}
			});
		},
	};

	/* =========================================================================
        8d. Pool Profile – Photo Full Preview Modal
    ========================================================================= */
	ShopChop.PoolPhotoModal = {
		init() {
			const $modal = $('#pool_photo_modal');
			const $modalImg = $('#pool_photo_modal_img');

			if (!$modal.length) return;

			const open = (src) => {
				$modalImg.attr('src', src);
				$modal.addClass('is-open').attr('aria-hidden', 'false');
			};

			const close = () => {
				$modal.removeClass('is-open').attr('aria-hidden', 'true');
				$modalImg.attr('src', '');
			};

			$(document).on('click', '#pool_photo_preview img', function () {
				open($(this).data('full') || $(this).attr('src'));
			});

			$('#pool_photo_modal_close').on('click', close);
			$modal.on('click', (e) => {
				if (e.target === $modal[0]) close();
			});
			$(document).on('keydown', (e) => {
				if (e.key === 'Escape') close();
			});
		},
	};

	/* =========================================================================
        8e. Pool Profile – Delete Confirmation Modal
    ========================================================================= */
	ShopChop.OrderItemsToggle = {
		init() {
			$(document).on('click', '.order-product-details .more-items', function () {
				const $btn = $(this);
				const $wrapper = $btn.closest('.order-product-details');
				const expanded = $wrapper.toggleClass('is-expanded').hasClass('is-expanded');

				$btn.attr('aria-expanded', expanded ? 'true' : 'false');
				$btn.text(expanded ? $btn.data('label-collapse') : $btn.data('label-expand'));
			});
		},
	};

	ShopChop.PoolProfileDeleteModal = {
		init() {
			const $modal = $('#pool_profile_delete_modal');
			const $name = $('#pool_profile_delete_modal_name');
			const $confirm = $('#pool_profile_delete_modal_confirm');

			if (!$modal.length) return;

			const open = ($trigger) => {
				$name.text($trigger.data('profile-name'));
				$confirm.attr('href', $trigger.attr('href'));
				$modal.addClass('is-open').attr('aria-hidden', 'false');
			};

			const close = () => {
				$modal.removeClass('is-open').attr('aria-hidden', 'true');
			};

			$(document).on('click', '.pool-profile-delete', function (e) {
				e.preventDefault();
				open($(this));
			});

			$('#pool_profile_delete_modal_close, #pool_profile_delete_modal_cancel').on('click', close);
			$modal.on('click', (e) => {
				if (e.target === $modal[0]) close();
			});
			$(document).on('keydown', (e) => {
				if (e.key === 'Escape') close();
			});
		},
	};

	/* =========================================================================
        9. My Account – Horizontal Menu Scroll to Active Item
    ========================================================================= */
	ShopChop.AccountMenu = {
		init() {
			const activeLink = document.querySelector('.is-active');
			if (activeLink) {
				activeLink.scrollIntoView({
					behavior: 'smooth',
					block: 'nearest',
					inline: 'center',
				});
			}
		},
	};

	/* =========================================================================
        10. Cart – Auto-update Totals on Quantity Change
    ========================================================================= */
	ShopChop.CartAutoUpdate = {
		init() {
			const triggerUpdate = Utils.debounce(
				() => $('[name="update_cart"]').trigger('click'),
				500
			);

			const attachListener = () => {
				$('div.woocommerce').on('change', 'input.qty', triggerUpdate);
			};

			attachListener();
			// Re-attach after WooCommerce rebuilds the cart HTML
			$(document.body).on('updated_cart_totals', attachListener);
		},
	};

	/* =========================================================================
        11. Payment Method – Highlight Selected Method Card
    ========================================================================= */
	ShopChop.PaymentMethods = {
		update() {
			$('.wc_payment_method').removeClass('is-selected');
			$('input[name="payment_method"]:checked')
				.closest('.wc_payment_method')
				.addClass('is-selected');
		},

		init() {
			const { update } = ShopChop.PaymentMethods;

			// Clicking anywhere on the card (not an interactive child) selects it
			$(document.body).on('click', '.wc_payment_method', function (e) {
				if ($(e.target).is('input, label, a, button, select, textarea'))
					return;
				const $radio = $(this).find('input[name="payment_method"]');
				if (!$radio.is(':checked'))
					$radio.prop('checked', true).trigger('change');
			});

			$(document.body)
				.on('change', 'input[name="payment_method"]', update)
				.on('updated_checkout', update);

			update(); // Set initial state
		},
	};

	/* =========================================================================
        12. AJAX Product Search Bar
    ========================================================================= */
	ShopChop.Search = {
		init() {
			const $input = $('.shopchop-search-input');
			const $catSelect = $('.shopchop-cat-select');
			const $results = $('.shopchop-search-results');
			if (!$input.length) return;

			// ── Load categories ───────────────────────────────────────────
			$.ajax({
				url: shopchopDynamicSearch.ajax_url,
				type: 'POST',
				data: {
					action: 'wc_get_categories',
					nonce: shopchopDynamicSearch.nonce,
				},
				success(response) {
					if (response.success && response.data.categories.length) {
						response.data.categories.forEach((cat) => {
							$catSelect.append(
								`<option value="${cat.slug}">${cat.name}</option>`
							);
						});
					}
				},
			});

			// ── Helpers ───────────────────────────────────────────────────
			const setActive = (active) =>
				$input.add($results).toggleClass('results-active', active);

			/** Hide panel but keep HTML so re-focusing can re-show results. */
			const hideResults = () => {
				$results.hide();
				setActive(false);
			};

			/** Hide AND clear HTML (used when input is empty). */
			const clearResults = () => {
				$results.hide().html('');
				setActive(false);
			};

			// ── Perform search ────────────────────────────────────────────
			const search = Utils.debounce((term) => {
				$.ajax({
					url: shopchopDynamicSearch.ajax_url,
					type: 'POST',
					data: {
						action: 'wc_search_products',
						search_term: term,
						category: $catSelect.val(),
						nonce: shopchopDynamicSearch.nonce,
					},
					beforeSend() {
						$results
							.html(
								'<div class="search-loading">Searching…</div>'
							)
							.show();
						setActive(true);
					},
					success(response) {
						if (!response.success) return;
						const { products } = response.data;

						if (!products.length) {
							$results.html(
								'<div class="no-results">No results found</div>'
							);
							return;
						}

						const html = products
							.map((p) => {
								const title = Utils.escapeHtml(p.title);
								const url = Utils.escapeHtml(p.url);
								const img = p.image
									? `<img src="${Utils.escapeHtml(p.image)}" alt="${title}">`
									: '';
								return `
                                <div class="search-result-item" role="option" aria-label="${title}">
                                    <a href="${url}">
                                        <div class="result-image">${img}</div>
                                        <div class="result-details">
                                            <span class="result-title">${title}</span>
                                        </div>
                                    </a>
                                </div>`;
							})
							.join('');

						$results.html(html).show();
						setActive(true);
					},
					error() {
						$results.html(
							'<div class="search-error">Error loading results</div>'
						);
					},
				});
			}, 300);

			// ── Event listeners ───────────────────────────────────────────
			const toggleClearButton = ($el) => {
				$el.closest('.shopchop-search-bar')
					.find('.shopchop-search-clear')
					.toggleClass('is-visible', $el.val().trim().length > 0);
			};

			$input.on('keyup', function () {
				const term = $(this).val().trim();
				toggleClearButton($(this));
				term.length ? search(term) : clearResults();
			});

			$input.on('focus', function () {
				if ($(this).val().trim() && $results.html().trim()) {
					$results.show();
					setActive(true);
				}
			});

			$catSelect.on('change', () => {
				const term = $input.val().trim();
				if (term.length) search(term);
			});

			$(document).on('click', '.shopchop-search-clear', function () {
				const $bar = $(this).closest('.shopchop-search-bar');
				const $thisInput = $bar.find('.shopchop-search-input');
				$thisInput.val('').trigger('focus');
				toggleClearButton($thisInput);
				clearResults();
			});

			$(document).on('click', (e) => {
				if (!$(e.target).closest('.shopchop-search-wrapper').length)
					hideResults();
			});
		},
	};

	/* =========================================================================
        13. Desktop Account Dropdown
    ========================================================================= */
	ShopChop.AccountDropdown = {
		init() {
			const wrapper = $('.shopchop-account-wrapper');
			if (!wrapper.length) return;

			createDropdown({
				wrapper,
				trigger: $('.shopchop-account-trigger'),
				dropdown: $('.shopchop-account-dropdown'),
			});
		},
	};

	/* =========================================================================
        14. Desktop Cart Dropdown
    ========================================================================= */
	ShopChop.CartDropdown = {
		init() {
			const wrapper = $('.shopchop-cart-wrapper');
			if (!wrapper.length) return;

			const contentEl = $('.cart-dropdown-content');
			const trigger = $('.shopchop-cart-trigger');
			const dropdownEl = $('.shopchop-cart-dropdown');

			const miniCart = createMiniCart({
				contentEl,
				onCountUpdate(count) {
					$('.cart-count-badge').text(count >= 0 ? count : '');
					$('.count-number').text(count);
					$('.cart-items-count').html(Utils.itemsLabel(count));
				},
			});

			const dropdown = createDropdown({
				wrapper,
				trigger,
				dropdown: dropdownEl,
				// Load cart content the first time the panel opens
				onShow() {
					if (!miniCart.getIsLoaded()) miniCart.load();
				},
			});

			// When an item is added show the cart briefly, then auto-close
			let isHoveringCart = false;
			wrapper.on('mouseenter', () => { isHoveringCart = true; });
			wrapper.on('mouseleave', () => { isHoveringCart = false; });

			$(document.body).on('added_to_cart', () => {
				miniCart.markStale();

				if (!dropdownEl.is(':visible')) {
					dropdown.show();
					setTimeout(() => {
						if (!isHoveringCart) dropdown.hide();
					}, 2000);
				} else {
					miniCart.load();
				}
			});

			$(document.body).on(
				'wc_fragment_refresh updated_cart_totals',
				() => {
					miniCart.markStale();
				}
			);
		},
	};

	/* =========================================================================
        15. Mobile Drawers – Search, Cart, Menu
    ========================================================================= */
	ShopChop.MobileDrawers = {
		init() {
			const byId = (id) => document.getElementById(id);

			const searchDrawer = byId('mobile-search');
			const cartDrawer = byId('mobile-mini-cart');
			const menuDrawer = byId('mobile-panel');
			const backdrop = byId('backdrop');
			const searchBtn = byId('mobile-search-toggle');
			const cartBtn = byId('mobile-cart-toggle');
			const menuBtn = byId('mobile-menu-toggle');
			const cartClose = byId('cart-close');
			const searchClose = byId('search-close');
			const menuClose = byId('menu-close');

			// Guard: elements only exist on pages with the mobile header
			if (!menuBtn) return;

			const iconOpen = menuBtn.querySelector('.toggle-open');
			const iconClose = menuBtn.querySelector('.toggle-close');

			// ── Generic open / close helpers ──────────────────────────────
			const lockScroll = () => {
				document.body.style.overflow = 'hidden';
			};
			const unlockScroll = () => {
				document.body.style.overflow = '';
			};

			const openDrawer = (el, classToRemove) => {
				el.classList.add('open');
				el.classList.remove(classToRemove);
				backdrop.classList.add('open');
				document.body.classList.add('shopchop-drawer-open');
				lockScroll();
			};

			const closeDrawer = (el, classToAdd) => {
				el.classList.remove('open');
				el.classList.add(classToAdd);
				backdrop.classList.remove('open');
				document.body.classList.remove('shopchop-drawer-open');
				unlockScroll();
			};

			// ── Focus helpers ─────────────────────────────────────────────
			const focusFirst = (el) => {
				const target = el.querySelector('input, button, a, select, textarea, [tabindex]:not([tabindex="-1"])');
				if (target) target.focus();
			};

			const getFocusable = (el) =>
				[...el.querySelectorAll('input, button, a, select, textarea, [tabindex]:not([tabindex="-1"])')].filter(
					(n) => !n.disabled && n.offsetParent !== null
				);

			const trapFocus = (e, el) => {
				const nodes = getFocusable(el);
				if (!nodes.length) return;
				const first = nodes[0];
				const last = nodes[nodes.length - 1];
				if (e.shiftKey && document.activeElement === first) {
					e.preventDefault();
					last.focus();
				} else if (!e.shiftKey && document.activeElement === last) {
					e.preventDefault();
					first.focus();
				}
			};

			// ── Per-drawer wrappers ───────────────────────────────────────
			const openSearch = () => {
				openDrawer(searchDrawer, '-translate-y-full');
				searchBtn.setAttribute('aria-expanded', 'true');
				focusFirst(searchDrawer);
			};
			const closeSearch = () => {
				closeDrawer(searchDrawer, '-translate-y-full');
				searchBtn.setAttribute('aria-expanded', 'false');
				searchBtn.focus();
			};

			const openCart = () => {
				openDrawer(cartDrawer, 'translate-y-full');
				cartBtn.setAttribute('aria-expanded', 'true');
				focusFirst(cartDrawer);
			};
			const closeCart = () => {
				closeDrawer(cartDrawer, 'translate-y-full');
				cartBtn.setAttribute('aria-expanded', 'false');
				cartBtn.focus();
			};

			const openMenu = () => {
				openDrawer(menuDrawer, 'translate-x-full');
				menuBtn.setAttribute('aria-expanded', 'true');
				iconOpen.style.display = 'none';
				iconClose.style.display = '';
				focusFirst(menuDrawer);
			};
			const closeMenu = () => {
				closeDrawer(menuDrawer, 'translate-x-full');
				menuBtn.setAttribute('aria-expanded', 'false');
				iconOpen.style.display = '';
				iconClose.style.display = 'none';
				menuBtn.focus();
			};

			// ── Button triggers ───────────────────────────────────────────
			searchBtn.addEventListener('click', openSearch);
			cartBtn.addEventListener('click', openCart);
			menuBtn.addEventListener('click', () => {
				menuDrawer.classList.contains('open')
					? closeMenu()
					: openMenu();
			});

			// ── Close buttons ─────────────────────────────────────────────
			cartClose.addEventListener('click', closeCart);
			searchClose.addEventListener('click', closeSearch);
			menuClose.addEventListener('click', closeMenu);

			// ── Backdrop ──────────────────────────────────────────────────
			backdrop.addEventListener('click', () => {
				if (searchDrawer.classList.contains('open')) closeSearch();
				if (cartDrawer.classList.contains('open')) closeCart();
				if (menuDrawer.classList.contains('open')) closeMenu();
			});

			// ── Escape + Tab key handling ─────────────────────────────────
			document.addEventListener('keydown', (e) => {
				if (e.key === 'Escape') {
					if (searchDrawer.classList.contains('open')) closeSearch();
					if (cartDrawer.classList.contains('open')) closeCart();
					if (menuDrawer.classList.contains('open')) closeMenu();
				}
				if (e.key === 'Tab') {
					if (searchDrawer.classList.contains('open')) trapFocus(e, searchDrawer);
					if (cartDrawer.classList.contains('open')) trapFocus(e, cartDrawer);
					if (menuDrawer.classList.contains('open')) trapFocus(e, menuDrawer);
				}
			});
		},
	};

	/* =========================================================================
        16. Mobile Cart Content
    ========================================================================= */
	ShopChop.MobileCart = {
		init() {
			const $content = $('.mobile-cart-content');
			const $header = $('.mobile-cart-header');
			if (!$content.length) return;

			const miniCart = createMiniCart({
				contentEl: $content,
				onCountUpdate(count) {
					$header
						.find('.mobile-cart-items-count')
						.html(`(${Utils.itemsLabel(count)})`);
					// Keep desktop badge in sync
					$('.cart-count-badge').text(count >= 0 ? count : '');
				},
			});

			// Reload on any cart mutation
			$(document.body).on(
				'added_to_cart wc_fragment_refresh updated_cart_totals',
				() => {
					miniCart.load();
				}
			);

			// Lazy-load on first drawer open (same pattern as desktop CartDropdown)
			const cartToggle = document.getElementById('mobile-cart-toggle');
			if (cartToggle) {
				cartToggle.addEventListener('click', () => {
					if (!miniCart.getIsLoaded()) miniCart.load();
				}, { once: false });
			}
		},
	};

	/* =========================================================================
        17. Toast Notifications
    ========================================================================= */
	ShopChop.Toast = {
		show(message, type = 'error', action = null) {
			const existing = document.getElementById('shopchop-toast');
			if (existing) existing.remove();

			const toast = document.createElement('div');
			toast.id = 'shopchop-toast';
			toast.setAttribute('role', 'alert');
			toast.setAttribute('aria-live', 'assertive');
			toast.className = `shopchop-toast shopchop-toast--${type}`;

			const msg = document.createElement('span');
			msg.innerHTML = message;
			toast.appendChild(msg);

			if (action && action.text && action.href) {
				const actionLink = document.createElement('a');
				actionLink.href = action.href;
				actionLink.className = 'shopchop-toast-action';
				actionLink.textContent = action.text;
				toast.appendChild(actionLink);
			}

			const closeBtn = document.createElement('button');
			closeBtn.className = 'shopchop-toast-close';
			closeBtn.setAttribute('aria-label', 'Dismiss notification');
			closeBtn.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 256 256"><path d="M205.66,194.34a8,8,0,0,1-11.32,11.32L128,139.31,61.66,205.66a8,8,0,0,1-11.32-11.32L116.69,128,50.34,61.66A8,8,0,0,1,61.66,50.34L128,116.69l66.34-66.35a8,8,0,0,1,11.32,11.32L139.31,128Z"></path></svg>`;
			toast.appendChild(closeBtn);

			document.body.appendChild(toast);

			const dismiss = () => {
				toast.classList.remove('is-visible');
				toast.addEventListener('transitionend', () => toast.remove(), { once: true });
			};

			closeBtn.addEventListener('click', dismiss);

			// Trigger entrance
			requestAnimationFrame(() => toast.classList.add('is-visible'));

			// Auto-dismiss after 6s
			const timer = setTimeout(dismiss, 6000);

			// Escape key dismiss
			const onKeydown = (e) => {
				if (e.key === 'Escape') {
					clearTimeout(timer);
					dismiss();
					document.removeEventListener('keydown', onKeydown);
				}
			};
			document.addEventListener('keydown', onKeydown);
		},
	};

	/* =========================================================================
        18. Mobile Sub-Menu Accordion
    ========================================================================= */
	ShopChop.MobileSubMenu = {
		init() {
			const nav = document.getElementById('main-header-menu-mobile');
			if (!nav) return;

			nav.querySelectorAll('.menu-item-has-children').forEach((item) => {
				const link = item.querySelector(':scope > a');
				const subMenu = item.querySelector(':scope > .sub-menu');
				if (!link || !subMenu) return;

				// Inject toggle button next to the link
				const btn = document.createElement('button');
				btn.className = 'mobile-submenu-toggle';
				btn.setAttribute('aria-expanded', 'false');
				btn.setAttribute('aria-label', `Toggle ${link.textContent.trim()} sub-menu`);
				btn.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 256 256"><path d="M181.66,133.66l-80,80a8,8,0,0,1-11.32-11.32L164.69,128,90.34,53.66a8,8,0,0,1,11.32-11.32l80,80A8,8,0,0,1,181.66,133.66Z"></path></svg>`;
				item.appendChild(btn);

				btn.addEventListener('click', (e) => {
					e.preventDefault();
					const isOpen = subMenu.classList.contains('is-open');

					// Close sibling sub-menus only (not nested children of other branches)
					item.parentElement.querySelectorAll(':scope > .menu-item-has-children > .sub-menu.is-open').forEach((s) => {
						s.classList.remove('is-open');
					});
					item.parentElement.querySelectorAll(':scope > .menu-item-has-children > .mobile-submenu-toggle[aria-expanded="true"]').forEach((b) => {
						b.setAttribute('aria-expanded', 'false');
					});

					if (!isOpen) {
						subMenu.classList.add('is-open');
						btn.setAttribute('aria-expanded', 'true');
					}
				});
			});
		},
	};

	/* =========================================================================
        19. Login / Register Toggle
    ========================================================================= */
	ShopChop.AuthToggle = {
		init() {
			const toggle = function ($heading) {
				const $target = $(`#${$heading.data('target')}`);
				const wasOpen = $target.hasClass('is-open');

				// Close all panels first
				$('.wc-toggle-form, .wc-toggle-heading').removeClass('is-open');
				$('.wc-toggle-heading').attr('aria-expanded', 'false');

				// Re-open the clicked one if it was previously closed
				if (!wasOpen) {
					$target.addClass('is-open');
					$heading.addClass('is-open').attr('aria-expanded', 'true');
				}
			};

			$(document).on('click', '.wc-toggle-heading', function () {
				toggle($(this));
			});

			$(document).on('keydown', '.wc-toggle-heading', function (e) {
				if (e.key === 'Enter' || e.key === ' ') {
					e.preventDefault();
					toggle($(this));
				}
			});
		},
	};

	/* =========================================================================
        ScrollUtils – progress bar + back-to-top button
    ========================================================================= */
	ShopChop.ScrollUtils = {
		init() {
			const root = document.documentElement;

			// Inject progress bar
			const bar = document.createElement('div');
			bar.id = 'shopchop-progress-bar';
			document.body.prepend(bar);

			// Inject back-to-top button
			const btn = document.createElement('button');
			btn.id = 'shopchop-back-to-top';
			btn.setAttribute('aria-label', 'Back to top');
			btn.setAttribute('title', 'Back to top');
			btn.innerHTML =
				'<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 256 256"><path d="M213.66,165.66a8,8,0,0,1-11.32,0L128,91.31,53.66,165.66a8,8,0,0,1-11.32-11.32l80-80a8,8,0,0,1,11.32,0l80,80A8,8,0,0,1,213.66,165.66Z"></path></svg>';
			document.body.appendChild(btn);

			// Measure header height and set --header-h
			// Only offset the bar if the header is fixed (normal header on mobile).
			// Minimal header is static, so the bar should sit at top-0.
			const setHeaderH = () => {
				const header = document.getElementById('masthead');
				const isFixed = header && getComputedStyle(header).position === 'fixed';
				root.style.setProperty('--header-h', isFixed ? header.offsetHeight + 'px' : '0px');
			};
			setHeaderH();
			window.addEventListener('resize', setHeaderH);

			// Scroll handler (rAF-throttled)
			let ticking = false;
			const onScroll = () => {
				if (ticking) return;
				ticking = true;
				requestAnimationFrame(() => {
					const scrolled = window.scrollY;
					const total =
						document.documentElement.scrollHeight - window.innerHeight;
					const pct = total > 0 ? (scrolled / total) * 100 : 0;
					root.style.setProperty('--scroll-progress', pct + '%');
					btn.classList.toggle('is-visible', scrolled > 300);
					ticking = false;
				});
			};
			window.addEventListener('scroll', onScroll, { passive: true });

			// Back to top click
			btn.addEventListener('click', () => {
				window.scrollTo({ top: 0, behavior: 'smooth' });
			});
		},
	};

	/* =========================================================================
        20. Postcode Autofill – fills City + State from Malaysia postcode
    ========================================================================= */
	ShopChop.PostcodeAutofill = {
		init() {
			// Strip non-digits from postcode fields; enforce 5-char limit
			const enforcePostcode = function () {
				const $el  = $( this );
				const clean = $el.val().replace( /\D/g, '' ).slice( 0, 5 );
				if ( clean !== $el.val() ) $el.val( clean );
			};

			$( document ).on( 'input', '#billing_postcode, #shipping_postcode, #calc_shipping_postcode', enforcePostcode );

			// Strip non-tel chars from phone fields (digits, +, space, -, parentheses)
			$( document ).on( 'input', '#billing_phone, #shipping_phone', function () {
				const $el  = $( this );
				const clean = $el.val().replace( /[^\d+\s\-()\[\]]/g, '' );
				if ( clean !== $el.val() ) $el.val( clean );
			} );

			if ( typeof shopchopPostcodes === 'undefined' ) return;

			const fill = ( $input, cityId, stateId ) => {
				const code = $input.val().trim();
				if ( code.length !== 5 ) return false;

				const match = shopchopPostcodes[ code ];
				if ( ! match ) return false;

				$( cityId ).val( match.city );
				const $state = $( stateId );
				if ( $state.length ) {
					$state.val( match.state ).trigger( 'change' );
				}
				return true;
			};

			$( document ).on( 'input', '#billing_postcode', function () {
				fill( $( this ), '#billing_city', '#billing_state' );
			} );

			$( document ).on( 'input', '#shipping_postcode', function () {
				fill( $( this ), '#shipping_city', '#shipping_state' );
			} );

			let calcAutoSubmitTimer;
			$( document ).on( 'input', '#calc_shipping_postcode', function () {
				const matched = fill( $( this ), '#calc_shipping_city', '#calc_shipping_state' );
				if ( matched ) {
					clearTimeout( calcAutoSubmitTimer );
					calcAutoSubmitTimer = setTimeout( () => {
						$( '.woocommerce-shipping-calculator button[name="calc_shipping"]' ).trigger( 'click' );
					}, 800 );
				}
			} );
		},
	};

	/* =========================================================================
        WhatsApp Button – update href dynamically on variation select
    ========================================================================= */
	ShopChop.WhatsAppButton = {
		init() {
			const btn = document.getElementById( 'shopchop-whatsapp-btn' );
			if ( ! btn ) return;

			const number      = btn.dataset.waNumber;
			const name        = btn.dataset.productName;
			const pageUrl     = btn.dataset.productUrl;
			const defaultHref = btn.getAttribute( 'href' );

			const buildUrl = ( price ) => {
				const message = `Hi, I'm interested in ${ name } (${ price })\n${ pageUrl }`;
				return `https://wa.me/${ number }?text=${ encodeURIComponent( message ) }`;
			};

			$( document.body ).on( 'found_variation', ( e, variation ) => {
				if ( variation.display_price ) {
					const price = ShopChop.WhatsAppButton.formatPrice( variation.display_price );
					btn.href = buildUrl( price );
				}
			} );

			$( document.body ).on( 'reset_data', () => {
				btn.href = defaultHref;
			} );
		},

		formatPrice( amount ) {
			return 'RM' + parseFloat( amount ).toFixed( 2 );
		},
	};

	/* =========================================================================
        Boot – initialise all modules on DOM ready
    ========================================================================= */
	$(function () {
		ShopChop.HeroCarousel.init();
		ShopChop.ProductsCarousel.init();
		ShopChop.TestimonialsCarousel.init();
		ShopChop.ProductGallery.init();
		ShopChop.CartButton.init();
		ShopChop.ReviewsPagination.init();
		ShopChop.ShopPagination.init();
		ShopChop.CartItemRemove.init();
		ShopChop.VariableProduct.init();
		ShopChop.SingleAddToCart.init();
		ShopChop.WishlistAddToCart.init();
		ShopChop.PillSwatches.init();
		ShopChop.QuantityButtons.init();
		ShopChop.AccountMenu.init();
		ShopChop.CartAutoUpdate.init();
		ShopChop.PaymentMethods.init();
		ShopChop.Search.init();
		ShopChop.AccountDropdown.init();
		ShopChop.CartDropdown.init();
		ShopChop.MobileDrawers.init();
		ShopChop.MobileCart.init();
		ShopChop.MobileSubMenu.init();
		ShopChop.AuthToggle.init();
		ShopChop.ScrollUtils.init();
		ShopChop.WhatsAppButton.init();
		ShopChop.PostcodeAutofill.init();
		ShopChop.PoolSizeCalculator.init();
		ShopChop.PoolPhotoDropzone.init();
		ShopChop.PoolPhotoModal.init();
		ShopChop.PoolProfileDeleteModal.init();
		ShopChop.OrderItemsToggle.init();
	});
})(jQuery);
