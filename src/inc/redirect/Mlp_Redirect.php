<?php # -*- coding: utf-8 -*-
/**
 * Main controller for the Redirect feature.
 */
class Mlp_Redirect {

	/**
	 * @var Mlp_Language_Api_Interface
	 */
	private $language_api;

	/**
	 * @var string
	 */
	private $option = 'inpsyde_multilingual_redirect';

	/**
	 * Constructor.
	 *
	 * @param Mlp_Language_Api_Interface $language_api
	 */
	public function __construct( Mlp_Language_Api_Interface $language_api ) {

		$this->language_api = $language_api;
	}

	/**
	 * Determines the current state and actions, and calls subsequent methods.
	 *
	 * @param string $module_name
	 *
	 * @return bool
	 */
	public function setup( $module_name ) {

		// Quit here if module is turned off
		if ( did_action( "inpsyde_module_{$module_name}_setup" ) ) {
			return FALSE;
		}

		$this->user_settings();

		if ( ! is_admin() && ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) ) {
			$this->frontend_redirect();

			return TRUE;
		}

		$this->site_settings();

		if ( is_network_admin() ) {
			$this->activation_column();
		}

		return TRUE;
	}

	/**
	 * Redirects visitors to the best matching language alternative.
	 *
	 * @return void
	 */
	private function frontend_redirect() {

		$negotiation = new Mlp_Language_Negotiation( $this->language_api );
		$response    = new Mlp_Redirect_Response( $negotiation );
		$controller  = new Mlp_Redirect_Frontend( $response, $this->option );
		$controller->setup();
	}

	/**
	 * Shows the redirect status in the sites list.
	 *
	 * @return void
	 */
	private function activation_column() {

		$controller = new Mlp_Redirect_Column( NULL, NULL );
		$controller->setup();
	}

	/**
	 * Sets up user-specific settings.
	 *
	 * @return void
	 */
	private function user_settings() {

		$controller = new Mlp_Redirect_User_Settings();
		$controller->setup();
	}

	/**
	 * Sets up site-specific settings.
	 *
	 * @return void
	 */
	private function site_settings() {

		$controller = new Mlp_Redirect_Site_Settings( $this->option );
		$controller->setup();
	}
}
