<!DOCTYPE html>
<html lang="fi">
<head>
	<meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1">

	<link rel="stylesheet" href="css/styles.css">
	<link rel="stylesheet" href="css/bootstrap.css">
	<link rel="stylesheet" href="css/jsmodal-light.css">

<!--	<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0-alpha.3/css/bootstrap.min.css" integrity="sha384-MIwDKRSSImVFAZCVLtU0LMDdON6KVCrZHyVQQj6e8wIEJkW4tvwqXrbMIya1vriY" crossorigin="anonymous">-->

	<link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">

	<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.0/jquery.min.js"></script>
	<script src="https://ajax.googleapis.com/ajax/libs/jqueryui/1.11.4/jquery-ui.min.js"></script>

	<script src="http://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/js/bootstrap.min.js"></script>

<!--	<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0-alpha.3/js/bootstrap.min.js"-->
<!--			integrity="sha384-ux8v3A6CPtOTqOzMKiuo3d/DomGaaClxFYdCu2HPMBEkf6x2xiDyJ7gkXU0MWwaD"-->
<!--			crossorigin="anonymous"></script>-->

	<script src="http://webservicepilot.tecdoc.net/pegasus-3-0/services/TecdocToCatDLB.jsonEndpoint?js"></script>
	<script src="js/jsmodal-1.0d.min.js"></script>
	<title>Tuotehaku</title>

	<style type="text/css">
		.class #id tag {}

		/*div, section {*/
			/*border: 1px solid;*/
		/*}*/
		.tuotehaku_header {
			display: flex;
			flex-grow: 1;
			height: 35px;
		}
		.ostoskorilinkki {
			flex-grow: 1;
			font-size: 150%;
		}
		.start { text-align: start;	}
		.end { text-align: end; }

		.hakutyypit {
			display: flex;
			flex-direction: row;
		}
		.tuotekoodihaku, .ajoneuvomallihaku {
			padding: 0 20px 20px;
		}
	</style>
</head>
<body>

<?php require 'header.php';
//require 'tecdoc_asetukset.php';
require 'tecdoc.php';
require 'apufunktiot.php';
require 'tietokanta.php';
require 'ostoskori_lomake.php';

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
	} else $returnString = "<script type='text/javascript'>alert('TecDoc ei vastaa.');</script>";

	return $returnString;
}

/**
 * Palauttaa merkkijonona linkin ostoskoriin.
 * @return string <p> Linkki ostoskoriin, HTML:nä
 */
function printOstoskoriLinkki() {
	$cart_count = isset($_SESSION['cart'])
					? count($_SESSION['cart'])
					: 0;

	$cart_contents = ($cart_count !== 1)
					? "{$cart_count} tuotetta"
					: "1 tuote";

	$returnString = "<a href='ostoskori.php'>Ostoskori ({$cart_contents}) </a>";

	return $returnString;
}

/**
 * Suodattaa annetusta tuote-arraysta vain tuotteet, jotka löytyvät katalogista,
 * ja ovat aktivoitu tietokannassa.
 * Lopuksi lisää liittää TecDoc-tiedot tuotteisiin.
 *
 * @param DByhteys $db <p> Tietokantayhteys
 * @param array $products <p> Tuote-array, josta etsitään aktivoidut tuotteet.
 * @return array <p> Tuotteet, jotka löytyi catalogista aktivoituna..
 */
function filter_catalog_products ( DByhteys $db, array $products ) {
	$catalog_products = array();
	$ids = array();

	foreach ( $products as $product ) {
		$query = "	SELECT 	*, (hinta_ilman_alv * (1+ALV_kanta.prosentti)) AS hinta
					FROM 	tuote 
					JOIN 	ALV_kanta
						ON	tuote.ALV_kanta = ALV_kanta.kanta
					WHERE 	tuote.articleNo = ?
					 		AND tuote.brandNo = ?
					 		AND tuote.aktiivinen = 1 ";
		$row = $db->query( $query, [$product->articleNo, $product->brandNo], NULL, PDO::FETCH_OBJ );
		if ($row && !in_array($row->id, $ids) ) {
			$ids[] = $row->id;
			$row->articleId = $product->articleId;
			$row->articleName = $product->articleName;
			$row->brandName = $product->brandName;
			$catalog_products[] = $row;
		}
	}
	merge_catalog_with_tecdoc($catalog_products, false);

	return $catalog_products;
}

/**
 * @param $product
 * @return string <p> palauttaa kpl-kentän tai ostopyyntö-napin
 */
function laske_tuotesaldo_ja_tulosta_huomautus ( $product ) {
	return //Hyvin monimutkainen if-else-lauseke:
		($product->varastosaldo >= $product->minimimyyntiera || $product->varastosaldo === 0)
			? "<input id='maara_{$product->id}' name='maara_{$product->id}' class='maara' 
				type='number' value='0' min='0'></td>"
			: '<a href="javascript:void(0);" onClick="ostopyynnon_varmistus({$product->id});">
				<i class="material-icons">first_page</i></a>';
}

$manufs = getManufacturers();
$catalog_products = array();
if ( !empty($_POST['tuote_ostopyynto']) ) {
	$sql = 'INSERT
			INTO tuote_ostopyynto (tuote_id, kayttaja_id )
			VALUES ( ?, ? ) ';
	$db->query($sql, [ $_POST['tuote_ostopyynto'], $_SESSION['id'] ]);
}

if ( !empty($_GET['haku']) ) {
	$number = addslashes(str_replace(" ", "", $_GET['haku']));

	$products = getArticleDirectSearchAllNumbersWithState($number); // haetaan kaikki linkitetyt tuotteet

	//filtteröidään vain catalogi tuotteet ja liitetään lisätiedot
	$catalog_products = filter_catalog_products( $db, $products );
//	print_results($catalog_products);

//	$ids = array();
////	haetaan vielä kaikki tuotteet jotka eivät olleet valikoimassa
//	foreach ($catalog_products as $catalog_product) {
//		$ids[] = $catalog_product->articleId;
//	}
//	foreach ($products as $product){
//		if (in_array($product->articleId, $ids));
//	}
}

if(isset($_GET["manuf"])) {
	$selectCar = $_GET["car"];
	$selectPartType = $_GET["osat_alalaji"];

	$products = getArticleIdsWithState($selectCar, $selectPartType);

	global $db;

	$catalog_products = array();
	$ids = array();
	foreach ($products as $product) {
		$articleNo = str_replace(" ", "", $product->articleNo);
		$query = " SELECT 	*, (hinta_ilman_alv * (1+ALV_kanta.prosentti)) AS hinta
					FROM 	tuote 
					JOIN 	ALV_kanta
						ON	tuote.ALV_kanta = ALV_kanta.kanta
					WHERE 	tuote.articleNo = ?
					 		AND tuote.brandNo = ?
					 		AND tuote.aktiivinen = 1 ";
		$row = $db->query( $query, [$articleNo, $product->brandNo], NULL, PDO::FETCH_OBJ );
		if ( $row && !in_array($row->id, $ids) ) {
			$ids[] = $row->id;
			$row->articleId = $product->articleId;
			$row->articleName = $product->genericArticleName;
			$row->brandName = $product->brandName;
			$catalog_products[] = $row;
		}
	}
	merge_catalog_with_tecdoc($catalog_products, false);
//	print_results($catalog_products);
}
?>

<main class="main_body_container">
	<header class="tuotehaku_header">
		<span class="start small_note">Katsotaanpa huomaako kukaan jos vaihdan tuotehaun uuteen...</span>
		<span class="end ostoskorilinkki"><?= printOstoskoriLinkki() ?></span>
	</header>
	<section class="hakutyypit">
		<div class="tuotekoodihaku">
			Tuotenumerolla haku:<br>
			<form action="tuotehaku.php" method="get" class="haku">
				<input id="search" type="text" name="haku" placeholder="Tuotenumero"><br>
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

	<?= handle_shopping_cart_action() ?>

	<section class="hakutulokset">
		<h2>Tulokset:</h2>
		<table>
			<thead>
				<tr><th>Kuva</th>
					<th>Tuotenumero</th>
					<th>Tuote</th>
					<th>Info</th>
					<th style="text-align: right;">Saldo</th>
					<th style="text-align: right;">Hinta (sis. ALV)</th>
					<th>Kpl</th>
					<th></th>
				</tr>
			</thead>
			<tbody>
			<?php foreach ($catalog_products as $product) : ?>
				<tr data-val="<?=$product->articleId?>">
					<td class="clickable thumb">
						<img src="<?=$product->thumburl?>" alt="<?=$product->articleName?>"></td>
					<td class="clickable"><?=$product->articleNo?></td>
					<td class="clickable"><?=$product->brandName?><br><?=$product->articleName?></td>
					<td class="clickable">
						<?php foreach ( $product->infos as $info ) :
							echo (!empty($info->attrName) ? $info->attrName : "") .
								(!empty($info->attrValue) ? $info->attrValue : "") .
								(!empty($info->attrUnit) ? $info->attrUnit : "") . "<br>";
						endforeach; ?>
						</td>
					<td style="text-align: right;"><?=format_integer($product->varastosaldo)?></td>
					<td style="text-align: right;"><?=format_euros($product->hinta)?></td>
					<td style="padding-top: 0; padding-bottom: 0;">
						<?=laske_tuotesaldo_ja_tulosta_huomautus( $product )?></td>
					<td></td>
					<td class="toiminnot">
						<a class="nappi" href="javascript:void(0)" onclick="addToShoppingCart(<?=$product->id?>)">
							Osta</a></td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
	</section>
</main>


<form class="hidden" id="ostopyynto_form" action="#" method=post>
	<input type=hidden name="tuote_ostopyynto" value="" id="tuote_ostopyynto">
</form>

<!-- Tuoteikkuna Modal -->
<div class="modal fade" id="myModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
	<div class="modal-dialog" role="document" style="top:50px;">
		<div class="modal-content">
			<div class="modal-header" style="height: 35px;">
				<button type="button" class="close" style="display: inline-block;" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
				<ul class="nav nav-pills" id="modalnav" style="position:relative; top:-20px; max-width: 300px;">
					<li class="active"><a data-toggle="tab" href="#menu1" id="maintab">Tuote</a></li>
					<li><a data-toggle="tab" href="#menu2">Kuvat</a></li>
					<li><a data-toggle="tab" href="#menu3">OE</a></li>
				</ul>
			</div>



			<div class="modal-body" style="margin-top:-20px;">
				<div class="tab-content">
					<div id="menu1" class="tab-pane fade in active"></div>
					<div id="menu2" class="tab-pane fade text-center"></div>
					<div id="menu3" class="tab-pane fade"></div>
				</div>
			</div>

		</div>
	</div>
</div>
<!-- Spinning kuvake ladattaessa -->
<div id="cover"></div>


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
		$('#model').removeAttr('disabled');

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

		function makeTableHTML( array ) {
			var i;
			var result = "";
			if (array.length !== 0) {
				array = array.array;
				/* TODO: Jokaisessa näistä funktioista on asetettu parametrin arvo heti joksikin muuksi.
				 *	Mikset vaan kutsu sitä funktiota sillä parametrilla, jota oikeasti tarvitset? */
				result = "" +
					'<div style="display:inline-block; width:50%;">' +
					'	<table style="margin-left:auto; margin-right:auto;">' +
					'		<th colspan="2" class="text-center">OE</th>';
				for ( i=0; i<array.length; i++ ) {
					result += "<tr>";
					result += "" +
						"<td style='font-size:14px;'>"+array[i].brandName+"</td>" +
						"<td style='font-size:14px;'>"+array[i].oeNumber+"</td>";
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
					if ( response.articleDocuments.array[i].docTypeName != "Valokuva" ) {
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

			/**
			 * Oh my god, it's funception! BWAAAMM!
			 * ... sorry not sorry. (No but seriosly, this is a funtcion inside a function,
			 * that is itself inside a function)
			 * @param response
			 * @returns {string|*}
			 */
			function combarableNumbersToList( response ) {
				var i, result;

				if ( response.data != "" ) {
					response = response.data.array;
				}
				//luodaan taulu
				result = "<div style='display:inline-block; width:49%; vertical-align:top;'>" +
					"<table style='margin-left:auto; margin-right:auto;'>" +
						"<th colspan='2' class='text-center'>Vertailunumerot</th>" +
							"<tr><td style='font-size:14px;'>"+brand+"</td>" +
							"<td style='font-size:14px;'>"+articleNo+"</td></tr>";

				if ( response.length !== 0 ) {
					for ( i=0; i<response.length; i++ ) {
						result += "<tr>";
						result += "<td style='font-size:14px;'>"+response[i].brandName+"</td>" +
							"<td style='font-size:14px;'>"+response[i].articleNo+"</td>";
						result += "</tr>";
					}
					result += "</table>";
				}

				return result;
			}

			//luodaan taulu
			var comparableNumbers = combarableNumbersToList(response);
			//lisätään modaliin
			$("#menu3").append('\ ' +comparableNumbers+ '\ ');
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


		var documents, infos, brand, articleNo, name, OEtable, imgs, id, display_img, display_img_id, img;
		response = response.data.array[0];
		id = response.directArticle.articleId;

		//Luodaan kaikki html elementit valmiiksi, joita käytetään Modal ikkunassa
		imgs = imgsToHTML(response);
		OEtable = makeTableHTML(response.oenNumbers);
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
			<br> \
			<div class="left">'+img+'</div> \
			<div id="middle"> \
			<div id="perus_infot"> \
			<span style="font-weight:bold;">'+name+'</span><br>'+articleNo+'<br>'+brand+'<br><br> \
			</div> \
			<br>'+infos+'<br><br>'+documents+' \
			\
			\
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

		//näytetään modal
		showModal();


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
	 * Lähettää POST:ina formin. Vastaanotto puolella INSERT ostopyyntö tietokantaan.
	 * @param product_id <p> Halutun tuotteen ID
	 * @returns {boolean}
	 */
	function ostopyynnon_varmistus( product_id ) {
		var form_id = 'ostopyynto_form';
		var form_id_value = 'tuote_ostopyynto';
		var vahvistus = confirm( "Tuote on loppuunmyyty tai poistettu valikoimasta.\n"
			+ "Olisitko halunnut tilata tuotteen? Jos klikkaat OK, ostopyyntösi kirjataan ylös ylläpitoa varten.\n"
			+ "Ostopyyntö ei ole sitova.");
		if ( vahvistus ) {
			document.getElementById(form_id_value).value = product_id;
			document.getElementById(form_id).submit();
		} else {
			return false;
		}
	}

	//jQuery
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
		$("span.question").hover(function () {
			$(this).append('<div class="tooltip"><p>Mahdollista infoa käyttäjälle...</p></div>');
		}, function () {
			$("div.tooltip").remove();
		});

		//TODO: Mikä näistä kolmesta eri tavast toimii?
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

//		$('.clickable').(function () {
//			$(this).css('cursor', 'pointer'); //Muutetaan hiiren kuvaketta hoverissa
//
//			$(this).click(function() { //Tuotteen klikkaaminen hiirellä
//				//haetaan tuotteen id
//				var articleId = $(this).closest('tr').attr('data-val');
//				//spinning icon
//				$('#cover').addClass("loading");
//				//haetaan tuotteen tiedot tecdocista
//				getDirectArticlesByIds6(articleId);
//			});
//		});

		//Jos listassa olevaa tuotetta painetaan
//		$('.clickable').click(function(){
//			//haetaan tuotteen id
//			var articleId = $(this).closest('tr').attr('data-val');
//			//spinning icon
//			$('#cover').addClass("loading");
//			//haetaan tuotteen tiedot tecdocista
//			getDirectArticlesByIds6(articleId);
//		});
//		$('.clickable').css('cursor', 'pointer');

		$('#myModal').on('hidden.bs.modal', function () {
			$( "#menu1" ).empty();
			$( "#menu2" ).empty();
			$( "#menu3" ).empty();
		});

		//Käytetään eri muotoilua, koska dynaaminen content
		//TODO: Will this actually work?
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
			myWindow =window.open(src, src, "width="+w+",height="+h+",left="+left+",top="+top+"");
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

		setTimeout(setSelected ,700);

		function setSelected(){
			$("#manufacturer").find("option[value=" + manuf + "]").attr('selected', 'selected');
			$("#model").find("option[value=" + model + "]").attr('selected', 'selected');
			$("#car").find("option[value=" + car + "]").attr('selected', 'selected');
			$("#o").find("option[value=" + osat + "]").attr('selected', 'selected');
			$("#osat_alalaji").find("option[value=" + osat_alalaji + "]").attr('selected', 'selected');
		}

	}

	if ( qs["haku"] ){
		var search = qs["haku"];

		$("#search").val(search);
	}

</script>

<script>
</script>

</body>
</html>
