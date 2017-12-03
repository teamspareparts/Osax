<?php
/**
 *	Haetaan itse perustetuille tuotteille lisää vertailunumeroita.
 * 	Tiedosto ajetaan automaattisesti cronjobin avulla, aina 3 min välein.
 *
 */

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
		WHERE genericArticleId IS NULL
		LIMIT 10";
$tuotteet = $db->query($sql, [], FETCH_ALL);

foreach ( $tuotteet as $tuote ) {
	$generic_article_id = -1;

	// Haetaan tuote tecdocista
	$tecdoc_tuote = getArticleDirectSearchAllNumbersWithState($tuote->articleNo, 0, true, $tuote->brandNo);

	if ( count($tecdoc_tuote) === 1 ) {
		$generic_article_id = $tecdoc_tuote[0]->genericArticleId;
	}

	// Lisätään genericArticleId
	$sql = "UPDATE tuote_linkitys
			SET genericArticleId = ?
			WHERE tuote_id = ?
				AND brandNo = ?
				AND articleNo = ?";
	$db->query($sql, [$generic_article_id, $tuote->tuote_id, $tuote->brandNo, $tuote->articleNo]);
}

?>
