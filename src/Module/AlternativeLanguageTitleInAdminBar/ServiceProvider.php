<?php # -*- coding: utf-8 -*-

namespace Inpsyde\MultilingualPress\Module\AlternativeLanguageTitleInAdminBar;

use Inpsyde\MultilingualPress\Service\Container;
use Inpsyde\MultilingualPress\Service\ModuleServiceProvider;
use Inpsyde\MultilingualPress\Service\ModuleServiceProviderOnActivationTrait;

/**
 * Service provider for alternative language title in admin bar module.
 *
 * @package Inpsyde\MultilingualPress\Assets
 * @since   3.0.0
 */
final class ServiceProvider implements ModuleServiceProvider {

	use ModuleServiceProviderOnActivationTrait;

	const MODULE = 'admin_bar_customizer';

	/**
	 * @inheritdoc
	 */
	public function provide( Container $container ) {

		$container[ 'mlp.module.admin_bar_customizer' ] = function () {

			return new \Mlp_Admin_Bar_Customizer();
		};

		return TRUE;
	}

	/**
	 * @inheritdoc
	 */
	public function boot( Container $container ) {

		/** @var \Mlp_Admin_Bar_Customizer $customizer */
		$customizer = $container[ 'mlp.module.admin_bar_customizer' ];

		add_action( 'mlp_blogs_save_fields', [ $customizer, 'update_cache' ] );

		$this->on_activation(
			function () use ( $customizer ) {

				add_filter( 'admin_bar_menu', [ $customizer, 'replace_site_nodes' ], 11 );
				is_network_admin() or add_filter( 'admin_bar_menu', [ $customizer, 'replace_site_name' ], 31 );
			}
		);

		return TRUE;
	}

	/**
	 * @inheritdoc
	 */
	public function provided_module() {

		return self::MODULE;
	}

	/**
	 * @inheritdoc
	 */
	public function register_module( \Mlp_Module_Manager $module_manager, Container $container ) {

		return $module_manager->register(
			[
				'description'  => __(
					'Show sites with their alternative language title in the admin bar.',
					'multilingual-press'
				),
				'display_name' => __( 'Alternative Language Title', 'multilingual-press' ),
				'slug'         => 'module-' . self::MODULE,
				'state'        => 'off',
			]
		);
	}
}