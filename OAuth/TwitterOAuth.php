<?php

if (!class_exists('TwitterOAuth')) {
	require_once dirname(__FILE__).'/BaseOAuthImpl.php';
	class TwitterOAuth extends BaseOAuthImpl {
		
		function getHost() {
			return 'https://api.twitter.com/1.1/';
		}
		
		function getUserAgent() {
			return 'TwitterOAuth v0.2.0-beta2';
		}

		/**
		* Set API URLS
		*/
		function accessTokenURL()  {
			return 'https://api.twitter.com/oauth/access_token';
		}
		function authenticateURL() {
			return 'https://api.twitter.com/oauth/authenticate';
		}
		function authorizeURL()    {
			return 'https://api.twitter.com/oauth/authorize';
		}
		function requestTokenURL() {
			return 'https://api.twitter.com/oauth/request_token';
		}
		
		
	}
}
?>