<?php if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class NF_FU_Integrations_NinjaForms_MergeTags {

	/**
	 * NF_FU_Integrations_NinjaForms_MergeTags constructor.
	 */
	public function __construct() {
		add_filter( 'ninja_forms_merge_tag_value_' . NF_FU_File_Uploads::TYPE, array( $this, 'merge_tag_value' ), 10, 2 );
	}

	/**
	 * Format the file URLs to links using the filename as link text
	 *
	 * @param string $value
	 * @param array $field
	 *
	 * @return string
	 */
	public function merge_tag_value( $value, $field ) {
		if ( is_null( $value ) ) {
			return $value;
		}

		if ( ! is_array( $value ) ) {
			$value = explode( ',', $value );
		}

		$return = array();
		foreach ( $value as $url ) {
			if ( ! isset( $field['files'] ) ) {
				continue;
			}

			foreach ( $field['files'] as $file ) {
				if ( trim( $url ) !== $file['data']['file_url'] ) {
					continue;
				}

				$upload = NF_File_Uploads()->controllers->uploads->get( $file['data']['upload_id'] );

				if ( false === $upload ) {
					continue;
				}

				$file_url = NF_File_Uploads()->controllers->uploads->get_file_url( $upload->file_url, $upload->data );


				$return[] = sprintf( '<a href="%s" target="_blank">%s</a>', $file_url, $upload->file_name );
			}
		}

		if ( empty( $return ) ) {
			return is_array( $value ) ? $value[0] : $value;
		}

		return implode( '<br>', $return );
	}
}
