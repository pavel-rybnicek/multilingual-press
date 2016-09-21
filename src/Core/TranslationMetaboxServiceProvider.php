<?php # -*- coding: utf-8 -*-

namespace Inpsyde\MultilingualPress\Core;

use Inpsyde\MultilingualPress\Service\BootableServiceProvider;
use Inpsyde\MultilingualPress\Service\Container;

/**
 * Service provider for translation metabox.
 *
 * TODO: this will removed when refactoring for version 3.0.0 is complete
 *
 * @package Inpsyde\MultilingualPress\Assets
 * @since   3.0.0
 */
final class TranslationMetaboxServiceProvider implements BootableServiceProvider {

	/**
	 * @inheritdoc
	 */
	public function provide( Container $container ) {

		$container['mlp.translation_metabox_post_types'] = function() {
			$allowed_post_types = (array) apply_filters( 'mlp_allowed_post_types', [ 'post', 'page', ]);

			return new \ArrayObject($allowed_post_types);

		};

		$container['mlp.translatable_post_data'] = function( Container $container ) {

			/** @var \ArrayObject $post_types */
			$post_types = $container['mlp.translation_metabox_post_types'];

			return new \Mlp_Translatable_Post_Data(
				$post_types->getArrayCopy(),
				$GLOBALS[ 'wpdb' ]->base_prefix . 'multilingual_linked',
				$container[ 'mlp.content_relations' ]
			);
		};

		$container['mlp.translation_metabox'] = function( Container $container ) {

			/** @var \ArrayObject $post_types */
			$post_types = $container['mlp.translation_metabox_post_types'];

			return new \Mlp_Translation_Metabox(
				$post_types->getArrayCopy(),
				$container['mlp.translatable_post_data'],
				$container[ 'mlp.site_relations' ],
				$container[ 'mlp.assets' ]
			);
		};
	}

	/**
	 * @inheritdoc
	 */
	public function boot( Container $container ) {

		if ( ! is_admin() ) {
			return;
		}

		$method = array_key_exists( 'REQUEST_METHOD', $_SERVER ) ? $_SERVER[ 'REQUEST_METHOD' ] : '';
		if ( $method !== 'POST' ) {
			return;
		}

		add_action(
			'inpsyde_mlp_loaded',
			function() use ( $container ) {

				/** @var \Mlp_Translation_Metabox $translation_metabox */
				$translation_metabox = $container['mlp.translation_metabox'];
				$translation_metabox->setup();

				/** @var \Mlp_Global_Switcher $switcher */
				$switcher = $container[ 'mlp.global_switcher_post' ];
				add_action( 'mlp_before_post_synchronization', [ $switcher, 'strip' ] );
				add_action( 'mlp_after_post_synchronization',  [ $switcher, 'fill' ] );
			}
		);
	}
}