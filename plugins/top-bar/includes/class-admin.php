<?php
/**
 * Top Bar admin settings page and option registration.
 *
 * @package TopBar
 */

declare(strict_types=1);

namespace TopBar;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Admin {

	public function __construct() {
		add_action( 'admin_menu', [ $this, 'add_settings_page' ] );
		add_action( 'admin_init', [ $this, 'handle_bar_actions' ], 5 );
		add_action( 'admin_init', [ $this, 'register_settings' ] );
	}

	/**
	 * Add/remove bar (GET + nonce). Order is array order in `top_bars`.
	 */
	public function handle_bar_actions(): void {
		if ( ! isset( $_GET['page'] ) || sanitize_key( (string) wp_unslash( $_GET['page'] ) ) !== 'top-bar' ) {
			return;
		}
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		if ( isset( $_GET['top_bar_add'] ) ) {
			check_admin_referer( 'top_bar_add' );
			$bars = get_option( Options::OPTION_BARS, [] );
			if ( ! is_array( $bars ) ) {
				$bars = [];
			}
			$bars = array_values( array_filter( $bars, 'is_array' ) );
			if ( count( $bars ) >= Options::max_bars() ) {
				wp_safe_redirect( admin_url( 'options-general.php?page=top-bar&top_bar_max=1' ) );
				exit;
			}
			$bars[] = Options::default_bar();
			update_option( Options::OPTION_BARS, $bars );
			wp_safe_redirect( admin_url( 'options-general.php?page=top-bar' ) );
			exit;
		}

		if ( isset( $_GET['top_bar_remove'] ) ) {
			$raw_id = isset( $_GET['top_bar_remove'] ) ? (string) wp_unslash( $_GET['top_bar_remove'] ) : '';
			$id     = sanitize_text_field( $raw_id );
			check_admin_referer( 'top_bar_remove_' . $id );
			$bars = get_option( Options::OPTION_BARS, [] );
			if ( ! is_array( $bars ) || count( $bars ) <= Options::MIN_BARS ) {
				wp_safe_redirect( admin_url( 'options-general.php?page=top-bar&top_bar_min=1' ) );
				exit;
			}
			$next = [];
			foreach ( $bars as $row ) {
				if ( ! is_array( $row ) ) {
					continue;
				}
				$bid = isset( $row['id'] ) ? (string) $row['id'] : '';
				if ( $bid !== $id ) {
					$next[] = $row;
				}
			}
			if ( count( $next ) < Options::MIN_BARS ) {
				wp_safe_redirect( admin_url( 'options-general.php?page=top-bar&top_bar_min=1' ) );
				exit;
			}
			update_option( Options::OPTION_BARS, array_values( $next ) );
			wp_safe_redirect( admin_url( 'options-general.php?page=top-bar' ) );
			exit;
		}

		if ( isset( $_GET['top_bar_remove_message'], $_GET['top_bar_message_index'] ) ) {
			$raw_id = (string) wp_unslash( $_GET['top_bar_remove_message'] );
			$id     = sanitize_text_field( $raw_id );
			$index  = max( 0, (int) $_GET['top_bar_message_index'] );
			check_admin_referer( 'top_bar_remove_message_' . $id . '_' . $index );

			$bars = get_option( Options::OPTION_BARS, [] );
			if ( ! is_array( $bars ) ) {
				$bars = [];
			}

			foreach ( $bars as $bar_idx => $row ) {
				if ( ! is_array( $row ) ) {
					continue;
				}
				$bid = isset( $row['id'] ) ? (string) $row['id'] : '';
				if ( $bid !== $id ) {
					continue;
				}

				$messages = isset( $row['messages'] ) && is_array( $row['messages'] ) ? array_values( $row['messages'] ) : [];
				if ( count( $messages ) <= 1 ) {
					break;
				}
				if ( array_key_exists( $index, $messages ) ) {
					unset( $messages[ $index ] );
					$row['messages'] = array_values( $messages );
					$bars[ $bar_idx ] = $row;
				}
				break;
			}

			update_option( Options::OPTION_BARS, array_values( $bars ) );
			wp_safe_redirect( admin_url( 'options-general.php?page=top-bar' ) );
			exit;
		}

		if ( isset( $_GET['top_bar_add_message'] ) ) {
			$raw_id = isset( $_GET['top_bar_add_message'] ) ? (string) wp_unslash( $_GET['top_bar_add_message'] ) : '';
			$id     = sanitize_text_field( $raw_id );
			check_admin_referer( 'top_bar_add_message_' . $id );

			$bars = get_option( Options::OPTION_BARS, [] );
			if ( ! is_array( $bars ) ) {
				$bars = [];
			}

			foreach ( $bars as $idx => $row ) {
				if ( ! is_array( $row ) ) {
					continue;
				}
				$bid = isset( $row['id'] ) ? (string) $row['id'] : '';
				if ( $bid !== $id ) {
					continue;
				}

				$messages = isset( $row['messages'] ) && is_array( $row['messages'] ) ? array_values( $row['messages'] ) : [];
				$max_messages = Options::max_messages();
				if ( $max_messages > 1 && count( $messages ) < $max_messages ) {
					$messages[] = '';
				}
				$row['messages'] = $messages;
				$bars[ $idx ] = $row;
				break;
			}

			update_option( Options::OPTION_BARS, array_values( $bars ) );
			wp_safe_redirect( admin_url( 'options-general.php?page=top-bar' ) );
			exit;
		}
	}

	public function add_settings_page(): void {
		add_options_page(
			__( 'Top Bar', 'top-bar' ),
			__( 'Top Bar', 'top-bar' ),
			'manage_options',
			'top-bar',
			[ $this, 'render_settings_page' ]
		);
	}

	public function register_settings(): void {
		register_setting(
			'top_bar_settings',
			Options::OPTION_BARS,
			[
				'type'              => 'array',
				'sanitize_callback' => [ Options::class, 'sanitize_bars_input' ],
				'default'           => [],
			]
		);
	}

	public function render_settings_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$bars = Options::get_bars();
		$add_url = wp_nonce_url(
			add_query_arg(
				[
					'page'        => 'top-bar',
					'top_bar_add' => '1',
				],
				admin_url( 'options-general.php' )
			),
			'top_bar_add'
		);
		$can_add_bar = count( $bars ) < Options::max_bars();
		?>

		<form action="options.php" method="post">
			<?php settings_fields( 'top_bar_settings' ); ?>

		<div id="top-bar">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

			<?php if ( isset( $_GET['top_bar_max'] ) ) : ?>
				<div class="notice notice-warning"><p><?php echo esc_html( sprintf( /* translators: %d: max bars */ __( 'You can add at most %d top bars.', 'top-bar' ), Options::max_bars() ) ); ?></p></div>
			<?php endif; ?>
			<?php if ( isset( $_GET['top_bar_min'] ) ) : ?>
				<div class="notice notice-warning"><p><?php esc_html_e( 'At least one top bar must remain.', 'top-bar' ); ?></p></div>
			<?php endif; ?>
			
			<!-- Empty list  -->

			<div class="top-bar-row center empty">
				<p class="xlg bold"><?php esc_html_e( 'Welcome to Top Bar plugin', 'top-bar' ); ?></p>
				<p class="xs"><?php esc_html_e( 'Click the button to add your first Top Bar', 'top-bar' ); ?></p>	
				<?php if ( $can_add_bar ) : ?>
					<a href="<?php echo esc_url( $add_url ); ?>" class="top-bar-btn mint md"><?php esc_html_e( 'Add new Top Bar', 'top-bar' ); ?></a>
				<?php endif; ?>
			</div>

			<!-- List of buttons  -->

			<div class="top-bar-row rt">
				<?php if ( $can_add_bar ) : ?>
					<a href="<?php echo esc_url( $add_url ); ?>" class="top-bar-btn mint sm"><?php esc_html_e( 'Add new Top Bar', 'top-bar' ); ?></a>
				<?php endif; ?>
			</div>


			<!-- Pomysł: 
			 	Przenoszenie dodanych kolumn za pomca drag&drop			
			-->

			<?php
			foreach ( $bars as $i => $bar ) :
				if ( ! is_array( $bar ) ) {
					continue;
				}
				$bar_id         = isset( $bar['id'] ) ? (string) $bar['id'] : '';
				$bar_name       = isset( $bar['name'] ) ? (string) $bar['name'] : '';
				$position       = isset( $bar['position'] ) ? (string) $bar['position'] : 'top';
				$effect         = isset( $bar['effect'] ) ? (string) $bar['effect'] : 'none';
				$messages       = isset( $bar['messages'] ) && is_array( $bar['messages'] ) ? array_values( $bar['messages'] ) : [];
				$messages_mobile_visible = ! array_key_exists( 'messages_mobile_visible', $bar ) || ! empty( $bar['messages_mobile_visible'] );
				$bg_color       = isset( $bar['bg_color'] ) ? Options::sanitize_hex_color( (string) $bar['bg_color'] ) : '#1d2327';
				$frame_color    = isset( $bar['frame_color'] ) ? Options::sanitize_hex_color( (string) $bar['frame_color'] ) : '';
				$frame_width    = isset( $bar['frame_width'] ) ? (int) $bar['frame_width'] : 0;
				if ( $frame_width < 0 ) {
					$frame_width = 0;
				}
				if ( $frame_width > 10 ) {
					$frame_width = 10;
				}
				$hide_on_scroll = ! empty( $bar['hide_on_scroll'] );
				// `Options::normalize_bar()` already stores `visible` as a real boolean.
				$visible = ! empty( $bar['visible'] );
				// Whether the bar's settings details are expanded in the admin UI.
				$admin_visibile = ! empty( $bar['admin_visibile'] );
				// scheduling.
				$scheduled_enabled     = ! empty( $bar['scheduled_enabled'] );
				$scheduled_from_datetime = isset( $bar['scheduled_from_datetime'] ) ? (string) $bar['scheduled_from_datetime'] : '';
				$scheduled_to_datetime   = isset( $bar['scheduled_to_datetime'] ) ? (string) $bar['scheduled_to_datetime'] : '';
				$pf             = Options::OPTION_BARS . '[' . (int) $i . ']';
				$remove_url     = wp_nonce_url(
					add_query_arg(
						[
							'page'            => 'top-bar',
							'top_bar_remove' => $bar_id,
						],
						admin_url( 'options-general.php' )
					),
					'top_bar_remove_' . $bar_id
				);
				$add_message_url = wp_nonce_url(
					add_query_arg(
						[
							'page'                => 'top-bar',
							'top_bar_add_message' => $bar_id,
						],
						admin_url( 'options-general.php' )
					),
					'top_bar_add_message_' . $bar_id
				);
				$can_remove     = count( $bars ) > Options::MIN_BARS;
				?>

			<div class="top-bar-row bg">		
				
				<!-- Navigation -->
				<div class="top-bar-nav">
					<div class="item name">
						<p class="lg bold"><?php echo esc_html( $bar_name !== '' ? $bar_name : __( 'Name of TopaBar', 'top-bar' ) ); ?></p>
					</div>

					<div class="item nav">
						<button
							type="button"
							class="top-bar-icons top-bar-visibility-toggle <?php echo esc_attr( $visible ? 'status-on' : 'status-off' ); ?>"
							data-target-visible-id="<?php echo esc_attr( 'top_bar_visible_' . (int) $i ); ?>"
							aria-label="<?php esc_attr_e( 'Toggle bar visibility on page', 'top-bar' ); ?>"
						><?php esc_html_e( 'Visible On/Off', 'top-bar' ); ?></button>
						<input type="hidden" name="<?php echo esc_attr( $pf ); ?>[visible]" value="0" />
						<input
							type="checkbox"
							id="<?php echo esc_attr( 'top_bar_visible_' . (int) $i ); ?>"
							name="<?php echo esc_attr( $pf ); ?>[visible]"
							value="1"
							<?php checked( $visible ); ?>
						/>
						<?php if ( $can_remove ) : ?>
							<a href="<?php echo esc_url( $remove_url ); ?>" class="top-bar-icons delete" title="<?php esc_attr_e( 'Remove this bar', 'top-bar' ); ?>" aria-label="<?php esc_attr_e( 'Delete', 'top-bar' ); ?>"></a>
						<?php else : ?>
							<button type="button" class="top-bar-icons delete" disabled title="<?php esc_attr_e( 'At least one bar is required', 'top-bar' ); ?>"><?php esc_html_e( 'Delete', 'top-bar' ); ?></button>
						<?php endif; ?>
						<button
							type="button"
							class="top-bar-icons arrow-down top-bar-toggle-options"
							data-options-panel-id="<?php echo esc_attr( 'top-bar-options-' . (int) $i ); ?>"
							data-admin-visible-input-id="<?php echo esc_attr( 'top-bar-admin-visibile-' . (int) $i ); ?>"
							aria-expanded="<?php echo esc_attr( $admin_visibile ? 'true' : 'false' ); ?>"
							aria-controls="<?php echo esc_attr( 'top-bar-options-' . (int) $i ); ?>"
						><?php esc_html_e( 'Open/Close', 'top-bar' ); ?></button>
						<input type="hidden" id="<?php echo esc_attr( 'top-bar-admin-visibile-' . (int) $i ); ?>" name="<?php echo esc_attr( $pf ); ?>[admin_visibile]" value="<?php echo esc_attr( $admin_visibile ? '1' : '0' ); ?>" />
					</div>
				</div>
				<div id="<?php echo esc_attr( 'top-bar-options-' . (int) $i ); ?>" class="top-bar-options<?php echo $admin_visibile ? ' active' : ''; ?>">
					<div class="top-bar-grid">
						<div class="item">			
							<fieldset class="clear">
								<legend class="bold lg"><?php esc_html_e( 'Name', 'top-bar' ); ?></legend>
								<input type="hidden" name="<?php echo esc_attr( $pf ); ?>[id]" value="<?php echo esc_attr( $bar_id ); ?>" />
								<input type="text" id="top-bar-name" name="<?php echo esc_attr( $pf ); ?>[name]" value="<?php echo esc_attr( $bar_name ); ?>" placeholder="<?php esc_attr_e( 'Name of Top Bar', 'top-bar' ); ?>">
							</fieldset>
						</div>
					</div>
					<div class="top-bar-grid title">
						<div class="item">
							<p class="bold lg"><?php esc_html_e( 'Basic settings', 'top-bar' ); ?></p>
						</div>
					</div>
					<div class="top-bar-grid bg bg-blue">
						<div class="item">
							<fieldset class="clear">
								<legend class="bold"><?php esc_html_e( 'Position', 'top-bar' ); ?></legend>
								<select id="top_bar_position_<?php echo (int) $i; ?>" name="<?php echo esc_attr( $pf ); ?>[position]" aria-label="<?php esc_attr_e( 'Position', 'top-bar' ); ?>">
									<option value="top" <?php selected( $position, 'top' ); ?>><?php esc_html_e( 'Top', 'top-bar' ); ?></option>
									<option value="bottom" <?php selected( $position, 'bottom' ); ?>><?php esc_html_e( 'Bottom', 'top-bar' ); ?></option>
								</select>
							</fieldset>
						</div>
						<div class="item">
							<fieldset  class="clear">
								<legend class="bold">Fonts</legend>
								<select>
									<option value="Roboto">Roboto</option>
									<option value="Open Sans">Open Sans</option>
									<option value="Lato">Lato</option>
									<option value="Montserrat">Montserrat</option>
									<option value="Oswald">Oswald</option>
									<option value="Raleway">Raleway</option>
									<option value="Poppins">Poppins</option>
									<option value="Playfair Display">Playfair Display</option>
									<option value="Nunito">Nunito</option>
									<option value="Ubuntu">Ubuntu</option>
								</select>
							</fieldset>
						</div>
						<div class="item">
							<fieldset class="clear">
								<legend class="bold"><?php esc_html_e( 'Background', 'top-bar' ); ?></legend>
								<input type="color" id="top_bar_bg_color_<?php echo (int) $i; ?>" name="<?php echo esc_attr( $pf ); ?>[bg_color]" value="<?php echo esc_attr( $bg_color ?: '#1d2327' ); ?>" />
							</fieldset>
						</div>
						<div class="item">
							<fieldset class="clear">
								<legend class="bold"><?php esc_html_e( 'Border frame', 'top-bar' ); ?></legend>
								<div class="top-bar-border-frame-controls">
									<input type="color" id="top_bar_frame_color_<?php echo (int) $i; ?>" name="<?php echo esc_attr( $pf ); ?>[frame_color]" value="<?php echo esc_attr( $frame_color ?: '#000000' ); ?>" />
									<select name="<?php echo esc_attr( $pf ); ?>[frame_width]" aria-label="<?php esc_attr_e( 'Border width', 'top-bar' ); ?>">
										<?php
										for ( $px = 0; $px <= 10; $px += 1 ) {
											echo '<option value="' . (int) $px . '"' . selected( $frame_width, $px, false ) . '>' . (int) $px . 'px</option>';
										}
										?>
									</select>
								</div>
							</fieldset>
						</div>
						<div class="item">
							<fieldset class="clear">
								<legend class="bold"><?php esc_html_e( 'On scroll', 'top-bar' ); ?></legend>
								<select id="top_bar_hide_on_scroll_<?php echo (int) $i; ?>" name="<?php echo esc_attr( $pf ); ?>[hide_on_scroll]" aria-label="<?php esc_attr_e( 'On scroll', 'top-bar' ); ?>">
									<option value="0" <?php selected( ! $hide_on_scroll ); ?>><?php esc_html_e( 'Keep showing', 'top-bar' ); ?></option>
									<option value="1" <?php selected( $hide_on_scroll ); ?>><?php esc_html_e( 'Hide on scroll', 'top-bar' ); ?></option>
								</select>
								<p class="xs"><?php esc_html_e( 'Whether the bar stays visible or hides when the user scrolls the page.', 'top-bar' ); ?></p>
							</fieldset>
						</div>
					</div>

					<?php if ( defined( 'FF_SCHEDULE' ) && FF_SCHEDULE ) : ?>
						<div class="top-bar-grid title">
							<div class="item">
								<label class="check top-bar-life-time-checkbox">
									<input type="hidden" name="<?php echo esc_attr( $pf ); ?>[scheduled_enabled]" value="0" />
									<input
										type="checkbox"
										class="top-bar-toggle-life-time"
										name="<?php echo esc_attr( $pf ); ?>[scheduled_enabled]"
										data-lifetime-panel-id="<?php echo esc_attr( 'top-bar-lifetime-panel-' . (int) $i ); ?>"
										value="1"
										<?php checked( $scheduled_enabled ); ?>
									>
									<span class="lifetime-label">
										<p class="bold lg"><?php esc_html_e( 'Scheduled', 'top-bar' ); ?></p>
									</span>
									<span class="lifetime-description">
										<p class="xs"><?php esc_html_e( 'Schedule when the bar should be visible.', 'top-bar' ); ?></p>
									</span>
								</label>
							</div>
						</div>
						
						<div
							id="<?php echo esc_attr( 'top-bar-lifetime-panel-' . (int) $i ); ?>"
							class="top-bar-grid bg bg-amber top-bar-lifetime-panel"
							<?php echo $scheduled_enabled ? '' : 'hidden'; ?>
						>
							<div class="item">
								<fieldset class="clear">
									<legend class="bold"><?php esc_html_e( 'From', 'top-bar' ); ?></legend>
									<label>
									<input
										type="datetime-local"
										id="<?php echo esc_attr( 'top-bar-datetime-from-' . (int) $i ); ?>"
										name="<?php echo esc_attr( $pf ); ?>[scheduled_from_datetime]"
										class="top-bar-life-time-datetime"
										value="<?php echo esc_attr( $scheduled_from_datetime ); ?>"
									>
					
								</label>
								</fieldset>
							</div>
							<div class="item">
								<fieldset class="clear">
									<legend class="bold"><?php esc_html_e( 'To', 'top-bar' ); ?></legend>
									<label>
									<input
										type="datetime-local"
										id="<?php echo esc_attr( 'top-bar-datetime-to-' . (int) $i ); ?>"
										name="<?php echo esc_attr( $pf ); ?>[scheduled_to_datetime]"
										class="top-bar-life-time-datetime"
										value="<?php echo esc_attr( $scheduled_to_datetime ); ?>"
									>
									</label>
							</fieldset>
							</div>
						</div>
					<?php endif; ?>

					<div class="top-bar-grid title">
						<div class="item">
							<p class="bold lg"><?php esc_html_e( 'Create a design', 'top-bar' ); ?></p>
							<p class="xs"><?php esc_html_e( 'Create your own top bar. You can add a maximum of 4 columns, choosing different types of content.', 'top-bar' ); ?></p>
						</div>
					</div>

					<div class="top-bar-grid">
						<div id="top-bar-column-creator">

	<!-- Text  -->

						<div class="top-bar-column-creator-grid">
							<div class="item-creator no">								
								<p class="bold lg">1</p>								
							</div>					
							<div class="item-creator">
								<fieldset>
									<legend class="bold"><?php esc_html_e( 'Type', 'top-bar' ); ?></legend>
									<label><input type="radio" name="top_bar_position__ui_mock" value="top" <?php checked( $position, 'top' ); ?> /> <?php esc_html_e( 'Text Editor', 'top-bar' ); ?></label>
									<label><input type="radio" name="top_bar_position__ui_mock" value="top" <?php checked( $position, 'top' ); ?> /> <?php esc_html_e( 'Social media', 'top-bar' ); ?></label>
									<label><input type="radio" name="top_bar_position__ui_mock" value="top" <?php checked( $position, 'top' ); ?> /> <?php esc_html_e( 'Contact data', 'top-bar' ); ?></label>
								</fieldset>
							</div>
						
							<div class="item-creator lg">
								<fieldset class="line">
									<legend class="bold"><?php esc_html_e( 'Effect', 'top-bar' ); ?></legend>
									<label>
											<select name="<?php echo esc_attr( $pf ); ?>[effect]" aria-label="<?php esc_attr_e( 'Effect', 'top-bar' ); ?>">
												<option value="none" <?php selected( $effect, 'none' ); ?>><?php esc_html_e( 'None', 'top-bar' ); ?></option>
												<option value="slider" <?php selected( $effect, 'slider' ); ?>><?php esc_html_e( 'Slider', 'top-bar' ); ?></option>
												<option value="fadein" <?php selected( $effect, 'fadein' ); ?>><?php esc_html_e( 'Fade In', 'top-bar' ); ?></option>
												<option value="blink" <?php selected( $effect, 'blink' ); ?>><?php esc_html_e( 'Blink', 'top-bar' ); ?></option>
											</select>
									</label>
								</fieldset>
								<fieldset class="line">
									<legend class="bold"><?php esc_html_e( 'Add multi fields', 'top-bar' ); ?></legend>
								
									<div class="top-bar-message-list" data-pf="<?php echo esc_attr( $pf ); ?>" data-bar-index="<?php echo esc_attr( (string) (int) $i ); ?>">
										<?php
										$message_count = max( 1, min( count( $messages ), Options::max_messages() ) );
										for ( $mi = 0; $mi < $message_count; $mi++ ) :
											$editor_value = isset( $messages[ $mi ] ) && is_string( $messages[ $mi ] )
												? $messages[ $mi ]
												: ( $mi === 0 ? __( 'Welcome!', 'top-bar' ) : '' );
											$remove_message_url = wp_nonce_url(
												add_query_arg(
													[
														'page'                   => 'top-bar',
														'top_bar_remove_message' => $bar_id,
														'top_bar_message_index'  => (int) $mi,
													],
													admin_url( 'options-general.php' )
												),
												'top_bar_remove_message_' . $bar_id . '_' . (int) $mi
											);
											?>
											<div class="top-bar-column-creator-grid">
												<div class="item-creator no">								
													<p class="bold md"><?php echo esc_html( (string) ( $mi + 1 ) ); ?></p>								
													<?php if ( $message_count > 1 ) : ?>
														<a href="<?php echo esc_url( $remove_message_url ); ?>" class="top-bar-btn amber sm"><?php esc_html_e( 'X', 'top-bar' ); ?></a>
													<?php endif; ?>
												</div>
												<div class="item-creator">						
													<?php
													wp_editor( $editor_value, 'top_bar_message_' . (int) $i . '_' . (int) $mi, [
														'textarea_name' => $pf . '[messages][' . (int) $mi . ']',
														'textarea_rows' => 2,
														'media_buttons' => false,
														'teeny'         => true,
														'quicktags'     => false,
														'tinymce'       => [
															'resize'   => false,
															'plugins'  => 'textcolor',
															'toolbar1' => 'formatselect,bold,italic,forecolor,backcolor,link,unlink,bullist,numlist,blockquote,undo,redo',
														],
														'editor_css'    => '',
														'dfw'           => false,
													] );
													?>									
												</div>
											</div>
										<?php endfor; ?>
									</div>
								</fieldset>								
								<div class="top-bar-row rt">
									<?php
									$max_messages = Options::max_messages();
									$can_add_message = $max_messages > 1 && count( $messages ) < $max_messages;
									?>
									<?php if ( $can_add_message ) : ?>
										<a href="<?php echo esc_url( $add_message_url ); ?>" class="top-bar-btn amber sm right"><?php esc_html_e( 'Add new text', 'top-bar' ); ?></a>
									<?php endif; ?>
								</div>
							</div>

							<div class="item item-creator">
								<fieldset>
									<legend class="bold"><?php esc_html_e( 'Size column', 'top-bar' ); ?></legend>
									<label>
										<select name="size">
											<?php
												for ( $pct = 0; $pct <= 100; $pct += 5 ) {
												echo '<option value="' . (int) $pct . '">' . (int) $pct . '%</option>';
											}
										?>
									</select>

									</label>
								</fieldset>
								<fieldset>
									<legend class="bold"><?php esc_html_e( 'Visible on the mobile', 'top-bar' ); ?></legend>
									<select name="<?php echo esc_attr( $pf ); ?>[messages_mobile_visible]" aria-label="<?php esc_attr_e( 'Visible on the mobile', 'top-bar' ); ?>">
										<option value="1" <?php selected( $messages_mobile_visible, true ); ?>><?php esc_html_e( 'On', 'top-bar' ); ?></option>
										<option value="0" <?php selected( $messages_mobile_visible, false ); ?>><?php esc_html_e( 'Off', 'top-bar' ); ?></option>
									</select>			
								</fieldset>							
							</div>
						</div>	

	<!-- Social media -->

						<div class="top-bar-column-creator-grid">
							<div class="item-creator no">								
								<p class="bold lg">2</p>								
							</div>
							<div class="item-creator">
								<fieldset>
									<legend class="bold"><?php esc_html_e( 'Type', 'top-bar' ); ?></legend>
									<label><input type="radio" name="top_bar_position__ui_mock" value="top" <?php checked( $position, 'top' ); ?> /> <?php esc_html_e( 'Text Editor', 'top-bar' ); ?></label><br />
									<label><input type="radio" name="top_bar_position__ui_mock" value="top" <?php checked( $position, 'top' ); ?> /> <?php esc_html_e( 'Social media', 'top-bar' ); ?></label><br />
									<label><input type="radio" name="top_bar_position__ui_mock" value="top" <?php checked( $position, 'top' ); ?> /> <?php esc_html_e( 'Contact data', 'top-bar' ); ?></label><br />
								</fieldset>
							</div>							
							<div class="item-creator lg">
								<fieldset class="line">
									<legend class="bold"><?php esc_html_e( 'Choose the icon appearance', 'top-bar' ); ?></legend>
									<label><input type="radio" name="top_bar_position__ui_mock" value="top" <?php checked( $position, 'top' ); ?> /> Zaokraglony (tutaj podgląd ikon)</label><br />
									<label><input type="radio" name="top_bar_position__ui_mock" value="top" <?php checked( $position, 'top' ); ?> /> Kwadratowy</label><br />
									<label><input type="radio" name="top_bar_position__ui_mock" value="top" <?php checked( $position, 'top' ); ?> /> Sama ikona</label><br />
								</fieldset>
								<div class="top-bar-grid">
									<div class="item">
										<fieldset class="line">
											<legend class="bold"><?php esc_html_e( 'Background color', 'top-bar' ); ?></legend>
											<label><input type="color" id="top_bar_frame_color_social_bg" name="top_bar_frame_color__ui_mock_social_bg" value="<?php echo esc_attr( $frame_color ?: '#000000' ); ?>" /></label>
										</fieldset>
									</div>
									<div class="item">
										<fieldset class="line">
											<legend class="bold"><?php esc_html_e( 'Color icon', 'top-bar' ); ?></legend>
											<label><input type="color" id="top_bar_frame_color_social_icon" name="top_bar_frame_color__ui_mock_social_icon" value="<?php echo esc_attr( $frame_color ?: '#000000' ); ?>" /></label>
										</fieldset>
									</div>
								</div>
								<fieldset class="line">
									<legend class="bold"><?php esc_html_e( 'Add contact', 'top-bar' ); ?></legend>
									<div class="top-bar-column-creator-grid">
										<div class="item-creator no">								
											<p class="bold md">1</p>								
										</div>
										<div class="item-creator vertical">	

											<select name="social_media">
												<option value="">Select social media</option>
												<option value="facebook">Facebook</option>
												<option value="instagram">Instagram</option>
												<option value="x">X (Twitter)</option>
												<option value="linkedin">LinkedIn</option>
												<option value="youtube">YouTube</option>
												<option value="tiktok">TikTok</option>
												<option value="pinterest">Pinterest</option>
												<option value="snapchat">Snapchat</option>
												<option value="reddit">Reddit</option>
												<option value="tumblr">Tumblr</option>
												<option value="whatsapp">WhatsApp</option>
												<option value="telegram">Telegram</option>
												<option value="discord">Discord</option>
												<option value="threads">Threads</option>
												<option value="mastodon">Mastodon</option>
												<option value="medium">Medium</option>
												<option value="github">GitHub</option>
												<option value="dribbble">Dribbble</option>
												<option value="behance">Behance</option>
												<option value="flickr">Flickr</option>
											</select>
											<input type="text" name="top_bar_position__ui_mock" value="Your proflie link" <?php checked( $position, 'top' ); ?> />
				
									</div>
								</div>
								<!-- // -->
								<div class="top-bar-column-creator-grid">
									<div class="item-creator no">								
										<p class="bold md">2</p>								
									</div>
									<div class="item-creator">						
										<fieldset class="vertical">					
											<select name="social_media">
												<option value="">Select social media</option>
												<option value="facebook">Facebook</option>
												<option value="instagram">Instagram</option>
												<option value="x">X (Twitter)</option>
												<option value="linkedin">LinkedIn</option>
												<option value="youtube">YouTube</option>
												<option value="tiktok">TikTok</option>
												<option value="pinterest">Pinterest</option>
												<option value="snapchat">Snapchat</option>
												<option value="reddit">Reddit</option>
												<option value="tumblr">Tumblr</option>
												<option value="whatsapp">WhatsApp</option>
												<option value="telegram">Telegram</option>
												<option value="discord">Discord</option>
												<option value="threads">Threads</option>
												<option value="mastodon">Mastodon</option>
												<option value="medium">Medium</option>
												<option value="github">GitHub</option>
												<option value="dribbble">Dribbble</option>
												<option value="behance">Behance</option>
												<option value="flickr">Flickr</option>
											</select>
											<input type="text" name="top_bar_position__ui_mock" value="top" <?php checked( $position, 'top' ); ?> />
										</fieldset>
									</div>
								</div>
								<div class="top-bar-row rt">
									<a href="#" class="top-bar-btn amber sm right"><?php esc_html_e( 'Add new social media', 'top-bar' ); ?></a>	
								</div>
							</div>

							<div class="item-creator">
								<fieldset>
									<legend class="bold"><?php esc_html_e( 'Size column', 'top-bar' ); ?></legend>
									<label>
										<select name="size">
											<?php
												for ( $pct = 0; $pct <= 100; $pct += 5 ) {
												echo '<option value="' . (int) $pct . '">' . (int) $pct . '%</option>';
											}
										?>
									</select>

									</label>
								</fieldset>
								<fieldset>
									<legend class="bold"><?php esc_html_e( 'Visible on the mobile', 'top-bar' ); ?></legend>
									<select name="top_bar_visible_mobile_ui" aria-label="<?php esc_attr_e( 'Visible on the mobile', 'top-bar' ); ?>">
										<option value="on"><?php esc_html_e( 'On', 'top-bar' ); ?></option>
										<option value="off"><?php esc_html_e( 'Off', 'top-bar' ); ?></option>
									</select>			
								</fieldset>					
							</div>
						</div>	

<!-- Contact -->
						<div class="top-bar-column-creator-grid">
							<div class="item-creator no">								
								<p class="bold lg">3</p>								
							</div>
							<div class="item-creator">
								<fieldset>
									<legend class="bold"><?php esc_html_e( 'Type', 'top-bar' ); ?></legend>
									<label><input type="radio" name="top_bar_position__ui_mock" value="top" <?php checked( $position, 'top' ); ?> /> <?php esc_html_e( 'Text Editor', 'top-bar' ); ?></label><br />
									<label><input type="radio" name="top_bar_position__ui_mock" value="top" <?php checked( $position, 'top' ); ?> /> <?php esc_html_e( 'Social media', 'top-bar' ); ?></label><br />
									<label><input type="radio" name="top_bar_position__ui_mock" value="top" <?php checked( $position, 'top' ); ?> /> <?php esc_html_e( 'Contact data', 'top-bar' ); ?></label><br />
								</fieldset>
							</div>							
							<div class="item-creator lg">
								<fieldset class="line">
									<legend class="bold"><?php esc_html_e( 'Choose the icon appearance', 'top-bar' ); ?></legend>
									<label><input type="radio" name="top_bar_position__ui_mock" value="top" <?php checked( $position, 'top' ); ?> /> Zaokraglony (tutaj podgląd ikon)</label><br />
									<label><input type="radio" name="top_bar_position__ui_mock" value="top" <?php checked( $position, 'top' ); ?> /> Kwadratowy</label><br />
									<label><input type="radio" name="top_bar_position__ui_mock" value="top" <?php checked( $position, 'top' ); ?> /> Sama ikona</label><br />
								</fieldset>
								<div class="top-bar-grid">
									<div class="item">
										<fieldset class="line">
											<legend class="bold"><?php esc_html_e( 'Background color', 'top-bar' ); ?></legend>
											<label><input type="color" id="top_bar_frame_color_contact_bg" name="top_bar_frame_color__ui_mock_contact_bg" value="<?php echo esc_attr( $frame_color ?: '#000000' ); ?>" /></label>
										</fieldset>
									</div>
									<div class="item">
										<fieldset class="line">
											<legend class="bold"><?php esc_html_e( 'Color icon', 'top-bar' ); ?></legend>
											<label><input type="color" id="top_bar_frame_color_contact_icon" name="top_bar_frame_color__ui_mock_contact_icon" value="<?php echo esc_attr( $frame_color ?: '#000000' ); ?>" /></label>
										</fieldset>
									</div>
								</div>
								<fieldset class="line">
									<legend class="bold"><?php esc_html_e( 'Add your contact', 'top-bar' ); ?></legend>
									<div class="top-bar-column-creator-grid">
										<div class="item-creator no">								
											<p class="bold md">1</p>								
										</div>
										<div class="item-creator vertical">	

											<select name="contact_type">
												<option value="">Select type</option>
												<option value="email">Email</option>
												<option value="phone">Phone</option>
												<option value="mobile">Mobile</option>
												<option value="address">Address</option>
												<option value="location">Map location</option>
												<option value="website">Website</option>
												<option value="fax">Fax</option>
												<option value="support">Customer support</option>
												<option value="calendar">Appointment / Booking</option>
										</select>
												<input type="text" name="top_bar_position__ui_mock" value="Your profile link" <?php checked( $position, 'top' ); ?> />
				
									</div>
								</div>
								<!-- // -->
								<div class="top-bar-column-creator-grid">
									<div class="item-creator no">								
										<p class="bold md">2</p>								
									</div>
									<div class="item-creator">						
										<fieldset class="vertical">					
										
											<select name="contact_type">
												<option value="">Select type</option>
												<option value="email">Email</option>
												<option value="phone">Phone</option>
												<option value="mobile">Mobile</option>
												<option value="address">Address</option>
												<option value="location">Map location</option>
												<option value="website">Website</option>
												<option value="fax">Fax</option>
												<option value="support">Customer support</option>
												<option value="calendar">Appointment / Booking</option>
										</select>
												<input type="text" name="top_bar_position__ui_mock" value="Your profile link" <?php checked( $position, 'top' ); ?> />
										</fieldset>
									</div>
								</div>
								<div class="top-bar-row rt">
									<a href="#" class="top-bar-btn amber sm right"><?php esc_html_e( 'Add new contact', 'top-bar' ); ?></a>	
								</div>
							</div>

							<div class="item-creator">
								<fieldset>
									<legend class="bold"><?php esc_html_e( 'Size column', 'top-bar' ); ?></legend>
									<label>
										<select name="size">
											<?php
												for ( $pct = 0; $pct <= 100; $pct += 5 ) {
												echo '<option value="' . (int) $pct . '">' . (int) $pct . '%</option>';
											}
										?>
									</select>
									</label>
								</fieldset>
								<fieldset>
									<legend class="bold"><?php esc_html_e( 'Visible on the mobile', 'top-bar' ); ?></legend>
									<select name="top_bar_visible_mobile_ui" aria-label="<?php esc_attr_e( 'Visible on the mobile', 'top-bar' ); ?>">
										<option value="on"><?php esc_html_e( 'On', 'top-bar' ); ?></option>
										<option value="off"><?php esc_html_e( 'Off', 'top-bar' ); ?></option>
									</select>
								</fieldset>
							</div>
						</div>	

					</div>					
				</div>

				

				<div class="top-bar-row rt">
					<a href="#" class="top-bar-btn mint sm"><?php esc_html_e( 'Add new column', 'top-bar' ); ?>n</a>	
				</div>

			<!-- End options -->
			</div>
			</div>

			<?php endforeach; ?>

		</div>

		<div class="wrap">
				<?php submit_button(); ?>
		</div>

		<script>
		(function(){
			function syncButton(btn){
				var targetId = btn.getAttribute('data-target-visible-id');
				if(!targetId) return;
				var cb = document.getElementById(targetId);
				if(!cb) return;
				var visible = cb.checked;
				btn.classList.toggle('status-off', !visible);
				btn.classList.toggle('status-on', visible);
			}
			document.querySelectorAll('.top-bar-visibility-toggle').forEach(function(btn){
				syncButton(btn);
				btn.addEventListener('click', function(e){
					var targetId = btn.getAttribute('data-target-visible-id');
					if(!targetId) return;
					var cb = document.getElementById(targetId);
					if(!cb) return;
					cb.checked = !cb.checked;
					cb.dispatchEvent(new Event('change', { bubbles: true }));
					syncButton(btn);
				});
			});

			document.querySelectorAll('.top-bar-toggle-options').forEach(function(btn){
				btn.addEventListener('click', function(){
					var id = btn.getAttribute('data-options-panel-id');
					if(!id) return;
					var panel = document.getElementById(id);
					if(!panel) return;
					panel.classList.toggle('active');
					var open = panel.classList.contains('active');
					btn.setAttribute('aria-expanded', open ? 'true' : 'false');

					var inputId = btn.getAttribute('data-admin-visible-input-id');
					if(inputId){
						var hidden = document.getElementById(inputId);
						if(hidden){ hidden.value = open ? '1' : '0'; }
					}
				});
			});

			// Life time (datepicker) UI mock: enable + show when checkbox is checked.
			document.querySelectorAll('.top-bar-toggle-life-time').forEach(function(cb){
				var panelId = cb.getAttribute('data-lifetime-panel-id');
				if(!panelId) return;
				var panel = document.getElementById(panelId);
				if(!panel) return;

				function sync(){
					var enabled = cb.checked;
					panel.hidden = !enabled;
				}

				sync();
				cb.addEventListener('change', sync);
			});

		})();
		</script>
		</form>
		<?php
	}
}
