<!DOCTYPE html>
<html lang="fi">
<head>
	<meta charset="UTF-8">
	<link rel="stylesheet" href="css/styles.css">
	<link rel="stylesheet" href="css/jsmodal-light.css">
	<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.0/jquery.min.js"></script>
	<script src="http://webservicepilot.tecdoc.net/pegasus-3-0/services/TecdocToCatDLB.jsonEndpoint?js"></script>
	<script>
    <?php require_once 'tecdoc_asetukset.php'; ?>
    var TECDOC_MANDATOR = <?php echo json_encode(TECDOC_PROVIDER); ?>;
    var TECDOC_DEBUG = <?php echo json_encode(TECDOC_DEBUG); ?>;
    var TECDOC_COUNTRY = <?php echo json_encode(TECDOC_COUNTRY); ?>;
    var TECDOC_LANGUAGE = <?php echo json_encode(TECDOC_LANGUAGE); ?>;
	</script>
	<title>Tuotteet</title>
</head>
<body>
<?php
	include("header.php");
	require 'tecdoc.php';
	require 'tietokanta.php';
	require 'apufunktiot.php';
	if(!is_admin()){
		header("Location:tuotehaku.php");
		exit();
	}
?>
<h1 class="otsikko">Tuotteet</h1>
<div id="painikkeet">
	<a href="lue_tiedostosta.php"><span class="nappi">Lisää tiedostosta</span></a>
	<?php include("yp_tuotteet_alv_muokkaus.php"); //Sisaltaa toiminnallisuuden ALV:ien muokkaamiseen ?>
</div>
<form action="yp_tuotteet.php" method="post" class="haku">
	<input type="text" name="haku" placeholder="Tuotenumero">
	<input class="nappi" type="submit" value="Hae">
</form>

<h4 style="margin-left: 5%;">TAI</h4>

<form action="yp_tuotteet.php" method="post" id="ajoneuvomallihaku">
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

<script src="js/jsmodal-1.0d.min.js"></script>
<script>

// Tuotteen lisäys valikoimaan
function showAddDialog(id) {
	Modal.open({
    	content: '\
			<div class="dialogi-otsikko">Lisää tuote</div> \
			<form action="yp_tuotteet.php" name="lisayslomake" method="post"> \
			<label for="hinta">Hinta (ilman ALV):</label><span class="dialogi-kentta"><input class="eur" name="hinta" placeholder="0,00"> &euro;</span><br> \
			<label for="alv">ALV-taso:</label><span class="dialogi-kentta"><input class="eur" name="alv" placeholder="0"></span><br> \
			<label for="varastosaldo">Varastosaldo:</label><span class="dialogi-kentta"><input class="kpl" name="varastosaldo" placeholder="0"> kpl</span><br> \
			<label for="minimisaldo">Minimisaldo:</label><span class="dialogi-kentta"><input class="kpl" name="minimisaldo" placeholder="0"> kpl</span><br> \
			<label for="minimimyyntiera">Minimimyyntierä:</label><span class="dialogi-kentta"><input class="kpl" name="minimimyyntiera" placeholder="0"> kpl</span><br> \
			<p><input class="nappi" type="submit" name="laheta" value="Lisää" onclick="document.lisayslomake.submit()"><a class="nappi" style="margin-left: 10pt;" href="javascript:void(0)" onclick="Modal.close()">Peruuta</a></p> \
			<input type="hidden" name="lisaa" value="' + id + '"> \
			</form>'
	});
}

// Tuotteen poisto valikoimasta
function showRemoveDialog(id) {
	Modal.open({
    	content: '\
		<div class="dialogi-otsikko">Poista tuote</div> \
		<p>Haluatko varmasti poistaa tuotteen valikoimasta?</p> \
		<p style="margin-top: 20pt;"><a class="nappi" href="yp_tuotteet.php?poista=' + id + '">Poista</a><a class="nappi" style="margin-left: 10pt;" href="javascript:void(0)" onclick="Modal.close()">Peruuta</a></p>'
	});
}

// Valikoimaan lisätyn tuotteen muokkaus
function showModifyDialog(id, price, alv, count, minimumCount, minimumSaleCount) {
	Modal.open({
    	content: '\
			<div class="dialogi-otsikko">Muokkaa tuotetta</div> \
			<form action="yp_tuotteet.php" name="muokkauslomake" method="post"> \
			<label for="hinta">Hinta (ilman ALV):</label><span class="dialogi-kentta"><input class="eur" name="hinta" placeholder="0,00" value="' + price + '"> &euro;</span><br> \
			<label for="alv">ALV-taso:</label><span class="dialogi-kentta"><input class="eur" name="alv" placeholder="0" value="' + alv + '"></span><br> \
			<label for="varastosaldo">Varastosaldo:</label><span class="dialogi-kentta"><input class="kpl" name="varastosaldo" placeholder="0" value="' + count + '"> kpl</span><br> \
			<label for="minimisaldo">Minimisaldo:</label><span class="dialogi-kentta"><input class="kpl" name="minimisaldo" placeholder="0" value="' + minimumCount + '"> kpl</span><br> \
			<label for="minimimyyntiera">Minimimyyntierä:</label><span class="dialogi-kentta"><input class="kpl" name="minimimyyntiera" placeholder="0" value="' + minimumSaleCount + '"> kpl</span><br> \
			<p><input class="nappi" type="submit" name="tallenna" value="Tallenna" onclick="document.muokkauslomake.submit()"><a class="nappi" style="margin-left: 10pt;" href="javascript:void(0)" onclick="Modal.close()">Peruuta</a></p> \
			<input type="hidden" name="muokkaa" value="' + id + '"> \
			</form>'
	});
}

</script>

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
			        alert("Täytä kaikki kohdat ennen hakua!");
			        return false;
			    }
			});
			
		});




		//apufunktio, jonka avulla voidaan muotoilla ajoneuvomallihaun
		//vuosiluvut parempaan muotoon
		function addSlash(text) {
			text = String(text);
			return (text.substr(0, 4) + "/" + text.substr(4));
		}



</script>

<?php
//$catalog_products = get_products_in_catalog();

//
// Lisää uuden tuotteen valikoimaan
//
function add_product_to_catalog($id, $price, $alv, $count, $minimum_count, $minimum_sale_count) {
	global $connection;
	$id = intval($id);
	$price = doubleval($price);
	$alv = intval($alv);
	$price_with_alv = (float)$price * (1 + hae_ALV_prosentti($alv));
	$count = intval($count);
	$minimum_count = intval($minimum_count);
	$minimum_sale_count = intval($minimum_sale_count);
	$result = mysqli_query($connection, "
		INSERT INTO tuote 
			(id, hinta, hinta_ilman_ALV, ALV_taso, varastosaldo, minimisaldo, minimimyyntiera) 
		VALUES 
			('$id', '$price_with_alv', '$price', '$alv', '$count', '$minimum_count', '$minimum_sale_count')
		ON DUPLICATE KEY 
			UPDATE hinta=$price_with_alv, hinta_ilman_ALV=$price, ALV_taso=$alv, varastosaldo=$count, minimisaldo=$minimum_count, minimimyyntiera=$minimum_sale_count, aktiivinen=1;");
	return $result;
}

//
// Deaktivoi tuotteen valikoimasta
//
function remove_product_from_catalog($id) {
	global $connection;
	$id = intval($id);
	mysqli_query($connection, "
		UPDATE tuote 
		SET aktiivinen=0 
		WHERE id=$id;");
	return mysqli_affected_rows($connection) > 0;
}

//
// Muokkaa valikoimaan lisättyä tuotetta
//
function modify_product_in_catalog($id, $price, $alv, $count, $minimum_count, $minimum_sale_count) {
	global $connection;
	$id = intval($id);
	$price = doubleval($price);
	$alv = intval($alv);
	$price_with_alv = $price * (1.00 + hae_ALV_prosentti($alv));
	$count = intval($count);
	$minimum_count = intval($minimum_count);
	$minimum_sale_count = intval($minimum_sale_count);
	$result = mysqli_query($connection, "
		UPDATE 	tuote 
		SET 	hinta=$price_with_alv, hinta_ilman_ALV=$price, ALV_taso=$alv, varastosaldo=$count, minimisaldo=$minimum_count, minimimyyntiera=$minimum_sale_count 
		WHERE 	id=$id;");
	return mysqli_affected_rows($connection) >= 0;
}

//
// Hakee tietokannasta kaikki tuotevalikoimaan lisätyt tuotteet
//
function get_products_in_catalog() {
	global $connection;
	$result = mysqli_query($connection, "
		SELECT id, hinta, hinta_ilman_ALV, ALV_taso, varastosaldo, minimisaldo, minimimyyntiera 
		FROM tuote 
		WHERE aktiivinen=1;");
	if ($result) {
		$products = [];
		while ($row = mysqli_fetch_object($result)) {
			array_push($products, $row);
		}
		merge_products_with_tecdoc($products);
		return $products;
	}
	return [];
}

//
// Tulostaa hakutulokset
//
function print_results($number) {
    global $catalog_products;

	if (!$number) {
		return;
	} else {
		$number = trim(addslashes($number));
	}

    $ids_in_catalog = [];
    foreach ($catalog_products as $product) {
        array_push($ids_in_catalog, $product->id);
    }

	echo '<div class="tulokset">';
	echo '<h2>Tulokset:</h2>';
	$products = getArticleDirectSearchAllNumbersWithState($number);
	merge_products_with_optional_data($products);
	if (count($products) > 0) {
		echo '<table>';
		echo '<tr><th>Kuva</th><th>Tuotenumero</th><th>Tuote</th><th>Info</th><th>EAN</th><th>OE</th></tr>';
		foreach ($products as $article) {
			echo '<tr>';
			echo "<td class=\"thumb\"><img src=\"$article->thumburl\" alt=\"$article->articleName\"></td>";
			echo "<td>$article->articleNo</td>";
			echo "<td>$article->brandName <br> $article->articleName</td>";
			echo "<td>";
			foreach ($article->infos as $info){
				if(!empty($info->attrName)) echo $info->attrName . " ";
				if(!empty($info->attrValue)) echo $info->attrValue . " ";
				if(!empty($info->attrUnit)) echo $info->attrUnit . " ";
				echo "<br>";
			}
			echo "</td>";
			echo "<td>$article->ean</td>";
			echo "<td>$article->oe</td>";
            if (in_array($article->articleId, $ids_in_catalog)) {
                // Tuote on jo valikoimassa
                echo "<td class=\"toiminnot\"><a class=\"nappi disabled\">Lisää</a></td>";
            } else {
                echo "<td class=\"toiminnot\"><a class=\"nappi\" href=\"javascript:void(0)\" onclick=\"showAddDialog($article->articleId)\">Lisää</a></td>";
            }
			echo '</tr>';
		}
		echo '</table>';
	} else {
		echo '<p>Ei tuloksia.</p>';
	}
	echo '</div>';
}

//
// Tulostaa tuotevalikoiman
//
function print_catalog($products) {
	echo '<div class="tulokset">';
	echo '<h2>Valikoima</h2>';
    /*
    Debuggausta varten:
    echo '<pre>';
    var_dump($products);
    echo '</pre>';
    */
	if (count($products) > 0) {
		echo '<table>';
        echo '<tr><th>Kuva</th><th>Tuotenumero</th><th>Tuote</th><th>Info</th><th>EAN</th><th>OE</th><th style="text-align: right;">Hinta</th><th style="text-align: right;">Varastosaldo</th><th style="text-align: right;">Minimisaldo</th><th style="text-align: right;">Minimimyyntierä</th></tr>';
		foreach ($products as $product) {
			$article = $product->directArticle;
			echo '<tr>';
			echo "<td class=\"thumb\"><img src=\"$product->thumburl\" alt=\"$article->articleName\"></td>";
			echo "<td>$article->articleNo</td>";
			echo "<td>$article->brandName <br> $article->articleName</td>";
            echo "<td>";
            foreach ($product->infos as $info){
                if (!empty($info->attrName)) echo $info->attrName . " ";
                if (!empty($info->attrValue)) echo $info->attrValue . " ";
                if (!empty($info->attrUnit)) echo $info->attrUnit . " ";
                echo "<br>";
            }
            echo "</td>";
            echo "<td>$product->ean</td>";
			echo "<td>$product->oe</td>";
			echo "<td style=\"text-align: right;\">" . format_euros($product->hinta) . "</td>";
			echo "<td style=\"text-align: right;\">" . format_integer($product->varastosaldo) . "</td>";
			echo "<td style=\"text-align: right;\">" . format_integer($product->minimisaldo) . "</td>";
			echo "<td style=\"text-align: right;\">" . format_integer($product->minimimyyntiera) . "</td>";
			echo "<td class=\"toiminnot\"><a class=\"nappi\" href=\"javascript:void(0)\" 
				onclick=\"showModifyDialog(
					$product->id,
					'" . str_replace('.', ',', $product->hinta_ilman_ALV) . "',
					$product->ALV_taso,
					$product->varastosaldo,
					$product->minimisaldo,
					$product->minimimyyntiera)
					\">Muokkaa</a> <a class=\"nappi\" href=\"javascript:void(0)\" onclick=\"showRemoveDialog($product->id)\">Poista</a></td>";
			echo '</tr>';
		}
		echo '</table>';
	} else {
		echo '<p>Ei tuotteita valikoimassa.</p>';
	}
	echo '</div>';
}



$number = isset($_POST['haku']) ? $_POST['haku'] : false;

	if (isset($_POST['lisaa'])) {
		$id = intval($_POST['lisaa']);
		$hinta = doubleval(str_replace(',', '.', $_POST['hinta']));
		$alv = intval($_POST['alv']);
		$varastosaldo = intval($_POST['varastosaldo']);
		$minimisaldo = intval($_POST['minimisaldo']);
		$minimimyyntiera = intval($_POST['minimimyyntiera']);
		$success = add_product_to_catalog($id, $hinta, $alv, $varastosaldo, $minimisaldo, $minimimyyntiera);
		if ($success) {
			echo '<p class="success">Tuote lisätty!</p>';
		} else {
			echo '<p class="error">Tuotteen lisäys epäonnistui!</p>';
		}
	} elseif (isset($_GET['poista'])) {
		$success = remove_product_from_catalog($_GET['poista']);
		if ($success) {
			echo '<p class="success">Tuote poistettu!</p>';
		} else {
			echo '<p class="error">Tuotteen poisto epäonnistui!<br><br>Luultavasti kyseistä tuotetta ei ollut valikoimassa.</p>';
		}
	} elseif (isset($_POST['muokkaa'])) {
		$id = intval($_POST['muokkaa']);
		$hinta = doubleval(str_replace(',', '.', $_POST['hinta']));
		$alv = intval($_POST['alv']);
		$varastosaldo = intval($_POST['varastosaldo']);
		$minimisaldo = intval($_POST['minimisaldo']);
		$minimimyyntiera = intval($_POST['minimimyyntiera']);
		$success = modify_product_in_catalog($id, $hinta, $alv, $varastosaldo, $minimisaldo, $minimimyyntiera);
		if ($success) {
			echo '<p class="success">Tuotteen tiedot päivitetty!</p>';
		} else {
			echo '<p class="error">Tuotteen muokkaus epäonnistui!</p>';
		}
	}elseif (isset($_POST["manuf"])) {

		$selCar = $_POST["car"];
		$selPartType = $_POST["osat_alalaji"];


		/*  Debuggaukseen:
		 *
		 *  echo "manuf: " . $_POST["manuf"] . " ";
		 *	echo "model: " . $_POST["model"] . " ";
		 *  echo "car: " . $_POST["car"] . " ";
		 *  echo "groupID: " . $_POST["osat_alalaji"] . " ";
		 */


		$articleIDs = [];
		$products = [];
		$articles = getArticleIdsWithState($selCar, $selPartType);
		merge_products_with_optional_data($articles);

		//poistetaan duplikaatit
		foreach ($articles as $article){
			if(!in_array($article->articleId, $articleIDs)){
				array_push($articleIDs, $article->articleId);
				array_push($products, $article);
			}
		}

		echo '<div class="tulokset">';
		echo '<h2>Tulokset:</h2>';
		if (count($articles) > 0) {
			echo '<table>';
			echo '<tr><th>Kuva</th><th>Tuotenumero</th><th>Tuote</th><th>Info</th><th>EAN</th><th>OE</th></tr>';
			foreach ($products as $product) {
				echo '<tr>';
				echo "<td class=\"thumb\"><img src=\"$product->thumburl\" alt=\"$product->genericArticleName\"></td>";
				echo "<td>$product->articleNo</td>";
				echo "<td>$product->brandName <br> $product->genericArticleName</td>";
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

				echo "<td class=\"toiminnot\"><a class=\"nappi\" href=\"javascript:void(0)\" onclick=\"showAddDialog($product->articleId)\">Lisää</a></td>";
				echo '</tr>';
			}
			echo '</table>';
		} else {
				echo '<p>Ei tuloksia.</p>';
		}
		echo '</div>';

	}
	$catalog_products = get_products_in_catalog();
	print_results($number);
	print_catalog($catalog_products);

?>

</body>
</html>
