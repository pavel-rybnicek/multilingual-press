<?php # -*- coding: utf-8 -*-

namespace Inpsyde\MultilingualPress\Service;

/**
 * @author  Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 * @package multilingual-press
 * @since   3.0.0
 */
interface BootableServiceProvider extends ServiceProvider {

	/**
	 * @param Container $container
	 *
	 * @return bool
	 * @throws ContainerException
	 */
	public function boot( Container $container );
}