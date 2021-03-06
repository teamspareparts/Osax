<?php declare(strict_types=1);

set_include_path(get_include_path().PATH_SEPARATOR.'luokat/');
spl_autoload_extensions('.class.php');
spl_autoload_register();

session_start();

if ( empty( $_SESSION[ 'id' ] ) ) {
	header( 'Location: index.php?redir=4' );
	exit;
}

$db = new DByhteys();
/**
 * @var mixed <p> Tuloksen palauttamista JSON-muodossa. Jokaisessa requestissa haluttu
 * tulos laitetaan tähän muuttujaan, joka sitten tulostetaan JSON-muodossa takaisin vastauksena.
 */
$result = null;


/**
 * Tallennetaan tuotteen nimi ja valmistaja kantaan.
 */
if ( isset( $_POST[ 'tallenna_tuote' ] ) ) {
	$result = $db->query( 'UPDATE tuote SET nimi = ?, valmistaja = ? WHERE id = ? LIMIT 1',
		[ $_POST[ 'tuote_nimi' ], $_POST[ 'tuote_valmistaja' ], (int)$_POST[ 'tuote_id' ] ] );
}
/**
 * Ostoskorin toimintaa varten
 */
else if ( isset( $_POST[ 'ostoskori_toiminto' ] ) ) {
	$cart = new Ostoskori( $db, (int)$_SESSION[ 'yritys_id' ], 0 );
	$tilaustuote = isset($_POST['tilaustuote']) ? true : false;
	$result = $cart->lisaa_tuote( $db, (int)$_POST[ 'tuote_id' ], (int)$_POST[ 'kpl_maara' ], $tilaustuote );
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
	$result = $db->query( $sql, [ (int)$_POST[ 'tuote_ostopyynto' ], (int)$_SESSION[ 'id' ] ] );
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
	$sales = $db->query( $sql, array_values( $_POST ), FETCH_ALL );

	$sql = "SELECT id, tuotekoodi, nimi
			FROM tuote
			JOIN tuoteryhma_tuote ON tuote.id = tuoteryhma_tuote.tuote_id
			WHERE tuoteryhma_id = ?
			ORDER BY id DESC LIMIT 5";
	$tuotteet = $db->query( $sql, array_values( $_POST ), FETCH_ALL );

	$result = [$sales, $tuotteet];
}

/**
 * Haetaan tuotteen tiedot kannasta tuotemodalia varten
 */
elseif ( isset( $_POST[ 'tuote_modal_tiedot' ] ) ) {
	$tuote = new Tuote($db, (int)$_POST['tuote_id']);
	$tuote->haeAlennukset($db);
	$tuote->haeVertailutuote($db);
	$result = $tuote;
}

/**
 * Haetaan tuotteelle vertailutuotteet omien linkitysten perusteella. (Palauttaa vertailutuotteet arrayna)
 */
elseif ( isset( $_POST[ 'tuote_modal_omat_vertailutuotteet' ] ) ) {
	if ( $_POST['tuotteet'] ) {
		$questionmarks = implode(',', array_fill(0, count($_POST['tuotteet'])/2, 'ROW(?,?)'));
		$sql = "SELECT 	    tuote.articleNo, tuote.valmistaja
				FROM 	    tuote
				LEFT JOIN 	tuote_linkitys ON tuote_linkitys.tuote_id = tuote.id
				WHERE 	    (tuote_linkitys.articleNo, tuote_linkitys.brandNo) IN ({$questionmarks})
					AND		tuote.aktiivinen = 1
					AND		tecdocissa = 0";
		$result = $db->query($sql, $_POST['tuotteet'], FETCH_ALL);
		$result = !empty($result) ? array_unique($result, SORT_REGULAR) : [];
	}
}

/**
 * Haetaan tuotteelle Eoltaksen tehdassaldo reaaliaikaisesti. (Palauttaa int|null)
 */
elseif ( isset( $_POST[ 'eoltas_tehdassaldo' ] ) ) {
	$result = EoltasWebservice::getEoltasTehdassaldo( (int)$_POST['hankintapaikka_id'], $_POST['articleNo'], $_POST['brandName'] );
}

/**
 * Lisätään käyttäjän IP, jotta tecdoc toimii
 */
elseif ( isset( $_POST[ 'tecdoc_add_ip' ] ) ) {
	require 'tecdoc.php';
	$result = addDynamicAddress();
}

else {
	$result = 'Jotain meni väärin\n';
	$result .= print_r($_POST, true);

}

header('Content-Type: application/json'); // Paluuarvo JSON-muodossa
echo json_encode( $result ); // Tulos palautuu takaisin JSON-muodossa AJAX:in pyytäneelle javascriptille.
exit();
