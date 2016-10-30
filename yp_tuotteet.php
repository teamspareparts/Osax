<?php
require_once '_start.php'; global $db, $user, $cart;
require_once 'tecdoc.php';
require_once 'apufunktiot.php';


/**
 * Lisää uuden tuotteen tietokantaan.
 * Jos tuote on jo tietokannassa, päivittää uudet tiedot, ja asettaa aktiiviseksi.
 * @param DByhteys $db
 * @param array $val
 * @return bool <p> onnistuiko lisäys. Tosin, jos jotain menee pieleen niin se heittää exceptionin.
 */
function add_product_to_catalog( DByhteys $db, /*array*/ $val ) {
	$sql = "INSERT INTO tuote 
				(articleNo, brandNo, hinta_ilman_ALV, ALV_kanta, varastosaldo, minimimyyntiera, alennusera_kpl, 
					alennusera_prosentti) 
			VALUES (?, ?, ?, ?, ?, ?, ?, ?)
			ON DUPLICATE KEY 
				UPDATE articleNo=VALUES(articleNo), brandNo=VALUES(brandNo), hinta_ilman_ALV=VALUES(hinta_ilman_ALV), 
					ALV_kanta=VALUES(ALV_kanta), varastosaldo=VALUES(varastosaldo),
					minimimyyntiera=VALUES(minimimyyntiera), alennusera_kpl=VALUES(alennusera_kpl),
					alennusera_prosentti=VALUES(alennusera_prosentti), aktiivinen = 1";
	return $db->query( $sql,
		[ $val[0],$val[1],$val[2],$val[3],$val[4],$val[5],$val[6],$val[7] ] );
}

/**
 * Poistaa tuotteen tietokannasta, asettamalla 'aktiivinen'-kentän -> 0:ksi.
 * @param DByhteys $db
 * @param int $id
 * @return bool <p> onnistuiko poisto. Tosin, jos jotain menee pieleen, niin DByhteys heittää exceptionin.
 */
function remove_product_from_catalog( DByhteys $db, /*int*/ $id) {
	return $db->query( "UPDATE tuote SET aktiivinen = 0 WHERE id = ?", [$id] );
}

/**
 * Muokkaa aktivoitua tuotetta tietokannassa.
 * Parametrina annetut tiedot tallennetaan tietokantaan.
 * @param DByhteys $db
 * @param array $val
 * @return bool <p> onnistuiko muutos. Tosin heittää exceptionin, jos jotain menee vikaan haussa.
 */
function modify_product_in_catalog( DByhteys $db, /*array*/ $val ) {
	$sql = "UPDATE tuote 
			SET hinta_ilman_ALV = ?, ALV_kanta = ?, varastosaldo = ?, minimimyyntiera = ?, 
				alennusera_kpl = ?, alennusera_prosentti = ?
		  	WHERE id = ?";

	return $db->query( $sql,
		[ $val[0],$val[1],$val[2],$val[3],$val[4],$val[5],$val[6] ] );
}

/**
 * Hakee kaikki ALV-kannat, tekee niistä dropdown-valikon, ja palauttaa HTML-koodin.
 * @param DByhteys $db
 * @return String <p> HTML-koodia. Dropdown-valikko.
 */
function hae_kaikki_ALV_kannat_ja_lisaa_alasvetovalikko ( $db ) {
    $sql = "SELECT kanta, prosentti FROM ALV_kanta ORDER BY kanta ASC;";
    $rows = $db->query( $sql, NULL, FETCH_ALL );

    $return_string = '<select name="alv_lista">';
    foreach ( $rows as $alv ) {
        $alv->prosentti = str_replace( '.', ',', $alv->prosentti );
        $return_string .= "<option name=\"alv\" value=\"{$alv->kanta}\">{$alv->kanta}; {$alv->prosentti}</option>";
    }
    $return_string .= "</select>";

    return $return_string;
}

/**
 * Hakee kaikki hankintapaikat, tekee niistä dropdown-valikon, ja palauttaa HTML-koodin.
 * @param DByhteys $db
 * @return String <p> HTML-koodia. Dropdown-valikko.
 */
function hae_kaikki_hankintapaikat_ja_lisaa_alasvetovalikko ( $db ) {
    $sql = "SELECT id, nimi FROM hankintapaikka ORDER BY id ASC;";
    $rows = $db->query( $sql, NULL, FETCH_ALL );

    $return_string = '<select name="hankintapaikka_lista">';
    foreach ( $rows as $hp ) {
        $return_string .= "<option name=\"alv\" value=\"{$hp->id}\">{$hp->id} - {$hp->nimi}</option>";
    }
    $return_string .= "</select>";

    return $return_string;
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
		$query = "	SELECT 	*, (hinta_ilman_alv * (1+ALV_kanta.prosentti)) AS hinta
			    		FROM 	tuote 
		  	  	    	JOIN 	ALV_kanta
		    				ON	tuote.ALV_kanta = ALV_kanta.kanta
				    	WHERE 	tuote.articleNo = ?
					        AND tuote.brandNo = ?
					 	    AND tuote.aktiivinen = 1 ";

		return $db->query($query, [str_replace(" ", "", $product->articleNo), $product->brandNo], FETCH_ALL, PDO::FETCH_OBJ);
	}


	$catalog_products = $not_available_catalog_products = $not_in_catalog = array();
	$ids = $articleIds = array();	//duplikaattien tarkistusta varten

	//Lajitellaan tuotteet sen mukaan, löytyikö tietokannasta vai ei.
	foreach ( $products as $product ) {
		$row = get_product_from_database($db, $product);
		if ( !$row && !in_array($product->articleId, $articleIds)) {
			$articleIds[] = $product->articleId;
			$product->articleName = isset($product->articleName) ? $product->articleName : $product->genericArticleName;
			$not_in_catalog[] = $product;
		}
		if ( $row ) {
			//Kaikki löytyneet tuotteet (eri hankintapaikat)
			foreach ($row as $tuote) {
				if (!in_array($tuote->id, $ids)){
					$ids[] = $tuote->id;
					$tuote->articleId = $product->articleId;
					$tuote->articleName = isset($product->articleName) ? $product->articleName : $product->genericArticleName;
					$tuote->brandName = $product->brandName;
					if (($tuote->varastosaldo >= $tuote->minimimyyntiera) && ($tuote->varastosaldo != 0)) {
						$catalog_products[] = $tuote;
					} else {
						$not_available_catalog_products[] = $tuote;
					}
				}
			}
		}
	}
	merge_products_with_optional_data( $catalog_products );
	merge_products_with_optional_data( $not_available_catalog_products );

	return [$catalog_products, $not_available_catalog_products, $not_in_catalog];
}

/** Järjestetään tuotteet hinnan mukaan
 * @param $catalog_products
 * @return array <p> Sama array, mutta sorted
 */
function sortProductsByPrice( $catalog_products ){
	/** @param $a
	 * @param $b
	 * @return bool
	 */
	function cmpPrice($a, $b) {
		return ($a->hinta > $b->hinta);
	}
	usort($catalog_products, "cmpPrice");
	return $catalog_products;
}

/**
 * Tarkastaa onko numerossa hankintapaikkaan viittaavaa etuliitettä.
 * @param $number
 * @return bool
 */
function tarkasta_etuliite(/*String*/ $number){
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
function halkaise_hakunumero(&$number, &$etuliite){
	$etuliite = substr($number, 0, 3);
	$number = substr($number, 4);
}


$haku = FALSE;
$products = $catalog_products = $not_in_catalog = $not_available = [];

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
	$not_available = $filtered_product_arrays[1];
	$not_in_catalog = $filtered_product_arrays[2];
	$catalog_products = sortProductsByPrice($catalog_products);
}

if ( !empty($_GET["manuf"]) ) {
	$haku = TRUE; // Hakutulosten tulostamista varten. Ei tarvitse joka kerta tarkistaa isset()
	$selectCar = $_GET["car"];
	$selectPartType = $_GET["osat_alalaji"];

	$products = getArticleIdsWithState($selectCar, $selectPartType);
	$filtered_product_arrays = filter_catalog_products( $db, $products );
	$catalog_products = $filtered_product_arrays[0];
	$not_available = $filtered_product_arrays[1];
	$not_in_catalog = $filtered_product_arrays[2];
	$catalog_products = sortProductsByPrice($catalog_products);
}
?>
<!DOCTYPE html>
<html lang="fi">
<head>
	<meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1">

	<link rel="stylesheet" href="css/styles.css">
	<link rel="stylesheet" href="css/jsmodal-light.css">
	<link rel="stylesheet" href="css/bootstrap.css">

	<link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
	<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.0/jquery.min.js"></script>
	<script src="https://ajax.googleapis.com/ajax/libs/jqueryui/1.11.4/jquery-ui.min.js"></script>

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
		</div>
		<?php require 'ajoneuvomallillahaku.php'; ?>
	</section>

	<section class="hakutulokset">
		<?php if ( $haku ) : ?>
			<h4>Yhteensä löydettyjä tuotteita:
				<?=count($catalog_products) + count($not_available) + count($not_in_catalog)?></h4>

			<?php if ( $catalog_products) : // Tulokset (saatavilla) ?>
				<h2>Saatavilla: (<?=count($catalog_products)?>)</h2>
				<table style="min-width: 90%;"><!-- Katalogissa saatavilla, tilattavissa olevat tuotteet (varastosaldo > 0) -->
					<thead>
					<tr><th>Kuva</th>
						<th>Tuotenumero</th>
						<th>Tuote</th>
						<th>Info</th>
						<th class="number">Saldo</th>
						<th class="number">Hinta (sis. ALV)</th>
						<?php if ( $user->isAdmin() ) : ?>
							<th class="number">Ostohinta ALV0%</th>
						<?php endif; ?>
						<th>Kpl</th>
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
							<?php if ( $user->isAdmin() ) : ?>
								<td class="number"><?=format_euros($product->sisaanostohinta)?></td>
							<?php endif;?>
							<td style="padding-top: 0; padding-bottom: 0;">
								<input id="maara_<?=$product->id?>" name="maara_<?=$product->id?>" class="maara"
									   type="number" value="0" min="0" title="Kappale-määrä"></td>
							<td class="toiminnot" id="tuote_cartAdd_<?=$product->id?>">
								<!-- //TODO: Disable nappi, ja väritä tausta lisäyksen jälkeen -->
								<button class="nappi" onclick="addToShoppingCart(<?=$product->id?>)">
									<i class="material-icons">add_shopping_cart</i>Osta</button></td>
						</tr>
					<?php endforeach; //TODO: Poista ostoskorista -nappi(?) ?>
					</tbody>
				</table>
			<?php endif; //if $catalog_products

			if ( $not_available) : // Tulokset (ei saatavilla) ?>
				<h2>Ei varastossa: (<?=count($not_available)?>)</h2>
				<table style="min-width: 90%;"><!-- Katalogissa olevat, ei tilattavissa olevat tuotteet (varastosaldo < minimimyyntierä) -->
					<thead>
					<tr><th>Kuva</th>
						<th>Tuotenumero</th>
						<th>Tuote</th>
						<th>Info</th>
						<th class="number">Saldo</th>
						<th class="number">Hinta (sis. ALV)</th>
						<?php if ( $user->isAdmin() ) : ?>
							<th class="number">Ostohinta ALV0%</th>
						<?php endif; ?>
						<th></th>
					</tr>
					</thead>
					<tbody>
					<?php foreach ($not_available as $product) : ?>
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
							<?php if ( $user->isAdmin() ) : ?>
								<td class="number"><?=format_euros($product->sisaanostohinta)?></td>
							<?php endif; ?>
							<td id="tuote_ostopyynto_<?=$product->id?>">
								<button onClick="ostopyynnon_varmistus(<?=$product->id?>);">
									<i class="material-icons">info</i></button>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; //if $not_available catalog products

			if ( $not_in_catalog) : //Tulokset (ei katalogissa)?>
				<h2>Ei valikoimassa: (<?=count($not_in_catalog)?>)</h2>
				<table><!-- Katalogissa ei olevat, ei tilattavissa olevat tuotteet. TecDocista. -->
					<thead>
					<tr><th>Tuotenumero</th>
						<th>Tuote</th>
						<th>Info</th>
					</tr>
					</thead>
					<tbody>
					<?php foreach ($not_in_catalog as $product) : ?>
						<tr data-val="<?=$product->articleId?>">
							<td class="clickable"><?=$product->articleNo?></td>
							<td class="clickable"><?=$product->brandName?><br><?=$product->articleName?></td>
							<td><a class="nappi" href='javascript:void(0)'
                                   onclick="showAddDialog('<?=$product->articleNo?>', <?=$product->brandNo?>)">Lisää</a></td>
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

<script type="text/javascript">

    /**
     * Tuotteen lisäys valikoimaan.
     * @param articleNo
     * @param brandNo
     */
    function showAddDialog( articleNo, brandNo ) {
        var alv_valikko = <?php echo json_encode( hae_kaikki_ALV_kannat_ja_lisaa_alasvetovalikko( $db ) ); ?>;
        Modal.open( {
            content: '\
				<div class="dialogi-otsikko">Lisää tuote</div> \
				<form action="yp_tuotteet.php" name="lisayslomake" method="post"> \
					<label for="hinta">Hinta (ilman ALV):</label><span class="dialogi-kentta"><input class="eur" name="hinta" placeholder="0,00"> &euro;</span><br> \
					<label for="alv">ALV Verokanta:</label><span class="dialogi-kentta"> \
				    '+alv_valikko+'\
					</span><br> \
					<label for="varastosaldo">Varastosaldo:</label><span class="dialogi-kentta"><input class="kpl" name="varastosaldo" placeholder="0"> kpl</span><br> \
					<label for="minimimyyntiera">Minimimyyntierä:</label><span class="dialogi-kentta"><input class="kpl" name="minimimyyntiera" placeholder="0"> kpl</span><br> \
					<label for="alennusera_kpl">Määräalennus (kpl):</label><span class="dialogi-kentta"><input class="kpl" name="alennusera_kpl" placeholder="0"> kpl</span><br> \
					<label for="alennusera_prosentti">Määräalennus (%):</label><span class="dialogi-kentta"><input class="eur" name="alennusera_prosentti" placeholder="0"></span><br> \
					<p><input class="nappi" type="submit" name="laheta" value="Lisää" onclick="document.lisayslomake.submit()"><a class="nappi" style="margin-left: 10pt;" \
						href="javascript:void(0)" onclick="Modal.close()">Peruuta</a></p> \
					<input type="hidden" name="lisaa" value="' + articleNo + '"> \
					<input type="hidden" name="brandNo" value=' + brandNo + '> \
				</form>',
            draggable: true
        } );
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
		<p style="margin-top: 20pt;"><a class="nappi" href="yp_tuotteet.php?poista=' + id + '">Poista</a><a class="nappi" style="margin-left: 10pt;" href="javascript:void(0)" \
			onclick="Modal.close()">Peruuta</a></p>'
        } );
    }


    /**
     * Valikoimaan lisätyn tuotteen muokkaus
     * @param id
     * @param price
     * @param alv
     * @param count
     * @param minimumSaleCount
     * @param alennusera_kpl
     * @param alennusera_prosentti
     */
    function showModifyDialog(id, price, alv, count, minimumSaleCount, alennusera_kpl, alennusera_prosentti) {
        var alv_valikko = <?php echo json_encode( hae_kaikki_ALV_kannat_ja_lisaa_alasvetovalikko( $db ) ); ?>;
        Modal.open( {
            content: '\
				<div class="dialogi-otsikko">Muokkaa tuotetta</div> \
				<form action="yp_tuotteet.php" name="muokkauslomake" method="post"> \
					<label for="hinta">Hinta (ilman ALV):</label><span class="dialogi-kentta"><input class="eur" name="hinta" placeholder="0,00" value="' + price + '"> &euro;</span><br> \
					<label for="alv">ALV Verokanta:</label><span class="dialogi-kentta"> \
						 \
					</span><br> \
					<span class="dialogi-kentta">Nykyinen verokanta: ' + alv + '</span><br>\
					<label for="varastosaldo">Varastosaldo:</label><span class="dialogi-kentta"><input class="kpl" name="varastosaldo" placeholder="0" value="' + count + '"> kpl</span><br> \
					<label for="minimimyyntiera">Minimimyyntierä:</label><span class="dialogi-kentta"><input class="kpl" name="minimimyyntiera" placeholder="0" value="' + minimumSaleCount + '"> kpl</span><br> \
					<label for="alennusera_kpl">Määräalennus (kpl):</label><span class="dialogi-kentta"><input class="kpl" name="alennusera_kpl" placeholder="0" value="' + alennusera_kpl + '"> kpl</span><br> \
					<label for="alennusera_prosentti">Määräalennus (%):</label><span class="dialogi-kentta"><input class="eur" name="alennusera_prosentti" placeholder="0" value="' + alennusera_prosentti + '"></span><br> \
					<p><input class="nappi" type="submit" name="tallenna" value="Tallenna" onclick="document.muokkauslomake.submit()"><a class="nappi" style="margin-left: 10pt;" \
						href="javascript:void(0)" onclick="Modal.close()">Peruuta</a></p> \
					<input type="hidden" name="muokkaa" value="' + id + '"> \
				</form>',
            draggable: true
        } );
    }



	$(document).ready(function(){

		//info-nappulan sisältö
		$("span.info-box").hover(function () {
			$(this).append('<div class="tooltip"><p>Tarkka haku</p></div>');
		}, function () {
			$("div.tooltip").remove();
		});


		$('.clickable')
			.css('cursor', 'pointer')
			.click(function(){
				//haetaan tuotteen id
				var articleId = $(this).closest('tr').attr('data-val');
				//spinning icon
				//$('#cover').addClass("loading");
				//haetaan tuotteen tiedot tecdocista
				//getDirectArticlesByIds6(articleId);
                productModal(articleId);
			});

	});//doc.ready

	//qs["haluttu ominaisuus"] voi hakea urlista php:n GET
	//funktion tapaan tietoa
	var qs = (function(a) {
		var p, i, b = {};
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
	if ( qs["manuf"] ){
		var manuf = qs["manuf"];
		var model = qs["model"];
		var car = qs["car"];
		var osat = qs["osat"];
		var osat_alalaji = qs["osat_alalaji"];


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

	if ( qs["haku"] ){
		var search = qs["haku"];
		$("#search").val(search);
	}

	if( qs["numerotyyppi"] ){
		var number_type = qs["numerotyyppi"];
		if (number_type == "all" || number_type == "articleNo" ||
			number_type == "comparable" || number_type == "oe") {
			$("#numerotyyppi").val(number_type);
		}
	}

	if ( qs["exact"] ){
		var exact = qs["exact"];
		if (exact == "true" || exact == "false") {
			$("#hakutyyppi").val(exact);
		}
	}



</script>

</body>
</html>
