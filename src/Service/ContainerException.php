<?php # -*- coding: utf-8 -*-

namespace Inpsyde\MultilingualPress\Service;

/**
 * @author  Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 * @package multilingual-press
 * @since   3.0.0
 */
class ContainerException extends \Exception {

	const SERVICE_NOT_FOUND = 1;
	const LOCKED_CONTAINER = 2;
	const BOOTSTRAPPED_CONTAINER = 3;

	/**
	 * @param  string $service
	 * @param string  $method
	 *
	 * @return ContainerException
	 */
	public static function service_not_found( $service, $method = 'get' ) {

		$message = "Service '%s' cannot be %s because not registered in the container.";

		return new static( sprintf( $message, $service, $method ), self::SERVICE_NOT_FOUND );
	}

	/**
	 * @param  string $service
	 * @param string  $method
	 *
	 * @return ContainerException
	 */
	public static function locked_container( $service, $method = 'set' ) {

		$message = "Service '%s' cannot be %s because container is locked.";

		return new static( sprintf( $message, $service, $method ), self::LOCKED_CONTAINER );

	}

	/**
	 * @param  string $service
	 * @param string  $method
	 *
	 * @return ContainerException
	 */
	public static function bootstrapped_container( $service, $method = 'get' ) {

		$message = "Service '%s' cannot be %s because container is locked and it is not shared.";

		return new static( sprintf( $message, $service, $method ), self::BOOTSTRAPPED_CONTAINER );

	}

}