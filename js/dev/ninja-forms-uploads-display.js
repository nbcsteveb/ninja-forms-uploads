jQuery(document).ready(function(jQuery) {
		
	/* * * Begin File Upload JS * * */

	jQuery(".ninja-forms-change-file-upload").click(function(e){
		e.preventDefault();
		var file_upload_id = this.id.replace('ninja_forms_change_file_upload_', '');
		jQuery("#ninja_forms_file_upload_" + file_upload_id).toggle();
	});


	jQuery(".ninja-forms-delete-file-upload").click(function(e){
		e.preventDefault();
		//var answer = confirm( ninja_forms_uploads_settings.delete );
		//if(answer){
			var file_upload_li = this.id.replace('_delete', '' );
			file_upload_li += "_li";
			jQuery("#" + file_upload_li).fadeOut('fast', function(){
				jQuery("#" + file_upload_li).remove();
			});
						
		//}
	});

	/* * * End File Upload JS * * */
});