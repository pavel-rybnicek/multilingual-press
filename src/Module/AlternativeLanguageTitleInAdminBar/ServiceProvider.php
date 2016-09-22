<?php # -*- coding: utf-8 -*-

namespace Inpsyde\MultilingualPress\Module\AlternativeLanguageTitleInAdminBar;

use Inpsyde\MultilingualPress\Module\ActivationAwareness;
use Inpsyde\MultilingualPress\Module\ModuleServiceProvider;
use Inpsyde\MultilingualPress\Service\Container;

/**
 * Service provider for the Alternative Language Title In Admin Bar module.
 *
 * @package Inpsyde\MultilingualPress\Module\AlternativeLanguageTitleInAdminBar
 * @since   3.0.0
 */
final class ServiceProvider implements ModuleServiceProvider {

	use ActivationAwareness;

	/**
	 * Constructor. Sets up the properties.
	 *
	 * @since 3.0.0
	 */
	public function __construct() {

		$this->module = 'alternative_language_title_in_admin_bar';
	}

	/**
	 * Registers the provided services on the given container.
	 *
	 * @since 3.0.0
	 *
	 * @param Container $container Container object.
	 */
	public function register( Container $container ) {

		$container['multilingualpress.module.admin_bar_customizer'] = function () {

			return new \Mlp_Admin_Bar_Customizer();
		};
	}

	/**
	 * Bootstraps the registered services.
	 *
	 * @since 3.0.0
	 *
	 * @param Container $container Container object.
	 */
	public function bootstrap( Container $container ) {

		$customizer = $container['multilingualpress.module.admin_bar_customizer'];

		add_action( 'mlp_blogs_save_fields', [ $customizer, 'update_cache' ] );

		$this->on_activation( function () use ( $customizer ) {

			add_filter( 'admin_bar_menu', [ $customizer, 'replace_site_nodes' ], 11 );

			if ( ! is_network_admin() ) {
				add_filter( 'admin_bar_menu', [ $customizer, 'replace_site_name' ], 31 );
			}
		} );
	}

	/**
	 * Registers the module at the module manager.
	 *
	 * @since 3.0.0
	 *
	 * @param \Mlp_Module_Manager_Interface $module_manager Module manager object.
	 * @param Container                     $container      Container object.
	 *
	 * @return bool Whether or not the module was registerd successfully AND was activated.
	 */
	public function register_module( \Mlp_Module_Manager_Interface $module_manager, Container $container ) {

		return $module_manager->register( [
			'description'  => __(
				'Show sites with their alternative language title in the admin bar.',
				'multilingual-press'
			),
			'display_name' => __( 'Alternative Language Title', 'multilingual-press' ),
			'slug'         => "module-{$this->module}",
			'state'        => 'off',
		] );
	}
}
