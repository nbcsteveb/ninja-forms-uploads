<?php

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
	const CONSUMER_SECRET = 'hsy0xtrr3gjkd0i';

	protected $oauth;
	protected $access_token;
	protected $oauth_state;
	protected $request_token;

	/**
	 * Load other classes
	 */
	public function maybe_alias_library( $class ) {
		parent::maybe_alias_library( $class );

		$prefix = 'Dropbox_';
		$path   = dirname( NF_File_Uploads()->plugin_file_path ) . '/vendor/' . dirname( self::get_instance()->library_file ) . '/';

		NF_File_Uploads()->maybe_load_class( $class, $prefix, $path, true );
	}

	/**
	 * Get Oauth class instance
	 *
	 * @return Dropbox_OAuth_Consumer_Curl
	 */
	protected function get_oauth() {
		if ( is_null( $this->oauth ) ) {
			$this->oauth = new Dropbox_OAuth_Consumer_Curl( self::CONSUMER_KEY, self::CONSUMER_SECRET );

			$this->oauth_state   = NF_File_Uploads()->controllers->settings->get_setting( 'dropbox_oauth_state', false );
			$this->request_token = $this->get_token( 'request' );
			$this->access_token  = $this->get_token( 'access' );

			if ( $this->oauth_state == 'request' ) {
				//If we have not got an access token then we need to grab one
				try {
					$this->oauth->setToken( $this->request_token );
					$this->access_token = $this->oauth->getAccessToken();
					$this->oauth_state  = 'access';
					$this->oauth->setToken( $this->access_token );
					$this->save_tokens();
					//Supress the error because unlink, then init should be called
				} catch ( Exception $e ) {
				}
			} elseif ( $this->oauth_state == 'access' ) {
				$this->oauth->setToken( $this->access_token );
			} else {
				//If we don't have an access token then lets setup a new request
				$this->request_token = $this->oauth->getRequestToken();
				$this->oauth->setToken( $this->request_token );
				$this->oauth_state = 'request';
				$this->save_tokens();
			}
		}

		return $this->oauth;
	}

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
	 * Init service
	 */
	protected function init() {
		add_action( 'admin_init', array( $this, 'connect' ) );
		add_action( 'admin_init', array( $this, 'connect_redirect' ) );
		add_action( 'admin_init', array( $this, 'disconnect' ) );
		add_action( 'admin_notices', array( $this, 'connect_notice' ) );
		add_action( 'admin_notices', array( $this, 'disconnect_notice' ) );
	}

	/**
	 * Load Dropbox settings and ensure we cleared any tokens if not connected
	 *
	 * @return array
	 */
	public function load_settings() {
		$settings = parent::load_settings();

		if ( ! NF_File_Uploads()->controllers->settings->get_setting( 'dropbox_oauth_state', false ) ) {
			return $settings;
		}

		return $settings;
	}

	/**
	 * Get token
	 *
	 * @param $type
	 *
	 * @return stdClass
	 */
	protected function get_token( $type ) {
		$token        = NF_File_Uploads()->controllers->settings->get_setting( "dropbox_{$type}_token", false );
		$token_secret = NF_File_Uploads()->controllers->settings->get_setting( "dropbox_{$type}_token_secret", false);

		$ret                     = new stdClass;
		$ret->oauth_token        = null;
		$ret->oauth_token_secret = null;

		if ( $token && $token_secret ) {
			$ret                     = new stdClass;
			$ret->oauth_token        = $token;
			$ret->oauth_token_secret = $token_secret;
		}

		return $ret;
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
	}

	/**
	 * Get the URL to authorise the app
	 *
	 * @param $callback_url
	 *
	 * @return string
	 */
	public function get_authorize_url( $callback_url ) {
		return $this->get_oauth()->getAuthoriseUrl( $callback_url );
	}

	public function get_account_info() {
		if ( ! isset( $this->account_info_cache ) ) {
			$response                 = $this->get_client()->accountInfo();
			$this->account_info_cache = $response['body'];
		}

		return $this->account_info_cache;
	}

	private function save_tokens() {
		NF_File_Uploads()->controllers->settings->set_setting( 'dropbox_oauth_state', $this->oauth_state );

		if ( $this->request_token ) {
			NF_File_Uploads()->controllers->settings->set_setting( 'dropbox_request_token', $this->request_token->oauth_token );
			NF_File_Uploads()->controllers->settings->set_setting( 'dropbox_request_token_secret', $this->request_token->oauth_token_secret );
		} else {
			NF_File_Uploads()->controllers->settings->set_setting( 'dropbox_request_token', null );
			NF_File_Uploads()->controllers->settings->set_setting( 'dropbox_request_token_secret', null );
		}

		if ( $this->access_token ) {
			NF_File_Uploads()->controllers->settings->set_setting( 'dropbox_access_token', $this->access_token->oauth_token );
			NF_File_Uploads()->controllers->settings->set_setting( 'dropbox_access_token_secret', $this->access_token->oauth_token_secret );
		} else {
			NF_File_Uploads()->controllers->settings->set_setting( 'dropbox_access_token', null );
			NF_File_Uploads()->controllers->settings->set_setting( 'dropbox_access_token_secret', null );
		}

		NF_File_Uploads()->controllers->settings->update_settings();

		return $this;
	}

	public function connect_url() {
		$connect_url    = NF_File_Uploads()->page->get_url( 'external', array( 'action' => 'connect_' . $this->slug ), false );
		$disconnect_url = NF_File_Uploads()->page->get_url( 'external', array( 'action' => 'disconnect_' . $this->slug ), false );

		if ( $this->is_authorized() ) {
			?>
			<a id="dropbox-disconnect" href="<?php echo $disconnect_url; ?>" class="button-secondary"><?php _e( 'Disconnect', 'ninja-forms-uploads' ); ?></a>
		<?php } else { ?>
			<a id="dropbox-connect" href="<?php echo $connect_url; ?>" class="button-secondary"><?php _e( 'Connect', 'ninja-forms-uploads' ); ?></a>
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
		if ( is_null( $settings ) ) {
			$settings = $this->load_settings();
		}

		$access_keys = array( 'dropbox_access_token', 'dropbox_access_token_secret' );
		foreach ( $access_keys as $access_key ) {
			if ( ! isset( $settings[ $access_key ] ) || '' === $settings[ $access_key ] ) {
				return false;
			}
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

	/**
	 * Unlink account with our app
	 */
	public function unlink_account()
	{
		$this->get_oauth()->resetToken();
		$this->request_token = null;
		$this->access_token = null;
		$this->oauth_state = null;
		delete_transient( 'nf_fu_dropbox_authorised' );

		$this->save_tokens();
	}

	/**
	 * Connect to Dropbox handler
	 */
	public function connect() {
		if ( NF_FU_Helper::is_page( 'external', array( 'action' => 'connect_' . $this->slug ) ) ) {
			$this->unlink_account();

			$connect_url = NF_File_Uploads()->page->get_url( 'external', array( 'action' => 'redirect_connect_' . $this->slug ), false );

			wp_redirect( $connect_url );
			exit;
		}
	}

	/**
	 * Connect to Dropbox URL handler
	 */
	public function connect_redirect() {
		if ( NF_FU_Helper::is_page( 'external', array( 'action' => 'redirect_connect_' . $this->slug ) ) ) {

			$callback_url = NF_File_Uploads()->page->get_url( 'external', array(), false );
			$connect_url  = $this->get_authorize_url( $callback_url );

			wp_redirect( $connect_url );
			exit;
		}
	}

	/**
	 * Disconnect from Dropbox
	 */
	public function disconnect() {
		if ( NF_FU_Helper::is_page( 'external', array( 'action' => 'disconnect_' . $this->slug ) ) ) {
			$this->unlink_account();
		}
	}

	/**
	 * Display connection notice
	 */
	public function connect_notice() {
		if ( NF_FU_Helper::is_page( 'external', array( 'oauth_token' => false, 'uid' => false ) ) ) {
			echo '<div class="updated"><p>' . __( 'Connected to Dropbox', 'ninja-forms-uploads' ) . '</p></div>';
		}
	}

	/**
	 * Display disconnection notice
	 */
	public function disconnect_notice() {
		if ( NF_FU_Helper::is_page( 'external', array( 'action' => 'disconnect_' . $this->slug ) ) ) {
			echo '<div class="updated"><p>' . __( 'Disconnected from Dropbox', 'ninja-forms-uploads' ) . '</p></div>';
		}
	}
}