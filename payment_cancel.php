<?php
/**
 * @version 2017-04-10 <p> Korjattu varastosaldojen palautus. Ostoskorin palautus.
 */
require '_start.php'; global $db, $user, $cart;
require 'luokat/paymentAPI.class.php';

/*
 * Jos maksua ei hyväksytä, tai käyttäjä peruuttaa maksun, hänet suunnataan tälle sivulle.
 * Maksun tietojen tarkistuksen jälkeen, tilaus merkitään peruutetuksi, ja tuotteet lisätään takaisin ostoskoriin.
 */

if ( PaymentAPI::checkReturnAuthCode( $_GET, true ) ) {

	$conn = $db->getConnection();
	$conn->beginTransaction();

	try {
		$stmt = $conn->prepare(
			'UPDATE tilaus SET maksettu = -1, kasitelty = -1 WHERE id = ? AND kayttaja_id = ?' );
		$stmt->execute( [ $_GET[ 'ORDER_NUMBER' ], $user->id ] );

		$stmt = $conn->prepare( "SELECT tuote_id, kpl FROM tilaus_tuote WHERE tilaus_id = ?" );
		$stmt->execute( [ $_GET[ 'ORDER_NUMBER' ] ] );
		$results = $stmt->fetchAll();

		// Tuotteiden varastosaldojen palautus takaisin.
		$placeholders = implode( ',', array_fill(0, count($results), '(?,?)') );
		$values = array();
		$stmt = $conn->prepare( "INSERT INTO temp_tuote (tuote_id, varastosaldo) VALUES {$placeholders}" );
		foreach ( $results as $tuote ) {
			array_push( $values, $tuote->tuote_id, $tuote->kpl );
		}
		$stmt->execute( $values );

		// Yhdistetään temp_taulu tuote-taulun tietoihin, joka päivittää varastosaldot takaisin.
		$stmt = $conn->prepare("
				UPDATE tuote 
				JOIN temp_tuote ON tuote.id = temp_tuote.tuote_id 
				SET tuote.varastosaldo = tuote.varastosaldo + temp_tuote.varastosaldo, tuote.paivitettava = 1" );
		$stmt->execute();

		// Lisätään lopuksi tuotteet takaisin ostoskoriin.
		$stmt = $conn->prepare("
				INSERT INTO ostoskori_tuote (ostoskori_id, tuote_id, kpl_maara)
				SELECT ?, tuote_id, varastosaldo FROM temp_tuote
 				ON DUPLICATE KEY UPDATE kpl_maara = VALUES(kpl_maara)" );
		$stmt->execute( [ $cart->ostoskori_id ] );

		// Tyhjennetään temp_tuote -taulu.
		$conn->query( "DELETE FROM temp_tuote" );

		$conn->commit();
		$_SESSION['feedback'] = "<p class='error'>Tilaus peruutettu. Tuotteet on lisätty takaisin ostoskoriin.</p>";

	} catch ( PDOException $ex ) {
		// Rollback any changes, and print error message to user.
		$conn->rollback();
		$_SESSION["feedback"] = "<p class='error'>Tilauksen peruutus ei onnistunut. 
			Ole hyvä, ja ota yhteys ylläpitäjiin.<br>Virhe: ".
			print_r($ex->errorInfo,1)."</p>";
		// TODO: Do not print error message to user in full (only generic)!
	}
}

// Lähetetään käyttäjä takaisin ostoskoriin. Se vaikuttaisi loogiselta kohteelta.
header( "location:ostoskori.php" );
exit;
