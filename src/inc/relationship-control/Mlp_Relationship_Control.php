<?php
/**
 * Controller for the relationship management above the Advanced Translator.
 *
 * @author  Inpsyde GmbH, toscho
 * @version 2014.10.10
 * @license GPL
 */
class Mlp_Relationship_Control implements Mlp_Updatable {

	/**
	 * @var Mlp_Relationship_Changer
	 */
	private $changer;

	/**
	 * Unique prefix to detect our registered actions and form names.
	 *
	 * @var string
	 */
	private $prefix = 'mlp_rc';

	/**
	 * @var Mlp_Relationship_Control_Data
	 */
	private $data;

	/**
	 * Constructor
	 *
	 * @uses  Mlp_Relationship_Control_Data
	 *
     * @param Mlp_Relationship_Changer $changer
	 */
	public function __construct( Mlp_Relationship_Changer $changer ) {

		$this->changer = $changer;
		$this->data = new Mlp_Relationship_Control_Data();
	}

	/**
	 * Register AJAX callbacks.
	 *
	 * @return void
	 */
	public function set_up_ajax() {

		$callback_type = "{$this->prefix}_remote_post_search" === $_REQUEST['action'] ? 'search' : 'reconnect';

		add_action( "wp_ajax_{$_REQUEST['action']}", [ $this, "ajax_{$callback_type}_callback" ] );
	}

	/**
	 * Callback for AJAX search.
	 *
	 * @uses   Mlp_Relationship_Control_Ajax_Search
	 * @return void
	 */
	public function ajax_search_callback() {

		$search = new Mlp_Relationship_Control_Ajax_Search( $this->data );
		$search->send_response();
	}

	/**
	 * Callback for AJAX reconnect.
	 *
	 * @uses   Mlp_Relationship_Changer
	 * @return void
	 */
	public function ajax_reconnect_callback() {

		$start = strlen( $this->prefix ) + 1;
		$func = substr( $_REQUEST['action'], $start ) . '_post';
		/** @var callable $method */
		$method = [$this->changer, $func];
		$result =  $method();

		status_header( 200 );

		// Never visible for the user, for debugging only.
		if ( is_scalar( $result ) )
			print $result;
		else
			print '<pre>' . print_r( $result, 1 ) . '</pre>';

		mlp_exit();
	}

	/**
	 * Create the UI above the Advanced Translator metabox.
	 *
	 * @wp-hook mlp_translation_meta_box_bottom
	 * @uses    Mlp_Relationship_Control_Meta_Box_View
	 * @param   WP_Post $post
	 * @param   int     $remote_site_id
	 * @param   WP_Post $remote_post
	 * @return void
	 */
	public function set_up_meta_box_handlers(
		WP_Post $post,
		        $remote_site_id,
		WP_Post $remote_post
	) {

		global $pagenow;

		if ( 'post-new.php' === $pagenow )
			return; // maybe later, for now, we work on existing posts only

		$this->data->set_ids( [
			'source_post_id' => $post->ID,
			'source_site_id' => get_current_blog_id(),
			'remote_site_id' => $remote_site_id,
			'remote_post_id' => $remote_post->ID,
		] );
		$view = new Mlp_Relationship_Control_Meta_Box_View( $this->data, $this );
		$view->render();
	}

	/**
	 * @param string $name
	 *
	 * @return mixed|void Either a value, or void for actions.
	 */
	public function update( $name ) {

		if ( 'default.remote.posts' === $name ) {
			$search = new Mlp_Relationship_Control_Ajax_Search( $this->data );
			$search->render();
		}
	}

	/**
	 * Check if this is our AJAX request.
	 *
	 * @return bool
	 */
	public function is_ajax() {

		if ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) {
			return false;
		}

		if ( empty( $_REQUEST['action'] ) ) {
			return false;
		}

		return 0 === strpos( $_REQUEST['action'], $this->prefix );
	}
}
