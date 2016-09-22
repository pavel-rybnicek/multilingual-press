<?php # -*- coding: utf-8 -*-

namespace Inpsyde\MultilingualPress\Assets;

/**
 * Asset URL data type implementation aware of debug mode and thus potentially minified asset files.
 *
 * @package Inpsyde\MultilingualPress\Assets
 * @since   3.0.0
 */
final class DebugAwareURL implements URL {

	/**
	 * @var string
	 */
	private $url = '';

	/**
	 * @var string
	 */
	private $version = '';

	/**
	 * Constructor. Sets up the properties.
	 *
	 * @since 3.0.0
	 *
	 * @param string $file     File name (e.g., admin.css).
	 * @param string $dir_path Local path to the directory containing the file.
	 * @param string $dir_url  Public URL for the directory containing the file.
	 */
	public function __construct( $file, $dir_path, $dir_url ) {

		$dir_path = rtrim( $dir_path, '/' );

		$file = $this->get_file( $file, $dir_path );

		$file_path = "$dir_path/$file";
		if ( is_readable( $file_path ) ) {
			$this->url = rtrim( $dir_url, '/' ) . "/$file";

			$this->version = filemtime( $file_path );
		}
	}

	/**
	 * Returns a new asset URL object according to the given arguments.
	 *
	 * @since 3.0.0
	 *
	 * @param string $file     File name (e.g., admin.css).
	 * @param string $dir_path Local path to the directory containing the file.
	 * @param string $dir_url  Public URL of the directory containing the file.
	 *
	 * @return static Asset URL object.
	 */
	public static function create( $file, $dir_path, $dir_url ) {

		return new static( $file, $dir_path, $dir_url );
	}

	/**
	 * Returns the URL string.
	 *
	 * @since 3.0.0
	 *
	 * @return string URL string.
	 */
	public function __toString() {

		return $this->url;
	}

	/**
	 * Returns the file version.
	 *
	 * @since 3.0.0
	 *
	 * @return string File version.
	 */
	public function version() {

		return $this->version;
	}

	/**
	 * Returns the name of the minified version of the given file if it exists and not debugging, otherwise the
	 * unmodified file.
	 *
	 * @param string $file     File name (e.g., admin.css).
	 * @param string $dir_path Local path to the directory containing the file.
	 *
	 * @return string Name of the minified or unmodified file, depending on debugging settings.
	 */
	private function get_file( $file, $dir_path ) {

		if ( \Inpsyde\MultilingualPress\is_script_debug_mode() ) {
			return $file;
		}

		$minified_file = $this->get_minified_file( $file );

		if ( $minified_file === $file ) {
			return $file;
		}

		if ( is_readable( "$dir_path/$minified_file" ) ) {
			return $minified_file;
		};

		return $file;
	}

	/**
	 * Returns the given file with inserted ".min" infix, if not already minified file.
	 *
	 * @param string $file Normal file name (e.g., admin.css).
	 *
	 * @return string Minified file.
	 */
	private function get_minified_file( $file ) {

		// Check for already minified file.
		if ( preg_match( '~\.min\.[^.]+$~', $file ) ) {
			return $file;
		}

		return preg_replace( '~\.[^.]+$~', '.min$0', $file );
	}
}
