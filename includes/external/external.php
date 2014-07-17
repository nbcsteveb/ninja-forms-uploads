<?php

abstract class NF_Upload_External {

	private $slug;

	private $title;

	private $settings;

	function __construct( $title, $slug, $settings ) {
		$this->title    = $title;
		$this->slug     = $slug;
		$this->settings = $settings;

		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_filter( 'ninja_forms_upload_locations', array( $this, 'register_location' ) );

		add_action( 'ninja_forms_post_process', array( $this, 'upload_to_external' ) );
		add_action( 'ninja_forms_post_process', array( $this, 'remove_server_upload' ), 1001 );

	}

	public static function instance( $external, $require = false) {
		if ( $require ) {
			require_once( $external );
			$external = basename( $external, '.php' );
		}
		$external_class = 'External_'. ucfirst( $external );
		if ( class_exists( $external_class ) ) {
			return new $external_class();
		}

		return false;
	}

	public function register_location( $locations) {
		if ( $this->is_connected() ) {
			$locations[] = array (
				'value' => $this->slug,
				'name' => $this->title
			);
		}

		return $locations;
	}

	public function register_settings() {
		$args = array(
			'page' => 'ninja-forms-uploads',
			'tab' => 'external_settings',
			'slug' => $this->slug .'_settings',
			'title' => __( $this->title .' Settings', 'ninja-forms'),
			'settings' => $this->settings
		);
		if( function_exists( 'ninja_forms_register_tab_metabox' ) ){
			ninja_forms_register_tab_metabox($args);
		}
	}

	public function is_connected() {
		return false;
	}

	private function post_process( $form_id ) {
		if ( ! $this->is_connected() ) return false;
		global $ninja_forms_processing;
		if ( $ninja_forms_processing->get_form_setting( 'create_post' ) != 1 ) {
			if( $ninja_forms_processing->get_extra_value( 'uploads' ) ){
				foreach( $ninja_forms_processing->get_extra_value( 'uploads' ) as $field_id ){
					$field_row = $ninja_forms_processing->get_field_settings( $field_id );
					$user_value = $ninja_forms_processing->get_field_value( $field_id );
					if( isset( $field_row['data']['upload_location'] ) AND $field_row['data']['upload_location'] == $this->slug ){
						if( is_array( $user_value ) ){
							return $user_value;
						}
					}
				}
			}
		}

		return false;
	}

	public function upload_to_external( $form_id ){
		$user_value = $this->post_process( $form_id );
		if ( ! $user_value ) return;

		foreach( $user_value as $key => $file ){
			if ( ! isset( $file['file_path'] ) ) {
				continue;
			}
			$filename = $file['file_path'] . $file['file_name'];
			if ( file_exists( $filename ) ) {
				$this->upload_file( $filename );
			}
		}
	}

	public function remove_server_upload( $form_id ){
		$user_value = $this->post_process( $form_id );
		if ( ! $user_value ) return;

		foreach( $user_value as $key => $file ){
			if ( ! isset( $file['file_path'] ) ) {
				continue;
			}
			$filename = $file['file_path'] . $file['file_name'];
			if ( file_exists( $filename ) ) {
				// Delete local file
				unlink( $filename );
			}
		}
	}

	protected function upload_file( $filename ) {}

	public function file_url( $filename ) {
		return '';
	}

} 