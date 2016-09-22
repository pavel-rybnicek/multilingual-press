<?php # -*- coding: utf-8 -*-

namespace Inpsyde\MultilingualPress\Module;

use Inpsyde\MultilingualPress\Service\Container;
use Inpsyde\MultilingualPress\Service\BootstrappableServiceProvider;

/**
 * Interface for all module service provider implementations to be used for dependency management.
 *
 * @package Inpsyde\MultilingualPress\Module
 * @since   3.0.0
 */
interface ModuleServiceProvider extends BootstrappableServiceProvider {

	/**
	 * Returns the module name.
	 *
	 * @since 3.0.0
	 *
	 * @return string Module name.
	 */
	public function module();

	// TODO: register_module() should NOT get passed the container - currently this is necessary, though. Refactor!

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
	public function register_module( \Mlp_Module_Manager_Interface $module_manager, Container $container );
}
