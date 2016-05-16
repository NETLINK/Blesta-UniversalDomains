<?php
// All available TLDs
Configure::set( "universal_domains.tlds", array(
	".ie", ".de",
));

// Transfer fields
Configure::set( "universal_domains.transfer_fields", array(
	'domain' => array(
		'label' => Language::_( "universal_domains.transfer.domain", true),
		'type' => "text"
	),
	'auth' => array(
		'label' => Language::_( "universal_domains.transfer.EPPCode", true ),
		'type' => "text"
	),
));

// Domain fields
Configure::set( "universal_domains.domain_fields", array(
	'domain' => array(
		'label' => Language::_( "universal_domains.domain.domain", true ),
		'type' => "text"
	),
));

// Nameserver fields
Configure::set( "universal_domains.nameserver_fields", array(
	'ns1' => array(
		'label' => Language::_( "universal_domains.nameserver.ns1", true ),
		'type' => "text"
	),
	'ns2' => array(
		'label' => Language::_("universal_domains.nameserver.ns2", true ),
		'type' => "text"
	),
	'ns3' => array(
		'label' => Language::_( "universal_domains.nameserver.ns3", true ),
		'type' => "text"
	),
	'ns4' => array(
		'label' => Language::_( "universal_domains.nameserver.ns4", true ),
		'type' => "text"
	),
	'ns5' => array(
		'label' => Language::_( "universal_domains.nameserver.ns5", true ),
		'type' => "text"
	)
));

// Whois fields
Configure::set("universal_domains.whois_fields", array(
	'first_name' => array(
		'label' => Language::_("universal_domains.whois.FirstName", true),
		'type' => "text",
		'rp' => 'fn',
		'lp' => 'first_name',
	),
	'last_name' => array(
		'label' => Language::_("universal_domains.whois.LastName", true),
		'type' => "text",
		'rp' => 'ln',
		'lp' => 'last_name',
	),
	'company' => array(
		'label' => Language::_("universal_domains.whois.Organization", true),
		'type' => "text",
		'rp' => 'cp',
		'lp' => 'company',
	),
	'address' => array(
		'label' => Language::_("universal_domains.whois.Address1", true),
		'type' => "text",
		'rp' => 'ad',
		'lp' => 'address1',
	),
	'address2' => array(
		'label' => Language::_("universal_domains.whois.Address2", true),
		'type' => "text",
		'rp' => 'ad2',
		'lp' => 'address2',
	),
	'city' => array(
		'label' => Language::_("universal_domains.whois.City", true),
		'type' => "text",
		'rp' => 'cy',
		'lp' => 'city',
	),
	'state' => array(
		'label' => Language::_("universal_domains.whois.StateProvince", true),
		'type' => "text",
		'rp' => 'st',
		'lp' => 'state',
	),
	'zip' => array(
		'label' => Language::_("universal_domains.whois.PostalCode", true),
		'type' => "text",
		'rp' => 'zp',
		'lp' => 'zip',
	),
	'country' => array(
		'label' => Language::_("universal_domains.whois.Country", true),
		'type' => "text",
		'rp' => 'ct',
		'lp' => 'country',
	),
	'phone' => array(
		'label' => Language::_("universal_domains.whois.Phone", true),
		'type' => "text",
		'rp' => 'ph',
		'lp' => 'phone',
	),
	'email' => array(
		'label' => Language::_("universal_domains.whois.EmailAddress", true),
		'type' => "text",
		'rp' => 'em',
		'lp' => 'email',
	),
));

// .US
Configure::set("universal_domains.domain_fields.us", array(
	'RegistrantNexus' => array(
		'label' => Language::_("universal_domains.domain.RegistrantNexus", true),
		'type' => "select",
		'options' => array(
			'C11' => Language::_("universal_domains.domain.RegistrantNexus.c11", true),
			'C12' => Language::_("universal_domains.domain.RegistrantNexus.c12", true),
			'C21' => Language::_("universal_domains.domain.RegistrantNexus.c21", true),
			'C31' => Language::_("universal_domains.domain.RegistrantNexus.c31", true),
			'C32' => Language::_("universal_domains.domain.RegistrantNexus.c32", true)
		)
	),
	'RegistrantPurpose' => array(
		'label' => Language::_("universal_domains.domain.RegistrantPurpose", true),
		'type' => "select",
		'options' => array(
			'P1' => Language::_("universal_domains.domain.RegistrantPurpose.p1", true),
			'P2' => Language::_("universal_domains.domain.RegistrantPurpose.p2", true),
			'P3' => Language::_("universal_domains.domain.RegistrantPurpose.p3", true),
			'P4' => Language::_("universal_domains.domain.RegistrantPurpose.p4", true),
			'P5' => Language::_("universal_domains.domain.RegistrantPurpose.p5", true)
		)
	)
));
