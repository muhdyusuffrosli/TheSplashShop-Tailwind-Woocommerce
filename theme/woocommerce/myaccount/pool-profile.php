<?php
/**
 * My Account — Pool Profile
 *
 * @package shopchop
 */

defined( 'ABSPATH' ) || exit;

$profiles   = shopchop_get_pool_profiles();
$editing_id = isset( $_GET['edit'] ) ? absint( $_GET['edit'] ) : 0;
$editing    = null;
$adding     = isset( $_GET['pool_action'] ) && 'add' === $_GET['pool_action'];

if ( $editing_id ) {
	foreach ( $profiles as $profile ) {
		if ( (int) $profile->ID === $editing_id ) {
			$editing = $profile;
			break;
		}
	}
}

$show_form = $adding || $editing;
$list_url  = wc_get_account_endpoint_url( 'pool-profile' );
$add_url   = add_query_arg( 'pool_action', 'add', $list_url );

$get_meta = function( $key ) use ( $editing ) {
	return $editing ? get_post_meta( $editing->ID, $key, true ) : '';
};
?>

<?php if ( ! $show_form ) : ?>

	<div class="pool-profile-intro">
		<p><?php esc_html_e( 'Add or edit your pool profiles.', 'shopchop' ); ?></p>
		<a class="button pool-profile-add-new" href="<?php echo esc_url( $add_url ); ?>">
			<?php esc_html_e( 'Add new pool profile', 'shopchop' ); ?>
		</a>
	</div>

	<?php if ( $profiles ) : ?>
		<div class="pool-profile-list">
			<?php foreach ( $profiles as $profile ) :
				$volume   = get_post_meta( $profile->ID, '_pool_volume', true );
				$type     = get_post_meta( $profile->ID, '_pool_type', true );
				$photo_id = get_post_meta( $profile->ID, '_pool_photo_id', true );
				?>
				<div class="pool-profile-card">
					<?php if ( $photo_id ) : ?>
						<div class="pool-profile-card-photo">
							<?php echo wp_get_attachment_image( $photo_id, 'thumbnail' ); ?>
						</div>
					<?php endif; ?>
					<div class="pool-profile-card-info">
						<h3><?php echo esc_html( $profile->post_title ); ?></h3>
						<p>
							<?php
							printf(
								/* translators: 1: volume in litres, 2: pool type label */
								esc_html__( '%1$s L · %2$s', 'shopchop' ),
								esc_html( number_format_i18n( (int) $volume ) ),
								esc_html( SHOPCHOP_POOL_TYPES[ $type ] ?? $type )
							);
							?>
						</p>
					</div>
					<div class="pool-profile-card-actions">
						<a class="button pool-profile-edit" href="<?php echo esc_url( add_query_arg( 'edit', $profile->ID, $list_url ) ); ?>">
							<?php esc_html_e( 'Edit', 'shopchop' ); ?>
						</a>
						<a class="button pool-profile-delete" href="<?php echo esc_url( wp_nonce_url( add_query_arg( 'shopchop_delete_pool', $profile->ID, $list_url ), 'shopchop_delete_pool_profile_' . $profile->ID ) ); ?>" data-profile-name="<?php echo esc_attr( $profile->post_title ); ?>">
							<?php esc_html_e( 'Delete', 'shopchop' ); ?>
						</a>
					</div>
				</div>
			<?php endforeach; ?>
		</div>

		<div class="pool-profile-delete-modal" id="pool_profile_delete_modal" aria-hidden="true">
			<div class="pool-profile-delete-modal-card">
				<button type="button" class="pool-profile-delete-modal-close" id="pool_profile_delete_modal_close" aria-label="<?php esc_attr_e( 'Close', 'shopchop' ); ?>">&times;</button>

				<span class="pool-profile-delete-modal-icon">
					<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 256 256" aria-hidden="true"><path d="M128,24A104,104,0,1,0,232,128,104.12,104.12,0,0,0,128,24Zm0,192a88,88,0,1,1,88-88A88.1,88.1,0,0,1,128,216Zm-8-80V80a8,8,0,0,1,16,0v56a8,8,0,0,1-16,0Zm20,36a12,12,0,1,1-12-12A12,12,0,0,1,140,172Z"></path></svg>
				</span>

				<h3><?php esc_html_e( 'Delete Pool Profile?', 'shopchop' ); ?></h3>
				<p>
					<?php
					printf(
						/* translators: %s: pool profile name, wrapped in <strong> at render time */
						esc_html__( 'This will permanently delete %s and all of its data. This action cannot be undone.', 'shopchop' ),
						'<strong id="pool_profile_delete_modal_name"></strong>'
					);
					?>
				</p>

				<div class="pool-profile-delete-modal-actions">
					<button type="button" class="pool-profile-delete-modal-cancel" id="pool_profile_delete_modal_cancel"><?php esc_html_e( 'Cancel', 'shopchop' ); ?></button>
					<a href="#" class="pool-profile-delete-modal-confirm" id="pool_profile_delete_modal_confirm"><?php esc_html_e( 'Delete Pool Profile', 'shopchop' ); ?></a>
				</div>
			</div>
		</div>
	<?php else : ?>
		<div class="woocommerce-error pool-profile-empty" role="alert">
			<span class="wc-error-icon"></span>
			<span class="wc-error-inner">
				<?php esc_html_e( 'You haven\'t saved a pool profile yet. Add one to unlock dosage recommendations and faster reordering.', 'shopchop' ); ?>
			</span>
		</div>
	<?php endif; ?>

<?php else : ?>

	<div class="pool-profile-form-wrapper">
		<h2><?php echo $editing ? esc_html__( 'Edit pool profile', 'shopchop' ) : esc_html__( 'Add a pool', 'shopchop' ); ?></h2>

		<form method="post" class="pool-profile-form" enctype="multipart/form-data">
			<?php wp_nonce_field( 'shopchop_save_pool_profile', 'shopchop_pool_profile_nonce' ); ?>
			<input type="hidden" name="profile_id" value="<?php echo esc_attr( $editing ? $editing->ID : '' ); ?>" />

			<fieldset class="pool-profile-fieldset">
				<legend><?php esc_html_e( 'Basic details', 'shopchop' ); ?></legend>

				<?php $photo_id = $get_meta( '_pool_photo_id' ); ?>
				<div class="pool-basic-details-layout">
					<div class="pool-basic-details-fields">
						<p class="form-row">
							<label for="pool_name"><?php esc_html_e( 'Pool name', 'shopchop' ); ?>&nbsp;<span class="required">*</span></label>
							<input type="text" id="pool_name" name="pool_name" required placeholder="<?php esc_attr_e( 'e.g. Homestay, Dream Home, Condo Unit, etc.', 'shopchop' ); ?>" value="<?php echo esc_attr( $editing ? $editing->post_title : '' ); ?>" />
						</p>

						<div class="form-row">
							<label for="pool_photo"><?php esc_html_e( 'Pool photo (optional)', 'shopchop' ); ?></label>
							<div class="pool-photo-dropzone" id="pool_photo_dropzone">
								<label for="pool_photo" class="pool-photo-dropzone-inner">
									<span class="pool-photo-dropzone-placeholder">
										<svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="currentColor" viewBox="0 0 256 256" aria-hidden="true"><path d="M216,40H40A16,16,0,0,0,24,56V200a16,16,0,0,0,16,16H216a16,16,0,0,0,16-16V56A16,16,0,0,0,216,40Zm0,16V158.75l-26.07-26.06a16,16,0,0,0-22.63,0l-20,20-44-44a16,16,0,0,0-22.62,0L40,149.37V56ZM40,172l52-52,80,80H40Zm176,28H194.63l-36-36,20-20L216,181.38V200ZM144,100a12,12,0,1,1,12,12A12,12,0,0,1,144,100Z"></path></svg>
										<strong><?php esc_html_e( 'Click to select', 'shopchop' ); ?></strong>
										<span><?php esc_html_e( 'or drag and drop file here', 'shopchop' ); ?></span>
									</span>
								</label>
								<input type="file" id="pool_photo" name="pool_photo" accept=".jpg,.jpeg,.png,.heic" class="pool-photo-input" />
							</div>
						</div>
					</div>

					<div class="pool-photo-preview" id="pool_photo_preview">
						<?php if ( $photo_id ) : ?>
							<img src="<?php echo esc_url( wp_get_attachment_image_url( $photo_id, 'medium' ) ); ?>" data-full="<?php echo esc_url( wp_get_attachment_image_url( $photo_id, 'large' ) ); ?>" alt="" />
						<?php else : ?>
							<span class="pool-photo-preview-placeholder"><?php esc_html_e( 'Your image will appear here', 'shopchop' ); ?></span>
						<?php endif; ?>
					</div>

					<div class="pool-photo-modal" id="pool_photo_modal" aria-hidden="true">
						<button type="button" class="pool-photo-modal-close" id="pool_photo_modal_close" aria-label="<?php esc_attr_e( 'Close preview', 'shopchop' ); ?>">&times;</button>
						<img src="" alt="" id="pool_photo_modal_img" />
					</div>
				</div>
			</fieldset>

			<fieldset class="pool-profile-fieldset">
				<legend><?php esc_html_e( 'Pool details', 'shopchop' ); ?></legend>

				<div class="pool-profile-fields-grid">
					<div class="pool-size-calculator form-row">
						<div class="pool-size-calculator-label">
							<label><?php esc_html_e( 'Pool size (optional)', 'shopchop' ); ?></label>
							<?php $pool_size_unit = $get_meta( '_pool_size_unit' ) === 'ft' ? 'ft' : 'm'; ?>
							<div class="pool-size-unit-toggle" role="group" aria-label="<?php esc_attr_e( 'Unit', 'shopchop' ); ?>">
								<button type="button" class="pool-size-unit-option<?php echo 'm' === $pool_size_unit ? ' is-active' : ''; ?>" data-unit="m"><?php esc_html_e( 'm', 'shopchop' ); ?></button>
								<button type="button" class="pool-size-unit-option<?php echo 'ft' === $pool_size_unit ? ' is-active' : ''; ?>" data-unit="ft"><?php esc_html_e( 'ft', 'shopchop' ); ?></button>
							</div>
							<input type="hidden" id="pool_size_unit" name="pool_size_unit" value="<?php echo esc_attr( $pool_size_unit ); ?>" />
						</div>
						<div class="pool-size-inputs">
							<div class="pool-size-input">
								<label for="pool_size_l"><?php esc_html_e( 'Length', 'shopchop' ); ?></label>
								<input type="number" step="0.01" min="0" id="pool_size_l" name="pool_size_l" value="<?php echo esc_attr( $get_meta( '_pool_size_l' ) ); ?>" />
							</div>
							<span class="pool-size-separator" aria-hidden="true">&times;</span>
							<div class="pool-size-input">
								<label for="pool_size_w"><?php esc_html_e( 'Width', 'shopchop' ); ?></label>
								<input type="number" step="0.01" min="0" id="pool_size_w" name="pool_size_w" value="<?php echo esc_attr( $get_meta( '_pool_size_w' ) ); ?>" />
							</div>
							<span class="pool-size-separator" aria-hidden="true">&times;</span>
							<div class="pool-size-input">
								<label for="pool_size_h"><?php esc_html_e( 'Height', 'shopchop' ); ?></label>
								<input type="number" step="0.01" min="0" id="pool_size_h" name="pool_size_h" value="<?php echo esc_attr( $get_meta( '_pool_size_h' ) ); ?>" />
							</div>
						</div>
					</div>

					<p class="form-row">
						<label for="pool_volume"><?php esc_html_e( 'Volume (litres)', 'shopchop' ); ?>&nbsp;<span class="required">*</span></label>
						<input type="number" id="pool_volume" name="pool_volume" min="1" required value="<?php echo esc_attr( $editing ? get_post_meta( $editing->ID, '_pool_volume', true ) : '' ); ?>" />
					</p>

					<p class="form-row">
						<label for="pool_type"><?php esc_html_e( 'Pool type', 'shopchop' ); ?>&nbsp;<span class="required">*</span></label>
						<?php $current_type = $editing ? get_post_meta( $editing->ID, '_pool_type', true ) : ''; ?>
						<select id="pool_type" name="pool_type" required>
							<option value=""><?php esc_html_e( 'Select pool type', 'shopchop' ); ?></option>
							<?php foreach ( SHOPCHOP_POOL_TYPES as $value => $label ) : ?>
								<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $current_type, $value ); ?>><?php echo esc_html( $label ); ?></option>
							<?php endforeach; ?>
						</select>
					</p>

					<p class="form-row">
						<label for="pool_shape"><?php esc_html_e( 'Pool shape (optional)', 'shopchop' ); ?></label>
						<select id="pool_shape" name="pool_shape">
							<option value=""><?php esc_html_e( 'Select pool shape', 'shopchop' ); ?></option>
							<?php foreach ( SHOPCHOP_POOL_SHAPES as $value => $label ) : ?>
								<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $get_meta( '_pool_shape' ), $value ); ?>><?php echo esc_html( $label ); ?></option>
							<?php endforeach; ?>
						</select>
					</p>

					<p class="form-row">
						<label for="sanitiser_method"><?php esc_html_e( 'Sanitiser method (optional)', 'shopchop' ); ?></label>
						<select id="sanitiser_method" name="sanitiser_method">
							<option value=""><?php esc_html_e( 'Select sanitiser method', 'shopchop' ); ?></option>
							<?php foreach ( SHOPCHOP_SANITISER_METHODS as $value => $label ) : ?>
								<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $get_meta( '_sanitiser_method' ), $value ); ?>><?php echo esc_html( $label ); ?></option>
							<?php endforeach; ?>
						</select>
					</p>

					<p class="form-row">
						<label for="skimmer_type"><?php esc_html_e( 'Skimmer (optional)', 'shopchop' ); ?></label>
						<select id="skimmer_type" name="skimmer_type">
							<option value=""><?php esc_html_e( 'Select type', 'shopchop' ); ?></option>
							<?php foreach ( SHOPCHOP_SKIMMER_TYPES as $value => $label ) : ?>
								<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $get_meta( '_skimmer_type' ), $value ); ?>><?php echo esc_html( $label ); ?></option>
							<?php endforeach; ?>
						</select>
					</p>
				</div>
			</fieldset>

			<fieldset class="pool-profile-fieldset">
				<legend><?php esc_html_e( 'Equipment details', 'shopchop' ); ?></legend>

				<div class="pool-profile-fields-grid">
					<p class="form-row">
						<label for="pump_used"><?php esc_html_e( 'Pump used (optional)', 'shopchop' ); ?></label>
						<input type="text" id="pump_used" name="pump_used" value="<?php echo esc_attr( $get_meta( '_pump_used' ) ); ?>" />
					</p>

					<p class="form-row">
						<label for="filter_type"><?php esc_html_e( 'Filter type (optional)', 'shopchop' ); ?></label>
						<select id="filter_type" name="filter_type">
							<option value=""><?php esc_html_e( 'Select filter type', 'shopchop' ); ?></option>
							<?php foreach ( SHOPCHOP_FILTER_TYPES as $value => $label ) : ?>
								<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $get_meta( '_filter_type' ), $value ); ?>><?php echo esc_html( $label ); ?></option>
							<?php endforeach; ?>
						</select>
					</p>

					<p class="form-row">
						<label for="equipment_brand"><?php esc_html_e( 'Equipment brand (optional)', 'shopchop' ); ?></label>
						<select id="equipment_brand" name="equipment_brand">
							<option value=""><?php esc_html_e( 'Select brand', 'shopchop' ); ?></option>
							<?php foreach ( SHOPCHOP_EQUIPMENT_BRANDS as $value => $label ) : ?>
								<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $get_meta( '_equipment_brand' ), $value ); ?>><?php echo esc_html( $label ); ?></option>
							<?php endforeach; ?>
						</select>
					</p>

					<p class="form-row">
						<label for="last_service_date"><?php esc_html_e( 'Last service date (optional)', 'shopchop' ); ?></label>
						<input type="text" id="last_service_date" name="last_service_date" placeholder="<?php esc_attr_e( 'e.g. June 2025', 'shopchop' ); ?>" value="<?php echo esc_attr( $get_meta( '_last_service_date' ) ); ?>" />
					</p>

					<p class="form-row form-row-full">
						<label for="pool_notes"><?php esc_html_e( 'Pool notes (optional)', 'shopchop' ); ?></label>
						<textarea id="pool_notes" name="pool_notes" placeholder="<?php esc_attr_e( 'e.g. infinity edge, indoor pool, heated pool', 'shopchop' ); ?>"><?php echo esc_textarea( $get_meta( '_pool_notes' ) ); ?></textarea>
					</p>
				</div>
			</fieldset>

			<p class="pool-profile-form-submit">
				<button type="submit" name="shopchop_pool_profile_save" class="button">
					<?php echo $editing ? esc_html__( 'Update Pool Profile', 'shopchop' ) : esc_html__( 'Save Pool Profile', 'shopchop' ); ?>
				</button>
			</p>
		</form>
	</div>

<?php endif; ?>
