<?php # -*- coding: utf-8 -*-

namespace Inpsyde\MultilingualPress\Module\Translation;

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

	const MODULE = 'translator';

	/**
	 * @inheritdoc
	 */
	public function provide( Container $container ) {

		$container[ 'mlp.module.translator' ] = function ( Container $container ) {

			return new \Mlp_Advanced_Translator( $container[ 'site_relations' ] );
		};

		return TRUE;
	}

	/**
	 * @inheritdoc
	 */
	public function boot( Container $container ) {

		$this->on_activation(
			function () use ( $container ) {

				/** @var \Mlp_Advanced_Translator $translator */
				$translator = $container[ 'mlp.module.translator' ];

				if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
					add_action(
						'wp_ajax_' . \Mlp_Advanced_Translator::AJAX_ACTION,
						[ $translator, 'process_post_data' ]
					);
				}

				add_action( 'mlp_post_translator_init', [ $translator, 'setup' ] );
				add_filter( 'mlp_external_save_method', '__return_true' );

				add_action(
					'mlp_translation_meta_box_registered',
					[ $translator, 'register_metabox_view_details' ],
					10,
					2
				);
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

		return $module_manager->register(
			[
				'display_name' => __( 'Advanced Translator', 'multilingual-press' ),
				'slug'         => 'module-' . self::MODULE,
				'description'  => __(
					'Use the WYSIWYG editor to write all translations on one screen, including thumbnails and taxonomies.',
					'multilingual-press'
				),
			]
		);
	}
}