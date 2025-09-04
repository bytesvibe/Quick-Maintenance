<?php
/**
 * Plugin Name:       Quick Maintenance
 * Plugin URI:        https://bytesvibe.com/quick-maintenance
 * Description:       A simple plugin to enable maintenance mode for your WordPress site with custom styling, logo support, and color presets.
 * Version:           1.0.0
 * Author:            Riduan Chowdhury
 * Author URI:        https://bytesvibe.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       quick-maintenance
 * Domain Path:       /languages
 * Requires at least: 5.0
 * Tested up to: 6.8
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Define Constants
 */
define( 'QUICK_MAINTENANCE_VERSION', '1.0.0' );
define( 'QUICK_MAINTENANCE_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'QUICK_MAINTENANCE_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
class Quick_Maintenance {

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {

		$this->plugin_name = 'quick-maintenance';
		$this->version = QUICK_MAINTENANCE_VERSION;

		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
		add_action( 'template_redirect', array( $this, 'maintenance_mode_frontend' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_scripts' ) );

	}

	/**
	 * Get available color presets
	 */
	private function get_color_presets() {
		return array(
			'purple-blue' => array(
				'name' => __( 'Purple to Blue (Default)', 'quick-maintenance' ),
				'gradient' => 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)'
			),
			'orange-red' => array(
				'name' => __( 'Orange to Red', 'quick-maintenance' ),
				'gradient' => 'linear-gradient(135deg, #ff9a9e 0%, #fecfef 50%, #fecfef 100%)'
			),
			'green-blue' => array(
				'name' => __( 'Green to Blue', 'quick-maintenance' ),
				'gradient' => 'linear-gradient(135deg, #a8edea 0%, #fed6e3 100%)'
			),
			'pink-orange' => array(
				'name' => __( 'Pink to Orange', 'quick-maintenance' ),
				'gradient' => 'linear-gradient(135deg, #ff9a9e 0%, #fad0c4 100%)'
			),
			'blue-purple' => array(
				'name' => __( 'Blue to Purple', 'quick-maintenance' ),
				'gradient' => 'linear-gradient(135deg, #a18cd1 0%, #fbc2eb 100%)'
			),
			'dark-blue' => array(
				'name' => __( 'Dark Blue', 'quick-maintenance' ),
				'gradient' => 'linear-gradient(135deg, #2c3e50 0%, #3498db 100%)'
			),
			'sunset' => array(
				'name' => __( 'Sunset', 'quick-maintenance' ),
				'gradient' => 'linear-gradient(135deg, #ff7e5f 0%, #feb47b 100%)'
			),
			'ocean' => array(
				'name' => __( 'Ocean', 'quick-maintenance' ),
				'gradient' => 'linear-gradient(135deg, #00c6ff 0%, #0072ff 100%)'
			),
			'forest' => array(
				'name' => __( 'Forest', 'quick-maintenance' ),
				'gradient' => 'linear-gradient(135deg, #56ab2f 0%, #a8e6cf 100%)'
			),
			'royal' => array(
				'name' => __( 'Royal', 'quick-maintenance' ),
				'gradient' => 'linear-gradient(135deg, #8360c3 0%, #2ebf91 100%)'
			)
		);
	}

	/**
	 * Enqueue admin scripts
	 */
	public function admin_scripts( $hook ) {
		if ( 'settings_page_quick-maintenance' !== $hook ) {
			return;
		}
		wp_enqueue_media();
		wp_enqueue_script( 'quick-maintenance-admin', QUICK_MAINTENANCE_PLUGIN_URL . 'admin/js/quick-maintenance-admin.js', array( 'jquery' ), $this->version, true );
	}

	/**
	 * Register plugin settings.
	 */
	public function register_settings() {
		register_setting( 'quick_maintenance_options', 'quick_maintenance_enabled', array( 'type' => 'boolean', 'sanitize_callback' => array( $this, 'sanitize_boolean' ), 'default' => false ) );
		register_setting( 'quick_maintenance_options', 'quick_maintenance_heading', array( 'type' => 'string', 'sanitize_callback' => 'sanitize_text_field', 'default' => __( 'Website Under Maintenance', 'quick-maintenance' ) ) );
		register_setting( 'quick_maintenance_options', 'quick_maintenance_message', array( 'type' => 'string', 'sanitize_callback' => 'wp_kses_post', 'default' => __( 'Our website is currently undergoing scheduled maintenance. Please check back soon.', 'quick-maintenance' ) ) );
		register_setting( 'quick_maintenance_options', 'quick_maintenance_logo', array( 'type' => 'string', 'sanitize_callback' => 'esc_url_raw', 'default' => '' ) );
		register_setting( 'quick_maintenance_options', 'quick_maintenance_color_preset', array( 'type' => 'string', 'sanitize_callback' => 'sanitize_text_field', 'default' => 'purple-blue' ) );
		register_setting( 'quick_maintenance_options', 'quick_maintenance_whitelisted_ips', array( 'type' => 'string', 'sanitize_callback' => array( $this, 'sanitize_whitelisted_ips' ), 'default' => '' ) );

		add_settings_section(
			'quick_maintenance_main_section',
			__( 'Maintenance Mode Settings', 'quick-maintenance' ),
			array( $this, 'section_callback' ),
			'quick-maintenance'
		);

		add_settings_field(
			'quick_maintenance_enabled_field',
			__( 'Enable Maintenance Mode', 'quick-maintenance' ),
			array( $this, 'enabled_callback' ),
			'quick-maintenance',
			'quick_maintenance_main_section'
		);

		add_settings_field(
			'quick_maintenance_heading_field',
			__( 'Maintenance Heading', 'quick-maintenance' ),
			array( $this, 'heading_callback' ),
			'quick-maintenance',
			'quick_maintenance_main_section'
		);

		add_settings_field(
			'quick_maintenance_message_field',
			__( 'Maintenance Message', 'quick-maintenance' ),
			array( $this, 'message_callback' ),
			'quick-maintenance',
			'quick_maintenance_main_section'
		);

		add_settings_field(
			'quick_maintenance_logo_field',
			__( 'Logo/Image', 'quick-maintenance' ),
			array( $this, 'logo_callback' ),
			'quick-maintenance',
			'quick_maintenance_main_section'
		);

		add_settings_field(
			'quick_maintenance_color_preset_field',
			__( 'Color Preset', 'quick-maintenance' ),
			array( $this, 'color_preset_callback' ),
			'quick-maintenance',
			'quick_maintenance_main_section'
		);

		add_settings_field(
			'quick_maintenance_whitelisted_ips_field',
			__( 'Whitelisted IP Addresses', 'quick-maintenance' ),
			array( $this, 'whitelisted_ips_callback' ),
			'quick-maintenance',
			'quick_maintenance_main_section'
		);
	}

	/**
	 * Section callback
	 */
	public function section_callback() {
		echo '<p>' . esc_html__( 'Configure your maintenance mode settings below.', 'quick-maintenance' ) . '</p>';
	}

	/**
	 * Sanitize boolean values
	 */
	public function sanitize_boolean( $input ) {
		return (bool) $input;
	}

	/**
	 * Sanitize whitelisted IPs.
	 */
	public function sanitize_whitelisted_ips( $input ) {
		if ( empty( $input ) ) {
			return '';
		}
		$ips = array_map( 'trim', explode( ',', $input ) );
		$sanitized_ips = array();
		foreach ( $ips as $ip ) {
			if ( filter_var( $ip, FILTER_VALIDATE_IP ) ) {
				$sanitized_ips[] = sanitize_text_field( wp_unslash( $ip ) );
			}
		}
		return implode( ',', $sanitized_ips );
	}

	/**
	 * Callback for enabled setting field.
	 */
	public function enabled_callback() {
		$enabled = get_option( 'quick_maintenance_enabled', false );
		echo '<label><input type="checkbox" name="quick_maintenance_enabled" value="1" ' . checked( 1, $enabled, false ) . ' /> ' . esc_html__( 'Enable maintenance mode', 'quick-maintenance' ) . '</label>';
	}

	/**
	 * Callback for heading setting field.
	 */
	public function heading_callback() {
		$heading = get_option( 'quick_maintenance_heading', __( 'Website Under Maintenance', 'quick-maintenance' ) );
		echo '<input type="text" name="quick_maintenance_heading" value="' . esc_attr( $heading ) . '" class="large-text" />';
		echo '<p class="description">' . esc_html__( 'The main heading displayed on the maintenance page.', 'quick-maintenance' ) . '</p>';
	}

	/**
	 * Callback for message setting field.
	 */
	public function message_callback() {
		$message = get_option( 'quick_maintenance_message', __( 'Our website is currently undergoing scheduled maintenance. Please check back soon.', 'quick-maintenance' ) );
		echo '<textarea name="quick_maintenance_message" rows="5" cols="50" class="large-text">' . esc_textarea( $message ) . '</textarea>';
		echo '<p class="description">' . esc_html__( 'The message displayed to visitors. HTML tags are allowed.', 'quick-maintenance' ) . '</p>';
	}

	/**
	 * Callback for logo setting field.
	 */
	public function logo_callback() {
		$logo = get_option( 'quick_maintenance_logo', '' );
		echo '<input type="text" name="quick_maintenance_logo" value="' . esc_attr( $logo ) . '" class="large-text" id="maintenance_logo_url" />';
		echo '<input type="button" class="button" value="' . esc_attr__( 'Select Image', 'quick-maintenance' ) . '" id="maintenance_logo_button" />';
		echo '<p class="description">' . esc_html__( 'Upload or select a logo/image to display on the maintenance page.', 'quick-maintenance' ) . '</p>';
		
		if ( $logo ) {
			echo '<div style="margin-top: 10px;"><img src="' . esc_url( $logo ) . '" style="max-width: 200px; height: auto;" /></div>';
		}
	}

	/**
	 * Callback for color preset setting field.
	 */
	public function color_preset_callback() {
		$current_preset = get_option( 'quick_maintenance_color_preset', 'purple-blue' );
		$presets = $this->get_color_presets();
		
		echo '<div class="color-preset-container">';
		foreach ( $presets as $key => $preset ) {
			echo '<label class="color-preset-option" style="display: inline-block; margin-right: 15px; margin-bottom: 10px;">';
			echo '<input type="radio" name="quick_maintenance_color_preset" value="' . esc_attr( $key ) . '" ' . checked( 1, $current_preset, false ) . ' />';
			echo '<span class="color-preview" style="display: inline-block; width: 30px; height: 30px; margin-left: 5px; margin-right: 5px; border-radius: 50%; background: ' . esc_attr( $preset['gradient'] ) . '; vertical-align: middle;"></span>';
			echo '<span>' . esc_html( $preset['name'] ) . '</span>';
			echo '</label><br>';
		}
		echo '</div>';
		echo '<p class="description">' . esc_html__( 'Choose a color preset for the maintenance page background.', 'quick-maintenance' ) . '</p>';
	}

	/**
	 * Callback for whitelisted IPs setting field.
	 */
	public function whitelisted_ips_callback() {
		$whitelisted_ips = get_option( 'quick_maintenance_whitelisted_ips', '' );
		$user_ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : __( 'Unknown', 'quick-maintenance' );
		echo '<input type="text" name="quick_maintenance_whitelisted_ips" value="' . esc_attr( $whitelisted_ips ) . '" class="large-text" />';
		echo '<p class="description">' .
			/* translators: %s: User's current IP address */
			sprintf( esc_html__( 'Enter comma-separated IP addresses to whitelist. Your current IP: %s', 'quick-maintenance' ), '<strong>' . esc_html( $user_ip ) . '</strong>' ) . '</p>';
	}

	/**
	 * Add admin menu page.
	 */
	public function add_admin_menu() {
		add_options_page(
			__( 'Quick Maintenance Settings', 'quick-maintenance' ),
			__( 'Maintenance Mode', 'quick-maintenance' ),
			'manage_options',
			'quick-maintenance',
			array( $this, 'settings_page_content' )
		);
	}

	/**
	 * Settings page content.
	 */
	public function settings_page_content() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			<form action="options.php" method="post">
				<?php
				settings_fields( 'quick_maintenance_options' );
				do_settings_sections( 'quick-maintenance' );
				submit_button( __( 'Save Settings', 'quick-maintenance' ) );
				?>
			</form>
		</div>
		
		<!-- JavaScript for media uploader -->
		<script type="text/javascript">
		jQuery(document).ready(function($) {
			$('#maintenance_logo_button').click(function(e) {
				e.preventDefault();
				var image = wp.media({
					title: '<?php echo esc_js( __( 'Select Logo/Image', 'quick-maintenance' ) ); ?>',
					multiple: false
				}).open().on('select', function(e) {
					var uploaded_image = image.state().get('selection').first();
					var image_url = uploaded_image.toJSON().url;
					$('#maintenance_logo_url').val(image_url);
				});
			});
		});
		</script>
		<?php
	}

	/**
	 * Display maintenance mode page on frontend.
	 */
	public function maintenance_mode_frontend() {
		// Check if maintenance mode is enabled
		$enabled = get_option( 'quick_maintenance_enabled', false );
		if ( ! $enabled ) {
			return;
		}

		// Allow administrators to bypass
		if ( current_user_can( 'manage_options' ) ) {
			return;
		}

		// Check IP whitelist
		$whitelisted_ips_option = get_option( 'quick_maintenance_whitelisted_ips', '' );
		if ( ! empty( $whitelisted_ips_option ) ) {
			$whitelisted_ips = array_map( 'trim', explode( ',', $whitelisted_ips_option ) );
			$user_ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
			
			if ( in_array( $user_ip, $whitelisted_ips, true ) ) {
				return;
			}
		}

		// Display maintenance page
		$this->display_maintenance_page();
	}

	/**
	 * Display the maintenance page with custom styling
	 */
	private function display_maintenance_page() {
		$heading = get_option( 'quick_maintenance_heading', __( 'Website Under Maintenance', 'quick-maintenance' ) );
		$message = get_option( 'quick_maintenance_message', __( 'Our website is currently undergoing scheduled maintenance. Please check back soon.', 'quick-maintenance' ) );
		$logo = get_option( 'quick_maintenance_logo', '' );
		$color_preset = get_option( 'quick_maintenance_color_preset', 'purple-blue' );
		
		$presets = $this->get_color_presets();
		$background_gradient = isset( $presets[ $color_preset ] ) ? $presets[ $color_preset ]['gradient'] : $presets['purple-blue']['gradient'];

		// Set proper headers
		status_header( 503 );
		header( 'Retry-After: 3600' );

		?>
		<!DOCTYPE html>
		<html <?php language_attributes(); ?>>
		<head>
			<meta charset="<?php bloginfo( 'charset' ); ?>">
			<meta name="viewport" content="width=device-width, initial-scale=1">
			<meta name="robots" content="noindex, nofollow">
			<title><?php echo esc_html( $heading ); ?></title>
			<style>
				* {
					margin: 0;
					padding: 0;
					box-sizing: border-box;
				}
				
				body {
					font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
					background: <?php echo esc_attr( $background_gradient ); ?>;
					color: #fff;
					display: flex;
					justify-content: center;
					align-items: center;
					min-height: 100vh;
					text-align: center;
					overflow: hidden;
				}

				.maintenance-content {
					background: rgba(255, 255, 255, 0.1);
					padding: 40px;
					border-radius: 15px;
					box-shadow: 0 4px 30px rgba(0, 0, 0, 0.1);
					backdrop-filter: blur(5px);
					-webkit-backdrop-filter: blur(5px);
					border: 1px solid rgba(255, 255, 255, 0.3);
					max-width: 600px;
					width: 90%;
					animation: fadeIn 1s ease-in-out;
				}

				@keyframes fadeIn {
					from { opacity: 0; transform: translateY(-20px); }
					to { opacity: 1; transform: translateY(0); }
				}

				.logo img {
					max-width: 150px;
					margin-bottom: 20px;
				}

				h1 {
					font-size: 2.5em;
					margin-bottom: 20px;
				}

				p {
					font-size: 1.2em;
					line-height: 1.6;
				}
			</style>
		</head>
		<body>
			<div class="maintenance-content">
				<?php if ( ! empty( $logo ) ) : ?>
					<div class="logo">
						<img src="<?php echo esc_url( $logo ); ?>" alt="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?> Logo" />
					</div>
				<?php endif; ?>
				<h1><?php echo esc_html( $heading ); ?></h1>
				<p><?php echo wp_kses_post( $message ); ?></p>
			</div>
		</body>
		</html>
		<?php
		die();
	}
}

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks, initiation
 * of the plugin will not run until WordPress has finished loading and
 * is running the 'plugins_loaded' hook.
 *
 * @since    1.0.0
 */
function run_quick_maintenance() {

	$plugin = new Quick_Maintenance();

}
run_quick_maintenance();


