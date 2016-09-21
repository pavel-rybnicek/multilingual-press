<?php # -*- coding: utf-8 -*-
/*
 * Plugin Name: MultilingualPress
 * Plugin URI:  https://wordpress.org/plugins/multilingual-press/
 * Description: Create a fast translation network on WordPress multisite. Run each language in a separate site, and connect the content in a lightweight user interface. Use a customizable widget to link to all sites.
 * Author:      Inpsyde GmbH
 * Author URI:  http://inpsyde.com
 * Version:     3.0.0-dev
 * Text Domain: multilingual-press
 * Domain Path: languages
 * License:     MIT
 * Network:     true
 */

namespace Inpsyde\MultilingualPress;

use Inpsyde\MultilingualPress\Common\Type\SemanticVersionNumber;

defined( 'ABSPATH' ) or die();

if ( file_exists( __DIR__ . '/vendor/autoload.php' ) ) {
	/**
	 * Composer-generated autoload file.
	 */
	require_once __DIR__ . '/vendor/autoload.php';
}

// Kick-Off
add_action( 'plugins_loaded', __NAMESPACE__ . '\\init', 0 );

/**
 * Initialize the plugin.
 *
 * @wp-hook plugins_loaded
 *
 * @return void
 */
function init() {

	if ( ! class_exists( 'Mlp_Load_Controller' ) ) {
		require __DIR__ . '/src/inc/autoload/Mlp_Load_Controller.php';
	}

	$properties = new Core\Properties( $this->plugin_file_path );
	$properties->lock();

	$container = new Service\Container();
	$container->share( 'mlp.properties', $properties );

	$mlp = new MultilingualPress( $container );
	$mlp
		->add_service_provider( new Core\CoreServiceProvider() )
		->add_service_provider( new Assets\ServiceProvider() )
		->add_service_provider( new API\ServiceProvider() )
		->add_service_provider( new Core\FeaturesServiceProvider() )
		->add_service_provider( new Core\TranslationMetaboxServiceProvider() )
		->add_service_provider( new Module\AlternativeLanguageTitleInAdminBar\ServiceProvider() )
		->add_service_provider( new Module\Quicklinks\ServiceProvider() )
		->add_service_provider( new Module\Redirect\ServiceProvider() )
		->add_service_provider( new Module\Translation\ServiceProvider() )
		->add_service_provider( new Module\PostTypeSupport\ServiceProvider() )
		->add_service_provider( new Module\Trasher\ServiceProvider() )
		->add_service_provider( new Module\UserAdminLanguage\ServiceProvider() );

	/**
	 * Fires after core providers have been added.
	 * Useful to add custom providers from extensions / plugins.
	 *
	 * @param MultilingualPress $mlp Plugin front controller.
	 */
	do_action( 'inpsyde_mlp_add_providers', $mlp );

	/**
	 * @var bool                          $check_ok
	 * @var \Mlp_Site_Relations_Interface $site_relations
	 */
	list( $check_ok, $site_relations ) = pre_run_test( $properties );

	if ( $check_ok && $site_relations instanceof \Mlp_Site_Relations_Interface ) {

		$container->share( 'mlp.site_relations', $site_relations );

		add_filter(
			'inpsyde_mlp_container',
			function () use ( $container ) {

				return $container;
			}
		);

		require_once __DIR__ . 'src/inc/functions.php';

		$mlp->bootstrap();
	}
}

/**
 * Check current state of the WordPress installation.
 *
 * @param  Core\Properties $properties
 *
 * @return array
 */
function pre_run_test( Core\Properties $properties ) {

	global $pagenow, $wp_version, $wpdb;

	$self_check         = new \Mlp_Self_Check( __FILE__, $pagenow );
	$requirements_check = $self_check->pre_install_check(
		$properties->plugin_name(),
		$properties->plugin_base_name(),
		$wp_version
	);

	if ( \Mlp_Self_Check::PLUGIN_DEACTIVATED === $requirements_check ) {
		return [ FALSE, NULL ];
	}

	$site_relations = new \Mlp_Site_Relations( $wpdb, 'mlp_site_relations' );

	if ( \Mlp_Self_Check::INSTALLATION_CONTEXT_OK === $requirements_check ) {

		$deactivator = new \Mlp_Network_Plugin_Deactivation();

		$last_ver      = SemanticVersionNumber::create( get_site_option( 'mlp_version' ) );
		$current_ver   = SemanticVersionNumber::create( $properties->version() );
		$upgrade_check = $self_check->is_current_version( $current_ver, $last_ver );
		$updater       = new \Mlp_Update_Plugin_Data( $properties, $site_relations, $current_ver, $last_ver );

		if ( \Mlp_Self_Check::NEEDS_INSTALLATION === $upgrade_check ) {
			$updater->install_plugin();
		}

		if ( \Mlp_Self_Check::NEEDS_UPGRADE === $upgrade_check ) {
			$updater->update( $deactivator );
		}
	}

	return [ TRUE, $site_relations ];
}

/**
 * Write debug data to the error log.
 *
 * Add the following line to your `wp-config.php` to enable this function:
 *
 *     const MULTILINGUALPRESS_DEBUG = TRUE;
 *
 * @param string $message
 *
 * @return void
 */
function debug( $message ) {

	if ( ! defined( 'MULTILINGUALPRESS_DEBUG' ) || ! MULTILINGUALPRESS_DEBUG ) {
		$date = date( 'H:m:s' );

		error_log( "MultilingualPress: $date $message" );
	}
}

if ( defined( 'MULTILINGUALPRESS_DEBUG' ) && MULTILINGUALPRESS_DEBUG ) {
	add_action( 'mlp_debug', __NAMESPACE__ . '\\debug' );
}
