<?php

require_once( NINJA_FORMS_UPLOADS_DIR . '/includes/lib/dropbox/dropbox.php' );

class External_Dropbox extends NF_Upload_External {

	private $title = 'Dropbox';

	private $slug = 'dropbox';

	private $settings;

	function __construct() {
		$this->set_settings();
		parent::__construct( $this->title, $this->slug, $this->settings );

		add_action( 'admin_init', array( $this, 'disconnect' ) );
		add_action( 'admin_notices', array( $this, 'connect_notice' ) );
		add_action( 'admin_notices', array( $this, 'disconnect_notice' ) );
	}

	private function set_settings() {
		$this->settings = array(
			array(
				'name'             => 'dropbox_connect',
				'type'             => '',
				'label'            => sprintf( __( 'Connect to %s', 'ninja-forms-uploads' ), $this->title ),
				'desc'             => '',
				'display_function' => array( $this, 'connect_url' )
			),
			array(
				'name' => 'dropbox_token',
				'type' => 'hidden'
			)
		);
	}

	public function is_connected() {
		$data = get_option( 'ninja_forms_settings' );
		if ( ( isset( $data['dropbox_access_token'] ) && $data['dropbox_access_token'] != '' ) &&
			 ( isset( $data['dropbox_access_token_secret'] ) && $data['dropbox_access_token_secret'] != '' )
		) {
			$dropbox = new nf_dropbox();
			return $dropbox->is_authorized();
		}

		return false;
	}

	protected function upload_file( $filename ) {
		$dropbox = new nf_dropbox();
		$dropbox->upload_file( $filename );
	}

	public function file_url( $filename ) {
		$dropbox = new nf_dropbox();
		$url     = $dropbox->get_link( $filename );
		if ( $url ) {
			return $url;
		}

		return admin_url();
	}

	public function connect_url( $form_id, $data ) {
		$dropbox        = new nf_dropbox();
		$callback_url   = admin_url( '/admin.php?page=ninja-forms-uploads&tab=external_settings' );
		$disconnect_url = admin_url( '/admin.php?page=ninja-forms-uploads&tab=external_settings&action=disconnect_' . $this->slug );
		if ( $dropbox->is_authorized() ) {
			?>
			<a id="dropbox-disconnect" href="<?php echo $disconnect_url; ?>" class="button-secondary"><?php _e( 'Disconnect', 'ninja-forms-uploads' ); ?></a>
		<?php } else { ?>
			<a id="dropbox-connect" href="<?php echo $dropbox->get_authorize_url( $callback_url ); ?>" class="button-secondary"><?php _e( 'Connect', 'ninja-forms-uploads' ); ?></a>
		<?php
		}
	}

	public function disconnect() {
		if ( isset( $_GET['page'] ) && $_GET['page'] == 'ninja-forms-uploads' &&
			 isset( $_GET['tab'] ) && $_GET['tab'] == 'external_settings' &&
			 isset( $_GET['action'] ) && $_GET['action'] == 'disconnect_' . $this->slug
		) {

			$dropbox = new nf_dropbox();
			$dropbox->unlink_account();
		}
	}

	public function connect_notice() {
		if ( isset( $_GET['page'] ) && $_GET['page'] == 'ninja-forms-uploads' &&
			 isset( $_GET['tab'] ) && $_GET['tab'] == 'external_settings' &&
			 isset( $_GET['oauth_token'] ) && isset( $_GET['uid'] )
		) {
			echo '<div class="updated"><p>' . sprintf( __( 'Connected to %s', 'ninja-forms-uploads' ), $this->title ) . '</p></div>';
		}
	}

	public function disconnect_notice() {
		if ( isset( $_GET['page'] ) && $_GET['page'] == 'ninja-forms-uploads' &&
			 isset( $_GET['tab'] ) && $_GET['tab'] == 'external_settings' &&
			 isset( $_GET['action'] ) && $_GET['action'] == 'disconnect_' . $this->slug
		) {
			echo '<div class="updated"><p>' . sprintf( __( 'Disconnected from %s', 'ninja-forms-uploads' ), $this->title ) . '</p></div>';
		}
	}

}