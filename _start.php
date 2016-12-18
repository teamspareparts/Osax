<?php
/**
 * For debugging. Tulostaa kaikki tiedot muuttujasta käyttäen print_r()- ja var_dump()-funktioita.
 * @param $var
 */
function debug($var){echo"<br><pre>Print_r ::<br>";print_r($var);echo"<br>Var_dump ::<br>";var_dump($var);echo"</pre><br>";}

/**
 * Tulostaa numeron muodossa 1.000[,00 [€]]
 * @param double|int $number <p> Tulostettava numero/luku/hinta
 * @param bool $int [optional] <p> Kokonaisluvuille eri tulostus ilman decimaalipaikkoja tai euro-merkkiä.
 * @param bool $ilman_euro [optional] <p> Tulostetaanko float-arvo ilman euro-merkkiä
 * @return string
 */
function format_number( /*double|int*/ $number, /*bool*/ $int = false, /*bool*/ $ilman_euro = false ) {
	if ( $int ) { return number_format( (int)$number, 0, ',', '.' );
	} else { return number_format( (double)$number, 2, ',', '.' ) . ( $ilman_euro ? '' : ' &euro;' ); }
}

/*
 * Aloitetaan sessio
 */
session_start();

/*
 * Ladataan sivuston käyttöön tarkoitetut luokat
 */
require "luokat/db_yhteys_luokka.class.php";
require "luokat/user.class.php";
require "luokat/yritys.class.php";
require "luokat/ostoskori.class.php";
require "luokat/tuote.class.php";

/*
 * Haetaan tietokannan tiedot erillisestä tiedostosta, ja yhdistetään tietokantaan.
 */
$db = parse_ini_file("../src/tietokanta/db-config.ini.php");
$db = new DByhteys( $db['user'], $db['pass'], $db['name'], $db['host'] );

/*
 * Luodaan tarvittava oliot
 */
$user = new User( $db, $_SESSION['id'] );
$cart = new Ostoskori( $db, $user->yritys_id );

/*
 * Tarkistetaan, että käyttäjä on olemassa, ja oikea.
 */
if ( !$user->isValid() ) {
	header( 'Location: index.php?redir=4' ); exit;
}
elseif ( !$user->eula_hyvaksytty() ) {
    header( 'Location: eula.php' ); exit;
}
