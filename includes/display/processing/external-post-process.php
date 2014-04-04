<?php
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
                            $filename = $file['file_path'] . $file['file_name'];

                            require_once(NINJA_FORMS_UPLOADS_DIR. "/includes/lib/dropbox/dropbox.php");
                            $dropbox = new nf_dropbox();
                            $dropbox->upload_file( $filename );
                        }
                    }
                }
            }
        }
    }
}
add_action( 'ninja_forms_post_process', 'ninja_forms_check_add_to_dropbox' );

function ninja_forms_check_add_to_s3( $form_id ){
    $s3_settings = ninja_forms_upload_s3_connected();
    if ( !$s3_settings ) return;
    global $ninja_forms_processing;
    if ( $ninja_forms_processing->get_form_setting( 'create_post' ) != 1 ) {
        if( $ninja_forms_processing->get_extra_value( 'uploads' ) ){
            foreach( $ninja_forms_processing->get_extra_value( 'uploads' ) as $field_id ){

                $field_row = $ninja_forms_processing->get_field_settings( $field_id );
                $user_value = $ninja_forms_processing->get_field_value( $field_id );
                if( isset( $field_row['data']['s3'] ) AND $field_row['data']['s3'] == 1 ){

                    if( is_array( $user_value ) ){
                        foreach( $user_value as $key => $file ){
                            $filename = $file['file_path'] . $file['file_name'];

                            require_once(NINJA_FORMS_UPLOADS_DIR."/includes/lib/s3/s3.php");
                            $s3 = new S3( $s3_settings['access_key'], $s3_settings['secret_key'] );

                            $s3->putObjectFile($filename, $s3_settings['bucket_name'], 'ninja-forms/' .baseName($filename), S3::ACL_PUBLIC_READ);
                        }
                    }
                }
            }
        }
    }
}
add_action( 'ninja_forms_post_process', 'ninja_forms_check_add_to_s3' );
