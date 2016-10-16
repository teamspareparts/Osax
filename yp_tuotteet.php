<?php
require '_start.php'; global $db, $user, $cart;
require 'tecdoc_asetukset.php';
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
 * Hakee ja palauttaa arrayn aktivoiduista tuotteista.
 * //TODO Holy hell. Kaikki tuotteet?
 * @param DByhteys $db
 * @return array array
 */
function get_products_in_catalog( DByhteys $db ) {
	$sql = "SELECT id, articleNo, brandNo, hinta_ilman_ALV, ALV_kanta, varastosaldo, minimimyyntiera, alennusera_kpl, 
				(alennusera_prosentti * 100) AS alennusera_prosentti,
				(hinta_ilman_alv * (1+ALV_kanta.prosentti)) AS hinta
			FROM tuote
			JOIN ALV_kanta
				ON tuote.ALV_kanta = ALV_kanta.kanta 
			WHERE aktiivinen = 1";
	$result = $db->query( $sql, NULL, FETCH_ALL );

	$products = array();
	if ( $result ) {
		foreach ( $result as $tuote ) {
			$products[] = $tuote;
		}
		merge_catalog_with_tecdoc($products, true);
	}

	return $products;
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

if ( !$user->isAdmin() ) {
	header("Location:etusivu.php"); exit();
}

$haku = FALSE;
$manufs = getManufacturers();
$feedback = "";

if ( !empty($_POST['lisaa']) ) {
	$array = [
		$_POST['lisaa'],
		$_POST['brandNo'],
		$_POST['hinta'],
		$_POST['alv_lista'],
		$_POST['varastosaldo'],
		$_POST['minimimyyntiera'],
		$_POST['alennusera_kpl'],
		($_POST['alennusera_prosentti'] / 100),
	];
	if ( add_product_to_catalog( $db, $array ) ) {
		$feedback = '<p class="success">Tuote lisätty!</p>';
	} else { $feedback = '<p class="error">Tuotteen lisäys epäonnistui!</p>'; }

} elseif ( !empty($_GET['poista']) ) {
	if ( remove_product_from_catalog( $db, $_GET['poista'] ) ) {
		$feedback = '<p class="success">Tuote poistettu!</p>';
	} else { $feedback = '<p class="error">Tuotteen poisto epäonnistui!</p>'; }

} elseif ( !empty($_POST['muokkaa']) ) {
	$array = [
		$_POST['muokkaa'],
		$_POST['hinta'],
		$_POST['alv_lista'],
		$_POST['varastosaldo'],
		$_POST['minimimyyntiera'],
		$_POST['alennusera_kpl'],
		($_POST['alennusera_prosentti'] / 100),
	];
	if ( modify_product_in_catalog( $db, $array ) ) {
		echo '<p class="success">Tuotteen tiedot päivitetty!</p>';
	} else {
		echo '<p class="error">Tuotteen muokkaus epäonnistui!</p>';
	}
}

if ( !empty($_GET['haku']) ) {
	$haku = TRUE; // Hakutulosten tulostamista varten. Ei tarvitse joka kerta tarkistaa isset()

	//poistetaan duplikaatit
	$tecdoc_ids = array();
	$products = array();
	$articles = getArticleDirectSearchAllNumbersWithState( $_GET['haku'], false );
	foreach ( $articles as $product ) {
		if ( !in_array($product->articleId, $tecdoc_ids) ) {
			$tecdoc_ids[] = $product->articleId;
			$products[] = $product;
		}
	}
	merge_products_with_optional_data($products);
}

if ( !empty($_GET['manuf']) ) {
	$haku = TRUE; // Hakutulosten tulostamista varten. Ei tarvitse joka kerta tarkistaa isset()
	$selectCar = $_GET["car"];
	$selectPartType = $_GET["osat_alalaji"];

	$articleIDs = array();
	$products = array();
	$articles = getArticleIdsWithState( $selectCar, $selectPartType );
	merge_products_with_optional_data( $articles );

	//poistetaan duplikaatit
	foreach ($articles as $article){
		if ( !in_array($article->articleNo, $articleIDs) ) {
			$articleIDs[] = $article->articleId;
			$products[] = $article;
		}
	}
}
?>

<!DOCTYPE html>
<html lang="fi">
<head>
	<meta charset="UTF-8">
	<link rel="stylesheet" href="css/styles.css">
	<link rel="stylesheet" href="css/jsmodal-light.css">

	<link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
	<!-- https://design.google.com/icons/ -->

	<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.0/jquery.min.js"></script>
	<script src="http://webservicepilot.tecdoc.net/pegasus-3-0/services/TecdocToCatDLB.jsonEndpoint?js"></script>
	<script src="js/jsmodal-1.0d.min.js"></script>
	<title>Tuotteet</title>
</head>

<body>
<?php require "header.php"; ?>
<main class="main_body_container">
	<section class="flex_row">
		<div class="tuotekoodihaku">
			Tuotenumerolla haku:<br>
			<form action="yp_tuotteet.php" method="get" class="haku">
				<input id="search" type="text" name="haku" placeholder="Tuotenumero"><br>
				<input class="nappi" type="submit" value="Hae">
			</form>
		</div>
		<div class="ajoneuvomallihaku">
			Ajoneuvomallilla haku:<br>
			<form action="yp_tuotteet.php" method="get" id="ajoneuvomallihaku">
				<select id="manufacturer" name="manuf" title="Valmistaja">
					<option value="">-- Valmistaja --</option>
					<?= printManufSelectOptions( $manufs ) ?>
				</select><br>
				<select id="model" name="model" disabled="disabled" title="Auton malli">
					<option value="">-- Malli --</option>
				</select><br>
				<select id="car" name="car" disabled="disabled" title="Auto">
					<option value="">-- Auto --</option>
				</select><br>
				<select id="osaTyyppi" name="osat" disabled="disabled" title="Osastyyppi">
					<option value="">-- Osat --</option>
				</select><br>
				<select id="osat_alalaji" name="osat_alalaji" disabled="disabled" title="Tyypin alalaji">
					<option value="">-- Osien alalaji --</option>
				</select>
				<br>
				<input type="submit" class="nappi" value="HAE" id="ajoneuvohaku">
			</form>
		</div>
	</section>

	<section class="hakutulokset">
		<?php if ( $haku ) : ?>
			<h4>Löydetyt tuotteet: <?= count($products) ?></h4>
			<?php if ( $products) : ?>
				<table>
					<thead>
					<tr><th>Kuva</th>
						<th>Tuotenumero</th>
						<th>Tuote</th>
						<th>Info</th>
						<th></th> <!-- TODO: Add a gear icon -->
					</tr>
					</thead>
					<tbody>
					<?php foreach ( $products as $product ) : ?>
						<tr data-val="<?=$product->articleId?>">
							<td class="clickable thumb">
								<img src="<?=$product->thumburl?>" alt="<?=$product->articleName?>"></td>
							<td class="clickable"><?=$product->articleNo?></td>
							<td class="clickable"><?=$product->brandName?><br><?=$product->articleName?></td>
							<td class="clickable">
								<?php foreach ( $product->infos as $info ) :
									echo (!empty($info->attrName) ? $info->attrName : "") . " " .
										(!empty($info->attrValue) ? $info->attrValue : "") .
										(!empty($info->attrUnit) ? $info->attrUnit : "") . "<br>";
								endforeach; ?>
							</td>
							<td class="toiminnot">
								<a class="nappi" href='javascript:void(0)'
								   onclick="showAddDialog('<?=$product->articleNo?>', <?=$product->brandNo?>)">Lisää</a></td>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>
		<?php endif; //if $haku?>
	</section>
</main>

<!-- Jos mietit mitä nuo kaksi juttua tuossa alhaalla tekee: ensimmäinen poistaa valitukset jokaisesta
 		tecdocin metodista; toinen poistaa jokaisen varoituksen siitä kun asettaa parametrin arvon
 		heti funktion alussa. -->
<!--suppress JSUnresolvedVariable, AssignmentToFunctionParameterJS -->
<script>
	var TECDOC_MANDATOR = <?= json_encode(TECDOC_PROVIDER); ?>;
	//	var TECDOC_DEBUG = <?= ''//json_encode(TECDOC_DEBUG); ?>//;
	var TECDOC_COUNTRY = <?= json_encode(TECDOC_COUNTRY); ?>;
	var TECDOC_LANGUAGE = <?= json_encode(TECDOC_LANGUAGE); ?>;

	/**
	 * Tuotteen lisäys valikoimaan.
	 * @param articleNo
	 * @param brandNo
	 */
	function showAddDialog( articleNo, brandNo ) {
		Modal.open( {
			content: '\
				<div class="dialogi-otsikko">Lisää tuote</div> \
				<form action="yp_tuotteet.php" name="lisayslomake" method="post"> \
					<label for="hinta">Hinta (ilman ALV):</label><span class="dialogi-kentta"><input class="eur" name="hinta" placeholder="0,00"> &euro;</span><br> \
					<label for="alv">ALV Verokanta:</label><span class="dialogi-kentta"> \
						<?= hae_kaikki_ALV_kannat_ja_lisaa_alasvetovalikko( $db ) ?> \
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
		Modal.open( {
			content: '\
				<div class="dialogi-otsikko">Muokkaa tuotetta</div> \
				<form action="yp_tuotteet.php" name="muokkauslomake" method="post"> \
					<label for="hinta">Hinta (ilman ALV):</label><span class="dialogi-kentta"><input class="eur" name="hinta" placeholder="0,00" value="' + price + '"> &euro;</span><br> \
					<label for="alv">ALV Verokanta:</label><span class="dialogi-kentta"> \
						<?= hae_kaikki_ALV_kannat_ja_lisaa_alasvetovalikko( $db ) ?> \
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

	/**
	 * Hakee tecdocista automallit annetun valmistaja id:n perusteella
	 * @param manufacturerID
	 */
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

	/**
	 * Hakee autojen id:t valmistajan ja mallin perusteella
	 * @param manufacturerID
	 * @param modelID
	 */
	function getVehicleIdsByCriteria(manufacturerID, modelID) {
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
		params = toJSON(params);
		tecdocToCatPort[functionName] (params, getVehicleByIds3);

	}

	/**
	 * Hakee lisätietoa autoista id:n perusteella
	 * @param response
	 */
	function getVehicleByIds3(response) {
		var i, IDarray, params;
		var functionName = "getVehicleByIds3";
		var ids = [];
		for(i = 0; i < response.data.array.length; i++) {
			ids.push(response.data.array[i].carId);
		}

		//pystyy vastaanottamaan max 25 id:tä
		while (ids.length > 0) {
			if (ids.length >= 25) {
				IDarray = ids.slice(0, 25);
				ids.splice(0, 25);
			} else {
				IDarray = ids.slice(0, ids.length);
				ids.splice(0, ids.length);
			}

			params = {
				"favouredList": 1,
				"carIds": {"array": IDarray},
				"articleCountry": TECDOC_COUNTRY,
				"countriesCarSelection": TECDOC_COUNTRY,
				"country": TECDOC_COUNTRY,
				"lang": TECDOC_LANGUAGE,
				"provider": TECDOC_MANDATOR
			};
			params = toJSON(params);
			tecdocToCatPort[functionName](params, updateCarList);
		}
	}

	/**
	 * Hakee autoon linkitetyt osatyypit
	 * @param carID
	 */
	function getPartTypes(carID) {
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
		tecdocToCatPort[functionName] (params, updatePartTypeList);
	}

	/**
	 * Hakee osatyypin alalajit (kuten jarrut -> jarrulevyt)
	 * @param carID
	 * @param parentNodeID
	 */
	function getChildNodes(carID, parentNodeID) {
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
		tecdocToCatPort[functionName] (params, updatePartSubTypeList);
	}

	/**
	 * Create JSON String and put a blank after every ',': Muuttaa tecdociin lähetettävän pyynnön JSON-muotoon
	 * @param obj
	 * @returns {string|XML|void}
	 */
	function toJSON(obj) {
		return JSON.stringify(obj).replace(/,/g,", ");
	}

	/**
	 * Callback function to do something with the response: Päivittää alasvetolistaan uudet tiedot
	 * @param response
	 */
	function updateModelList( response ) {
		var model;
		var text;
		var yearTo;
		var i;
		var modelList;
		response = response.data;

		//uudet tiedot listaan
		modelList = document.getElementById("model");

		if (response.array){
			for ( i=0; i < response.array.length; i++ ) {
				yearTo = response.array[i].yearOfConstrTo;
				if ( !yearTo ) {
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
		$('#model').removeAttr('disabled');
	}

	/**
	 * Callback function to do something with the response: Päivittää alasvetolistaan uudet tiedot
	 * @param response
	 */
	function updateCarList(response) {
		var carList, i, yearTo, text, car;
		response = response.data;

		//uudet tiedot listaan
		carList = document.getElementById("car");

		if ( response.array ){
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

	/**
	 * Päivittää alasvetolistaan uudet tiedot
	 * @param response
	 */
	function updatePartTypeList(response) {
		var partTypeList, i, partType;
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

	/**
	 * Päivittää alasvetolistaan uudet tiedot
	 * @param response
	 */
	function updatePartSubTypeList(response) {
		var subPartTypeList, i, subPartType;
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

	/**
	 * Apufunktio, jonka avulla voidaan muotoilla ajoneuvomallihaun vuosiluvut parempaan muotoon
	 * @param text
	 * @returns {string}
	 */
	function addSlash(text) {
		text = String(text);
		return (text.substr(0, 4) + "/" + text.substr(4));
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
			if (document.getElementById("osat_alalaji").selectedIndex != 0) {
				//sallitaan formin lähetys
				return true;
			}
			else {
				e.preventDefault();
				alert("Täytä kaikki kohdat ennen hakua!");
				return false;
			}
		});
	});
</script>

</body>
</html>
