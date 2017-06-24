<?php
/**
 * Palautetaan varastosaldot niistä tilauksista, jotka on keskeytetty
 * (maksettu = 0, tarkastetaan vain arkipäivinä)
 */


chdir(dirname(__FILE__)); // Määritellään työskentelykansio
set_time_limit(300); // 5min

require "./luokat/dbyhteys.class.php";
$db = new DByhteys();

// Montako tuntia tilauksen pitää olla keskeytynyt, jotta saldot palautetaan
$tunnit_keskeytyneena = 24;

// Haetaan keskeneräisten tilausten tuotteet
$sql = "	SELECT tuote_id, kpl
  			FROM tilaus_tuote
  		  	LEFT JOIN tilaus
  		  		ON tilaus.id = tilaus_tuote.tilaus_id
  		  	WHERE tilaus.paivamaara < (now() - INTERVAL ? HOUR)
 		   		AND tilaus.maksettu = 0 AND tilaus.maksettu IS NOT NULL ";
$tuotteet = $db->query( $sql, [$tunnit_keskeytyneena], FETCH_ALL);

// Haetaan tilausten id:t
$sql = "	SELECT id
  			FROM tilaus
  		  	WHERE tilaus.paivamaara < (now() - INTERVAL ? HOUR)
 		   		AND tilaus.maksettu = 0 AND tilaus.maksettu IS NOT NULL ";
$tilaukset = $db->query( $sql, [$tunnit_keskeytyneena], FETCH_ALL);

if ( !$tilaukset || !$tuotteet ) {
	return;
}

// Päivitetään tuotteiden varastosaldot
$questionmarks = implode( ',', array_fill( 0, count( $tuotteet ), '(?,?)' ) );
$values = [];
$sql = "INSERT INTO tuote (id, varastosaldo) VALUES {$questionmarks}
		ON DUPLICATE KEY 
		UPDATE varastosaldo = varastosaldo + VALUES(varastosaldo), paivitettava = 1";
foreach ( $tuotteet as $tuote ) {
	array_push($values, $tuote->tuote_id, $tuote->kpl);
}
$db->query($sql, $values);

// Merkataan tilaukset epäonnistuneiksi
foreach ( $tilaukset as $tilaus ) {
	$db->query("UPDATE tilaus SET maksettu = -1 WHERE id = ?", [$tilaus->id]);
}