<?php # -*- coding: utf-8 -*-

/**
 * Provides a new post meta and checkbox to trash the posts through the related blogs.
 */
class Mlp_Trasher {

	/**
	 * @var Mlp_Module_Manager_Interface
	 */
	private $module_manager;

	/**
	 * @var bool
	 */
	private $saved_post = false;

	/**
	 * Displays the checkbox for the Trasher post meta.
	 *
	 * @since 0.1
	 *
	 * @return void
	 */
	public function post_submitbox_misc_actions() {

		if ( isset( $_GET['post'] ) ) {
			// old key
			$trash_the_other_posts = (int) get_post_meta( $_GET['post'], 'trash_the_other_posts', true );

			if ( 1 !== $trash_the_other_posts ) {
				$trash_the_other_posts = (int) get_post_meta( $_GET['post'], '_trash_the_other_posts', true );
			}
		} else {
			$trash_the_other_posts = false;
		}
		?>
		<div class="misc-pub-section curtime misc-pub-section-last">
			<input type="hidden" name="trasher_box" value="1">
			<label for="trash_the_other_posts">
				<input type="checkbox" id="trash_the_other_posts" name="_trash_the_other_posts"
					<?php checked( 1, $trash_the_other_posts ); ?>>
				<?php _e( 'Send all the translations to trash when this post is trashed.', 'multilingual-press' ); ?>
			</label>
		</div>
		<?php
	}

	/**
	 * Trashes the related posts if the user wants to.
	 *
	 * @param int $post_id Post ID.
	 *
	 * @return void
	 */
	public function trash_post( $post_id ) {

		$trash_the_other_posts = (int) get_post_meta( $post_id, '_trash_the_other_posts', true );

		// old key
		if ( 1 !== $trash_the_other_posts ) {
			$trash_the_other_posts = (int) get_post_meta( $post_id, 'trash_the_other_posts', true );
		}

		if ( 1 !== $trash_the_other_posts ) {
			return;
		}

		// remove filter to avoid recursion
		remove_filter( current_filter(), [ $this, __FUNCTION__ ] );

		$linked_posts = mlp_get_linked_elements( $post_id );
		foreach ( $linked_posts as $linked_blog => $linked_post ) {
			switch_to_blog( $linked_blog );

			wp_trash_post( $linked_post );

			restore_current_blog();
		}

		add_filter( current_filter(), [ $this, __FUNCTION__ ] );
	}

	/**
	 * Updates the post meta.
	 *
	 * @param int $post_id Post ID.
	 *
	 * @return void
	 */
	public function save_post( $post_id ) {

		// leave function if box was not available
		if ( ! isset ( $_POST['trasher_box'] ) ) {
			return;
		}

		// We're only interested in published posts at this time
		$post_status = get_post_status( $post_id );
		if ( ! in_array( $post_status, [ 'publish', 'draft' ], true ) ) {
			return;
		}

		// The wp_insert_post() method fires the save_post action hook, so we have to avoid recursion.
		if ( $this->saved_post ) {
			return;
		} else {
			$this->saved_post = true;
		}

		// old key
		delete_post_meta( $post_id, 'trash_the_other_posts' );

		$trash_the_other_posts = false;

		// Should the other post also been trashed?
		if ( ! empty( $_POST['_trash_the_other_posts'] ) && 'on' === $_POST['_trash_the_other_posts'] ) {
			$trash_the_other_posts = true;

			update_post_meta( $post_id, '_trash_the_other_posts', '1' );
		} else {
			update_post_meta( $post_id, '_trash_the_other_posts', '0' );
		}

		// Get linked posts
		$linked_posts = mlp_get_linked_elements( $post_id );
		foreach ( $linked_posts as $linked_blog => $linked_post ) {
			switch_to_blog( $linked_blog );

			delete_post_meta( $linked_post, 'trash_the_other_posts' );

			// Should the other post also been trashed?
			update_post_meta( $linked_post, '_trash_the_other_posts', $trash_the_other_posts ? '1' : '0' );

			restore_current_blog();
		}
	}
}
