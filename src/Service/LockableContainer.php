<?php # -*- coding: utf-8 -*-

namespace Inpsyde\MultilingualPress\Service;

/**
 * Interface for all lockable service container implementations to be used for dependency management.
 *
 * @package Inpsyde\MultilingualPress\Service
 * @since   3.0.0
 */
interface LockableContainer extends Container {

	/**
	 * Locks the container.
	 *
	 * @since 3.0.0
	 *
	 * @return void
	 */
	public function lock();
}
