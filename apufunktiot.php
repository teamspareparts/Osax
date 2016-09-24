<?php

//
// Muotoilee rahasumman muotoon "1 000,00 €"
//
function format_euros($amount) {
	return number_format($amount, 2, ',', '&nbsp;') . ' &euro;';
}

//
// Muotoilee kokonaisluvun muotoon "1 000 000"
//
function format_integer($number) {
	return number_format($number, 0, ',', '&nbsp;');
}

/*
 * Hakee annetun ALV-tason prosentin tietokannasta
 */
function hae_ALV_prosentti($ALV_kanta) {
	global $connection;
	$sql_query = "
			SELECT	prosentti
			FROM	ALV_kanta
			WHERE	kanta = '$ALV_kanta'";
	$result = mysqli_query($connection, $sql_query) or die(mysqli_error($connection));
	$prosentti = mysqli_fetch_assoc($result)['prosentti'];
	return $prosentti;
}
