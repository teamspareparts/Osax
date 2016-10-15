<?php
/**
 * Tarkistaa onko käyttäjä kirjautunut sisään.
 * Jos käyttäjä ei ole kirjautunut sisään, funktio heittää hänet ulos.
 * Muussa tapauksessa funktio ei tee mitään.
 */
function check_login_status() {
	if ( empty($_SESSION['id']) ) {
		header('Location: index.php?redir=4'); exit;
	}
}

/**
 * Tarkistaa onko käyttäjä admin.
 * Onko admin-arvo asetettu Session-datassa.
 * @return Boolean <p> Onko arvo asetettu ja TRUE.
 */
function is_admin() {
	return isset($_SESSION['admin']) && $_SESSION['admin'] == 1;
}

/**
 * For debuggin. Tulostaa kaikki tiedot muuttujasta käyttäen print_r()- ja var_dump()-funktioita.
 * @param $var
 */
function debug($var){echo"<br><pre>Print_r ::<br>";print_r($var);echo"<br>Var_dump ::<br>";var_dump($var);echo"</pre><br>";}

/*
 * Aloitetaan sessio ja tarkistetaan kirjautuminen jo ennen kaikkea muuta
 */
session_start();
check_login_status();

/*
 * Ladataan sivuston käyttöön tarkoitetut luokat
 */
require "luokat/db_yhteys_luokka.class.php";
require "luokat/user.class.php";
require "luokat/yritys.class.php";
require "luokat/ostoskori.class.php";

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
