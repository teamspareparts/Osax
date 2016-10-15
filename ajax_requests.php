<?php
/**
 * Tässä tiedostossa olisi tarkoitus pitää kaikki mahdolliset AJAX-request tyyppiset pyynnöt.
 */

session_start();
if ( empty($_SESSION['id']) ) { header('Location: index.php?redir=4'); exit; }

require "luokat/db_yhteys_luokka.class.php";
$db = parse_ini_file("../src/tietokanta/db-config.ini.php");
$db = new DByhteys( $db['user'], $db['pass'], $db['name'], $db['host'] );
/**
 * @var String <p> Tuloksen palauttamista JSON-muodossa. Jokaisessa requestissa haluttu
 * tulos laitetaan tähän muuttujaan, joka sitten tulostetaan JSON-muodossa takaisin vastauksena.
 */
$result = NULL;

/**
 * Ostoskorin toimintaa varten
 */
if ( isset($_POST['ostoskori_toiminto']) ) {

	require "luokat/ostoskori.class.php";
	$cart = new Ostoskori( $db, $_SESSION['yritys_id'], 0 );
	$result = $cart->lisaa_tuote( $db, $_POST['tuote_id'], $_POST['kpl_maara'] );
    if ( $result ) {
        $result = [
            'success' => true,
            'tuotteet_kpl' => $cart->hae_tuotteiden_maara(),
            'yhteensa_kpl' => $cart->hae_kaikkien_tuotteiden_kappalemaara(),
        ];
    }
}

/**
 * Tuotteen ostospyyntöä varten.
 */
elseif ( !empty($_POST['tuote_ostopyynto']) ) {
	$sql = "INSERT INTO tuote_ostopyynto (tuote_id, kayttaja_id )
			VALUES ( ?, ? )";
	$result = $db->query( $sql, [$_POST['tuote_ostopyynto'], $_SESSION['id']] );
}

/**
 * Tuotteen hankintapyyntöä varten. Hankintapyynnössä haluttua tuotetta
 * ei ole vielä meidän tietokannassa, joten sillä on erillinen taulu.
 */
elseif ( !empty($_POST['tuote_hankintapyynto']) ) {
	$sql = "INSERT INTO tuote_hankintapyynto (articleNo, brandName, selitys, korvaava_okey, kayttaja_id )
			VALUES ( ?, ?, ?, ?, ? )";
	$result = $db->query( $sql,
		[$_POST['articleNo'], $_POST['brandNo'], $_POST['selitys'], (int)$_POST['korvaava_okey'], $_SESSION['id']] );
}

/**
 * Eulan vahvistus
 */
elseif ( !empty($_POST['eula_vahvista']) ) {
	$sql = "UPDATE kayttaja SET vahvista_eula = '0' WHERE id = ?";
	$result = $db->query( $sql, [$_POST['user_id']] );
}

header('Content-Type: application/json'); // Paluuarvo JSON-muodossa
echo json_encode( $result ); // Tulos palautuu takaisin JSON-muodossa AJAX:in pyytäneelle javascriptille.
exit();
