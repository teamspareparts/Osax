<?php
/**
 * @version 2017-02-13 <p> WIP
 */
require '_start.php'; global $db, $user, $cart;
require 'luokat/paymentAPI.class.php';


if ( PaymentAPI::checkReturnAuthCode( $_GET, true ) ) {

	$sql = "UPDATE tilaus SET maksettu = -1, kasitelty = -1 WHERE id = ? AND kayttaja_id = ?";
	$db->query( $sql, [ $_GET[ 'ORDER_NUMBER' ], $user->id ] );

	$sql = "SELECT tuote_id, kpl FROM tilaus_tuote WHERE tilaus_id = ?";
	$results = $db->query( $sql, [ $_GET[ 'ORDER_NUMBER' ] ] );

	foreach ( $results as $tuote ) {
		$cart->lisaa_tuote( $db, $tuote->id, $tuote->kpl );
	}

}
