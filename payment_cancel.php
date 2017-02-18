<?php
/**
 * @version 2017-02-xx <p> WIP
 */
require '_start.php'; global $db, $user, $cart;
require 'luokat/paymentAPI.class.php';

/*
 * Jos maksua ei hyväksytä, tai käyttäjä peruuttaa maksun, hänet suunnataan tälle sivulle.
 * Maksun tietojen tarkistuksen jälkeen, tilaus merkitään peruutetuksi, ja tuotteet lisätään takaisin ostoskoriin.
 */

if ( PaymentAPI::checkReturnAuthCode( $_GET, true ) ) {

	$sql = "UPDATE tilaus SET maksettu = -1, kasitelty = -1 WHERE id = ? AND kayttaja_id = ?";
	$db->query( $sql, [ $_GET[ 'ORDER_NUMBER' ], $user->id ] );

	$sql = "SELECT tuote_id, kpl FROM tilaus_tuote WHERE tilaus_id = ?";
	$results = $db->query( $sql, [ $_GET[ 'ORDER_NUMBER' ] ] );

	//TODO: Palauta tuotteiden kpl-määrä

	foreach ( $results as $tuote ) {
		$cart->lisaa_tuote( $db, $tuote->id, $tuote->kpl );
	}

	$_SESSION['feedback'] = "<p class='error'>Tilaus peruutettu. Tuotteet on lisätty takaisin ostoskoriin.</p>";
}



header( "location:ostoskori.php" );
exit;
