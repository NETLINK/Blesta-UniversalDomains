<?php
/**
 * Universal Domains Module
 *
 * @copyright Copyright (c) 2015, NETLINK IT SERVICES
 * @link http://www.netlink.ie/ NETLINK
 */
class UniversalDomains extends Module {
	
	/**
	 * @var string The version of this module
	 */
	//private static $version = "1.0.6-alpha";
	/**
	 * @var array The authors of this module
	 */
	/*
	private static $authors = array(
		array(
			'name' => "NETLINK IT SERVICES",
			'url' => "http://www.netlink.ie/"
		),
	);
	*/
	
	private static $debug = false;
	private static $debug_to = "root@localhost";
	
	// Pending statutes (array)
	private static $pending = array( 'in_review', 'pending' );
	
	/**
	 * Initializes the module
	 */
	public function __construct() {
		
		$this->loadConfig( dirname( __FILE__ ) . DS . "config.json" );
		
		// Load components required by this module
		Loader::loadComponents( $this, array( "Input", "Session" ) );
		
		# Load required models
		Loader::loadModels( $this, array( "EmailGroups", "Emails" ) );
			
		// Load the language required by this module
		Language::loadLang( "universal_domains", null, dirname( __FILE__ ) . DS . "language" . DS );
		
		Configure::load( "universal_domains", dirname( __FILE__ ) . DS . "config" . DS );
		
		//$email_group = $this->EmailGroups->getByAction( "UniversalDomains.nameserver_notice" );
		
		//if ( $email_group === false ) {
			//$this->addEmailGroup();
		//}
		
		//var_dump( $email_group );
		
	}

	/**
	 * Returns the value used to identify a particular service
	 *
	 * @param stdClass $service A stdClass object representing the service
	 * @return string A value used to identify this service amongst other similar services
	 */
	public function getServiceName( $service ) {
		foreach ( $service->fields as $field ) {
			if ( $field->key == "domain" )
				return $field->value;
		}
		return null;
	}
	
	/**
	 * Returns a noun used to refer to a module row (e.g. "Server", "VPS", "Reseller Account", etc.)
	 *
	 * @return string The noun used to refer to a module row
	 */
	public function moduleRowName() {
		return Language::_( "universal_domains.module_row", true );
	}
	
	/**
	 * Returns a noun used to refer to a module row in plural form (e.g. "Servers", "VPSs", "Reseller Accounts", etc.)
	 *
	 * @return string The noun used to refer to a module row in plural form
	 */
	public function moduleRowNamePlural() {
		return Language::_( "universal_domains.module_row_plural", true );
	}
	
	/**
	 * Returns a noun used to refer to a module group (e.g. "Server Group", "Cloud", etc.)
	 *
	 * @return string The noun used to refer to a module group
	 */
	public function moduleGroupName() {
		return null;
	}
	
	/**
	 * Returns the key used to identify the primary field from the set of module row meta fields.
	 * This value can be any of the module row meta fields.
	 *
	 * @return string The key used to identify the primary field from the set of module row meta fields
	 */
	public function moduleRowMetaKey() {
		return "username";
	}
	
	/**
	 * Returns the value used to identify a particular package service which has
	 * not yet been made into a service. This may be used to uniquely identify
	 * an uncreated services of the same package (i.e. in an order form checkout)
	 *
	 * @param stdClass $package A stdClass object representing the selected package
	 * @param array $vars An array of user supplied info to satisfy the request
	 * @return string The value used to identify this package service
	 * @see Module::getServiceName()
	 */
	public function getPackageServiceName( $packages, array $vars = null ) {
		if ( isset( $vars['domain'] ) ) {
			return $vars['domain'];
		}
		return null;
	}
	
	/**
	 * Attempts to validate service info. This is the top-level error checking method. Sets Input errors on failure.
	 *
	 * @param stdClass $package A stdClass object representing the selected package
	 * @param array $vars An array of user supplied info to satisfy the request
	 * @return boolean True if the service validates, false otherwise. Sets Input errors when false.
	 */
	public function validateService($package, array $vars=null) {
		return true;
	}
	
	/**
	 * Adds the service to the remote server. Sets Input errors on failure,
	 * preventing the service from being added.
	 *
	 * @param stdClass $package A stdClass object representing the selected package
	 * @param array $vars An array of user supplied info to satisfy the request
	 * @param stdClass $parent_package A stdClass object representing the parent service's selected package (if the current service is an addon service)
	 * @param stdClass $parent_service A stdClass object representing the parent service of the service being added (if the current service is an addon service and parent service has already been provisioned)
	 * @param string $status The status of the service being added. These include:
	 * 	- active
	 * 	- canceled
	 * 	- pending
	 * 	- suspended
	 * @return array A numerically indexed array of meta fields to be stored for this service containing:
	 * 	- key The key for this meta field
	 * 	- value The value for this key
	 * 	- encrypted Whether or not this field should be encrypted (default 0, not encrypted)
	 * @see Module::getModule()
	 * @see Module::getModuleRow()
	 */
	public function addService( $package, array $vars = null, $parent_package = null, $parent_service = null, $status = "pending" ) {
		
		$tld = NULL;
		$input_fields = array();
		
		if ( $package->meta->type == "domain" ) {
			if ( array_key_exists( "auth", $vars ) ) {
				$input_fields = array_merge( Configure::get( "universal_domains.transfer_fields" ), array( 'years' => true ) );
			}
			else {
				if ( isset( $vars['domain'] ) ) {
					$tld = $this->getTld( $vars['domain'] );
				}
				$whois_fields = Configure::get( "universal_domains.whois_fields" );
				$input_fields = array_merge(
					Configure::get( "universal_domains.domain_fields" ),
					(array) Configure::get( "universal_domains.domain_fields" . $tld ),
					(array) Configure::get( "universal_domains.nameserver_fields" ),
					array ( 'years' => true )
				);
			}
		}
		
		if ( isset ( $vars['use_module'] ) && $vars['use_module'] == "true" ) {
			
			if ( $package->meta->type == "domain" ) {

				$vars['years'] = 1;
				
				foreach ( $package->pricing as $pricing ) {
					if ( $pricing->id == $vars['pricing_id'] ) {
						$vars['years'] = $pricing->term;
						break;
					}
				}
				
				// Handle transfer
				if ( isset( $vars['transfer'] ) || isset( $vars['auth'] ) ) {
					
					$fields = array_intersect_key( $vars, $input_fields );
					
					if ( $this->Input->errors() ) {
						return;
					}
					
					return array( array( 'key' => "domain", 'value' => $fields['domain'], 'encrypted' => 0 ) );
				}
				// Handle registration
				else {
					
					$fields = array_intersect_key( $vars, $input_fields );
					
					if ( $this->Input->errors() )
						return;
					
					return array( array( 'key' => "domain", 'value' => $vars['domain'], 'encrypted' => 0 ) );
				}
			}
		}
		
		$meta = array();
		$fields = array_intersect_key( $vars, $input_fields );
		foreach ( $fields as $key => $value ) {
			$meta[] = array(
				'key' => $key,
				'value' => $value,
				'encrypted' => 0
			);
		}

		return $meta;
	}
	
	/**
	 * Edits the service on the remote server. Sets Input errors on failure,
	 * preventing the service from being edited.
	 *
	 * @param stdClass $package A stdClass object representing the current package
	 * @param stdClass $service A stdClass object representing the current service
	 * @param array $vars An array of user supplied info to satisfy the request
	 * @param stdClass $parent_package A stdClass object representing the parent service's selected package (if the current service is an addon service)
	 * @param stdClass $parent_service A stdClass object representing the parent service of the service being edited (if the current service is an addon service)
	 * @return array A numerically indexed array of meta fields to be stored for this service containing:
	 * 	- key The key for this meta field
	 * 	- value The value for this key
	 * 	- encrypted Whether or not this field should be encrypted (default 0, not encrypted)
	 * @see Module::getModule()
	 * @see Module::getModuleRow()
	 */
	public function editService( $package, $service, array $vars=array(), $parent_package=null, $parent_service=null ) {
		return null; // All this handled by admin/client tabs instead
	}
	
	/**
	 * Cancels the service on the remote server. Sets Input errors on failure,
	 * preventing the service from being canceled.
	 */
	public function cancelService( $package, $service, $parent_package = null, $parent_service = null ) {
		return null;
	}
	
	/**
	 * Suspends the service on the remote server. Sets Input errors on failure,
	 * preventing the service from being suspended.
	 */
	public function suspendService( $package, $service, $parent_package=null, $parent_service=null ) {
		return null;
	}
	
	/**
	 * Unsuspends the service on the remote server. Sets Input errors on failure,
	 * preventing the service from being unsuspended.
	 */
	public function unsuspendService( $package, $service, $parent_package=null, $parent_service=null ) {
		return null; // Nothing to do
	}
	
	/**
	 * Allows the module to perform an action when the service is ready to renew.
	 * Sets Input errors on failure, preventing the service from renewing.
	 *
	 * @param stdClass $package A stdClass object representing the current package
	 * @param stdClass $service A stdClass object representing the current service
	 * @param stdClass $parent_package A stdClass object representing the parent service's selected package (if the current service is an addon service)
	 * @param stdClass $parent_service A stdClass object representing the parent service of the service being renewed (if the current service is an addon service)
	 * @return mixed null to maintain the existing meta fields or a numerically indexed array of meta fields to be stored for this service containing:
	 * 	- key The key for this meta field
	 * 	- value The value for this key
	 * 	- encrypted Whether or not this field should be encrypted (default 0, not encrypted)
	 * @see Module::getModule()
	 * @see Module::getModuleRow()
	 */
	public function renewService( $package, $service, $parent_package = null, $parent_service = null ) {
		return null;
	}
	
	/**
	 * Updates the package for the service on the remote server. Sets Input
	 * errors on failure, preventing the service's package from being changed.
	 */
	public function changeServicePackage($package_from, $package_to, $service, $parent_package=null, $parent_service=null) {
		return null; // Nothing to do
	}

	/**
	 * Validates input data when attempting to add a package, returns the meta
	 * data to save when adding a package. Performs any action required to add
	 * the package on the remote server. Sets Input errors on failure,
	 * preventing the package from being added.
	 *
	 * @param array An array of key/value pairs used to add the package
	 * @return array A numerically indexed array of meta fields to be stored for this package containing:
	 * 	- key The key for this meta field
	 * 	- value The value for this key
	 * 	- encrypted Whether or not this field should be encrypted (default 0, not encrypted)
	 * @see Module::getModule()
	 * @see Module::getModuleRow()
	 */
	public function addPackage( array $vars = null ) {
		
		$meta = array();
		if ( isset( $vars['meta'] ) && is_array( $vars['meta'] ) ) {
			// Return all package meta fields
			foreach ( $vars['meta'] as $key => $value ) {
				$meta[] = array(
					'key' => $key,
					'value' => $value,
					'encrypted' => 0
				);
			}
		}
		
		return $meta;
	}
	
	/**
	 * Validates input data when attempting to edit a package, returns the meta
	 * data to save when editing a package. Performs any action required to edit
	 * the package on the remote server. Sets Input errors on failure,
	 * preventing the package from being edited.
	 *
	 * @param stdClass $package A stdClass object representing the selected package
	 * @param array An array of key/value pairs used to edit the package
	 * @return array A numerically indexed array of meta fields to be stored for this package containing:
	 * 	- key The key for this meta field
	 * 	- value The value for this key
	 * 	- encrypted Whether or not this field should be encrypted (default 0, not encrypted)
	 * @see Module::getModule()
	 * @see Module::getModuleRow()
	 */
	public function editPackage( $package, array $vars = null ) {
		
		$meta = array();
		if ( isset( $vars['meta'] ) && is_array( $vars['meta'] ) ) {
			// Return all package meta fields
			foreach ( $vars['meta'] as $key => $value ) {
				$meta[] = array(
					'key' => $key,
					'value' => $value,
					'encrypted' => 0
				);
			}
		}
		
		return $meta;	
	}
	
	/**
	 * Returns the rendered view of the manage module page
	 *
	 * @param mixed $module A stdClass object representing the module and its rows
	 * @param array $vars An array of post data submitted to or on the manage module page (used to repopulate fields after an error)
	 * @return string HTML content containing information to display when viewing the manager module page
	 */
	public function manageModule( $module, array &$vars ) {
		
		// Load the view into this object, so helpers can be automatically added to the view
		$this->view = new View( "manage", "default" );
		$this->view->base_uri = $this->base_uri;
		$this->view->setDefaultView( "components" . DS . "modules" . DS . "universal_domains" . DS );
		
		// Load the helpers required for this view
		Loader::loadHelpers( $this, array( "Form", "Html", "Widget" ) );
		
		$this->view->set( "module", $module );
		
		return $this->view->fetch();
	}
	
	/**
	 * Returns the rendered view of the add module row page
	 *
	 * @param array $vars An array of post data submitted to or on the add module row page (used to repopulate fields after an error)
	 * @return string HTML content containing information to display when viewing the add module row page
	 */
	public function manageAddRow( array &$vars ) {
		// Load the view into this object, so helpers can be automatically added to the view
		$this->view = new View( "add_row", "default" );
		$this->view->base_uri = $this->base_uri;
		$this->view->setDefaultView( "components" . DS . "modules" . DS . "universal_domains" . DS );
		
		// Load the helpers required for this view
		Loader::loadHelpers( $this, array( "Form", "Html", "Widget" ) );
		
		$this->view->set( "vars", (object)$vars );
		return $this->view->fetch();
	}

	/**
	 * Returns the rendered view of the edit module row page
	 *
	 * @param stdClass $module_row The stdClass representation of the existing module row
	 * @param array $vars An array of post data submitted to or on the edit module row page (used to repopulate fields after an error)
	 * @return string HTML content containing information to display when viewing the edit module row page
	 */	
	public function manageEditRow( $module_row, array &$vars ) {
		// Load the view into this object, so helpers can be automatically added to the view
		$this->view = new View( "edit_row", "default" );
		$this->view->base_uri = $this->base_uri;
		$this->view->setDefaultView( "components" . DS . "modules" . DS . "universal_domains" . DS );
		
		// Load the helpers required for this view
		Loader::loadHelpers( $this, array( "Form", "Html", "Widget" ) );
		
		if ( empty( $vars ) )
			$vars = $module_row->meta;
		
		$this->view->set( "vars", (object)$vars );
		return $this->view->fetch();
	}
	
	/**
	 * Adds the module row on the remote server. Sets Input errors on failure,
	 * preventing the row from being added.
	 *
	 * @param array $vars An array of module info to add
	 * @return array A numerically indexed array of meta fields for the module row containing:
	 * 	- key The key for this meta field
	 * 	- value The value for this key
	 * 	- encrypted Whether or not this field should be encrypted (default 0, not encrypted)
	 */
	public function addModuleRow( array &$vars ) {
		
		$meta_fields = array( "username", "password", "support" );
		$encrypted_fields = array( "password" );
		
		$this->Input->setRules( $this->getRowRules( $vars ) );
		
		// Validate module row
		if ( $this->Input->validates( $vars ) ) {

			// Build the meta data for this row
			$meta = array();
			foreach ( $vars as $key => $value ) {
				
				if ( in_array( $key, $meta_fields ) ) {
					$meta[] = array(
						'key' => $key,
						'value' => $value,
						'encrypted' => in_array( $key, $encrypted_fields ) ? 1 : 0
					);
				}
			}
			
			return $meta;
		}
	}
	
	/**
	 * Edits the module row on the remote server. Sets Input errors on failure,
	 * preventing the row from being updated.
	 *
	 * @param stdClass $module_row The stdClass representation of the existing module row
	 * @param array $vars An array of module info to update
	 * @return array A numerically indexed array of meta fields for the module row containing:
	 * 	- key The key for this meta field
	 * 	- value The value for this key
	 * 	- encrypted Whether or not this field should be encrypted (default 0, not encrypted)
	 */
	public function editModuleRow($module_row, array &$vars) {
		// Same as adding
		return $this->addModuleRow($vars);
	}
	
	/**
	 * Deletes the module row on the remote server. Sets Input errors on failure,
	 * preventing the row from being deleted.
	 *
	 * @param stdClass $module_row The stdClass representation of the existing module row
	 */
	public function deleteModuleRow( $module_row ) {
		return null;
	}
	
	/**
	 * Returns all fields used when adding/editing a package, including any
	 * javascript to execute when the page is rendered with these fields.
	 *
	 * @param $vars stdClass A stdClass object representing a set of post fields
	 * @return ModuleFields A ModuleFields object, containg the fields to render as well as any additional HTML markup to include
	 */
	public function getPackageFields( $vars = null ) {
		
		Loader::loadHelpers( $this, array( "Html" ) );
		
		$fields = new ModuleFields();
		
		$types = array(
			'domain' => Language::_( "universal_domains.package_fields.type_domain", true ),
		);
		
		// Set type of package
		$type = $fields->label( Language::_( "universal_domains.package_fields.type", true ), "universal_domains_type" );
		$type->attach( $fields->fieldSelect( "meta[type]", $types,
			$this->Html->ifSet( $vars->meta['type'] ), array( "id" => "universal_domains_type" ) ) );
		$fields->setField( $type );
		
		// Set all TLD checkboxes
        $tld_options = $fields->label( Language::_( "universal_domains.package_fields.tld_options", true ) );
		
		$tlds = Configure::get( "universal_domains.tlds" );
		sort( $tlds );
		foreach ( $tlds as $tld ) {
			$tld_label = $fields->label( $tld, "tld_" . $tld );
			$tld_options->attach($fields->fieldCheckbox( "meta[tlds][]", $tld, ( isset( $vars->meta['tlds'] ) && in_array( $tld, $vars->meta['tlds'] ) ), array( 'id' => "tld_" . $tld ), $tld_label ) );
		}
		$fields->setField( $tld_options );
		
		// Set nameservers
		for ( $i=1; $i<=5; $i++ ) {
			$type = $fields->label( Language::_( "universal_domains.package_fields.ns" . $i, true ), "universal_domains_ns" . $i );
			$type->attach( $fields->fieldText( "meta[ns][]",
				$this->Html->ifSet( $vars->meta['ns'][$i-1] ), array( "id" => "universal_domains_ns" . $i ) ) );
			$fields->setField( $type );
		}	
		
		$fields->setHtml("
			<script type=\"text/javascript\">
				$(document).ready(function() {
					toggleTldOptions($('#universal_domains_type').val());
				
					// Re-fetch module options
					$('#universal_domains_type').change(function() {
						toggleTldOptions($(this).val());
					});
					
					function toggleTldOptions(type) {
						if (type == 'ssl')
							$('.universal_domains_tlds').hide();
						else
							$('.universal_domains_tlds').show();
					}
				});
			</script>
		");
		
		return $fields;
	}
	
	/**
	 * Returns an array of key values for fields stored for a module, package,
	 * and service under this module, used to substitute those keys with their
	 * actual module, package, or service meta values in related emails.
	 *
	 * @return array A multi-dimensional array of key/value pairs where each key is one of 'module', 'package', or 'service' and each value is a numerically indexed array of key values that match meta fields under that category.
	 * @see Modules::addModuleRow()
	 * @see Modules::editModuleRow()
	 * @see Modules::addPackage()
	 * @see Modules::editPackage()
	 * @see Modules::addService()
	 * @see Modules::editService()
	 */
	public function getEmailTags() {
		return array('service' => array('domain'));
	}

	/**
	 * Returns all fields to display to an admin attempting to add a service with the module
	 *
	 * @param stdClass $package A stdClass object representing the selected package
	 * @param $vars stdClass A stdClass object representing a set of post fields
	 * @return ModuleFields A ModuleFields object, containg the fields to render as well as any additional HTML markup to include
	 */
	public function getAdminAddFields( $package, $vars = null ) {
		
		Loader::loadHelpers( $this, array( "Form", "Html" ) );
			
		if ( $package->meta->type == "domain" ) {
			
			// Set default name servers
			if ( !isset( $vars->ns1 ) && isset( $package->meta->ns ) ) {
				$i = 1;
				foreach ( $package->meta->ns as $ns ) {
					$vars->{"ns" . $i++} = $ns;
				}
			}
			
			// Handle transfer request
			if ( isset( $vars->transfer ) || isset( $vars->auth ) ) {
				return $this->arrayToModuleFields( Configure::get( "universal_domains.transfer_fields" ), null, $vars );
			}
			// Handle domain registration
			else {
				
				#
				# TODO: Select TLD, then display additional fields
				#
				
				$fields = Configure::get( "universal_domains.transfer_fields" );
				
				$fields["transfer"] = array(
					'label' => Language::_( "universal_domains.domain.DomainAction", true ),
					'type' => "radio",
					'value' => "1",
					'options' => array(
						'1' => "Register",
						'2' => "Transfer",
					),
				);
				
				$fields["auth"] = array(
					"label" => Language::_( "universal_domains.transfer.EPPCode", true ),
					"type" => "text",
				);
				
				$module_fields = $this->arrayToModuleFields( array_merge( $fields, Configure::get( "universal_domains.nameserver_fields" ) ), null, $vars );
				
				// $module_fields = $this->arrayToModuleFields(array_merge(Configure::get("Namesilo.domain_fields"), Configure::get("Namesilo.nameserver_fields")), null, $vars);
				
				$module_fields->setHtml("
					<script type=\"text/javascript\">
						$(document).ready(function() {
							$('#transfer_id_0').prop('checked', true);
							$('#auth_id').closest('li').hide();
							// Set whether to show or hide the ACL option
							$('#auth').closest('li').hide();
							if ($('input[name=\"transfer\"]:checked').val() == '2')
								$('#auth_id').closest('li').show();
								
							$('input[name=\"transfer\"]').change(function() {
								if ($(this).val() == '2')
									$('#auth_id').closest('li').show();
								else
									$('#auth_id').closest('li').hide();
							});
						});
					</script>");
	
                // Build the domain fields
                $fields = $this->buildDomainModuleFields( $vars );
                if ( $fields )
                    $module_fields = $fields;
			}
		}
		
		return ( isset( $module_fields ) ? $module_fields : new ModuleFields() );
	}
	
	/**
	 * Returns all fields to display to a client attempting to add a service with the module
	 *
	 * @param stdClass $package A stdClass object representing the selected package
	 * @param $vars stdClass A stdClass object representing a set of post fields
	 * @return ModuleFields A ModuleFields object, containg the fields to render as well as any additional HTML markup to include
	 */	
	public function getClientAddFields( $package, $vars = null ) {
		
		// Handle universal domain name
		if ( isset ( $vars->domain ) )
			$vars->domain = $vars->domain;
		
		if ( $package->meta->type == "domain" ) {
			
			// Set default name servers
			if ( ! isset ( $vars->ns ) && isset ( $package->meta->ns ) ) {
				$i=1;
				foreach ( $package->meta->ns as $ns) {
					$vars->{"ns" . $i++} = $ns;
				}
			}
			
			// Handle transfer request
			if ( isset ( $vars->transfer ) || isset ( $vars->auth ) ) {
				
				$fields = array_merge( Configure::get( "universal_domains.transfer_fields" ), Configure::get( "universal_domains.nameserver_fields" ) );
				
				// We should already have the domain name don't make editable
				$fields['domain']['type'] = "hidden";
				$fields['domain']['label'] = null;
				
				return $this->arrayToModuleFields($fields, null, $vars);
			}
			// Handle domain registration
			else {
				$fields = array_merge( Configure::get( "universal_domains.nameserver_fields" ), Configure::get( "universal_domains.domain_fields" ) );
				
				// We should already have the domain name don't make editable
				$fields['domain']['type'] = "hidden";
				$fields['domain']['label'] = null;
				
				$module_fields = $this->arrayToModuleFields( $fields, null, $vars );
				
                // Build the domain fields
                $domain_fields = $this->buildDomainModuleFields( $vars, true );
                if ( $domain_fields ) {
                    $module_fields = $domain_fields;
				}
			}
		}

        // Determine whether this is an AJAX request
        return ( isset ( $module_fields ) ? $module_fields : new ModuleFields() );
	}

    /**
     * Builds and returns the module fields for domain registration
     *
     * @param stdClass $vars An stdClass object representing the input vars
     * @param $client True if rendering the client view, or false for the admin (optional, default false)
     * return mixed The module fields for this service, or false if none could be created
     */
    private function buildDomainModuleFields( $vars, $client = false ) {
		
        if ( isset( $vars->domain ) ) {
            $tld = $this->getTld( $vars->domain );

            $extension_fields = Configure::get( "universal_domains.domain_fields" . $tld );
            if ( $extension_fields ) {
                // Set the fields
                if ( $client )
                    $fields = array_merge( Configure::get( "universal_domains.nameserver_fields"), Configure::get( "universal_domains.domain_fields" ), $extension_fields );
                else
                    $fields = array_merge( Configure::get( "universal_domains.domain_fields" ), Configure::get( "universal_domains.nameserver_fields" ), $extension_fields );

                if ( $client ) {
                    // We should already have the domain name don't make editable
                    $fields['domain']['type'] = "hidden";
                    $fields['domain']['label'] = null;
                }

                // Build the module fields
                $module_fields = new ModuleFields();

                // Allow AJAX requests
                $ajax = $module_fields->fieldHidden( "allow_ajax", "true", array( "id" => "universal_domains_allow_ajax" ) );
                $module_fields->setField( $ajax );
                $please_select = array( '' => Language::_( "AppController.select.please", true ) );

                foreach ( $fields as $key => $field ) {
                    // Build the field
                    $label = $module_fields->label( ( isset( $field['label'] ) ? $field['label'] : ""), $key );

                    $type = null;
                    if ( $field['type'] == "text" ) {
                        $type = $module_fields->fieldText( $key, ( isset( $vars->{$key} ) ? $vars->{$key} : "" ), array( "id" => $key ) );
                    }
                    elseif ($field['type'] == "select") {
                        $type = $module_fields->fieldSelect( $key, ( isset( $field['options'] ) ? $please_select + $field['options'] : $please_select ),
                                    ( isset( $vars->{$key} ) ? $vars->{$key} : '' ), array( 'id'=>$key ) );
                    }
                    elseif ($field['type'] == "hidden") {
                        $type = $module_fields->fieldHidden( $key, ( isset( $vars->{$key} ) ? $vars->{$key} : '' ), array( "id" => $key ) );
                    }

                    // Include a tooltip if set
                    if ( !empty( $field['tooltip'] ) )
                        $label->attach( $module_fields->tooltip( $field['tooltip'] ) );

                    if ( $type ) {
                        $label->attach( $type );
                        $module_fields->setField( $label );
                    }
                }
            }
        }

        return ( isset( $module_fields ) ? $module_fields : false );
    }

	/**
	 * Returns all fields to display to an admin attempting to edit a service with the module
	 *
	 * @param stdClass $package A stdClass object representing the selected package
	 * @param $vars stdClass A stdClass object representing a set of post fields
	 * @return ModuleFields A ModuleFields object, containg the fields to render as well as any additional HTML markup to include
	 */	
	public function getAdminEditFields( $package, $vars = NULL ) {
		return new ModuleFields();
	}
	
	/**
	 * Fetches the HTML content to display when viewing the service info in the
	 * admin interface.
	 *
	 * @param stdClass $service A stdClass object representing the service
	 * @param stdClass $package A stdClass object representing the service's package
	 * @return string HTML content containing information to display when viewing the service info
	 */
	public function getAdminServiceInfo($service, $package) {
		return "";
	}
	
	/**
	 * Fetches the HTML content to display when viewing the service info in the
	 * client interface.
	 *
	 * @param stdClass $service A stdClass object representing the service
	 * @param stdClass $package A stdClass object representing the service's package
	 * @return string HTML content containing information to display when viewing the service info
	 */
	public function getClientServiceInfo($service, $package) {
		return "";
	}
	
	/**
	 * Returns all tabs to display to an admin when managing a service whose
	 * package uses this module
	 *
	 * @param stdClass $package A stdClass object representing the selected package
	 * @return array An array of tabs in the format of method => title. Example: array('methodName' => "Title", 'methodName2' => "Title2")
	 */
	public function getAdminTabs( $package ) {
		if ( $package->meta->type == "domain" ) {
			return array(
				'tabNameservers' => Language::_( "universal_domains.tab_nameservers.title", true ),
			);
		}
	}

	/**
	 * Returns all tabs to display to a client when managing a service whose
	 * package uses this module
	 *
	 * @param stdClass $package A stdClass object representing the selected package
	 * @return array An array of tabs in the format of method => title. Example: array('methodName' => "Title", 'methodName2' => "Title2")
	 */
	public function getClientTabs( $package ) {
		if ( $package->meta->type == "domain" ) {
			return array(
				'tabClientWhois' => Language::_( "universal_domains.tab_whois.title", true ),
				'tabClientNameservers' => Language::_( "universal_domains.tab_nameservers.title", true ),
			);
		}
	}
	
	/**
	 * Admin Whois tab
	 *
	 * @param stdClass $package A stdClass object representing the current package
	 * @param stdClass $service A stdClass object representing the current service
	 * @param array $get Any GET parameters
	 * @param array $post Any POST parameters
	 * @param array $files Any FILES parameters
	 * @return string The string representing the contents of this tab
	 */
	public function tabWhois( $package, $service, array $get = null, array $post = null, array $files = null ) {
		return $this->manageWhois( "tab_whois", $package, $service, $get, $post, $files );
	}
	
	/**
	 * Client Whois tab
	 *
	 * @param stdClass $package A stdClass object representing the current package
	 * @param stdClass $service A stdClass object representing the current service
	 * @param array $get Any GET parameters
	 * @param array $post Any POST parameters
	 * @param array $files Any FILES parameters
	 * @return string The string representing the contents of this tab
	 */
	public function tabClientWhois( $package, $service, array $get = null, array $post = null, array $files = null ) {
		return $this->manageWhois( "tab_client_whois", $package, $service, $get, $post, $files );
	}
	
	/**
	 * Admin Nameservers tab
	 *
	 * @param stdClass $package A stdClass object representing the current package
	 * @param stdClass $service A stdClass object representing the current service
	 * @param array $get Any GET parameters
	 * @param array $post Any POST parameters
	 * @param array $files Any FILES parameters
	 * @return string The string representing the contents of this tab
	 */
	public function tabNameservers( $package, $service, array $get = null, array $post = null, array $files = null ) {
		return $this->manageNameservers( "tab_nameservers", $package, $service, $get, $post, $files );
	}
	
	/**
	 * Admin Nameservers tab
	 *
	 * @param stdClass $package A stdClass object representing the current package
	 * @param stdClass $service A stdClass object representing the current service
	 * @param array $get Any GET parameters
	 * @param array $post Any POST parameters
	 * @param array $files Any FILES parameters
	 * @return string The string representing the contents of this tab
	 */
	public function tabClientNameservers( $package, $service, array $get = null, array $post = null, array $files = null ) {
		return $this->manageNameservers( "tab_client_nameservers", $package, $service, $get, $post, $files );
	}
	
	/**
	 * Admin Settings tab
	 *
	 * @param stdClass $package A stdClass object representing the current package
	 * @param stdClass $service A stdClass object representing the current service
	 * @param array $get Any GET parameters
	 * @param array $post Any POST parameters
	 * @param array $files Any FILES parameters
	 * @return string The string representing the contents of this tab
	 */
	public function tabSettings( $package, $service, array $get = null, array $post = null, array $files = null ) {
		return $this->manageSettings( "tab_settings", $package, $service, $get, $post, $files );
	}
	
	/**
	 * Client Settings tab
	 *
	 * @param stdClass $package A stdClass object representing the current package
	 * @param stdClass $service A stdClass object representing the current service
	 * @param array $get Any GET parameters
	 * @param array $post Any POST parameters
	 * @param array $files Any FILES parameters
	 * @return string The string representing the contents of this tab
	 */
	public function tabClientSettings( $package, $service, array $get = null, array $post = null, array $files = null ) {
		return $this->manageSettings( "tab_client_settings", $package, $service, $get, $post, $files );
	}
	
	/**
	 * Handle updating whois information
	 *
	 */
	private function manageWhois( $view, $package, $service, array $get = null, array $post = null, array $files = null ) {
		
		$vars = new stdClass();
		
		if ( in_array( $service->status, self::$pending ) ) {
			$this->view = new View( "pending", "default" );
			$this->view->setDefaultView( "components" . DS . "modules" . DS . "universal_domains" . DS );
			return $this->view->fetch();
		}
		else if ( $view == "tab_client_whois" && $service->status == "suspended" ) {
			$this->view = new View( "suspended", "default" );
			$this->view->setDefaultView( "components" . DS . "modules" . DS . "universal_domains" . DS );
			return $this->view->fetch();
		}
		
		$this->view = new View( $view, "default" );
		// Load the helpers required for this view
		Loader::loadHelpers( $this, array( "Form", "Html" ) );			
		
		$this->view->set( "vars", $vars );
		$this->view->setDefaultView( "components" . DS . "modules" . DS . "universal_domains" . DS );
		return $this->view->fetch();
	}
	
	/**
	 * Handle updating nameserver information
	 *
	 */
	private function manageNameservers( $view, $package, $service, array $get = null, array $post = null, array $files = null ) {
		
		$vars = new stdClass();
		
		$email = ! empty( $this->getModuleRow()->meta->support ) ? $this->getModuleRow()->meta->support : false;
		
		$fields = $this->serviceFieldsToObject( $service->fields );
		
		$dns = dns_get_record( $fields->domain );
		
		$ns = array(); $i = 0;
		
		if ( !empty( $dns ) && is_array( $dns ) ) {
			foreach ( $dns as $key => $value ) {
				if ( $value['type'] == "NS" ) {
					$ns[$i] = $value['target'];
					$i++;
				}
			}
		}
		
		if ( in_array( $service->status, self::$pending ) ) {
			$this->view = new View( 'pending', "default" );
		}
		else if ( $view == "tab_nameservers" ) {
			$this->view = new View( 'tab_admin_nameservers', "default" );
			Loader::loadHelpers( $this, array( "Html" ) );
		}
		else if ( $view == "tab_client_nameservers" && $service->status == "suspended" ) {
			$this->view = new View( 'suspended', "default" );
		}
		else if ( $email === false ) {
			$this->view = new View( 'tab_nameservers_static', "default" );
			Loader::loadHelpers( $this, array( "Html" ) );
		}
		else {
			
			$this->view = new View( $view, "default" );
			Loader::loadHelpers( $this, array( "Form", "Html" ) );
			
			if ( ! empty ( $post ) ) {
				
				$client_id = $this->Session->read( "blesta_client_id" );
				
				if ( ! isset ( $this->Clients ) ) {
					
					$ns = array();
					
					foreach ( $post['ns'] as $k => $value ) {
						if ( ! empty ( $value ) ) {
							if ( checkdnsrr( "{$value}.", "A" ) || checkdnsrr( "{$value}.", "AAAA" ) ) {
								$ns[] = $value;
							}
							else {
								$this->Input->setErrors( array( 'errors' => array( 'One or more of your nameservers failed validation. Invalid nameservers were removed automatically.' ) ) );
							}
						}
					}
					
					$ns = array_unique ( $ns );
					
					Loader::loadModels( $this, array ( "Clients" ) );
					$client = $this->Clients->get( $client_id, false );
					
					$subject = "Update Nameservers - {$fields->domain}";
					# Create hash for verifying the authenticity of the request
					$hash = md5( $client_id . $subject . time() );
					$body = "A request has been made to update the nameservers for {$fields->domain}.\n\n" . implode( "\n", $ns ) . "\n\nHash: {$hash}\n";
					
					# Add client note
					$this->Clients->addNote( $client_id, NULL, array( "title" => $subject, "description" => $body ) );
					# Cross check hash to verify authenticity
					$body .= "\nPlease review the client notes to verify the authenticity of this request.";
					# Send email notification to support address
					$this->Emails->sendCustom( $client->email, "{$client->first_name} {$client->last_name}", $email, $subject, array ( "text" => $body ) );
				}
			}
			
		}
		
		$vars->ns = $ns;
		
		$this->view->set( "vars", $vars );
		$this->view->setDefaultView( "components" . DS . "modules" . DS . "universal_domains" . DS );
		return $this->view->fetch();
	}
	
	/**
	 * Handle updating settings
	 *
	 * @param string $view The view to use
	 * @param stdClass $package A stdClass object representing the current package
	 * @param stdClass $service A stdClass object representing the current service
	 * @param array $get Any GET parameters
	 * @param array $post Any POST parameters
	 * @param array $files Any FILES parameters
	 * @return string The string representing the contents of this tab
	 */
	private function manageSettings($view, $package, $service, array $get=null, array $post=null, array $files=null) {
		
		$vars = new stdClass();
		
		if ( in_array( $service->status, self::$pending ) ) {
			$this->view = new View( 'pending', "default" );
		}
		else if ( $view == "tab_client_settings" && $service->status == "suspended" ) {
			$this->view = new View( 'suspended', "default" );
		}
		else {
			
			$this->view = new View( $view, "default" );
		}
		
		$this->view->set( "vars", $vars );
		$this->view->setDefaultView( "components" . DS . "modules" . DS . "universal_domains" . DS );
		return $this->view->fetch();
	}
	
	/**
	 * Performs a whois lookup on the given domain
	 *
	 * @param string $domain The domain to lookup
	 * @return boolean true if available, false otherwise
	 */
	public function checkAvailability( $domain ) {

		$row = $this->getModuleRow();
		$api = $this->getApi( $row->meta->username, $row->meta->password );
		
		$domains = new Whois( $api );
		$result = $domains->check( array( "domainName" => $domain, "getMode" => "DNS_ONLY" ) );
		
		if ( $this->debug ) {
			$this->debug( $result );
			$this->debug( $api->lastRequest() );
		}
		
		//if ( self::$codes[$result->status()][1] == "fail" )
			//return false;
		
		$response = $result->response();
		
		$available = isset( $response->{'domainAvailability'} ) && $response->{'domainAvailability'} == "AVAILABLE";
		return $available;
	}
	
	/**
	 * Builds and returns the rules required to add/edit a module row
	 *
	 * @param array $vars An array of key/value data pairs
	 * @return array An array of Input rules suitable for Input::setRules()
	 */
	private function getRowRules( &$vars ) {
		return array(
			'username' => array(
				'valid' => array(
					'rule' => "isEmpty",
					'negate' => true,
					'message' => Language::_("universal_domains.!error.username.valid", true)
				)
			),
			'password' => array(
				'valid' => array(
					'last' => true,
					'rule' => "isEmpty",
					'negate' => true,
					'message' => Language::_("universal_domains.!error.password.valid", true)
				),
				'valid_connection' => array(
					'rule' => array( array( $this, "validateConnection" ) ),
					'message' => Language::_("universal_domains.!error.password.valid_connection", true)
				)
			)
		);
	}
	
	/**
	 * No validation, return "success"
	 */
	public function validateConnection() {
		return "success";
	}
	
	/**
	 * Initializes the NamesiloApi and returns an instance of that object
	 *
	 * @param string $user The user to connect as
	 * @param string $key The key to use when connecting
	 * @param boolean $sandbox Whether or not to process in sandbox mode (for testing)
	 * @param string $username The username to execute an API command using
	 * @return NamesiloApi The NamesiloApi instance
	 */
	private function getApi( $username, $password ) {
		Loader::load( dirname( __FILE__ ) . DS . "apis" . DS . "whois_api.php" );
		return new WhoisApi( $username, $password );
	}
	
	/**
	 * Process API response, setting an errors, and logging the request
	 *
	 * @param NamesiloApi $api The Namesilo API object
	 * @param NamesiloResponse $response The Namesilo API response object
	 */
	private function processResponse( WhoisApi $api, WhoisResponse $response ) {
		$this->logRequest( $api, $response );
		
		$status = $response->status();
		
		// Set errors, if any
		if ( self::$codes[$status][1] == "fail" ) {
			//$errors = isset( $response->errors()->Error ) ? $response->errors()->Error : array();
			$errors = $response->errors() ? $response->errors() : array();
			$this->Input->setErrors( array( 'errors' => (array)$errors ) );
		}
	}
	
	/**
	 * Logs the API request
	 *
	 * @param NamesiloApi $api The Namesilo API object
	 * @param NamesiloResponse $response The Namesilo API response object
	 */
	private function logRequest( WhoisApi $api, WhoisResponse $response ) {		
		$last_request = $api->lastRequest();
		$url = substr( $last_request['url'], 0, strpos( $last_request['url'], '?' ) );
		$this->log( $url, serialize( $last_request['args'] ), "input", true );
		$this->log( $url, $response->raw(), "output", self::$codes[$response->status()][1] == "success" );
	}
	
	/**
	 * Returns the TLD of the given domain
	 *
	 * @param string $domain The domain to return the TLD from
	 * @return string The TLD of the domain
	 */
	private function getTld( $domain ) {
		$tlds = Configure::get( "universal_domains.tlds" );
		
		$domain = strtolower( $domain );
		
		foreach ( $tlds as $tld ) {
			if (substr( $domain, -strlen( $tld ) ) == $tld )
				return $tld;
		}
		return strstr( $domain, "." );
	}
	
	/**
	 * Formats a phone number into +NNN.NNNNNNNNNN
	 *
	 * @param string $number The phone number
	 * @param string $country The ISO 3166-1 alpha2 country code
	 * @return string The number in +NNN.NNNNNNNNNN
	 */
	private function formatPhone( $number, $country ) {
		if ( ! isset ( $this->Contacts ) ) {
			Loader::loadModels( $this, array ( "Contacts" ) );
		}
		
		return $this->Contacts->intlNumber( $number, $country, "." );
	}
	
	private function addEmailGroup() {
		
		$this->deleteEmails( "UniversalDomains.nameserver_notice" );
		return;
		
		$group = array(
			'action' => "UniversalDomains.nameserver_notice",
			'type' => "staff",
			//'plugin_dir' => "universal_domains",
			'notice_type' => 'to',
			'tags' => "first_name,last_name",
		);
		
		// Add the custom group
		$group_id = $this->EmailGroups->add( $group );
		
		$email = array(
			'email_group_id' => $group_id,
			'company_id' => Configure::get( "Blesta.company_id" ),
			'lang' => "en_us",
			'from' => "no-reply@mydomain.com",
			'from_name' => "My Company",
			'subject' => "Subject of the email",
			'text' => "Hi {first_name},
			This is the text version of your email",
			'html' => "<p>Hi {first_name},</p>
			<p>This is the HTML version of your email</p>"
		);
		
		// Add an email to the group
		$this->Emails->add( $email );
	}
	
	private function deleteEmails( $action ) {
		
		// Fetch the email template created by this plugin
		$group = $this->EmailGroups->getByAction( $action );
		
		// Delete all emails templates belonging to this plugin's email group and company
		if ( $group ) {
			$this->Emails->deleteAll( $group->id, Configure::get( "Blesta.company_id" ) );
		}
		
		try {
			// Remove the email template created by this plugin
			if ( $group ) {
				$this->EmailGroups->delete( $group->id );
			}
		}
		catch ( Exception $e ) {
			// Error dropping... no permission?
			$this->Input->setErrors(
				array(
					'db' => array(
						'create' => $e->getMessage()
					)
				)
			);
			return;
		}
	}
	
	private function debug( $data ) {
		mail( self::$debug_to, "Universal Domains Module Debug", var_export( $data, true ), "From: blesta@localhost\n\n" );
	}
	
}
?>
