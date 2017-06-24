<?php
/***********************************************
 * Tiedostojen lataaminen serveriltä.
 **********************************************/
require '_start.php'; global $user;

if ( !$user->isValid() ) { // Vain sisäänkirjautuneille
	header("Location:etusivu.php");
	exit();
}

// Alustetaan muuttujat
$filepath = isset($_POST['filepath']) ? $_POST['filepath'] : null;
$path_parts = isset($filepath) ? pathinfo($filepath) : null;
// Rajoitukset
$allowed_extensions = ["pdf", "txt"];
$allowed_folders = ["tilaukset", "hinnasto"];
$allowed_filename_patterns = ["/^lasku-\d+-{$user->id}$/", "/^hinnasto$/"]; //Regex

if ( !isset($filepath) ) {
	header("Location:etusivu.php");
	exit();
}

// Tarkastetaan tiedostopääte
if ( !in_array($path_parts['extension'], $allowed_extensions, true) ) {
	header("Location:etusivu.php");
	exit();
}
// Tarkastetaan kansio
if ( !in_array(basename($path_parts['dirname']), $allowed_folders, true) ) {
	header("Location:etusivu.php");
	exit();
}
// Jos tavallinen käyttäjä, tarkastetaan vielä tiedoston nimi
if ( !$user->isAdmin() ) {
	$sallittu_tiedosto = false;
	foreach ( (array)$allowed_filename_patterns as $filename_pattern ) {
		if ( preg_match($filename_pattern, $path_parts['filename']) ) {
			$sallittu_tiedosto = true;
			break;
		}
	}
	if ( !$sallittu_tiedosto ) {
		header("Location:etusivu.php");
		exit();
	}
}

// Varmistetaan, että tiedosto on olemassa
if ( !file_exists($filepath) ) {
	header("Location:etusivu.php");
	exit();
}

// Ladataan tiedosto annetusta sijainnista
header("Cache-Control: public");
header("Content-Description: File Transfer");
header("Content-Disposition: attachment; filename=" . $path_parts['basename'] . "");
header("Content-Transfer-Encoding: binary");
header("Content-Type: binary/octet-stream");
readfile($filepath);
