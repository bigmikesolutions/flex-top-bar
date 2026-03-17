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
		add_action( 'admin_init', [ $this, 'register_settings' ] );
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
		?>

		<div id="top-bar">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			
			<!-- Empty list  -->

			<div class="top-bar-row center empty">
				<p class="lg bold">Welcome to the best TopBar plugin ever</p>
				<a href="#" class="top-bar-btn mint md">Add new button</a>	
			</div>

			<!-- List of buttons  -->

			<div class="top-bar-row rt">
				<a href="#" class="top-bar-btn mint sm">Add new button</a>	
			</div>


			<!-- Pomysł: 
			 	Przenoszenie dodanych kolumn za pomca drag&drop			
			-->

			<div class="top-bar-row bg">		
				
				<!-- Navigation -->
				<div id="top-bar-nav">
					<div class="item">
						<p class="lg bold">Name of TopBar
					</div>
					<div class=""></div>
					<div class="top-bar-icons arrow-down"></div>				
				</div>
				<div id="top-bar-options" class="active">			<!-- The active class opens the options  -->
					<div class="top-bar-grid">
						<div class="item">			
							<fieldset class="clear">
								<legend class="bold lg">Name</legend>
								<input type="text" id="top-bar-name" placeholder="Name of TopBar">
							</fieldset>
						</div>
					</div>
					<div class="top-bar-grid title">
						<div class="item">
							<p class="bold lg">Basic settings</p>
						</div>
					</div>
					<div class="top-bar-grid bg bg-blue">
						<div class="item">
							<fieldset class="clear">
								<legend class="bold">Position</legend>
								<label><input type="radio" name="top_bar_position" value="top" <?php checked( $position, 'top' ); ?> /> <?php esc_html_e( 'Top', 'top-bar' ); ?></label>
								<label><input type="radio" name="top_bar_position" value="bottom" <?php checked( $position, 'bottom' ); ?> /> <?php esc_html_e( 'Bottom', 'top-bar' ); ?></label>
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
							<fieldset  class="clear">
								<legend class="bold">Global Background</legend>
								<input type="color" id="top_bar_bg_color" name="top_bar_bg_color" value="<?php echo esc_attr( $bg_color ?: '#1d2327' ); ?>" />
							</fieldset>
						</div>
						<div class="item">
						<label>								
							
							<input type="checkbox" id="top_bar_frame_disable" name="top_bar_frame_disable" value="1" <?php checked( empty( $frame_color ) ); ?> />
							<p class="bold">Border frame</p>

							<input type="color" id="top_bar_frame_color" name="top_bar_frame_color" value="<?php echo esc_attr( $frame_color ?: '#000000' ); ?>" />
								<p class="bold">Size</p>
								<select>
									<option value="Roboto">1</option>
									<option value="Open Sans">2</option>
									<option value="Lato">3</option>
									<option value="Montserrat">4</option>
									<option value="Oswald">5</option>
									<option value="Raleway">6</option>
									<option value="Poppins">7</option>
									<option value="Playfair Display">8</option>
									<option value="Nunito">9</option>
									<option value="Ubuntu">10</option>
								</select>
							</label>
						</div>
						<div class="item">
							<p class="bold">Scroll behaviour</p>
						</div>
						<div class="item">
							<p class="bold">Status</p>
							<fieldset class="clear">
								<label><input type="radio" name="top_bar_position" value="top" <?php checked( $position, 'top' ); ?> /> <?php esc_html_e( 'On', 'top-bar' ); ?></label><br />
								<label><input type="radio" name="top_bar_position" value="bottom" <?php checked( $position, 'bottom' ); ?> /> <?php esc_html_e( 'Off', 'top-bar' ); ?></label>
							</fieldset>
						</div>
					</div>

					<div class="top-bar-grid title">
						<div class="item">
							<label>
								<input type="checkbox" name="top_bar_hide_on_scroll" value="1">
								<p class="bold lg">Top bar lifetime (po zaznaczeniu radio  pokazuje sie ponizsze opcje) </p>
							</label>
						</div>
					</div>
					
					<div class="top-bar-grid bg bg-amber">
						<div class="item">
							<fieldset class="clear">
								<legend class="bold">Show</legend>
								<label><input type="datetime-local" id="meeting-time" name="meeting-time" value="2018-06-12T19:30" min="2018-06-07T00:00" max="2018-06-14T00:00" /></label>
									<p class="xs">Krotkie wyjasnienie</p>
							</fieldset>
						</div>
						<div class="item">
							<fieldset class="clear">
								<legend class="bold">Hide</legend>
								<label><input type="datetime-local" id="meeting-time" name="meeting-time" value="2018-06-12T19:30" min="2018-06-07T00:00" max="2018-06-14T00:00" /></label>
								<p class="xs">Krotkie wyjasnienie</p>
						</fieldset>
						</div>
						<div class="item">
							<fieldset class="clear">
								<legend class="bold">Repeat</legend>
								<label><input type="datetime-local" id="meeting-time" name="meeting-time" value="2018-06-12T19:30" min="2018-06-07T00:00" max="2018-06-14T00:00" /></label>
								<p class="xs">Krotkie wyjasnienie</p>
							</fieldset>
						</div>
					</div>

					<div class="top-bar-grid title">
						<div class="item">
							<p class="bold lg">Create a design</p>
							<p class="xs">Create your own top bar. You can add a maximum of 4 columns, choosing different types of content.</p>
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
									<legend class="bold">Type</legend>
									<label><input type="radio" name="top_bar_position" value="top" <?php checked( $position, 'top' ); ?> /> <?php esc_html_e( 'Text Editor', 'top-bar' ); ?></label>
									<label><input type="radio" name="top_bar_position" value="top" <?php checked( $position, 'top' ); ?> /> <?php esc_html_e( 'Social media', 'top-bar' ); ?></label>
									<label><input type="radio" name="top_bar_position" value="top" <?php checked( $position, 'top' ); ?> /> <?php esc_html_e( 'Contact data', 'top-bar' ); ?></label>
								</fieldset>
							</div>
						
							<div class="item-creator lg">
								<fieldset class="line">
									<legend class="bold">Effect</legend>
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
									<legend class="bold">Add multi fields</legend>
								
									<div class="top-bar-column-creator-grid">
										<div class="item-creator no">								
											<p class="bold md">1</p>								
										</div>
										<div class="item-creator">						
											<?php

												wp_editor( $message, 'top_bar_message1', [
															'textarea_name' => 'top_bar_message1',
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
											<!-- <div class="top-bar-column-options">
												<label>
													<p class="xs">Background</p>
													<input type="color" id="top_bar_bg_color" name="top_bar_bg_color" value="<?php echo esc_attr( $bg_color ?: '#1d2327' ); ?>" />
												</label>
											</div> -->
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
											<!-- <div class="top-bar-column-options">
												<label>
													<p class="xs">Background</p>
													<input type="color" id="top_bar_bg_color" name="top_bar_bg_color" value="<?php echo esc_attr( $bg_color ?: '#1d2327' ); ?>" />
												</label>
											</div> -->
										</div>
									</div>

								</fieldset>
								
								<div class="top-bar-row rt">
									<a href="#" class="top-bar-btn amber sm right">Add new text</a>	
								</div>
							</div>

							<div class="item item-creator">
								<fieldset>
									<legend class="bold">Size column</legend>
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
									<legend class="bold">Visible on the mobile</legend>
										<label><input type="radio" name="top_bar_position" value="top" <?php checked( $position, 'top' ); ?> /> <?php esc_html_e( 'Yes', 'top-bar' ); ?></label>
									<label><input type="radio" name="top_bar_position" value="top" <?php checked( $position, 'top' ); ?> /> <?php esc_html_e( 'No', 'top-bar' ); ?></label>
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
									<legend class="bold">Type</legend>
									<label><input type="radio" name="top_bar_position" value="top" <?php checked( $position, 'top' ); ?> /> <?php esc_html_e( 'Text Editor', 'top-bar' ); ?></label><br />
									<label><input type="radio" name="top_bar_position" value="top" <?php checked( $position, 'top' ); ?> /> <?php esc_html_e( 'Social media', 'top-bar' ); ?></label><br />
									<label><input type="radio" name="top_bar_position" value="top" <?php checked( $position, 'top' ); ?> /> <?php esc_html_e( 'Contact data', 'top-bar' ); ?></label><br />
								</fieldset>
							</div>							
							<div class="item-creator lg">
								<fieldset class="line">
									<legend class="bold">Design</legend>
									<label><input type="radio" name="top_bar_position" value="top" <?php checked( $position, 'top' ); ?> /> Zaokraglony (tutaj podgląd ikon)</label><br />
									<label><input type="radio" name="top_bar_position" value="top" <?php checked( $position, 'top' ); ?> /> Kwadratowy</label><br />
									<label><input type="radio" name="top_bar_position" value="top" <?php checked( $position, 'top' ); ?> /> Sama ikona</label><br />
								</fieldset>
								<div class="top-bar-grid">
									<div class="item">
										<fieldset class="line">
											<legend class="bold">Background color</legend>
											<label><input type="color" id="top_bar_frame_color" name="top_bar_frame_color" value="<?php echo esc_attr( $frame_color ?: '#000000' ); ?>" /></label>
										</fieldset>
									</div>
									<div class="item">
										<fieldset class="line">
											<legend class="bold">Color icon</legend>
											<label><input type="color" id="top_bar_frame_color" name="top_bar_frame_color" value="<?php echo esc_attr( $frame_color ?: '#000000' ); ?>" /></label>
										</fieldset>
									</div>
								</div>
								<fieldset class="line">
									<legend class="bold">Add contact</legend>
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
												<input type="text" name="top_bar_position" value="Your proflie link" <?php checked( $position, 'top' ); ?> />
				
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
											<input type="text" name="top_bar_position" value="top" <?php checked( $position, 'top' ); ?> />
										</fieldset>
									</div>
								</div>
								<div class="top-bar-row rt">
									<a href="#" class="top-bar-btn amber sm right">Add new social media</a>	
								</div>
							</div>

							<div class="item-creator">
								<fieldset>
									<legend class="bold">Size column</legend>
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
									<legend class="bold">Visible on the mobile</legend>
										<label><input type="radio" name="top_bar_position" value="top" <?php checked( $position, 'top' ); ?> /> <?php esc_html_e( 'Yes', 'top-bar' ); ?></label>
									<label><input type="radio" name="top_bar_position" value="top" <?php checked( $position, 'top' ); ?> /> <?php esc_html_e( 'No', 'top-bar' ); ?></label>
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
									<legend class="bold">Type</legend>
									<label><input type="radio" name="top_bar_position" value="top" <?php checked( $position, 'top' ); ?> /> <?php esc_html_e( 'Text Editor', 'top-bar' ); ?></label><br />
									<label><input type="radio" name="top_bar_position" value="top" <?php checked( $position, 'top' ); ?> /> <?php esc_html_e( 'Social media', 'top-bar' ); ?></label><br />
									<label><input type="radio" name="top_bar_position" value="top" <?php checked( $position, 'top' ); ?> /> <?php esc_html_e( 'Contact data', 'top-bar' ); ?></label><br />
								</fieldset>
							</div>							
							<div class="item-creator lg">
								<fieldset class="line">
									<legend class="bold">Design</legend>
									<label><input type="radio" name="top_bar_position" value="top" <?php checked( $position, 'top' ); ?> /> Zaokraglony (tutaj podgląd ikon)</label><br />
									<label><input type="radio" name="top_bar_position" value="top" <?php checked( $position, 'top' ); ?> /> Kwadratowy</label><br />
									<label><input type="radio" name="top_bar_position" value="top" <?php checked( $position, 'top' ); ?> /> Sama ikona</label><br />
								</fieldset>
								<div class="top-bar-grid">
									<div class="item">
										<fieldset class="line">
											<legend class="bold">Background color</legend>
											<label><input type="color" id="top_bar_frame_color" name="top_bar_frame_color" value="<?php echo esc_attr( $frame_color ?: '#000000' ); ?>" /></label>
										</fieldset>
									</div>
									<div class="item">
										<fieldset class="line">
											<legend class="bold">Color icon</legend>
											<label><input type="color" id="top_bar_frame_color" name="top_bar_frame_color" value="<?php echo esc_attr( $frame_color ?: '#000000' ); ?>" /></label>
										</fieldset>
									</div>
								</div>
								<fieldset class="line">
									<legend class="bold">Add your contacts icons </legend>
									<div class="top-bar-column-creator-grid">
										<div class="item-creator no">								
											<p class="bold md">1</p>								
										</div>
										<div class="item-creator vertical">	

											<select name="contact_type">
												<option value="">Select contact type</option>
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
												<input type="text" name="top_bar_position" value="Your proflie link" <?php checked( $position, 'top' ); ?> />
				
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
												<option value="">Select contact type</option>
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
												<input type="text" name="top_bar_p
											<input type="text" name="top_bar_position" value="top" <?php checked( $position, 'top' ); ?> />
										</fieldset>
									</div>
								</div>
								<div class="top-bar-row rt">
									<a href="#" class="top-bar-btn amber sm right">Add new contacts</a>	
								</div>
							</div>

							<div class="item-creator">
								<fieldset>
									<legend class="bold">Size column</legend>
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
									<legend class="bold">Visible on the mobile</legend>
										<label><input type="radio" name="top_bar_position" value="top" <?php checked( $position, 'top' ); ?> /> <?php esc_html_e( 'Yes', 'top-bar' ); ?></label>
									<label><input type="radio" name="top_bar_position" value="top" <?php checked( $position, 'top' ); ?> /> <?php esc_html_e( 'No', 'top-bar' ); ?></label>
								</fieldset>
							</div>
						</div>	

					</div>					
				</div>

				

				<div class="top-bar-row rt">
					<a href="#" class="top-bar-btn mint sm">Add new column</a>	
				</div>

			<!-- End options -->
			</div>
				

		</div>
	
		<!-- // old frontend -->
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			<form action="options.php" method="post">
				<?php settings_fields( 'top_bar_settings' ); ?>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><?php esc_html_e( 'Position', 'top-bar' ); ?></th>
						<td>
							<fieldset>
								<label><input type="radio" name="top_bar_position" value="top" <?php checked( $position, 'top' ); ?> /> <?php esc_html_e( 'Top', 'top-bar' ); ?></label><br />
								<label><input type="radio" name="top_bar_position" value="bottom" <?php checked( $position, 'bottom' ); ?> /> <?php esc_html_e( 'Bottom', 'top-bar' ); ?></label>
							</fieldset>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="top_bar_message"><?php esc_html_e( 'Message', 'top-bar' ); ?></label></th>
						<td>
							<?php
							wp_editor( $message, 'top_bar_message', [
								'textarea_name' => 'top_bar_message',
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
						<td><input type="color" id="top_bar_bg_color" name="top_bar_bg_color" value="<?php echo esc_attr( $bg_color ?: '#1d2327' ); ?>" /></td>
					</tr>
					<tr>
						<th scope="row"><label for="top_bar_frame_color"><?php esc_html_e( 'Frame (border) colour', 'top-bar' ); ?></label></th>
						<td>
							<input type="color" id="top_bar_frame_color" name="top_bar_frame_color" value="<?php echo esc_attr( $frame_color ?: '#000000' ); ?>" />
							<input type="checkbox" id="top_bar_frame_disable" name="top_bar_frame_disable" value="1" <?php checked( empty( $frame_color ) ); ?> />
							<label for="top_bar_frame_disable"><?php esc_html_e( 'No border', 'top-bar' ); ?></label>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Scroll behaviour', 'top-bar' ); ?></th>
						<td>
							<input type="hidden" name="top_bar_hide_on_scroll" value="0" />
							<label>
								<input type="checkbox" name="top_bar_hide_on_scroll" value="1" <?php checked( $hide_on_scroll ); ?> />
								<?php esc_html_e( 'Hide bar when user scrolls down; show again when scrolling up', 'top-bar' ); ?>
							</label>
						</td>
					</tr>
				</table>
				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}
}
