<!DOCTYPE html>
<html lang="fi">
<head>
	<link rel="stylesheet" href="css/styles.css">
	<meta charset="UTF-8">
	<meta name="description" content="Asiakkaalle näkyvä pohja">
	<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.0/jquery.min.js"></script>
	<script src="http://webservicepilot.tecdoc.net/pegasus-3-0/services/TecdocToCatDLB.jsonEndpoint?js"></script>
	<script>
	    <?php require_once 'tecdoc_asetukset.php'; ?>
	    var TECDOC_MANDATOR = <?php echo json_encode(TECDOC_PROVIDER); ?>;
	    var TECDOC_DEBUG = <?php echo json_encode(TECDOC_DEBUG); ?>;
	    var TECDOC_COUNTRY = <?php echo json_encode(TECDOC_COUNTRY); ?>;
	    var TECDOC_LANGUAGE = <?php echo json_encode(TECDOC_LANGUAGE); ?>;
	</script>
	<title>Tuotehaku</title>
</head>
<body>
<?php include('header.php');
	require 'tecdoc.php';
	require 'apufunktiot.php';?>
<h1 class="otsikko">Tuotehaku <span class="question">?</span></h1>

<div id="ostoskori-linkki"></div>
<form action="tuotehaku.php" method="post" class="haku">
	<input type="text" name="haku" placeholder="Tuotenumero">
	<input class="nappi" type="submit" value="Hae">
</form>
<?php include('ostoskori_lomake.php'); ?>
<h4 style="margin-left: 5%;">TAI</h4>

<form action="tuotehaku.php" method="get" id="ajoneuvomallihaku">
	<select id="manufacturer" name="manuf">
		<option value="">-- Valmistaja --</option>
	<?php
	$manufs = getManufacturers ();
	if ($manufs){
		foreach ( $manufs as $manuf ) {
			echo "<option value='$manuf->manuId'>$manuf->manuName</option>";
		}
	} else echo "<script type='text/javascript'>alert('TecDoc ei vastaa.');</script>";;
	?>
	</select>
	<br>


	<select id="model" name="model" disabled="disabled">
	<option value="">-- Malli --</option>
	</select>
	<br>

	<select id="car" name="car" disabled="disabled">
	<option value="">-- Auto --</option>
	</select>
	<br>

	<select id="osaTyyppi" name="osat" disabled="disabled">
	<option value="">-- Osat --</option>
	</select>
	<br>

	<select id="osat_alalaji" name="osat_alalaji" disabled="disabled">
	<option value="">-- Osien alalaji --</option>
	</select>
	<br>

	<input type="submit" value="HAE" id="ajoneuvohaku">
</form>

<?php 
//ostoskorin päivitys ja ostoskorin sisällön määrittäminen
handle_shopping_cart_action();

$cart_contents = '0 tuotetta';
if (isset($_SESSION['cart'])) {
	$cart_count = count($_SESSION['cart']);
	if ($cart_count === 1) {
		$cart_contents = '1 tuote';
	} else {
		$cart_contents = $cart_count . ' tuotetta';
	}
}
?>

<script type="text/javascript">

	//hakee tecdocista automallit annetun valmistaja id:n perusteella
    function getModelSeries(manufacturerID) {
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

  	//hakee lisätietoa autoista id:n perusteella
    function getVehicleByIds3(response) {
        var functionName = "getVehicleByIds3";
		var ids = [];
		for(var i = 0; i < response.data.array.length; i++) {
			ids.push(response.data.array[i].carId);
		}


		//pystyy vastaanottamaan max 25 id:tä
		while(ids.length > 0) {
			if(ids.length >= 25){
				IDarray = ids.slice(0,25);
				ids.splice(0, 25);
			} else {
				IDarray = ids.slice(0, ids.length);
				ids.splice(0, ids.length);
			}

	        var params = {
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

  	//hakee osatyypin alalajit (kuten jarrut -> jarrulevyt)
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

 	// Create JSON String and put a blank after every ',':
 	//Muuttaa tecdociin lähetettävän pyynnön JSON-muotoon
    function toJSON(obj) {
        return JSON.stringify(obj).replace(/,/g,", ");
    }

      // Callback function to do something with the response:
      // Päivittää alasvetolistaan uudet tiedot
      function updateModelList(response) {
          response = response.data;

        	//uudet tiedot listaan
			var modelList = document.getElementById("model");


		    if (response.array){
			    var i;
			    for (i = 0; i < response.array.length; i++) {
			    	var yearTo = response.array[i].yearOfConstrTo;
				    if(!yearTo) {
					    yearTo = "";
					} else {
						yearTo = addSlash(yearTo);
					}


				    var text = response.array[i].modelname
				    			+ "\xa0\xa0\xa0\xa0\xa0\xa0"
				    			+ "Year: " + addSlash(response.array[i].yearOfConstrFrom)
	    						+ " -> " + yearTo;

					var model = new Option(text, response.array[i].modelId);
					modelList.options.add(model);
			    }
		    }
		    $('#model').removeAttr('disabled');

      }

      // Callback function to do something with the response:
      // Päivittää alasvetolistaan uudet tiedot
      function updateCarList(response) {
            response = response.data;

        	//uudet tiedot listaan
			var carList = document.getElementById("car");

		   if (response.array){
			    var i;
			    for (i = 0; i < response.array.length; i++) {
				    var yearTo = response.array[i].vehicleDetails.yearOfConstrTo;
				    if(!yearTo){
					    yearTo = "";
					} else {
						yearTo = addSlash(yearTo);
					}
				    var text = response.array[i].vehicleDetails.typeName
				    			+ "\xa0\xa0\xa0\xa0\xa0\xa0"
				    			+ "Year: " + addSlash(response.array[i].vehicleDetails.yearOfConstrFrom)
	    						+ " -> " + yearTo
	    						+ "\xa0\xa0\xa0\xa0\xa0\xa0"
	    						 + response.array[i].vehicleDetails.powerKwFrom + "KW"
	    						+ " (" +response.array[i].vehicleDetails.powerHpFrom + "hp)";

			    	var car = new Option(text, response.array[i].carId);
					carList.options.add(car);
			    }
		    }
		    $('#car').removeAttr('disabled');

      }

	  // Päivittää alasvetolistaan uudet tiedot
	  function updatePartTypeList(response) {
          response = response.data;

			//uudet tiedot listaan
			var partTypeList = document.getElementById("osaTyyppi");
			if (response.array){
			    var i;
			    for (i = 0; i < response.array.length; i++) {
			    	var partType = new Option(response.array[i].assemblyGroupName, response.array[i].assemblyGroupNodeId);
					partTypeList.options.add(partType);
			    }
		    }

		    $('#osaTyyppi').removeAttr('disabled');

      }

	  // Päivittää alasvetolistaan uudet tiedot
      function updatePartSubTypeList(response) {
          response = response.data;

			//uudet tiedot listaan
			var subPartTypeList = document.getElementById("osat_alalaji");
			if (response.array){
			    var i;
			    for (i = 0; i < response.array.length; i++) {
			    	var subPartType = new Option(response.array[i].assemblyGroupName, response.array[i].assemblyGroupNodeId);
					subPartTypeList.options.add(subPartType);
			    }
		    }

		    $('#osat_alalaji').removeAttr('disabled');

      }

	  //jQuery
		$(document).ready(function(){
			$("#manufacturer").on("change", function(){
				//kun painaa jotain automerkkiä->

				var manuList = document.getElementById("manufacturer");
				//selManu = manuID
				var selManu = parseInt(manuList.options[manuList.selectedIndex].value);

				//Poistetaan vanhat tiedot
				var modelList = document.getElementById("model");
				var carList = document.getElementById("car");
				var partTypeList = document.getElementById("osaTyyppi");
				var subPartTypeList = document.getElementById("osat_alalaji");
				while (modelList.options.length - 1) {
					modelList.remove(1);
				}
				while (carList.options.length - 1) {
					carList.remove(1);
				}
				while (partTypeList.options.length - 1) {
					partTypeList.remove(1);
				}
				while (subPartTypeList.options.length - 1) {
					subPartTypeList.remove(1);
				}


				//väliaikaisesti estetään modelin ja auton valinta
				$('#model').attr('disabled', 'disabled');
				$('#car').attr('disabled', 'disabled');
				$('#osaTyyppi').attr('disabled', 'disabled');
				$('#osat_alalaji').attr('disabled', 'disabled');
				if(selManu > 0){
					getModelSeries(selManu);
				}

			});


			$("#model").on("change", function(){
				//kun painaa jotain automallia->
				var manuList = document.getElementById("manufacturer");
				var modelList = document.getElementById("model");
				var selManu = parseInt(manuList.options[manuList.selectedIndex].value);
				var selModel = parseInt(modelList.options[modelList.selectedIndex].value);

				//tyhjennetään autolista ja haetaan uudet autot
				var carList = document.getElementById("car");
				while (carList.options.length - 1) {
					carList.remove(1);
				}
				var partTypeList = document.getElementById("osaTyyppi");
				while (partTypeList.options.length - 1) {
					partTypeList.remove(1);
				}
				var subPartTypeList = document.getElementById("osat_alalaji");
				while (subPartTypeList.options.length - 1) {
					subPartTypeList.remove(1);
				}

				$('#car').attr('disabled', 'disabled');
				$('#osaTyyppi').attr('disabled', 'disabled');
				$('#osat_alalaji').attr('disabled', 'disabled');

				if (selModel > 0 ) {
					getVehicleIdsByCriteria(selManu, selModel);
				}
			});


			$("#car").on("change", function(){
				//kun painaa jotain autoa->
				var manuList = document.getElementById("manufacturer");
				var modelList = document.getElementById("model");
				var carList = document.getElementById("car");

				var selCar = parseInt(carList.options[carList.selectedIndex].value);

				//tyhjennetään autolista ja haetaan uudet autot
				var partTypeList = document.getElementById("osaTyyppi");
				while (partTypeList.options.length - 1) {
					partTypeList.remove(1);
				}
				var subPartTypeList = document.getElementById("osat_alalaji");
				while (subPartTypeList.options.length - 1) {
					subPartTypeList.remove(1);
				}

				$('#osaTyyppi').attr('disabled', 'disabled');
				$('#osat_alalaji').attr('disabled', 'disabled');
				if (selCar > 0 ) {
					getPartTypes(selCar);
				}
			});

			$("#osaTyyppi").on("change", function(){
				//kun painaa jotain osatyyppiä->
				var manuList = document.getElementById("manufacturer");
				var modelList = document.getElementById("model");
				var carList = document.getElementById("car");
				var partTypeList = document.getElementById("osaTyyppi");

				var selCar = parseInt(carList.options[carList.selectedIndex].value);
				var selPartType = parseInt(partTypeList.options[partTypeList.selectedIndex].value);

				//tyhjennetään osatyypilista
				var subPartTypeList = document.getElementById("osat_alalaji");
				while (subPartTypeList.options.length - 1) {
					subPartTypeList.remove(1);
				}

				$('#osat_alalaji').attr('disabled', 'disabled');
				if (selPartType > 0 ) {
					getChildNodes(selCar, selPartType);
				}
			});



			//annetaan hakea vain jos kaikki tarvittavat tiedot on annettu
			$("#ajoneuvomallihaku").submit(function(e) {
			    if (document.getElementById("osat_alalaji").selectedIndex != 0) {
				    //sallitaan formin lähetys
			        return true;
			    }
			    else {
			        e.preventDefault();
			        alert("Täytä kaikki kohdat ennen hakua! tms.....");
			        return false;
			    }
			});



			//info-nappulan sisältö
			$("span.question").hover(function () {
			    $(this).append('<div class="tooltip"><p>? vastaa yhtä merkkiä</p><p>* vastaa merkkijonoa</p></div>');
			  	}, function () {
			    $("div.tooltip").remove();
			});



		});

	  	
		//päivitetään ostoskorilinkki
		var cart_contents = <?php echo json_encode($cart_contents); ?>;
		$("#ostoskori-linkki").append('<a href="ostoskori.php">Ostoskori (' + cart_contents + ') </a>');

		

	  	//qs["haluttu ominaisuus"] voi hakea urlista php:n GET
	  	//funktion tapaan tietoa
		var qs = (function(a) {
		    if (a == "") return {};
		    var b = {};
		    for (var i = 0; i < a.length; ++i)
		    {
		        var p=a[i].split('=', 2);
		        if (p.length == 1)
		            b[p[0]] = "";
		        else
		            b[p[0]] = decodeURIComponent(p[1].replace(/\+/g, " "));
		    }
		    return b;
		})(window.location.search.substr(1).split('&'));

		 //laitetaan ennen sivun päivittämistä tehdyt valinnat takaisin
		if(qs["manuf"]){
			var manuf = qs["manuf"];
			var model = qs["model"];
			var car = qs["car"];
			var osat = qs["osat"];
			var osat_alalaji = qs["osat_alalaji"];


			getModelSeries(manuf);
			getVehicleIdsByCriteria(manuf, model);
			getPartTypes(car);
			getChildNodes(car, osat);

			setTimeout(setSelected ,700)

			function setSelected(){
				$("#manufacturer option[value=" + manuf + "]").attr('selected', 'selected');
				$("#model option[value=" + model + "]").attr('selected', 'selected');
				$("#car option[value=" + car + "]").attr('selected', 'selected');
				$("#osaTyyppi option[value=" + osat + "]").attr('selected', 'selected');
				$("#osat_alalaji option[value=" + osat_alalaji + "]").attr('selected', 'selected');
			}

		}

		//apufunktio, jonka avulla voidaan muotoilla ajoneuvomallihaun
		//vuosiluvut parempaan muotoon
		function addSlash(text) {
			text = String(text);
			return (text.substr(0, 4) + "/" + text.substr(4));
		}
</script>

<?php

require 'tietokanta.php';

$connection = mysqli_connect(DB_HOST, DB_USERNAME, DB_PASSWORD, DB_NAME) or die('Tietokantayhteyttä ei voitu muodostaa: ' . mysqli_connect_error());

//
// Hakee tuotteista vain sellaiset, joilla on haluttu tuotenumero/EAN/OE-numero
//
function filter_by_article_number($products, $number) {
	
	// Korvaa jokerimerkit * ja ? säännöllisen lausekkeen vastineilla
	// ja jättää muut säännöllisten lausekkeiden merkinnät huomioimatta.
	function replace_wildcards($string) {
		$replaced = preg_quote($string);
		$replaced = str_replace('\*', '.*', $replaced);
		$replaced = str_replace('\?', '.', $replaced);
		$replaced = str_replace(' ', '', $replaced);
		return $replaced;
	}

	function matches_any_number($regexp, $product) {
		$numbers = [
			str_replace(" ", "", $product->directArticle->articleNo),
            $product->ean,
			$product->oe,
			//str_replace(" ", "", $product->articleNo),
			//str_replace(" ", "", $product->articleSearchNo),
			
			
		];
        foreach ($numbers as $number) {
            if (preg_match($regexp, $number)) {
    			return true;
    		}
        }
		return false;
	}

	//Poistetaan välilyönnit
	//$number = str_replace(" ", "", $number);
	// Muodostetaan säännöllinen lauseke joka tunnistaa minkä tahansa annetuista numeroista
	$regexp = '@^' . replace_wildcards($number) . '$@i';

	$filtered = [];

	foreach ($products as $product) {
		if (matches_any_number($regexp, $product)) {
			array_push($filtered, $product);
		}
	}

	return $filtered;
}

//
// Hakee tuotevalikoimasta tuotteet tuotenumeron/EAN:n/OE-numeron perusteella
//
function search_for_product_in_catalog($number) {
	global $connection;
	

    // Haetaan ensin TecDocista tuotteet annetun numeron perusteella
    $tecdoc_products = getArticleDirectSearchAllNumbersWithState($number);
    
    
    
    /**
      KESKENERÄINEN! Jatkan tuotehaun tekoa vertailunumerolla ensi viikolla
     
    $number = trim(addslashes($number));
    
    echo count($tecdoc_products);
    $correct_products = filter_by_article_number($tecdoc_products, $number);

    
    $ids = [];
    foreach ($correct_products as $product) {
    	array_push($ids, addslashes($product->articleId));
    }
    
    $query = "SELECT * FROM tuote WHERE id IN (".implode(',',$ids).")";
    //$result = mysqli_query($connection, $query) or die(mysqli_error($connection));;
    
    if ($result) {
    	$products = [];
    	while ($row = mysqli_fetch_object($result)) {
    		array_push($products, $row);
    	}
    	if (count($products) > 0) {
    		merge_products_with_tecdoc($products);
    			
    		return $products;
    	
    	}
    }
    return []; */
    
    // Kerätään tuotteiden ID:t taulukkoon
    $ids = [];
	foreach ($tecdoc_products as $tecdoc_product) {
		array_push($ids, addslashes($tecdoc_product->articleId));
	}
	

    // Haetaan tuotevalikoimasta vastaavat tuotteet, mikäli ne on sinne lisätty
    $id_list = implode(',', $ids);
    
    $number = trim(addslashes($number));
	$result = mysqli_query($connection, "SELECT id, hinta, varastosaldo, minimisaldo FROM tuote WHERE aktiivinen=1;");

	if ($result) {
		$products = [];
		while ($row = mysqli_fetch_object($result)) {
			array_push($products, $row);
		}
		if (count($products) > 0) {
			merge_products_with_tecdoc($products);
			
            $products = filter_by_article_number($products, $number);
            
		}
		return $products;
	}

	return [];
	
}

$number = isset($_POST['haku']) ? $_POST['haku'] : null;

if ($number) {
	$products = search_for_product_in_catalog($number);

	print_results($products);
}

if(isset($_GET["manuf"])) {

	$selCar = $_GET["car"];
	$selPartType = $_GET["osat_alalaji"];



	/* echo "manuf: " . $_POST["manuf"] . " ";
	echo "model: " . $_POST["model"] . " ";
	echo "car: " . $_POST["car"] . " ";
	echo "groupID: " . $_POST["osat_alalaji"] . " "; */


	$articleIDs = [];
	$articles = getArticleIdsWithState($selCar, $selPartType);

	//poistetaan duplikaatit
	foreach ($articles as $article){
		if(!in_array($article->articleId, $articleIDs)){
			array_push($articleIDs, $article->articleId);

		}
	}

	//valitaan vain ne tuotteet jotka lisätty tietokantaan

	global $connection;

	$result = mysqli_query($connection, "SELECT id, hinta, varastosaldo, minimisaldo FROM tuote;");

	if ($result) {
		$products = [];
		while ($row = mysqli_fetch_object($result)) {
			if(in_array($row->id, $articleIDs)) {
				array_push($products, $row);
			}
		}
		//yhdistetään tietokannasta löytyneet tuotteet tecdoc-datan kanssa
		if (count($products) > 0) {
			merge_products_with_tecdoc($products);
		}
	}
	print_results($products);



}


function print_results($products) {
	echo '<div class="tulokset">';
	echo '<h2>Tulokset:</h2>';
	if (count($products) > 0) {
		echo '<table>';
		echo '<tr><th>Kuva</th><th>Tuotenumero</th><th>Tuote</th><th>Info</th><th>EAN</th><th>OE</th><th style="text-align: right;">Hinta</th><th style="text-align: right;">Varastosaldo</th><th>Kpl</th></tr>';
		foreach ($products as $product) {
			$article = $product->directArticle;
			echo '<tr>';
			echo "<td class=\"thumb\"><img src=\"$product->thumburl\" alt=\"$article->articleName\"></td>";
			echo "<td>$article->articleNo</td>";
			echo "<td>$article->brandName <br> $article->articleName</td>";
			echo "<td>";
			foreach ($product->infos as $info){
				if(!empty($info->attrName)) echo $info->attrName . " ";
				if(!empty($info->attrValue)) echo $info->attrValue . " ";
				if(!empty($info->attrUnit)) echo $info->attrUnit . " ";
				echo "<br>";
			}
			echo "</td>";
			echo "<td>$product->ean</td>";
			echo "<td>$product->oe</td>";
			echo "<td style=\"text-align: right;\">" . format_euros($product->hinta) . "</td>";
			echo "<td style=\"text-align: right;\">" . format_integer($product->varastosaldo) . "</td>";
			echo "<td style=\"padding-top: 0; padding-bottom: 0;\"><input id=\"maara_" . $article->articleId . "\" name=\"maara_" . $article->articleId . "\" class=\"maara\" type=\"number\" value=\"0\" min=\"0\"></td>";
			echo "<td class=\"toiminnot\"><a class=\"nappi\" href=\"javascript:void(0)\" onclick=\"addToShoppingCart($article->articleId)\">Osta</a></td>";
			echo '</tr>';
		}
		echo '</table>';
	} else {
		echo '<p>Ei tuloksia.</p>';
	}
	echo '</div>';
}



?>

</body>


</body>
</html>
