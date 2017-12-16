<?php declare(strict_types=1);
require '_start.php'; global $db, $user, $cart;
require 'tecdoc.php';
require 'apufunktiot.php';

/**
 * Lisää uuden tuotteen tietokantaan.
 * Jos tuote on jo tietokannassa, päivittää uudet tiedot, ja asettaa aktiiviseksi.
 * //TODO: Mitä tehdään keskiostohinnalle ja yhteensa_kpl, kun ON DUPLICATE KEY ?
 * @param DByhteys $db
 * @param array    $val
 * @return bool <p> onnistuiko lisäys. Tosin, jos jotain menee pieleen niin se heittää exceptionin.
 */
function add_product_to_catalog( DByhteys $db, array $val ) : bool {
	$result = $db->query("SELECT aktiivinen FROM tuote WHERE articleNo = ? AND brandNo = ? AND hankintapaikka_id = ?",
		[ $val[0], $val[1], $val[2] ]);

	//Jos ei löydy valikoimasta tai ei ole aktiivinen
	if ( !($result ? $result->aktiivinen : false) ) {
		$sql = "INSERT INTO tuote
					(articleNo, brandNo, hankintapaikka_id, tuotekoodi, tilauskoodi, sisaanostohinta, hinta_ilman_ALV,
					 ALV_kanta, varastosaldo, minimimyyntiera, hyllypaikka, nimi, valmistaja, yhteensa_kpl, 
					 keskiostohinta, ensimmaisen_kerran_varastossa)
				VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, varastosaldo, sisaanostohinta, now())
				ON DUPLICATE KEY UPDATE
					sisaanostohinta=VALUES(sisaanostohinta), hinta_ilman_ALV=VALUES(hinta_ilman_ALV), 
					ALV_kanta=VALUES(ALV_kanta), varastosaldo=VALUES(varastosaldo),
					minimimyyntiera=VALUES(minimimyyntiera), hyllypaikka=VALUES(hyllypaikka), nimi=VALUES(nimi),
					valmistaja=VALUES(valmistaja), tilauskoodi=VALUES(tilauskoodi),
					ensimmaisen_kerran_varastossa = now(), aktiivinen = 1";
		$result = $db->query($sql, $val);
		if ( $result ) {
			return true;
		}
	}

	return false;
}

/**
 * Poistaa tuotteen tietokannasta, asettamalla 'aktiivinen'-kentän -> 0:ksi.
 * @param DByhteys $db
 * @param int      $id
 * @return bool <p> onnistuiko poisto. Tosin, jos jotain menee pieleen, niin DByhteys heittää exceptionin.
 */
function remove_product_from_catalog( DByhteys $db, int $id) : bool {
    $db->query( "DELETE FROM ostotilauskirja_tuote WHERE tuote_id = ?", [$id] );
	$result =  $db->query( "UPDATE tuote SET aktiivinen = 0 WHERE id = ?", [$id] );
	if ( $result ) {
		return true;
	}
	return false;
}

/**
 * Muokkaa aktivoitua tuotetta tietokannassa.
 * Parametrina annetut tiedot tallennetaan tietokantaan.
 * @param DByhteys $db
 * @param array    $val
 * @return bool <p> onnistuiko muutos. Tosin heittää exceptionin, jos jotain menee vikaan haussa.
 */
function modify_product_in_catalog( DByhteys $db, array $val ) : bool {
	$sql = "UPDATE tuote 
			SET keskiostohinta = IFNULL((keskiostohinta * yhteensa_kpl + sisaanostohinta * (?-varastosaldo)) / (yhteensa_kpl - varastosaldo + ?),0),
				yhteensa_kpl = yhteensa_kpl + ? - varastosaldo,
				tilauskoodi = ?, sisaanostohinta = ? ,hinta_ilman_ALV = ?, ALV_kanta = ?, varastosaldo = ?, 
				minimimyyntiera = ?, hyllypaikka = ?, paivitettava = 1
		  	WHERE id = ?";

	$result = $db->query( $sql,
		[ $val[3],$val[3],$val[3],$val[0],$val[1],$val[2],$val[3],$val[4],$val[5],$val[6],$val[7] ] );
	if ( $result ) {
		return true;
	}
	return false;
}

/**
 * Hakee kaikki ALV-kannat, tekee niistä dropdown-valikon, ja palauttaa HTML-koodin.
 * @param DByhteys $db
 * @return String <p> HTML-koodia. Dropdown-valikko.
 */
function hae_kaikki_ALV_kannat_ja_lisaa_alasvetovalikko ( DByhteys $db ) : string {
    $sql = "SELECT kanta, prosentti FROM ALV_kanta ORDER BY kanta ASC";
    $rows = $db->query( $sql, [], FETCH_ALL );

    $return_string = '<select name="alv_lista" id="alv_lista">';
    foreach ( $rows as $alv ) {
        $alv->prosentti = str_replace( '.', ',', $alv->prosentti );
        $return_string .= "<option value=\"{$alv->kanta}\">{$alv->kanta}; {$alv->prosentti}</option>";
    }
    $return_string .= "</select>";

    return $return_string;
}

/**
 * //TODO: Väliaikainen ratkaisuc
 * Hakee kaikki yritykset, tekee niistä dropdown-valikon, ja palauttaa HTML-koodin.
 * @param DByhteys $db
 * @return String <p> HTML-koodia. Dropdown-valikko.
 */
function hae_kaikki_yritykset_ja_lisaa_alasvetovalikko ( DByhteys $db ) : string {
	$sql = "SELECT id, nimi FROM yritys WHERE aktiivinen = 1 ORDER BY nimi ASC";
	$rows = $db->query( $sql, [], FETCH_ALL );

	$return_string = '<select name="yritys_id">
		<option value="">- Tyhjä -</option>';
	foreach ( $rows as $yritys ) {
		$return_string .= "<option value='{$yritys->id}'>{$yritys->id}; {$yritys->nimi}</option>";
	}
	$return_string .= "</select>";

	return $return_string;
}

/**
 * //TODO: Väliaikainen ratkaisu.
 * //TODO MIKSI TÄMÄ ON VÄLIAIKAINEN RATKAISU?! MITÄ MINÄ OIKEIN AJATTELIN? --jj170705
 * //TODO: KOSKA https://stackoverflow.com/questions/23740548/how-to-pass-variables-and-data-from-php-to-javascript --SL170720
 * Hakee kaikki tuoteryhmät, tekee niistä dropdown-valikon, ja palauttaa HTML-koodin.
 * @param DByhteys $db
 * @return String <p> HTML-koodia. Dropdown-valikko.
 */
function hae_kaikki_tuoteryhmat_ja_luo_alasvetovalikko ( DByhteys $db ) : string {
	$sql = "SELECT id, nimi, oma_taso FROM tuoteryhma ORDER BY oma_taso ASC";
	$rows = $db->query( $sql, [], FETCH_ALL );

	$return_string = '<select name="tuoteryhma_id" required>
		<option selected disabled>- Tyhjä -</option>';
	foreach ( $rows as $tr ) {
		//TODO: Pahoittelut, jos hajotin jotain. :(
		//TODO: Muutin hieman koodia, että pääsen php errorista eroon.
		$taso = $tr->oma_taso; // Monesko taso, 11 merkkiä / 3 = 3[,6666]
		$taso = str_repeat( "-", (int)$taso-1 ); // Montako viivaa == monesko taso
		if ( $taso == '' ) {
			$return_string .= "<option disabled>--------------------------</option>";
		}
		$return_string .= "<option value='{$tr->id}'>{$taso} {$tr->nimi} ({$tr->id})</option>";
	}
	$return_string .= "</select>";

	return $return_string;
}

/**
 * @param DByhteys $db
 * @param array    $values
 * @param bool     $yrityskohtainen
 * @return bool
 */
function lisaa_alennus( DByhteys $db, array $values, bool $yrityskohtainen ) : bool {
	// Yrityskohtaisille alennuksille on oma taulu.
	if ( $yrityskohtainen ) {
		$sql = "INSERT INTO tuoteyritys_erikoishinta
					(tuote_id, maaraalennus_kpl, alennus_prosentti, alkuPvm, loppuPvm, yritys_id)
				VALUES (?, ?, ?, ?, ?, ?)
				ON DUPLICATE KEY UPDATE
					maaraalennus_kpl=VALUES(maaraalennus_kpl), alennus_prosentti=VALUES(alennus_prosentti),
					alkuPvm=VALUES(alkuPvm), loppuPvm=VALUES(loppuPvm)";
	}
	// Vain tuotekohtainen alennus
	else {
		array_pop($values); // Poistetaan yritys_id listasta
		$sql = "INSERT INTO tuote_erikoishinta (tuote_id, maaraalennus_kpl, alennus_prosentti, alkuPvm, loppuPvm)
				VALUES (?, ?, ?, ?, ?)
				ON DUPLICATE KEY UPDATE
					maaraalennus_kpl=VALUES(maaraalennus_kpl), alennus_prosentti=VALUES(alennus_prosentti),
					alkuPvm=VALUES(alkuPvm), loppuPvm=VALUES(loppuPvm)";
	}

	$result = $db->query( $sql, $values );
	if ( $result ) {
		return true;
	}
	return false;
}

/**
 * Jakaa tecdocista löytyvät tuotteet kahteen ryhmään: niihin, jotka löytyvät
 * valikoimasta ja niihin, jotka eivät löydy.
 * Lopuksi lisätään TecDoc-tiedot valikoiman tuotteisiin.
 *
 * @param DByhteys $db <p> Tietokantayhteys
 * @param array $products <p> Tuote-array, josta etsitään aktivoidut tuotteet.
 * @return array <p> Kaksi arrayta:
 * 		[0]: tuotteet, jotka löytyvät catalogista;
 * 		[1]: tuotteet, jotka eivät löydy catalogista
 */
function filter_catalog_products ( DByhteys $db, array $products ) : array {

	/**
	 * Haetaan tuote tietokannasta artikkelinumeron ja brandinumeron perusteella.
	 * @param DByhteys $db
	 * @param stdClass $product
	 * @return array|bool|stdClass
	 */
	function get_product_from_database( DByhteys $db, stdClass $product ) : array {
		$product->articleNo = str_replace(" ", "", $product->articleNo);
		$sql = "SELECT 	*, (hinta_ilman_alv * (1+ALV_kanta.prosentti)) AS hinta,
					toimittaja_tehdassaldo.tehdassaldo
				FROM 	tuote 
				JOIN 	ALV_kanta ON tuote.ALV_kanta = ALV_kanta.kanta
				LEFT JOIN toimittaja_tehdassaldo ON tuote.hankintapaikka_id = toimittaja_tehdassaldo.hankintapaikka_id
						AND tuote.articleNo = toimittaja_tehdassaldo.tuote_articleNo
				WHERE 	tuote.articleNo = ? AND tuote.brandNo = ? AND tuote.aktiivinen = 1 ";

		return $db->query($sql, [$product->articleNo, $product->brandNo], FETCH_ALL );
	}

	$catalog_products = $all_products = [];
	$ids = $article_ids = [];	//duplikaattien tarkistusta varten

	//Lajitellaan tuotteet sen mukaan, löytyikö tietokannasta vai ei.
	foreach ( $products as $product ) {
		$db_tuotteet = get_product_from_database($db, $product);
		if (!in_array($product->articleId, $article_ids)) {
			$article_ids[] = $product->articleId;
			$product->articleName = isset($product->articleName) ? $product->articleName : $product->genericArticleName;
			$product->id = $db_tuotteet ? $db_tuotteet[0]->id : null;
			$all_products[] = $product;
		}
		if ( $db_tuotteet ) {
			//Kaikki löytyneet tuotteet (eri hankintapaikat)
			foreach ( $db_tuotteet as $tuote ) {
				if ( !in_array($tuote->id, $ids) ) {
					$ids[] = $tuote->id;
					$tuote->articleId = $product->articleId;
					$tuote->articleName = isset($product->articleName) ? $product->articleName : $product->genericArticleName;
					$tuote->brandName = $product->brandName;
                    $catalog_products[] = $tuote;
				}
			}
		}
	}
	merge_products_with_optional_data( $catalog_products );

	return [$catalog_products, $all_products];
}

/**
 * Etsii kannasta itse perustetut tuotteet.
 * @param DByhteys $db
 * @param string   $search_number
 * @param bool     $tarkka_haku
 * @return array
 */
function search_own_products_from_database( DByhteys $db, string $search_number, bool $tarkka_haku=true ) : array {
	if ( $tarkka_haku ) {
		$search_pattern = $search_number;
	} else {
		$simple_search_number = preg_replace("/[^ \w]+/", "", $search_number); // Poistetaan kaikki erikoismerkit
		$search_pattern = implode('%', str_split($simple_search_number, 1)) . "%";
		// Hakunumero muotoa q%t%b%2%4%9%, joten lyhyillä hakunumeroilla se voi löytää liikaa tuloksia
	}
	$sql = "SELECT 	    tuote.*, (hinta_ilman_alv * (1+ALV_kanta.prosentti)) AS hinta, 
                        LEAST(
                        	COALESCE(MIN(ostotilauskirja_arkisto.oletettu_saapumispaiva), MIN(ostotilauskirja.oletettu_saapumispaiva)), 
                        	COALESCE(MIN(ostotilauskirja.oletettu_saapumispaiva), MIN(ostotilauskirja_arkisto.oletettu_saapumispaiva)) 
                        ) AS saapumispaiva,
                        MIN(ostotilauskirja_arkisto.oletettu_saapumispaiva) AS tilauskirja_arkisto_saapumispaiva,
                        MIN(ostotilauskirja.oletettu_saapumispaiva) AS tilauskirja_saapumispaiva,
                        toimittaja_tehdassaldo.tehdassaldo
			FROM 	    tuote
			JOIN 	    ALV_kanta ON tuote.ALV_kanta = ALV_kanta.kanta
			LEFT JOIN   ostotilauskirja_tuote_arkisto ON tuote.id = ostotilauskirja_tuote_arkisto.tuote_id
			LEFT JOIN   ostotilauskirja_arkisto
						ON ostotilauskirja_tuote_arkisto.ostotilauskirja_id = ostotilauskirja_arkisto.id 
			            AND ostotilauskirja_arkisto.hyvaksytty = 0
			LEFT JOIN   ostotilauskirja_tuote ON tuote.id = ostotilauskirja_tuote.tuote_id
			LEFT JOIN   ostotilauskirja ON ostotilauskirja_tuote.ostotilauskirja_id = ostotilauskirja.id
			LEFT JOIN	toimittaja_tehdassaldo ON tuote.hankintapaikka_id = toimittaja_tehdassaldo.hankintapaikka_id
						AND tuote.articleNo = toimittaja_tehdassaldo.tuote_articleNo
			WHERE 	    tuote.articleNo LIKE ? AND tuote.aktiivinen = 1 AND tuote.tecdocissa = 0
			GROUP BY    tuote.id
			LIMIT 10";
	$own_products = $db->query($sql, [$search_pattern], FETCH_ALL);

	if ( !$own_products ) {
		$own_products = [];
	}

	return merge_tecdoc_product_variables_to_catalog_products($own_products);
}

/**
 * Etsitään kannasta kaikki omat tuotteet, jotka ovat verrattavissa hakutuloksiin.
 * @param DByhteys $db
 * @param array    $products
 * @return array
 */
function search_comparable_products_from_database(  DByhteys $db, array $products ) : array {

	if ( !$products ) {
		return [];
	}

	$values = [];
	foreach ( $products as $product ) {
		$values[] = str_replace(" ", "", $product->articleNo);
		$values[] = $product->brandNo;
	}
	$questionmarks = implode( ',', array_fill( 0, count($products), 'ROW(?,?)' ) );
	$sql = "SELECT 	    tuote.*, (hinta_ilman_alv * (1+ALV_kanta.prosentti)) AS hinta, 
                        LEAST(
                        	COALESCE(MIN(ostotilauskirja_arkisto.oletettu_saapumispaiva), MIN(ostotilauskirja.oletettu_saapumispaiva)), 
                        	COALESCE(MIN(ostotilauskirja.oletettu_saapumispaiva), MIN(ostotilauskirja_arkisto.oletettu_saapumispaiva)) 
                        ) AS saapumispaiva,
                        MIN(ostotilauskirja_arkisto.oletettu_saapumispaiva) AS tilauskirja_arkisto_saapumispaiva,
                        MIN(ostotilauskirja.oletettu_saapumispaiva) AS tilauskirja_saapumispaiva,
                        toimittaja_tehdassaldo.tehdassaldo
			FROM 	    tuote_linkitys
			JOIN		tuote ON tuote_linkitys.tuote_id = tuote.id
			JOIN 	    ALV_kanta ON tuote.ALV_kanta = ALV_kanta.kanta
			LEFT JOIN   ostotilauskirja_tuote_arkisto ON tuote.id = ostotilauskirja_tuote_arkisto.tuote_id
			LEFT JOIN   ostotilauskirja_arkisto
						ON ostotilauskirja_tuote_arkisto.ostotilauskirja_id = ostotilauskirja_arkisto.id 
			            AND ostotilauskirja_arkisto.hyvaksytty = 0
			LEFT JOIN   ostotilauskirja_tuote ON tuote.id = ostotilauskirja_tuote.tuote_id
			LEFT JOIN   ostotilauskirja ON ostotilauskirja_tuote.ostotilauskirja_id = ostotilauskirja.id
			LEFT JOIN	toimittaja_tehdassaldo ON tuote.hankintapaikka_id = toimittaja_tehdassaldo.hankintapaikka_id
						AND tuote.articleNo = toimittaja_tehdassaldo.tuote_articleNo
			WHERE ROW(tuote_linkitys.articleNo, tuote_linkitys.brandNo) IN ( {$questionmarks} )
			GROUP BY tuote.id";
	$own_products = $db->query($sql, $values, FETCH_ALL);

	if ( !$own_products ) {
		$own_products = [];
	}

	return merge_tecdoc_product_variables_to_catalog_products($own_products);
}

/**
 * Alustetaan omasta tietokannasta löytyneille tuotteille saman
 * nimiset muuttujat kuin TecDoc-tuotteille.
 * @param array     $products
 * @return array
 */
function merge_tecdoc_product_variables_to_catalog_products( array $products ) : array {
	foreach ($products as $tuote) {
		$tuote->articleId = null;
		$tuote->articleName = (string)$tuote->nimi;
		$tuote->brandName = (string)$tuote->valmistaja;
		$tuote->infot = (string)$tuote->infot;
		$infot = explode('|', $tuote->infot);
		foreach ($infot as $index=>$info) {
			$tuote->infos[$index] = new stdClass();
			$tuote->infos[$index]->attrName = $info;
		}
		$tuote->thumburl = !empty($tuote->kuva_url) ? $tuote->kuva_url : 'img/ei-kuvaa.png';
	}

	return $products;
}

/**
 * Jos hakunumerona on oma tuote, haetaan kannasta vertailutuote ja tehdään haku sillä.
 * @param DByhteys $db
 * @param string   $search_number
 * @return stdClass
 */
function get_comparable_number_for_own_product( DByhteys $db, string $search_number ) {
	$sql = "SELECT tuote_linkitys.articleNo, tuote_linkitys.genericArticleId
			FROM tuote_linkitys
			LEFT JOIN tuote ON tuote.id = tuote_linkitys.tuote_id
			WHERE tuote.articleNo = ?
			LIMIT 1";
	return $db->query($sql, [$search_number], false, PDO::FETCH_OBJ);
}

/**
 * Järjestetään tuotteet hinnan mukaan.
 * @param $catalog_products
 */
function sortProductsByPrice( &$catalog_products ){
	usort($catalog_products, function ($a, $b){return ($a->hinta > $b->hinta);});
}

/**
 * Tarkastaa onko numerossa hankintapaikkaan viittaavaa etuliitettä.
 * @param string $number
 * @return bool
 */
function tarkasta_etuliite( string $number ) : bool {
	return strlen($number) > 4
		&& $number[3] === "-"
		&& is_numeric(substr($number, 0, 3));
}

/**
 * Jakaa hakunumeron etuliitteeseen ja hakunumeroon
 * @param int $number reference
 * @param int $etuliite reference
 */
function halkaise_hakunumero( &$number, &$etuliite ) {
	$etuliite = substr($number, 0, 3);
	$number = substr($number, 4);
}

if ( !$user->isAdmin() ) { // Sivu tarkoitettu vain ylläpitäjille
	header("Location:etusivu.php"); exit();
}

if ( !empty($_POST['lisaa']) ) {
	$articleNo = str_replace(" ", "", strval(mb_strtoupper($_POST['articleNo'])));
    $tuotekoodi = str_pad($_POST['hankintapaikat'], 3, "0", STR_PAD_LEFT) .
                    "-" . $articleNo;
    $array = [
        $articleNo,
        $_POST['brandNo'],
        $_POST['hankintapaikat'],
        $tuotekoodi,
        str_replace(" ", "", $_POST['tilauskoodi']),
		str_replace(',', '.', $_POST['ostohinta']), //TODO: Turha, HTML tekee automaattisesti tämän.
		str_replace(',', '.', $_POST['hinta']),
        $_POST['alv_lista'],
        $_POST['varastosaldo'],
        $_POST['minimimyyntiera'],
		$_POST['hyllypaikka'],
		$_POST['nimi'],
		$_POST['valmistaja']
    ];
    if ( add_product_to_catalog( $db, $array ) ) {
		$_SESSION["feedback"] = '<p class="success">Tuote lisätty!</p>';
    } else { $_SESSION["feedback"] = '<p class="error">Tuote on jo valikoimassa!</p>'; }

}
elseif ( !empty($_POST['poista']) ) {
	$id = (int)$_POST['id'];
    if ( remove_product_from_catalog( $db, $id ) ) {
		$_SESSION["feedback"] = '<p class="success">Tuote poistettu!</p>';
    } else { $_SESSION["feedback"] = '<p class="error">Tuotteen poisto epäonnistui!</p>'; }

}
elseif ( !empty($_POST['muokkaa']) ) {
    $array = [
		str_replace(" ", "", $_POST['tilauskoodi']),
		str_replace(',', '.', $_POST['ostohinta']), //TODO: HTML tekee tämän automaattisesti.
		str_replace(',', '.', $_POST['hinta']),
        $_POST['alv_lista'],
        $_POST['varastosaldo'],
        $_POST['minimimyyntiera'],
		$_POST['hyllypaikka'],
        $_POST['id']
    ];
    if ( modify_product_in_catalog( $db, $array ) ) {
		$_SESSION["feedback"] = '<p class="success">Tuotteen tietoja muokattu!</p>';
	} else {
    	$_SESSION["feedback"] = '<p class="error">ERROR: Tuotetta ei voitu muokata!</p>';
    }
}
elseif ( !empty($_POST['tuotealennus']) ) {
	$_POST['alennus_pros'] = $_POST['alennus_pros'] / 100;
	if ( lisaa_alennus( $db, array_values($_POST), !empty($_POST['yritys_id']) ) ) {
		$_SESSION["feedback"] = '<p class="success">Tuotteelle lisätty alennus!</p>';
	} else {
		$_SESSION["feedback"] = '<p class="error">ERROR: Alennuksen lisäys ei onnistunut.</p>';
	}
}
elseif ( !empty($_POST['tuote_tuoteryhma']) ) {
	$result = $db->query("INSERT INTO tuoteryhma_tuote (tuote_id, tuoteryhma_id) VALUES (?,?)",
	                     [$_POST['tuote_tuoteryhma'],$_POST['tuoteryhma_id']] );
	if ( $result ) {
		$_SESSION["feedback"] = '<p class="success">Tuote lisätty tuoteryhmään! Linkitys onnistui</p>';
	} else {
		$_SESSION["feedback"] = '<p class="error">ERROR: Tuotteen ja tuoteryhmän linkitys epäonnistui.</p>';
	}
}
elseif ( !empty($_POST['tuote_linkitys']) ) {
	$result = false;
	// Etsitään tuote vielä tecdocista
	$tecdoc_tuote = getArticleDirectSearchAllNumbersWithState($_POST['tecdoctuote']['article'],
		0, true, (int)$_POST['tecdoctuote']['brand']);
	if ( count($tecdoc_tuote) > 0 ) {
		$tecdoc_tuote[0]->articleNo = str_replace(" ", "", $tecdoc_tuote[0]->articleNo);
		// Lisätään vertailu
		$sql = "INSERT INTO tuote_linkitys (tuote_id, brandNo, articleNo, genericArticleId) VALUES (?,?,?,?)
				ON DUPLICATE KEY 
				UPDATE brandNo = VALUES(brandNo), articleNo = VALUES(articleNo),
					genericArticleId = VALUES(genericArticleId)";
		$result = $db->query($sql, [$_POST['id'], $tecdoc_tuote[0]->brandNo,
			$tecdoc_tuote[0]->articleNo, $tecdoc_tuote[0]->genericArticleId]);
	}
	if ( $result ) {
		$_SESSION["feedback"] = '<p class="success">Tuote linkitetty onnistuneesti.</p>';
	} else {
		$_SESSION["feedback"] = '<p class="error">Linkitys epäonnistui.</p>';
	}
}

/** Tarkistetaan feedback, ja estetään formin uudelleenlähetys */
if ( !empty($_POST) || !empty($_FILES) ) { //Estetään formin uudelleenlähetyksen
	header("Location: " . $_SERVER['REQUEST_URI']); exit();
} else {
	$feedback = isset($_SESSION['feedback']) ? $_SESSION['feedback'] : "";
	unset($_SESSION["feedback"]);
}

$haku = FALSE;
$products = $catalog_products = $all_products = [];
$yrityksien_nimet_alennuksen_asettamista_varten = hae_kaikki_yritykset_ja_lisaa_alasvetovalikko( $db );
$tuoteryhmien_nimet_tuotteiden_linkitysta_varten = hae_kaikki_tuoteryhmat_ja_luo_alasvetovalikko( $db );

if ( !empty($_GET['haku']) ) { // Tuotekoodillahaku
	$haku = TRUE; // Hakutulosten tulostamista varten.
	$number = addslashes(str_replace(" ", "", $_GET['haku']));  // Hakunumero
	$etuliite = null;   // Mahdollinen etuliite
	$products = $own_products = $own_comparable_products = [];
	//TODO: Jos tuotenumerossa on neljäs merkki: -, tulee se jättää pois tai haku epäonnistuu
	//TODO: sillä ei voida tietää kuuluuko etuliite tuotenumeroon vai kertooko se hankintapaikan (Esim 200-149)

	/**
	 * Haun järjestys:
	 * 1: Haetaan tecdocista annetulla hakunumerolla.
	 * 2: Jos hakunumero vastaa omaa tuotetta, etsitään kannasta tecdoc-hakunumero ja
	 *      etsitään sillä. (vain jos hakutyyppi "all" tai comparable)
	 * 3: Etsitään kannasta omat tuotteet joiden tallennetut vertailutuotteet vastaavat
	 *      hakutuloksia. (vain jos hakutyyppi "all" tai comparable)
	 * 4: Karsitaan ne tuotteet, joita ei ole perustettu meidän järjestelmään.
	 * 5: Etsitään kannasta ne omat tuotteet, jotka vastaavat suoraan hakunumeroa.
	 *      (vain jos hakutyyppi ei ole "oe")
	 */
	$numerotyyppi = isset($_GET['numerotyyppi']) ? $_GET['numerotyyppi'] : null;	//numerotyyppi
	$exact = (isset($_GET['exact']) && $_GET['exact'] === 'false') ? false : true;	//tarkka haku
	switch ($numerotyyppi) {
		case 'all':
			if(tarkasta_etuliite($number)) halkaise_hakunumero($number, $etuliite);
			$products = getArticleDirectSearchAllNumbersWithState($number, 10, $exact);
			$alternative_search_number = get_comparable_number_for_own_product($db, $number);
			if ( !empty($alternative_search_number) ) {
				$products2 = getArticleDirectSearchAllNumbersWithState($alternative_search_number->articleNo,
					10, $exact, null, $alternative_search_number->genericArticleId);
				$products = array_merge($products, $products2);
			}
			$own_comparable_products = search_comparable_products_from_database($db, $products);
			$own_products = search_own_products_from_database( $db, $number, $exact );
			break;
		case 'articleNo':
			if(tarkasta_etuliite($number)) halkaise_hakunumero($number, $etuliite);
			$products = getArticleDirectSearchAllNumbersWithState($number, 0, $exact);
			$own_products = search_own_products_from_database( $db, $number, $exact );
			break;
		case 'comparable':
			if(tarkasta_etuliite($number)) halkaise_hakunumero($number, $etuliite);
			$products = getArticleDirectSearchAllNumbersWithState($number, 0, $exact);	//tuote
			$products2 = getArticleDirectSearchAllNumbersWithState($number, 3, $exact);	//vertailut
			$products = array_merge($products, $products2);
			$alternative_search_number = get_comparable_number_for_own_product($db, $number);
			if ( $alternative_search_number ) {
				$products2 = getArticleDirectSearchAllNumbersWithState($alternative_search_number->articleNo,
					10, $exact, null, $alternative_search_number->genericArticleId);
				$products = array_merge($products, $products2);
			}
			$own_comparable_products = search_comparable_products_from_database($db, $products);
			$own_products = search_own_products_from_database( $db, $number, $exact );
			break;
		case 'oe':
			$products = getArticleDirectSearchAllNumbersWithState($number, 1, $exact);
			$own_comparable_products = search_comparable_products_from_database($db, $products);
			break;
		default:	//jos numerotyyppiä ei ole määritelty (= joku on ruvennut leikkimään GET parametreilla)
			$products = getArticleDirectSearchAllNumbersWithState($number, 10, $exact);
			break;
	}

	// Filtteröidään catalogin tuotteet kahteen listaan: valikoimasta löytyvät ja tuotteet, jotka ei ole valikoimassa.
	$filtered_product_arrays = filter_catalog_products( $db, $products );
	// Yhdistetään kaikki tuotteet
	$catalog_products = array_unique(array_merge($filtered_product_arrays[0], $own_products, $own_comparable_products), SORT_REGULAR);
	$all_products = array_unique(array_merge($filtered_product_arrays[1], $own_products, $own_comparable_products), SORT_REGULAR);

	// Järjestetään hinnan mukaan
	sortProductsByPrice($catalog_products);
}

else if ( !empty($_GET["manuf"]) ) { // Ajoneuvomallillahaku
	$haku = TRUE; // Hakutulosten tulostamista varten. Ei tarvitse joka kerta tarkistaa isset()
	$car = (int)$_GET["car"];
	$part_type = (int)$_GET["osat_alalaji"];

	$products = getArticleIdsWithState($car, $part_type);
	$own_comparable_products = search_comparable_products_from_database($db, $products);
	$filtered_product_arrays = filter_catalog_products( $db, $products );

	$catalog_products = array_unique(array_merge($filtered_product_arrays[0], $own_comparable_products), SORT_REGULAR);
	$all_products = array_unique(array_merge($filtered_product_arrays[1], $own_comparable_products), SORT_REGULAR);
	sortProductsByPrice($catalog_products);
}

else if ( !empty($_GET["hyllypaikka"]) ) { // Hyllypaikallahaku
	$haku = TRUE;
	$hyllypaikka = str_replace(" ", "", $_GET["hyllypaikka"]);
	$sql = "SELECT  *, (hinta_ilman_alv * (1+ALV_kanta.prosentti)) AS hinta,
					toimittaja_tehdassaldo.tehdassaldo
            FROM    tuote
            JOIN    ALV_kanta ON tuote.ALV_kanta = ALV_kanta.kanta
            LEFT JOIN toimittaja_tehdassaldo ON tuote.hankintapaikka_id = toimittaja_tehdassaldo.hankintapaikka_id
						AND tuote.articleNo = toimittaja_tehdassaldo.tuote_articleNo
            WHERE   hyllypaikka = ? AND aktiivinen = 1";
    $products = $db->query($sql, [$hyllypaikka], FETCH_ALL);
    $tecdoc_products = [];
    $catalog_products = [];
    foreach ( $products as $product ) {
    	if ( $product->tecdocissa ) {
			$tecdoc_products[] = $product;
	    } else {
			$catalog_products[] = $product;
	    }
    }
    // Tecdoc tuotteille etsitään data tecdocista
    get_basic_product_info( $tecdoc_products );
    merge_products_with_optional_data( $tecdoc_products );
    // Itse perustetuille tuotteille lisätään tecdoc-attribuutit
    merge_tecdoc_product_variables_to_catalog_products( $catalog_products );
    $catalog_products = array_merge($catalog_products, $tecdoc_products);
	// Järjestetään hinnan mukaan
	sortProductsByPrice($catalog_products);

}
?>
<!DOCTYPE html>
<html lang="fi">
<head>
	<meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">

    <link rel="stylesheet" href="./css/bootstrap.css">
	<link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
	<link rel="stylesheet" href="./css/jsmodal-light.css">
    <link rel="stylesheet" href="./css/image_modal.css">
	<link rel="stylesheet" href="./css/styles.css">

	<script src="https://code.jquery.com/jquery-3.1.1.min.js"></script>
	<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>
	<!--<script src="http://webservicepilot.tecdoc.net/pegasus-3-0/services/TecdocToCatDLB.jsonEndpoint?js"></script>-->
	<script src="./js/TecdocToCatDLB.jsonEndpoint"></script>
	<script src="./js/jsmodal-1.0d.min.js"></script>
	<title>Tuotteet</title>
</head>
<body>
<?php
require 'header.php';
require 'tuotemodal.php';
?>
<main class="main_body_container">
	<div class="otsikko_container">
		<section class="takaisin">
		</section>
		<section class="otsikko">
			<h1>Ylläpitäjän tuotehaku</h1>
		</section>
		<section class="napit">
		</section>
	</div>

	<section class="white-bg" style="border-radius: 5px; border: 1px solid;">
		<div class="tuotekoodihaku">
			<form action="" method="get" class="haku">
				<div class="inline-block">
					<label for="search">Hakunumero:</label>
					<br>
					<input id="search" type="text" name="haku" placeholder="Tuotenumero">
				</div>
				<div class="inline-block">
					<label for="numerotyyppi">Numerotyyppi:</label>
					<br>
					<select id="numerotyyppi" name="numerotyyppi">
						<option value="all">Kaikki numerot</option>
						<option value="articleNo">Tuotenumero</option>
						<option value="comparable">Tuotenumero + vertailut</option>
						<option value="oe">OE-numerot</option>
					</select>
				</div>
				<div class="inline-block">
					<label for="hakutyyppi">Hakutyyppi:</label>
					<br>
					<select id="hakutyyppi" name="exact">
						<option value="true">Tarkka</option>
						<option value="false">Samankaltainen</option>
					</select>
				</div>
				<br>
				<input class="nappi" type="submit" value="Hae">
			</form>
            <br>
            <form action="" method="get" class="haku">
                <label for="hyllypaikka">Hae hyllypaikalla:</label><br>
                <input type="text" id="hyllypaikka" name="hyllypaikka" placeholder="Hyllypaikka">
                <input class="nappi" type="submit" value="Hae">
            </form>
		</div>
		<?php require 'ajoneuvomallillahaku.php'; ?>
	</section>

    <?= $feedback ?>

	<section class="hakutulokset">
		<?php if ( $haku ) : ?>
            <h3>Yhteensä löydettyjä tuotteita:
				<?=count($catalog_products) + count($all_products) ?></h3>
			<?php if ( $catalog_products) : // Tulokset (saatavilla) ?>
				<table style="min-width: 90%;"><!-- Katalogissa saatavilla, tilattavissa olevat tuotteet (varastosaldo > 0) -->
					<thead>
					<tr><th colspan="10" class="center" style="background-color:#1d7ae2;">Valikoimassa: (<?=count($catalog_products)?>)</th></tr>
					<tr> <th>Kuva</th> <th>Tuotenumero</th> <th>Tuote</th> <th>Info</th>
						<th class="number">Saldo</th> <th class="number">Tehdas</th> <th class="number">Hinta (sis. ALV)</th>
                        <th class="number">Ostohinta ALV0%</th><th class="number">Kate %</th>
						<th>Hyllypaikka</th>
						<th></th>
					</tr>
					</thead>
					<tbody>
					<?php foreach ($catalog_products as $product) : ?>
						<tr data-tecdoc_id="<?=$product->articleId?>" data-tuote_id="<?=$product->id?>">
							<td class="clickable thumb">
								<img src="<?=$product->thumburl?>" alt="<?=$product->articleName?>"></td>
							<td class="clickable"><?=$product->tuotekoodi?></td>
							<td class="clickable"><?=$product->brandName?><br><?=$product->articleName?></td>
							<td class="clickable">
								<?php foreach ( $product->infos as $info ) :
									echo (!empty($info->attrName) ? $info->attrName : "") . " " .
										(!empty($info->attrValue) ? $info->attrValue : "") .
										(!empty($info->attrUnit) ? $info->attrUnit : "") . "<br>";
								endforeach; ?>
							</td>
							<td class="number"><?=format_number($product->varastosaldo, 0)?></td>
							<td class="number">
								<?php if ( !is_null($product->tehdassaldo) ) : ?>
									<?php if ( $product->tehdassaldo > 0 ) : ?>
										<i class="material-icons" style="color:green;" title="
											<?= ($product->hankintapaikka_id == 140) ? "Saatavilla toimittajalta."
												: "Varastossa saldoa: ".format_number($product->tehdassaldo, 0)." kpl" ?>"
											> check_circle
										</i>
									<?php else : ?>
										<i class="material-icons" style="color:red;" title="Tehdasaldo nolla (0).">
											highlight_off
										</i>
									<?php endif; ?>
								<?php endif; ?>
							</td>
							<td class="number"><?=format_number($product->hinta)?></td>
                            <td class="number"><?=format_number($product->sisaanostohinta)?></td>
                            <td class="number"><?=round(100*(($product->hinta_ilman_ALV - $product->sisaanostohinta)/$product->hinta_ilman_ALV), 0)?>%</td>
							<td><?=$product->hyllypaikka?></td>
							<td class="toiminnot">
								<!-- //TODO: Disable nappi, ja väritä tausta lisäyksen jälkeen -->
								<button class="nappi red" onclick="showRemoveDialog(<?=$product->id?>)">
                                    Poista</button><br>
                                <button class="nappi" onclick="showModifyDialog(<?=$product->id?>, '<?=$product->tuotekoodi?>', '<?=$product->tilauskoodi?>',
		                            '<?=number_format(round($product->sisaanostohinta,2),2)?>',
		                            '<?=number_format(round($product->hinta_ilman_ALV,2),2)?>',
                                    '<?=$product->ALV_kanta?>', '<?=$product->varastosaldo?>',
                                    '<?=$product->minimimyyntiera?>', '<?=$product->hyllypaikka?>',
                                    <?=$product->tecdocissa?>)">
                                    Muokkaa</button><br>
                                <button class="nappi" id="lisaa_otk_nappi_<?=$product->id?>" onclick="showLisaaOstotilauskirjalleDialog(<?=$product->id?>,
                                    <?=$product->hankintapaikka_id?>, '<?= $product->articleName?>', '<?= $product->brandName?>')">
                                    OTK</button>
                            </td>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; //if $catalog_products
			if ( $all_products) : //Tulokset (ei katalogissa)?>
				<table><!-- Katalogissa ei olevat, ei tilattavissa olevat tuotteet. TecDocista. -->
					<thead>
					<tr><th colspan="3" class="center" style="background-color:#1d7ae2;">Kaikki tuotteet: (<?=count($all_products)?>)</th></tr>
					<tr> <th>Tuotenumero</th> <th>Tuote</th> <th></th> </tr>
					</thead>
					<tbody>
					<?php foreach ($all_products as $product) : ?>
						<tr data-tecdoc_id="<?=$product->articleId?>" data-tuote_id="<?=$product->id?>">
							<td class="clickable"><?=$product->articleNo?></td>
							<td class="clickable"><?=$product->brandName?><br><?=$product->articleName?></td>
							<td><button class="nappi" onclick="showAddDialog(
										'<?=$product->articleNo?>', <?=$product->brandNo?>,
									   	'<?=$product->articleName?>', '<?=$product->brandName?>')">Lisää</button></td>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; //if $all_products

			if ( !$catalog_products && !$all_products ) : ?>
				<h2>Ei tuloksia.</h2>
			<?php endif; //if ei tuloksia?>

		<?php endif; //if $haku?>
	</section>
</main>

<?php require 'footer.php'; ?>

<script type="text/javascript">

	/**
	 * Modal tuotteen lisäämiseen
	 * @param articleNo
	 * @param brandNo
	 * @param nimi
	 * @param valmistaja
	 */
    function showAddDialog( /*string*/ articleNo, /*int*/ brandNo, /*string*/ nimi, /*string*/ valmistaja ) {
		//haetaan hankintapaikan ostotilauskirjat
		$.post(
			"ajax_requests.php",
			{   valmistajan_hankintapaikat: true,
				brand_id: brandNo },
			function( data ) {
				hankintapaikat = JSON.parse(toJSON(data));
				if( hankintapaikat.length === 0 ){
					alert("Linkitä ensin tuotteen brändi johonkin hankintapaikkaan!");
					return;
				}
				//Luodaan alasvetovalikko
				let hankintapaikka_valikko = '<select name="hankintapaikat">';
				for(let i=0; i < hankintapaikat.length; i++){
					hankintapaikka_valikko += '<option name="hankintapaikka" value="'+hankintapaikat[i].id+'">'+hankintapaikat[i].id + " - " + hankintapaikat[i].nimi+'</option>';
				}
				hankintapaikka_valikko += '</select>';
				let alv_valikko = <?= json_encode(hae_kaikki_ALV_kannat_ja_lisaa_alasvetovalikko($db)) ?>;
				Modal.open({
					content: '\
                    <div class="dialogi-otsikko">Lisää tuote</div> \
                    <form action="" name="lisayslomake" method="post"> \
                        <label for="ostohinta">Ostohinta:</label>\
                            <input type="number" step="0.01" class="eur" name="ostohinta" placeholder="0,00" required> &euro;<br> \
                        <label for="hinta">Myyntihinta (ilman ALV):</label>\
                            <input type="number" step="0.01" class="eur" name="hinta" placeholder="0,00" required> &euro;<br> \
                        <label for="alv">ALV Verokanta:</label> \
                            ' + alv_valikko + '<br> \
                        <label for="hp">Hankintapaikka:</label> \
                            ' + hankintapaikka_valikko + '<br> \
                        <label for="tilauskoodi">Tilauskoodi:</label>\
                            <input type="text" name="tilauskoodi" value="'+articleNo+'" required><br> \
                        <label for="varastosaldo">Varastosaldo:</label>\
                            <input type="number" class="kpl" name="varastosaldo" placeholder="0"> kpl<br> \
                        <label for="minimimyyntiera">Minimimyyntierä:</label>\
                            <input type="number" class="kpl" name="minimimyyntiera" placeholder="1" value="1" min="1" required> kpl<br> \
                        <label for="minimimyyntiera">Hyllypaikka:</label>\
                            <input class="kpl" name="hyllypaikka"><br> \
                        <input class="nappi" type="submit" name="lisaa" value="Lisää">\
                        <button class="nappi grey" style="margin-left: 10pt;" onclick="Modal.close()">Peruuta</button> \
                        <input type="hidden" name="nimi" value="' + nimi + '"> \
                        <input type="hidden" name="valmistaja" value="' + valmistaja + '"> \
                        <input type="hidden" name="articleNo" value="' + articleNo + '"> \
                        <input type="hidden" name="brandNo" value=' + brandNo + '> \
                    </form>',
					draggable: true
				});
			});
    }

    /**
     * Tuotteen poisto valikoimasta.
     * @param id
     */
    function showRemoveDialog(id) {
        Modal.open( {
            content: '\
		<div class="dialogi-otsikko">Poista tuote</div> \
		<p>Haluatko varmasti poistaa tuotteen valikoimasta?</p> \
		<form action="" name="poistolomake" method="post"> \
		    <input class="nappi red" type="submit" name="poista" value="Poista">\
		    <button class="nappi grey" type="button" style="margin-left: 10pt;" onclick="Modal.close()">Peruuta</button>\
		    <input type="hidden" name="id" value="' + id + '"> \
		</form>'
        } );
    }

	/**
	 * Modal tuotteen tietojen muokkamiseen.
	 * @param id
	 * @param tuotekoodi
	 * @param tilauskoodi
	 * @param ostohinta
	 * @param hinta
	 * @param alv
	 * @param varastosaldo
	 * @param minimimyyntiera
	 * @param hyllypaikka
	 * @param tecdocissa
	 */
    function showModifyDialog(id, tuotekoodi, tilauskoodi, ostohinta, hinta, alv, varastosaldo, minimimyyntiera,
                              hyllypaikka, tecdocissa ) {
        let alv_valikko = <?= json_encode( hae_kaikki_ALV_kannat_ja_lisaa_alasvetovalikko( $db ) ) ?>;
        //TODO: Eikö nämä pitäisi olla funktion ulkopuol-- y'know what I don't care. --jj170705
        let yrit_valikko = <?= json_encode($yrityksien_nimet_alennuksen_asettamista_varten) ?>;
		let tr_valikko = <?= json_encode($tuoteryhmien_nimet_tuotteiden_linkitysta_varten) ?>;
		let vertailunumerolinkitys_html = (tecdocissa === 0) ? '\
			<hr>\
			<form method="post" id="tuote_linkitys_form"> \
				<span style="font-weight:bold;">Linkitä TecDoc tuotteisiin:</span> \
				<input type="hidden" name="id" value="' + id + '"> \
				<br> \
				<label for="tecdoctuote_brand" class="required">Brändin id:</label> \
				<input type="number" name="tecdoctuote[brand]" \
					id="tecdoctuote_brand" step="1" min="1" autocomplete="off" required> \
				<br>\
				<label for="tecdoctuote_article" class="required">Tuotenumero:</label>\
				<input type="text" name="tecdoctuote[article]" \
				    id="tecdoctuote_article" autocomplete="off" required> \
				<button onclick="return nayta_tecdoctuotteet();">Hae</button> \
				<br> \
				<table id="vertailunumerot"></table> \
				<input type="submit" name="tuote_linkitys" id="tuote_linkitys" class="nappi" value="Linkitä" disabled> \
				<button class="nappi grey" type="button" style="margin-left:10pt;" \
					onclick="Modal.close()">Peruuta</button> \
			</form>' : "";

        Modal.open( {
            content: '\
				<div class="dialogi-otsikko">Muokkaa tuotetta '+tuotekoodi+'</div> \
				<form action="" method="post"> \
					<label for="ostohinta">Ostohinta:</label> \
						<input type="number" step="0.01" class="eur" name="ostohinta" placeholder="0,00" value="'+ostohinta+'" required> &euro;<br> \
					<label for="hinta">Hinta (ilman ALV):</label> \
						<input type="number" step="0.01" class="eur" name="hinta" placeholder="0,00" value="'+hinta+'" required> &euro;<br> \
					<label for="alv">ALV Verokanta:</label> \
				        '+alv_valikko+'<br> \
				    <label for="tilaskoodi">Tilauskoodi:</label> \
				        <input style="width: 80pt;" name="tilauskoodi" value="'+tilauskoodi+'" required><br> \
					<label for="varastosaldo">Varastosaldo:</label> \
						<input type="number" class="kpl" name="varastosaldo" placeholder="0" value="'+varastosaldo+'"> kpl<br> \
					<label for="minimimyyntiera">Minimimyyntierä:</label> \
						<input type="number" class="kpl" name="minimimyyntiera" value="'+minimimyyntiera+'" min="1" required> kpl<br> \
					<label for="hyllypaikka">Hyllypaikka:</label> \
						<input class="kpl" name="hyllypaikka" value="'+hyllypaikka+'"><br> \
					<input class="nappi" type="submit" name="muokkaa" value="Tallenna">\
					<button class="nappi grey" type="button" style="margin-left: 10pt;" onclick="Modal.close()">Peruuta</button>\
					<input type="hidden" name="id" value="' + id + '"> \
				</form> \
				<hr> \
				<form method="post"> \
					<span style="font-weight:bold;">Lisää alennus tuotteelle:</span>\
					<input type="hidden" name="tuotealennus" value="' + id + '"> \
					<br> \
					<label for="kpl_maara" class="required">Kpl-määrä:</label> \
						<input name="kpl_maara" class="kpl number" placeholder="0" value="" \
							title="Kpl-määrä alennukselle" required> kpl \
					<br> \
					<label for="alennus_pros" class="required">Alennus:</label> \
						<input name="alennus_pros" class="kpl number" placeholder="0" value=""\
							title="Alennus-prosentti" required> % \
					<br> \
					<label for="pvm_alku" class="required">Pvm-alku:</label> \
						<input type="date" name="pvm_alku" class="kpl number" style="width:95pt;"\
						    placeholder="YYYY-MM-DD" value="" title="Pvm alku" required> \
					<br> \
					<label for="pvm_loppu" class="required">Pvm-Loppu:</label> \
						<input type="date" name="pvm_loppu" class="kpl number" style="width:95pt;" \
						    placeholder="YYYY-MM-DD" value="" title="Pvm loppu" required> \
					<br> \
					<label for="yritys_id">Yritys-ID:</label> \
						 '+yrit_valikko+'\
					<br> \
					<span class="small_note"><span style="color:red;">*</span> = pakollinen kenttä</span> \
					<br> \
					<input class="nappi" type="submit" value="Lisää alennus"> \
					<button class="nappi grey" type="button" style="margin-left:10pt;" \
						onclick="Modal.close()">Peruuta</button> \
				</form>\
				<hr> \
				<form method="post"> \
					<span style="font-weight:bold;">Linkitä tuoteryhmään:</span>\
					<input type="hidden" name="tuote_tuoteryhma" value="' + id + '"> \
					<br> \
					<label for="tr_select" class="required">Tuoteryhmä:</label> \
						'+tr_valikko+' \
					<br> \
					<input type="submit" class="nappi" value="Lisää tuoteryhmään"> \
					<button class="nappi grey" type="button" style="margin-left:10pt;" \
						onclick="Modal.close()">Peruuta</button> \
				</form>\
				'+vertailunumerolinkitys_html+'\
            ',
            draggable: true,
	        width: "450px"
        } );
        $("#alv_lista").val(alv);
    }

    /**
     * Hakee tuotteen vertailunumerot muokkaus modaliin.
     */
    function nayta_tecdoctuotteet(){
        const search_number = document.getElementById("tecdoctuote_article").value;
        const brand_number = +document.getElementById("tecdoctuote_brand").value;
        let submit_painike = document.getElementById("tuote_linkitys");
        let table = document.getElementById("vertailunumerot");
        submit_painike.disabled = true; // Submit disabled
        // Tyhjennetään taulu
        while(table.rows.length > 0) {
            table.deleteRow(0);
        }
        let functionName = "getArticleDirectSearchAllNumbersWithState";
        let params = {
            "articleCountry": TECDOC_COUNTRY,
            "lang": TECDOC_LANGUAGE,
            "provider": TECDOC_MANDATOR,
            "articleNumber": search_number,
	        "brandId": brand_number,
            "numberType": 0,
            "searchExact": true
        };
        params = JSON.stringify(params).replace(/,/g,", ");

        tecdocToCatPort[functionName] (params, function(response) {
            if (response.data) {
                // Jos taulukossa on jo tuloksia poistetaan vanhat
                if (table.rows.length !== 0) {
                    while(table.rows.length > 0) {
                        table.deleteRow(0);
                    }
                }
                response = response.data.array[0];

                // thead
                let header = table.createTHead();
                let row = header.insertRow(0);
                let th = document.createElement("th");
                th.innerText = "Tecdoctuote";
                th.colSpan = 4;
                th.style.textAlign = "center";
                row.appendChild(th);

                // Löydetty tuote
                row = table.insertRow(1);
                let brand_no = row.insertCell(0);
                let brand = row.insertCell(1);
                let article_no = row.insertCell(2);
                let article_name = row.insertCell(3);
                brand_no.innerHTML = response.brandNo;
                brand.innerHTML = response.brandName;
                article_no.innerHTML = response.articleNo;
                article_name.innerHTML = response.articleName;

                // Tyylittely
                row.style.fontWeight = "bold";

                // Submit enabled
                submit_painike.disabled = false;
            } else {
                // Ei tuloksia
                let row = table.insertRow(0);
                let cell = row.insertCell(0);
                cell.innerHTML = "Ei tuloksia.";
            }
        });
        return false;
	}

	/**
	 * Lisää tuotteen ostotilauskirjalle
	 * @param id
	 * @param hankintapaikka_id
	 * @param tuote_nimi
	 * @param tuote_valmistaja
	 */
	function showLisaaOstotilauskirjalleDialog(id, hankintapaikka_id, tuote_nimi, tuote_valmistaja) {
		//haetaan hankintapaikan ostotilauskirjat
		$.post(
			"ajax_requests.php",
			{   hankintapaikan_ostotilauskirjat: true,
				hankintapaikka_id: hankintapaikka_id,
				tuote_id: id,
				tuote_nimi: tuote_nimi,
				tuote_valmistaja: tuote_valmistaja },
			function( data ) {
				let ostotilauskirjat = JSON.parse(toJSON(data));
				if ( ostotilauskirjat.length === 0 ) {
					alert("Luo ensin kyseiselle toimittajalle ostotilauskirja!" +
						"\rMUUT -> TILAUSKIRJAT -> HANKINTAPAIKKA -> UUSI OSTOTILAUSKIRJA");
					return;
				}
				//Luodaan alasvetovalikko
				let ostotilauskirja_lista = '<select name="ostotilauskirjat">';
				for (let i=0; i < ostotilauskirjat.length; i++) {
					ostotilauskirja_lista += '<option name="ostotilauskirja" value="'+ostotilauskirjat[i].id+'">'+ostotilauskirjat[i].tunniste+'</option>';
				}
				ostotilauskirja_lista += '</select>';
				//avataan Modal
				Modal.open({
					content: '\
                        <div class="dialogi-otsikko">Lisää ostotilauskirjaan</div> \
                        <form action="" name="ostotilauskirjalomake" id="ostotilauskirjalomake" method="post"> \
                            <label for="ostotilauskirja">Ostotilauskirja:</label><br> \
				            '+ostotilauskirja_lista+'<br><br> \
				            <label for="kpl">Kappaleet:</label><br> \
				            <input class="kpl" type="number" name="kpl" placeholder="1" min="1" required> kpl<br><br> \
                            <label for="selite">Selite:</label><br> \
                            <textarea rows="3" cols="25" name="selite" form="ostotilauskirjalomake" placeholder="Miksi lisäät tuotteen käsin?"></textarea><br><br> \
                            <input class="nappi" type="submit" name="lisaa_otk" value="Lisää ostotilauskirjalle">\
                            <input type="hidden" name="id" id="otk_id" value="'+id+'"> \
				        </form> \
                    ',
					draggable: true
				});
			}
		);
	}

	$(document).ready(function(){
		//Tuotteen lisääminen ostotilauskirjalle
		$(document.body)
			.on('submit', '#ostotilauskirjalomake', function(e){
				e.preventDefault();
				let tuote_id = $('#otk_id').val();
				$.post(
					"ajax_requests.php",
					{   lisaa_tilauskirjalle: true,
						ostotilauskirja_id: $('select[name=ostotilauskirjat]').val(),
						tuote_id: tuote_id,
						kpl: $('input[name=kpl]').val(),
                        selite: $('textarea[name=selite]').val() },
					function( data ) {
						Modal.close();
						if ((!!data) === true ) {
							$("#lisaa_otk_nappi_" + tuote_id)
								.css("background-color","green")
								.addClass("disabled");
						} else {
							alert("ERROR: Tuote on jo kyseisellä tilauskirjalla.");
						}
					});
			});

		//Avataan tuoteikkuna tuotetta painettaessa
		$('.clickable')
            .css('cursor', 'pointer')
			.click(function(){
				//haetaan tuotteen id
				let tecdoc_id = $(this).closest('tr').attr('data-tecdoc_id');
                let tuote_id = $(this).closest('tr').attr('data-tuote_id');
                tecdoc_id = (!!tecdoc_id) ? tecdoc_id : null;
                tuote_id = (!!tuote_id) ? tuote_id : null;

                productModal(tuote_id, tecdoc_id);
			});
	});//doc.ready

	//qs["haluttu ominaisuus"] voi hakea urlista php:n GET
	//funktion tapaan tietoa
	let qs = (function(a) {
		let p, i, b = {};
		if (a !== "") {
			for ( i = 0; i < a.length; ++i ) {
				p = a[i].split('=', 2);

				if (p.length === 1) {
					b[p[0]] = "";
				} else {
					b[p[0]] = decodeURIComponent(p[1].replace(/\+/g, " ")); }
			}
		}

		return b;
	})(window.location.search.substr(1).split('&'));

    // Laitetaan ennen sivun päivittämistä tehdyt valinnat takaisin
    if ( qs["manuf"] ) {
        let manuf = qs["manuf"];
        let model = qs["model"];
        let car = qs["car"];
        let osat = qs["osat"];
        let osat_alalaji = qs["osat_alalaji"];
        taytaAjoneuvomallillahakuValinnat(manuf, model, car, osat, osat_alalaji);
    }

	if ( qs["haku"] ) {
		let search = qs["haku"];
		$("#search").val(search);
	}

	if( qs["numerotyyppi"] ){
		let number_type = qs["numerotyyppi"];
		if (number_type === "all" || number_type === "articleNo" ||
			number_type === "comparable" || number_type === "oe") {
			$("#numerotyyppi").val(number_type);
		}
	}

	if ( qs["exact"] ){
		let exact = qs["exact"];
		if (exact === "true" || exact === "false") {
			$("#hakutyyppi").val(exact);
		}
	}

</script>
</body>
</html>
