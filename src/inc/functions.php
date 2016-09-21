<?php # -*- coding: utf-8 -*-

namespace Inpsyde\MultilingualPress;

use Inpsyde\MultilingualPress\Common\Factory\Error;
use Inpsyde\MultilingualPress\Common\Type\Translation;

function get_default_content_id( $content_id = 0 ) {

	if ( 0 < (int) $content_id ) {
		return $content_id;
	}

	return get_queried_object_id();
}

/**
 * Wrapper for the exit language construct.
 *
 * Introduced to allow for easy unit testing.
 *
 * @param int|string $status Exit status.
 *
 * @return void
 */
function exit_now( $status = '' ) {

	exit( $status );
}

/**
 * Checks if MultilingualPress debug mode is on.
 *
 * @return bool Whether or not MultilingualPress debug mode is on.
 */
function is_debug_mode() {

	return ( defined( 'MULTILINGUALPRESS_DEBUG' ) && MULTILINGUALPRESS_DEBUG );
}

/**
 * Checks if either MultilingualPress or WordPress debug mode is on.
 *
 * @return bool Whether or not MultilingualPress or WordPress debug mode is on.
 */
function is_wp_debug_mode() {

	return is_debug_mode() || ( defined( 'WP_DEBUG' ) && WP_DEBUG );
}

/**
 * Checks if either MultilingualPress or script debug mode is on.
 *
 * @return bool Whether or not MultilingualPress or script debug mode is on.
 */
function is_script_debug_mode() {

	return is_debug_mode() || ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG );
}

/**
 * Check whether redirect = on for specific blog.
 *
 * @param    bool $blogid
 *
 * @return    bool TRUE/FALSE
 */
function is_redirect( $blogid = FALSE ) {

	$blogid or $blogid = get_current_blog_id();

	return (bool) get_blog_option( $blogid, 'inpsyde_multilingual_redirect' );
}

/**
 * Return current blog's language code ( not the locale used by WordPress, but the one set by MLP)
 *
 * @param   bool $short
 *
 * @return    array Available languages
 */
function get_current_blog_language( $short = FALSE ) {

	// Get all registered blogs
	$languages = get_site_option( 'inpsyde_multilingual' );

	// Get current blog
	$blogid = get_current_blog_id();

	// If this blog is in a language
	if ( ! isset ( $languages[ $blogid ][ 'lang' ] ) ) {
		return '';
	}

	return $short ? strtok( $languages[ $blogid ][ 'lang' ], '_' ) : $languages[ $blogid ][ 'lang' ];
}

/**
 * Load the languages set for each blog.
 *
 * @param  bool $not_related
 *
 * @return    array Available languages
 */
function get_available_languages( $not_related = FALSE ) {

	$related_blogs = [];

	// Get all registered blogs
	$languages = get_site_option( 'inpsyde_multilingual' );

	if ( empty ( $languages ) ) {
		return [];
	}

	/** @var \Mlp_Site_Relations $site_relations */
	$site_relations = MultilingualPress::resolve( 'mlp.site_relations' );

	// Do we need related blogs only?
	if ( FALSE === $not_related ) {
		$related_blogs = $site_relations->get_related_sites( get_current_blog_id(), ! is_user_logged_in() );

		// No related blogs? Leave here.
		if ( empty ( $related_blogs ) ) {
			return [];
		}
	}

	$options = [];

	// Loop through blogs
	foreach ( $languages as $language_blogid => $language_data ) {

		// no blogs with a link to other blogs
		if ( empty ( $language_data[ 'lang' ] ) || '-1' === $language_data[ 'lang' ] ) {
			continue;
		}

		// Filter out blogs that are not related
		if ( ! $not_related && ! in_array( $language_blogid, $related_blogs ) ) {
			continue;
		}

		$lang = $language_data[ 'lang' ];

		$options[ $language_blogid ] = $lang;
	}

	return $options;
}

/**
 * Load the available language titles.

 * @param  bool $related
 *
 * @return    array Available languages
 */
function get_available_languages_titles( $related = TRUE ) {

	/** @var \Mlp_Language_Api $api */
	$api  = MultilingualPress::resolve( 'mlp.api' );
	$blog = $related ? get_current_blog_id() : 0;

	return $api->get_site_languages( $blog );
}

/**
 * Function to get the element ID in other blogs for the selected element
 *
 * @param    int    $element_id ID of the selected element
 * @param    string $type       type of the selected element
 * @param    int    $blog_id    ID of the selected blog
 *
 * @return    array linked elements
 */
function get_linked_elements( $element_id = 0, $type = '', $blog_id = 0 ) {

	$element_id = get_default_content_id( $element_id );

	if ( ! $element_id ) {
		return [];
	}

	// If no ID is provided, get current blogs' ID
	if ( 0 === $blog_id ) {
		$blog_id = get_current_blog_id();
	}

	if ( '' === $type ) {
		$type = 'post';
	}

	/** @var \Mlp_Language_Api $api */
	$api = MultilingualPress::resolve( 'mlp.api' );

	return $api->get_related_content_ids( $blog_id, $element_id, $type );
}

/**
 * Function for custom plugins to get activated on all language blogs.
 *
 * @param    int    $element_id ID of the selected element
 * @param    string $type       type of the selected element
 * @param    int    $blog_id    ID of the selected blog
 * @param    string $hook       name of the hook that will be executed
 * @param    array  $param      parameters for the function
 *
 * @return    \WP_Error|NULL
 */
function run_custom_plugin( $element_id = 0, $type = '', $blog_id = 0, $hook = NULL, $param = NULL ) {

	if ( empty( $element_id ) ) {
		return Error::create(
			'mlp_empty_custom_element',
			__( 'Empty Element', 'multilingual-press' )
		);
	}

	if ( empty( $type ) ) {
		return Error::create( 'mlp_empty_custom_type', __( 'Empty Type', 'multilingual-press' ) );
	}

	if ( empty ( $hook ) || ! is_callable( $hook ) ) {
		return Error::create( 'mlp_empty_custom_hook', __( 'Invalid Hook', 'multilingual-press' ) );
	}

	// set the current element in the mlp class
	$languages       = get_available_languages();
	$current_blog_id = get_current_blog_id();

	if ( 0 == count( $languages ) ) {
		return NULL;
	}

	foreach ( $languages as $language_id => $language_name ) {

		if ( (int) $current_blog_id !== (int) $language_id ) {
			switch_to_blog( $language_id );

			/**
			 * custom hook
			 *
			 * @param mixed $param
			 */
			do_action( $hook, $param );
			restore_current_blog();
		}
	}

	return NULL;
}

/**
 * Function to get the url of the flag from a blogid
 *
 * @param    int $site_id ID of a blog
 *
 * @return    string url of the language image
 */
function get_language_flag( $site_id = 0 ) {

	$site_id === 0 and $site_id = get_current_blog_id();

	$languages = get_site_option( 'inpsyde_multilingual' );

	if ( empty ( $languages[ $site_id ] ) ) {
		return '';
	}

	/** @var \Mlp_Language_Api $api */
	$api = MultilingualPress::resolve( 'mlp.api' );

	return (string) $api->get_flag_by_language( $languages[ $site_id ], $site_id );
}

/**
 * Get the linked elements and display them as a list.
 *
 * @param array $args
 *
 * @return string
 */
function get_linked_elements_list( $args ) {

	$defaults = [
		'link_text'         => 'native',
		'display_flag'      => FALSE,
		'sort'              => 'priority',
		'show_current_blog' => FALSE,
		'strict'            => FALSE, // get exact translations only
	];
	$params   = wp_parse_args( $args, $defaults );

	// TODO: Eventually remove this, with version 2.2.0 + 4 at the earliest.
	switch ( $params[ 'link_text' ] ) {
		case 'text_flag':
			_doing_it_wrong(
				__METHOD__,
				"The value 'text_flag' for the argument 'link_text' is deprecated and will be removed in the future. Please use the value TRUE for the argument 'display_flag', and choose one of the possible options for the argument 'link_text'.",
				'2.2.0'
			);

			$params[ 'link_text' ]    = 'native';
			$params[ 'display_flag' ] = TRUE;
			break;

		case 'flag':
			_doing_it_wrong(
				__METHOD__,
				"The value 'flag' for the argument 'link_text' is deprecated and will be removed in the future. Please use the value TRUE for the argument 'display_flag', and the value 'none' for the argument 'link_text'.",
				'2.2.0'
			);

			$params[ 'link_text' ]    = 'none';
			$params[ 'display_flag' ] = TRUE;
			break;
	}

	$api = MultilingualPress::resolve( 'mlp.api' );

	$translations_args = [
		'strict'       => $params[ 'strict' ],
		'include_base' => $params[ 'show_current_blog' ],
	];

	$translations = $api->get_translations( $translations_args );
	if ( empty( $translations ) ) {
		return '';
	}

	$items = [];

	/** @var Translation $translation */
	foreach ( $translations as $site_id => $translation ) {
		$url = $translation->remote_url();
		if ( empty( $url ) ) {
			continue;
		}

		$language = $translation->language();

		$items[ $site_id ] = [
			'url'      => $url,
			'http'     => $language->name( 'http' ),
			'name'     => $language->name( $params[ 'link_text' ] ),
			'priority' => $language->priority(),
			'icon'     => (string) $translation->icon_url(),
		];
	}

	switch ( $params[ 'sort' ] ) {
		case 'blogid':
			ksort( $items );
			break;

		case 'priority':
			uasort( $items, [ __CLASS__, 'sort_priorities' ] );
			break;

		case 'name':
			uasort( $items, [ __CLASS__, 'strcasecmp_sort_names' ] );
			break;
	}

	$output = '<div class="mlp-language-box mlp_language_box"><ul>';

	foreach ( $items as $site_id => $item ) {
		$text = $item[ 'name' ];

		$img = ( ! empty( $item[ 'icon' ] ) && $params[ 'display_flag' ] )
			? '<img src="' . esc_url( $item[ 'icon' ] ) . '" alt="' . esc_attr( $item[ 'name' ] ) . '"> '
			: '';

		if ( get_current_blog_id() === $site_id ) {
			$output .= '<li><a class="current-language-item" href="">' . $img . esc_html( $text ) . '</a></li>';
		} else {
			$output .= sprintf(
				'<li><a rel="alternate" hreflang="%1$s" href="%2$s">%3$s%4$s</a></li>',
				esc_attr( $item[ 'http' ] ),
				esc_url( $item[ 'url' ] ),
				$img,
				esc_html( $text )
			);
		}
	}

	$output .= '</ul></div>';

	return $output;
}

/**
 * Get the linked elements and display them as a list.
 *
 * @param array $args Arguments array
 *
 * @return string
 */
function show_linked_elements( array $args ) {

	$defaults = [
		'link_text'         => 'text',
		'sort'              => 'priority',
		'show_current_blog' => FALSE,
		'display_flag'      => FALSE,
		'strict'            => FALSE, // get exact translations only
	];

	$params = wp_parse_args( $args, $defaults );
	$output = get_linked_elements( $params );

	$echo = isset( $params[ 'echo' ] ) ? $params[ 'echo' ] : TRUE;
	if ( $echo ) {
		echo $output;
	}

	return $output;
}

/**
 * Get the element ID in other blogs for the selected element
 * with additional information.
 *
 * @param    int $element_id current post / page / whatever
 *
 * @return    array
 */
function get_interlinked_permalinks( $element_id = 0 ) {

	if ( ! is_singular() && ! is_tag() && ! is_category() && ! is_tax() ) {
		return [];
	}

	$return = [];
	/** @var \Mlp_Language_Api $api */
	$api = MultilingualPress::resolve( 'mlp.api' );

	$site_id    = get_current_blog_id();

	$args = [
		'site_id'    => $site_id,
		'content_id' => $element_id
	];

	// Array of Mlp_Translation instances, site IDs are the keys
	$related = $api->get_translations( $args );

	if ( empty ( $related ) ) {
		return $return;
	}

	/** @var Translation $translation */
	foreach ( $related as $remote_site_id => $translation ) {

		if ( $site_id === (int) $remote_site_id ) {
			continue;
		}

		$url = $translation->remote_url();

		if ( empty ( $url ) ) {
			continue;
		}

		$language = $translation->language();

		$return[ $remote_site_id ] = [
			'post_id'        => $translation->target_content_id(),
			'post_title'     => $translation->remote_title(),
			'permalink'      => $url,
			'flag'           => $translation->icon_url(),
			/* 'lang' is the old entry, language_short the first part
			 * until the '_', long the complete language tag.
			 */
			'lang'           => $language->name( 'lang' ),
			'language_short' => $language->name( 'lang' ),
			'language_long'  => $language->name( 'language_long' ),
		];
	}

	return $return;
}

/**
 * Return the language for the given blog.
 *
 * @param int  $site_id Blog ID.
 * @param bool $short   Return only the first part of the language code?
 *
 * @return string
 */
function get_blog_language( $site_id = 0, $short = TRUE ) {

	static $languages;

	$site_id == 0 and $site_id = get_current_blog_id();

	if ( empty ( $languages ) )
		$languages = get_site_option( 'inpsyde_multilingual' );

	if ( empty ( $languages )
		or empty ( $languages[ $site_id ] )
		or empty ( $languages[ $site_id ][ 'lang' ] )
	) {
		return '';
	}

	return $short ? strtok( $languages[ $site_id ][ 'lang' ], '_' ) : $languages[ $site_id ][ 'lang' ];
}

/**
 * Get language representation.
 *
 * @param string $iso   Two-letter code like "en" or "de"
 * @param string $field Sub-key name: "iso_639_2", "en" or "native",
 *                      defaults to "native", "all" returns the complete list.
 *
 * @return boolean|array|string FALSE for unknown language codes or fields,
 *               array for $field = 'all' and string for specific fields
 */
function get_lang_by_iso( $iso, $field = 'native_name' ) {

	/** @var \Mlp_Language_Api $api */
	$api  = MultilingualPress::resolve( 'mlp.api');

	return $api->get_lang_data_by_iso( $iso, $field );
}
/**
 * Checks if a blog exists and is not marked as deleted.
 *
 * @link   http://wordpress.stackexchange.com/q/138300/73
 *
 * @param  int $blog_id
 * @param  int $site_id
 *
 * @return bool
 */
function blog_exists( $blog_id, $site_id = 0 ) {

	/** @var \wpdb $wpdb */
	global $wpdb;
	static $cache = [];

	$site_id = (int) $site_id;
	$site_id === 0 and $site_id = get_current_site()->id;

	if ( empty ( $cache ) or empty ( $cache[ $site_id ] ) ) {

		// we do not test large sites.
		if ( wp_is_large_network() ) {
			return TRUE;
		}

		$query = "SELECT `blog_id` FROM {$wpdb->blogs} WHERE site_id = {$site_id} AND deleted = 0";
		$result = $wpdb->get_col( $query );

		// Make sure the array is always filled with something.
		if ( empty ( $result ) ) {
			$cache[ $site_id ] = [ 'do not check again' ];
		} else {
			$cache[ $site_id ] = $result;
		}
	}

	return in_array( $blog_id, $cache[ $site_id ] );
}
