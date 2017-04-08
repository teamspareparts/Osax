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


	// Tuotteiden varastosaldojen palautus takaisin.
	$placeholders = implode( ',', array_fill(0, count($results), '(?,?)') );
	$values = array();
	$db->prepare_stmt( "INSERT INTO temp_tuote (tuote_id, varastosaldo) VALUES {$placeholders}" );
	foreach ( $results as $tuote ) {
		array_push( $values, $tuote->id, ($tuote->varastosaldo - $tuote->kpl) );
	}
	$db->run_prepared_stmt( $values );

	// Yhdistetään temp_taulu tuote-taulun tietoihin, joka päivittää varastosaldot takaisin.
	$db->prepare_stmt("
            UPDATE tuote 
            JOIN temp_tuote ON tuote.id = temp_tuote.tuote_id 
            SET tuote.varastosaldo = tuote.varastosaldo - temp_tuote.varastosaldo, tuote.paivitettava = 1" );
	$db->run_prepared_stmt();

	// Tyhjennetään temp_tuote -taulu.
	$db->query( "DELETE FROM temp_tuote" );

	// Viesti käyttäjälle.
	//TODO: Lisää passivis-agressiivinen kommentti "Voisitko olla tekemättä tuota uudelleen? It really hurts."
	$_SESSION['feedback'] = "<p class='error'>Tilaus peruutettu. Tuotteet on lisätty takaisin ostoskoriin.</p>";
}

// Lähetetään käyttäjä takaisin ostoskoriin. Se vaikuttaisi loogiselta kohteelta.
header( "location:ostoskori.php" );
exit;
