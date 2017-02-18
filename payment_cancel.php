<?php
/**
 * @version 2017-02-18 <p> Lopullinen versio.
 */
require '_start.php'; global $db, $user, $cart;
require 'luokat/paymentAPI.class.php';

/*
 * Jos maksua ei hyväksytä, tai käyttäjä peruuttaa maksun, hänet suunnataan tälle sivulle.
 * Maksun tietojen tarkistuksen jälkeen, tilaus merkitään peruutetuksi, ja tuotteet lisätään takaisin ostoskoriin.
 */

if ( PaymentAPI::checkReturnAuthCode( $_GET, true ) ) {

	// Päivitetään tilaus peruutetuksi
	$sql = "UPDATE tilaus SET maksettu = -1, kasitelty = -1 WHERE id = ? AND kayttaja_id = ?";
	$db->query( $sql, [ $_GET[ 'ORDER_NUMBER' ], $user->id ] );

	// Haetaan tilauksen tuotteet
	$sql = "SELECT tuote_id, kpl FROM tilaus_tuote WHERE tilaus_id = ?";
	$results = $db->query( $sql, [ $_GET[ 'ORDER_NUMBER' ] ] );

	// Varastosaldon korjaus takaisin.
	$db->prepare_stmt( "UPDATE tuote SET varastosaldo = varastosaldo + ? WHERE id = ?" );

	foreach ( $results as $tuote ) {
		$db->run_prepared_stmt( [ $tuote->kpl, $tuote->id ] ); // Varastosaldon korjaus takaisin
		//Lisätään tuotteet takaisin ostoskoriin.
		$cart->lisaa_tuote( $db, $tuote->id, $tuote->kpl );
	}

	// Viesti käyttäjälle.
	//TODO: Lisää passivis-agressiivinen kommentti "Voisitko olla tekemättä tuota uudelleen? It really hurts."
	$_SESSION['feedback'] = "<p class='error'>Tilaus peruutettu. Tuotteet on lisätty takaisin ostoskoriin.</p>";
}

// Lähetetään käyttäjä takaisin ostoskoriin. Se vaikuttaisi loogiselta kohteelta.
header( "location:ostoskori.php" );
exit;
