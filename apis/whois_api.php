<?php

require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . "whois_response.php";
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . "commands" . DIRECTORY_SEPARATOR . "domains.php";

/**
 * Whois API processor
 *
 * Documentation on the Whois API: https://www.whoisxmlapi.com/domain-availability-api-doc.php
 *
 * @copyright Copyright (c) 2015 NETLINK IT SERVICES
 * @license http://opensource.org/licenses/mit-license.php MIT License
 * @package namesilo
 */
class WhoisApi {

	const LIVE_URL = "https://www.whoisxmlapi.com/whoisserver/WhoisService";

	/**
	 * @var string API version
	 */
	private $api_version = 1;
	/**
	 * @var string The format of the API response
	 */
	private $format = 'xml';
	/**
	 * @var string The username to execute an API command using
	 */
	private $username;
	/**
	 * @var string The password to use when connecting
	 */
	private $password;
	/**
	 * @var array An array representing the last request made
	 */
	private $last_request = array( 'url' => null, 'args' => null );
	
	/**
	 * Sets the connection details
	 *
	 * @param string $username The user to connect as
	 * @param string $password The key to use when connecting
	 */
	public function __construct( $username, $password ) {
		$this->username = $username;
		$this->password = $password;
	}
	
	/**
	 * Submits a request to the API
	 *
	 * @param string $command The command to submit
	 * @param array $args An array of key/value pair arguments to submit to the given API command
	 * @return NamesiloResponse The response object
	 */
	public function submit( $command, array $args = array() ) {

		$url = self::LIVE_URL;
		
		$args['outputFormat'] = $this->format;
		$args['cmd'] = $command;
		$args["username"] = $this->username;
		$args["password"] = $this->password;
		
		$query = http_build_query( $args );
		
		//if (!isset($args['ClientIP']))
			//$args['ClientIP'] = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : "127.0.0.1";
		
		$this->last_request = array(
			'url' => $url,
			'args' => $args
		);
		
		$ch = curl_init();
		curl_setopt( $ch, CURLOPT_URL, $url . '?' . $query );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
		//curl_setopt( $ch, CURLOPT_POSTFIELDS, $args );
		curl_setopt( $ch, CURLOPT_CUSTOMREQUEST, "GET" );
		curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );
		curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, false );
		$response = curl_exec( $ch );
		curl_close( $ch );
		
		//trigger_error( var_export( $args, true ) );
		//trigger_error( var_export( $response, true ) );
		
		return new WhoisResponse( $response );
	}
	
	/**
	 * Returns the details of the last request made
	 *
	 * @return array An array containg:
	 * 	- url The URL of the last request
	 * 	- args The paramters passed to the URL
	 */
	public function lastRequest() {
		return $this->last_request;
	}
}
?>