<?php
/**
 * Tässä tiedostossa olisi tarkoitus pitää kaikki mahdolliset AJAX-request tyyppiset pyynnöt.
 */

/**
 * @param DByhteys $db
 * @param $tuote_id
 * @param $nimi
 * @param $valmistaja
 * @return int
 */
function tallenna_nimi_ja_valmistaja( DByhteys $db, /*int*/ $tuote_id, /*string*/ $nimi, /*string*/ $valmistaja ) {
	$sql = 'UPDATE tuote SET nimi = ?, valmistaja = ? WHERE id = ? LIMIT 1';
	return $db->query( $sql, [$nimi, $valmistaja, $tuote_id] );
}

session_start();
if ( empty($_SESSION['id']) ) { header('Location: index.php?redir=4'); exit; }

require "luokat/db_yhteys_luokka.class.php";
$db = parse_ini_file("../src/tietokanta/db-config.ini.php");
$db = new DByhteys( $db['user'], $db['pass'], $db['name'], $db['host'] );
/**
 * @var Mixed <p> Tuloksen palauttamista JSON-muodossa. Jokaisessa requestissa haluttu
 * tulos laitetaan tähän muuttujaan, joka sitten tulostetaan JSON-muodossa takaisin vastauksena.
 */
$result = NULL;

/**
 * Ostoskorin toimintaa varten
 */
if ( isset($_POST['ostoskori_toiminto']) ) {
	tallenna_nimi_ja_valmistaja( $db, $_POST['tuote_id'], $_POST['tuote_nimi'], $_POST['tuote_valmistaja'] );
	require "luokat/ostoskori.class.php";
	$cart = new Ostoskori( $db, $_SESSION['yritys_id'], 0 );
	$result = $cart->lisaa_tuote( $db, $_POST['tuote_id'], $_POST['kpl_maara'] );
    if ( $result ) {
        $result = [
            'success' => true,
            'tuotteet_kpl' => $cart->get_tuotteiden_maara(),
            'yhteensa_kpl' => $cart->get_kaikkien_tuotteiden_kappalemaara(),
        ];
    }
}

/**
 * Tuotteen ostospyyntöä varten.
 */
elseif ( !empty($_POST['tuote_ostopyynto']) ) {
	$sql = "INSERT INTO tuote_ostopyynto (tuote_id, kayttaja_id ) VALUES ( ?, ? )";
	$result = $db->query( $sql, [$_POST['tuote_ostopyynto'], $_SESSION['id']] );
}

/**
 * Tuotteen hankintapyyntöä varten. Hankintapyynnössä haluttua tuotetta
 * ei ole vielä meidän tietokannassa, joten sillä on erillinen taulu.
 */
elseif ( !empty($_POST['tuote_hankintapyynto']) ) {
	$sql = "INSERT INTO tuote_hankintapyynto (articleNo, valmistaja, tuotteen_nimi, selitys, korvaava_okey, kayttaja_id)
			VALUES ( ?, ?, ?, ?, ?, ? )";
	$result = $db->query( $sql,
		[ $_POST['articleNo'], $_POST['valmistaja'], $_POST['tuotteen_nimi'],
			$_POST['selitys'], $_POST['korvaava_okey'], $_SESSION['id'] ] );
}

/**
 * Eulan vahvistus
 */
elseif ( !empty($_POST['eula_vahvista']) ) {
	$sql = "UPDATE kayttaja SET vahvista_eula = '0' WHERE id = ?";
	$result = $db->query( $sql, [$_POST['user_id']] );
}

/**
 * Haetaan tuotteen hankintapaikan ostotilauskirjat
 */
elseif ( !empty($_POST['hankintapaikan_ostotilauskirjat']) ) {
	tallenna_nimi_ja_valmistaja( $db, $_POST['tuote_id'], $_POST['tuote_nimi'], $_POST['tuote_valmistaja'] );
	$sql = "SELECT id, tunniste FROM ostotilauskirja WHERE hankintapaikka_id = ?";
    $result = $db->query( $sql, [$_POST['hankintapaikka_id']], FETCH_ALL);
}

/**
 * Haetaan tuotteen hankintapaikat
 */
elseif ( !empty($_POST['valmistajan_hankintapaikat']) ) {
	$sql = "SELECT hankintapaikka.id, hankintapaikka.nimi FROM valmistajan_hankintapaikka
			LEFT JOIN hankintapaikka ON valmistajan_hankintapaikka.hankintapaikka_id = hankintapaikka.id
			WHERE brandId = ?";
	$result = $db->query( $sql, [$_POST['brand_id']], FETCH_ALL);
}

/**
 * Tuotteen lisäys ostotilauskirjalle
 */
elseif ( !empty($_POST['lisaa_tilauskirjalle'])) {
	$sql = "INSERT IGNORE INTO ostotilauskirja_tuote (ostotilauskirja_id, 
				tuote_id, kpl, lisays_kayttaja_id, lisays_tapa)
            VALUES ( ?, ?, ?, ?, 1)";
	$result = $db->query( $sql, [ $_POST['ostotilauskirja_id'], $_POST['tuote_id'], $_POST['kpl'], $_SESSION['id'] ] );
}


header('Content-Type: application/json'); // Paluuarvo JSON-muodossa
echo json_encode( $result ); // Tulos palautuu takaisin JSON-muodossa AJAX:in pyytäneelle javascriptille.
exit();
