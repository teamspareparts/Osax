<?php
/**
 *	Haetaan itse perustetuille tuotteille lisää vertailunumeroita.
 * 	Tiedosto ajetaan automaattisesti cronjobin avulla, aina 3 min välein.
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
 * Haetaan 10 tuotteelle vertailunumerot tecdocista
 */
$sql = "SELECT tuote_id, articleNo, brandNo
		FROM tuote_linkitys
		WHERE hae_tecdoc_vertailut = 1
		LIMIT 10";
$tuotteet = $db->query($sql, [], FETCH_ALL);

foreach ( $tuotteet as $tuote ) {
	$genericArticleId = null;

	// Haetaan vertailunumerot tecdocista
	$vertailu_tuotteet = getArticleDirectSearchAllNumbersWithState($tuote->articleNo, 10, true);

	// Etsitään genericArticleId, jos hakutuote tecdocissa
	foreach ( $vertailu_tuotteet as $vt ) {
		if ( str_replace(" ", "", $vt->articleNo) == $tuote->articleNo &&
			$vt->brandNo == $tuote->brandNo) {
			$genericArticleId = $vt->genericArticleId;
			break;
		}
	}

	// Vertailutuotteet kantaan
	foreach ($vertailu_tuotteet as $vt) {
		echo $vt->genericArticleId;
		$vt->articleNo = str_replace(" ", "", $vt->articleNo);
		// Jos genericArticleId ei täsmää hakutuotteeseen, hypätään sen yli
		if ( !empty($genericArticleId) && $genericArticleId != $vt->genericArticleId ) {
			continue;
		}
		$sql = "INSERT INTO tuote_linkitys (tuote_id, brandNo, articleNo, genericArticleId)
				VALUES (?, ?, ?, ?)
				ON DUPLICATE KEY UPDATE genericArticleId = VALUES(genericArticleId)";
		$db->query($sql, [$tuote->tuote_id, $vt->brandNo, $vt->articleNo, $genericArticleId]);
	}

	// Merkataan käsitellyksi
	$sql = "UPDATE tuote_linkitys
			SET hae_tecdoc_vertailut = 0
			WHERE tuote_id = ?
				AND brandNo = ?
				AND articleNo = ?";
	$db->query($sql, [$tuote->tuote_id, $tuote->brandNo, $tuote->articleNo]);
}
?>
