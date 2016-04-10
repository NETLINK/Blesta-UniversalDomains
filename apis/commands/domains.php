<?php
/**
 * Whois Domain Lookup
 *
 * @copyright Copyright (c) 2015 NETLINK IT SERVICES
 * @license http://opensource.org/licenses/mit-license.php MIT License
 * @package iedomains.commands
 */
class Whois {
	
	/**
	 * @var ieApi
	 */
	private $api;
	
	/**
	 * Sets the API to use for communication
	 *
	 * @param WhoisApi $api The API to use for communication
	 */
	public function __construct( WhoisApi $api ) {
		$this->api = $api;
	}
	
	/**
	 * Get essential information on a particular domain, including the expiration date, creation date, status, locked status and nameservers.
	 *
	 * https://www.namesilo.com/api_reference.php#getDomainInfo
	 */
	public function getDomainInfo( array $vars ) {
		return $this->api->submit( "getDomainInfo", $vars );
	}
	
	/**
	 * Gets contact information for the requested domain.
	 *
	 * @param array $vars An array of input params including:
	 * 	- DomainName Domain to get contacts
	 * @return NamesiloResponse
	 */
	public function getContacts( array $vars ) {
		
		return $this->api->submit( "contactList", $vars );
		
		$response = self::getDomainInfo( $vars );
		
		if ( parent::$codes[$response->status()][1] != "fail" ) {
			
			$contact_ids = $response->response()->contact_ids;
			
			$contacts = $temp = array();
			foreach ( $contact_ids as $type => $id ) {
				if ( !isset( $temp[$id] ) ) {
					$response = $this->api->submit( "contactList", array( "contact_id" => $id ) );
					if ( parent::$codes[$response->status()][1] != "fail" ) {
						$temp[$id] = $response->response()->contact;
						$contacts[$type] = $temp[$id];
					}
				}
				else {
					$contacts[$type] = $temp[$id];
				}
			}
			return $contacts;
		}
		return false;
	}
	
	/**
	 * Checks the availability of a domain name.
	 */
	public function check( array $vars ) {
		return $this->api->submit( "GET_DN_AVAILABILITY", $vars );
	}
	
}
?>