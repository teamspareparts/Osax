<?php
/**
 * Palautetaan varastosaldot niistä tilauksista, jotka on keskeytetty
 * (maksettu = 0 yli 2 päivää, poislukien viikonloppu)
 */


chdir(dirname(__FILE__)); //Määritellään työskentelykansio
set_time_limit(300);

require "./luokat/dbyhteys.class.php";
$db = new DByhteys();

// Montako päivää tilauksen pitää olla keskeytynyt, jottasaldot palautetaan
$paivat_keskyetyneena = 2;

//Haetaan keskeneräisten tilausten tuotteet, jotka olleet kesken yli 4 päivää
$sql = "	SELECT *
  			FROM tilaus_tuote
  		  	LEFT JOIN tilaus
  		  		ON tilaus.id = tilaus_tuote.tilaus_id
  		  	WHERE tilaus.paivamaara < (now() - INTERVAL ? DAY)
 		   		AND tilaus.maksettu = 0 AND tilaus.maksettu IS NOT NULL ";
$tuotteet = $db->query($sql, [$paivat_keskyetyneena], FETCH_ALL);

//Haetaan tilausten id:t
$sql = "	SELECT id
  			FROM tilaus
  		  	WHERE tilaus.paivamaara < (now() - INTERVAL ? DAY)
 		   		AND tilaus.maksettu = 0 AND tilaus.maksettu IS NOT NULL ";
$tilaukset = $db->query($sql, [$paivat_keskyetyneena], FETCH_ALL);

if ( !$tilaukset || !$tuotteet ) {
	return;
}

//Tuotteiden varastosaldot temp-tauluun
$questionmarks = implode( ',', array_fill( 0, count( $tuotteet ), '(?,?)' ) );
$values = [];
$sql = "INSERT INTO temp_tuote (tuote_id, varastosaldo) VALUES {$questionmarks}
		ON DUPLICATE KEY UPDATE varastosaldo = ( varastosaldo + VALUES(varastosaldo) )";
foreach ( $tuotteet as $tuote ) {
	array_push( $values, $tuote->tuote_id, ($tuote->kpl) );
}
$db->query($sql, $values);

//Päivitetään tuotteiden varastosaldot ja merkataan ostoautomaatiota varten
$db->query("UPDATE tuote JOIN temp_tuote
            ON tuote.id = temp_tuote.tuote_id 
            SET tuote.varastosaldo = ( tuote.varastosaldo + temp_tuote.varastosaldo ) ,
                tuote.paivitettava = 1");
$db->query( "DELETE FROM temp_tuote" );

//Merkataan tilaukset epäonnistuneiksi
foreach ( $tilaukset as $tilaus ) {
	$db->query("UPDATE tilaus SET maksettu = -1 WHERE id = ?", [$tilaus->id]);
}
