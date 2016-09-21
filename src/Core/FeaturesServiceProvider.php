<?php # -*- coding: utf-8 -*-

namespace Inpsyde\MultilingualPress\Core;

use Inpsyde\MultilingualPress\Service\BootableServiceProvider;
use Inpsyde\MultilingualPress\Service\Container;

/**
 * Service provider for "features".
 *
 * TODO: this will removed when refactoring for version 3.0.0 is done and replaced by specific providers.
 *
 * @package Inpsyde\MultilingualPress\Assets
 * @since   3.0.0
 */
final class FeaturesServiceProvider implements BootableServiceProvider {

	/**
	 * @inheritdoc
	 */
	public function provide( Container $container ) {

		$container[ 'mlp.custom_columns' ] = function ( Container $container ) {

			return new \Mlp_Custom_Columns(
				[
					'id'               => 'mlp_interlinked',
					'header'           => esc_attr__( 'Relationships', 'multilingual-press' ),
					'content_callback' => $this->build_related_blog_column_callback( $container ),
				]
			);
		};

		$container[ 'mlp.dashboard_widget' ] = function ( Container $container ) {

			return new \Mlp_Dashboard_Widget( $container[ 'mlp.site_relations' ] );
		};

		$container[ 'mlp.table_duplicator' ] = function () {

			return new \Mlp_Table_Duplicator( $GLOBALS[ 'wpdb' ] );
		};

		$container[ 'mlp.blogs_duplicator' ] = function ( Container $container ) {

			return new \Mlp_Duplicate_Blogs(
				$GLOBALS[ 'wpdb' ]->base_prefix . 'multilingual_linked',
				$GLOBALS[ 'wpdb' ],
				$container[ 'mlp.table_duplicator' ],
				$container[ 'mlp.table_list' ]
			);
		};

		$container[ 'mlp.nav_menu_controller' ] = function ( Container $container ) {

			return new \Mlp_Nav_Menu_Controller( $container[ 'mlp.api' ], $container[ 'mlp.assets' ] );
		};

		$container[ 'mlp.relationships_changer' ] = function ( Container $container ) {

			return new \Mlp_Relationship_Changer( $container[ 'mlp.content_relations' ] );
		};

		$container[ 'mlp.relationships_control' ] = function ( Container $container ) {

			return new \Mlp_Relationship_Control( $container[ 'mlp.relationships_changer' ] );
		};

		$container[ 'mlp.global_switcher_post' ] = function () {

			return new \Mlp_Global_Switcher( \Mlp_Global_Switcher::TYPE_POST );
		};

		$container[ 'mlp.term_translation_controller' ] = function ( Container $container ) {

			return new \Mlp_Term_Translation_Controller( $container[ 'mlp.content_relations' ] );
		};
	}

	/**
	 * @inheritdoc
	 */
	public function boot( Container $container ) {

		add_action( 'widgets_init', [ 'Mlp_Widget', 'widget_register' ] );

		add_action( 'inpsyde_mlp_init', function() use($container) {
			\Mlp_Widget::insert_asset_instance( $container['mlp.assets'] );
		} );


		add_action(
			'mlp_and_wp_loaded',
			function () use ( $container ) {

				/** @var \Mlp_Dashboard_Widget $dashboard_widget */
				$dashboard_widget = $container[ 'mlp.dashboard_widget' ];
				$dashboard_widget->initialize();
			}
		);

		add_action(
			'inpsyde_mlp_loaded',
			function () use ( $container ) {

				/** @var \Mlp_Duplicate_Blogs $duplicator */
				$duplicator = $container[ 'mlp.blogs_duplicator' ];
				add_filter( 'wpmu_new_blog', [ $duplicator, 'wpmu_new_blog' ], 10, 2 );
				add_filter( 'mlp_after_new_blog_fields', [ $duplicator, 'display_fields' ] );

				/** @var \Mlp_Nav_Menu_Controller $nav_menu_controller */
				$nav_menu_controller = $container[ 'mlp.nav_menu_controller' ];
				$nav_menu_controller->initialize();
				is_admin()
					? $nav_menu_controller->backend_setup()
					: add_action( 'template_redirect', [ $nav_menu_controller, 'frontend_setup' ] );
			}
		);

		if ( ! is_admin() ) {
			return;
		}

		if ( ! empty( $GLOBALS[ 'pagenow' ] ) && 'sites.php' === $GLOBALS[ 'pagenow' ] ) {
			add_action(
				'inpsyde_mlp_loaded',
				function () use ( $container ) {

					$columns = $container[ 'mlp.custom_columns' ];
					add_filter( 'wpmu_blogs_columns', [ $columns, 'add_header' ] );
					add_action( 'manage_sites_custom_column', [ $columns, 'render_column' ], 10, 2 );
				}
			);
		}

		add_action(
			'mlp_and_wp_loaded',
			function () use ( $container ) {

				$method = array_key_exists( 'REQUEST_METHOD', $_SERVER ) ? $_SERVER[ 'REQUEST_METHOD' ] : '';
				if ( $method !== 'POST' ) {
					return;
				}

				/** @var \Mlp_Relationship_Control $relationships_control */
				$relationships_control = $container[ 'mlp.relationships_control' ];
				$set_up_meta_box       = [ $relationships_control, 'set_up_meta_box_handlers' ];
				$relationships_control->is_ajax()
					? $relationships_control->set_up_ajax()
					: add_action( 'mlp_translation_meta_box_bottom', $set_up_meta_box, 200, 3 );

				/** @var \Mlp_Global_Switcher $switcher */
				$switcher = $container[ 'mlp.global_switcher_post' ];
				add_action( 'mlp_before_post_synchronization', [ $switcher, 'strip' ] );
				add_action( 'mlp_after_post_synchronization', [ $switcher, 'fill' ] );
			}
		);

		add_action(
			'mlp_and_wp_loaded',
			function() use ( $container ) {
				/** @var \Mlp_Term_Translation_Controller $term_translation_controller */
				$term_translation_controller = $container[ 'mlp.term_translation_controller' ];
				$term_translation_controller->setup();
			},
			1000
		);
	}

	/**
	 * @param Container $container
	 *
	 * @return \Closure
	 */
	private function build_related_blog_column_callback( Container $container ) {

		return function ( $column_name, $blog_id ) use ( $container ) {

			switch_to_blog( $blog_id );
			/** @var \Mlp_Language_Api $api */
			$api   = $container[ 'mlp.api' ];
			$blog  = get_current_blog_id();
			$blogs = $api->get_site_languages( $blog );
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