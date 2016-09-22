<?php # -*- coding: utf-8 -*-

namespace Inpsyde\MultilingualPress\Module\AdvancedTranslator;

use Inpsyde\MultilingualPress\Module\ActivationAwareness;
use Inpsyde\MultilingualPress\Module\ModuleServiceProvider;
use Inpsyde\MultilingualPress\Service\Container;

/**
 * Service provider for the Advanced Translator module.
 *
 * @package Inpsyde\MultilingualPress\Module\AdvancedTranslator
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

		$this->module = 'advanced_translator';
	}

	/**
	 * Registers the provided services on the given container.
	 *
	 * @since 3.0.0
	 *
	 * @param Container $container Container object.
	 */
	public function register( Container $container ) {

		$container['multilingualpress.module.advanced_translator'] = function ( Container $container ) {

			return new \Mlp_Advanced_Translator( $container['multilingualpress.site_relations'] );
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

		$translator = $container['multilingualpress.module.advanced_translator'];

		$this->on_activation( function () use ( $translator ) {

			if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
				add_action( 'wp_ajax_' . \Mlp_Advanced_Translator::AJAX_ACTION, [ $translator, 'process_post_data' ] );
			}

			add_action( 'mlp_post_translator_init', [ $translator, 'setup' ] );

			add_filter( 'mlp_external_save_method', '__return_true' );

			add_action( 'mlp_translation_meta_box_registered', [ $translator, 'register_metabox_view_details' ], 10, 2 );
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
				'Use the WYSIWYG editor to write all translations on one screen, including thumbnails and taxonomies.',
				'multilingual-press'
			),
			'display_name' => __( 'Advanced Translator', 'multilingual-press' ),
			'slug'         => "module-{$this->module}",
		] );
	}
}
