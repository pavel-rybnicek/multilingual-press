<?php # -*- coding: utf-8 -*-

namespace Inpsyde\MultilingualPress\Module\Quicklinks;

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

	const MODULE = 'quicklinks';

	/**
	 * @inheritdoc
	 */
	public function provide( Container $container ) {

		$container[ 'mlp.module.quicklinks' ] = function ( Container $container ) {

			return new \Mlp_Quicklink( $container[ 'mlp.api' ], $container[ 'mlp.assets' ] );
		};

		return TRUE;
	}

	/**
	 * @inheritdoc
	 */
	public function boot( Container $container ) {

		$this->on_activation(
			function () use ( $container ) {

				/** @var \Mlp_Quicklink $quicklinks */
				$quicklinks = $container[ 'mlp.module.quicklinks' ];
				$quicklinks->initialize( $this->provided_module() );
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
				'description'  => __( 'Show link to translations in post content.', 'multilingual-press' ),
				'display_name' => __( 'Quicklink', 'multilingual-press' ),
				'slug'         => 'module-' . self::MODULE,
				'state'        => 'off',
			]
		);
	}
}