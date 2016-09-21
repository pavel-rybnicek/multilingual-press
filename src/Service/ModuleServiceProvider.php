<?php # -*- coding: utf-8 -*-

namespace Inpsyde\MultilingualPress\Service;

/**
 * @author  Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 * @package multilingual-press
 * @since   3.0.0
 */
interface ModuleServiceProvider extends BootableServiceProvider {

	/**
	 * @return string
	 */
	public function provided_module();

	/**
	 * @param \Mlp_Module_Manager $module_manager
	 * @param Container           $container
	 *
	 * @return bool
	 */
	public function setup_module( \Mlp_Module_Manager $module_manager, Container $container );

}