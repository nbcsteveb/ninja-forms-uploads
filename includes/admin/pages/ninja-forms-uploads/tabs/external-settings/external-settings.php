<?php

add_action('admin_init', 'ninja_forms_register_tab_external_settings');
function ninja_forms_register_tab_external_settings(){
    $args = array(
        'name' => __( 'External Settings', 'ninja-forms-uploads' ),
        'page' => 'ninja-forms-uploads',
        'display_function' => '',
        'save_function' => 'ninja_forms_save_upload_settings',
        'tab_reload' => true,
    );
    if( function_exists( 'ninja_forms_register_tab' ) ){
        ninja_forms_register_tab('external_settings', $args);
    }
}

add_action('admin_init', 'ninja_forms_external_url');
function ninja_forms_external_url() {
	if ( isset( $_GET['nf-upload'] ) ) {
		$args = array(
			'id' => $_GET['nf-upload']
		);
		$upload = ninja_forms_get_uploads( $args );
		$external = NF_Upload_External::instance( $upload['data']['upload_location'] );
		if ( $external ) {
			$path = ( isset( $upload['data']['external_path'] ) ) ? $upload['data']['external_path'] : '';
			$file_url = $external->file_url( $upload['data']['file_name'], $path );
		}
		wp_redirect( $file_url );
		die();
	}
}

function ninja_forms_upload_file_url( $data ) {
	$file_url = $data['file_url'];
	if ( isset( $data['upload_location'] ) && $data['upload_location'] != NINJA_FORMS_UPLOADS_DEFAULT_LOCATION ) {
		$external = NF_Upload_External::instance( $data['upload_location'] );
		if ( $external && $external->is_connected() ) {
			$file_url = admin_url( '?nf-upload='. $data['upload_id'] );
		}
	}

	return $file_url;
}