<?php
require '_start.php'; global $db, $user, $cart;
require 'tecdoc.php';
require 'apufunktiot.php';

/**
 * Lisää uuden tuotteen tietokantaan.
 * Jos tuote on jo tietokannassa, päivittää uudet tiedot, ja asettaa aktiiviseksi.
 * //TODO: Mitä tehdään keskiostohinnalle ja yhteensa_kpl, kun ON DUPLICATE KEY ?
 * @param DByhteys $db
 * @param array $val
 * @return bool <p> onnistuiko lisäys. Tosin, jos jotain menee pieleen niin se heittää exceptionin.
 */
function add_product_to_catalog( DByhteys $db, array $val ) {
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
		return $db->query($sql, $val);
	}

	return false;
}

/**
 * Poistaa tuotteen tietokannasta, asettamalla 'aktiivinen'-kentän -> 0:ksi.
 * @param DByhteys $db
 * @param int $id
 * @return bool <p> onnistuiko poisto. Tosin, jos jotain menee pieleen, niin DByhteys heittää exceptionin.
 */
function remove_product_from_catalog( DByhteys $db, /*int*/ $id) {
    $db->query( "DELETE FROM ostotilauskirja_tuote WHERE tuote_id = ?", [$id] );
	return $db->query( "UPDATE tuote SET aktiivinen = 0 WHERE id = ?", [$id] );
}

/**
 * Muokkaa aktivoitua tuotetta tietokannassa.
 * Parametrina annetut tiedot tallennetaan tietokantaan.
 * @param DByhteys $db
 * @param array $val
 * @return bool <p> onnistuiko muutos. Tosin heittää exceptionin, jos jotain menee vikaan haussa.
 */
function modify_product_in_catalog( DByhteys $db, array $val ) {
	$sql = "UPDATE tuote 
			SET keskiostohinta = IFNULL((keskiostohinta * yhteensa_kpl + sisaanostohinta * (?-varastosaldo)) / (yhteensa_kpl - varastosaldo + ?),0),
				yhteensa_kpl = yhteensa_kpl + ? - varastosaldo,
				tilauskoodi = ?, sisaanostohinta = ? ,hinta_ilman_ALV = ?, ALV_kanta = ?, varastosaldo = ?, 
				minimimyyntiera = ?, hyllypaikka = ?, paivitettava = 1
		  	WHERE id = ?";

	return $db->query( $sql,
		[ $val[3],$val[3],$val[3],$val[0],$val[1],$val[2],$val[3],$val[4],$val[5],$val[6],$val[7] ] );
}

/**
 * Hakee kaikki ALV-kannat, tekee niistä dropdown-valikon, ja palauttaa HTML-koodin.
 * @param DByhteys $db
 * @return String <p> HTML-koodia. Dropdown-valikko.
 */
function hae_kaikki_ALV_kannat_ja_lisaa_alasvetovalikko ( DByhteys $db ) {
    $sql = "SELECT kanta, prosentti FROM ALV_kanta ORDER BY kanta ASC";
    $rows = $db->query( $sql, NULL, FETCH_ALL );

    $return_string = '<select name="alv_lista" id="alv_lista">';
    foreach ( $rows as $alv ) {
        $alv->prosentti = str_replace( '.', ',', $alv->prosentti );
        $return_string .= "<option name=\"alv\" value=\"{$alv->kanta}\">{$alv->kanta}; {$alv->prosentti}</option>";
    }
    $return_string .= "</select>";

    return $return_string;
}

/**
 * //TODO: Väliaikainen ratkaisu
 * @param DByhteys $db
 * @return String <p> HTML-koodia. Dropdown-valikko.
 */
function hae_kaikki_yritykset_ja_lisaa_alasvetovalikko ( $db ) {
	$sql = "SELECT id, nimi FROM yritys WHERE aktiivinen = 1 ORDER BY nimi ASC";
	$rows = $db->query( $sql, NULL, FETCH_ALL );

	$return_string = '<select name="yritys_id">
		<option value="">- Tyhjä -</option>';
	foreach ( $rows as $yritys ) {
		$return_string .= "<option name='yritys' value='{$yritys->id}'>{$yritys->id}; {$yritys->nimi}</option>";
	}
	$return_string .= "</select>";

	return $return_string;
}

/**
 * @param DByhteys $db
 * @param array $values
 * @param bool $yrityskohtainen
 * @return int
 */
function lisaa_alennus( DByhteys $db, array $values, /*bool*/$yrityskohtainen ) {
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
		$sql = "INSERT INTO tuote_erikoishinta (tuote_id, maaraalennus_kpl, alennus_prosentti, alkuPvm, loppuPvm)
				VALUES (?, ?, ?, ?, ?)
				ON DUPLICATE KEY UPDATE
					maaraalennus_kpl=VALUES(maaraalennus_kpl), alennus_prosentti=VALUES(alennus_prosentti),
					alkuPvm=VALUES(alkuPvm), loppuPvm=VALUES(loppuPvm)";
	}

	return $db->query( $sql, $values );
}

/**
 * Jakaa tecdocista löytyvät tuotteet kahteen ryhmään: niihin, jotka löytyvät
 * valikoimasta ja niihin, jotka eivät löydy.
 * Lopuksi lisää liittää TecDoc-tiedot valikoiman tuotteisiin.
 *
 * @param DByhteys $db <p> Tietokantayhteys
 * @param array $products <p> Tuote-array, josta etsitään aktivoidut tuotteet.
 * @return array <p> Kolme arrayta:
 * 		[0]: saatavilla olevat tuotteet, jotka löytyvät catalogista;
 *      [1]: ei saatavilla olevat tuotteet;
 * 		[2]: tuotteet, jotka eivät löydy catalogista
 */
function filter_catalog_products ( DByhteys $db, array $products ) {

	/**
	 * Haetaan tuote tietokannasta artikkelinumeron ja brandinumeron perusteella.
	 * @param DByhteys $db
	 * @param stdClass $product
	 * @return array|bool|stdClass
	 */
	function get_product_from_database(DByhteys $db, stdClass $product){
		$sql = "SELECT 	*, (hinta_ilman_alv * (1+ALV_kanta.prosentti)) AS hinta
				FROM 	tuote 
				JOIN 	ALV_kanta ON tuote.ALV_kanta = ALV_kanta.kanta
				WHERE 	tuote.articleNo = ? AND tuote.brandNo = ? AND tuote.aktiivinen = 1 ";

		return $db->query($sql, [str_replace(" ", "", $product->articleNo), $product->brandNo], FETCH_ALL );
	}

	$catalog_products = $all_products = array();
	$ids = $articleIds = array();	//duplikaattien tarkistusta varten

	//Lajitellaan tuotteet sen mukaan, löytyikö tietokannasta vai ei.
	foreach ( $products as $product ) {
		$row = get_product_from_database($db, $product);
		if (!in_array($product->articleId, $articleIds)) {
			$articleIds[] = $product->articleId;
			$product->articleName = isset($product->articleName) ? $product->articleName : $product->genericArticleName;
			$all_products[] = $product;
		}
		if ( $row ) {
			//Kaikki löytyneet tuotteet (eri hankintapaikat)
			foreach ($row as $tuote) {
				if (!in_array($tuote->id, $ids)){
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
 * Järjestetään tuotteet hinnan mukaan.
 * @param $catalog_products
 */
function sortProductsByPrice( &$catalog_products ) {
	usort($catalog_products, "cmpPrice");
}

/**
 * Vertailufunktio usortille
 * @param $a
 * @param $b
 * @return bool
 */
function cmpPrice($a, $b) {
	return ($a->hinta > $b->hinta);
}


/**
 * Tarkastaa onko numerossa hankintapaikkaan viittaavaa etuliitettä.
 * @param $number
 * @return bool
 */
function tarkasta_etuliite( /*String*/ $number ) {
	if ( strlen($number)>4 && $number[3]==="-" && is_numeric(substr($number, 0, 3)) ){
		return true;
	} else {
		return false;
	}
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

if ( !empty($_POST['lisaa']) ) {
    $tuotekoodi = (str_pad($_POST['hankintapaikat'], 3, "0", STR_PAD_LEFT) .
                    "-" . str_replace(" ", "", strval($_POST['articleNo'])));
    $array = [
        str_replace(" ", "", strval($_POST['articleNo'])),
        $_POST['brandNo'],
        $_POST['hankintapaikat'],
        $tuotekoodi,
        str_replace(" ", "", $_POST['tilauskoodi']),
		str_replace(',', '.', $_POST['ostohinta']),
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
    if ( remove_product_from_catalog( $db, $_POST['id'] ) ) {
		$_SESSION["feedback"] = '<p class="success">Tuote poistettu!</p>';
    } else { $_SESSION["feedback"] = '<p class="error">Tuotteen poisto epäonnistui!</p>'; }

}
elseif ( !empty($_POST['muokkaa']) ) {
    $array = [
		str_replace(" ", "", $_POST['tilauskoodi']),
		str_replace(',', '.', $_POST['ostohinta']),
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
	if ( lisaa_alennus( $db, array_values($_POST), !empty($_POST['yritys_id']) ) ) {
		$_SESSION["feedback"] = '<p class="success">Tuotteelle lisätty alennus!</p>';
	} else {
		$_SESSION["feedback"] = '<p class="error">ERROR: Alennuksen lisäys ei onnistunut.</p>';
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

if ( !empty($_GET['haku']) ) {
	$haku = TRUE; // Hakutulosten tulostamista varten.
	$number = addslashes(str_replace(" ", "", $_GET['haku']));  //hakunumero
	$etuliite = null;                                           //mahdollinen etuliite
	//TODO: Jos tuotenumerossa on neljäs merkki: -, tulee se jättää pois tai haku epäonnistuu
	//TODO: sillä ei voida tietää kuuluuko etuliite tuotenumeroon vai kertooko se hankintapaikan (Esim 200-149)

	$numerotyyppi = isset($_GET['numerotyyppi']) ? $_GET['numerotyyppi'] : null;	//numerotyyppi
	$exact = (isset($_GET['exact']) && $_GET['exact'] === 'false') ? false : true;	//tarkka haku
	switch ($numerotyyppi) {
		case 'all':
			if(tarkasta_etuliite($number)) halkaise_hakunumero($number, $etuliite);
			$products = getArticleDirectSearchAllNumbersWithState($number, 10, $exact);
			break;
		case 'articleNo':
			if(tarkasta_etuliite($number)) halkaise_hakunumero($number, $etuliite);
			$products = getArticleDirectSearchAllNumbersWithState($number, 0, $exact);
			break;
		case 'comparable':
			if(tarkasta_etuliite($number)) halkaise_hakunumero($number, $etuliite);
			$products1 = getArticleDirectSearchAllNumbersWithState($number, 0, $exact);	//tuote
			$products2 = getArticleDirectSearchAllNumbersWithState($number, 3, $exact);	//vertailut
			$products = array_merge($products1, $products2);
			break;
		case 'oe':
			$products = getArticleDirectSearchAllNumbersWithState($number, 1, $exact);
			break;
		default:	//jos numerotyyppiä ei ole määritelty (= joku on ruvennut leikkimään GET parametrilla)
			$products = getArticleDirectSearchAllNumbersWithState($number, 10, $exact);
			break;
	}

	// Filtteröidään catalogin tuotteet kolmeen listaan: saatavilla, ei saatavilla ja tuotteet, jotka ei ole valikoimassa.
	$filtered_product_arrays = filter_catalog_products( $db, $products );
	$catalog_products = $filtered_product_arrays[0];
	$all_products = $filtered_product_arrays[1];
	sortProductsByPrice($catalog_products);
}
else if ( !empty($_GET["manuf"]) ) {
	$haku = TRUE; // Hakutulosten tulostamista varten. Ei tarvitse joka kerta tarkistaa isset()
	$selectCar = $_GET["car"];
	$selectPartType = $_GET["osat_alalaji"];

	$products = getArticleIdsWithState($selectCar, $selectPartType);
	$filtered_product_arrays = filter_catalog_products( $db, $products );
	$catalog_products = $filtered_product_arrays[0];
	$all_products = $filtered_product_arrays[1];
	sortProductsByPrice($catalog_products);
}
?>
<!DOCTYPE html>
<html lang="fi">
<head>
	<meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">

    <link rel="stylesheet" href="css/bootstrap.css">
	<link rel="stylesheet" href="css/styles.css">
	<link rel="stylesheet" href="css/jsmodal-light.css">
    <link rel="stylesheet" href="css/image_modal.css">

	<link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
	<script src="https://code.jquery.com/jquery-3.1.1.min.js"></script>

	<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>
	<script src="http://webservicepilot.tecdoc.net/pegasus-3-0/services/TecdocToCatDLB.jsonEndpoint?js"></script>
	<script src="js/jsmodal-1.0d.min.js"></script>
	<title>Tuotteet</title>
</head>
<body>
<?php
require 'header.php';
require 'tuotemodal.php';
?>
<main class="main_body_container">
	<section class="flex_row">
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
			<?php if ( $haku ) : ?>
				<h3>Yhteensä löydettyjä tuotteita:
					<?=count($catalog_products) + count($all_products) ?></h3>
			<?php endif; ?>
		</div>
		<?php require 'ajoneuvomallillahaku.php'; ?>
	</section>

    <?= $feedback ?>

	<section class="hakutulokset">
		<?php if ( $haku ) : ?>
			<?php if ( $catalog_products) : // Tulokset (saatavilla) ?>
				<table style="min-width: 90%;"><!-- Katalogissa saatavilla, tilattavissa olevat tuotteet (varastosaldo > 0) -->
					<thead>
					<tr><th colspan="9" class="center" style="background-color:#1d7ae2;">Valikoimassa: (<?=count($catalog_products)?>)</th></tr>
					<tr> <th>Kuva</th> <th>Tuotenumero</th> <th>Tuote</th> <th>Info</th>
						<th class="number">Saldo</th> <th class="number">Hinta (sis. ALV)</th>
                        <th class="number">Ostohinta ALV0%</th>
						<th>Hyllypaikka</th>
						<th></th>
					</tr>
					</thead>
					<tbody>
					<?php foreach ($catalog_products as $product) : ?>
						<tr data-val="<?=$product->articleId?>">
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
							<td class="number"><?=format_integer($product->varastosaldo)?></td>
							<td class="number"><?=format_euros($product->hinta)?></td>
                            <td class="number"><?=format_euros($product->sisaanostohinta)?></td>
							<td><?=$product->hyllypaikka?></td>
							<td class="toiminnot">
								<!-- //TODO: Disable nappi, ja väritä tausta lisäyksen jälkeen -->
								<button class="nappi" onclick="showRemoveDialog(<?=$product->id?>)">
                                    Poista</button><br>
                                <button class="nappi" onclick="showModifyDialog(<?=$product->id?>, '<?=$product->tuotekoodi?>', '<?=$product->tilauskoodi?>',
                                    '<?=$product->sisaanostohinta?>', '<?=$product->hinta_ilman_ALV?>',
                                    '<?=$product->ALV_kanta?>', '<?=$product->varastosaldo?>',
                                    '<?=$product->minimimyyntiera?>', '<?=$product->hyllypaikka?>')">
                                    Muokkaa</button><br>
                                <button class="nappi" id="lisaa_otk_nappi_<?=$product->id?>" onclick="showLisaaOstotilauskirjalleDialog(<?=$product->id?>,
                                    <?=$product->hankintapaikka_id?>, '<?= $product->articleName?>', '<?= $product->brandName?>')">
                                    OTK</button>
                            </td>
						</tr>
					<?php endforeach; //TODO: Poista ostoskorista -nappi(?) ?>
					</tbody>
				</table>
			<?php endif; //if $catalog_products
			if ( $all_products) : //Tulokset (ei katalogissa)?>
				<table><!-- Katalogissa ei olevat, ei tilattavissa olevat tuotteet. TecDocista. -->
					<thead>
					<tr><th colspan="3" class="center" style="background-color:#1d7ae2;">Kaikki tuotteet: (<?=count($all_products)?>)</th></tr>
					<tr> <th>Tuotenumero</th> <th>Tuote</th> <th>Info</th> </tr>
					</thead>
					<tbody>
					<?php foreach ($all_products as $product) : ?>
						<tr data-val="<?=$product->articleId?>">
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

<script type="text/javascript">

	/**
	 *
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
				if(hankintapaikat.length === 0){
					alert("Luo ensin kyseiselle toimittajalle hankintapaikka!" +
						"\rMUUT -> TOIMITTAJAT -> VALITSE TOIMITTAJA -> UUSI HANKINTAPAIKKA");
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
                        <button class="nappi" style="margin-left: 10pt;" onclick="Modal.close()">Peruuta</button> \
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
		    <input class="nappi" type="submit" name="poista" value="Poista">\
		    <button class="nappi" type="button" style="margin-left: 10pt;" onclick="Modal.close()">Peruuta</button>\
		    <input type="hidden" name="id" value="' + id + '"> \
		</form>'
        } );
    }

	/**
	 * @param id
	 * @param tuotekoodi
	 * @param tilauskoodi
	 * @param ostohinta
	 * @param hinta
	 * @param alv
	 * @param varastosaldo
	 * @param minimimyyntiera
	 * @param hyllypaikka
	 */
    function showModifyDialog(id, tuotekoodi, tilauskoodi, ostohinta, hinta, alv, varastosaldo, minimimyyntiera, hyllypaikka ) {
        let alv_valikko = <?php echo json_encode( hae_kaikki_ALV_kannat_ja_lisaa_alasvetovalikko( $db ) ); ?>;
        let yrit_valikko = <?= json_encode($yrityksien_nimet_alennuksen_asettamista_varten) ?>;
        Modal.open( {
            content: '\
				<div class="dialogi-otsikko">Muokkaa tuotetta'+tuotekoodi+'</div> \
				<form action="" name="muokkauslomake" method="post"> \
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
					<input class="nappi" type="submit" name="muokkaa" value="Tallenna"\
						onclick="document.muokkauslomake.submit()">\
					<button class="nappi red" type="button" style="margin-left: 10pt;" onclick="Modal.close()">Peruuta</button>\
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
					<button class="nappi red" type="button" style="margin-left:10pt;" \
						onclick="Modal.close()">Peruuta</button> \
				</form>',
            draggable: true
        } );
        $("#alv_lista").val(alv);
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
				ostotilauskirjat = JSON.parse(toJSON(data));
				if(ostotilauskirjat.length === 0){
					alert("Luo ensin kyseiselle toimittajalle ostotilauskirja!" +
						"\rMUUT -> TILAUSKIRJAT -> HANKINTAPAIKKA -> UUSI OSTOTILAUSKIRJA");
					return;
				}
				//Luodaan alasvetovalikko
				let ostotilauskirja_lista = '<select name="ostotilauskirjat">';
				for(let i=0; i < ostotilauskirjat.length; i++){
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
                        \
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
				let articleId = $(this).closest('tr').attr('data-val');
                productModal(articleId);
			});

	});//doc.ready

	//qs["haluttu ominaisuus"] voi hakea urlista php:n GET
	//funktion tapaan tietoa
	let qs = (function(a) {
		let p, i, b = {};
		if (a != "") {
			for ( i = 0; i < a.length; ++i ) {
				p = a[i].split('=', 2);

				if (p.length == 1) {
					b[p[0]] = "";
				} else {
					b[p[0]] = decodeURIComponent(p[1].replace(/\+/g, " ")); }
			}
		}

		return b;
	})(window.location.search.substr(1).split('&'));

	//laitetaan ennen sivun päivittämistä tehdyt valinnat takaisin
	if ( qs["manuf"] ) {
		let manuf = qs["manuf"];
		let model = qs["model"];
		let car = qs["car"];
		let osat = qs["osat"];
		let osat_alalaji = qs["osat_alalaji"];

		getModelSeries(manuf);
		getVehicleIdsByCriteria(manuf, model);
		getPartTypes(car);
		getChildNodes(car, osat);

		setTimeout(setSelected ,1000);

		function setSelected(){
			$("#manufacturer").find("option[value=" + manuf + "]").attr('selected', 'selected');
			$("#model").find("option[value=" + model + "]").attr('selected', 'selected');
			$("#car").find("option[value=" + car + "]").attr('selected', 'selected');
			$("#osaTyyppi").find("option[value=" + osat + "]").attr('selected', 'selected');
			$("#osat_alalaji").find("option[value=" + osat_alalaji + "]").attr('selected', 'selected');
		}
	}

	if ( qs["haku"] ) {
		let search = qs["haku"];
		$("#search").val(search);
	}

	if( qs["numerotyyppi"] ){
		let number_type = qs["numerotyyppi"];
		if (number_type == "all" || number_type == "articleNo" ||
			number_type == "comparable" || number_type == "oe") {
			$("#numerotyyppi").val(number_type);
		}
	}

	if ( qs["exact"] ){
		let exact = qs["exact"];
		if (exact == "true" || exact == "false") {
			$("#hakutyyppi").val(exact);
		}
	}
</script>
</body>
</html>
