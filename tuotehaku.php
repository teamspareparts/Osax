<?php declare(strict_types=1);
require '_start.php'; global $db, $user, $cart;
require 'tecdoc.php';
/**
 * Jakaa tecdocista löytyvät tuotteet kahteen ryhmään: niihin, jotka löytyvät
 * valikoimasta ja niihin, jotka eivät löydy.
 * Lopuksi lisätään TecDoc-tiedot valikoiman tuotteisiin.
 *
 * @param DByhteys $db <p> Tietokantayhteys
 * @param array $products <p> Tuote-array, josta etsitään aktivoidut tuotteet.
 * @return array <p> Kolme arrayta:
 * 		[0]: tuotteet, jotka löytyvät catalogista;
 * 		[1]: tuotteet, jotka eivät löydy catalogista
 */
function filter_catalog_products ( DByhteys $db, array $products ) : array {

	/**
	 * Haetaan tuote tietokannasta artikkelinumeron ja brandinumeron perusteella.
	 * @param DByhteys $db
	 * @param string   $articleNo
	 * @param int      $brandNo
	 * @return array
	 */
    function get_tecdoc_product_from_database( DByhteys $db, string $articleNo, int $brandNo ) : array {
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
				WHERE 	    tuote.articleNo = ? AND tuote.aktiivinen = 1 AND tuote.brandNo = ? AND tuote.tecdocissa = 1
				GROUP BY    tuote.id";

        return $db->query($sql, [$articleNo, $brandNo], FETCH_ALL);
    }

	$catalog_products = $not_in_catalog = [];
	$ids = $article_ids = []; // Duplikaattien tarkistusta varten

    // Lajitellaan tuotteet sen mukaan, löytyikö tietokannasta vai ei.
	foreach ( $products as $product ) {
		$product->articleNo = str_replace(" ", "", $product->articleNo);
        $row = get_tecdoc_product_from_database($db, $product->articleNo, $product->brandNo);
		if ( !$row && !in_array($product->articleId, $article_ids)) {
			$article_ids[] = $product->articleId;
			$product->articleName = isset($product->articleName) ? $product->articleName : $product->genericArticleName;
			$not_in_catalog[] = $product;
		}
		if ( $row ) {
		    // Kaikki löytyneet tuotteet (eri hankintapaikat)
            foreach ( $row as $tuote ) {
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

	return [$catalog_products, $not_in_catalog];
}

/**
 * Etsii kannasta itse perustetut tuotteet.
 * @param DByhteys $db
 * @param string   $search_number
 * @param bool     $tarkka_haku
 * @return array
 */
function search_own_products_from_database( DByhteys $db, string $search_number, bool $tarkka_haku = true ) : array {
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
 * @param array $products
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
 * Jaetaan omasta kannasta löytyneet tuotteet kahteen listaan
 * sen perusteella, onko tuotetta varastossa.
 * @param array $products
 * @return array
 */
function divide_products_by_availability( array $products ) : array {
	$available = [];
	$not_available = [];
	foreach ($products as $tuote) {
		if (($tuote->varastosaldo != 0) and ($tuote->varastosaldo >= $tuote->minimimyyntiera)) {
			$available[] = $tuote;
		} else {
			$not_available[] = $tuote;
		}
	}
	return [$available, $not_available];
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
 * Järjestää tuotteet hinnan mukaan.
 * @param $catalog_products
 */
function sortProductsByPrice( &$catalog_products ) {
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

$haku = FALSE;
$products = $catalog_products = $not_in_catalog = $not_available = [];
$own_products = $own_comparable_products = [];

if ( !empty($_GET['haku']) ) {
	$haku = TRUE; // Hakutulosten tulostamista varten.
	$number = addslashes(str_replace(" ", "", $_GET['haku']));  //hakunumero
    $etuliite = null;                                           //mahdollinen etuliite
    //TODO: Jos tuotenumerossa on neljäs merkki: -, tulee se jättää pois tai haku epäonnistuu
    //TODO: sillä ei voida tietää kuuluuko etuliite tuotenumeroon vai kertooko se hankintapaikan (Esim 200-149)


	$numerotyyppi = $_GET['numerotyyppi'] ?? null;	//numerotyyppi
    $exact = (isset($_GET['exact']) && $_GET['exact'] === 'false') ? false : true;	//tarkka haku
	switch ( $numerotyyppi ) {
		case 'all':
            if(tarkasta_etuliite($number)) halkaise_hakunumero($number, $etuliite);
			$products = getArticleDirectSearchAllNumbersWithState($number, 10, $exact);
			$alternative_search_number = get_comparable_number_for_own_product($db, $number);
			if ( $alternative_search_number ) {
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
			if ( !empty($alternative_search_number) ) {
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
		default:	//jos numerotyyppiä ei ole määritelty (= joku on ruvennut leikkimään GET parametrilla)
            $products = getArticleDirectSearchAllNumbersWithState($number, 10, $exact);
			break;
	}

	$filtered_product_arrays = filter_catalog_products( $db, $products );

	// Kaikki tuotteet, jotka löytyy omasta kannasta
	$found_catalog_products = array_unique(array_merge($filtered_product_arrays[0], $own_products, $own_comparable_products), SORT_REGULAR);
	$found_catalog_products = divide_products_by_availability($found_catalog_products);

	// Jaetaan tuotteet kolmeen listaan: saatavilla, ei saatavilla ja tuotteet, jotka ei ole valikoimassa.
	$catalog_products = $found_catalog_products[0];
	$not_available = $found_catalog_products[1];
	$not_in_catalog = $filtered_product_arrays[1];

	// Järjestetään hinnan mukaan
	sortProductsByPrice($catalog_products);
	sortProductsByPrice($not_available);
}

if ( !empty($_GET["manuf"]) ) {
	$haku = TRUE; // Hakutulosten tulostamista varten. Ei tarvitse joka kerta tarkistaa isset()
	$car = (int)$_GET["car"];
	$part_type = (int)$_GET["osat_alalaji"];

	$products = getArticleIdsWithState($car, $part_type);
	$own_comparable_products = search_comparable_products_from_database($db, $products);
	$filtered_product_arrays = filter_catalog_products( $db, $products );

	// Kaikki tuotteet, jotka löytyy omasta kannasta
	$found_catalog_products = array_unique(array_merge($filtered_product_arrays[0], $own_comparable_products), SORT_REGULAR);
	$found_catalog_products = divide_products_by_availability($found_catalog_products);

	$catalog_products = $found_catalog_products[0];
	$not_available = $found_catalog_products[1];
	$not_in_catalog = $filtered_product_arrays[1];

	sortProductsByPrice($catalog_products);
	sortProductsByPrice($not_available);
}

?>
<!DOCTYPE html>
<html lang="fi">
<head>
    <meta charset="utf-8">
	<title>Tuotehaku</title>

	<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css">
	<link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
	<link rel="stylesheet" type="text/css" href="./css/styles.css">
    <link rel="stylesheet" type="text/css" href="./css/jsmodal-light.css">
    <link rel="stylesheet" type="text/css" href="./css/image_modal.css">

    <script src="https://code.jquery.com/jquery-3.1.1.min.js"></script>
	<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js"></script>
	<script src="https://webservice.tecalliance.services/pegasus-3-0/services/TecdocToCatDLB.jsonEndpoint?js"></script>
	<script src="./js/jsmodal-1.0d.min.js"></script>
</head>
<body>
<?php
require 'header.php';
require 'tuotemodal.php';
?>
<main class="main_body_container">
	<div class="otsikko_container">
		<section class="otsikko">
			<h1>Tuotehaku</h1>
		</section>
	</div>

	<section class="white-bg" style="border-radius: 5px; border: 1px solid;">
		<div class="tuotekoodihaku">
			<form action="tuotehaku.php" method="get" class="haku">
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
		</div>
        <?php require 'ajoneuvomallillahaku.php';?>
	</section>

	<section class="hakutulokset">
	<?php if ( $haku ) : ?>
        <h3>Yhteensä löydettyjä tuotteita:
			<?=count($catalog_products) + count($not_available) + count($not_in_catalog)?></h3>
		<?php if ( $catalog_products) : // Tulokset (saatavilla) ?>
		<table style="min-width: 90%;"><!-- Katalogissa saatavilla, tilattavissa olevat tuotteet (varastosaldo > 0) -->
			<thead>
			<tr><th colspan="10" class="center" style="background-color:#1d7ae2;">Saatavilla: (<?=count($catalog_products)?>)</th></tr>
			<tr><th>Kuva</th>
				<th>Tuotenumero</th>
				<th>Tuote</th>
				<th>Info</th>
				<th class="number">Saldo</th>
				<th class="number">Hinta (sis. ALV)</th>
				<?php if ( $user->isAdmin() ) : ?>
					<th class="number">Ostohinta ALV0%</th>
                    <th class="number">Kate %</th>
				<?php endif; ?>
				<th>Kpl</th>
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
					<td class="number"><?=format_number($product->hinta)?></td>
					<?php if ( $user->isAdmin() ) : ?>
						<td class="number"><?=format_number($product->sisaanostohinta)?></td>
                        <td class="number"><?=round(100*(($product->hinta_ilman_ALV - $product->sisaanostohinta)/$product->hinta_ilman_ALV), 2)?>%</td>
					<?php endif;?>
					<td style="padding-top: 0; padding-bottom: 0;">
						<input id="maara_<?=$product->id?>" name="maara_<?=$product->id?>" class="maara"
							   type="number" value="0" min="0" title="Kappale-määrä"></td>
					<td class="toiminnot">
						<button class="nappi" id="tuote_cartAdd_<?=$product->id?>" onclick="addToShoppingCart(
							<?=$product->id?>,'<?=$product->articleName?>','<?=$product->brandName?>')">
							<i class="material-icons">add_shopping_cart</i>Osta</button>
						<?php if ($user->isAdmin()) : ?>
                            <button class="nappi" id="lisaa_otk_nappi_<?=$product->id?>"
                                    onclick="showLisaaOstotilauskirjalleDialog(<?=$product->id?>,
									<?=$product->hankintapaikka_id?>, '<?= $product->articleName?>',
                                    '<?= $product->brandName?>')">OTK</button>
						<?php endif;?>
                    </td>
				</tr>
			<?php endforeach; //TODO: Poista ostoskorista -nappi(?) ?>
			</tbody>
		</table>
		<?php endif; //if $catalog_products

		if ( $not_available) : // Tulokset (ei saatavilla) ?>
		<table style="min-width: 90%;"><!-- Katalogissa olevat, ei tilattavissa olevat tuotteet (varastosaldo < minimimyyntierä) -->
			<thead>
			<tr><th colspan="11" class="center" style="background-color:#1d7ae2;">Ei varastossa: (<?=count($not_available)?>)</th></tr>
			<tr> <th>Kuva</th> <th>Tuotenumero</th> <th>Tuote</th> <th>Info</th> <th class="number">Saldo</th>
				<th class="number">Tehdassaldo</th> <th class="number">Hinta (sis. ALV)</th>
				<?php if ( $user->isAdmin() ) : ?>
					<th class="number">Ostohinta ALV0%</th>
                    <th class="number">Kate%</th>
				<?php endif; ?>
				<th>Tulossa</th>
                <th></th>
			</tr>
			</thead>
			<tbody>
			<?php foreach ($not_available as $product) : ?>
				<tr class="tuote" data-tecdoc_id="<?=$product->articleId?>" data-tuote_id="<?=$product->id?>">
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
					<td class="number" style="text-align: center;">
						<?php if ( !is_null($product->tehdassaldo) ) : ?>
							<?php if ( $product->tehdassaldo > 0 ) : ?>
								<i class="material-icons" style="color:green;" title="
								<?= ($product->hankintapaikka_id == 140) ? "Saatavilla toimittajalta."
									: "Varastossa saldoa: ".format_number($product->tehdassaldo, 0)." kpl" ?>"
								   >
									check_circle
								</i>
							<?php else : ?>
								<i class="material-icons" style="color:red;" title="Tehdasaldo nolla (0).">
									highlight_off
								</i>
							<?php endif; ?>
						<?php endif; ?>
						<!-- Hidden section -->
						<div class="hidden">
							<input style="width: 50px;" id="maara_<?=$product->id?>" name="maara_<?=$product->id?>" class="maara"
							       type="number" value="0" min="0" title="Kappale-määrä">
							<button class="nappi" id="tuote_cartAdd_<?=$product->id?>" onclick="addToShoppingCart(
							<?=$product->id?>,'<?=$product->articleName?>','<?=$product->brandName?>', 1)">
								<i class="material-icons">add_shopping_cart</i></button>
						</div>
					</td>
					<td class="number"><?=format_number($product->hinta)?></td>
					<?php if ( $user->isAdmin() ) : ?>
						<td class="number"><?=format_number($product->sisaanostohinta)?></td>
                        <td class="number"><?=round(100*(($product->hinta_ilman_ALV - $product->sisaanostohinta)/$product->hinta_ilman_ALV), 0)?>%</td>
					<?php endif; ?>
                    <td>
                        <?php if ( isset($product->saapumispaiva) && time() <= strtotime($product->saapumispaiva) ) : ?>
                            <?=date("j.n.Y", strtotime($product->saapumispaiva))?>
                        <?php elseif ( isset($product->tilauskirja_arkisto_saapumispaiva) ) : ?>
	                        <span>Odottaa varastoon purkua.</span>
	                    <?php elseif ( isset($product->tilauskirja->saapumispaiva) ) : ?>
		                    <span>Odottaa tilauskirjan lähetystä...</span>
		                 <?php endif; ?>
                    </td>
					<td id="tuote_ostopyynto_<?=$product->id?>">
						<button class="nappi grey" onClick="ostopyynnon_varmistus(<?=$product->id?>);">
							<i class="material-icons" style="color:#2f5cad;">help_outline</i></button>
						<?php if ($user->isAdmin()) : ?>
                            <button class="nappi" id="lisaa_otk_nappi_<?=$product->id?>"
                                    onclick="showLisaaOstotilauskirjalleDialog(<?=$product->id?>,
									<?=$product->hankintapaikka_id?>, '<?= $product->articleName?>',
                                            '<?= $product->brandName?>')">OTK</button>
						<?php endif;?>
                    </td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
		<?php endif; //if $not_available catalog products

		if ( $not_in_catalog) : //Tulokset (ei katalogissa)?>
		<table><!-- Katalogissa ei olevat, ei tilattavissa olevat tuotteet. TecDocista. -->
			<thead>
			<tr><th colspan="3" class="center" style="background-color:#1d7ae2;">Tilaustuotteet: (<?=count($not_in_catalog)?>)</th></tr>
			<tr><th>Tuotenumero</th>
				<th>Tuote</th>
				<th>Info</th>
			</tr>
			</thead>
			<tbody>
			<?php foreach ($not_in_catalog as $product) : ?>
				<tr data-tecdoc_id="<?=$product->articleId?>">
					<td class="clickable"><?=$product->articleNo?></td>
					<td class="clickable"><?=$product->brandName?><br><?=$product->articleName?></td>
					<td><button class="nappi grey" id="tuote_hnktpyynto_<?=$product->articleId?>"
								onClick="hankintapyynnon_varmistus(
									'<?=$product->articleNo?>',
									'<?=$product->brandName?>',
									'<?=$product->articleName?>',
									'<?=$product->articleId?>');">
						<i class="material-icons" style="color:#2f5cad;">help_outline</i></button>
                    </td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
		<?php endif; //if $not_in_catalog products

		if ( !$catalog_products && !$not_available && !$not_in_catalog ) : ?>
			<h2>Ei tuloksia.</h2>
    	<?php endif; //if ei tuloksia?>

	<?php endif; //if $haku?>
	</section>
</main>

<?php require 'footer.php'; ?>

<script type="text/javascript">
	/**
	 * Tallentaa ostospyynnön tietokantaan
	 * @param {int} product_id - Halutun tuotteen ID
	 */
	function ostopyynnon_varmistus( product_id ) {
		// TODO: Tuotteen nimen ja valmistajan lisäys tietokantaan.
		let vahvistus = confirm( "Olisin tilannut tuotteen, jos sitä olisi ollut saatavilla?");
		if ( vahvistus ) {
			$.post(
				"ajax_requests.php",
				{ tuote_ostopyynto: product_id },
				function( data ) {
					if ( (!!data) === true ) { // Typecasting to int to boolean: !! + variable
						$("#tuote_ostopyynto_" + product_id)
							.css("background-color","green")
							.addClass("disabled");
					} else {
						alert("ERROR: Ostopyyntö ei onnistunut.");
					}
				}
			);
		}
	}

	/**
	 * Tallentaa hankintapyynnön tietokantaan (Käyttäjältä varmistuksen kysymisen jälkeen).
	 * @param {string} articleNo
	 * @param {string} valmistaja
	 * @param {string} tuotteet_nimi
	 * @param {string} articleId
	 */
	function hankintapyynnon_varmistus( articleNo, valmistaja, tuotteet_nimi, articleId ) {
		//TODO: Selitys-tekstikenttä, ja Käykö korvaava -checkbox. jQuery UI?
		let vahvistus, selitys, korvaava_okey;
		vahvistus = confirm( "Olisin tilannut tuotteen, jos sitä olisi ollut saatavilla?");
		if ( vahvistus ) {
			korvaava_okey = confirm( "Kelpaako korvaava tuote?" );
			selitys = prompt( "Syy, miksi olisit halunnut tämä tuotteen? (Vapaaehtoinen)" );
			$.post("ajax_requests.php",
				{	tuote_hankintapyynto: true,
					articleNo: articleNo,
					valmistaja: valmistaja,
					tuotteen_nimi: tuotteet_nimi,
					selitys: selitys,
					korvaava_okey: korvaava_okey },
				function( data ) {
					if ( (!!data) === true ) { // Typecasting to int to boolean: !! + variable
						$("#tuote_hnktpyynto_" + articleId)
							.css("background-color","green")
							.addClass("disabled");
					} else {
						alert("ERROR: Hankintapyyntö ei onnistunut.");
					}
				}
			);
		}
	}

	/**
	 * Lisää tuotteen ostoskoriin ajaxilla.
	 * @param tuote_id
	 * @param tuote_nimi
	 * @param tuote_valmistaja
	 * @param tilaustuote
	 */
	function addToShoppingCart( tuote_id, tuote_nimi, tuote_valmistaja, tilaustuote ) {
		let kpl_maara = $("#maara_" + tuote_id).val();
		if ( kpl_maara > 0 ) {
		    // Tallenna tuotteen tiedot
		    $.post("ajax_requests.php",
			    {   tallenna_tuote: true,
				    tuote_nimi: tuote_nimi,
				    tuote_valmistaja: tuote_valmistaja,
				    tuote_id: tuote_id });
		    // Lisää ostoskoriin
			$.post("ajax_requests.php",
				{	ostoskori_toiminto: true,
					tuote_id: tuote_id,
					kpl_maara: kpl_maara,
					tilaustuote: tilaustuote },
				function( data ) {
					if ( data.success === true ) {
						$("#tuote_cartAdd_" + tuote_id)
							.css("background-color","green")
							.addClass("disabled");
						$("#head_cart_tuotteet").text(data.tuotteet_kpl);
						$("#head_cart_kpl").text(data.yhteensa_kpl);
					} else {
						alert("ERROR: Tuotteen lisääminen ei onnistunut.");
					}
				}
			);
		}
	}

	/**
     * Lisää tuotteen ostotilauskirjalle.
	 * @param tuote_id
	 * @param hankintapaikka_id
	 * @param tuote_nimi
	 * @param tuote_valmistaja
	 */
	function showLisaaOstotilauskirjalleDialog(tuote_id, hankintapaikka_id, tuote_nimi, tuote_valmistaja){
        // Tallenna tuotteen tiedot
        $.post("ajax_requests.php",
            {   tallenna_tuote: true,
                tuote_nimi: tuote_nimi,
                tuote_valmistaja: tuote_valmistaja,
                tuote_id: tuote_id });
        // Haetaan hankintapaikan ostotilauskirjat
		$.post(
			"ajax_requests.php",
			{   hankintapaikan_ostotilauskirjat: true,
				hankintapaikka_id: hankintapaikka_id },
			function( data ) {
				let ostotilauskirjat = data;
				if ( ostotilauskirjat.length === 0 ) {
					alert("Luo ensin tuotteen hankintapaikalle ostotilauskirja!" +
						"\rMUUT -> TILAUSKIRJAT -> HANKINTAPAIKKA -> UUSI OSTOTILAUSKIRJA");
					return;
				}
				//Luodaan alasvetovalikko
				let ostotilauskirja_lista = '<select name="ostotilauskirjat" id="otk_id">';
				for ( let i=0; i < ostotilauskirjat.length; i++ ) {
					ostotilauskirja_lista += '<option name="ostotilauskirja" value="'+ostotilauskirjat[i].id+'">'+ostotilauskirjat[i].tunniste+'</option>';
				}
				ostotilauskirja_lista += '</select>';
				//avataan Modal
				Modal.open({
					content: '\
                        <div class="dialogi-otsikko">Lisää ostotilauskirjaan</div> \
                        <form action="" method="post" onsubmit="event.preventDefault(); lisaaOstotilauskirjalle()"> \
                            <label for="ostotilauskirja">Ostotilauskirja:</label><br> \
				            '+ostotilauskirja_lista+'<br><br> \
				            <label for="kpl">Kappaleet:</label><br> \
				            <input type="number" name="kpl" id="otk_kpl" class="kpl" placeholder="1" min="1" required> kpl<br><br> \
                            <label for="selite">Selite:</label><br> \
                            <textarea name="selite" id="otk_selite" rows="3" cols="25" form="ostotilauskirjalomake"\
                                placeholder="Miksi lisäät tuotteen käsin?"></textarea><br><br> \
                            <input type="submit" value="Lisää tilauskirjalle" class="nappi">\
                            <input type="hidden" name="tuote_id" id="otk_tuote_id" value="'+tuote_id+'"> \
				        </form> \
                    ',
					draggable: true
				});
			}
		);
	}

	/**
	 * Lisää tuotteen ostotilauskirjalle
	 */
	function lisaaOstotilauskirjalle() {
	    let tilauskirja_id = document.getElementById('otk_id').value;
		let tuote_id = document.getElementById('otk_tuote_id').value;
		let kpl = document.getElementById('otk_kpl').value;
		let selite = document.getElementById('otk_selite').value;
		$.post(
			"ajax_requests.php",
			{   lisaa_tilauskirjalle: true,
				ostotilauskirja_id: tilauskirja_id,
				tuote_id: tuote_id,
				kpl: kpl,
				selite: selite },
			function( data ) {
				Modal.close();
				if ( (!!data) === true ) {
					$("#lisaa_otk_nappi_" + tuote_id)
						.css("background-color","green")
						.addClass("disabled");
				} else {
					alert("Tuote on jo kyseisellä tilauskirjalla.");
				}
			});
		return 0;
    }

    /**
     * Haetaan ajaxilla Eoltas-tuotteiden tehdassaldot ja lisätään
     * tehdassaldosta ilmoittava kuvake ja osto-nappi
     */
    function hae_eoltas_tehdassaldo() {
        let tuotteet = document.getElementsByClassName("tuote");
        for (let i = 0; i < tuotteet.length; i++) {
            let hankintapaikka_id = tuotteet[i].cells[1].innerText.substr(0,3);
            let articleNo = tuotteet[i].cells[1].innerText.slice(4);
            let brandName = tuotteet[i].cells[2].innerText.split("\n")[0];
            $.post(
                "ajax_requests.php",
                {   eoltas_tehdassaldo: true,
                    hankintapaikka_id: hankintapaikka_id,
                    articleNo: articleNo,
                    brandName: brandName },
                function( data ) {
                    if ( data ) {
                        // Lisätään tehdassaldoa ilmaiseva kuvake
                        let varoitus_kuvake = "";
                        let tehdassaldo = +data;
                        // Valitaan varoitus ikoni
                        if ( tehdassaldo === 0 ) {
                            varoitus_kuvake = "<i class='material-icons' style='color:red;' " +
                                "title='Tehdassaldo nolla (0).'>highlight_off</i>";
                        } else {
                            varoitus_kuvake = "<i class='material-icons' style='color:green;' " +
                                "title='Tehdassaldo " + tehdassaldo + " kpl'>check_circle</i>";
                        }
                        tuotteet[i].cells[5].innerHTML = varoitus_kuvake + tuotteet[i].cells[5].innerHTML;

                        /************************************************************************
                        */
                         // Lisätään osta -nappi
	                    if ( tehdassaldo > 0 ) {
                            let osta_section = tuotteet[i].cells[5].childNodes[4];
                            osta_section.className = osta_section.className.replace(/\bhidden\b/g, "");
                            // Määritellään max osto-määrä
                            osta_section.childNodes[1].setAttribute('max', tehdassaldo);
                        }
                        /*
                        *************************************************************************/
                    }

                });
        }
    }

    /**
     * Sivun latautuessa esitäytetään valikot URLin perusteella.
     */
    function tayta_valikot() {

        //qs["haluttu ominaisuus"] voi hakea urlista php:n GET
        //funktion tapaan tietoa
        let qs = (function (a) {
            let p, i, b = {};
            if (a !== "") {
                for (i = 0; i < a.length; ++i) {
                    p = a[i].split('=', 2);

                    if (p.length === 1) {
                        b[p[0]] = "";
                    } else {
                        b[p[0]] = decodeURIComponent(p[1].replace(/\+/g, " "));
                    }
                }
            }

            return b;
        })(window.location.search.substr(1).split('&'));

        // Laitetaan ennen sivun päivittämistä tehdyt valinnat takaisin
        if (qs["manuf"]) {
            let manuf = qs["manuf"];
            let model = qs["model"];
            let car = qs["car"];
            let osat = qs["osat"];
            let osat_alalaji = qs["osat_alalaji"];
            taytaAjoneuvomallillahakuValinnat(manuf, model, car, osat, osat_alalaji);
        }

        if (qs["haku"]) {
            let search = qs["haku"];
            $("#search").val(search);
        }

        if (qs["numerotyyppi"]) {
            let number_type = qs["numerotyyppi"];
            if (number_type === "all" || number_type === "articleNo" ||
                number_type === "comparable" || number_type === "oe") {
                $("#numerotyyppi").val(number_type);
            }
        }

        if (qs["exact"]) {
            let exact = qs["exact"];
            if (exact === "true" || exact === "false") {
                $("#hakutyyppi").val(exact);
            }
        }
    }


	$(document).ready(function(){

		// Tuoteikkuna
		$('.clickable')
			.css('cursor', 'pointer')
			.click(function(){
                //haetaan tuotteen id
                let tecdoc_id = $(this).parent('tr').attr('data-tecdoc_id');
                let tuote_id = $(this).parent('tr').attr('data-tuote_id');
                tecdoc_id = (!!tecdoc_id) ? tecdoc_id : null;
                tuote_id = (!!tuote_id) ? tuote_id : null;
                productModal(tuote_id, tecdoc_id);
			});

		// Haetaan Eoltas-tuotteille tehdassaldot
        hae_eoltas_tehdassaldo();

        // Täytetään valikot sivun latautuessa
		tayta_valikot();

	});//doc.ready


</script>

</body>
</html>
