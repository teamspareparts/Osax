<!DOCTYPE html>
<html>
<head>
<link rel="stylesheet" href="css/styles.css">
<script
	src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.0/jquery.min.js"></script>
<script
	src="http://webservicepilot.tecdoc.net/pegasus-3-0/services/TecdocToCatDLB.jsonEndpoint?js"></script>
<script>
	var TECDOC_MANDATOR = 149;
	var TECDOC_DEBUG = false;
	var TECDOC_COUNTRY = 'FI';
	var TECDOC_LANGUAGE = 'FI';
</script>
</head>
<body> 


<form action="ajoneuvohakutesti.php" method="post" id="ajoneuvomallihaku">
	<select id="manufacturer" name="manuf">
		<option value="">-- Valmistaja --</option> 
	<?php
	require 'tecdoc.php';
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
	
	<input type="submit" value="HAE">
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
        //params = toJSON(params);
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


    // Create JSON String and put a blank after every ',':
    function toJSON(obj) {        
        return JSON.stringify(obj).replace(/,/g,", ");
    }



    //debuggaukseen....
    function displayText(label, obj) {
        // Create element to display:
        var element = document.createElement('div');
        // Create element as 'label' and append it:
        var header = document.createElement('div');
        header.innerHTML = label + ":";
        header.style.fontWeight = 'bold';
        element.appendChild(header);
        
        // Create element with data to display and append it:
        var display = document.createElement('span');
        display.appendChild(document.createTextNode(obj));
        element.appendChild(display);
        
        // Append element to body:
        document.body.appendChild(element);
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
			    	var partType = new Option(response.array[i].shortCutName, response.array[i].shortCutId);
					partTypeList.options.add(partType);
			    }
		    }
		    
		    $('#osaTyyppi').removeAttr('disabled');
	          
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
				while (modelList.options.length - 1) {
					modelList.remove(1);
				}	
				while (carList.options.length - 1) {
					carList.remove(1);
				}
				while (partTypeList.options.length - 1) {
					partTypeList.remove(1);
				}	


				//väliaikaisesti estetään modelin ja auton valinta
				$('#model').attr('disabled', 'disabled');
				$('#car').attr('disabled', 'disabled');
				$('#osaTyyppi').attr('disabled', 'disabled');
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

				$('#car').attr('disabled', 'disabled');
				$('#osaTyyppi').attr('disabled', 'disabled');
				
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

				$('#osaTyyppi').attr('disabled', 'disabled');
				if (selCar > 0 ) {
					getShortCuts2(selCar);
				}
			});
			


			//annetaan hakea vain jos kaikki tarvittavat tiedot on annettu
			$("#ajoneuvomallihaku").submit(function(e) {
			    if (document.getElementById("osaTyyppi").selectedIndex != 0) {
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
	</script>
	
	<?php if(isset($_POST["manuf"])) echo "Tähän kaikki hakutulokset"?>


</body>
</html>
