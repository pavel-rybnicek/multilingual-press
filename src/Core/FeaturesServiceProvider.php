<?php # -*- coding: utf-8 -*-

namespace Inpsyde\MultilingualPress\Core;

use Inpsyde\MultilingualPress\Service\BootstrappableServiceProvider;
use Inpsyde\MultilingualPress\Service\Container;

/**
 * Service provider for Feature objects.
 *
 * TODO: this will removed when refactoring for version 3.0.0 is done and replaced by specific providers.
 *
 * @package Inpsyde\MultilingualPress\Core
 * @since   3.0.0
 */
final class FeaturesServiceProvider implements BootstrappableServiceProvider {

	/**
	 * Registers the provided services on the given container.
	 *
	 * @since 3.0.0
	 *
	 * @param Container $container Container object.
	 */
	public function register( Container $container ) {

		$container['multilingualpress.custom_columns'] = function ( Container $container ) {

			return new \Mlp_Custom_Columns( [
				'id'               => 'mlp_interlinked',
				'header'           => esc_attr__( 'Relationships', 'multilingual-press' ),
				'content_callback' => $this->build_related_blog_column_callback( $container ),
			] );
		};

		$container['multilingualpress.dashboard_widget'] = function ( Container $container ) {

			return new \Mlp_Dashboard_Widget( $container['multilingualpress.site_relations'] );
		};

		$container['multilingualpress.table_duplicator'] = function () {

			return new \Mlp_Table_Duplicator( $GLOBALS['wpdb'] );
		};

		$container['multilingualpress.blogs_duplicator'] = function ( Container $container ) {

			return new \Mlp_Duplicate_Blogs(
				$GLOBALS['wpdb']->base_prefix . 'multilingual_linked',
				$GLOBALS['wpdb'],
				$container['multilingualpress.table_duplicator'],
				$container['multilingualpress.table_list']
			);
		};

		$container['multilingualpress.nav_menu_controller'] = function ( Container $container ) {

			return new \Mlp_Nav_Menu_Controller(
				$container['multilingualpress.languages'],
				$container['multilingualpress.assets']
			);
		};

		$container['multilingualpress.relationship_changer'] = function ( Container $container ) {

			return new \Mlp_Relationship_Changer( $container['multilingualpress.content_relations'] );
		};

		$container['multilingualpress.relationship_control'] = function ( Container $container ) {

			return new \Mlp_Relationship_Control( $container['multilingualpress.relationship_changer'] );
		};

		// TODO: Think about POST-specific and GET-specific one - or not to use the container at all for this.

		$container['multilingualpress.global_switcher_post'] = function () {

			return new \Mlp_Global_Switcher( \Mlp_Global_Switcher::TYPE_POST );
		};

		$container['multilingualpress.term_translation_controller'] = function ( Container $container ) {

			return new \Mlp_Term_Translation_Controller( $container['multilingualpress.content_relations'] );
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

		add_action( 'widgets_init', [ 'Mlp_Widget', 'widget_register' ] );

		add_action( 'inpsyde_mlp_init', function () use ( $container ) {

			\Mlp_Widget::insert_asset_instance( $container['multilingualpress.assets'] );
		} );

		$dashboard_widget = $container['multilingualpress.dashboard_widget'];

		add_action( 'mlp_and_wp_loaded', function () use ( $dashboard_widget ) {

			$dashboard_widget->initialize();
		} );

		$duplicator = $container['multilingualpress.blogs_duplicator'];

		$nav_menu_controller = $container['multilingualpress.nav_menu_controller'];

		add_action( 'inpsyde_mlp_loaded', function () use ( $duplicator, $nav_menu_controller ) {

			add_action( 'wpmu_new_blog', [ $duplicator, 'wpmu_new_blog' ], 10, 2 );
			add_action( 'mlp_after_new_blog_fields', [ $duplicator, 'display_fields' ] );

			$nav_menu_controller->initialize();

			if ( is_admin() ) {
				$nav_menu_controller->backend_setup();

				return;
			}

			add_action( 'template_redirect', [ $nav_menu_controller, 'frontend_setup' ] );
		} );

		if ( ! is_admin() ) {
			return;
		}

		if ( ! empty( $GLOBALS['pagenow'] ) && 'sites.php' === $GLOBALS['pagenow'] ) {
			$columns = $container['multilingualpress.custom_columns'];

			add_action( 'inpsyde_mlp_loaded', function () use ( $columns ) {

				add_filter( 'wpmu_blogs_columns', [ $columns, 'add_header' ] );
				add_action( 'manage_sites_custom_column', [ $columns, 'render_column' ], 10, 2 );
			} );
		}

		if ( isset( $_SERVER['REQUEST_METHOD'] ) && 'POST' === $_SERVER['REQUEST_METHOD'] ) {
			$switcher = $container['multilingualpress.global_switcher_post'];

			$rel_control = $container['multilingualpress.relationship_control'];

			add_action( 'mlp_and_wp_loaded', function () use ( $switcher, $rel_control ) {

				add_action( 'mlp_before_post_synchronization', [ $switcher, 'strip' ] );
				add_action( 'mlp_after_post_synchronization', [ $switcher, 'fill' ] );

				if ( $rel_control->is_ajax() ) {
					$rel_control->set_up_ajax();

					return;
				}

				add_action( 'mlp_translation_meta_box_bottom', [ $rel_control, 'set_up_meta_box_handlers' ], 200, 3 );
			} );
		}

		$term_translation_controller = $container['multilingualpress.term_translation_controller'];

		add_action( 'mlp_and_wp_loaded', function () use ( $term_translation_controller ) {

			$term_translation_controller->setup();
		}, 1000 );
	}

	/**
	 * Returns the callback for the Related Sites column.
	 *
	 * @param Container $container Container object.
	 *
	 * @return callable Callback.
	 */
	private function build_related_blog_column_callback( Container $container ) {

		return function ( $column_name, $blog_id ) use ( $container ) {

			switch_to_blog( $blog_id );

			$languages = $container['multilingualpress.languages'];

			$blogs = $languages->get_site_languages( get_current_blog_id() );

			restore_current_blog();

			unset( $blogs[ $blog_id ] );

			if ( empty( $blogs ) ) {
				return esc_html__( 'none', 'multilingual-press' );
			}

			$blogs = array_map( 'esc_html', $blogs );

			return '<div class="mlp_interlinked_blogs">' . join( '<br>', $blogs ) . '</div>';
		};
	}
}
