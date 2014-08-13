<?php
/**
 * @title      WHMCS Purchase Order Gateway
 *
 * @author     Myles McNamara (get@smyl.es)
 * @copyright  Copyright (c) Myles McNamara 2014
 * @date       8/13/14
 * @link       http://smyl.es
 */

function purchaseorder_config() {

	$configarray = array(
		"FriendlyName" => array(
			"Type"  => "PO",
			"Value" => "Purchase Order"
		),
		"approvedforpo" => array(
			"FriendlyName" => "Approved for PO Message",
			"Type"         => "textarea",
			"Rows"         => "5",
			"Value"        => "<strong style=\"color: #008000;\">Verified Account</strong><br />Invoice will be marked as paid when payment is verified for the PO.",
			"Description"  => "",
		),
		"notapprovedforpo" => array(
			"FriendlyName" => "Not Approved for PO Message",
			"Type"         => "textarea",
			"Rows"         => "5",
			"Value"        => "<strong style=\"color: #BA3832;\">Invalid Account</strong><br />You are not authorized to pay via Purchase Order, please contact support, or choose another payment method.",
			"Description"  => "",
		),
		"verifiedaccountfield" => array(
			"FriendlyName" => "Approved for PO Client Custom Field",
			"Type"         => "dropdown",
			"Description"  => "Select the custom field used to Verify/Approve a client to use Purchase Orders.",
			"Options"      => purchaseorder_get_custom_fields()
		),
		"enableautoactivatefield" => array(
			"FriendlyName" => "Enable Auto Activate",
			"Type"         => "yesno"
		),
	    "autoactivatefield" => array(
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
	$request  = full_query( "SELECT GROUP_CONCAT(fieldname) FROM tblcustomfields where type='client' GROUP BY type" );
	$values = mysql_fetch_array( $request );

	return $values[0];

}

function purchaseorder_accept_order( $orderid ) {

	$command               = "acceptorder";
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

	global $CONFIG;
	$code      = "";
	$cart_action = filter_input( INPUT_GET, 'a', FILTER_SANITIZE_STRING );
	$actual_auto_activate_value = array();
	$actual_verified_account_value = array();

	$sysurl    = ( $CONFIG[ 'SystemSSLURL' ] ? $CONFIG[ 'SystemSSLURL' ] : $CONFIG[ 'SystemURL' ] );
	$invoiceid = $params[ 'invoiceid' ];

	if( $params['enableautoactivatefield'] == 'on' && ! empty( $params[ 'autoactivatefield' ] ) ){

		$auto_activate_field_label = $params['autoactivatefield'];
		$auto_activate_query = select_query( 'tblcustomfields', 'id', array( 'fieldname' => $auto_activate_field_label ) );
		$auto_activate_id = mysql_fetch_array( $auto_activate_query );

	}

	if ( ! empty( $params[ 'verifiedaccountfield' ] ) ) {

		$verified_account_field_label = $params[ 'verifiedaccountfield' ];
		$verified_account_query       = select_query( 'tblcustomfields', 'id', array( 'fieldname' => $verified_account_field_label ) );
		$verified_account_id          = mysql_fetch_array( $verified_account_query );

	}

//	echo "AUTO ACTIVATE ID =" . $auto_activate_id;
//	echo "VERIFIED ACCOUNT ID =" . $verified_account_id;

	foreach ( $params[ 'clientdetails' ][ 'customfields' ] as $customfield ) {
		if ( $customfield[ 'id' ] == $auto_activate_id[0] ) {
			$actual_auto_activate_value = $customfield[ 'value' ];
		}

		if( $customfield[ 'id' ] == $verified_account_id[0] ) {
			$actual_verified_account_value = $customfield['value'];
		}
	}

	$orderid_req  = select_query( 'tblorders', 'id', array( 'invoiceid' => $invoiceid ) );
	$orderid_data = mysql_fetch_array( $orderid_req );

	if ( ! empty ( $orderid_data[ 'id' ] ) ) $orderid = $orderid_data[ 'id' ];

	// Make sure you set the custom field value above
	if ( $actual_verified_account_value == 'on' ) {

		$code .= html_entity_decode($params['approvedforpo']);

	} else {

		$code .= html_entity_decode($params['notapprovedforpo']);

	}

	if( $actual_auto_activate_value == 'on' && ! empty( $orderid ) && $cart_action != 'complete' ){
		purchaseorder_accept_order( $orderid );
		logModuleCall( 'autoactivate', 'activate', 'accepting order...', $orderid );
	}

	// Required to redirect after checking out
	$code .= "<form method=\"POST\" action=\"{$sysurl}/viewinvoice.php?id={$invoiceid}\">";

	return $code;

}
