<?php # -*- coding: utf-8 -*-

namespace Inpsyde\MultilingualPress\Service;

/**
 * @author  Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 * @package multilingual-press
 * @since   3.0.0
 */
trait ModuleServiceProviderOnActivationTrait {

	private function on_activation( callable $callable ) {

		/** @var ModuleServiceProvider $this */
		$module = $this->provided_module();

		add_action( "inpsyde_module_{$module}_activated", $callable );
	}

}