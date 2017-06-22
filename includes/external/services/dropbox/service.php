<?php

use Polevaultweb\WP_OAuth2\Dropbox_Client;
use Polevaultweb\WP_OAuth2\WP_OAuth2;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class NF_FU_External_Services_Dropbox_Service
 */
class NF_FU_External_Services_Dropbox_Service extends NF_FU_External_Abstracts_Service {

	public $name = 'Dropbox';

	protected $library_class = 'Dropbox_API';

	protected $library_file = 'benthedesigner/dropbox/API.php';

	protected $client;

	const CONSUMER_KEY = 'g80jscev5iosghi';

	/**
	 * Get Dropbox API client instance
	 *
	 * @return Dropbox_API
	 */
	protected function get_client() {
		if ( is_null( $this->client ) ) {
			$this->load_settings();

			$this->client = new NF_FU_Library_Dropbox( $this->get_oauth() );
		}

		return $this->client;
	}

	/**
	 * Has the account authorised our API app?
	 *
	 * @return bool
	 */
	public function is_authorized() {
		try {
			$this->get_account_info();

			return true;
		} catch ( Exception $e ) {
			return false;
		}
	/**
	 * Get the plugins page URL to return to.
	 * 
	 * @return string
	 */
	protected function get_callback_url() {
		return NF_File_Uploads()->page->get_url( 'external', array(), false );
	}

	/**
	 * Get the URL to authorise the app
	 *
	 * @return string
	 */
	public function get_authorize_url() {
		$callback_url = $this->get_callback_url();
		$oauth        = new Dropbox_Client( self::CONSUMER_KEY );

		return $oauth->get_authorize_url( $callback_url );
	}

	public function get_account_info() {
		if ( ! isset( $this->account_info_cache ) ) {
			$response                 = $this->get_client()->accountInfo();
			$this->account_info_cache = $response['body'];
		}

		return $this->account_info_cache;
	}

	public function connect_url() {
		if ( $this->is_authorized() ) {
			$url = WP_OAuth2::get_disconnect_url( $this->slug, $this->get_callback_url() );
			?>
			<a id="dropbox-disconnect" href="<?php echo $url; ?>" class="button-secondary"><?php _e( 'Disconnect', 'ninja-forms-uploads' ); ?></a>
		<?php } else {
			$url = $this->get_authorize_url();
			?>
			<a id="dropbox-connect" href="<?php echo $url; ?>" class="button-secondary"><?php _e( 'Connect', 'ninja-forms-uploads' ); ?></a>
			<?php
		}
	}

	/**
	 * Dropbox requirements
	 *
	 * @return array
	 */
	public function get_missing_requirements() {
		$missing_requirements = parent::get_missing_requirements();

		if ( ! extension_loaded( 'curl' ) ) {
			$curl_link              = sprintf( '<a href="%s">%s</a>', 'http://php.net/manual/en/curl.installation.php', __( 'Learn more', 'ninja-forms-upload' ) );
			$missing_requirements[] = sprintf( __( 'The cURL extension to be installed. Please ensure its installed and activated. %s.', 'ninja-forms-upload' ), $curl_link );
		}

		return $missing_requirements;
	}

	/**
	 * Is the service connected?
	 *
	 * @param null|array $settings
	 *
	 * @return bool
	 */
	public function is_connected( $settings = null ) {
		if ( ! WP_OAuth2::is_authorized( 'dropbox' ) ) {
			return false;
		}

		if ( false === ( $authorised = get_transient( 'nf_fu_dropbox_authorised' ) ) ) {
			$authorised = $this->is_authorized();

			set_transient( 'nf_fu_dropbox_authorised', $authorised, 60 * 60 * 5 );
		}

		return $authorised;
	}

	/**
	 * Get path on Dropbox to upload to
	 *
	 * @return string
	 */
	protected function get_path_setting() {
		return 'dropbox_file_path';
	}
	
	/**
	 * Upload the file to Dropbox
	 *
	 * @param array $data
	 *
	 * @return array|bool
	 */
	public function upload_file( $data ) {
		if ( $this->external_filename === '' ) {
			$this->external_filename = $this->remove_secret( $this->upload_file );
		}

		$retry_count = apply_filters( 'ninja_forms_upload_dropbox_retry_count', 3 );
		$i           = 0;
		while ( $i ++ < $retry_count ) {
			try {
				$result = $this->get_client()->putFile( $this->upload_file, $this->external_filename, $this->external_path );

				return $data;

			} catch ( Exception $e ) {
			}
		}

		return false;
	}

	/**
	 * Get the Dropbox URL for the file
	 *
	 * @param string $filename
	 * @param string $path
	 * @param array  $data
	 *
	 * @return string
	 */
	public function get_url( $filename, $path = '', $data = array() ) {
		$response = $this->get_client()->media( $path . $filename );
		if ( $response['code'] == 200 ) {
			return $response['body']->url;
		}

		return admin_url();
	}

	protected function remove_secret( $file, $basename = true ) {
		if ( preg_match( '/-nf-secret$/', $file ) ) {
			$file = substr( $file, 0, strrpos( $file, '.' ) );
		}

		if ( $basename ) {
			return basename( $file );
		}

		return $file;
	}
}