<?php
/**
 *	Haetaan tuotteille nimi, joilta se vielä puuttuu.
 * 	Tiedosto ajetaan automaattisesti cronjobin avulla, aina 5-10 min välein.
 */

require 'tecdoc.php';
require "luokat/dbyhteys.class.php";
/*
 * Haetaan tietokannan tiedot erillisestä tiedostosta, ja yhdistetään tietokantaan.
 */
$db = new DByhteys();

/*
 * For debugging.
 */
set_time_limit(60);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting( E_ALL );

/*
 * Haetaan 60 tuotteelle nimi tecdocista
 */
$sql = "SELECT id, articleNo, brandNo FROM tuote 
		WHERE nimi IS NULL LIMIT 60";
$products = $db->query($sql, [], FETCH_ALL);
if($products) {
	get_basic_product_info($products);
	foreach ($products as $product) {
		$sql = "UPDATE tuote SET nimi = ? WHERE id = ?";
		$db->query($sql, [$product->articleName, $product->id]);
	}
}
?>
