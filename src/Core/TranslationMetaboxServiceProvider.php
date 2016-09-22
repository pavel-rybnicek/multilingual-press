<?php # -*- coding: utf-8 -*-

namespace Inpsyde\MultilingualPress\Core;

use Inpsyde\MultilingualPress\Service\BootstrappableServiceProvider;
use Inpsyde\MultilingualPress\Service\Container;

/**
 * Service provider for Assets objects.
 *
 * @package Inpsyde\MultilingualPress\Core
 * @since   3.0.0
 */
final class TranslationMetaboxServiceProvider implements BootstrappableServiceProvider {

	/**
	 * Registers the provided services on the given container.
	 *
	 * @since 3.0.0
	 *
	 * @param Container $container Container object.
	 */
	public function register( Container $container ) {

		$container['multilingualpress.post_types'] = function () {

			return new \ArrayObject( (array) apply_filters( 'mlp_allowed_post_types', [
				'post',
				'page',
			] ) );
		};

		$container['multilingualpress.translatable_post_data'] = function ( Container $container ) {

			$post_types = $container['multilingualpress.post_types'];

			return new \Mlp_Translatable_Post_Data(
				$post_types->getArrayCopy(),
				$GLOBALS['wpdb']->base_prefix . 'multilingual_linked',
				$container['multilingualpress.content_relations']
			);
		};

		$container['multilingualpress.translation_metabox'] = function ( Container $container ) {

			$post_types = $container['multilingualpress.post_types'];

			return new \Mlp_Translation_Metabox(
				$post_types->getArrayCopy(),
				$container['multilingualpress.translatable_post_data'],
				$container['multilingualpress.site_relations'],
				$container['multilingualpress.assets']
			);
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

		if ( ! is_admin() ) {
			return;
		}

		if ( isset( $_SERVER['REQUEST_METHOD'] ) && 'POST' === $_SERVER['REQUEST_METHOD'] ) {
			$translation_metabox = $container['multilingualpress.translation_metabox'];

			$switcher = $container['multilingualpress.global_switcher_post'];

			add_action( 'inpsyde_mlp_loaded', function () use ( $translation_metabox, $switcher ) {

				$translation_metabox->setup();

				add_action( 'mlp_before_post_synchronization', [ $switcher, 'strip' ] );
				add_action( 'mlp_after_post_synchronization', [ $switcher, 'fill' ] );
			} );
		}
	}
}
