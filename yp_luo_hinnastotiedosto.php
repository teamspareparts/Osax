<?php
/**
 * Tällä sivulla luodaan hinnasto.txt tiedosto.
 * Tiedosto ajetaan cronjobilla kerran päivässä.
 */

//Määritellään työskentelykansio cronjobia varten
chdir(dirname(__FILE__));
require 'luokat/dbyhteys.class.php';
$db = new DByhteys();


set_time_limit(180);

//Debuggaukseen
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


$tiedoston_nimi = "hinnasto.txt";
$path = "hinnasto/";
if ( !file_exists("./{$path}") ) { // Tarkistetaan, että kansio on olemassa.
	mkdir( "./{$path}" ); // Jos ei, luodaan se
}

//Kaatuu jos haetaan kaikki tuotteet (yli 150k)
//Nopein kun fetchCount ~10k
$fetchCount = 10000;

/** Hinnastotiedoston tallentaminen serverille */
$hinnastotiedosto = fopen($path.$tiedoston_nimi, "w") or die("Tiedostoa ei voi avata!");
//fwrite($hinnastotiedosto, chr(0xEF).chr(0xBB).chr(0xBF)); //UTF-8 BOM
$offset = 0;
$products = true;
$edellinen_tuote = null;
while( $products ) {  //Haetaan fetchCount verran tuotteita kerrallaan
    $products = $db->query("
        SELECT * FROM tuote 
        LEFT JOIN ALV_kanta 
          ON tuote.ALV_kanta = ALV_kanta.kanta 
        WHERE aktiivinen = 1 AND nimi <> 'UNNAMED' AND nimi IS NOT NULL
        ORDER BY articleNo
        LIMIT ?, ?",
        [$offset, $fetchCount], FETCH_ALL);
    $offset += $fetchCount;

    if ( !isset($edellinen_tuote) ) { // Alustetaan edellinen_tuote
        $edellinen_tuote = $products[0];
    }

    //Kirjoitetaan haetut tuotteet tiedostoon
    foreach ( $products as $tuote ) {
    	if ( $edellinen_tuote->tuotekoodi != $tuote->tuotekoodi ) {
		    $edellinen_tuote->nimi = !empty($edellinen_tuote->hyllypaikka) ? ('* ' . $edellinen_tuote->nimi) : $edellinen_tuote->nimi; // Jos tuotetta hyllyssä, nimen eteen *
		    //Kannassa käytetään UTF-8 encodingia. Muutetaan tuotteen nimi windows-1252 (ANSI) muotoon.
		    $row = str_pad($edellinen_tuote->tuotekoodi, 20, " ") .
			    str_pad(number_format((1 + $edellinen_tuote->prosentti) * $edellinen_tuote->hinta_ilman_ALV, 2, ',', ''), 10, " ", STR_PAD_LEFT) .
			    str_pad(mb_convert_encoding($edellinen_tuote->nimi, "windows-1252", "UTF-8"), 40, " ") . "\r\n";
		    fwrite($hinnastotiedosto, $row);
		    $edellinen_tuote = $tuote;
	    } else {
    		// Jos duplikaatteja, valitaan se kumpi on hyllyssä
			if ( empty($edellinen_tuote->hyllypaikka) && !empty($tuote->hyllypaikka) ) {
				$edellinen_tuote = $tuote;
			} elseif ( $edellinen_tuote->hyllypaikka == $tuote->hyllypaikka ) {
				// Jos samat hyllypaikat, priorisoidaan hinnan mukaan
				if ( ((1 + $edellinen_tuote->prosentti) * $edellinen_tuote->hinta_ilman_ALV) > ((1 + $tuote->prosentti) * $tuote->hinta_ilman_ALV )) {
					$edellinen_tuote = $tuote;
				}
			}
	    }
    }
}

//Poistetaan tiedostosta viimeinen newline
$stat = fstat($hinnastotiedosto);
ftruncate($hinnastotiedosto, $stat['size']-1);

//Suljetaan tiedostokahva
fclose($hinnastotiedosto);


?>