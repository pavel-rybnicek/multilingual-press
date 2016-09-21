<?php
/**
 * Module Name:	MultilingualPress Custom Post Type Module
 * Description:	Allow MlP functionality for specific custom post types
 * Author:		Inpsyde GmbH
 * Version:		0.9
 * Author URI:	http://inpsyde.com
 */

class Mlp_Cpt_Translator implements Mlp_Updatable {

	/**
	 * Registered post types
	 *
	 * @access	private
	 * @since	0.1
	 * @var		array $post_types
	 */
	private $post_types;

	/**
	 * Prefix for 'name' attribute in form fields.
	 *
	 * @type string
	 */
	private $form_name = 'mlp_cpts';

	/**
	 * @var Inpsyde_Nonce_Validator
	 */
	private $nonce_validator;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->nonce_validator = Mlp_Nonce_Validator_Factory::create( 'save_cpt_translator_settings' );
	}

	/**
	 * Filter the list of allowed post types for translations.
	 *
	 * @wp-hook mlp_allowed_post_types
	 * @param   array $post_types
	 * @return  array
	 */
	public function filter_allowed_post_types( array $post_types ) {

		return array_merge( $post_types, $this->get_active_post_types() );
	}

	/**
	 * Explain when there are no custom post types.
	 *
	 * @return string
	 */
	public function extend_settings_description() {

		$found = $this->get_custom_post_types();

		if ( empty ( $found ) ) {
			return '<p class="mlp-callback-indent"><em>'
				. __( 'No custom post type found.', 'multilingual-press' )
				. '</em></p>';
		}

		return '';
	}

	/**
	 * This is the callback of the metabox
	 * used to display the modules options page
	 * form fields
	 *
	 * @return	void
	 */
	public function draw_options_page_form_fields() {

		$post_types = $this->get_custom_post_types();

		if ( empty ( $post_types ) )
			return;

		$data = new Mlp_Cpt_Translator_Extra_General_Settings_Box_Data(
			$this,
			$this->nonce_validator
		);
		$box  = new Mlp_Extra_General_Settings_Box( $data );
		$box->print_box();
	}

	/**
	 * @param string $name
	 *
	 * @return mixed|void Either a value, or void for actions.
	 */
	public function update( $name ) {

		if ( 'custom.post-type.list' === $name ) {
			return $this->get_custom_post_types();
		}

		return '';
	}

	/**
	 * Hook into mlp_settings_save_fields to handle module user input.
	 *
	 * @wp-hook mlp_settings_save_fields
	 * @return bool
	 */
	public function save_options_page_form_fields() {

		if ( ! $this->nonce_validator->is_valid() )
			return FALSE;

		$options    = get_site_option( 'inpsyde_multilingual_cpt' );
		$post_types = $this->get_custom_post_types();

		if ( empty ( $post_types ) or empty ( $_POST[ $this->form_name ] ) ) {
			$options[ 'post_types' ] = [];
			return update_site_option( 'inpsyde_multilingual_cpt', $options );
		}

		foreach ( $post_types as $cpt => $cpt_params ) {

			if ( empty ( $_POST[ $this->form_name ][ $cpt ] ) )
				$options[ 'post_types' ][ $cpt ] = 0;
			elseif ( empty ( $_POST[ $this->form_name ][ $cpt . '|links' ] ) )
				$options[ 'post_types' ][ $cpt ] = 1;
			else
				$options[ 'post_types' ][ $cpt ] = 2;
		}

		return update_site_option( 'inpsyde_multilingual_cpt', $options );
	}

	/**
	 * Returns all custom post types.
	 *
	 * @return array
	 */
	public function get_custom_post_types() {

		if ( is_array( $this->post_types ) ) {
			return $this->post_types;
		}

		$this->post_types = get_post_types( [ 
			'_builtin' => false,
			'show_ui'  => true,
		 ], 'objects' );
		if ( $this->post_types ) {
			uasort( $this->post_types, [ $this, 'sort_cpts_by_label' ] );
		}

		return $this->post_types;
	}

	/**
	 * Sort post types by their display label.
	 *
	 * @param object $cpt1 First post type object.
	 * @param object $cpt2 Second post type object.
	 *
	 * @return int
	 */
	private function sort_cpts_by_label( $cpt1, $cpt2 ) {

		return strcasecmp( $cpt1->labels->name, $cpt2->labels->name );
	}

	/**
	 * Get all translatable custom post types.
	 *
	 * @return array
	 */
	public function get_active_post_types() {

		$options = get_site_option( 'inpsyde_multilingual_cpt' );
		$out     = [];

		if ( empty ( $options ) or empty ( $options[ 'post_types' ] ) )
			return $out;

		foreach ( $options[ 'post_types' ] as $post_type => $setting ) {
			if ( 0 != $setting )
				$out[] = $post_type;
		}

		return array_unique( $out );
	}

	/**
	 * show the metabox
	 *
	 * @return  void
	 */
	public function display_meta_box_translate() {

		?>
		<p>
			<label for="translate_this_post">
				<input type="checkbox" id="translate_this_post" name="translate_this_post"
					<?php
					/**
					 * Filter the default value of the 'Translate this post' checkbox.
					 *
					 * @param bool $translate Should 'Translate this post' be checked by default?
					 */
					$translate = (bool) apply_filters( 'mlp_translate_this_post_checkbox', false );
					checked( $translate );
					?>
				>
				<?php _e( 'Translate this post', 'multilingual-press' ); ?>
			</label>
		</p>
		<?php
	}

	/**
	 * add the link filter to change to non permalinks
	 *
	 * @access  public
	 * @since   0.9
	 * @uses	add_filter
	 * @return  void
	 */
	public function before_mlp_link() {

		add_filter( 'post_type_link', [ $this, 'change_cpt_slug' ], 10, 2 );
	}

	/**
	 * remove the link filter to avoid replacing all permalinks
	 *
	 * @access  public
	 * @since   0.9
	 * @uses	remove_filter
	 * @return  void
	 */
	public function after_mlp_link() {

		remove_filter( 'post_type_link', [ $this, 'change_cpt_slug' ], 10 );
	}

	/**
	 * Change the permalink to ?posttype=<post-name> links to avoid problems
	 * when switch_to_blog and different rewrite_slugs on blogs.
	 *
	 * @param   string $post_link
	 * @param   WP_Post $post
	 * @return  string
	 */
	public function change_cpt_slug( $post_link, $post ) {

		if ( ! $this->is_cpt_with_dynamic_permalink( $post->post_type ) )
			return $post_link;

		$draft_or_pending = $this->is_draft_or_pending( $post );
		$post_type        = get_post_type_object( $post->post_type );

		if ( $post_type->query_var && ( isset ( $post->post_status ) && ! $draft_or_pending ) )
			$post_link = add_query_arg( $post_type->query_var, $post->post_name, '' );
		else
			$post_link = add_query_arg(
				[ 'post_type' => $post->post_type, 'p' => $post->ID ],
				''
			);

		return site_url( $post_link );
	}

	/**
	 * Should this permalink be sent as a parameter?
	 *
	 * @param  string $post_type
	 * @return bool
	 */
	private function is_cpt_with_dynamic_permalink( $post_type ) {

		$options = get_site_option( 'inpsyde_multilingual_cpt' );

		if ( empty ( $options ) )
			return FALSE;

		if ( empty ( $options[ 'post_types' ] ) )
			return FALSE;

		if ( empty ( $options[ 'post_types' ][ $post_type ] ) )
			return FALSE;

		return (int) $options[ 'post_types' ][ $post_type ] > 1;
	}

	/**
	 * Get post type status.
	 *
	 * @param  WP_Post $post
	 * @return bool
	 */
	public function is_draft_or_pending( $post ) {

		if ( empty ( $post->post_status ) )
			return FALSE;

		return in_array( $post->post_status, [ 'draft', 'pending', 'auto-draft' ], true );
	}
}
