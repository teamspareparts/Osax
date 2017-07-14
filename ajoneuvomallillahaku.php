<?php
require_once 'tecdoc.php';
/****************************************************************
 *
 * Ajoneuvomallillahaun kaikki toiminnallisuus.
 *
 * Vaatii toimiakseen jQueryn ja TecDocin jsonEndpointin
 *
 ****************************************************************/

/**
 * Palauttaa Autovalmistajat selectiin.
 * @param array $manufs <p> automerkit
 * @return string <p> option lista automerkeistä, HTML:nä.
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

$manufs = getManufacturers();

?>

<div class="ajoneuvomallihaku">
    <label for="manufacturer">Ajoneuvomallilla haku:</label><br>
    <form action="" method="get" id="ajoneuvomallihaku">
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
        <select id="osat_ylalaji" name="osat" disabled="disabled" title="Osan tyyppi">
            <option value="">-- Tuoteryhmä --</option>
        </select><br>
        <select id="osat_alalaji" name="osat_alalaji" disabled="disabled" title="Tyypin alalaji">
            <option value="">-- Tuoteryhmä --</option>
        </select>
        <br>
        <input type="submit" class="nappi" value="HAE" id="ajoneuvohaku">
    </form>
</div>


<!-- Jos mietit mitä nuo kaksi juttua tuossa alhaalla tekee: ensimmäinen poistaa valitukset jokaisesta
 		tecdocin metodista; toinen poistaa jokaisen varoituksen siitä kun asettaa parametrin arvon
 		heti funktion alussa. //TODO: Pitäisikö tämä korjata? -->
<!--suppress JSUnresolvedVariable, AssignmentToFunctionParameterJS -->
<script type="text/javascript">
    var TECDOC_MANDATOR = <?= json_encode(TECDOC_PROVIDER); ?>;
    var TECDOC_COUNTRY = <?= json_encode(TECDOC_COUNTRY); ?>;
    var TECDOC_LANGUAGE = <?= json_encode(TECDOC_LANGUAGE); ?>;

    //hakee tecdocista automallit annetun valmistaja id:n perusteella
    function getModelSeries( manufacturerID ) {
        let functionName = "getModelSeries";
        let params = {
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
        let functionName = "getVehicleIdsByCriteria";
        let params = {
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
        let params, i;
        let functionName = "getVehicleByIds3";
        let ids = [], IDarray = [];

        for ( i = 0; i < response.data.array.length; i++ ) {
            ids.push(response.data.array[i].carId);
        }

        //max 25 id:tä kerralla
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
        let functionName = "getChildNodesAllLinkingTarget2";
        let params = {
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
        let functionName = "getChildNodesAllLinkingTarget2";
        let params = {
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

    // Create JSON String and put a blank after every ','
    // Muuttaa tecdociin lähetettävän pyynnön JSON-muotoon
    function toJSON( obj ) {
        return JSON.stringify(obj).replace(/,/g,", ");
    }

    // Callback function to do something with the response:
    // Päivittää alasvetolistaan uudet tiedot
    function updateModelList( response ) {
        let model, text, yearTo, i, modelList;
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
        let car, text, yearTo, i, carList;
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

	// Callback function to do something with the response:
    // Päivittää alasvetolistaan uudet tiedot
    function updatePartTypeList( response ) {
        let partType, i, partTypeList;
        response = response.data;

        //uudet tiedot listaan
        partTypeList = document.getElementById("osat_ylalaji");
        if (response.array){
            for (i = 0; i < response.array.length; i++) {
                partType = new Option(response.array[i].assemblyGroupName, response.array[i].assemblyGroupNodeId);
                partTypeList.options.add(partType);
            }
        }
        $('#osat_ylalaji').removeAttr('disabled');
    }

	// Callback function to do something with the response:
    // Päivittää alasvetolistaan uudet tiedot
    function updatePartSubTypeList( response ) {
        let subPartType, i, subPartTypeList;
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

    /**
     * Taytetaan selectit annettujen parametrien perusteella.
     * Valitaan listasta haluttu kohta.
     * @param manuf
     * @param model
     * @param car
     * @param osat
     * @param osat_alalaji
     */
    function taytaAjoneuvomallillahakuValinnat (/*int*/manuf, /*int*/model, /*int*/car, /*int*/osat, /*int*/osat_alalaji) {

        /**
         * Odottaa niin kauan, kunnes kaikki tiedot on haettu tecdocista.
         * Tämän jälkeen päivittää ajoneuvomallillahakuun oikeat valinnat.
         */
        function teeValinnat() {
            if ($("#manufacturer").children('option').length === 1 ||
                $("#model").children('option').length === 1 ||
                $("#car").children('option').length === 1 ||
                $("#osat_ylalaji").children('option').length === 1 ||
                $("#osat_alalaji").children('option').length === 1) {
                setTimeout(teeValinnat, 100);
            } else {
                $("#manufacturer").find("option[value=" + manuf + "]").attr('selected', 'selected');
                $("#model").find("option[value=" + model + "]").attr('selected', 'selected');
                $("#car").find("option[value=" + car + "]").attr('selected', 'selected');
                $("#osat_ylalaji").find("option[value=" + osat + "]").attr('selected', 'selected');
                $("#osat_alalaji").find("option[value=" + osat_alalaji + "]").attr('selected', 'selected');
            }
        }

        // Haetaan tiedot tecdocista
        getModelSeries(manuf);
        getVehicleIdsByCriteria(manuf, model);
        getPartTypes(car);
        getChildNodes(car, osat);

        teeValinnat();
    }



    $("#manufacturer").on("change", function(){
        //kun painaa jotain automerkkiä->
        let manuList = document.getElementById("manufacturer");
        let selManu = parseInt(manuList.options[manuList.selectedIndex].value);

        //Poistetaan vanhat tiedot
        let modelList = document.getElementById("model");
        let carList = document.getElementById("car");
        let partTypeList = document.getElementById("osat_ylalaji");
        let subPartTypeList = document.getElementById("osat_alalaji");
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

        //väliaikaisesti estetään mallin, auton ja osatyyppien valinta
        $('#model').attr('disabled', 'disabled');
        $('#car').attr('disabled', 'disabled');
        $('#osat_ylalaji').attr('disabled', 'disabled');
        $('#osat_alalaji').attr('disabled', 'disabled');

        //Haetaan automallit
        if ( selManu > 0 ) {
            getModelSeries(selManu);
        }
    });//#manuf.onChange

    $("#model").on("change", function(){
        //kun painaa jotain automallia->
        let manuList = document.getElementById("manufacturer");
        let modelList = document.getElementById("model");
        let selManu = parseInt(manuList.options[manuList.selectedIndex].value);
        let selModel = parseInt(modelList.options[modelList.selectedIndex].value);

		//Poistetaan vanhat tiedot
        let carList = document.getElementById("car");
		let partTypeList = document.getElementById("osat_ylalaji");
		let subPartTypeList = document.getElementById("osat_alalaji");
        while (carList.options.length - 1) {
            carList.remove(1);
        }
        while (partTypeList.options.length - 1) {
            partTypeList.remove(1);
        }
        while (subPartTypeList.options.length - 1) {
            subPartTypeList.remove(1);
        }

        //Väliaikaisesti estetään auton, ja osatyyppien valinta
        $('#car').attr('disabled', 'disabled');
        $('#osat_ylalaji').attr('disabled', 'disabled');
        $('#osat_alalaji').attr('disabled', 'disabled');

        //Haetaan tarkka automalli
        if (selModel > 0 ) {
            getVehicleIdsByCriteria(selManu, selModel);
        }
    });//#model.onChange

    $("#car").on("change", function(){
        //kun painaa jotain autoa->
        let carList = document.getElementById("car");
        let selCar = parseInt(carList.options[carList.selectedIndex].value);

		//Poistetaan vanhat tiedot
        let subPartTypeList = document.getElementById("osat_alalaji");
        let partTypeList = document.getElementById("osat_ylalaji");
        while (partTypeList.options.length - 1) {
            partTypeList.remove(1);
        }
        while (subPartTypeList.options.length - 1) {
            subPartTypeList.remove(1);
        }

        //Väliaikaisesti estetään osatyyppien valinta
        $('#osat_ylalaji').attr('disabled', 'disabled');
        $('#osat_alalaji').attr('disabled', 'disabled');

        //Haetaan osatyyppit
        if (selCar > 0 ) {
            getPartTypes(selCar);
        }
    });//#car.onChange

    $("#osat_ylalaji").on("change", function(){
        //kun painaa jotain osan ylätyyppiä->
        let carList = document.getElementById("car");
        let partTypeList = document.getElementById("osat_ylalaji");
        let selCar = parseInt(carList.options[carList.selectedIndex].value);
        let selPartType = parseInt(partTypeList.options[partTypeList.selectedIndex].value);

		//Poistetaan vanhat tiedot
        let subPartTypeList = document.getElementById("osat_alalaji");
        while (subPartTypeList.options.length - 1) {
            subPartTypeList.remove(1);
        }

        //Väliaikaisesti estetään osan alalajin valinta
        $('#osat_alalaji').attr('disabled', 'disabled');

        //Haetaan tuoteryhmän alalajit
        if (selPartType > 0 ) {
            getChildNodes(selCar, selPartType);
        }
    });//#osat_ylalaji.onChange


    //Sallitaan ajoneuvomallillahaku vain, jos kaikki tiedot annettu
	$("#ajoneuvomallihaku").submit(function(e) {
        if (document.getElementById("osat_alalaji").selectedIndex !== 0) {
            return true;
        } else {
            e.preventDefault();
            alert("Täytä kaikki kohdat ennen hakua!");
            return false;
        }
    }); //#ajoneuvomallihaku.submit

</script>
