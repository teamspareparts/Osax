<?php
/**
 * Tällä sivulla luodaan hinnasto.txt tiedosto.
 * Tiedosto ajetaan cronjobilla kerran päivässä.
 */

//Määritellään työskentelykansio cronjobia varten
chdir(dirname(__FILE__));
require 'luokat/db_yhteys_luokka.class.php';
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
while( $products ) {  //Haetaan fetchCount verran tuotteita kerrallaan
    $products = $db->query("
        SELECT * FROM tuote 
        LEFT JOIN ALV_kanta 
          ON tuote.ALV_kanta=ALV_kanta.kanta 
        WHERE aktiivinen=1 AND nimi <> 'UNNAMED' AND nimi IS NOT NULL
        LIMIT ?, ?",
        [$offset, $fetchCount], FETCH_ALL);
    $offset += $fetchCount;

    //Kirjoitetaan haetut tuotteet tiedostoon
    foreach ( $products as $p ) {
    	$p->nimi = isset($p->hyllypaikka) ? ('* ' . $p->nimi) : $p->nimi; // Jos tuotetta hyllyssä, nimen eteen *
        //Kannassa käytetään UTF-8 encodingia. Muutetaan tuotteen nimi windows-1252 (ANSI) muotoon.
        $row = str_pad( $p->tuotekoodi, 20 , " " ) .
                str_pad( number_format((1+$p->prosentti)*$p->hinta_ilman_ALV,2,',',''), 10 , " ", STR_PAD_LEFT ) .
                str_pad( mb_convert_encoding($p->nimi, "windows-1252", "UTF-8"), 40 , " " ) . "\r\n";
        fwrite($hinnastotiedosto, $row);
    }
}

//Poistetaan tiedostosta viimeinen newline
$stat = fstat($hinnastotiedosto);
ftruncate($hinnastotiedosto, $stat['size']-1);

//Suljetaan tiedostokahva
fclose($hinnastotiedosto);


?>