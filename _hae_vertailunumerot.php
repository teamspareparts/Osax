<?php
/**
 *	Haetaan itse perustetuille tuotteille lisää vertailunumeroita.
 * 	Tiedosto ajetaan automaattisesti cronjobin avulla, aina 5-10 min välein.
 *
 */

//TODO: indev

chdir(dirname(__FILE__)); //Määritellään työskentelykansio
require 'tecdoc.php';
require "luokat/dbyhteys.class.php";
$db = new DByhteys();

/*
 * For debugging.
 */
set_time_limit(60);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting( E_ALL );

/*
 * Haetaan 50 tuotteelle nimi tecdocista
 */
$sql = "SELECT tuote_id, articleNo, brandNo
		FROM tuote_linkitys
		WHERE hae_tecdoc_vertailut = 1
		LIMIT 50";
$tuotteet = $db->query($sql, [], FETCH_ALL);

foreach ( $tuotteet as $tuote ) {
	//Haetaan vertailunumerot tecdocista
	$vertailu_tuotteet = getArticleDirectSearchAllNumbersWithState($tuote->articleNo, 3, true, $tuote->brandNo);
	foreach ($vertailu_tuotteet as $t) {
		$t->articleNo = str_replace(" ", "", $t->articleNo);
		$sql = "INSERT INTO tuote_linkitys (tuote_id, brandNo, articleNo)
				VALUES (?, ?, ?)
				ON DUPLICATE KEY UPDATE tuote_id = tuote_id";
		//$db->query($sql, [$tuote->tuote_id, $t->brandNo, $t->articleNo]);
	}
	// Merkataan käsitellyksi
	$sql = "UPDATE tuote_linkitys
			SET hae_tecdoc_vertailut = 0
			WHERE tuote_id = ?
				AND brandNo = ?
				AND articleNo = ?";
	//$db->query($sql, [$tuote->tuote_id, $tuote->brandNo, $tuote->articleNo]);
}
?>
