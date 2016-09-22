<?php # -*- coding: utf-8 -*-

namespace Inpsyde\MultilingualPress\Module\Trasher;

use Inpsyde\MultilingualPress\Module\ActivationAwareness;
use Inpsyde\MultilingualPress\Module\ModuleServiceProvider;
use Inpsyde\MultilingualPress\Service\Container;

/**
 * Service provider for the Trasher module.
 *
 * @package Inpsyde\MultilingualPress\Module\Trasher
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

		$this->module = 'trasher';
	}

	/**
	 * Registers the provided services on the given container.
	 *
	 * @since 3.0.0
	 *
	 * @param Container $container Container object.
	 */
	public function register( Container $container ) {

		$container['multilingualpress.module.trasher'] = function () {

			return new \Mlp_Trasher();
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

		$trasher = $container['multilingualpress.module.trasher'];

		$this->on_activation( function () use ( $trasher ) {

			add_action( 'mlp_and_wp_loaded', function () use ( $trasher ) {

				add_action( 'post_submitbox_misc_actions', [ $trasher, 'post_submitbox_misc_actions' ] );
				add_action( 'wp_trash_post', [ $trasher, 'trash_post' ] );
				add_action( 'save_post', [ $trasher, 'save_post' ] );
			} );
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
				'This module provides a new post meta and checkbox to trash the posts. If you enable the checkbox and move a post to the trash MultilingualPress also will trash the linked posts.',
				'multilingual-press'
			),
			'display_name' => __( 'Trasher', 'multilingual-press' ),
			'slug'         => "module-{$this->module}",
		] );
	}
}
