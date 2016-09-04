<?php if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class NF_FU_Integrations_NinjaForms_Submissions {

	/**
	 * NF_FU_Integrations_NinjaForms constructor.
	 */
	public function __construct() {
		add_filter( 'ninja_forms_custom_columns', array( $this, 'submission_table_row_value' ), 10, 2 );

		// TODO Edit sub value
		// TODO Front end editor edit sub
		// TODO Export submissions
		// TODO PDF?
	}

	/**
	 * Display the upload file URL as an HTML link for each uploaded file to the submission.
	 * Submission table row td.
	 *
	 * @param mixed $value
	 * @param array $field
	 *
	 * @return string
	 */
	public function submission_table_row_value( $value, $field ) {
		if ( NF_FU_File_Uploads::TYPE !== $field->get_setting( 'type' ) ) {
			return $value;
		}

		if ( ! is_array( $value ) ) {
			return $value;
		}

		$value = NF_File_Uploads()->normalize_submission_value( $value );

		$new_value = array();
		foreach ( $value as $upload_id => $file_url ) {
			$upload = NF_File_Uploads()->controllers->uploads->get( $upload_id );

			if ( false === $upload ) {
				continue;
			}

			$file_url    = NF_File_Uploads()->controllers->uploads->get_file_url( $file_url, $upload->data );
			$new_value[] = sprintf( '<a href="%s" target="_blank">%s</a>', $file_url, $upload->file_name );
		}

		return implode( '<br>', $new_value );
	}
}