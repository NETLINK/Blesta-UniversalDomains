<?php
/**
 * Whois API response handler
 *
 * @copyright Copyright (c) 2013, Phillips Data, Inc.
 * @license http://opensource.org/licenses/mit-license.php MIT License
 * @package namesilo
 */
class WhoisResponse {
	
	/**
	 * @var SimpleXMLElement The XML parsed response from the API
	 */
	private $xml;
	/**
	 * @var string The raw response from the API
	 */	
	private $raw;

	/**
	 * Initializes the Universal Domains Response
	 *
	 * @param string $response The raw XML response data from an API request
	 */
	public function __construct($response) {
		$this->raw = $response;
		
		try {
			$this->xml = new SimpleXMLElement($this->raw);
		}
		catch (Exception $e) {
			// Invalid response
		}
	}
	
	/**
	 * Returns the CommandResponse
	 *
	 * @return stdClass A stdClass object representing the CommandResponses, null if invalid response
	 */
	public function response( $assoc = false ) {
		if ($this->xml && $this->xml instanceof SimpleXMLElement) {
			return $this->formatResponse( $this->xml, $assoc );
		}
		return null;
	}
	
	/**
	 * Returns the status of the API Responses
	 *
	 * @return string The status (300 = success)
	 */
	public function status() {
		# To do: add status codes
		if ($this->xml && $this->xml instanceof SimpleXMLElement) {
			return (string)$this->xml->msg;
		}
		return null;
	}
	
	/**
	 * Returns all errors contained in the response
	 *
	 * @return stdClass A stdClass object representing the errors in the response, false if invalid response
	 */
	public function errors() {
		if ( $this->xml && $this->xml instanceof SimpleXMLElement ) {
			return $this->formatResponse( $this->xml->ErrorMessage );
		}
		return false;
	}
	
	/**
	 * Returns all warnings contained in the response
	 *
	 * @return stdClass A stdClass object representing the warnings in the response, false if invalid response
	 */
	public function warnings() {
		if ($this->xml && $this->xml instanceof SimpleXMLElement) {
			return $this->formatResponse($this->xml->Warnings);
		}
		return false;
	}
	
	/**
	 * Returns the raw response
	 *
	 * @return string The raw response
	 */
	public function raw() {
		return $this->raw;
	}
	
	/**
	 * Formats the given $data into a stdClass object by first JSON encoding, then JSON decoding it
	 *
	 * @param mixed $data The data to convert to a stdClass object
	 * @return stdClass $data in a stdClass object form
	 */
	private function formatResponse( $data, $assoc = false ) {
		return json_decode( json_encode( $data ), $assoc );
	}
}
?>