<?php
/**
 * Tässä tiedostossa olisi tarkoitus pitää kaikki mahdolliset AJAX-request tyyppiset pyynnöt.
 */

include "tietokanta.php";
session_start();

if ( !empty($_POST['ostoskori_toiminto']) ) {
	include "ostoskori.class.php";
	$cart = new Ostoskori( $_SESSION['yritys_id'], $db, -1 );
	$cart_product = str_replace(" ", "", $_POST['ostoskori_tuote']);

	switch ($_POST['ostoskori_toiminto']) {
		case "lisaa" :
			$cart->lisaa_tuote( $cart_product, $_POST['ostoskori_maara'] );
			break;
		case "poista" :
			$cart->poista_tuote( $cart_product );
			break;
	}
}

elseif ( !empty($_POST['tuote_ostopyynto']) ) {
	$sql = 'INSERT
			INTO tuote_ostopyynto (tuote_id, kayttaja_id )
			VALUES ( ?, ? ) ';
	$db->query( $sql, [$_POST['tuote_ostopyynto'], $_SESSION['id']] );
}
