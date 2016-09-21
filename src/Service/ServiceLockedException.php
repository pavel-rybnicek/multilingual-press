<?php # -*- coding: utf-8 -*-

namespace Inpsyde\MultilingualPress\Service;

/**
 * @author  Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 * @package multilingual-press
 * @since   3.0.0
 */
class ServiceLockedException extends \Exception {

	public static function for_service( $service, $method = 'extend' ) {

		return new static( sprintf( "Can't %s '%s' because it was already resolved.", $method, $service ) );
	}

}