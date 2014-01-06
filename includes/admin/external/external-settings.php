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

add_action( 'admin_init', 'ninja_forms_register_external_settings_metabox');
function ninja_forms_register_external_settings_metabox(){
    $args = array(
        'page' => 'ninja-forms-uploads',
        'tab' => 'external_settings',
        'slug' => 'dropbox_settings',
        'title' => __('Dropbox Settings', 'ninja-forms'),
        'settings' => array(
            array(
                'name' => 'dropbox_connect',
                'type' => '',
                'label' => __( 'Connect to Dropbox', 'ninja-forms' ),
                'desc' => '',
                'display_function' => 'ninja_forms_upload_dropbox_connect_url'
            ),
            array(
                'name' => 'dropbox_token',
                'type' => 'hidden',
            ),
        ),
    );
    if( function_exists( 'ninja_forms_register_tab_metabox' ) ){
        ninja_forms_register_tab_metabox($args);
    }

    $args = array(
        'page' => 'ninja-forms-uploads',
        'tab' => 'external_settings',
        'slug' => 'amazons3_settings',
        'title' => __('Amazon S3 Settings', 'ninja-forms'),
        'settings' => array(
            array(
                'name' => 'amazon_s3_access_key',
                'type' => 'text',
                'label' => __( 'Access Key', 'ninja-forms' ),
                'desc' => '',
            ),
            array(
                'name' => 'amazon_s3_secret_key',
                'type' => 'text',
                'label' => __( 'Secret Key', 'ninja-forms' ),
                'desc' => '',
            ),
        ),
    );
    if( function_exists( 'ninja_forms_register_tab_metabox' ) ){
        ninja_forms_register_tab_metabox($args);
    }
}

function ninja_forms_upload_dropbox_connected($data = null) {
    if (!$data) {
        $data = get_option( 'ninja_forms_settings' );
    }
    if (isset($data['dropbox_token']) && $data['dropbox_token'] != '') {
        return true;
    }
    return false;
}

function ninja_forms_upload_dropbox_token() {
    $data = get_option( 'ninja_forms_settings' );
    $access_token = array();
    if (isset($data['dropbox_token']) && $data['dropbox_token'] != '') {
        $access_token['oauth_token'] = $data['dropbox_token'];
        $access_token['oauth_token_secret'] = 'nosecret';
    }
    return $access_token;
}

function ninja_forms_upload_dropbox_connect_url($form_id, $data) {
    if ( ninja_forms_upload_dropbox_connected($data) ) {
        $url = admin_url('/admin.php?page=ninja-forms-uploads&tab=external_settings&action=disconnect_dropbox');
        ?>
        <a href="<?php echo $url; ?>" class="button-secondary">Disconnect</a>
    <?php
    } else {
        $dropbox = new ninja_forms_dropbox();
        $redirect = admin_url('/admin.php?page=ninja-forms-uploads&tab=external_settings');
        $url = $dropbox->get_authorise_url($redirect);
        ?>
        <a href="<?php echo $url; ?>" class="button-secondary">Connect</a>
        <?php
    }
}

add_action( 'admin_init', 'ninja_forms_upload_dropbox_disconnect' );
function ninja_forms_upload_dropbox_disconnect() {
    if(isset($_GET['page']) && $_GET['page'] == 'ninja-forms-uploads' &&
        isset($_GET['tab']) && $_GET['tab'] == 'external_settings' &&
        isset($_GET['action']) && $_GET['action'] == 'disconnect_dropbox') {

        $options = get_option( 'ninja_forms_settings' );
        $options['dropbox_token'] = '';
        update_option('ninja_forms_settings', $options);
    }
}

add_action( 'admin_init', 'ninja_forms_upload_dropbox_connect' );
function ninja_forms_upload_dropbox_connect() {
    if(isset($_GET['page']) && $_GET['page'] == 'ninja-forms-uploads' &&
        isset($_GET['tab']) && $_GET['tab'] == 'external_settings' &&
            isset($_GET['type']) && $_GET['type'] == 'dropbox') {

        $options = get_option( 'ninja_forms_settings' );
        $source = $_GET['type'];
        $request_code = $_GET['oauth_verifier'];

        if (isset($_SESSION[$source .'_oauth_token']) && isset($_SESSION[$source .'_oauth_token_secret'])) {

            $auth_token = $_SESSION[$source .'_oauth_token'];
            $auth_token_secret = $_SESSION[$source .'_oauth_token_secret'];

            $dropbox = new ninja_forms_dropbox($auth_token, $auth_token_secret);
            $token = $dropbox->getAccessToken($request_code);

             if($token != '') {
                $options['dropbox_token'] = $token;
                update_option('ninja_forms_settings', $options);

                if (isset($_SESSION[$source .'_oauth_token'])) unset($_SESSION[$source .'_oauth_token']);
                if (isset($_SESSION[$source .'_oauth_token_secret'])) unset($_SESSION[$source .'_oauth_token_secret']);
            }
        }
    }
}

function ninja_forms_check_add_to_dropbox( $form_id ){
    if (!ninja_forms_upload_dropbox_connected()) return;
    global $ninja_forms_processing;
    if ( $ninja_forms_processing->get_form_setting( 'create_post' ) != 1 ) {
        if( $ninja_forms_processing->get_extra_value( 'uploads' ) ){
            foreach( $ninja_forms_processing->get_extra_value( 'uploads' ) as $field_id ){

                $field_row = $ninja_forms_processing->get_field_settings( $field_id );
                $user_value = $ninja_forms_processing->get_field_value( $field_id );
                if( isset( $field_row['data']['dropbox'] ) AND $field_row['data']['dropbox'] == 1 ){

                    if( is_array( $user_value ) ){
                        foreach( $user_value as $key => $file ){
                            $filename = $file['file_path'].$file['file_name'];
                            $access_token = ninja_forms_upload_dropbox_token();
                            $dropbox = new ninja_forms_dropbox($access_token['oauth_token'], $access_token['oauth_token_secret']);

                            $response = $dropbox->uploadFile($file['file_name'], $filename);
                            _log('Add file '. $filename . ' to dropbox');
                        }
                    }
                }
            }
        }
    }
}
add_action( 'ninja_forms_post_process', 'ninja_forms_check_add_to_dropbox' );
