<?php

require_once('oauth/provider.php');

if (class_exists('ninja_forms_oauth_provider')) {
	class ninja_forms_dropbox extends ninja_forms_oauth_provider {
	  
		public 	$host = 'https://api-content.dropbox.com/1/';
		public  $format;
		private $access_token_url = 'https://api.dropbox.com/1/oauth2/token';
		private $authenticate_token_url = '';
		private $authorize_url = 'https://api.dropbox.com/1/oauth2/authorize';
		private $request_token_url = '';
		
	 	private $consumer_key = 'lmrj7v0rqo3jrju';
	 	private $consumer_secret = 'iwriz5trgpg43cd';
	 	private $redirect_uri = 'http://localhost/sandbox/dropbox/dropbox.php';
	 		
	 	function __construct($oauth_token = NULL, $oauth_token_secret = NULL) {
	 	
	 		parent::__construct(	$this->host,
	 								$this->format,
	 								$this->access_token_url,
	 								$this->authenticate_token_url,
	 								$this->authorize_url,
	 								$this->request_token_url,
	 								$this->consumer_key,
	 								$this->consumer_secret,
	 								$oauth_token,
	 								$oauth_token_secret
	 							);
		}
		
		function getFormat($url) { return "{$this->host}{$url}"; }

		function get_authorise_url($callback = '', $source = 'dropbox') {
			$_SESSION[$source .'_oauth_token'] = $source .'_token';
			$_SESSION[$source .'_oauth_token_secret'] = $source .'_secret';
			$redirect = $this->redirect_uri;
			$state = base64_encode($callback);	
			$params = array(
			    'response_type' => 'code',
			    'client_id' => $this->consumer_key,
			    'redirect_uri' => $redirect,
			    'state' => $state
			);
			$url = $this->authorize_url.'?'. ninja_forms_OAuthUtil::build_http_query($params);
			return $url;
		}
		
	    function getAccessToken($oauth_verifier = FALSE, $return_uri = NULL) {	
			$state = base64_encode($return_uri);
		    $redirect_uri = $this->redirect_uri;
		    $parameters = array(
		    	'client_id' => $this->consumer_key, 
		    	'client_secret' => $this->consumer_secret, 
		    	'code' => $oauth_verifier, 
		    	'redirect_uri' => $redirect_uri, 
		    	'grant_type' => 'authorization_code',
		    );
		    $request = $this->post($this->accessTokenUrl(), $parameters);
	        $token = json_decode($request);
            $access_token = '';
            if (!$token || $token == '') {
		        parse_str($request);
	        } else {
		        $access_token = $token->access_token;
	        }
	       return $access_token;
		}

		function uploadFile($filename, $file) {
			$url = 'files_put/dropbox/apps/NF Test/'. $filename;
			$parameters = array();
			$response = $this->oAuthRequest($url, 'PUT', $parameters, $file);
			$response = json_decode($response);
            _log($response);
			return (isset($response->url)) ? $response->url : '';			
		}
	}
}
