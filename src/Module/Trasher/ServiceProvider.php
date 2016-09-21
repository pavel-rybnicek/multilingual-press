<?php # -*- coding: utf-8 -*-

namespace Inpsyde\MultilingualPress\Module\Trasher;

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

	const MODULE = 'trasher';

	/**
	 * @inheritdoc
	 */
	public function provide( Container $container ) {

		$container[ 'mlp.module.trasher' ] = function () {

			new \Mlp_Trasher();

		};

		return TRUE;
	}

	/**
	 * @inheritdoc
	 */
	public function boot( Container $container ) {

		$on_activation_callable = function () use ( $container ) {

			add_action(
				'mlp_and_wp_loaded',
				function () use ( $container ) {

					$trasher = $container[ 'mlp.module.trasher' ];
					add_action( 'post_submitbox_misc_actions', [ $trasher, 'post_submitbox_misc_actions' ] );
					add_action( 'wp_trash_post', [ $trasher, 'trash_post' ] );
					add_action( 'save_post', [ $trasher, 'save_post' ] );
				}
			);
		};

		$this->on_activation( $on_activation_callable );

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
				'display_name' => __( 'Trasher', 'multilingual-press' ),
				'slug'         => 'module-' . self::MODULE,
				'description'  => __(
					'This module provides a new post meta and checkbox to trash the posts. If you enable the checkbox and move a post to the trash MultilingualPress also will trash the linked posts.',
					'multilingual-press'
				),
			]
		);
	}
}