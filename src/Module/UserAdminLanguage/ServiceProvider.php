<?php # -*- coding: utf-8 -*-

namespace Inpsyde\MultilingualPress\Module\UserAdminLanguage;

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

	const MODULE = 'user_admin_language';

	/**
	 * @inheritdoc
	 */
	public function provide( Container $container ) {

		$container[ 'mlp.module.user_admin_language' ] = function () {

			new \Mlp_User_Backend_Language();

		};

		return TRUE;
	}

	/**
	 * @inheritdoc
	 */
	public function boot( Container $container ) {

		is_admin() and $this->on_activation(
			function () use ( $container ) {

				/** @var \Mlp_User_Backend_Language $user_admin_language */
				$user_admin_language = $container[ 'mlp.module.user_admin_language' ];

				// Load user specific language in the backend
				add_filter( 'locale', [ $user_admin_language, 'locale' ] );

				// Add User Field for own blog language
				add_filter( 'personal_options', [ $user_admin_language, 'edit_user_profile' ] );
				add_filter( 'profile_update', [ $user_admin_language, 'profile_update' ] );

				add_action( 'admin_head-options-general.php', [ $user_admin_language, 'enqueue_script' ] );
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
	public function register_module( \Mlp_Module_Manager $module_manager, Container $container ) {

		return $module_manager->register(
			[
				'display_name' => __( 'User Backend Language', 'multilingual-press' ),
				'slug'         => 'module-' . self::MODULE,
				'description'  => __(
					'Let each user choose a preferred language for the backend of all connected sites. Does not affect the frontend.',
					'multilingual-press'
				),
			]
		);
	}
}