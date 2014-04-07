<?php

add_action('admin_init', 'ninja_forms_register_tab_external_settings');
function ninja_forms_register_tab_external_settings(){
    $args = array(
        'name' => 'External Settings',
        'page' => 'ninja-forms-uploads',
        'display_function' => '',
        'save_function' => 'ninja_forms_save_upload_settings',
        'tab_reload' => true,
    );
    if( function_exists( 'ninja_forms_register_tab' ) ){
        ninja_forms_register_tab('external_settings', $args);
    }
}