<?php # -*- coding: utf-8 -*-

namespace Inpsyde\MultilingualPress\Module\PostTypeSupport;

use Inpsyde\MultilingualPress\Module\ActivationAwareness;
use Inpsyde\MultilingualPress\Module\ModuleServiceProvider;
use Inpsyde\MultilingualPress\Service\Container;

/**
 * Service provider for the Post Type Support module.
 *
 * @package Inpsyde\MultilingualPress\Module\PostTypeSupport
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

		$this->module = 'post_type_support';
	}

	/**
	 * Registers the provided services on the given container.
	 *
	 * @since 3.0.0
	 *
	 * @param Container $container Container object.
	 */
	public function register( Container $container ) {

		$container['multilingualpress.module.post_type_support'] = function () {

			return new \Mlp_Cpt_Translator();
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

		$post_type_support = $container['multilingualpress.module.post_type_support'];

		$this->on_activation( function () use ( $post_type_support ) {

			add_filter( 'mlp_allowed_post_types', [ $post_type_support, 'filter_allowed_post_types' ] );

			add_action( 'mlp_modules_add_fields', [ $post_type_support, 'draw_options_page_form_fields' ] );

			// Use this hook to handle the user input of your modules' options page form fields.
			add_action( 'mlp_modules_save_fields', [ $post_type_support, 'save_options_page_form_fields' ] );

			// Replace the permalink if selected.
			add_action( 'mlp_before_link', [ $post_type_support, 'before_mlp_link' ] );
			add_action( 'mlp_after_link', [ $post_type_support, 'after_mlp_link' ] );
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

		$post_type_support = $container['multilingualpress.module.post_type_support'];

		return $module_manager->register( [
			'description'  => __(
				'Enable translation of custom post types. Creates a second settings box below this. The post types must be activated for the whole network or on the main site.',
				'multilingual-press'
			),
			'display_name' => __( 'Custom Post Type Translator', 'multilingual-press' ),
			'slug'         => "module-{$this->module}",
			'state'        => 'off',
			'callback'     => [ $post_type_support, 'extend_settings_description' ],
		] );
	}
}
