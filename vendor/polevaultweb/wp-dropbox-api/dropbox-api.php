<?php

class PVW_Dropbox_API {

	const API_URL_BASE = 'https://api.dropboxapi.com/2/';
	const CONTENT_URL_BASE = 'https://content.dropboxapi.com/2/';
	protected $access_token;

	public function __construct( $access_token ) {
		$this->access_token = $access_token;
	}

	public function get_account_info() {
		$endpoint = 'users/get_current_account';

		$args = array(
			'headers' => array( 'Content-Type' => 'application/json', ),
			'body'    => json_encode( null ),
		);

		return $this->post( $endpoint, $args, false );
	}

	public function put_file( $file, $filename, $path ) {
		$endpoint = 'files/upload';

		$path = trailingslashit( $path ) . $filename;

		$data = array( 'path' => $path, 'mode' => 'overwrite' );

		$args = array(
			'headers'     => array(
				'Content-Type'    => 'application/octet-stream',
				'Dropbox-API-Arg' => json_encode( $data ),
			),
			'data-binary' => '@' . $file,
		);

		return $this->post( $endpoint, $args );
	}

	public function get_url( $path ) {
		$endpoint = 'files/get_temporary_link';

		$path = '/' . ltrim( $path, '/' );

		$args = array(
			'headers' => array( 'Content-Type' => 'application/json', ),
			'body'    => json_encode( array( 'path' => $path ) ),
		);

		return $this->post( $endpoint, $args, false );
	}

	protected function post( $endpoint, $args, $content_api = true ) {
		$url      = ( $content_api ? self::CONTENT_URL_BASE : self::API_URL_BASE ) . $endpoint;
		$defaults = array(
			'headers' => array(
				'Authorization' => 'Bearer ' . $this->access_token,
			),
		);

		$args   = array_merge_recursive( $args, $defaults );
		$result = wp_remote_post( $url, $args );

		$response = wp_remote_retrieve_body( $result );
		if ( empty( $response ) ) {
			return false;
		}

		$body = json_decode( $response );
		if ( empty( $body ) ) {
			return false;
		}

		return $body;
	}
}