<!DOCTYPE html>
<html lang="fi">
<head>
	<link rel="stylesheet" href="css/styles.css">
	<link rel="stylesheet" href="css/bootstrap.css">
	<link rel="stylesheet" href="css/jsmodal-light.css">
	
	<meta charset="UTF-8">
	<meta name="description" content="Asiakkaalle näkyvä pohja">
	<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.0/jquery.min.js"></script>
	<script src="https://ajax.googleapis.com/ajax/libs/jqueryui/1.11.4/jquery-ui.min.js"></script>
	<script src="http://webservicepilot.tecdoc.net/pegasus-3-0/services/TecdocToCatDLB.jsonEndpoint?js"></script>
	<script src="js/jsmodal-1.0d.min.js"></script>
	<script src="http://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/js/bootstrap.min.js"></script>
	<script>
	    <?php require_once 'tecdoc_asetukset.php'; ?>
	    var TECDOC_MANDATOR = <?php echo json_encode(TECDOC_PROVIDER); ?>;
	    var TECDOC_DEBUG = <?php echo json_encode(TECDOC_DEBUG); ?>;
	    var TECDOC_COUNTRY = <?php echo json_encode(TECDOC_COUNTRY); ?>;
	    var TECDOC_LANGUAGE = <?php echo json_encode(TECDOC_LANGUAGE); ?>;
	    var TECDOC_THUMB_URL = <?php echo json_encode(TECDOC_THUMB_URL); ?>;
	</script>
	<title>Tuotehaku</title>
</head>
<body>
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

<?php include('header.php');
require 'tecdoc.php';
require 'apufunktiot.php';
require 'tietokanta.php';
if ( !empty($_POST['tuote_ostopyynto']) ) {
	$sql = 'INSERT
			INTO tuote_ostopyynto (tuote_id, kayttaja_id )
			VALUES ( ?, ? ) ';
	$db->query($sql, [ $_POST['tuote_ostopyynto'], $_SESSION['id'] ]);
}
?>
<h1 class="otsikko">Tuotehaku <span class="question">?</span></h1>

<div id="ostoskori-linkki"></div>
<form action="tuotehaku.php" method="get" class="haku">
	<input id="search" type="text" name="haku" placeholder="Tuotenumero">
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
//var_dump($_SESSION['cart']);

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
        params = toJSON(params);
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
        params = toJSON(params);
		tecdocToCatPort[functionName] (params, updatePartSubTypeList);
    }

  	//haetaan tuotteen tarkemmat tiedot
    function getDirectArticlesByIds6(ids) {
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

	  
	//Näytetään tuotteen tarkemmat tiedot Modal-ikkunassa
	function addProductInfoOnModal(response){

		function makeTableHTML(array) {
			if(array.length==0) return "";
			array=array.array;
		    var result = "<div style='display:inline-block; width:50%;'><table style='margin-left:auto; margin-right:auto;'><th colspan='2' class='text-center'>OE</th>";
		    for(var i=0; i<array.length; i++) {
		        result += "<tr>";
		        result += "<td style='font-size:14px'>"+array[i].brandName+"</td><td style='font-size:14px'>"+array[i].oeNumber+"</td>";
		        result += "</tr>";
		    }
		    result += "</table>";

		    return result;	
		}

		//Tehdään peräkkäinen html-muotoinen lista, jossa kaikki löytyneet kuvat peräkkäin
		function imgsToHTML(response) {
			if(response.articleThumbnails.length==0) return "<img src='img/ei-kuvaa.png' class='no-image' />";
			var imgs = "";
			for(var i=0; i<response.articleThumbnails.array.length; i++) {
				thumb_id = response.articleThumbnails.array[i].thumbDocId;
				img = TECDOC_THUMB_URL +thumb_id + '/0/';
				imgs += '<img src='+ img +' border="1" class="tuote_img kuva" /><br>';
			}
			return imgs;
		}

		//Tehdään html-muotoinen listaus tuotteen infoista
		function infosToHTML(response) {
			var infos = "";
			//saatavuustiedot
			if(response.directArticle.articleState != 1) {
				infos += "<span style='color:red;'>" + response.directArticle.articleStateName + "</span><br>";
			}
			//pakkaustiedot
			if (typeof response.directArticle.packingUnit != 'undefined') {
				  infos += "Pakkauksia: " + response.directArticle.packingUnit + "<br>";
			}
			if (typeof response.directArticle.quantityPerPackingUnit != 'undefined') {
				  infos += "Kpl/pakkaus: " + response.directArticle.quantityPerPackingUnit + "<br>";
			}
			infos+="<br>";

			//infot
			if (response.articleAttributes == "") {
				  return infos;
			}
			for(var i = 0; i < response.articleAttributes.array.length; i++) {
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
			return infos;
		}

		//Tehdään dokumenttien latauslinkit, jos olemassa
		function getDocuments(response){
			if (response.articleDocuments == "") {
				return "";
			}
			var documentlink = "";
			for(var i = 0; i < response.articleDocuments.array.length; i++) {
				//Dokumentit
				if(response.articleDocuments.array[i].docTypeName != "Valokuva") {
					//alert(response.articleDocuments.array[i].docTypeId);
					var doc = TECDOC_THUMB_URL + response.articleDocuments.array[i].docId;
					var docName = response.articleDocuments.array[i].docFileName;
					var docTypeName = response.articleDocuments.array[i].docTypeName;

					documentlink += '<img src="img/pdficon.png" style="margin-right:5px;margin-bottom:7px;"><a href="'+doc+
						'" download="'+docName+'" id="asennusohje">'+docTypeName+' (PDF)</a><br>';
				}
			}			
			return documentlink;
		}

        function getComparableNumber(articleNumber) {
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
        function addComparableNumbersToModal(response){

            function combarableNumbersToList(response){

                if(response.data != "") {
                    response = response.data.array;
                }
                //luodaan taulu
                var result = "<div style='display:inline-block; width:49%; vertical-align:top;'>" +
                    "<table style='margin-left:auto; margin-right:auto;'>" +
                    "<th colspan='2' class='text-center'>Vertailunumerot</th>" +
                    "<tr><td style='font-size:14px'>"+brand+"</td><td style='font-size:14px'>"+articleNo+"</td></tr>";
                if(response.length==0) return "";
                for(var i=0; i<response.length; i++) {
                    result += "<tr>";
                    result += "<td style='font-size:14px'>"+response[i].brandName+"</td><td style='font-size:14px'>"+response[i].articleNo+"</td>";
                    result += "</tr>";
                }
                result += "</table>";

                return result;
            }



            //luodaan taulu
            comparableNumbers = combarableNumbersToList(response);
            //lisätään modaliin
            $("#menu3").append('\
			'+comparableNumbers+'\
			');
        }

        //avataan modal ja pysäytetään spinning icon
        function showModal(){
            //lopetetaan spinning iconin näyttäminen
            $('#cover').removeClass("loading");
            //avataan modal
            $("#myModal").modal({
                keyboard: true
            });
            //avataan aina "tuote" tabi ensin
            $('#maintab').tab('show');
        }

		
		response = response.data.array[0];
		var id = response.directArticle.articleId;

		//Luodaan kaikki html elementit valmiiksi, joita käytetään Modal ikkunassa
		var imgs = imgsToHTML(response);
		var OEtable = makeTableHTML(response.oenNumbers);
		var name = response.directArticle.articleName;
		var articleNo = response.directArticle.articleNo;
		var brand = response.directArticle.brandName;
		var infos = infosToHTML(response);
		var documents = getDocuments(response);
		
		//display image
		var display_img_id = "";
		if(response.articleThumbnails.length==0) {
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
			<span style="font-weight:bold">'+name+'</span><br>'+articleNo+'<br>'+brand+'<br><br> \
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
			    $(this).append('<div class="tooltip"><p>Mahdollista infoa käyttäjälle...</p></div>');
			  	}, function () {
			    $("div.tooltip").remove();
			});


			//Jos listassa olevaa tuotetta painetaan
			$('.clickable').click(function(){
					//haetaan tuotteen id
					var articleId = $(this).closest('tr').attr('data-val');
					//spinning icon
					$('#cover').addClass("loading");
					//haetaan tuotteen tiedot tecdocista
					getDirectArticlesByIds6(articleId);

					
			});


			$('.clickable').css('cursor', 'pointer');
			
			$('#myModal').on('hidden.bs.modal', function () {
				$( "#menu1" ).empty();
				$( "#menu2" ).empty();
				$( "#menu3" ).empty();
			});

		  
			//Käytetään eri muotoilua, koska dynaaminen content
			
			$(document.body).on('mouseover', '#asennusohje', function(){
				$(this).css("text-decoration", "underline");	
			});
			$(document.body).on('mouseout', '#asennusohje', function(){
				$(this).css("text-decoration", "none");	
			});

			//avaa tuotteen kuvan isona uuteen ikkunaan
			$(document.body).on('click', '.kuva', function(){
				var src = this.src;
				var w = this.naturalWidth;
				var h = this.naturalHeight;

				var left = (screen.width/2)-(w/2);
				var top = (screen.height/2)-(h/2);
				myWindow = window.open(src, src, "width="+w+",height="+h+",left="+left+",top="+top+"");
				
			}); //close click


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

			setTimeout(setSelected ,700);

			function setSelected(){
				$("#manufacturer option[value=" + manuf + "]").attr('selected', 'selected');
				$("#model option[value=" + model + "]").attr('selected', 'selected');
				$("#car option[value=" + car + "]").attr('selected', 'selected');
				$("#osaTyyppi option[value=" + osat + "]").attr('selected', 'selected');
				$("#osat_alalaji option[value=" + osat_alalaji + "]").attr('selected', 'selected');
			}

		}

		if(qs["haku"]){
			var search = qs["haku"];
			
			$("#search").val(search);
		}

		//apufunktio, jonka avulla voidaan muotoilla ajoneuvomallihaun
		//vuosiluvut parempaan muotoon
		function addSlash(text) {
			text = String(text);
			return (text.substr(0, 4) + "/" + text.substr(4));
		}
</script>

<?php

/* function filter_by_article_number($number) {

	// Korvaa jokerimerkit * ja ? merkeillä % ja _ ,joita käytetään
	// MySQL LIKE-käskyssä
	function replace_wildcards($string) {
		$replaced = str_replace('*', '%', $string);
		$replaced = str_replace('?', '_', $replaced);
		$replaced = str_replace(' ', '', $replaced);
		return $replaced;
	}

	function matches_any_number($number) {
		global $connection;
		$number = replace_wildcards($number);
		//search number
		$query = "SELECT * FROM tuote_search WHERE search_no LIKE '$number';";
		$result = mysqli_query($connection, $query) or die("ERROR1: " . mysqli_error($connection));
		$numbers = array();
		while ($row = mysqli_fetch_object($result)) {
			array_push($numbers, $row->tuote_id);
		}
		//oe
		$query = "SELECT * FROM tuote_oe WHERE oe_number LIKE '$number';";
		$result = mysqli_query($connection, $query) or die("ERROR2: " . mysqli_error($connection));
		while ($row = mysqli_fetch_object($result)) {
			array_push($numbers, $row->tuote_id);
		}
		//voitaisiin hakea myös EAN-tunnuksella, mutta jätän tekemättä,
		//koska EAN numerolla hakua ei juuri käytetä ja se syö tuotehaun tehokkuutta
		return $numbers;
	}

	function get_only_catalog_products($filtered) {
		global $connection;
		$filtered = implode("','", $filtered);
		$query = "	SELECT 	*, (hinta_ilman_alv * (1+alv_kanta.prosentti)) AS hinta
					FROM 	tuote 
					JOIN	alv_kanta
						ON	tuote.alv_kanta = alv_kanta.kanta
					WHERE 	id IN('$filtered') AND aktiivinen=1;";
		$result = mysqli_query($connection, $query) or die("Error3: " . mysqli_error($connection));
		$products = array();
		while ($row = mysqli_fetch_object($result)) {
			array_push($products, $row);
		}
		//echo count($ids);
		return $products;
	}

	$filtered_ids = array();
	
	$filtered_ids = matches_any_number($number); //etsitään kaikki linkitettyjen tuotteiden id:t
	
	$searched_products = get_only_catalog_products($filtered_ids); //etsitään vain ne tuotteet, joiden id:t ovat catalogissa

	merge_products_with_tecdoc($searched_products);

	//$unique_ids = array_unique($searched_ids);

	return $searched_products;
}
 */


/**
 * Hakee catalogista löytyvät tuotteet annetun tuotenumeron perusteella
 * 
 * @param string $number: Tuotenumero, jota vastaavat tuotteet haetaan catalogista.
 * @return array $products: Tuotteet, jotka löytyi catalogista.
 */
function filter_catalog_products($products){
	global $connection;

	$catalog_products = array();
	$ids = array();
	foreach ($products as $product) {
		$articleNo = addslashes(str_replace(" ", "", $product->articleNo));
		$query = " SELECT 	*, (hinta_ilman_alv * (1+ALV_kanta.prosentti)) AS hinta
					FROM 	tuote 
					JOIN 	ALV_kanta
						ON	tuote.ALV_kanta = ALV_kanta.kanta
					WHERE 	tuote.articleNo = '$articleNo'
					 		AND tuote.brandNo = $product->brandNo
					 		AND tuote.aktiivinen=1;";
		$result = mysqli_query($connection, $query) or die("Error:" . mysqli_error($connection));
		while ($row = mysqli_fetch_object($result)) {
			if (!in_array($row->id, $ids)) {
				array_push($ids, $row->id);
				$row->articleId = $product->articleId;
				$row->articleName = $product->articleName;
				$row->brandName = $product->brandName;
				array_push($catalog_products, $row);

			}
		}
	}
	merge_catalog_with_tecdoc($catalog_products, false);
	/*//Etsitään omasta catalogista
	$product_ids = array();
	$product_articleNos = array();
	//articleNo:t ja articleId:t listaan
	foreach ($products as $product) {
		array_push($product_articleNos, addslashes(str_replace(" ", "", $product->articleNo)));
		array_push($product_ids, $product->articleId);
	}
	$articleNos = implode("', '", $product_articleNos);
	$query = "	SELECT 	*, (hinta_ilman_alv * (1+ALV_kanta.prosentti)) AS hinta
				FROM 	tuote 
				JOIN 	ALV_kanta
					ON	tuote.ALV_kanta = ALV_kanta.kanta
				WHERE 	tuote.articleNo IN ('$articleNos') AND tuote.aktiivinen=1";
	$result = mysqli_query($connection, $query) or die("Error:" . mysqli_error($connection));
	$products = array();
	while ($row = mysqli_fetch_object($result)) {
		//vaihdetaan id (articleNo) tecdoc_id:ksi, jotta voidaan käyttää 
		//merge_products_with_tecdoc -funktiota
		$row->id = $product_ids[array_search($row->articleNo, $product_articleNos)];
		array_push($products, $row);
	}
	merge_catalog_with_tecdoc($products, true);*/
	return $catalog_products;
}



$number = isset($_GET['haku']) ? $_GET['haku'] : null;

if ($number) {
	$number = trim(addslashes(str_replace(" ", "", $number)));
	//haetaan kaikki linkitetyt tuotteet
	$products = getArticleDirectSearchAllNumbersWithState($number);
	//filtteröidään vain catalogi tuotteet ja liitetään lisätiedot
	$catalog_products = filter_catalog_products($products);
	print_results($catalog_products);

	//$ids = array();
	//haetaan vielä kaikki tuotteet jotka eivät olleet valikoimassa
	//foreach ($catalog_products as $catalog_product) {
	//	array_push($ids, $catalog_product->articleId);
	//}
	//foreach ($products as $product){
	//	if (in_array($product->articleId, $ids));
	//}

}

if(isset($_GET["manuf"])) {

	$selCar = $_GET["car"];
	$selPartType = $_GET["osat_alalaji"];

	$products = getArticleIdsWithState($selCar, $selPartType);

	global $connection;

	$catalog_products = array();
	$ids = array();
	foreach ($products as $product) {
		$articleNo = addslashes(str_replace(" ", "", $product->articleNo));
		$query = " SELECT 	*, (hinta_ilman_alv * (1+ALV_kanta.prosentti)) AS hinta
					FROM 	tuote 
					JOIN 	ALV_kanta
						ON	tuote.ALV_kanta = ALV_kanta.kanta
					WHERE 	tuote.articleNo = '$articleNo'
					 		AND tuote.brandNo = $product->brandNo
					 		AND tuote.aktiivinen=1;";
		$result = mysqli_query($connection, $query) or die("Error:" . mysqli_error($connection));
		while ($row = mysqli_fetch_object($result)) {
			if (!in_array($row->id, $ids)) {
				array_push($ids, $row->id);
				$row->articleId = $product->articleId;
				$row->articleName = $product->genericArticleName;
				$row->brandName = $product->brandName;
				array_push($catalog_products, $row);

			}
		}
	}
	merge_catalog_with_tecdoc($catalog_products, false);
	print_results($catalog_products);
}

/**
 * Tulostaa annetut tuotteet (haun tulokset) taulukossa.
 * @param array $products
 */
function print_results( array $products ) {
	echo '<div class="tulokset">';
	echo '<h2>Tulokset:</h2>';
	if (count($products) > 0) {
		echo '<table>';
		echo '<thead>';
		echo '<tr><th>Kuva</th><th>Tuotenumero</th><th>Tuote</th><th>Info</th><th style="text-align: right;">Saldo</th><th style="text-align: right;">Hinta (sis. ALV)</th><th>Kpl</th><th>Testing</th></tr>';
		echo '</thead>';
		foreach ($products as $product) {
			echo '<tr data-val="'. $product->articleId .'">';
			echo '<td class="clickable thumb"><img src="'.$product->thumburl.'" alt="'.$product->articleName.'"></td>';
			echo '<td class="clickable">'.$product->articleNo.'</td>';
			echo '<td class="clickable">'.$product->brandName.' <br> '. $product->articleName.'</td>';
			echo '<td class="clickable">';
			foreach ($product->infos as $info){
				if(!empty($info->attrName)) echo $info->attrName . " ";
				if(!empty($info->attrValue)) echo $info->attrValue . " ";
				if(!empty($info->attrUnit)) echo $info->attrUnit . " ";
				echo "<br>";
			}
			echo "</td>";
			echo '<td style="text-align: right;">' . format_integer($product->varastosaldo) . '</td>';
			echo '<td style="text-align: right;">' . format_euros($product->hinta) . '</td>';
			echo '<td style="padding-top: 0; padding-bottom: 0;">' . laske_tuotesaldo_ja_tulosta_huomautus( $product, $product ) . ' </td>';
			echo '<td class="clickable">Stuff/Things</td>';
			echo '<td class="toiminnot"><a class="nappi" href="javascript:void(0)" onclick="addToShoppingCart('.$product->id.')">Osta</a></td>';
			echo '</tr>';
		}
		echo '</table>';
	} else {
		echo '<p>Ei tuloksia.</p>';
	}
	echo '</div>';
}

/**
 * @param $product
 * @param $article
 * @return string <p> palauttaa kpl-kentän tai ostopyyntö-napin
 */
function laske_tuotesaldo_ja_tulosta_huomautus ( $product ) {
	return //Hyvin monimutkainen if-else-lauseke:
		($product->varastosaldo >= $product->minimimyyntiera || $product->varastosaldo === 0) ?
			"<input id='maara_{$product->id}' name='maara_{$product->id}' class='maara' 
				type='number' value='0' min='0'></td>"
			: "<a href='javascript:void(0);' onClick='ostopyynnon_varmistus( {$product->id} );'>
			Tuotetta ei saatavilla</a>";
}

?>

<script>
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
</script>

<form class="hidden" id="ostopyynto_form" action="#" method=post>
	<input type=hidden name="tuote_ostopyynto" value="" id="tuote_ostopyynto">
</form>

</body>
</html>
