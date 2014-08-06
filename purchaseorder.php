<?php
 /**
 * @title               WHMCS Purchase Order Gateway
 *
 * @author              Myles McNamara (get@smyl.es)
 * @copyright           Copyright (c) Myles McNamara 2013-2014
 * @Date:               8/6/14
 * @Last Updated:       06 12 37
 */

function purchaseorder_config() {

	$configarray = array(
		"FriendlyName" => array(
			"Type"  => "System",
			"Value" => "Purchase Order"
		),
		"instructions" => array(
			"FriendlyName" => "Purchase Order Instructions",
			"Type"         => "textarea",
			"Rows"         => "5",
			"Value"        => "",
			"Description"  => "The instructions you want displaying to customers who choose this payment method - the invoice number will be shown underneath the text entered above",
		),
	);

	return $configarray;

}

function purchaseorder_link( $params ) {

	global $_LANG;

	$code = "";

	if ( $params[ 'clientdetails' ][ 'customfields6' ] === 'on' ){
		$code .= "<strong style=\"color: #008000;\">Account Authorized</strong>";
	} else {
		$code .= "Your account has not yet been authorized to use Purchase Orders, please contact support";
	}

	$code .= '<p>' . nl2br( $params[ 'instructions' ] ) . '<br />' . $_LANG[ 'invoicerefnum' ] . ': ' . $params[ 'invoiceid' ] . '</p>';

	return $code;

}

?>
