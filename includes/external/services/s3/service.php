<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class NF_FU_External_S3_Service
 */
class NF_FU_External_Services_S3_Service extends NF_FU_External_Abstracts_Service {

	public $name = 'Amazon S3';

	protected $library_class = 'S3';

	protected $library_file = 'tpyo/amazon-s3-php-class/s3.php';

	protected static $clients = array();

	/**
	 * Is the service connected?
	 *
	 * @param null|array $settings
	 *
	 * @return bool
	 */
	public function is_connected( $settings = null ) {
		if ( is_null( $settings ) ) {
			$settings = $this->load_settings();
		}

		foreach ( $settings as $key => $value ) {
			if ( 'amazon_s3_file_path' === $key ) {
				continue;
			}

			if ( ! is_array( $value ) && '' === trim( $value ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Load S3 settings and ensure we have the region for the bucket
	 *
	 * @return array
	 */
	public function load_settings() {
		$settings = parent::load_settings();

		if ( ! $this->is_connected( $settings ) ) {
			return $settings;
		}

		$bucket = $settings['amazon_s3_bucket_name'];

		$data = NF_File_Uploads()->controllers->settings->get_settings();

		if ( ( ! isset( $data['amazon_s3_bucket_region'][ $bucket ] ) || empty( $data['amazon_s3_bucket_region'][ $bucket ] ) ) ) {
			// Retrieve the bucket region if we don't have it
			// Or the bucket has changed since we last retrieved it
			$s3     = new NF_FU_Library_S3( $settings['amazon_s3_access_key'], $settings['amazon_s3_secret_key'] );
			$region = $s3->getBucketLocation( $bucket );

			$this->settings['amazon_s3_bucket_region'][ $bucket ] = $region;

			$data['amazon_s3_bucket_region'] = $this->settings['amazon_s3_bucket_region'];
			update_option( 'ninja_forms_settings', $data );
		}

		return $this->settings;
	}

	/**
	 * Get the S3 client
	 *
	 * @param string $region
	 *
	 * @return NF_FU_Library_S3
	 */
	protected function get_client( $region = '' ) {
		if ( '' === $region ) {
			$region = 'US';
		}

		if ( ! isset( self::$clients[ $region ] ) ) {

			$this->load_settings();

			$s3 = new NF_FU_Library_S3( $this->settings['amazon_s3_access_key'], $this->settings['amazon_s3_secret_key'] );

			if ( '' !== $region && 'US' !== $region ) {
				// Use the correct API endpoint for non US standard bucket regions
				$s3->setEndpoint( 's3-' . $region . '.amazonaws.com' );
			}

			self::$clients[ $region ] = $s3;
		}

		return self::$clients[ $region ];
	}

	/**
	 * Get region of configured bucket
	 *
	 *
	 * @return string
	 */
	protected function get_region() {
		$bucket = $this->settings['amazon_s3_bucket_name'];
		$data   = NF_File_Uploads()->controllers->settings->get_settings();
		$region = isset( $data['amazon_s3_bucket_region'][ $bucket ] ) ? $data['amazon_s3_bucket_region'][ $bucket ] : '';

		return $region;
	}

	/**
	 * Get path on S3 to upload to
	 *
	 * @return string
	 */
	protected function get_path_setting() {
		return 'amazon_s3_file_path';
	}

	/**
	 * Upload the file to S3
	 *
	 * @param array $data
	 *
	 * @return array|bool
	 */
	public function upload_file( $data ) {
		$bucket = $this->settings['amazon_s3_bucket_name'];
		$region = $this->get_region();
		$s3     = $this->get_client( $region );

		$result = $s3->putObjectFile( $this->upload_file, $bucket, $this->external_path . $this->external_filename, NF_FU_Library_S3::ACL_PUBLIC_READ );

		if ( false === $result ) {
			return false;
		}

		$data['bucket'] = $bucket;
		$data['region'] = $region;

		return $data;
	}

	/**
	 * Get the Amazon S3 URL using bucket and region for the file, falling
	 * back to the settings bucket and region
	 *
	 * @param string $filename
	 * @param string $path
	 * @param array  $data
	 *
	 * @return string
	 */
	public function get_url( $filename, $path = '', $data = array() ) {
		$bucket = ( isset( $data['bucket'] ) ) ? $data['bucket'] : $this->settings['amazon_s3_bucket_name'];
		$region = ( isset( $data['region'] ) ) ? $data['region'] : $this->get_region();

		$s3 = $this->get_client( $region );

		return $s3->getAuthenticatedURL( $bucket, $path . $filename, 3600 );
	}
}
