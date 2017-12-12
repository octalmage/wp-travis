<?php
/**
 * Plugin Name: WP Travis
 * Version: 0.0.1
 * Author: Jason Stallings
 * Author URI: https://jason.stallin.gs
 * Requires at least: 4.0.0
 * Tested up to: 4.9.1
 * GitHub Plugin URI: octalmage/wp-travis
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

function WP_Travis() {  // @codingStandardsIgnoreLine
	return WP_Travis::instance();
}

add_action( 'plugins_loaded', 'WP_Travis' );

final class WP_Travis {
	/**
	 * WP_Travis The single instance of WP_Travis.
	 * @var     object
	 * @access  private
	 * @since   1.0.0
	 */
	private static $_instance = null;

	/**
	 * Main WP_Travis Instance
	 *
	 * Ensures only one instance of WP_Travis is loaded or can be loaded.
	 *
	 * @since 1.0.0
	 * @static
	 * @return Main WP_Travis instance
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	} // End instance()

	/**
	 * Constructor function.
	 * @access  public
	 * @since   1.0.0
	 */
	public function __construct() {
		if ( is_admin() ) {
			// Register the settings with WordPress.
			add_action( 'admin_init', array( $this, 'register_settings' ) );
			// Register the settings screen within WordPress.
			add_action( 'admin_menu', array( $this, 'register_settings_screen' ) );
		}

		// Hook post transition.
		add_action( 'transition_post_status', array( $this, 'post_published' ), 10, 3 );
	} // End __construct()

	/**
	 * Register the admin screen.
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	public function register_settings_screen() {
		$this->_hook = add_submenu_page( 'options-general.php', 'WP Travis', 'WP Travis', 'manage_options', 'wp-travis', array( $this, 'settings_screen' ) );
	} // End register_settings_screen()

	/**
	 * Register the admin settings.
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	public function register_settings() {
		register_setting( 'wptplugin', 'wptravis_settings' );

		add_settings_section(
			'wptplugin_section',
			'Travis CI',
			function() {
				/* Section description goes here. */
			},
			'wptplugin'
		);

		add_settings_field(
			'wptravis_repo',
			'Repository (username/repository)',
			function() {
				$options = get_option( 'wptravis_settings' );
				?>
				<input type='text' name='wptravis_settings[wptravis_repo]' value='<?php echo $options['wptravis_repo']; ?>'>
				<?php
			},
			'wptplugin',
			'wptplugin_section'
		);

		add_settings_field(
			'wptravis_api_key',
			'API Key',
			function() {
				$options = get_option( 'wptravis_settings' );
				?>
				<input type='text' name='wptravis_settings[wptravis_api_key]' value='<?php echo $options['wptravis_api_key']; ?>'>
				<?php
			},
			'wptplugin',
			'wptplugin_section'
		);
	} // End register_settings_screen()

	/**
	 * Output the markup for the settings screen.
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	public function settings_screen() {
			?>
			<form action='options.php' method='post'>
				<h1>WP Travis</h1>

				<?php
				settings_fields( 'wptplugin' );
				do_settings_sections( 'wptplugin' );
				submit_button();
				?>

			</form>
			<?php
	} // End settings_screen()

	public function post_published( $new_status, $old_status, $post ) {
		if ( $new_status == 'publish'  &&  $old_status != 'publish' ) {
			$this->trigger_build();
		}
	}

	private function trigger_build() {
		$options = get_option( 'wptravis_settings' );
		$repo = urlencode( $options['wptravis_repo'] );
		$api_url = "https://api.travis-ci.org/repo/$repo/requests";
		$token = $options['wptravis_api_key'];
		$response = wp_remote_post( $api_url, array(
			'method' => 'POST',
			'sslverify' => false,
			'headers' => array(
				'Content-Type' => 'application/json',
				'Travis-API-Version' => '3',
				'Accept' => 'application/json',
				'Authorization' => "token $token"
			),
			'body' => json_encode( array( 'request' => array( 'branch' => 'master' ) ) )
		) );
	}
} // End Class
