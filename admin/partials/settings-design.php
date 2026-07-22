<?php
/**
 * Admin partial: Design Settings tab.
 *
 * @package SalesNotification
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$settings = SN_Settings::get_all();

$positions = array(
	'bottom-left'   => __( 'Bottom Left', 'sales-notification' ),
	'bottom-right'  => __( 'Bottom Right', 'sales-notification' ),
	'bottom-center' => __( 'Bottom Center', 'sales-notification' ),
	'top-left'      => __( 'Top Left', 'sales-notification' ),
	'top-right'     => __( 'Top Right', 'sales-notification' ),
	'top-center'    => __( 'Top Center', 'sales-notification' ),
);

$animations = array(
	'slide-in'  => __( 'Slide In', 'sales-notification' ),
	'fade-in'   => __( 'Fade In', 'sales-notification' ),
	'bounce-in' => __( 'Bounce In', 'sales-notification' ),
	'zoom-in'   => __( 'Zoom In', 'sales-notification' ),
	'flip-in'   => __( 'Flip In', 'sales-notification' ),
	'none'      => __( 'No Animation', 'sales-notification' ),
);

$exit_animations = array(
	'fade-out'  => __( 'Fade Out', 'sales-notification' ),
	'slide-out' => __( 'Slide Out', 'sales-notification' ),
	'zoom-out'  => __( 'Zoom Out', 'sales-notification' ),
	'none'      => __( 'No Animation', 'sales-notification' ),
);

$shadows = array(
	'none'   => __( 'None', 'sales-notification' ),
	'soft'   => __( 'Soft', 'sales-notification' ),
	'medium' => __( 'Medium', 'sales-notification' ),
	'strong' => __( 'Strong', 'sales-notification' ),
);

$font_weights = array( '300', '400', '500', '600', '700' );
?>
<div class="sn-settings-wrap">
	<h1 class="sn-page-title">
		<span class="dashicons dashicons-megaphone"></span>
		<?php esc_html_e( 'Sales Notification', 'sales-notification' ); ?>
		<span class="sn-version">v<?php echo esc_html( SN_VERSION ); ?></span>
	</h1>
	<?php
	$active_tab = 'design';
	$base_url   = admin_url( 'admin.php?page=sales-notification-settings' );
	$tabs = array(
		'general'       => __( 'General', 'sales-notification' ),
		'notifications' => __( 'Notifications', 'sales-notification' ),
		'design'        => __( 'Design', 'sales-notification' ),
		'advanced'      => __( 'Advanced', 'sales-notification' ),
		'import-export' => __( 'Import / Export', 'sales-notification' ),
	);
	?>
	<nav class="sn-tab-nav">
		<?php foreach ( $tabs as $tab_id => $tab_label ) : ?>
			<a href="<?php echo esc_url( add_query_arg( 'tab', $tab_id, $base_url ) ); ?>"
			   class="sn-tab<?php echo $active_tab === $tab_id ? ' sn-tab--active' : ''; ?>">
				<?php echo esc_html( $tab_label ); ?>
			</a>
		<?php endforeach; ?>
	</nav>

	<div class="sn-design-layout">
		<!-- Settings Column -->
		<div class="sn-design-settings">
			<div id="sn-notices" class="sn-notices-container"></div>
			<form id="sn-settings-form" method="post">
				<?php wp_nonce_field( 'sn_admin_nonce', 'sn_nonce' ); ?>
				<input type="hidden" name="sn_tab" value="design">

				<!-- Template -->
				<div class="sn-card">
					<div class="sn-card__header"><h2><?php esc_html_e( 'Popup Template', 'sales-notification' ); ?></h2></div>
					<div class="sn-card__body">
						<div class="sn-template-grid">
							<?php foreach ( array( '1', '2', '3' ) as $tpl ) : ?>
								<label class="sn-template-option <?php echo $settings['template'] === $tpl ? 'sn-template-option--active' : ''; ?>">
									<input type="radio" name="settings[template]" value="<?php echo esc_attr( $tpl ); ?>"
										<?php checked( $settings['template'], $tpl ); ?>>
									<div class="sn-template-thumb sn-template-thumb--<?php echo esc_attr( $tpl ); ?>">
										<div class="sn-tpl-preview"></div>
									</div>
									<span>
										<?php
										$tpl_names = array(
											'1' => __( 'Horizontal', 'sales-notification' ),
											'2' => __( 'Vertical', 'sales-notification' ),
											'3' => __( 'Minimal', 'sales-notification' ),
										);
										echo esc_html( $tpl_names[ $tpl ] );
										?>
									</span>
								</label>
							<?php endforeach; ?>
						</div>
					</div>
				</div>

				<!-- Position -->
				<div class="sn-card">
					<div class="sn-card__header"><h2><?php esc_html_e( 'Popup Position', 'sales-notification' ); ?></h2></div>
					<div class="sn-card__body">
						<div class="sn-position-grid">
							<?php foreach ( $positions as $pos_key => $pos_label ) : ?>
								<label class="sn-position-btn <?php echo $settings['position'] === $pos_key ? 'sn-position-btn--active' : ''; ?>" data-pos="<?php echo esc_attr( $pos_key ); ?>">
									<input type="radio" name="settings[position]" value="<?php echo esc_attr( $pos_key ); ?>"
										<?php checked( $settings['position'], $pos_key ); ?>>
									<?php echo esc_html( $pos_label ); ?>
								</label>
							<?php endforeach; ?>
						</div>
					</div>
				</div>

				<!-- Animations -->
				<div class="sn-card">
					<div class="sn-card__header"><h2><?php esc_html_e( 'Animations', 'sales-notification' ); ?></h2></div>
					<div class="sn-card__body">
						<div class="sn-field">
							<label class="sn-label" for="sn_animation_in"><?php esc_html_e( 'Entrance Animation', 'sales-notification' ); ?></label>
							<div class="sn-field__control">
								<select id="sn_animation_in" name="settings[animation_in]" class="sn-select">
									<?php foreach ( $animations as $anim_key => $anim_label ) : ?>
										<option value="<?php echo esc_attr( $anim_key ); ?>" <?php selected( $settings['animation_in'], $anim_key ); ?>>
											<?php echo esc_html( $anim_label ); ?>
										</option>
									<?php endforeach; ?>
								</select>
							</div>
						</div>
						<div class="sn-field">
							<label class="sn-label" for="sn_animation_out"><?php esc_html_e( 'Exit Animation', 'sales-notification' ); ?></label>
							<div class="sn-field__control">
								<select id="sn_animation_out" name="settings[animation_out]" class="sn-select">
									<?php foreach ( $exit_animations as $anim_key => $anim_label ) : ?>
										<option value="<?php echo esc_attr( $anim_key ); ?>" <?php selected( $settings['animation_out'], $anim_key ); ?>>
											<?php echo esc_html( $anim_label ); ?>
										</option>
									<?php endforeach; ?>
								</select>
							</div>
						</div>
					</div>
				</div>

				<!-- Colors -->
				<div class="sn-card">
					<div class="sn-card__header"><h2><?php esc_html_e( 'Colors', 'sales-notification' ); ?></h2></div>
					<div class="sn-card__body sn-color-grid">
						<?php
						$colors = array(
							'color_bg'             => __( 'Background', 'sales-notification' ),
							'color_text'           => __( 'Text', 'sales-notification' ),
							'color_text_secondary' => __( 'Secondary Text', 'sales-notification' ),
							'color_accent'         => __( 'Accent / Link', 'sales-notification' ),
							'color_border'         => __( 'Border', 'sales-notification' ),
						);
						foreach ( $colors as $color_key => $color_label ) : ?>
							<div class="sn-color-field">
								<label class="sn-label" for="sn_<?php echo esc_attr( $color_key ); ?>"><?php echo esc_html( $color_label ); ?></label>
								<input type="text" id="sn_<?php echo esc_attr( $color_key ); ?>"
									name="settings[<?php echo esc_attr( $color_key ); ?>]"
									value="<?php echo esc_attr( $settings[ $color_key ] ); ?>"
									class="sn-color-picker" data-default-color="<?php echo esc_attr( $settings[ $color_key ] ); ?>">
							</div>
						<?php endforeach; ?>
					</div>
				</div>

				<!-- Typography -->
				<div class="sn-card">
					<div class="sn-card__header"><h2><?php esc_html_e( 'Typography', 'sales-notification' ); ?></h2></div>
					<div class="sn-card__body">
						<div class="sn-field">
							<label class="sn-label" for="sn_font_family"><?php esc_html_e( 'Font Family', 'sales-notification' ); ?></label>
							<div class="sn-field__control">
								<select id="sn_font_family" name="settings[font_family]" class="sn-select sn-font-select">
									<option value="inherit" <?php selected( $settings['font_family'], 'inherit' ); ?>><?php esc_html_e( 'Inherit from theme', 'sales-notification' ); ?></option>
									<option value="'Inter', sans-serif" <?php selected( $settings['font_family'], "'Inter', sans-serif" ); ?>>Inter</option>
									<option value="'Roboto', sans-serif" <?php selected( $settings['font_family'], "'Roboto', sans-serif" ); ?>>Roboto</option>
									<option value="'Outfit', sans-serif" <?php selected( $settings['font_family'], "'Outfit', sans-serif" ); ?>>Outfit</option>
									<option value="'Poppins', sans-serif" <?php selected( $settings['font_family'], "'Poppins', sans-serif" ); ?>>Poppins</option>
									<option value="'Open Sans', sans-serif" <?php selected( $settings['font_family'], "'Open Sans', sans-serif" ); ?>>Open Sans</option>
									<option value="Arial, sans-serif" <?php selected( $settings['font_family'], 'Arial, sans-serif' ); ?>>Arial</option>
									<option value="Georgia, serif" <?php selected( $settings['font_family'], 'Georgia, serif' ); ?>>Georgia</option>
								</select>
							</div>
						</div>
						<div class="sn-field">
							<label class="sn-label" for="sn_font_size"><?php esc_html_e( 'Font Size (px)', 'sales-notification' ); ?></label>
							<div class="sn-field__control">
								<input type="number" id="sn_font_size" name="settings[font_size]"
									value="<?php echo esc_attr( $settings['font_size'] ); ?>" min="10" max="24" class="sn-input-sm">
							</div>
						</div>
						<div class="sn-field">
							<label class="sn-label" for="sn_font_weight"><?php esc_html_e( 'Font Weight', 'sales-notification' ); ?></label>
							<div class="sn-field__control">
								<select id="sn_font_weight" name="settings[font_weight]" class="sn-select sn-select--sm">
									<?php foreach ( $font_weights as $weight ) : ?>
										<option value="<?php echo esc_attr( $weight ); ?>" <?php selected( $settings['font_weight'], $weight ); ?>>
											<?php echo esc_html( $weight ); ?>
										</option>
									<?php endforeach; ?>
								</select>
							</div>
						</div>
					</div>
				</div>

				<!-- Shape & Shadow -->
				<div class="sn-card">
					<div class="sn-card__header"><h2><?php esc_html_e( 'Shape & Shadow', 'sales-notification' ); ?></h2></div>
					<div class="sn-card__body">
						<div class="sn-field">
							<label class="sn-label" for="sn_border_radius"><?php esc_html_e( 'Border Radius (px)', 'sales-notification' ); ?></label>
							<div class="sn-field__control">
								<div class="sn-range-wrap">
									<input type="range" class="sn-range" min="0" max="50"
										value="<?php echo esc_attr( $settings['border_radius'] ); ?>"
										data-target="sn_border_radius">
									<input type="number" id="sn_border_radius" name="settings[border_radius]"
										value="<?php echo esc_attr( $settings['border_radius'] ); ?>" min="0" max="50" class="sn-input-sm">
								</div>
							</div>
						</div>
						<div class="sn-field">
							<label class="sn-label" for="sn_border_width"><?php esc_html_e( 'Border Width (px)', 'sales-notification' ); ?></label>
							<div class="sn-field__control">
								<input type="number" id="sn_border_width" name="settings[border_width]"
									value="<?php echo esc_attr( $settings['border_width'] ); ?>" min="0" max="10" class="sn-input-sm">
							</div>
						</div>
						<div class="sn-field">
							<label class="sn-label" for="sn_box_shadow"><?php esc_html_e( 'Box Shadow', 'sales-notification' ); ?></label>
							<div class="sn-field__control">
								<select id="sn_box_shadow" name="settings[box_shadow]" class="sn-select sn-select--sm">
									<?php foreach ( $shadows as $shadow_key => $shadow_label ) : ?>
										<option value="<?php echo esc_attr( $shadow_key ); ?>" <?php selected( $settings['box_shadow'], $shadow_key ); ?>>
											<?php echo esc_html( $shadow_label ); ?>
										</option>
									<?php endforeach; ?>
								</select>
							</div>
						</div>
						<div class="sn-field">
							<label class="sn-label" for="sn_max_width"><?php esc_html_e( 'Max Width (px)', 'sales-notification' ); ?></label>
							<div class="sn-field__control">
								<input type="number" id="sn_max_width" name="settings[max_width]"
									value="<?php echo esc_attr( $settings['max_width'] ); ?>" min="200" max="500" class="sn-input-sm">
							</div>
						</div>
						<div class="sn-field">
							<label class="sn-label" for="sn_image_size"><?php esc_html_e( 'Image Size (px)', 'sales-notification' ); ?></label>
							<div class="sn-field__control">
								<input type="number" id="sn_image_size" name="settings[image_size]"
									value="<?php echo esc_attr( $settings['image_size'] ); ?>" min="40" max="120" class="sn-input-sm">
							</div>
						</div>
						<div class="sn-field sn-field--switch">
							<label class="sn-label"><?php esc_html_e( 'Show Close Button', 'sales-notification' ); ?></label>
							<div class="sn-field__control">
								<label class="sn-toggle">
									<input type="checkbox" name="settings[show_close_button]" value="1"
										<?php checked( $settings['show_close_button'], true ); ?>>
									<span class="sn-toggle__slider"></span>
								</label>
							</div>
						</div>
					</div>
				</div>

				<!-- Custom CSS -->
				<div class="sn-card">
					<div class="sn-card__header"><h2><?php esc_html_e( 'Custom CSS', 'sales-notification' ); ?></h2></div>
					<div class="sn-card__body">
						<div class="sn-field">
							<label class="sn-label" for="sn_custom_css"><?php esc_html_e( 'Additional CSS', 'sales-notification' ); ?></label>
							<div class="sn-field__control">
								<textarea id="sn_custom_css" name="settings[custom_css]" rows="8" class="sn-textarea sn-code"><?php echo esc_textarea( $settings['custom_css'] ); ?></textarea>
								<p class="sn-field__desc"><?php esc_html_e( 'Custom CSS injected on the frontend. Use .sn-popup as the base selector.', 'sales-notification' ); ?></p>
							</div>
						</div>
					</div>
				</div>

				<div class="sn-form-footer">
					<button type="submit" class="sn-btn sn-btn--primary" id="sn-save-btn">
						<span class="sn-btn__icon dashicons dashicons-saved"></span>
						<?php esc_html_e( 'Save Settings', 'sales-notification' ); ?>
					</button>
				</div>
			</form>
		</div>

		<!-- Live Preview Column -->
		<div class="sn-design-preview">
			<div class="sn-preview-label"><?php esc_html_e( 'Live Preview', 'sales-notification' ); ?></div>
			<div class="sn-preview-viewport">
				<div id="sn-live-preview" class="sn-popup sn-template-1 sn-anim-slide-in" role="alert">
					<button class="sn-popup__close" aria-label="<?php esc_attr_e( 'Close notification', 'sales-notification' ); ?>">×</button>
					<div class="sn-popup__inner">
						<div class="sn-popup__image-wrap">
							<img class="sn-popup__image" src="<?php echo esc_url( SN_PLUGIN_URL . 'assets/images/demo-product.png' ); ?>"
								alt="<?php esc_attr_e( 'Product image', 'sales-notification' ); ?>" width="60" height="60">
						</div>
						<div class="sn-popup__content">
							<p class="sn-popup__name"><strong><?php esc_html_e( 'John D.', 'sales-notification' ); ?></strong> <?php esc_html_e( 'purchased', 'sales-notification' ); ?></p>
							<p class="sn-popup__product"><?php esc_html_e( 'Wireless Headphones', 'sales-notification' ); ?></p>
							<p class="sn-popup__meta">
								<span class="sn-popup__location"><?php esc_html_e( 'New York, US', 'sales-notification' ); ?></span>
								<span class="sn-popup__time"><?php esc_html_e( '2 hours ago', 'sales-notification' ); ?></span>
							</p>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div><!-- .sn-design-layout -->
</div>
