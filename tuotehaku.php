<?php
require '_start.php';
require 'tecdoc.php';
require 'apufunktiot.php';

/**
 * Palauttaa Autovalmistajat selectiin. Vaatii TecDoc-yhteyden.
 * @param array $manufs <p>
 * @return string <p> Select valmistajan vaihtoehtot, HTML:nä.
 * 		Jos ei yhteyttä TecDociin, huomauttaa siitä.
 */
function printManufSelectOptions ( array $manufs ) {
	$returnString = '';
	if ( $manufs ){
		foreach ( $manufs as $manuf ) {
			$returnString .= "<option value='$manuf->manuId'>$manuf->manuName</option>";
		}
	} else { $returnString = "<script>alert('TecDoc ei vastaa.');</script>"; }

	return $returnString;
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

        return $db->query($query, [$product->articleNo, $product->brandNo], FETCH_ALL, PDO::FETCH_OBJ);
    }


	$catalog_products = $not_available_catalog_products = $not_in_catalog = array();
	$ids = $articleIds = array();

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
$manufs = getManufacturers();
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
	$filtered_product_arrays = filter_catalog_products( $db, $products, $etuliite );
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
    <title>Tuotehaku</title>
</head>
<body>
<?php require 'header.php'?>
<main class="main_body_container">
	<section class="flex_row">
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
                    <label for="exact">Hakutyyppi:</label>
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
		<div class="ajoneuvomallihaku">
			Ajoneuvomallilla haku:<br>
			<form action="tuotehaku.php" method="get" id="ajoneuvomallihaku">
				<select id="manufacturer" name="manuf" title="Valmistaja">
					<option value="">-- Valmistaja --</option>
					<?= printManufSelectOptions($manufs) ?>
				</select><br>
				<select id="model" name="model" disabled="disabled" title="Auton malli">
					<option value="">-- Malli --</option>
				</select><br>
				<select id="car" name="car" disabled="disabled" title="Auto">
					<option value="">-- Tyyppi --</option>
				</select><br>
				<select id="osaTyyppi" name="osat" disabled="disabled" title="Osastyyppi">
					<option value="">-- Tuoteryhmä --</option>
				</select><br>
				<select id="osat_alalaji" name="osat_alalaji" disabled="disabled" title="Tyypin alalaji">
					<option value="">-- Tuoteryhmä --</option>
				</select>
				<br>
				<input type="submit" class="nappi" value="HAE" id="ajoneuvohaku">
			</form>
		</div>
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
				<?php if (is_admin()) : ?>
					<th class="number">Ostohinta ALV0%</th>
				<?php endif;?>
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
					<?php if (is_admin()) : ?>
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
				<?php if (is_admin()) : ?>
					<th class="number">Ostohinta ALV0%</th>
				<?php endif;?>
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
					<?php if (is_admin()) : ?>
						<td class="number"><?=format_euros($product->sisaanostohinta)?></td>
					<?php endif;?>
					<td id="tuote_ostopyynto_<?=$product->id?>">
						<button onClick="ostopyynnon_varmistus(<?=$product->id?>);">
							<i class="material-icons">info</i></button>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
		<?php endif; //if $not_available catalog products

		if ( $not_in_catalog) : //Tulokset (ei katalogissa)?>
		<h2>Tilaustuotteet: (<?=count($not_in_catalog)?>)</h2>
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
					<td><button id="tuote_hnktpyynto_<?=$product->articleId?>"
								onClick="hankintapyynnon_varmistus(
									'<?=$product->articleNo?>', '<?=$product->brandName?>',
									'<?=$product->articleId?>');">
						<i class="material-icons">help_outline</i></button></td>
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

<!-- Tuoteikkuna Modal -->
<div class="modal fade" id="myModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
	<div class="modal-dialog" role="document" style="top:50px;">
		<div class="modal-content">
			<div class="modal-header" style="height: 35px;">
				<button type="button" class="close" style="display: inline-block;" data-dismiss="modal" aria-label="Close">
					<span aria-hidden="true">&times;</span></button>
				<ul class="nav nav-pills" id="modalnav" style="position:relative; top:-20px; max-width: 450px;">
					<li class="active"><a data-toggle="tab" href="#menu1" id="maintab">Tuote</a></li>
					<li><a data-toggle="tab" href="#menu2">Kuvat</a></li>
					<li><a data-toggle="tab" href="#menu3">Vertailunumerot</a></li>
					<li><a data-toggle="tab" href="#menu4">Autot</a></li>
				</ul>
			</div>

			<div class="modal-body" style="margin-top:-20px;">
				<div class="tab-content">
					<div id="menu1" class="tab-pane fade in active"></div>
					<div id="menu2" class="tab-pane fade text-center"></div>
					<div id="menu3" class="tab-pane fade"></div>
					<div id="menu4" class="tab-pane fade">
						<br><div id="dd" class="car_dropdown"></div> <!-- Dropdown -->
					</div>
				</div>
			</div>

		</div>
	</div>
</div>
<!-- Spinning kuvake ladattaessa -->
<div id="cover"></div>

<!-- Jos mietit mitä nuo kaksi juttua tuossa alhaalla tekee: ensimmäinen poistaa valitukset jokaisesta
 		tecdocin metodista; toinen poistaa jokaisen varoituksen siitä kun asettaa parametrin arvon
 		heti funktion alussa. //TODO: Pitäisikö tämä korjata? -->
<!--suppress JSUnresolvedVariable, AssignmentToFunctionParameterJS -->
<script type="text/javascript">
	var TECDOC_MANDATOR = <?= json_encode(TECDOC_PROVIDER); ?>;
	//	var TECDOC_DEBUG = <?//= json_encode(TECDOC_DEBUG); ?>//;
	var TECDOC_COUNTRY = <?= json_encode(TECDOC_COUNTRY); ?>;
	var TECDOC_LANGUAGE = <?= json_encode(TECDOC_LANGUAGE); ?>;
	var TECDOC_THUMB_URL = <?= json_encode(TECDOC_THUMB_URL); ?>;

	//hakee tecdocista automallit annetun valmistaja id:n perusteella
	function getModelSeries( manufacturerID ) {
		var functionName = "getModelSeries";
		var params = {
			"favouredList" : 1,
			"linkingTargetType" : 'P',
			"manuId" : manufacturerID,
			"country" : TECDOC_COUNTRY,
			"lang" : TECDOC_LANGUAGE,
			"provider" : TECDOC_MANDATOR
		};
		params = toJSON(params);
		tecdocToCatPort[functionName] (params, updateModelList);
	}

	//hakee autojen id:t valmistajan ja mallin perusteella
	function getVehicleIdsByCriteria( manufacturerID, modelID ) {
		var functionName = "getVehicleIdsByCriteria";
		var params = {
			"carType" : "P",
			"favouredList": 1,
			"manuId" : manufacturerID,
			"modId" : modelID,
			"countriesCarSelection" : TECDOC_COUNTRY,
			"lang" : TECDOC_LANGUAGE,
			"provider" : TECDOC_MANDATOR
		};
		params = toJSON( params );
		tecdocToCatPort[functionName] ( params, getVehicleByIds3 );
	}

	//hakee lisätietoa autoista id:n perusteella
	function getVehicleByIds3( response ) {
		var params, i;
		var functionName = "getVehicleByIds3";
		var ids = [], IDarray = [];

		for ( i = 0; i < response.data.array.length; i++ ) {
			ids.push(response.data.array[i].carId);
		}

		//pystyy vastaanottamaan max 25 id:tä
		while( ids.length > 0 ) {
			if( ids.length >= 25 ){
				IDarray = ids.slice(0,25);
				ids.splice(0, 25);
			} else {
				IDarray = ids.slice(0, ids.length);
				ids.splice(0, ids.length);
			}

			params = {
				"favouredList": 1,
				"carIds" : { "array" : IDarray},
				"articleCountry" : TECDOC_COUNTRY,
				"countriesCarSelection" : TECDOC_COUNTRY,
				"country" : TECDOC_COUNTRY,
				"lang" : TECDOC_LANGUAGE,
				"provider" : TECDOC_MANDATOR
			};
			params = toJSON(params);
			tecdocToCatPort[functionName] (params, updateCarList);
		}
	}

	//hakee autoon linkitetyt osatyypit
	function getPartTypes( carID ) {
		var functionName = "getChildNodesAllLinkingTarget2";
		var params = {
			"linked" : true,
			"linkingTargetId" : carID,
			"linkingTargetType" : "P",
			"articleCountry" : TECDOC_COUNTRY,
			"lang" : TECDOC_LANGUAGE,
			"provider" : TECDOC_MANDATOR,
			"childNodes" : false
		};
		params = toJSON( params );
		tecdocToCatPort[functionName] ( params, updatePartTypeList );
	}

	//hakee osatyypin alalajit (kuten jarrut -> jarrulevyt)
	function getChildNodes( carID, parentNodeID ) {
		var functionName = "getChildNodesAllLinkingTarget2";
		var params = {
			"linked" : true,
			"linkingTargetId" : carID,
			"linkingTargetType" : "P",
			"articleCountry" : TECDOC_COUNTRY,
			"lang" : TECDOC_LANGUAGE,
			"provider" : TECDOC_MANDATOR,
			"parentNodeId" : parentNodeID,
			"childNodes" : false
		};
		params = toJSON(params);
		tecdocToCatPort[functionName] (params, updatePartSubTypeList);
	}

	//haetaan tuotteen tarkemmat tiedot
	function getDirectArticlesByIds6( ids ) {
		var functionName = "getDirectArticlesByIds6";
		var params = {
			"articleCountry" : TECDOC_COUNTRY,
			"lang" : TECDOC_LANGUAGE,
			"provider" : TECDOC_MANDATOR,
			"basicData" : true,
			"articleId" : {"array" : ids},
			"thumbnails" : true,
			"attributs" : true,
			"eanNumbers" : true,
			"oeNumbers" : true,
			"documents" : true
		};
		params = toJSON(params);
		tecdocToCatPort[functionName] (params, addProductInfoOnModal);
	}

	// Create JSON String and put a blank after every ',':
	//Muuttaa tecdociin lähetettävän pyynnön JSON-muotoon
	function toJSON( obj ) {
		return JSON.stringify(obj).replace(/,/g,", ");
	}

	// Callback function to do something with the response:
	// Päivittää alasvetolistaan uudet tiedot
	function updateModelList( response ) {
		var model, text, yearTo, i, modelList;
		response = response.data;

		//uudet tiedot listaan
		modelList = document.getElementById("model");


		if (response.array){
			for (i = 0; i < response.array.length; i++) {
				yearTo = response.array[i].yearOfConstrTo;
				if(!yearTo) {
					yearTo = "";
				} else {
					yearTo = addSlash(yearTo);
				}


				text = response.array[i].modelname
					+ "\xa0\xa0\xa0\xa0\xa0\xa0"
					+ "Year: " + addSlash(response.array[i].yearOfConstrFrom)
					+ " -> " + yearTo;

				model = new Option(text, response.array[i].modelId);
				modelList.options.add(model);
			}
		}
		$("#model").removeAttr('disabled');
	}

	// Callback function to do something with the response:
	// Päivittää alasvetolistaan uudet tiedot
	function updateCarList( response ) {
		var car, text, yearTo, i, carList;
		response = response.data;

		//uudet tiedot listaan
		carList = document.getElementById("car");

		if (response.array){
			for (i = 0; i < response.array.length; i++) {
				yearTo = response.array[i].vehicleDetails.yearOfConstrTo;
				if(!yearTo){
					yearTo = "";
				} else {
					yearTo = addSlash(yearTo);
				}
				text = response.array[i].vehicleDetails.typeName
					+ "\xa0\xa0\xa0\xa0\xa0\xa0"
					+ "Year: " + addSlash(response.array[i].vehicleDetails.yearOfConstrFrom)
					+ " -> " + yearTo
					+ "\xa0\xa0\xa0\xa0\xa0\xa0"
					+ response.array[i].vehicleDetails.powerKwFrom + "KW"
					+ " (" +response.array[i].vehicleDetails.powerHpFrom + "hp)";

				car = new Option(text, response.array[i].carId);
				carList.options.add(car);
			}
		}
		$('#car').removeAttr('disabled');
	}

	// Päivittää alasvetolistaan uudet tiedot
	function updatePartTypeList( response ) {
		var partType, i, partTypeList;
		response = response.data;

		//uudet tiedot listaan
		partTypeList = document.getElementById("osaTyyppi");
		if (response.array){
			for (i = 0; i < response.array.length; i++) {
				partType = new Option(response.array[i].assemblyGroupName, response.array[i].assemblyGroupNodeId);
				partTypeList.options.add(partType);
			}
		}

		$('#osaTyyppi').removeAttr('disabled');
	}

	// Päivittää alasvetolistaan uudet tiedot
	function updatePartSubTypeList( response ) {
		var subPartType, i, subPartTypeList;
		response = response.data;

		//uudet tiedot listaan
		subPartTypeList = document.getElementById("osat_alalaji");
		if (response.array){
			for (i = 0; i < response.array.length; i++) {
				subPartType = new Option(response.array[i].assemblyGroupName, response.array[i].assemblyGroupNodeId);
				subPartTypeList.options.add(subPartType);
			}
		}

		$('#osat_alalaji').removeAttr('disabled');
	}

	//Näytetään tuotteen tarkemmat tiedot Modal-ikkunassa
	function addProductInfoOnModal( response ) {

		function oesToHTML( array ) {
			var i;
			var result = "";
			if (array.length !== 0) {
				array = array.array;
				result = "" +
					'<div style="display:inline-block; width:50%;">' +
					'	<table style="margin-left:auto; margin-right:auto;">' +
					'		<th colspan="2" class="text-center">OE</th>';
				for ( i=0; i<array.length; i++ ) {
					result += "<tr>";
					result += "" +
						"<td style='font-size:14px;'>"+array[i].brandName+"</td>" +
						"<td style='font-size:14px;'><a href='tuotehaku.php?haku="+array[i].oeNumber+"&numerotyyppi=oe&exact=on' style='color:black;'>"+array[i].oeNumber+"</a></td>";
					result += "</tr>";
				}
				result += "</table>";
			}

			return result;
		}

		//Tehdään peräkkäinen html-muotoinen lista, jossa kaikki löytyneet kuvat peräkkäin
		function imgsToHTML( response ) {
			var i, img, thumb_id;
			var imgs = "<img src='img/ei-kuvaa.png' class='no-image' />";
			if ( response.articleThumbnails.length !== 0 ) {
				imgs = "";
				for ( i=0; i<response.articleThumbnails.array.length; i++ ) {
					thumb_id = response.articleThumbnails.array[i].thumbDocId;
					img = TECDOC_THUMB_URL + thumb_id + '/0/';
					imgs += '<img src=' + img + ' border="1" class="tuote_img kuva" /><br>';
				}
			}

			return imgs;
		}

		//Tehdään html-muotoinen listaus tuotteen infoista
		function infosToHTML( response ) {
			var i;
			var infos = "";

			//saatavuustiedot
			if ( response.directArticle.articleState != 1 ) {
				infos += "<span style='color:red;'>" + response.directArticle.articleStateName + "</span><br>";
			}
			//pakkaustiedot
			if ( typeof response.directArticle.packingUnit != 'undefined' ) {
				infos += "Pakkauksia: " + response.directArticle.packingUnit + "<br>";
			}
			if ( typeof response.directArticle.quantityPerPackingUnit != 'undefined' ) {
				infos += "Kpl/pakkaus: " + response.directArticle.quantityPerPackingUnit + "<br>";
			}

			infos += "<br>";

			//infot
			if (response.articleAttributes != "") {
				for ( i=0; i < response.articleAttributes.array.length; i++ ) {
					if (typeof response.articleAttributes.array[i].attrName != 'undefined') {
						infos += response.articleAttributes.array[i].attrName;
					}
					if (typeof response.articleAttributes.array[i].attrValue != 'undefined') {
						infos += ": " + response.articleAttributes.array[i].attrValue + " ";
					}
					if (typeof response.articleAttributes.array[i].attrUnit != 'undefined') {
						infos += response.articleAttributes.array[i].attrUnit;
					}
					infos += "<br>";
				}
			}

			return infos;
		}

		//Tehdään dokumenttien latauslinkit, jos olemassa
		function getDocuments( response ) {
			var docTypeName, docName, doc, i;
			var documentlink = "";

			if ( response.articleDocuments != "" ) {
				for ( i = 0; i < response.articleDocuments.array.length; i++ ) {
					//Dokumentit
					if ( response.articleDocuments.array[i].docTypeName != "Valokuva" && response.articleDocuments.array[i].docTypeName != "Kuva" ) {
						doc = TECDOC_THUMB_URL + response.articleDocuments.array[i].docId;
						docName = response.articleDocuments.array[i].docFileName;
						docTypeName = response.articleDocuments.array[i].docTypeName;

						documentlink += '<img src="img/pdficon.png" style="margin-right:5px;margin-bottom:7px;">' +
							'<a href="' + doc + '" download="'+docName+'" id="asennusohje">' +
							'' + docTypeName + ' (PDF)</a><br>';
					}
				}
			}

			return documentlink;
		}

		function getComparableNumber( articleNumber ) {
			var functionName = "getArticleDirectSearchAllNumbersWithState";
			var params = {
				"articleCountry" : TECDOC_COUNTRY,
				"lang" : TECDOC_LANGUAGE,
				"provider" : TECDOC_MANDATOR,
				"articleNumber" : articleNumber,
				"numberType" : 3,
				"searchExact" : true
			};
			params = toJSON(params);
			tecdocToCatPort[functionName] (params, addComparableNumbersToModal);
		}

		//Lisätään vertailunumerot modaliin
		function addComparableNumbersToModal( response ) {

			// Any better now? ;D
			// :: slow_clap
			var i, comparableNumbers;

			//Luodaan haetuista vertailunumeroista html-muotoinen taulu
			if ( response.data != "" ) {
				response = response.data.array;
			}
			//luodaan taulu ja lisätään siihen tuote, jota on haettu
			//(Tätä tuotetta ei palauteta vertailunumerojen mukana)
			comparableNumbers = "<div style='display:inline-block; width:49%; vertical-align:top;'>" +
				"<table style='margin-left:auto; margin-right:auto;'>" +
				"<th colspan='2' class='text-center'>Vertailunumerot</th>" +
				"<tr><td style='font-size:14px;'>"+brand+"</td>" +
				"<td style='font-size:14px;'><a href='tuotehaku.php?haku="+articleNo+"&numerotyyppi=comparable&exact=on' style='color:black;'>"+articleNo+"</a></td></tr>";

			if ( response.length !== 0 ) {
				for ( i=0; i<response.length; i++ ) {
					comparableNumbers += "<tr>";
					comparableNumbers += "<td style='font-size:14px;'>"+response[i].brandName+"</td>" +
						"<td style='font-size:14px;'><a href='tuotehaku.php?haku="+response[i].articleNo+"&numerotyyppi=comparable&exact=on' style='color:black;'>"
						+response[i].articleNo+"</a></td>";
					comparableNumbers += "</tr>";
				}
				comparableNumbers += "</table>";
			}


			//lisätään modaliin
			$("#menu3").append('\ ' +comparableNumbers+ '\ ');
		}



		function getLinkedManufacturers(articleId){
			var functionName = "getArticleLinkedAllLinkingTargetManufacturer";
			var params = {
				"articleCountry" : TECDOC_COUNTRY,
				"provider" : TECDOC_MANDATOR,
				"articleId" : articleId,
				"linkingTargetType" : "P"
			};
			params = toJSON(params);
			tecdocToCatPort[functionName] (params, function (response){
				var i;
				for(i=0; i<response.data.array.length; i++) {
					$(".car_dropdown").append("<span style='cursor:pointer; display:block;' onClick=\"showCars(this,"+articleId+")\" data-list-filled='false' data-manuId="+response.data.array[i].manuId+">"+response.data.array[i].manuName+"</span>" +
						"<div class='car_dropdown_content' id=manufacturer-"+response.data.array[i].manuId+"></div>");
				}
				//getLinkedVehicleIds(articleId);
			})
		}




		//avataan modal ja pysäytetään spinning icon
		function showModal() {
			//lopetetaan spinning iconin näyttäminen
			$('#cover').removeClass("loading");
			//avataan modal
			$("#myModal").modal({
				keyboard: true
			});
			//avataan aina "tuote" tabi ensin
			$('#maintab').tab('show');
		}


		var documents, infos, brand, articleNo, name, OEtable, imgs, articleId, display_img, display_img_id, img;
		response = response.data.array[0];
		articleId = response.directArticle.articleId;

		//Luodaan kaikki html elementit valmiiksi, joita käytetään Modal ikkunassa
		imgs = imgsToHTML(response);
		OEtable = oesToHTML(response.oenNumbers);
		name = response.directArticle.articleName;
		articleNo = response.directArticle.articleNo;
		brand = response.directArticle.brandName;
		infos = infosToHTML(response);
		documents = getDocuments(response);

		//display image
		display_img_id = "";
		if ( response.articleThumbnails.length === 0 ) {
			display_img = "img/ei-kuvaa.png";
			img = '<img src='+display_img+' border="1" id="display_img"/>'
		} else {
			display_img_id = response.articleThumbnails.array[0].thumbDocId;
			display_img = TECDOC_THUMB_URL + display_img_id + '/0/';
			img = '<img src='+display_img+' border="1" id="display_img" class="kuva"/>'
		}

		//Lisätään tuote modaliin sisältö
		$("#menu1").append('\
			<br>\
			<div class="row">\
			    <div style="display: inline-block;">\
			        '+img+'\
			    </div> \
			    <div>\
			        <div style="font-size: 20px;">\
			            <span style="font-weight:bold;">'+name+'</span><br>'+articleNo+'<br>'+brand+'<br> \
			        </div>\
			        <div style="font-size: 18px;">\
			            <br>'+infos+'<br><br>'+documents+' \
			        </div>\
			    </div>\
			</div>\
		');
		$("#menu2").append('\
				<br> \
				'+imgs+' \
				</div> \
			');

		$("#menu3").append('\
			<br> \
			'+OEtable+' \
		');


		//Haetaan muiden valmistajien vastaavat tuotteet (vertailunumerot) ja lisätään modaliin
		getComparableNumber(articleNo);
		//Haetaan tuotteeseen linkitetyt autot ja lisätään modaliin
        getLinkedManufacturers(articleId);
		showModal(); //näytetään modal

	}


    function showCars(elmnt, articleId){
        //Haetaan autot, jos niitä ei ole vielä haettu
        if (elmnt.getAttribute("data-list-filled") == "false") {
            elmnt.setAttribute("data-list-filled", "true");
            $("#manufacturer-"+elmnt.getAttribute('data-manuId')).addClass("loader");
            getLinkedVehicleIds(articleId, elmnt.getAttribute("data-manuId"));
        }
        if ($("#manufacturer-"+elmnt.getAttribute('data-manuId')).css("display") == "none") {
            $("#manufacturer-"+elmnt.getAttribute('data-manuId')).css("display", "block");
        }
        else {
            $("#manufacturer-"+elmnt.getAttribute('data-manuId')).css("display", "none");
        }
    }
    //Haetaan linkitettyjen autojen ID:t
    function getLinkedVehicleIds( articleId, manuId ) {
        var functionName = "getArticleLinkedAllLinkingTarget3";
        var params = {
            "articleCountry" : TECDOC_COUNTRY,
            "lang" : TECDOC_LANGUAGE,
            "provider" : TECDOC_MANDATOR,
            "articleId" : articleId,
            "linkingTargetManuId" : manuId,
            "linkingTargetType" : "P"
        };
        params = toJSON(params);
        tecdocToCatPort[functionName] (params, function (response){
            var pair, i;
			var articleIdPairs = [];
            if ( response.data != "" ) {
                response = response.data.array[0];
                for (i = 0; i < response.articleLinkages.array.length; i++) {
                    pair = {
                        "articleLinkId" : response.articleLinkages.array[i].articleLinkId,
                        "linkingTargetId" : response.articleLinkages.array[i].linkingTargetId
                    };
                    articleIdPairs.push(pair);
                    if ( articleIdPairs.length == 25 ) {
                        getLinkedVehicleInfos(articleId, articleIdPairs);
                        articleIdPairs = [];
                    }
                }
            }
            getLinkedVehicleInfos(articleId, articleIdPairs);
        });
    }

    //Haetaan linkitettyjen autojen tiedot
    function getLinkedVehicleInfos( articleId, articleIdPairs ) {
        var functionName = "getArticleLinkedAllLinkingTargetsByIds3";
        var params = {
            "articleCountry" : TECDOC_COUNTRY,
            "lang" : TECDOC_LANGUAGE,
            "provider" : TECDOC_MANDATOR,
            "articleId" : articleId,
            "linkingTargetType" : "P",
            "linkedArticlePairs" : {
                "array" : articleIdPairs
            }
        };
        params = toJSON(params);
        tecdocToCatPort[functionName] (params, addLinkedVehiclesToModal);
    }

    function addLinkedVehiclesToModal(response) {
        $("#manufacturer-"+response.data.array[0].linkedVehicles.array[0].manuId).removeClass("loader");
        for (var i=0; i<response.data.array.length ; i++) {
            var yearTo = "";
            if (typeof response.data.array[i].linkedVehicles.array[0].yearOfConstructionTo != 'undefined') {
                yearTo = addSlash(response.data.array[i].linkedVehicles.array[0].yearOfConstructionTo);
            }
            $("#manufacturer-" + response.data.array[i].linkedVehicles.array[0].manuId).append("<li style='font-size: 14px;'>" +
                response.data.array[i].linkedVehicles.array[0].modelDesc + " " +
                response.data.array[i].linkedVehicles.array[0].carDesc + " " +
                addSlash(response.data.array[i].linkedVehicles.array[0].yearOfConstructionFrom + "-" +
                    yearTo + "</li>"));
        }
    }

	/**
	 * Apufunktio, jonka avulla voidaan muotoilla ajoneuvomallihaun vuosiluvut parempaan muotoon
	 * @param text
	 * @returns {string}
	 */
	function addSlash(text) {
		text = String(text);
		return (text.substr(0, 4) + "/" + text.substr(4));
	}

	/**
	 * Tallentaa ostospyynnön tietokantaan
	 * @param {int} product_id - Halutun tuotteen ID
	 */
	function ostopyynnon_varmistus( product_id ) {
		var vahvistus = confirm( "Olisin tilannut tuotteen, jos sitä olisi ollut saatavilla?");
		if ( vahvistus ) {
			$.post(
				"ajax_requests.php",
				{ tuote_ostopyynto: product_id },
				function( data ) {
					if ( data === true ) {
						$("#tuote_ostopyynto_" + product_id)
							.css("background-color","green")
							.addClass("disabled");
					} else {
						//TODO: Väritä punaiseksi. Tosin luulen, että se aina palauttaa true.
					}
				}
			);
		}
	}

	/**
	 * Tallentaa hankintapyynnön tietokantaan (Käyttäjältä varmistuksen kysymisen jälkeen).
	 * @param {string} articleNo
	 * @param {string} brandNo
	 * @param {string} articleId
	 */ //TODO: Selitys-tekstikenttä, ja Käykö korvaava -checkbox. jQuery UI?
	function hankintapyynnon_varmistus( articleNo, brandNo, articleId ) {
		var vahvistus, selitys, korvaava_okey;
		vahvistus = confirm( "Olisin tilannut tuotteen, jos sitä olisi ollut saatavilla?");
		if ( vahvistus ) {
			korvaava_okey = confirm( "Kelpaako korvaava tuote?" );
			selitys = prompt( "Syy, miksi olisit halunnut tämä tuotteen? (Vapaaehtoinen)" );
			$.post("ajax_requests.php",
				{	tuote_hankintapyynto: true,
					articleNo: articleNo,
					brandNo: brandNo,
					selitys: selitys,
					korvaava_okey: korvaava_okey },
				function( data ) {
					if ( data === true ) {
						console.log( articleNo + " " + brandNo + " " + selitys + " " + korvaava_okey );
						$("#tuote_hnktpyynto_" + articleId)
							.css("background-color","green")
							.addClass("disabled");
					} else {
						//TODO: Väritä punaiseksi. Tosin luulen, että se aina palauttaa true.
					}
				}
			);
		}
		alert("Hankintapyyntö on tekeillä. Kiitos kärsivällisyydestäsi.");
	}

	/**
	 * Tämän pitäisi lisätä tuote ostoskoriin...
	 * @param product_id
	 */
	function addToShoppingCart( product_id ) {
		var kpl_maara = $("#maara_" + product_id).val();
		if ( kpl_maara > 0 ) {
			$.post("ajax_requests.php",
				{	ostoskori_toiminto: true,
					tuote_id: product_id,
					kpl_maara: kpl_maara },
				function( data ) {
					console.debug(data);
					if ( data.success === true ) {
						$("#head_cart_tuotteet").text(data.tuotteet_kpl);
						$("#head_cart_kpl").text(data.yhteensa_kpl);
					} else {
						alert("ERROR: Tuotteen lisääminen ei onnistunut.")
					}
				}
			);
		}
	}



	$(document).ready(function(){
		$("#manufacturer").on("change", function(){
			//kun painaa jotain automerkkiä->

			var manuList = document.getElementById("manufacturer");
			var selManu = parseInt(manuList.options[manuList.selectedIndex].value);

			//Poistetaan vanhat tiedot
			var modelList = document.getElementById("model");
			var carList = document.getElementById("car");
			var partTypeList = document.getElementById("osaTyyppi");
			var subPartTypeList = document.getElementById("osat_alalaji");

			while ( modelList.options.length - 1 ) {
				modelList.remove(1);
			}
			while ( carList.options.length - 1 ) {
				carList.remove(1);
			}
			while ( partTypeList.options.length - 1 ) {
				partTypeList.remove(1);
			}
			while ( subPartTypeList.options.length - 1 ) {
				subPartTypeList.remove(1);
			}

			//väliaikaisesti estetään modelin ja auton valinta
			$('#model').attr('disabled', 'disabled');
			$('#car').attr('disabled', 'disabled');
			$('#osaTyyppi').attr('disabled', 'disabled');
			$('#osat_alalaji').attr('disabled', 'disabled');
			if ( selManu > 0 ) {
				getModelSeries(selManu);
			}
		});//#manuf.onChange

		$("#model").on("change", function(){
			//kun painaa jotain automallia->
			var manuList = document.getElementById("manufacturer");
			var modelList = document.getElementById("model");
			var selManu = parseInt(manuList.options[manuList.selectedIndex].value);
			var selModel = parseInt(modelList.options[modelList.selectedIndex].value);
			var partTypeList = document.getElementById("osaTyyppi");
			var subPartTypeList = document.getElementById("osat_alalaji");

			//tyhjennetään autolista ja haetaan uudet autot
			var carList = document.getElementById("car");
			while (carList.options.length - 1) {
				carList.remove(1);
			}
			while (partTypeList.options.length - 1) {
				partTypeList.remove(1);
			}
			while (subPartTypeList.options.length - 1) {
				subPartTypeList.remove(1);
			}

			$('#car').attr('disabled', 'disabled');
			$('#osaTyyppi').attr('disabled', 'disabled');
			$('#osat_alalaji').attr('disabled', 'disabled');

			if (selModel > 0 ) {
				getVehicleIdsByCriteria(selManu, selModel);
			}
		});//#model.onChange

		$("#car").on("change", function(){
			//kun painaa jotain autoa->
			var carList = document.getElementById("car");

			var selCar = parseInt(carList.options[carList.selectedIndex].value);
			var subPartTypeList = document.getElementById("osat_alalaji");
			var partTypeList = document.getElementById("osaTyyppi");

			//tyhjennetään autolista ja haetaan uudet autot
			while (partTypeList.options.length - 1) {
				partTypeList.remove(1);
			}
			while (subPartTypeList.options.length - 1) {
				subPartTypeList.remove(1);
			}

			$('#osaTyyppi').attr('disabled', 'disabled');
			$('#osat_alalaji').attr('disabled', 'disabled');
			if (selCar > 0 ) {
				getPartTypes(selCar);
			}
		});//#car.onChange

		$("#osaTyyppi").on("change", function(){
			//kun painaa jotain osatyyppiä->
			var carList = document.getElementById("car");
			var partTypeList = document.getElementById("osaTyyppi");

			var selCar = parseInt(carList.options[carList.selectedIndex].value);
			var selPartType = parseInt(partTypeList.options[partTypeList.selectedIndex].value);
			var subPartTypeList = document.getElementById("osat_alalaji");

			//tyhjennetään osatyypilista
			while (subPartTypeList.options.length - 1) {
				subPartTypeList.remove(1);
			}

			$('#osat_alalaji').attr('disabled', 'disabled');
			if (selPartType > 0 ) {
				getChildNodes(selCar, selPartType);
			}
		});//#osaTyyppi.onChange

		//annetaan hakea vain jos kaikki tarvittavat tiedot on annettu
		$("#ajoneuvomallihaku").submit(function(e) {
			if (document.getElementById("osat_alalaji").selectedIndex !== 0) {
				//sallitaan formin lähetys
				return true;
			} else {
				e.preventDefault();
				alert("Täytä kaikki kohdat ennen hakua! tms.....");
				return false;
			}
		}); //#ajoneuvomallihaku.submit

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
				$('#cover').addClass("loading");
				//haetaan tuotteen tiedot tecdocista
				getDirectArticlesByIds6(articleId);
			});

		$('#myModal').on('hidden.bs.modal', function () {
			$( "#menu1" ).empty();
			$( "#menu2" ).empty();
			$( "#menu3" ).empty();
			$( "#dd" ).empty();
		});

		//Käytetään eri muotoilua, koska dynaaminen content
		$(document.body)
			.on('mouseover', '#asennusohje', function(){
				$(this).css("text-decoration", "underline"); })
			.on('mouseout', '#asennusohje', function(){
				$(this).css("text-decoration", "none");
			});

		//avaa tuotteen kuvan isona uuteen ikkunaan
		$(document.body).on('click', '.kuva', function(){
			var src = this.src;
			var w = this.naturalWidth;
			var h = this.naturalHeight;

			var left = (screen.width/2)-(w/2);
			var top = (screen.height/2)-(h/2);
			//TODO: will this change work? myWindow =
			window.open(src, src, "width="+w+",height="+h+",left="+left+",top="+top+"");
		}); //close click
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
