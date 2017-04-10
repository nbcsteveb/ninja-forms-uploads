<?php if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class NF_FU_Integrations_NinjaForms_MergeTags {

	/**
	 * NF_FU_Integrations_NinjaForms_MergeTags constructor.
	 */
	public function __construct() {
		add_filter( 'ninja_forms_merge_tag_value_' . NF_FU_File_Uploads::TYPE, array( $this, 'merge_tag_value' ), 10, 2 );
		add_action( 'ninja_forms_uploads_external_action_post_process', array( $this, 'update_mergetags_for_external' ), 10, 2 );
	}

	/**
	 * Format the file URLs to links using the filename as link text
	 *
	 * @param string $value
	 * @param array  $field
	 *
	 * @return string
	 */
	public function merge_tag_value( $value, $field ) {
		if ( is_null( $value ) ) {
			return $value;
		}

		if ( ! isset( $field['files'] ) || empty( $field['files'] )  ) {
			return '';
		}

		$values = $this->get_values( $field );

		// Add plain mergetag
		$this->update_mergetags( $field, array( 'plain' => 'plain') );

		return $values['html'];
	}

	/**
	 * Update mergetag(s) value
	 *
	 * @param array $field
	 * @param array $tags Array keyed on field suffix ('default' for normal field), and value as the type of value, eg.
	 *                    html or plain
	 */
	protected function update_mergetags( $field, $tags = array() ) {
		$all_merge_tags = Ninja_Forms()->merge_tags;

		if ( ! isset( $all_merge_tags['fields'] ) ) {
			return;
		}

		$values = $this->get_values( $field );

		foreach ( $tags as $type => $value_type ) {
			$tag    = '_' . $type;
			$suffix = ':' . $type;
			if ( 'default' === $type ) {
				$tag    = '';
				$suffix = '';
			}

			$value = isset( $values[ $value_type ] ) ? $values[ $value_type ] : $values['plain'];
			$all_merge_tags['fields']->add( 'field_' . $field['key'] . $tag, $field['key'], "{field:{$field['key']}{$suffix}}", $value );
		}

		// Save merge tags
		Ninja_Forms()->merge_tags = $all_merge_tags;
	}

	/**
	 * Get the formatted value sets for the mergetag value.
	 *
	 * @param array $field
	 *
	 * @return array
	 */
	protected function get_values( $field ) {
		$values = array();
		foreach ( $field['files'] as $file ) {
			$upload = NF_File_Uploads()->controllers->uploads->get( $file['data']['upload_id'] );

			if ( false === $upload ) {
				continue;
			}

			$file_url = NF_File_Uploads()->controllers->uploads->get_file_url( $upload->file_url, $upload->data );

			$values['html'][]  = sprintf( '<a href="%s" target="_blank">%s</a>', $file_url, $upload->file_name );
			$values['plain'][] = $file_url;
		}

		if ( isset( $values['html'] ) ) {
			$values['html'] = implode( '<br>', $values['html'] );
		}

		if ( isset( $values['plain'] ) ) {
			$values['plain'] = implode( ',', $values['plain'] );
		}

		return $values;
	}

	/**
	 * Update mergetags with external service URL values
	 *
	 * @param array  $field
	 * @param string $service
	 */
	public function update_mergetags_for_external( $field, $service ) {
		$tags = array(
			$service            => 'html',
			$service . '_plain' => 'plain',
			'default'           => 'html',
			'plain'             => 'plain',
		);

		$this->update_mergetags( $field, $tags );
	}
}