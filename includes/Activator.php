<?php

namespace SCFS;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Activator {

	private static $plugin_status_option = 'scfs_plugin_status';

	/**
	 * Debug logger.
	 *
	 * @param string $message Log message.
	 * @return void
	 */
	private static function log( $message ) {

		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Development logging only.
			error_log( '[SCFS] ' . $message );
		}
	}

	public static function activate() {

		// Creează tabela în baza de date.
		AjaxHandler::create_database_table_on_activation();

		// Verifică dacă există date de migrat.
		$ajax_handler = AjaxHandler::get_instance();

		if ( $ajax_handler->has_data_to_migrate() ) {
			self::log( 'Data migration needed on activation' );
		}

		// Adaugă opțiuni implicite.
		self::add_default_options();

		// Setează statusul pluginului ca activ.
		update_option( self::$plugin_status_option, 1 );

		self::log( 'Plugin activated' );
	}

	public static function deactivate() {

		// Setează statusul pluginului ca inactiv.
		update_option( self::$plugin_status_option, 0 );

		self::log( 'Plugin deactivated' );
	}

	public static function uninstall() {

		// Șterge opțiunile pluginului.
		delete_option( self::$plugin_status_option );
		delete_option( 'scfs_social_settings' );
		delete_option( 'scfs_cdn_settings_predefined' );
		delete_option( 'scfs_cdn_settings_custom' );
		delete_option( 'scfs_social_buttons' );

		self::log( 'Plugin uninstalled' );
	}

	public static function is_active() {
		return (bool) get_option( self::$plugin_status_option, 1 );
	}

	private static function add_default_options() {

		// Adaugă setările implicite dacă nu există.
		if ( ! get_option( 'scfs_social_settings' ) ) {

			update_option(
				'scfs_social_settings',
				array(
					'position'               => 'right',
					'button_color'           => '#0073aa',
					'button_icon'            => '☰',
					'animation'              => 'slide',
					'mobile_enabled'         => 1,
					'show_names'             => 1,
					'transparent_icons'      => 0,
					'custom_message'         => 'Let`s chat with US!',
					'show_custom_message'    => 1,
					'show_shortcut_names'    => 1,
					'button_primary_color'   => 'var(--primary)',
					'button_secondary_color' => 'var(--secondary)',
					'use_theme_colors'       => 1,
				)
			);
		}

		// Adaugă statusul pluginului dacă nu există.
		if ( ! get_option( self::$plugin_status_option ) ) {
			update_option( self::$plugin_status_option, 1 );
		}
	}
}