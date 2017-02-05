<?php
//TODO: Hinnaston luonti siirretään hinnaston sisäänajon yhteyteen / yöajo
//TODO: Käyttäjä vain lataa hinnaston suoraan serveriltä.
/**
 * Tällä sivulla luodaan hinnasto.txt tiedosto ja lähetetään se käyttäjän koneelle.
 */
require "_start.php"; global $db, $user;
require "tecdoc.php";

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
fwrite($hinnastotiedosto, chr(0xEF).chr(0xBB).chr(0xBF)); //UTF-8 BOM
$offset = 0;
$products = true;
while($products) {  //Haetaan fetchCount verran tuotteita kerrallaan
    $products = $db->query("SELECT * FROM tuote LEFT JOIN ALV_kanta ON tuote.ALV_kanta=ALV_kanta.kanta WHERE aktiivinen=1 LIMIT ?, ?",
        [$offset, $fetchCount], FETCH_ALL);
    $offset += $fetchCount;

    //Kirjoitetaan haetut tuotteet tiedostoon
    foreach ($products as $p) {
        $row = str_pad($p->tuotekoodi, 20 , " ") .
                str_pad(number_format((1+$p->prosentti)*$p->hinta_ilman_ALV,2,',',''), 10 , " ", STR_PAD_LEFT) .
                str_pad($p->nimi, 40 , " ") . "\r\n";
        fwrite($hinnastotiedosto, $row);
    }
}


/** Ladataan tiedosto serveriltä */
$fullPath = $path.$tiedoston_nimi;

if ($fd = fopen ($fullPath, "r")) {
    $fsize = filesize($fullPath);
    $path_parts = pathinfo($fullPath);
    $ext = strtolower($path_parts["extension"]); //extension
	header('Content-Description: File Transfer');
    header("Content-type: text/plain; charset=utf-8"); //UTF-8
    header("Content-Disposition: attachment; filename=\"".$path_parts["basename"]."\"");
    header("Content-length: $fsize");
    header("Cache-control: private"); //use this to open files directly
    while(!feof($fd)) {
        $buffer = fread($fd, 2048);
        echo $buffer;
    }
}
fclose ($fd);
exit;
?>