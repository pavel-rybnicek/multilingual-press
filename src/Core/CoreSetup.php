<?php # -*- coding: utf-8 -*-

namespace Inpsyde\MultilingualPress\Core;

use Inpsyde\MultilingualPress\MultilingualPress;
use Inpsyde\MultilingualPress\Service\BootableServiceProvider;
use Inpsyde\MultilingualPress\Service\Container;
use Inpsyde\MultilingualPress\Service\ContainerException;

/**
 * Service provider for internal locations object.
 *
 * @package Inpsyde\MultilingualPress\Assets
 * @since   3.0.0
 */
class CoreSetup {

	/**
	 * @inheritdoc
	 */
	public function setup( Container $container ) {

		if ( MultilingualPress::is_active_site() ) {
			return FALSE;
		}

		// Load text domain when loaded
		add_action(
			'inpsyde_mlp_loaded',
			function () use ( $container ) {

				$this->load_textdomain( $container );
			},
			1
		);

		// Fires
		add_action(
			'wp_loaded',
			function () {

				/**
				 * Late loading event for MultilingualPress.
				 */
				do_action( 'mlp_and_wp_loaded' );
			},
			0
		);

		// Cleanup upon blog delete
		add_filter(
			'delete_blog',
			function ( $blog_id ) use ( $container ) {

				$this->delete_blog( $container, $blog_id );
			}
		);

		// Check for errors and show notice
		add_filter(
			'all_admin_notices',
			function () {

				$this->user_errors_admin_notice();
			}
		);

		add_action(
			'inpsyde_mlp_loaded',
			function () use ( $container ) {

				is_admin() ? $this->run_admin_actions( $container ) : $this->run_frontend_actions( $container );
			},
			100
		);

		return TRUE;
	}

	/**
	 * @param Container $container
	 */
	private function load_textdomain( Container $container ) {

		/** @var Properties $properties */
		$properties = $container[ 'mlp.properties' ];
		$rel_path   = dirname( $properties->plugin_base_name() ) . $properties->text_domain_path();

		load_plugin_textdomain( 'multilingual-press', FALSE, $rel_path );
	}

	/**
	 * @param Container $container
	 */
	private function delete_blog( Container $container, $blog_id ) {

		global $wpdb;
		$link_table = $wpdb->base_prefix . 'multilingual_linked';

		// Delete relations
		$site_relations = $container[ 'mlp.site_relations' ];
		$site_relations->delete_relation( $blog_id );

		// Update site option
		$blogs = (array) get_site_option( 'inpsyde_multilingual', [] );
		if ( isset( $blogs[ $blog_id ] ) ) {
			unset( $blogs[ $blog_id ] );
			update_site_option( 'inpsyde_multilingual', $blogs );
		}

		// Clean up linked elements table
		$sql = "DELETE FROM {$link_table} WHERE ml_source_blogid = %d OR ml_blogid = %d";
		$sql = $wpdb->prepare( $sql, $blog_id, $blog_id );
		$wpdb->query( $sql );
	}

	/**
	 * Checks for errors
	 *
	 * @access    public
	 * @since     0.9
	 * @uses
	 * @return    void
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
					'You didn\'t setup any site relationships. You have to setup these first to use MultilingualPress. Please go to Network Admin &raquo; Sites &raquo; and choose a site to edit. Then go to the tab MultilingualPress and set up the relationships.',
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
	 * @return    boolean
	 */
	private function check_for_errors() {

		if ( defined( 'DOING_AJAX' ) || is_network_admin() ) {
			return FALSE;
		}

		// Get blogs related to the current blog
		$all_blogs = get_site_option( 'inpsyde_multilingual', [] );

		return ( 1 > count( $all_blogs ) ) && is_super_admin();
	}

	/**
	 * @param Container $container
	 */
	private function run_admin_actions( Container $container ) {

		$module_manager = $container[ 'mlp.module_manager' ];
		$site_manager   = $container[ 'mlp.site_manager' ];

		$module_manager->has_modules() and $this->load_module_settings_page( $container );
		$site_manager->has_modules() and $this->load_site_settings_page( $container );

		new \Mlp_Network_Site_Settings_Controller( $container[ 'mlp.api' ], $container[ 'mlp.site_relations' ] );
		new \Mlp_Network_New_Site_Controller( $container[ 'mlp.api' ], $container[ 'mlp.site_relations' ] );
	}

	/**
	 * @param Container $container
	 */
	private function run_frontend_actions( Container $container ) {

		// Use correct language for html element
		add_filter( 'language_attributes', [ $this, 'language_attributes' ] );

		$translations = new FrontEnd\AlternateLanguages\UnfilteredTranslations( $container[ 'mlp.api' ] );

		add_action(
			'template_redirect',
			function () use ( $translations ) {

				( new FrontEnd\AlternateLanguages\HTTPHeaders( $translations ) )->send();
			}
		);

		add_action(
			'wp_head',
			function () use ( $translations ) {

				( new FrontEnd\AlternateLanguages\HTMLLinkTags( $translations ) )->render();
			}
		);
	}

	/**
	 * Create network settings page.
	 *
	 * @param Container $container
	 */
	private function load_module_settings_page( Container $container ) {

		$settings = new \Mlp_General_Settingspage( $container[ 'mlp.module_manager' ], $container[ 'mlp.assets' ] );
		add_action( 'plugins_loaded', [ $settings, 'setup' ], 8 );

		/** @var Properties $properties */
		$properties  = $container[ 'mlp.properties' ];
		$plugin_file = $properties->plugin_base_name();
		$url         = network_admin_url( 'settings.php?page=mlp' );
		$action_link = new \Mlp_Network_Plugin_Action_Link(
			[
				'settings' => '<a href="' . esc_url( $url ) . '">' . __( 'Settings', 'multilingual-press' ) . '</a>',
			]
		);

		add_filter( "network_admin_plugin_action_links_{$plugin_file}", [ $action_link, 'add' ] );
	}

	/**
	 * Create site settings page.
	 *
	 * @param Container $container
	 */
	private function load_site_settings_page( Container $container ) {

		$settings = new \Mlp_General_Settingspage( $container[ 'mlp.module_manager' ], $container[ 'mlp.assets' ] );
		$settings->setup();

		add_action( 'plugins_loaded', [ $settings, 'setup' ], 8 );
	}
}