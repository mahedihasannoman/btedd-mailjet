<?php
/**
 * Plugin Name: EDD - Mailjet
 * Plugin URI:  https://www.braintum.com/
 * Description: A Mailjet extension plugin for Easy Digital Downloads plugin that allows you to sync your contacts from your WordPress website to Mailjet.
 * Version:     1.0.0
 * Author:      Md. Mahedi Hasan
 * Author URI:  http://braintum.com
 * Donate link: https://braitum.com/contact
 * License:     GPLv2+
 * Text Domain: bt-edd-mailjet
 * Domain Path: /i18n/languages/
 * Tested up to: 5.9
 */

/**
 * Copyright (c) 2019 Braintum (email : mahedi@braintum.com)
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License, version 2 or, at
 * your discretion, any later version, as published by the Free
 * Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */

// don't call the file directly
defined( 'ABSPATH' ) || exit();

/**
 * Main BT_EDD_Mailjet_Addon Class.
 *
 * @class BT_EDD_Mailjet_Addon
 */
final class BT_EDD_Mailjet_Addon {

	/**
	 * BT_EDD_Mailjet version.
	 *
	 * @var string
	 * @since 1.0.0
	 */
	public $version = '1.0.0';

	/**
	 * The settings instance variable
	 *
	 * @var BT_EDD_Settings
	 * @since 1.0.0
	 */
	public $settings;

	/**
	 * The BT_EDD_MailJet instance variable
	 *
	 * @var BT_EDD_MailJet
	 * @since 1.0.0
	 */
	public $api;

	/**
	 * The BT_EDD_Log_Handler instance variable
	 *
	 * @var BT_EDD_Log_Handler
	 * @since 1.0.0
	 */
	public $logger;

	/**
	 * This plugin's instance
	 *
	 * @var BT_EDD_Mailjet_Addon The one true BT_EDD_Mailjet_Addon
	 * @since 1.0.0
	 */
	private static $instance;

	/**
	 * Main BT_EDD_Mailjet_Addon Instance
	 *
	 * Insures that only one instance of BT_EDD_Mailjet_Addon exists in memory at any one
	 * time. Also prevents needing to define globals all over the place.
	 *
	 * @return BT_EDD_Mailjet_Addon The one true BT_EDD_Mailjet_Addon
	 * @since 1.0.0
	 * @static var array $instance
	 */
	public static function get_instance() {
		if ( ! isset( self::$instance ) && ! ( self::$instance instanceof BT_EDD_Mailjet_Addon ) ) {
			self::$instance = new BT_EDD_Mailjet_Addon();
		}
		return self::$instance;
	}

	/**
	 * Return plugin version.
	 *
	 * @return string
	 * @since 1.0.0
	 * @access public
	 **/
	public function get_version() {
		return $this->version;
	}

	/**
	 * Plugin URL getter.
	 *
	 * @return string
	 * @since 1.0.0
	 */
	public function plugin_url() {
		return untrailingslashit( plugins_url( '/', __FILE__ ) );
	}

	/**
	 * Plugin path getter.
	 *
	 * @return string
	 * @since 1.0.0
	 */
	public function plugin_path() {
		return untrailingslashit( plugin_dir_path( __FILE__ ) );
	}

	/**
	 * Plugin base path name getter.
	 *
	 * @return string
	 * @since 1.0.0
	 */
	public function plugin_basename() {
		return plugin_basename( __FILE__ );
	}

	/**
	 * Determines if the EDD active.
	 *
	 * @return bool
	 * 
	 * @since 1.0.0
	 */
	public function is_edd_active() {
		include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
		return is_plugin_active( 'easy-digital-downloads/easy-digital-downloads.php' ) == true;
	}

	/**
	 * Throw error on object clone
	 *
	 * The whole idea of the singleton design pattern is that there is a single
	 * object therefore, we don't want the object to be cloned.
	 *
	 * @since 1.0.0
	 * 
	 * @access public
	 * @return void
	 */
	public function __clone() {
		// Cloning instances of the class is forbidden
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'bt-edd-mailjet' ), BT_EDD_MAILJET_VERSION );
	}

	/**
	 * Disable unserializing of the class
	 *
	 * @since 1.0.0
	 * 
	 * @access public
	 * @return void
	 */
	public function __wakeup() {
		// Unserializing instances of the class is forbidden
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'bt-edd-mailjet' ), BT_EDD_MAILJET_VERSION );
	}

	/**
	 * BT_EDD_Mailjet_Addon constructor.
	 * 
	 * @since 1.0.0
	 */
	protected function __construct() {
		$this->setup_constants();
		$this->includes();
		$this->init_hooks();
		$this->settings = new BT_EDD_Settings();
		$this->api 		= new BT_EDD_MailJet();
		$this->logger 	= new BT_EDD_Log_Handler();
	}

	/**
	 * Setup plugin constants
	 *
	 * @since 1.0.0
	 * 
	 * @access private
	 * 
	 * @return void
	 */
	private function setup_constants() {
		define( 'BT_EDD_MAILJET_VERSION', $this->version );
		define( 'BT_EDD_MAILJET_MIN_PHP_VERSION', '5.6' );
		define( 'BT_EDD_MAILJET_PLUGIN_FILE', __FILE__ );
		define( 'BT_EDD_MAILJET_PATH', dirname( BT_EDD_MAILJET_PLUGIN_FILE ) );
		define( 'BT_EDD_MAILJET_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
		define( 'BT_EDD_MAILJET_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
		define( 'BT_EDD_MAILJET_PLUGIN_INCLUDES', BT_EDD_MAILJET_PATH . '/includes' );
		define( 'BT_EDD_MAILJET_PLUGIN_ASSETS', BT_EDD_MAILJET_PLUGIN_URL . 'assets' );
	}

	/**
	 * Include required core files used in admin and on the frontend.
	 * 
	 * @since 1.0.0
	 */
	public function includes() {
		require_once BT_EDD_MAILJET_PLUGIN_INCLUDES . '/script-functions.php';
		require_once BT_EDD_MAILJET_PLUGIN_INCLUDES . '/core-functions.php';
		require_once BT_EDD_MAILJET_PLUGIN_INCLUDES . '/class-metabox.php';
		require_once BT_EDD_MAILJET_PLUGIN_INCLUDES . '/class-settings.php';
		require_once BT_EDD_MAILJET_PLUGIN_INCLUDES . '/class-mailjet.php';
		require_once BT_EDD_MAILJET_PLUGIN_INCLUDES . '/class-user.php';
		require_once BT_EDD_MAILJET_PLUGIN_INCLUDES . '/class-actions.php';
		require_once BT_EDD_MAILJET_PLUGIN_INCLUDES . '/class-eddmailjet-control.php';
		require_once BT_EDD_MAILJET_PLUGIN_INCLUDES . '/class-log-handler.php';
	}

	/**
	 * Hook into actions and filters.
	 *
	 * @since 1.0.0
	 */
	private function init_hooks() {
		add_action( 'plugins_loaded', array( $this, 'on_plugins_loaded' ), - 1 );
		add_action( 'plugins_loaded', array( $this, 'localization_setup' ) );
		add_action( 'admin_notices', array( $this, 'edd_required_notice' ) );
	}

	/**
	 * When WP has loaded all plugins, trigger the `plugins_loaded` hook.
	 *
	 * This ensures `plugins_loaded` is called only after all other plugins
	 * are loaded, to avoid issues caused by plugin directory naming changing
	 *
	 * @since 1.0.0
	 */
	public function on_plugins_loaded() {
		do_action( 'bt_edd_mailjet_loaded' );
	}

	/**
	 * Initialize plugin for localization
	 *
	 * @return void
	 * 
	 * @since 1.0.0
	 *
	 */
	public function localization_setup() {
		load_plugin_textdomain( 'bt-edd-mailjet', false, dirname( plugin_basename( __FILE__ ) ) . '/i18n/languages/' );
	}

	/**
	 * Returns error message and deactivates plugin when wc not installed.
	 *
	 * @since 1.0.0
	 */
	public function edd_required_notice() {
		
		if ( current_user_can( 'manage_options' ) && ! $this->is_edd_active() ) {
			$message = sprintf( __( '<strong>Easy Digital Downloads - Mailjet</strong> requires <strong>Easy Digital Downloads</strong> installed and activated. Please Install %s Easy Digital Downloads. %s', 'bt-edd-mailjet' ),
				'<a href="https://wordpress.org/plugins/easy-digital-downloads/" target="_blank">', '</a>' );
			echo sprintf( '<div class="notice notice-error"><p>%s</p></div>', $message );
		}
	}

}

/**
 * The main function responsible for returning the one true BT_EDD_Mailjet_Addon
 * Instance to functions everywhere.
 *
 * Use this function like you would a global variable, except without needing
 * to declare the global.
 *
 * @return BT_EDD_Mailjet_Addon
 * 
 * @since 1.0.0
 */
function bt_edd_mailjet() {
	return BT_EDD_Mailjet_Addon::get_instance();
}

// Get BT_EDD_Mailjet_Addon Running
bt_edd_mailjet();
