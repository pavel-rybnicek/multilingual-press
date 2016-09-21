<?php # -*- coding: utf-8 -*-

namespace Inpsyde\MultilingualPress\Core;

/**
 * @package Inpsyde\MultilingualPress\Assets
 * @since   3.0.0
 */
class Properties implements \ArrayAccess {

	/**
	 * @var bool
	 */
	private $locked = FALSE;

	/**
	 * @var array
	 */
	private $storage = [];

	/**
	 * @param string $plugin_file_path
	 */
	public function __construct( $plugin_file_path ) {

		if ( ! $this->storage ) {
			$base = [
				'plugin_file_path' => $plugin_file_path,
				'plugin_dir_url'   => plugins_url( '/', $plugin_file_path ),
				'plugin_base_name' => plugin_basename( $plugin_file_path ),
			];

			$headers = get_file_data(
				$plugin_file_path,
				[
					'text_domain_path' => 'Domain Path',
					'homepage'         => 'Plugin URI',
					'plugin_name'      => 'Plugin Name',
					'version'          => 'Version',
				]
			);

			$this->storage = array_merge( $base, $headers );
		}
	}

	/**
	 * Returns the absolute path of main plugin file.
	 *
	 * @return string
	 */
	public function plugin_file_path() {

		return $this->offsetGet( __FUNCTION__ );
	}

	/**
	 * Returns the URL of the plugin root folder.
	 *
	 * @return string
	 */
	public function plugin_dir_url() {

		return $this->offsetGet( __FUNCTION__ );
	}

	/**
	 * Returns the basename of the plugin.
	 *
	 * @return string
	 */
	public function plugin_base_name() {

		return $this->offsetGet( __FUNCTION__ );
	}

	/**
	 * Returns the plugin name as written in plugin headers.
	 *
	 * @return string
	 */
	public function plugin_name() {

		return $this->offsetGet( __FUNCTION__ );
	}

	/**
	 * Returns the homepage of the plugin.
	 *
	 * @return string
	 */
	public function homepage() {

		return $this->offsetGet( __FUNCTION__ );
	}

	/**
	 * Returns the plugin version.
	 *
	 * @return string
	 */
	public function version() {

		return $this->offsetGet( __FUNCTION__ );
	}

	/**
	 * Returns the absolute path of the plugin text domain.
	 *
	 * @return string
	 */
	public function text_domain_path() {

		return $this->offsetGet( __FUNCTION__ );
	}

	/**
	 * Locks the instance to avoid any possible further editing.
	 */
	public function lock() {

		$this->locked = TRUE;
	}

	/**
	 * Check if a property exists.
	 *
	 * @param mixed $offset
	 *
	 * @return bool
	 *
	 * @throws \BadMethodCallException If offset is not in a string.
	 */
	public function offsetExists( $offset ) {

		if ( ! is_string( $offset ) ) {
			throw new \BadMethodCallException(
				sprintf( '%s expects property name as string.', __METHOD__ )
			);
		}

		return array_key_exists( $offset, $this->storage );
	}

	/**
	 * Retrieve a property by name. Return null for property that are not set.
	 *
	 * @param string $offset
	 *
	 * @return mixed
	 */
	public function offsetGet( $offset ) {

		return $this->offsetExists( $offset ) ? $this->storage[ $offset ] : NULL;
	}

	/**
	 * Set a property by name.
	 *
	 * @param string $offset
	 * @param mixed  $value
	 *
	 * @throws \BadMethodCallException If either container is locked or property name is already used.
	 */
	public function offsetSet( $offset, $value ) {

		if ( $this->locked ) {
			throw new \BadMethodCallException(
				sprintf( '%s can not be called on locked Properties class.', __METHOD__ )
			);
		}

		if ( $this->offsetExists( $offset ) ) {
			throw new \BadMethodCallException( sprintf( "Can't override '%s' property.", $offset ) );
		}

		$this->storage[ $offset ] = $value;
	}

	/**
	 * Disabled. Always throws exception if used.
	 *
	 * @param string $offset
	 *
	 * @throws \BadMethodCallException
	 */
	public function offsetUnset( $offset ) {

		throw new \BadMethodCallException( 'It is not possible to unset plugin properties.' );
	}
}