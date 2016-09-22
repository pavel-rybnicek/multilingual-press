<?php # -*- coding: utf-8 -*-

namespace Inpsyde\MultilingualPress\Service;

/**
 * Interface for all bootstrappable service container implementations to be used for dependency management.
 *
 * @package Inpsyde\MultilingualPress\Service
 * @since   3.0.0
 */
interface BootstrappableContainer extends LockableContainer {

	/**
	 * Bootstraps (and locks) the container.
	 *
	 * @since 3.0.0
	 *
	 * @return void
	 */
	public function bootstrap();

	/**
	 * Stores the given value or factory callback with the given name, and defines it to be accessible even after the
	 * container has been bootstrapped.
	 *
	 * @since 3.0.0
	 *
	 * @param string $name  The name of a value or factory callback.
	 * @param mixed  $value The value or factory callback.
	 *
	 * @return void
	 */
	public function share( $name, $value );
}
