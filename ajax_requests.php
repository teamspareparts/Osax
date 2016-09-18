<?php
/**
 * Tässä tiedostossa olisi tarkoitus pitää kaikki mahdolliset AJAX-request tyyppiset pyynnöt.
 */

require "_start.php";

/**
 * Ostoskorin toimintaa varten
 */
if ( isset($_POST['ostoskori_toiminto']) ) {
	$cart = new Ostoskori( $db, $_SESSION['yritys_id'], -1 ); //FIXME: Se luo jo cartin _startissa. Korjaa.
	$cart->lisaa_tuote( $_POST['tuote_id'], $_POST['kpl_maara'] );
}

/**
 * Tuotteen ostospyyntöä varten.
 */
elseif ( !empty($_POST['tuote_ostopyynto']) ) {
	$sql = 'INSERT INTO tuote_ostopyynto (tuote_id, kayttaja_id )
			VALUES ( ?, ? ) ';
	$db->query( $sql, [$_POST['tuote_ostopyynto'], $_SESSION['id']] );
}

/**
 * Tuotteen hankintapyyntöä varten. Hankintapyynnössä haluttua tuotetta
 * ei ole vielä meidän tietokannassa, joten sillä on erillinen taulu.
 */
elseif ( !empty($_POST['tuote_hankintapyynto']) ) {
	$sql = 'INSERT INTO tuote_hankintapyynto (articleNo, brandNo, kayttaja_id )
			VALUES ( ?, ?, ? ) ';
	$db->query( $sql, [$_POST['tuote_articleNo'], $_POST['tuote_brandNo'], $_SESSION['id']] );
}
