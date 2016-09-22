<?php # -*- coding: utf-8 -*-

namespace Inpsyde\MultilingualPress\Module\UserAdminLanguage;

use Inpsyde\MultilingualPress\Module\ActivationAwareness;
use Inpsyde\MultilingualPress\Module\ModuleServiceProvider;
use Inpsyde\MultilingualPress\Service\Container;

/**
 * Service provider for the User Admin Language module.
 *
 * @package Inpsyde\MultilingualPress\Module\UserAdminLanguage
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

		$this->module = 'user_admin_language';
	}

	/**
	 * Registers the provided services on the given container.
	 *
	 * @since 3.0.0
	 *
	 * @param Container $container Container object.
	 */
	public function register( Container $container ) {

		$container['multilingualpress.module.user_admin_language'] = function () {

			return new \Mlp_User_Backend_Language();
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

		$user_admin_language = $container['multilingualpress.module.user_admin_language'];

		$this->on_activation( function () use ( $user_admin_language ) {

			add_filter( 'locale', [ $user_admin_language, 'locale' ] );
			add_filter( 'personal_options', [ $user_admin_language, 'edit_user_profile' ] );
			add_filter( 'profile_update', [ $user_admin_language, 'profile_update' ] );

			add_action( 'admin_head-options-general.php', [ $user_admin_language, 'enqueue_script' ] );
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
				'Let each user choose a preferred language for the backend of all connected sites. Does not affect the frontend.',
				'multilingual-press'
			),
			'display_name' => __( 'User Backend Language', 'multilingual-press' ),
			'slug'         => "module-{$this->module}",
		] );
	}
}
