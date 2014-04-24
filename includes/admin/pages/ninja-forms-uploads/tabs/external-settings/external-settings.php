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
			$file_url = $external->file_url( $upload['data']['file_name'] );
		}
		wp_redirect( $file_url );
		die();
	}
}