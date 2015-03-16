<?php

if (!class_exists('BaseOAuthImpl')) {

	require_once dirname(__FILE__).'/OAuth.php';
	
	abstract class BaseOAuthImpl {
		/* Contains the last HTTP status code returned. */
		public $http_code;
		/* Contains the last API call. */
		public $url;
		/* Set up the API root URL. */
		public $host;  
		/* Set timeout default. */
		public $timeout = 30;
		/* Set connect timeout. */
		public $connecttimeout = 30;
		/* Verify SSL Cert. */
		public $ssl_verifypeer = FALSE;
		/* Respons format. */
		public $format = 'json';
		/* Decode returned json data. */
		public $decode_json = TRUE;
		/* Contains the last HTTP headers returned. */
		public $http_info;
		/* Set the useragnet. */
		public $useragent; // 
		/* Immediately retry the API call if the response was not successful. */
		//public $retry = TRUE;
		public $error_message='';
		
		public $row_content;
		public $additional_params;
		public $oauth_nonce_length;
		public $pass_params_array;
		/**
		* construct TumblrOAuth object
		*/
		function __construct($consumer_key, $consumer_secret, $oauth_token = NULL, $oauth_token_secret = NULL) {
			$this->sha1_method = new OAuthSignatureMethod_HMAC_SHA1();
			$this->consumer = new OAuthConsumer($consumer_key, $consumer_secret);
			if (!empty($oauth_token) && !empty($oauth_token_secret)) {
				$this->token = new OAuthConsumer($oauth_token, $oauth_token_secret);
			} else {
				$this->token = NULL;
			}
			//
			$this->useragent = $this->getUserAgent();
			$this->host = $this->getHost();
		}
		
		abstract function getHost();
		abstract function getUserAgent();
		
		/**
		* Set API URLS
		*/
		abstract function accessTokenURL();
		abstract function authenticateURL();
		abstract function authorizeURL();
		abstract function requestTokenURL();

		/**
		* Debug helpers
		*/
		function lastStatusCode() {
			return $this->http_status;
		}
		function lastAPICall() {
			return $this->last_api_call;
		}
		
		/**
		* Get a request_token
		*
		* @returns a key/value array containing oauth_token and oauth_token_secret
		*/
		function getRequestToken($oauth_callback = NULL) {
			$parameters = array();
			if (!empty($oauth_callback)) {
				$parameters['oauth_callback'] = $oauth_callback;
			}
			$request = $this->oAuthRequest($this->requestTokenURL(), 'GET', $parameters);
			$token = OAuthUtil::parse_parameters($request);
			$this->token = new OAuthConsumer(((isset($token['oauth_token']))?$token['oauth_token']:''), ((isset($token['oauth_token_secret']))?$token['oauth_token_secret']:''));
			return $token;
		}
		
		/**
		* Get the authorize URL
		*
		* @returns a string
		*/
		function getAuthorizeURL($token, $sign_in = TRUE) {
			if (is_array($token)) {
				$token = (isset($token['oauth_token']))?$token['oauth_token']:'';
			}
			if (empty($sign_in)) {
				return $this->authorizeURL() . "?oauth_token={$token}";
			} else {
				return $this->authenticateURL() . "?oauth_token={$token}";
			}
		}
		
		/**
		* Exchange request token and secret for an access token and
		* secret, to sign API calls.
		*
		* @returns array("oauth_token" => "the-access-token",
		*                "oauth_token_secret" => "the-access-secret",
		*                "user_id" => "9436992",
		*                "screen_name" => "abraham")
		*/
		function getAccessToken($oauth_verifier = FALSE) {
			$parameters = array();
			if (!empty($oauth_verifier)) {
				$parameters['oauth_verifier'] = $oauth_verifier;
			}
			//echo 'URL:'.$this->accessTokenURL();
			$request = $this->oAuthRequest($this->accessTokenURL(), 'GET', $parameters);
			$token = OAuthUtil::parse_parameters($request);
			//var_dump($token);
			$this->token = new OAuthConsumer($token['oauth_token'], $token['oauth_token_secret']);
			return $token;
		}
		
		/**
		* Format and sign an OAuth / API request
		*/
		function oAuthRequest($url, $method, $parameters, $multipart = false) {
			if (strrpos($url, 'https://') !== 0 && strrpos($url, 'http://') !== 0) {
				$url = "{$this->host}{$url}.{$this->format}";
			}
			if ($multipart) {
				$signature_parameters = array();
				// When making a multipart request, use only oauth_* -keys for signature
				foreach ($parameters as $key => $value) {
					if ($multipart && strpos($key, 'oauth_') !== 0) {
						continue;
					}
					$signature_parameters[$key] = $value;
				}
			} else {
				$signature_parameters = $parameters;
			}
				
			$request = OAuthRequest::from_consumer_and_token($this->consumer, $this->token, $method, $url, $signature_parameters);

			if (isset($this->oauth_nonce_length) && $this->oauth_nonce_length) {
				$oauth_nonce = $request->get_parameter('oauth_nonce');
				$request->set_parameter('oauth_nonce', substr($oauth_nonce, 0,8),false);
			}
			/*
			echo 'Before: <pre>';
			print_r($request->get_parameters());
			echo '</pre>';
			*/
			$request->sign_request($this->sha1_method, $this->consumer, $this->token);

			if (isset($this->additional_params) && is_array($this->additional_params)) {
				foreach ($this->additional_params as $k=>$v) {
					$request->set_parameter($k, $v);
				}
			}

			if (isset($this->additional_params) && is_array($this->additional_params)) {
				foreach ($this->additional_params as $k=>$v) {
					echo 'Adding params<br>';
					$request->set_parameter($k, $v, false);
				}
			}
			/*
			echo 'After: <pre>';
			print_r($request->get_parameters());
			echo '</pre>';
			//return;
			*/
			if (isset($this->pass_params_array) && $this->pass_params_array) {
				$post_data = $request->get_parameters(); //$request->to_postdata();
			} else {
				$post_data = $request->to_postdata();
			}
				
			switch ($method) {
				case 'GET':
					return $this->http($request->to_url(), 'GET');
				default:
					if ($multipart) return $this->http_multipart($request->get_normalized_http_url(), $method, ($multipart ? $parameters : $post_data), $request, $multipart);
					else return $this->http($request->get_normalized_http_url(), $method, $post_data);
						
			}
		}
		
		
		/**
		* One time exchange of username and password for access token and secret.
		*
		* @returns array("oauth_token" => "the-access-token",
		*                "oauth_token_secret" => "the-access-secret",
		*                "user_id" => "9436992",
		*                "screen_name" => "abraham",
		*                "x_auth_expires" => "0")
		*/
		function getXAuthToken($username, $password) {
			$parameters = array();
			$parameters['x_auth_username'] = $username;
			$parameters['x_auth_password'] = $password;
			$parameters['x_auth_mode'] = 'client_auth';
			$request = $this->oAuthRequest($this->accessTokenURL(), 'POST', $parameters);
			$token = OAuthUtil::parse_parameters($request);
			$this->token = new OAuthConsumer($token['oauth_token'], $token['oauth_token_secret']);
			return $token;
		}
		
		/**
		 * GET wrapper for oAuthRequest.
		 */
		function get($url, $parameters = array()) {
			$response = $this->oAuthRequest($url, 'GET', $parameters);
			if ($this->format === 'json' && $this->decode_json) {
				return json_decode($response);
			}
			return $response;
		}
		
		/**
		 * POST wrapper for oAuthRequest.
		 */
		function post($url, $parameters = array(), $multipart=false) {
			$response = $this->oAuthRequest($url, 'POST', $parameters, $multipart);
			$this->row_content = $response;
			if ($this->format === 'json' && $this->decode_json) {
				return json_decode($response);
			}
			return $response;
		}
		
		/**
		 * DELETE wrapper for oAuthReqeust.
		 */
		function delete($url, $parameters = array()) {
			$response = $this->oAuthRequest($url, 'DELETE', $parameters);
			if ($this->format === 'json' && $this->decode_json) {
				return json_decode($response);
			}
			return $response;
		}
		
		/**
		* Make an HTTP request
		*
		* @return API results
		*/
		function http($url, $method, $postfields = NULL, $bearer='') {
			$this->http_info = array();
			$ci = curl_init();
			/* Curl settings */
			
			curl_setopt($ci, CURLOPT_USERAGENT, $this->useragent);
			curl_setopt($ci, CURLOPT_CONNECTTIMEOUT, $this->connecttimeout);
			curl_setopt($ci, CURLOPT_TIMEOUT, $this->timeout);
			curl_setopt($ci, CURLOPT_RETURNTRANSFER, TRUE);
			curl_setopt($ci, CURLOPT_HTTPHEADER, array('Expect:'));
			curl_setopt($ci, CURLOPT_SSL_VERIFYPEER, $this->ssl_verifypeer);
			curl_setopt($ci, CURLOPT_HEADERFUNCTION, array($this, 'getHeader'));
			
			//curl_setopt($ci, CURLOPT_SSL_VERIFYPEER, true);
			//curl_setopt($ci, CURLOPT_SSL_VERIFYHOST, false);
			//curl_setopt ($ci, CURLOPT_CAINFO, "http://curl.haxx.se/ca/cacert.pem");
			curl_setopt($ci, CURLOPT_HEADER, TRUE);
				
			if(!ini_get('open_basedir') && !ini_get('safe_mode')) {
				curl_setopt($ci, CURLOPT_MAXREDIRS, 5);
				curl_setopt($ci, CURLOPT_FOLLOWLOCATION, 1);
			}
			if ($bearer) {
				//echo "<br/>BEARER<br/>";
				$headers = array('Authorization: Bearer ' . $bearer);
				curl_setopt($ci, CURLOPT_HTTPHEADER, $headers);
			}
			
			curl_setopt($ci, CURLOPT_HEADER, FALSE);
			//if (!$bearer)
				//curl_setopt($ci, CURLOPT_USERPWD, 'Q1HQMoGFwsYTeA:r7rUy_wKW2Cvnjmzle5H3FlUl8M');
				
			switch ($method) {
				case 'POST':
					curl_setopt($ci, CURLOPT_POST, TRUE);
					if (!empty($postfields)) {
						curl_setopt($ci, CURLOPT_POSTFIELDS, $postfields);
					}
					break;
				case 'DELETE':
					curl_setopt($ci, CURLOPT_CUSTOMREQUEST, 'DELETE');
					if (!empty($postfields)) {
						$url = "{$url}?{$postfields}";
					}
			}
			//echo "BaseOAuthImpl http() URL: $url<br/>";flush(); 
			//exit;
			curl_setopt($ci, CURLOPT_URL, $url);
			$response = curl_exec($ci);
			$this->http_code = curl_getinfo($ci, CURLINFO_HTTP_CODE);
			$this->http_info = array_merge($this->http_info, curl_getinfo($ci));
			$this->url = $url;
			//echo "ERROR: {$this->http_code} - $url - $postfields <br/>besponse:<br/> $response -".curl_error ( $ci ).'<br/>';
			curl_close ($ci);
			$this->row_content = $response;
			return $response;
		}
		
		function http_multipart($url, $method, $postfields = NULL, OAuthRequest $request = NULL, $multipart = false) {
			$this->http_info = array();
			$ci = curl_init();
			/* Curl settings */
			curl_setopt($ci, CURLOPT_USERAGENT, $this->useragent);
			curl_setopt($ci, CURLOPT_CONNECTTIMEOUT, $this->connecttimeout);
			curl_setopt($ci, CURLOPT_TIMEOUT, $this->timeout);
			curl_setopt($ci, CURLOPT_RETURNTRANSFER, TRUE);
			$headers = array('Expect:');
			if ($multipart) {
				$headers[] = $request->to_header();
			}
			curl_setopt($ci, CURLOPT_HTTPHEADER, $headers);
			curl_setopt($ci, CURLOPT_SSL_VERIFYPEER, $this->ssl_verifypeer);
			curl_setopt($ci, CURLOPT_HEADERFUNCTION, array($this, 'getHeader'));
			curl_setopt($ci, CURLOPT_HEADER, FALSE);
			switch ($method) {
				case 'POST':
					curl_setopt($ci, CURLOPT_POST, TRUE);
					if (!empty($postfields)) {
						curl_setopt($ci, CURLOPT_POSTFIELDS, $postfields);
					}
					break;
				case 'DELETE':
					curl_setopt($ci, CURLOPT_CUSTOMREQUEST, 'DELETE');
					if (!empty($postfields)) {
						$url = "{$url}?{$postfields}";
					}
			}
			curl_setopt($ci, CURLOPT_URL, $url);
			$response = curl_exec($ci);
			$this->row_content = $response;
			$this->http_code = curl_getinfo($ci, CURLINFO_HTTP_CODE);
			$this->http_info = array_merge($this->http_info, curl_getinfo($ci));
			$this->url = $url;
			curl_close ($ci);
			return $response;
		}		
		/**
		 * Get the header info to store.
		 */
		function getHeader($ch, $header) {
			$i = strpos($header, ':');
			if (!empty($i)) {
				$key = str_replace('-', '_', strtolower(substr($header, 0, $i)));
				$value = trim(substr($header, $i + 2));
				$this->http_header[$key] = $value;
			}
			return strlen($header);
		}
		
	}
}

?>