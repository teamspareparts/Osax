<!DOCTYPE html>
<html lang="fi">
<head>
	<link rel="stylesheet" href="css/styles.css">
	<meta charset="UTF-8">
	<meta name="description" content="Asiakkaalle näkyvä pohja">
	<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.0/jquery.min.js"></script>
	<script src="http://webservicepilot.tecdoc.net/pegasus-3-0/services/TecdocToCatDLB.jsonEndpoint?js"></script>
	<script>
		var TECDOC_MANDATOR = 149;
		var TECDOC_DEBUG = false;
		var TECDOC_COUNTRY = 'FI';
		var TECDOC_LANGUAGE = 'FI';
	</script>
	<title>Tuotehaku</title>
</head>
<body>
<?php include('header.php');
	require 'tecdoc.php';?>
<h1 class="otsikko">Tuotehaku</h1>
<?php
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
<div id="ostoskori-linkki"><a href="ostoskori.php">Ostoskori (<?php echo $cart_contents; ?>)</a></div>
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

<script type="text/javascript">


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

    function getShortCuts2(carID) {		
        var functionName = "getShortCuts2";
        var params = {
                "linkingTargetId" : carID,
                "linkingTargetType" : "P",
                "articleCountry" : TECDOC_COUNTRY,
                "lang" : TECDOC_LANGUAGE,
                "provider" : TECDOC_MANDATOR
        };      
		tecdocToCatPort[functionName] (params, updatePartTypeList);
    }

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

    


    function getDirectArticlesByIds4(ids) {		
        var functionName = "getDirectArticlesByIds4";
        params = {
           		'lang' : TECDOC_LANGUAGE,
           		'articleCountry' : TECDOC_COUNTRY,
           		'provider' : TECDOC_PROVIDER,
           		'basicData' : true,
           		'articleId' : {'array' : ids}
        	};
        params = toJSON(params);
		tecdocToCatPort[functionName] (params, getVehicleByIds3);

    }


    // Create JSON String and put a blank after every ',':
    function toJSON(obj) {        
        return JSON.stringify(obj).replace(/,/g,", ");
    }

 

          

      
      

      // Callback function to do something with the response:
      function updateModelList(response) {         
          response = response.data;

        	//uudet tiedot listaan
			var modelList = document.getElementById("model");
			

		    if (response.array){
			    var i;
			    for (i = 0; i < response.array.length; i++) {
					var model = new Option(response.array[i].modelname, response.array[i].modelId);
					modelList.options.add(model);
			    }
		    }
		    $('#model').removeAttr('disabled');
	          
      }
      


      // Callback function to do something with the response:
      function updateCarList(response) {
            response = response.data;

        	//uudet tiedot listaan
			var carList = document.getElementById("car");

		   if (response.array){
			    var i;
			    for (i = 0; i < response.array.length; i++) {
				    var yearTo = response.array[i].vehicleDetails.yearOfConstrTo
				    if(!yearTo) yearTo = "";
				    var text = response.array[i].vehicleDetails.typeName
				    			+ "\xa0\xa0\xa0\xa0\xa0\xa0"
				    			+ "Year: " + response.array[i].vehicleDetails.yearOfConstrFrom
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


		   
			
		});


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















		
		
	</script>
<?php

require 'tietokanta.php';
require 'apufunktiot.php';

$connection = mysqli_connect(DB_HOST, DB_USERNAME, DB_PASSWORD, DB_NAME) or die('Tietokantayhteyttä ei voitu muodostaa: ' . mysqli_connect_error());

//
// Hakee tuotteista vain sellaiset, joilla on haluttu tuotenumero
//
function filter_by_article_no($products, $articleNo) {
	// Korvaa jokerimerkit * ja ? säännöllisen lausekkeen vastineilla
	// ja jättää muut säännöllisten lausekkeiden merkinnät huomioimatta.
	function replace_wildcards($string) {
		$replaced = preg_quote($string);
		$replaced = str_replace('\*', '.*', $replaced);
		$replaced = str_replace('\?', '.', $replaced);
		return $replaced;
	}

	$articleNo = replace_wildcards($articleNo);
	$regexp = '/^' . $articleNo . '$/i';  // kirjainkoolla ei väliä
	$filtered = [];

	foreach ($products as $product) {
		if (preg_match($regexp, $product->articleNo)) {
			array_push($filtered, $product);
		}
	}
	return $filtered;
}

//
// Hakee tuotevalikoimasta tuotteet tuotenumeron perusteella
//
function search_for_product_in_catalog($number) {
	global $connection;

	$number = addslashes(trim($number));
	$result = mysqli_query($connection, "SELECT id, hinta, varastosaldo, minimisaldo FROM tuote;");

	if ($result) {
		$products = [];
		while ($row = mysqli_fetch_object($result)) {
			array_push($products, $row);
		}
		if (count($products) > 0) {
			merge_products_with_tecdoc($products);
			$products = filter_by_article_no($products, $number);
		}
		return $products;
	}
	return [];
}

handle_shopping_cart_action();

$number = isset($_POST['haku']) ? $_POST['haku'] : null;
if ($number) {
	echo '<div class="tulokset">';
	echo '<h2>Tulokset</h2>';
	$products = search_for_product_in_catalog($number);
	if (count($products) > 0) {
		echo '<table>';
		echo '<tr><th>Tuotenumero</th><th>Tuote</th><th style="text-align: right;">Hinta</th><th style="text-align: right;">Varastosaldo</th><th>Kpl</th></tr>';
		foreach ($products as $product) {
			echo '<tr>';
			echo "<td>$product->articleNo</td>";
			echo "<td>$product->brandName $product->articleName</td>";
			echo "<td style=\"text-align: right;\">" . format_euros($product->hinta) . "</td>";
			echo "<td style=\"text-align: right;\">" . format_integer($product->varastosaldo) . "</td>";
			echo "<td style=\"padding-top: 0; padding-bottom: 0;\"><input id=\"maara_" . $product->articleId . "\" name=\"maara_" . $product->articleId . "\" class=\"maara\" type=\"number\" value=\"0\" min=\"0\"></td>";
			echo "<td class=\"toiminnot\"><a class=\"nappi\" href=\"javascript:void(0)\" onclick=\"addToShoppingCart($product->articleId)\">Osta</a></td>";
			echo '</tr>';
		}
		echo '</table>';
	} else {
	   echo '<p>Ei tuloksia.</p>';
	}
	echo '</div>';
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
	
	//valitaan vain ne jotka lisätty tietokantaan
	
	global $connection;
	
	$result = mysqli_query($connection, "SELECT id, hinta, varastosaldo, minimisaldo FROM tuote;");
	
	if ($result) {
		$products = [];
		while ($row = mysqli_fetch_object($result)) {
			if(in_array($row->id, $articleIDs)) {
				array_push($products, $row);
			}
		}
		if (count($products) > 0) {
			merge_products_with_tecdoc($products);
		}
	}


	//printataan tuotteet 25 kappaleen erissä
	echo '<div class="tulokset">';
	echo '<h2>Tulokset:</h2>';
	if (count($products) > 0) {
		echo '<table>';
		echo '<tr><th>Tuotenumero</th><th>Tuote</th><th style="text-align: right;">Hinta</th><th style="text-align: right;">Varastosaldo</th><th>Kpl</th></tr>';
		foreach ($products as $product) {
			echo '<tr>';
			echo "<td>$product->articleNo</td>";
			echo "<td>$product->brandName $product->articleName</td>";
			echo "<td style=\"text-align: right;\">" . format_euros($product->hinta) . "</td>";
			echo "<td style=\"text-align: right;\">" . format_integer($product->varastosaldo) . "</td>";
			echo "<td style=\"padding-top: 0; padding-bottom: 0;\"><input id=\"maara_" . $product->articleId . "\" name=\"maara_" . $product->articleId . "\" class=\"maara\" type=\"number\" value=\"0\" min=\"0\"></td>";
			echo "<td class=\"toiminnot\"><a class=\"nappi\" href=\"javascript:void(0)\" onclick=\"addToShoppingCart($product->articleId)\">Osta</a></td>";
			echo '</tr>';
		}
		echo '</table>';
	} else {
		echo '<p>Ei tuloksia.</p>';
	}
	echo '</div>';

}


function print_results2($ids) {

	$products = get_products_by_id($ids);

	foreach ($products as $product) {
		echo '<tr>';
		echo "<td>$product->articleNo</td>";
		echo "<td>$product->brandName $product->articleName</td>";
		//echo "<td>$product->articleId</td>";
		echo "<td class=\"toiminnot\"><a class=\"nappi\" href=\"javascript:void(0)\" onclick=\"showAddDialog($product->articleId)\">Lisää</a></td>";
		echo '</tr>';
	}
}

?>

</body>


</body>
</html>
