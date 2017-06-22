<?php

namespace Polevaultweb\WP_OAuth2;

class WP_OAuth2 {

	public static function get_disconnect_url( $provider, $url ) {
		$url = add_query_arg( array( 'wp-oauth2' => $provider, 'action' => 'disconnect' ), $url );

		return $url;
	}

	public static function get_access_token( $provider ) {
		$token = new Access_Token( $provider );
		$token->get();

		return $token;
	}

	public static function is_authorized( $provider ) {
		$token = self::get_access_token( $provider );

		return (bool) $token;
	}
}