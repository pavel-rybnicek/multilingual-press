<?php # -*- coding: utf-8 -*-

namespace Inpsyde\MultilingualPress\Service;

/**
 * @author  Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 * @package multilingual-press
 * @since   3.0.0
 */
final class Container implements \ArrayAccess {

	/**
	 * Holds the bitmask of current container status built using class status flags
	 *
	 * @var int
	 */
	private $status = 0;

	/**
	 * Storage for already factored object or for things that not to be factored (scalars, array and objects)
	 *
	 * @var array
	 */
	private $values = [];

	/**
	 * Storage for object factories (callbacks)
	 *
	 * @var callable[]
	 */
	private $factories = [];

	/**
	 * Array of ids of factories that are set to be shared or for scalar values.
	 * This object can always get from container, even after it has been bootstrapped.
	 *
	 * @var string[]
	 */
	private $shared = [];

	/**
	 * @param array $values
	 */
	public function __construct( array $values = [] ) {

		( $values && ! $this->values ) and $this->values = $values;
	}

	/**
	 * Sets container status to locked.
	 */
	public function lock() {

		$this->status = 1;
	}

	/**
	 * Sets container status to bootstrapped.
	 */
	public function bootstrap() {

		$this->status = 2;
	}

	/**
	 * Checks if given offset is available in the container either as factory or as resolved value.
	 *
	 * @param string $offset
	 *
	 * @return bool
	 */
	public function offsetExists( $offset ) {

		if ( ! is_string( $offset ) ) {
			throw new \InvalidArgumentException( sprintf( '%s require service id in a string.', __METHOD__ ) );
		}

		return array_key_exists( $offset, $this->factories ) || array_key_exists( $offset, $this->values );
	}

	/**
	 * Returns a value from the container.
	 *
	 * Calling with not registered id will throw an exception.
	 * After the container has been bootstrapped, only scalar values or shared values can be retrieved.
	 *
	 * @param string $offset
	 *
	 * @return mixed
	 * @throws ContainerException
	 */
	public function offsetGet( $offset ) {

		if ( ! $this->offsetExists( $offset ) ) {
			throw ContainerException::service_not_found( $offset, 'get' );
		}

		$is_value = array_key_exists( $offset, $this->values );

		// Only shared values are accessible after the container has been bootstrapped
		if ( ! array_key_exists( $offset, $this->shared ) && $this->status > 1 ) {
			throw ContainerException::bootstrapped_container( $offset, 'get' );
		}

		if ( $is_value ) {
			return $this->values[ $offset ];
		}

		$factory                 = $this->factories[ $offset ];
		$this->values[ $offset ] = $factory( $this );

		// If the container is locked, we don't need anymore the factory we just used, because no one could access it
		// nor in read nor in write mode, so we can free some memory unsetting it.
		if ( $this->status > 0 ) {
			unset( $this->factories[ $offset ] );
		}

		return $this->values[ $offset ];
	}

	/**
	 * Sets a value or a factory callback in the container.
	 * Every callable passed as value is assumed to be a factory callback.
	 *
	 * @param string $offset
	 * @param mixed  $value
	 *
	 * @throws ContainerException
	 */
	public function offsetSet( $offset, $value ) {

		if ( $this->status > 0 ) {
			throw ContainerException::locked_container( $offset, 'set' );
		}

		if ( ! is_callable( $value ) ) {
			// Scalar values are always shared
			( is_scalar( $value ) && ! array_key_exists( $offset, $this->shared ) ) and $this->shared[] = $offset;
			$this->values[ $offset ] = $value;

			return;
		}

		$this->factories[ $offset ] = $value;
	}

	/**
	 * Removes a value or a factory callback from the container.
	 *
	 * @param string $offset
	 *
	 * @throws ContainerException
	 * @throws ServiceLockedException
	 */
	public function offsetUnset( $offset ) {

		throw ContainerException::unset_disabled();
	}

	/**
	 * Stores a value or a factory in the container, and mark it to be shared, so retrievable after container
	 * is in bootstrapped status.
	 *
	 * @param string $offset
	 * @param mixed  $value
	 */
	public function share( $offset, $value ) {

		$this->shared[] = $offset;

		$this->offsetSet( $offset, $value );
	}

	/**
	 * Changes the factory of an object that was already registered with another factory callback.
	 *
	 * @param string   $offset
	 * @param callable $new_factory
	 *
	 * @throws ContainerException
	 * @throws ServiceLockedException
	 */
	public function extend( $offset, callable $new_factory ) {

		if ( $this->status > 0 ) {
			throw ContainerException::locked_container( $offset, 'extend' );
		}

		if ( ! array_key_exists( $offset, $this->factories ) ) {
			throw ContainerException::service_not_found( $offset, 'extend' );
		}

		/**
		 * If the key is present in factories *and* in values, it means it was already resolved so we can't allow
		 * to replace it otherwise we should also replace the "factored" value, which would break stuff.
		 */
		if ( array_key_exists( $offset, $this->values ) ) {
			throw ServiceLockedException::for_service( $offset, 'extend' );
		}

		$old_factory = $this->factories[ $offset ];

		$this->factories[ $offset ] = function ( Container $container ) use ( $new_factory, $old_factory ) {

			/**
			 * The new factory receives
			 * as 1st argument the object "factored" by the old factory and as 2nd argument the container.
			 */

			return $new_factory( $old_factory( $container ), $container );
		};

	}
}