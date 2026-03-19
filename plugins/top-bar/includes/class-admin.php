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
		add_action( 'admin_init', [ $this, 'persist_bar_name' ], 5 );
		add_action( 'admin_init', [ $this, 'register_settings' ] );
	}

	public function persist_bar_name(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		if ( ! isset( $_POST['option_page'] ) || wp_unslash( (string) $_POST['option_page'] ) !== 'top_bar_settings' ) {
			return;
		}
		if ( ! isset( $_POST['top_bar_name'] ) ) {
			return;
		}
		$name = sanitize_text_field( wp_unslash( (string) $_POST['top_bar_name'] ) );
		update_option( 'top_bar_name', $name );
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
		register_setting( 'top_bar_settings', 'top_bar_name', [
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
		] );
		register_setting( 'top_bar_settings', 'top_bar_position', [
			'type'              => 'string',
			'sanitize_callback' => function ( $v ) {
				return in_array( $v, [ 'top', 'bottom' ], true ) ? $v : 'top';
			},
		] );
		register_setting( 'top_bar_settings', 'top_bar_message', [
			'type'              => 'string',
			'sanitize_callback' => 'wp_kses_post',
		] );
		register_setting( 'top_bar_settings', 'top_bar_bg_color', [
			'type'              => 'string',
			'sanitize_callback' => function ( $v ) {
				$v = is_string( $v ) ? ltrim( $v, '#' ) : '';
				return preg_match( '/^([A-Fa-f0-9]{3}){1,2}$/', $v ) ? '#' . $v : '#1d2327';
			},
		] );
		register_setting( 'top_bar_settings', 'top_bar_frame_color', [
			'type'              => 'string',
			'sanitize_callback' => function ( $v ) {
				if ( ! empty( $_POST['top_bar_frame_disable'] ) ) {
					return '';
				}
				if ( empty( $v ) || ! is_string( $v ) ) {
					return '';
				}
				$v = ltrim( $v, '#' );
				return preg_match( '/^([A-Fa-f0-9]{3}){1,2}$/', $v ) ? '#' . $v : '';
			},
		] );
		register_setting( 'top_bar_settings', 'top_bar_hide_on_scroll', [
			'type'              => 'string',
			'sanitize_callback' => function ( $v ) {
				return ( $v === '1' || $v === 1 ) ? '1' : '0';
			},
		] );
		register_setting( 'top_bar_settings', 'top_bar_status', [
			'type'              => 'string',
			'sanitize_callback' => function ( $v ) {
				return in_array( $v, [ 'on', 'off' ], true ) ? $v : 'on';
			},
		] );
	}

	public function render_settings_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$position       = get_option( 'top_bar_position', 'top' );
		$message        = get_option( 'top_bar_message', __( 'Welcome!', 'top-bar' ) );
		$bg_color       = get_option( 'top_bar_bg_color', '#1d2327' );
		$frame_color    = get_option( 'top_bar_frame_color', '' );
		$hide_on_scroll = get_option( 'top_bar_hide_on_scroll', '0' ) === '1';
		$status         = get_option( 'top_bar_status', 'on' );
		$bar_name       = get_option( 'top_bar_name', '' );
		?>

		<form action="options.php" method="post">
			<?php settings_fields( 'top_bar_settings' ); ?>

		<div id="top-bar">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			
			<!-- Empty list  -->

			<div class="top-bar-row center empty">
				<p class="xlg bold"><?php esc_html_e( 'Welcome to Top Bar plugin', 'top-bar' ); ?></p>
				<p class="xs"><?php esc_html_e( 'Click the button to add your first Top Bar', 'top-bar' ); ?></p>	
				<a href="#" class="top-bar-btn mint md"><?php esc_html_e( 'Add new Top Bar', 'top-bar' ); ?></a>	
			</div>

			<!-- List of buttons  -->

			<div class="top-bar-row rt">
				<a href="#" class="top-bar-btn mint sm"><?php esc_html_e( 'Add new Top Bar', 'top-bar' ); ?></a>	
			</div>


			<!-- Pomysł: 
			 	Przenoszenie dodanych kolumn za pomca drag&drop			
			-->

			<div class="top-bar-row bg">		
				
				<!-- Navigation -->
				<div id="top-bar-nav">
					<div class="item name">
						<p class="lg bold"><?php echo esc_html( $bar_name !== '' ? $bar_name : __( 'Name of TopaBar', 'top-bar' ) ); ?>
					</div>

					<div class="item nav">
						<button type="button" class="top-bar-icons status-on"><?php esc_html_e( 'Visible On/Off', 'top-bar' ); ?></button>		
						<button type="button" class="top-bar-icons delete"><?php esc_html_e( 'Delete', 'top-bar' ); ?></button>				
						<button type="button" class="top-bar-icons arrow-down"><?php esc_html_e( 'Open/Close', 'top-bar' ); ?></button>		
					</div>
				</div>
				<div id="top-bar-options" class="active">			<!-- The active class opens the options  -->
					<div class="top-bar-grid">
						<div class="item">			
							<fieldset class="clear">
								<legend class="bold lg"><?php esc_html_e( 'Name', 'top-bar' ); ?></legend>
								<input type="text" id="top-bar-name" name="top_bar_name" value="<?php echo esc_attr( $bar_name ); ?>" placeholder="Name of Top Bar">
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
								<select id="top_bar_position" name="top_bar_position" aria-label="<?php esc_attr_e( 'Position', 'top-bar' ); ?>">
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
								<input type="color" id="top_bar_bg_color" name="top_bar_bg_color" value="<?php echo esc_attr( $bg_color ?: '#1d2327' ); ?>" />
							</fieldset>
						</div>
						<div class="item column">
							<div class="row">
								<label class="clear">				
									<input type="checkbox" id="top_bar_frame_disable" name="top_bar_frame_disable" value="1" <?php checked( empty( $frame_color ) ); ?> />
									<p class="bold clear"><?php esc_html_e( 'Border frame', 'top-bar' ); ?></p>
								</label>
							</div>
							<div class="row">
								<label class="clear">				
									<input type="color" id="top_bar_frame_color" name="top_bar_frame_color" value="<?php echo esc_attr( $frame_color ?: '#000000' ); ?>" />
									<select>
										<?php
											for ($i = 0; $i <= 10; $i += 1) {
													echo '<option value="' . $i . '">' . $i . 'px</option>';
											}
										?>
									</select>
								</label>
							</div>
						</div>
						<div class="item column">
							<div class="row">
								<label>		
									<input type="hidden" name="top_bar_hide_on_scroll" value="0" />
									<input type="checkbox" name="top_bar_hide_on_scroll" value="1" <?php checked( $hide_on_scroll ); ?> />
									<p class="bold clear"><?php esc_html_e('Scroll behaviour', 'top-bar' ); ?></p>
								</label>
							</div>
							<div class="row">
								<p class="xs"><?php esc_html_e( 'Hide/Show bar when user scrolls page', 'top-bar' ); ?></p>
							</div>
						</div>
						<div class="item">
							<fieldset class="clear">
								<legend class="bold"><?php esc_html_e( 'Status', 'top-bar' ); ?></legend>
								<select name="top_bar_status" aria-label="<?php esc_attr_e( 'Status', 'top-bar' ); ?>">
									<option value="on" <?php selected( $status, 'on' ); ?>><?php esc_html_e( 'On', 'top-bar' ); ?></option>
									<option value="off" <?php selected( $status, 'off' ); ?>><?php esc_html_e( 'Off', 'top-bar' ); ?></option>
								</select>							
							</fieldset>
						</div>
					</div>

					<div class="top-bar-grid title">
						<div class="item">
							<label class="check">
								<input type="checkbox" name="top_bar_hide_on_scroll__ui_mock" value="1">
								<span>
									<p class="bold lg"><?php esc_html_e( 'Life time', 'top-bar' ); ?></p>
								</span>
							</label>
						</div>
					</div>
					
					<div class="top-bar-grid bg bg-amber">
						<div class="item">
							<fieldset class="clear">
								<legend class="bold"><?php esc_html_e( 'Show', 'top-bar' ); ?></legend>
								<label>
								<input type="text" id="datepicker1" size="30" class="datepicker">	
				
							</label>
									<p class="xs">Krotkie wyjasnienie</p>
							</fieldset>
						</div>
						<div class="item">
							<fieldset class="clear">
								<legend class="bold"><?php esc_html_e( 'Hide', 'top-bar' ); ?></legend>
								<label>
								<input type="text" id="datepicker2" size="30" class="datepicker">	
								</label>
								<p class="xs">Krotkie wyjasnienie</p>
						</fieldset>
						</div>
						<div class="item">
							<fieldset class="clear">
								<legend class="bold">Repeat</legend>
								<label>
									<input type="text" id="datepicker3" size="30" class="datepicker">	
								</label>
								<p class="xs">Krotkie wyjasnienie</p>
							</fieldset>
						</div>
					</div>

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
											<select>
												<option value="Roboto">None</option>
												<option value="Roboto">Slider</option>
												<option value="Open Sans">Fade In</option>
												<option value="Lato">Blink</option>
											</select>
									</label>
								</fieldset>
								<fieldset class="line">
									<legend class="bold"><?php esc_html_e( 'Add multi fields', 'top-bar' ); ?></legend>
								
									<div class="top-bar-column-creator-grid">
										<div class="item-creator no">								
											<p class="bold md">1</p>								
										</div>
										<div class="item-creator">						
											<?php

												wp_editor( $message, 'top_bar_message1', [
															'textarea_name' => 'top_bar_message',
															'textarea_rows' => 2,
															'media_buttons' => false,
															'teeny'         => true,
															'quicktags'     => false,
															'tinymce'       => [
																'resize' => false,
																'plugins'  => 'textcolor',
																'toolbar1' => 'formatselect,bold,italic,forecolor,backcolor,link,unlink,bullist,numlist,blockquote,undo,redo',
															],
															'editor_css'    => '',
															'dfw'           => false,
														] );
											?>									
										</div>
									</div>
									<div class="top-bar-column-creator-grid">
										<div class="item-creator no">								
											<p class="bold md">2</p>								
										</div>
										<div class="item-creator">						
											<?php

												wp_editor( $message, 'top_bar_message12', [
															'textarea_name' => 'top_bar_message12',
															'textarea_rows' => 2,
															'media_buttons' => false,
															'teeny'         => true,
															'quicktags'     => false,
															'tinymce'       => [
																'resize' => false,
																'plugins'  => 'textcolor',
																'toolbar1' => 'formatselect,bold,italic,forecolor,backcolor,link,unlink,bullist,numlist,blockquote,undo,redo',
															],
															'editor_css'    => '',
															'dfw'           => false,
														] );
											?>										
										</div>
									</div>
								</fieldset>								
								<div class="top-bar-row rt">
									<a href="#" class="top-bar-btn amber sm right"><?php esc_html_e( 'Add new text', 'top-bar' ); ?></a>	
								</div>
							</div>

							<div class="item item-creator">
								<fieldset>
									<legend class="bold"><?php esc_html_e( 'Size column', 'top-bar' ); ?></legend>
									<label>
										<select name="size">
											<?php
												for ($i = 0; $i <= 100; $i += 5) {
														echo '<option value="' . $i . '">' . $i . '%</option>';
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
												for ($i = 0; $i <= 100; $i += 5) {
														echo '<option value="' . $i . '">' . $i . '%</option>';
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
												for ($i = 0; $i <= 100; $i += 5) {
														echo '<option value="' . $i . '">' . $i . '%</option>';
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

		</div>

		<div class="wrap">
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="top_bar_message"><?php esc_html_e( 'Message', 'top-bar' ); ?></label></th>
						<td>
							<?php
							wp_editor( $message, 'top_bar_message', [
								'textarea_name' => 'top_bar_message__ui_mock_legacy',
								'textarea_rows' => 3,
								'media_buttons' => false,
								'teeny'         => true,
								'quicktags'     => true,
								'tinymce'       => [
									'plugins'  => 'textcolor',
									'toolbar1' => 'formatselect,bold,italic,forecolor,backcolor,link,unlink,bullist,numlist,blockquote,undo,redo',
								],
								'editor_css'    => '',
								'dfw'           => false,
							] );
							?>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="top_bar_bg_color"><?php esc_html_e( 'Background colour', 'top-bar' ); ?></label></th>
						<td><input type="color" id="top_bar_bg_color_legacy" name="top_bar_bg_color__ui_mock_legacy" value="<?php echo esc_attr( $bg_color ?: '#1d2327' ); ?>" /></td>
					</tr>
					<tr>
						<th scope="row"><label for="top_bar_frame_color"><?php esc_html_e( 'Frame (border) colour', 'top-bar' ); ?></label></th>
						<td>
							<input type="color" id="top_bar_frame_color_legacy" name="top_bar_frame_color__ui_mock_legacy" value="<?php echo esc_attr( $frame_color ?: '#000000' ); ?>" />
							<input type="checkbox" id="top_bar_frame_disable_legacy" name="top_bar_frame_disable__ui_mock_legacy" value="1" <?php checked( empty( $frame_color ) ); ?> />
							<label for="top_bar_frame_disable_legacy"><?php esc_html_e( 'No border', 'top-bar' ); ?></label>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Scroll behaviour', 'top-bar' ); ?></th>
						<td>
							<input type="hidden" name="top_bar_hide_on_scroll__ui_mock_legacy" value="0" />
							<label>
								<input type="checkbox" name="top_bar_hide_on_scroll__ui_mock_legacy" value="1" <?php checked( $hide_on_scroll ); ?> />
								<?php esc_html_e( 'Hide bar when user scrolls down; show again when scrolling up', 'top-bar' ); ?>
							</label>
						</td>
					</tr>
				</table>
				<?php submit_button(); ?>
		</div>
		</form>
		<?php
	}
}
