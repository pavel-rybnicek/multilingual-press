<?php # -*- coding: utf-8 -*-

namespace Inpsyde\MultilingualPress;

use Inpsyde\MultilingualPress\Service\BootableServiceProvider;
use Inpsyde\MultilingualPress\Service\Container;
use Inpsyde\MultilingualPress\Service\ContainerException;
use Inpsyde\MultilingualPress\Service\ModuleServiceProvider;
use Inpsyde\MultilingualPress\Service\ServiceProvider;

/**
 * Kind of a front controller.
 *
 * @author  Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 * @since   3.0.0
 */
class MultilingualPress {

	/**
	 * @var Container
	 */
	private static $container_instance;

	/**
	 * @var bool
	 */
	private static $is_active_site;

	/**
	 * @var Container
	 */
	private $container;

	/**
	 * @var BootableServiceProvider[]
	 */
	private $bootable = [];

	/**
	 * @var ModuleServiceProvider[]
	 */
	private $modules = [];

	/**
	 * @var bool
	 */
	private $bootstrapped = FALSE;

	/**
	 * Resolve a shared element in the container.
	 *
	 * @param string $name
	 *
	 * @return mixed
	 *
	 * @throws \BadMethodCallException If called too early
	 * @throws ContainerException If the requested service/value is not found or not shared
	 */
	public static function resolve( $name ) {

		if ( ! self::$container_instance instanceof Container ) {
			throw new \BadMethodCallException(
				sprintf( '%s can only be called after MultilingualPress has been initialised.', __METHOD__ )
			);
		}

		return self::$container_instance[ $name ];
	}

	/**
	 * @param Container $container
	 */
	public function __construct( Container $container ) {

		$this->container or $this->container = $container;
		self::$container_instance or self::$container_instance = $this->container;
	}

	/**
	 * Adds a provider to the stack.
	 *
	 * @param ServiceProvider $provider
	 *
	 * @return MultilingualPress
	 */
	public function add_service_provider( ServiceProvider $provider ) {

		$provider->provide( $this->container );
		$provider instanceof BootableServiceProvider and $this->bootable[] = $provider;
		$provider instanceof ModuleServiceProvider and $this->modules[] = $provider;

		return $this;
	}

	/**
	 * Bootstraps all bootable providers, lock the container and setup all the module providers.
	 *
	 * @return void
	 */
	public function bootstrap() {

		if ( $this->bootstrapped ) {
			throw new \BadMethodCallException(
				'It is not possible to bootstrap an already bootstrapped MultilingualPress instance.'
			);
		}

		// After this, container is read-only
		$this->container->lock();

		$is_active = $this->is_active_site();

		// Every bootable module is now booted.
		array_walk(
			$this->bootable,
			function ( BootableServiceProvider $provider, $index, $is_active ) {

				// In case site is not active, we skip the boot of modules
				$is_module = $provider instanceof ModuleServiceProvider;
				( $is_active || ! $is_module ) and $provider->boot( $this->container );
			},
			$is_active
		);

		unset( $this->bootable );

		// In case site is not active, there's nothing else to do
		if ( ! $is_active ) {
			$this->bootstrapped = TRUE;

			return;
		}

		// Let's retrieve the instance of module manager to setup modules
		$module_manager = $this->container[ 'mlp.modules_manager' ];
		// ...ensuring it is the proper interface
		if ( ! $module_manager instanceof \Mlp_Module_Manager_Interface ) {
			throw new \RuntimeException( 'It was not possible to resolve MultilingualPress module manager instance.' );
		}

		/**
		 * Runs before `inpsyde_mlp_loaded`.
		 * For things that needs to happen before 'inpsyde_mlp_loaded'.
		 */
		do_action( 'inpsyde_mlp_init' );

		/**
		 * Runs after everything in core has been loaded and booted.
		 */
		do_action( 'inpsyde_mlp_loaded' );

		// Register all modules
		$this->register_modules();

		// From this point on, only shared elements can be get from the container by using MultilingualPress::resolve()
		$this->container->bootstrap();

		// Ensure this method can not be called again
		$this->bootstrapped = TRUE;
	}

	/**
	 * Register all the modules.
	 */
	private function register_modules() {

		array_walk(
			$this->modules,
			function ( ModuleServiceProvider $provider, $index, \Mlp_Module_Manager $module_manager ) {

				if ( $provider->register_module( $module_manager, $this->container ) ) {

					$module = $provider->provided_module();

					/**
					 * Fires after a module has been setup in the module manager and it is enabled.
					 *
					 * TODO:
					 * Currently checking this action is fired is the only way a module object may know if it's enabled.
					 * we should probably introduce an interface for modules with a setter method that let us set
					 * the enabled status from here.
					 */
					do_action( "inpsyde_module_{$module}_activated" );
				}
			},
			$module_manager
		);

		unset( $this->modules );
	}

	/**
	 * Check if the current context needs more MultilingualPress actions.
	 *
	 * @return bool
	 */
	private function is_active_site() {

		if ( is_bool( self::$is_active_site ) ) {
			return self::$is_active_site;
		}

		global $pagenow;

		if ( in_array( $pagenow, [ 'admin-post.php', 'admin-ajax.php' ], TRUE ) || is_network_admin() ) {
			self::$is_active_site = TRUE;

			return TRUE;
		}

		$relations = get_site_option( 'inpsyde_multilingual', [] );

		self::$is_active_site = array_key_exists( get_current_blog_id(), $relations );

		return self::$is_active_site;
	}

}
