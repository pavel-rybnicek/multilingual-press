<?php # -*- coding: utf-8 -*-

namespace Inpsyde\MultilingualPress\Core;

use Inpsyde\MultilingualPress\Service\Container;

// TODO: Get rid of this - and split things up as they make sense! Also, the container should NOT be used here at all!

/**
 * Temporary (!) class for all setup-specific stuff.
 *
 * @package Inpsyde\MultilingualPress\Core
 * @since   3.0.0
 */
class CoreSetup {

	/**
	 * Performs all setup-specific tasks.
	 *
	 * @since 3.0.0
	 *
	 * @param Container $container Container object.
	 */
	public function setup( Container $container ) {

		$module_manager = $container['multilingualpress.module_manager'];

		$site_manager = $container['multilingualpress.site_manager'];

		// Load text domain when loaded.
		add_action( 'inpsyde_mlp_loaded', function () use ( $container ) {

			$this->load_textdomain( $container );
		}, 1 );

		// Run plugin actions.
		add_action( 'inpsyde_mlp_loaded', function () use ( $container, $module_manager, $site_manager ) {

			if ( is_admin() ) {
				$this->run_admin_actions( $container, $module_manager, $site_manager );

				return;
			}

			$this->run_frontend_actions( $container );
		}, 100 );

		// Fires an action when both MLP and WP are loaded and site is active for MLP.
		add_action( 'wp_loaded', function () {

			/**
			 * Late loading event for MultilingualPress.
			 */
			do_action( 'mlp_and_wp_loaded' );
		}, 0 );

		// Cleanup upon blog delete.
		add_action( 'delete_blog', function ( $blog_id ) use ( $container ) {

			$this->delete_blog( $container, $blog_id );
		} );

		add_action( 'all_admin_notices', function () {

			if ( is_super_admin() && ! is_network_admin() ) {
				$this->user_errors_admin_notice();
			}
		} );
	}

	/**
	 * @param Container $container
	 */
	private function load_textdomain( Container $container ) {

		$properties = $container['multilingualpress.properties'];

		load_plugin_textdomain(
			'multilingual-press',
			false,
			dirname( $properties->plugin_base_name() ) . $properties->text_domain_path()
		);
	}

	/**
	 * @param Container $container
	 * @param int       $blog_id
	 */
	private function delete_blog( Container $container, $blog_id ) {

		global $wpdb;

		$link_table = $wpdb->base_prefix . 'multilingual_linked';

		// Delete relations.
		$site_relations = $container['multilingualpress.site_relations'];

		$site_relations->delete_relation( $blog_id );

		// Update site option.
		$blogs = (array) get_site_option( 'inpsyde_multilingual', [ ] );

		if ( isset( $blogs[ $blog_id ] ) ) {
			unset( $blogs[ $blog_id ] );

			update_site_option( 'inpsyde_multilingual', $blogs );
		}

		// Clean up linked elements table.
		$sql = $wpdb->prepare(
			"DELETE FROM {$link_table} WHERE ml_source_blogid = %d OR ml_blogid = %d",
			$blog_id,
			$blog_id
		);
		$wpdb->query( $sql );
	}

	/**
	 * Checks for errors and displays an admin notice if any error happen.
	 */
	private function user_errors_admin_notice() {

		if ( ! $this->check_for_errors() ) {
			return;
		}
		?>
		<div class="error">
			<p>
				<?php
				_e(
					'You did not setup any site relationships. You have to setup these first to use MultilingualPress. Please go to Network Admin &raquo; Sites &raquo; and choose a site to edit. Then go to the tab MultilingualPress and set up the relationships.',
					'multilingual-press'
				);
				?>
			</p>
		</div>
		<?php
	}

	/**
	 * Checks for errors
	 *
	 * @return bool
	 */
	private function check_for_errors() {

		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			return false;
		}

		return ( 1 > count( get_site_option( 'inpsyde_multilingual', [ ] ) ) );
	}

	/**
	 * @param Container                     $container
	 * @param \Mlp_Module_Manager_Interface $module_manager
	 * @param \Mlp_Module_Manager_Interface $site_manager
	 */
	private function run_admin_actions(
		Container $container,
		\Mlp_Module_Manager_Interface $module_manager,
		\Mlp_Module_Manager_Interface $site_manager
	) {

		if ( $module_manager->has_modules() ) {
			$this->load_module_settings_page( $container, $module_manager );
		}

		if ( $site_manager->has_modules() ) {
			$this->load_site_settings_page( $container, $site_manager );
		}

		$languages = $container['multilingualpress.languages'];

		$site_relations = $container['multilingualpress.site_relations'];

		// TODO: Constructors should not be self-firing.
		new \Mlp_Network_Site_Settings_Controller( $languages, $site_relations );
		new \Mlp_Network_New_Site_Controller( $languages, $site_relations );
	}

	/**
	 * @param Container $container
	 */
	private function run_frontend_actions( Container $container ) {

		// Use correct language for html element
		add_filter( 'language_attributes', [ $this, 'language_attributes' ] );

		$translations = new FrontEnd\AlternateLanguages\UnfilteredTranslations(
			$container['multilingualpress.languages']
		);

		add_action( 'template_redirect', function () use ( $translations ) {

			( new FrontEnd\AlternateLanguages\HTTPHeaders( $translations ) )->send();
		} );

		add_action( 'wp_head', function () use ( $translations ) {

			( new FrontEnd\AlternateLanguages\HTMLLinkTags( $translations ) )->render();
		} );
	}

	/**
	 * @param Container                     $container
	 * @param \Mlp_Module_Manager_Interface $module_manager
	 */
	private function load_module_settings_page( Container $container, \Mlp_Module_Manager_Interface $module_manager ) {

		add_action( 'plugins_loaded', function () use ( $container, $module_manager ) {

			( new \Mlp_General_SettingsPage(
				$module_manager,
				$container['multilingualpress.assets']
			) )->setup();
		}, 8 );

		$properties = $container['multilingualpress.properties'];

		$hook = 'network_admin_plugin_action_links_' . $properties->plugin_base_name();

		add_filter( $hook, function ( array $links ) {

			( new \Mlp_Network_Plugin_Action_Link( [
				'settings' => sprintf(
					'<a href="%s">%s</a>',
					esc_url( network_admin_url( 'settings.php?page=mlp' ) ),
					esc_html__( 'Settings', 'multilingual-press' )
				),
			] ) )->add( $links );
		} );
	}

	/**
	 * @param Container                     $container
	 * @param \Mlp_Module_Manager_Interface $site_manager
	 */
	private function load_site_settings_page( Container $container, \Mlp_Module_Manager_Interface $site_manager ) {

		add_action( 'plugins_loaded', function () use ( $container, $site_manager ) {

			( new \Mlp_General_SettingsPage(
				$site_manager,
				$container['multilingualpress.assets']
			) )->setup();
		}, 8 );
	}
}
