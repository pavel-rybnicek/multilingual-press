<?php # -*- coding: utf-8 -*-

namespace Inpsyde\MultilingualPress\Module;

/**
 * Trait for all module service provider implementations that need to be aware of their individual module's activation.
 *
 * @package Inpsyde\MultilingualPress\Module
 * @since   3.0.0
 */
trait ActivationAwareness {

	/**
	 * @var string
	 */
	private $module;

	/**
	 * Returns the module name.
	 *
	 * @since 3.0.0
	 *
	 * @return string Module name.
	 */
	public function module() {

		return (string) $this->module;
	}

	/**
	 * Registers the given callback to be executed on the activation of this service provider's module.
	 *
	 * @param callable $callback Callback to be executed on module activation.
	 */
	private function on_activation( callable $callback ) {

		add_action( \Mlp_Module_Manager_Interface::MODULE_ACTIVATION_ACTION_PREFIX . $this->module, $callback );
	}
}
