<?php
/**
 * @param DByhteys $db
 * @param int      $tuote_id
 * @param string   $nimi
 * @param string   $valmistaja
 * @return int
 */
function tallenna_nimi_ja_valmistaja( DByhteys $db, /*int*/ $tuote_id, /*string*/ $nimi, /*string*/ $valmistaja ) {
	return $db->query( 'UPDATE tuote SET nimi = ?, valmistaja = ? WHERE id = ? LIMIT 1',
					   [ $nimi, $valmistaja, $tuote_id ] );
}

session_start();

if ( empty( $_SESSION[ 'id' ] ) ) {
	header( 'Location: index.php?redir=4' );
	exit;
}

require "luokat/dbyhteys.class.php";
$db = new DByhteys();
/**
 * @var Mixed <p> Tuloksen palauttamista JSON-muodossa. Jokaisessa requestissa haluttu
 * tulos laitetaan tähän muuttujaan, joka sitten tulostetaan JSON-muodossa takaisin vastauksena.
 */
$result = null;

/**
 * Ostoskorin toimintaa varten
 */
if ( isset( $_POST[ 'ostoskori_toiminto' ] ) ) {
	tallenna_nimi_ja_valmistaja( $db, $_POST[ 'tuote_id' ], $_POST[ 'tuote_nimi' ], $_POST[ 'tuote_valmistaja' ] );
	require "luokat/ostoskori.class.php";
	$cart = new Ostoskori( $db, $_SESSION[ 'yritys_id' ], 0 );
	$result = $cart->lisaa_tuote( $db, $_POST[ 'tuote_id' ], $_POST[ 'kpl_maara' ] );
	if ( $result ) {
		$result = [ 'success' => true,
			'tuotteet_kpl' => $cart->montako_tuotetta,
			'yhteensa_kpl' => $cart->montako_tuotetta_kpl_maara_yhteensa, ];
	}
}

/**
 * Tuotteen ostospyyntöä varten.
 */
elseif ( !empty( $_POST[ 'tuote_ostopyynto' ] ) ) {
	$sql = "INSERT INTO tuote_ostopyynto (tuote_id, kayttaja_id ) VALUES ( ?, ? )";
	$result = $db->query( $sql, [ $_POST[ 'tuote_ostopyynto' ], $_SESSION[ 'id' ] ] );
}

/**
 * Tuotteen hankintapyyntöä varten. Hankintapyynnössä haluttua tuotetta
 * ei ole vielä meidän tietokannassa, joten sillä on erillinen taulu.
 */
elseif ( !empty( $_POST[ 'tuote_hankintapyynto' ] ) ) {
	$sql = "INSERT INTO tuote_hankintapyynto (articleNo, valmistaja, tuotteen_nimi, selitys, korvaava_okey, kayttaja_id)
			VALUES ( ?, ?, ?, ?, ?, ? )";
	$result = $db->query( $sql, [ $_POST[ 'articleNo' ], $_POST[ 'valmistaja' ], $_POST[ 'tuotteen_nimi' ],
							  $_POST[ 'selitys' ], $_POST[ 'korvaava_okey' ], $_SESSION[ 'id' ] ] );
}

/**
 * Haetaan tuotteen hankintapaikan ostotilauskirjat
 */
elseif ( !empty( $_POST[ 'hankintapaikan_ostotilauskirjat' ] ) ) {
	tallenna_nimi_ja_valmistaja( $db, $_POST[ 'tuote_id' ], $_POST[ 'tuote_nimi' ], $_POST[ 'tuote_valmistaja' ] );
	$sql = "SELECT id, tunniste FROM ostotilauskirja WHERE hankintapaikka_id = ?";
	$result = $db->query( $sql, [ $_POST[ 'hankintapaikka_id' ] ], FETCH_ALL );
}

/**
 * Haetaan tuotteen hankintapaikat
 */
elseif ( !empty( $_POST[ 'valmistajan_hankintapaikat' ] ) ) {
	$sql = "SELECT hankintapaikka.id, hankintapaikka.nimi FROM brandin_linkitys
			LEFT JOIN hankintapaikka ON brandin_linkitys.hankintapaikka_id = hankintapaikka.id
			WHERE brandin_linkitys.brandi_id = ?";
	$result = $db->query( $sql, [ $_POST[ 'brand_id' ] ], FETCH_ALL );
}

/**
 * Tuotteen lisäys ostotilauskirjalle
 */
elseif ( !empty( $_POST[ 'lisaa_tilauskirjalle' ] ) ) {
	$sql = "INSERT IGNORE INTO ostotilauskirja_tuote (ostotilauskirja_id, tuote_id, kpl, selite, lisays_kayttaja_id)
            VALUES ( ?, ?, ?, ?, ?)";
	$result = $db->query( $sql, [ $_POST['ostotilauskirja_id'], $_POST['tuote_id'], $_POST['kpl'], $_POST['selite'], $_SESSION['id'] ] );
}

/**
 * isset, koska voi palauttaa nollaa (0: Tarkistettu, ei toimenpiteitä)
 */
elseif ( isset( $_POST[ 'ostopyyntojen_kasittely' ] ) ) {
	$sql = "UPDATE tuote_ostopyynto SET kasitelty = ? WHERE tuote_id = ? AND kayttaja_id = ? AND pvm = ?";
	$result = $db->query( $sql, array_values( $_POST ) );
	//TODO: Sähköpostin lähetys asiakkaalle
}

/**
 * isset, koska voi palauttaa nollaa (0: Tarkistettu, ei toimenpiteitä)
 */
elseif ( isset( $_POST[ 'hankintapyyntojen_kasittely' ] ) ) {
	$sql = "UPDATE tuote_hankintapyynto SET kasitelty = ? WHERE articleNo = ? AND kayttaja_id = ? AND pvm = ?";
	$result = $db->query( $sql, array_values( $_POST ) );
	//TODO: Sähköpostin lähetys asiakkaalle
}

/**
 *
 */
elseif ( isset( $_POST[ 'tuoteryhma_alennukset' ] ) ) {
	$sql = "SELECT tuoteryhma_erikoishinta.id, yritys_id, yritys.nimi AS yritys_nimi, hankintapaikka_id,
				hankintapaikka.nimi AS hkp_nimi, maaraalennus_kpl, tuoteryhma_erikoishinta.alennus_prosentti,
 				DATE_FORMAT(alkuPvm, '%Y-%m-%d') AS alkuPvm, DATE_FORMAT(loppuPvm, '%Y-%m-%d') AS loppuPvm
			FROM tuoteryhma_erikoishinta
			LEFT JOIN yritys ON yritys_id = yritys.id
			JOIN hankintapaikka ON hankintapaikka_id = hankintapaikka.id
			WHERE tuoteryhma_id = ?";
	$result = $db->query( $sql, array_values( $_POST ), FETCH_ALL );
}

/**
 * Haetaan tuotteen tiedot kannasta tuotemodalia varten
 */
elseif ( isset( $_POST[ 'tuote_modal_tiedot' ] ) ) {
	$sql = "SELECT 	    tuote.*, (hinta_ilman_alv * (1+ALV_kanta.prosentti)) AS hinta, 
                        toimittaja_tehdassaldo.tehdassaldo,
                        tuote_linkitys.articleNo as c_articleNo, tuote_linkitys.brandNo as c_brandNo,
                        tuote_linkitys.genericArticleId as c_genericArticleId
			FROM 	    tuote
			JOIN 	    ALV_kanta ON tuote.ALV_kanta = ALV_kanta.kanta
			LEFT JOIN 	tuote_linkitys ON tuote_linkitys.tuote_id = tuote.id
  			LEFT JOIN	toimittaja_tehdassaldo ON tuote.hankintapaikka_id = toimittaja_tehdassaldo.hankintapaikka_id
						AND tuote.articleNo = toimittaja_tehdassaldo.tuote_articleNo
			WHERE 	    tuote.id = ? AND tuote.aktiivinen = 1
			LIMIT 1";
	$result = $db->query( $sql, [$_POST['tuote_id']]);
}

header('Content-Type: application/json'); // Paluuarvo JSON-muodossa
echo json_encode( $result ); // Tulos palautuu takaisin JSON-muodossa AJAX:in pyytäneelle javascriptille.
exit();
