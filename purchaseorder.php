<?php
/**
 * @title      WHMCS Purchase Order Gateway
 *
 * @author     Myles McNamara (get@smyl.es)
 * @copyright  Copyright (c) Myles McNamara 2014
 * @date       8/11/14
 * @link       http://smyl.es
 */

function purchaseorder_config() {

	$configarray = array(
		"FriendlyName" => array(
			"Type"  => "PO",
			"Value" => "Purchase Order"
		),
		"instructions" => array(
			"FriendlyName" => "Purchase Order Instructions",
			"Type"         => "textarea",
			"Rows"         => "5",
			"Value"        => "",
			"Description"  => "",
		),
	    "customfield" => array(
		    "FriendlyName" => "Auto Activate Client Custom Field",
	        "Type" => "dropdown",
	        "Description" => "Select the custom client field used for auto activation",
	        "Options" => purchaseorder_get_custom_fields()
	    )
	);

	return $configarray;

}

function purchaseorder_get_custom_fields(){
	$options = '';
	$request  = full_query( "SELECT GROUP_CONCAT(`fieldname` SEPARATOR ',' ) AS `emails` FROM table WHERE id=4 GROUP BY id" );

	while( $data = mysql_fetch_array( $request ) ){

		$options .= $data['fieldname'] . ',';

	}

	return $options;

}

function purchaseorder_accept_order( $orderid ) {

	$command               = "acceptorder";
	$adminuser             = "admin";
	$values[ "orderid" ]   = $orderid;
	$values[ "autosetup" ] = TRUE;
	$values[ "sendemail" ] = TRUE;

	$results = localAPI( $command, $values );

	logModuleCall( 'purchaseorder', 'activate', $values, $results );
}

// Output below cart buttons on review and checkout
function purchaseorder_orderformoutput( $params ) {

	return FALSE;
}

// Gateway response array with status, rawdata, etc
function purchaseorder_orderformcheckout( $params ) {

	return FALSE;
}

function purchaseorder_link( $params ) {

	/**
	 * You must set this value below to which custom field you have created and set as the checkbox for the client
	 * to auto activate.  WHMCS did a shitty job of storing custom field variables so you have to count from the top
	 * down to the field you added, and that will be the value below.  So if my custom field was the second one from
	 * the top, it would be the value you see below, customfield2.
	 */
	$auto_activate_custom_field = 'customfields2';

	// That's far enough buddy
	global $CONFIG;
	$code      = "";
	$sysurl    = ( $CONFIG[ 'SystemSSLURL' ] ? $CONFIG[ 'SystemSSLURL' ] : $CONFIG[ 'SystemURL' ] );
	$invoiceid = $params[ 'invoiceid' ];

	$orderid_req  = select_query( 'tblorders', 'id', array( 'invoiceid' => $invoiceid ) );
	$orderid_data = mysql_fetch_array( $orderid_req );

	if ( ! empty ( $orderid_data[ 'id' ] ) ) $orderid = $orderid_data[ 'id' ];

	// Make sure you set the custom field value above
	if ( $params[ 'clientdetails' ][ $auto_activate_custom_field ] == 'on' ) {

		$code .= "<strong style=\"color: #008000;\">Verified Account</strong><br />Invoice will be marked as paid when payment is verified for the PO.";

	} else {

		$code .= "<strong style=\"color: #BA3832;\">Invalid Account</strong><br />You are not authorized to pay via Purchase Order, please contact support, or choose another payment method.";

	}

	if ( $orderid ) purchaseorder_accept_order( $orderid );
	logModuleCall( 'autoactivate', 'activate', 'accepting order...', $orderid );

	// Required to redirect after checking out
	$code .= "<form method=\"POST\" action=\"{$sysurl}/viewinvoice.php?id={$invoiceid}\">";

	return $code;

}
