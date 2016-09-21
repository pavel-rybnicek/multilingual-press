<?php # -*- coding: utf-8 -*-

namespace Inpsyde\MultilingualPress\Service;

/**
 * @author  Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 * @package multilingual-press
 * @since   3.0.0
 */
final class Container implements \ArrayAccess {

	const STATUS_IDLE = 0;
	const STATUS_UNLOCKED = 1;
	const STATUS_LOCKED = 2;
	const STATUS_BOOTSTRAPPED = 4;

	/**
	 * Holds the bitmask of current container status built using class status flags
	 *
	 * @var int
	 */
	private $status = self::STATUS_IDLE;

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
	 * Returns current status as bitmask of class status flags.
	 *
	 * @return int
	 */
	public function status() {

		return $this->status;
	}

	/**
	 * Sets container status to locked.
	 *
	 * @throws \BadMethodCallException if called when the container is in any state but unlocked.
	 */
	public function lock() {

		if ( $this->status() !== self::STATUS_UNLOCKED ) {
			throw new \BadMethodCallException(
				sprintf( '%s can be marked as locked only when already in "unlocked" status.', __CLASS__ )
			);
		}

		$this->status = self::STATUS_LOCKED;
	}

	/**
	 * Sets container status to bootstrapped.
	 *
	 * @throws \BadMethodCallException if called when the container is in any state but locked.
	 */
	public function bootstrap() {

		if ( $this->status() !== self::STATUS_LOCKED ) {
			throw new \BadMethodCallException(
				sprintf( '%s can be marked as bootstrapped only when already locked.', __CLASS__ )
			);
		}

		// When container is bootstrapped it is also locked
		$this->status |= self::STATUS_BOOTSTRAPPED;
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
		if ( ! array_key_exists( $offset, $this->shared ) && ( $this->status() & self::STATUS_BOOTSTRAPPED ) ) {
			throw ContainerException::bootstrapped_container( $offset, 'get' );
		}

		if ( $is_value ) {
			return $this->values[ $offset ];
		}

		$factory                 = $this->factories[ $offset ];
		$this->values[ $offset ] = $factory( $this );

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

		$status = $this->status();

		if ( $status & self::STATUS_LOCKED ) {
			throw ContainerException::locked_container( $offset, 'set' );
		}

		// Move status to unlocked when called first time
		( $status === self::STATUS_IDLE) and $this->status = self::STATUS_UNLOCKED;

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

		if ( ! $this->offsetExists( $offset ) ) {
			throw ContainerException::service_not_found( $offset, 'unset' );
		}

		if ( $this->status() & self::STATUS_LOCKED ) {
			throw ContainerException::locked_container( $offset, 'unset' );
		}

		if ( $this->status() & self::STATUS_BOOTSTRAPPED ) {
			throw ContainerException::bootstrapped_container( $offset, 'unset' );
		}

		$is_factory = array_key_exists( $offset, $this->factories );
		$is_value   = array_key_exists( $offset, $this->values );

		// An already resolved object is something that, very likely, was injected in some other object.
		// If we would allow to unset it we could produce some very ugly things.
		if ( $is_factory && $is_value ) {
			throw ServiceLockedException::for_service( $offset, 'unset' );
		}

		if ( $is_factory ) {
			unset( $this->factories[ $offset ] );
		}

		if ( $is_value ) {
			unset( $this->values[ $offset ] );
		}
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
	 * The new factory callback will receive two arguments:
	 * the result of old factory (so the object) as first argument, and the container instance as second.
	 *
	 * @param string   $offset
	 * @param callable $new_factory
	 *
	 * @throws ContainerException
	 * @throws ServiceLockedException
	 */
	public function extend( $offset, callable $new_factory ) {

		$status = $this->status();

		if ( $status & self::STATUS_LOCKED ) {
			throw ContainerException::locked_container( $offset, 'extend' );
		}

		if ( ! array_key_exists( $offset, $this->factories ) ) {
			throw ContainerException::service_not_found( $offset, 'extend' );
		}

		if ( array_key_exists( $offset, $this->values ) ) {
			throw ServiceLockedException::for_service( $offset, 'extend' );
		}

		$old_factory                = $this->factories[ $offset ];
		$this->factories[ $offset ] = function ( Container $container ) use ( $new_factory, $old_factory ) {

			return $new_factory( $old_factory( $container ), $container );
		};

	}
}