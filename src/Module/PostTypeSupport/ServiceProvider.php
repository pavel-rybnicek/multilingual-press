<?php # -*- coding: utf-8 -*-

namespace Inpsyde\MultilingualPress\Module\PostTypeSupport;

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

	const MODULE = 'cpt_support';

	/**
	 * @inheritdoc
	 */
	public function provide( Container $container ) {

		$container[ 'mlp.module.cpt_support' ] = function ( Container $container ) {

			return new \Mlp_Cpt_Translator();
		};

		return TRUE;
	}

	/**
	 * @inheritdoc
	 */
	public function boot( Container $container ) {

		$this->on_activation(
			function () use ( $container ) {

				$cpt_support = $container[ 'mlp.module.cpt_support' ];

				add_filter( 'mlp_allowed_post_types', [ $cpt_support, 'filter_allowed_post_types' ] );

				add_action( 'mlp_modules_add_fields', [ $cpt_support, 'draw_options_page_form_fields' ] );
				// Use this hook to handle the user input of your modules' options page form fields
				add_action( 'mlp_modules_save_fields', [ $cpt_support, 'save_options_page_form_fields' ] );

				// replace the permalink if selected
				add_action( 'mlp_before_link', [ $cpt_support, 'before_mlp_link' ] );
				add_action( 'mlp_after_link', [ $cpt_support, 'after_mlp_link' ] );
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
	public function setup_module( \Mlp_Module_Manager $module_manager, Container $container ) {

		$cpt_support = $container[ 'mlp.module.cpt_support' ];

		$module_manager->register(
			[
				'description'  => __(
					'Enable translation of custom post types. Creates a second settings box below this. The post types must be activated for the whole network or on the main site.',
					'multilingual-press'
				),
				'display_name' => __( 'Custom Post Type Translator', 'multilingual-press' ),
				'slug'         => 'module-' . self::MODULE,
				'state'        => 'off',
				'callback'     => [ $cpt_support, 'extend_settings_description' ],
			]
		);
	}
}