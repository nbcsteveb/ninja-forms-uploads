<?php
/*
Plugin Name: Ninja Forms - File Uploads
Plugin URI: http://wpninjas.com
Description: File Uploads add-on for Ninja Forms.
Version: 1.0.5
Author: The WP Ninjas
Author URI: http://wpninjas.com
*/
global $wpdb;

define("NINJA_FORMS_UPLOADS_DIR", WP_PLUGIN_DIR."/ninja-forms-uploads");
define("NINJA_FORMS_UPLOADS_URL", plugins_url()."/ninja-forms-uploads");
define("NINJA_FORMS_UPLOADS_TABLE_NAME", $wpdb->prefix . "ninja_forms_uploads");
define("NINJA_FORMS_UPLOADS_VERSION", "1.0.5");

// this is the URL our updater / license checker pings. This should be the URL of the site with EDD installed
define( 'NINJA_FORMS_UPLOADS_EDD_SL_STORE_URL', 'http://wpninjas.com' ); // IMPORTANT: change the name of this constant to something unique to prevent conflicts with other plugins using this system

// the name of your product. This is the title of your product in EDD and should match the download title in EDD exactly
define( 'NINJA_FORMS_UPLOADS_EDD_SL_ITEM_NAME', 'File Uploads' ); // IMPORTANT: change the name of this constant to something unique to prevent conflicts with other plugins using this system

//Require EDD autoupdate file
if( !class_exists( 'EDD_SL_Plugin_Updater' ) ) {
	// load our custom updater if it doesn't already exist
	require_once( NINJA_FORMS_UPLOADS_DIR.'/includes/EDD_SL_Plugin_Updater.php' );
}

$plugin_settings = get_option( 'ninja_forms_settings' );

if( isset( $plugin_settings['uploads_version'] ) ){
	$current_version = $plugin_settings['uploads_version'];
}else{
	$current_version = 0.4;
}

// retrieve our license key from the DB
if( isset( $plugin_settings['uploads_license'] ) ){
	$uploads_license = $plugin_settings['uploads_license'];
}else{
	$uploads_license = '';
}

// setup the updater
$edd_updater = new EDD_SL_Plugin_Updater( NINJA_FORMS_UPLOADS_EDD_SL_STORE_URL, __FILE__, array(
		'version' 	=> NINJA_FORMS_UPLOADS_VERSION, 		// current version number
		'license' 	=> $uploads_license, 	// license key (used get_option above to retrieve from DB)
		'item_name'     => NINJA_FORMS_UPLOADS_EDD_SL_ITEM_NAME, 	// name of this plugin
		'author' 	=> 'WP Ninjas'  // author of this plugin
	)
);

require_once(NINJA_FORMS_UPLOADS_DIR."/includes/admin/pages/ninja-forms-uploads/tabs/browse-uploads/browse-uploads.php");
require_once(NINJA_FORMS_UPLOADS_DIR."/includes/admin/pages/ninja-forms-uploads/tabs/browse-uploads/sidebars/select-uploads.php");
require_once(NINJA_FORMS_UPLOADS_DIR."/includes/admin/pages/ninja-forms-uploads/tabs/upload-settings/upload-settings.php");
require_once(NINJA_FORMS_UPLOADS_DIR."/includes/admin/scripts.php");
require_once(NINJA_FORMS_UPLOADS_DIR."/includes/admin/help.php");
require_once(NINJA_FORMS_UPLOADS_DIR."/includes/admin/license-option.php");

require_once(NINJA_FORMS_UPLOADS_DIR."/includes/display/processing/pre-process.php");
require_once(NINJA_FORMS_UPLOADS_DIR."/includes/display/processing/process.php");
require_once(NINJA_FORMS_UPLOADS_DIR."/includes/display/processing/attach-image.php");
require_once(NINJA_FORMS_UPLOADS_DIR."/includes/display/processing/shortcode-filter.php");
require_once(NINJA_FORMS_UPLOADS_DIR."/includes/display/scripts.php");
require_once(NINJA_FORMS_UPLOADS_DIR."/includes/display/mp-confirm-filter.php");

require_once(NINJA_FORMS_UPLOADS_DIR."/includes/fields/file-uploads.php");

require_once(NINJA_FORMS_UPLOADS_DIR."/includes/activation.php");
require_once(NINJA_FORMS_UPLOADS_DIR."/includes/ajax.php");
require_once(NINJA_FORMS_UPLOADS_DIR."/includes/functions.php");


//Add File Uploads to the admin menu
add_action('admin_menu', 'ninja_forms_add_upload_menu', 99);
function ninja_forms_add_upload_menu(){
	$capabilities = 'administrator';
	$capabilities = apply_filters( 'ninja_forms_admin_menu_capabilities', $capabilities );

	$uploads = add_submenu_page("ninja-forms", "File Uploads", "File Uploads", $capabilities, "ninja-forms-uploads", "ninja_forms_admin");
	add_action('admin_print_styles-' . $uploads, 'ninja_forms_admin_js');
	add_action('admin_print_styles-' . $uploads, 'ninja_forms_uploads_admin_js');
	add_action('admin_print_styles-' . $uploads, 'ninja_forms_admin_css');
}

register_activation_hook( __FILE__, 'ninja_forms_uploads_activation' );

if( version_compare( $current_version, '0.5', '<' ) ){
	ninja_forms_uploads_activation();
}