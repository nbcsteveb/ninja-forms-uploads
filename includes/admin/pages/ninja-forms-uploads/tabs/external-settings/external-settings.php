<?php

require_once(NINJA_FORMS_UPLOADS_DIR. "/includes/lib/dropbox/dropbox.php");

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
            array(
                'name' => 'amazon_s3_bucket_name',
                'type' => 'text',
                'label' => __( 'Bucket Name', 'ninja-forms' ),
                'desc' => '',
            ),
        ),
    );
    if( function_exists( 'ninja_forms_register_tab_metabox' ) ){
        ninja_forms_register_tab_metabox($args);
    }
}

function ninja_forms_upload_dropbox_connected($data = null) {
    $dropbox = new nf_dropbox();
    return $dropbox->is_authorized();
}

function ninja_forms_upload_s3_connected($data = null) {
    if (!$data) {
        $data = get_option( 'ninja_forms_settings' );
    }
    if ( (isset($data['amazon_s3_access_key']) && $data['amazon_s3_access_key'] != '') &&
            (isset($data['amazon_s3_secret_key']) && $data['amazon_s3_secret_key'] != '') &&
                (isset($data['amazon_s3_bucket_name']) && $data['amazon_s3_bucket_name'] != '')
    ) {
        $settings['access_key'] = $data['amazon_s3_access_key'];
        $settings['secret_key'] = $data['amazon_s3_secret_key'];
        $settings['bucket_name'] = $data['amazon_s3_bucket_name'];
        return $settings;
    }
    return false;
}

function ninja_forms_upload_dropbox_connect_url($form_id, $data) {
    $dropbox = new nf_dropbox();
    $callback_url = admin_url('/admin.php?page=ninja-forms-uploads&tab=external_settings');
    $disconnect_url = admin_url('/admin.php?page=ninja-forms-uploads&tab=external_settings&action=disconnect_dropbox');
    if ( $dropbox->is_authorized() ) { ?>
        <a id="dropbox-disconnect"  href="<?php echo $disconnect_url; ?>" class="button-secondary">Disconnect</a>
    <?php } else { ?>
        <a id="dropbox-connect" href="<?php echo $dropbox->get_authorize_url( $callback_url ); ?>" class="button-secondary">Connect</a>
    <?php } ?>
<?php
}

function ninja_forms_upload_dropbox_connect_notice(){
    if(isset($_GET['page']) && $_GET['page'] == 'ninja-forms-uploads' &&
        isset($_GET['tab']) && $_GET['tab'] == 'external_settings' &&
        isset($_GET['oauth_token']) && isset($_GET['uid']) ) {
        echo '<div class="updated">
             <p>Connected to Dropbox</p>
         </div>';
    }
}
add_action('admin_notices', 'ninja_forms_upload_dropbox_connect_notice');

add_action( 'admin_init', 'ninja_forms_upload_dropbox_disconnect' );
function ninja_forms_upload_dropbox_disconnect() {
    if(isset($_GET['page']) && $_GET['page'] == 'ninja-forms-uploads' &&
        isset($_GET['tab']) && $_GET['tab'] == 'external_settings' &&
        isset($_GET['action']) && $_GET['action'] == 'disconnect_dropbox') {

        $dropbox = new nf_dropbox();
        $dropbox->unlink_account();
    }
}

function ninja_forms_upload_dropbox_disconnect_notice(){
    if(isset($_GET['page']) && $_GET['page'] == 'ninja-forms-uploads' &&
        isset($_GET['tab']) && $_GET['tab'] == 'external_settings' &&
        isset($_GET['action']) && $_GET['action'] == 'disconnect_dropbox') {
        echo '<div class="updated">
             <p>Disconnected from Dropbox</p>
         </div>';
    }
}
add_action('admin_notices', 'ninja_forms_upload_dropbox_disconnect_notice');
