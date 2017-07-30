<?php
require_once 'tecdoc_asetukset.php';
/****************************************************************
 *
 * Ajoneuvomallillahaun kaikki toiminnallisuus.
 *
 * Vaatii toimiakseen TecDocin jsonEndpointin
 *
 ****************************************************************/
?>

<div class="ajoneuvomallihaku">
    <label for="manufacturer">Ajoneuvomallilla haku:</label><br>
    <form action="" method="get" id="ajoneuvomallihaku">
        <select id="manufacturer" name="manuf" title="Valmistaja" disabled>
            <option value="">-- Valmistaja --</option>
        </select><br>
        <select id="model" name="model" title="Auton malli" disabled>
            <option value="">-- Malli --</option>
        </select><br>
        <select id="car" name="car" title="Auto" disabled>
            <option value="">-- Tyyppi --</option>
        </select><br>
        <select id="osat_ylalaji" name="osat" title="Osan tyyppi" disabled>
            <option value="">-- Tuoteryhmä --</option>
        </select><br>
        <select id="osat_alalaji" name="osat_alalaji" title="Tyypin alalaji" disabled>
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

    //hakee tecdocista autovalmistajat
    function getManufacturers() {
        let functionName = 'getManufacturers';
        let params = {
            'favouredList': 1,
            'linkingTargetType': 'P',
            'country': TECDOC_COUNTRY,
            'lang': TECDOC_LANGUAGE,
            'provider': TECDOC_MANDATOR
        };
        params = toJSON(params);
        tecdocToCatPort[functionName] (params, updateManufacturerList);
    }

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
        if ( !response.data ) { return false; }

        // Max 25 id:tä kerralla
        while( response.data.array.length ) {
            let ids = response.data.array.splice(0,25).map(function(obj){return obj.carId});
            let functionName = "getVehicleByIds3";
            let params = {
                "favouredList": 1,
                "carIds" : { "array" : ids},
                "articleCountry" : TECDOC_COUNTRY,
                "countriesCarSelection" : TECDOC_COUNTRY,
                "country" : TECDOC_COUNTRY,
                "lang" : TECDOC_LANGUAGE,
                "provider" : TECDOC_MANDATOR
            };
	        params = toJSON(params);
	        tecdocToCatPort[functionName] ( params, updateCarList );
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
    function updateManufacturerList( response ) {
        let manufacturer_select, manufacturer_option;
        response = response.data;

        //uudet tiedot listaan
        manufacturer_select = document.getElementById("manufacturer");

        if (response.array){
            for (let i = 0; i < response.array.length; i++) {
                manufacturer_option = document.createElement("option");
                manufacturer_option.text = response.array[i].manuName;
                manufacturer_option.value = response.array[i].manuId;
                manufacturer_select.options.add(manufacturer_option);
            }
        }
        manufacturer_select.removeAttribute('disabled');
    }

    // Callback function to do something with the response:
    // Päivittää alasvetolistaan uudet tiedot
    function updateModelList( response ) {
        let model_select, model_option;
        response = response.data;

        //uudet tiedot listaan
        model_select = document.getElementById("model");

        if (response.array){
            for (let i = 0; i < response.array.length; i++) {
                model_option = document.createElement("option");
                model_option.text = response.array[i].modelname
                    + "\xa0\xa0\xa0\xa0\xa0\xa0"
                    + "Year: " + addSlash(response.array[i].yearOfConstrFrom)
                    + " -> " + addSlash(response.array[i].yearOfConstrTo);
                model_option.value = response.array[i].modelId;
                model_select.options.add(model_option);
            }
        }
        model_select.removeAttribute('disabled');
    }

    // Callback function to do something with the response:
    // Päivittää alasvetolistaan uudet tiedot
    function updateCarList( response ) {
        let car_select, car_option;
        response = response.data;

        //uudet tiedot listaan
        car_select = document.getElementById("car");

        if (response.array){
            for (let i = 0; i < response.array.length; i++) {
                car_option = document.createElement("option");
                car_option.text = text = response.array[i].vehicleDetails.typeName
                    + "\xa0\xa0\xa0\xa0\xa0\xa0"
                    + "Year: " + addSlash(response.array[i].vehicleDetails.yearOfConstrFrom)
                    + " -> " + addSlash(response.array[i].vehicleDetails.yearOfConstrTo)
                    + "\xa0\xa0\xa0\xa0\xa0\xa0"
                    + response.array[i].vehicleDetails.powerKwFrom + "KW"
                    + " (" +response.array[i].vehicleDetails.powerHpFrom + "hp)";
                car_option.value = response.array[i].carId;
	            car_select.options.add(car_option);
            }
        }
        car_select.removeAttribute('disabled');
    }

	// Callback function to do something with the response:
    // Päivittää alasvetolistaan uudet tiedot
    function updatePartTypeList( response ) {
        let osat_ylalaji_select, osat_ylalaji_option;
        response = response.data;

        //uudet tiedot listaan
        osat_ylalaji_select = document.getElementById("osat_ylalaji");
        if (response.array){
            for (let i = 0; i < response.array.length; i++) {
                osat_ylalaji_option = document.createElement("option");
                osat_ylalaji_option.text = response.array[i].assemblyGroupName;
                osat_ylalaji_option.value = response.array[i].assemblyGroupNodeId;
                osat_ylalaji_select.options.add(osat_ylalaji_option);
            }
        }
        osat_ylalaji_select.removeAttribute('disabled');
    }

	// Callback function to do something with the response:
    // Päivittää alasvetolistaan uudet tiedot
    function updatePartSubTypeList( response ) {
        let osat_alalaji_select, osat_alalaji_option;
        response = response.data;

        //uudet tiedot listaan
        osat_alalaji_select = document.getElementById("osat_alalaji");
        if ( response.array ){
            for (let i = 0; i < response.array.length; i++) {
                osat_alalaji_option = document.createElement("option");
                osat_alalaji_option.text = response.array[i].assemblyGroupName;
                osat_alalaji_option.value = response.array[i].assemblyGroupNodeId;
                osat_alalaji_select.options.add(osat_alalaji_option);
            }
        }
        osat_alalaji_select.removeAttribute('disabled');
    }


    /**
     * Apufunktio, jonka avulla voidaan muotoilla ajoneuvomallihaun vuosiluvut parempaan muotoon
     * @param text
     * @returns {string}
     */
	function addSlash(text) {
	    if (typeof text === 'number' || typeof text === 'number') {
            text = text.toString();
            if (text.length === 6) {
                return text.substr(0, 4) + "/" + text.substr(4);
            }
        }
        return "";
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
            if (manufacturer_select.options.length === 1 ||
                model_select.options.length === 1 ||
                car_select.options.length === 1 ||
                osat_ylalaji_select.options.length === 1 ||
                osat_alalaji_select.options.length === 1) {
                setTimeout(teeValinnat, 100);
            } else {
                // Sallitaan valintojen tekeminen
                manufacturer_select.classList.remove('disabled');
                model_select.classList.remove('disabled');
                car_select.classList.remove('disabled');
                osat_ylalaji_select.classList.remove('disabled');
                osat_alalaji_select.classList.remove('disabled');
                // Tehdään valinnat
                manufacturer_select.querySelector('[value="'+manuf+'"]').setAttribute('selected', 'selected');
                model_select.querySelector('[value="'+model+'"]').setAttribute('selected', 'selected');
                car_select.querySelector('[value="'+car+'"]').setAttribute('selected', 'selected');
                osat_ylalaji_select.querySelector('[value="'+osat+'"]').setAttribute('selected', 'selected');
                osat_alalaji_select.querySelector('[value="'+osat_alalaji+'"]').setAttribute('selected', 'selected');
            }
        }

        let manufacturer_select = document.getElementById("manufacturer");
        let model_select = document.getElementById("model");
        let car_select = document.getElementById("car");
        let osat_ylalaji_select = document.getElementById("osat_ylalaji");
        let osat_alalaji_select = document.getElementById("osat_alalaji");

        // Estetään valintojen muuttaminen
        manufacturer_select.classList.add('disabled');
        model_select.classList.add('disabled');
        car_select.classList.add('disabled');
        osat_ylalaji_select.classList.add('disabled');
        osat_alalaji_select.classList.add('disabled');

        // Haetaan tiedot tecdocista
        getManufacturers();
        getModelSeries(manuf);
        getVehicleIdsByCriteria(manuf, model);
        getPartTypes(car);
        getChildNodes(car, osat);
        teeValinnat();
        // Virheenkäsittely
        setTimeout(function(){
            manufacturer_select.classList.remove('disabled');
            model_select.classList.remove('disabled');
            car_select.classList.remove('disabled');
            osat_ylalaji_select.classList.remove('disabled');
            osat_alalaji_select.classList.remove('disabled');
        },4000);
    }

    document.getElementById("manufacturer").addEventListener("change", function() {
        // Kun painaa jotain automerkkiä->
        let manuList = document.getElementById("manufacturer");
        let model_select = document.getElementById("model");
        let car_select = document.getElementById("car");
        let osat_ylalaji_select = document.getElementById("osat_ylalaji");
        let osat_alalaji_select = document.getElementById("osat_alalaji");

        let selManu = parseInt(manuList.options[manuList.selectedIndex].value);

        // Poistetaan vanhat tiedot
        while ( model_select.options.length - 1 ) {
            model_select.remove(1);
        }
        while ( car_select.options.length - 1 ) {
            car_select.remove(1);
        }
        while ( osat_ylalaji_select.options.length - 1 ) {
            osat_ylalaji_select.remove(1);
        }
        while ( osat_alalaji_select.options.length - 1 ) {
            osat_alalaji_select.remove(1);
        }

        // Väliaikaisesti estetään mallin, auton ja osatyyppien valinta
        model_select.setAttribute('disabled', 'disabled');
        car_select.setAttribute('disabled', 'disabled');
        osat_ylalaji_select.setAttribute('disabled', 'disabled');
        osat_alalaji_select.setAttribute('disabled', 'disabled');

        //Haetaan automallit
        if ( selManu > 0 ) {
            getModelSeries(selManu);
        }
    });//#manuf.onChange

    document.getElementById("model").addEventListener("change", function() {
        // Kun painaa jotain automallia->
        let manuList = document.getElementById("manufacturer");
        let model_select = document.getElementById("model");
        let car_select = document.getElementById("car");
        let osat_ylalaji_select = document.getElementById("osat_ylalaji");
        let osat_alalaji_select = document.getElementById("osat_alalaji");

        let selManu = parseInt(manuList.options[manuList.selectedIndex].value);
        let selModel = parseInt(model_select.options[model_select.selectedIndex].value);

		// Poistetaan vanhat tiedot
        while (car_select.options.length - 1) {
            car_select.remove(1);
        }
        while (osat_ylalaji_select.options.length - 1) {
            osat_ylalaji_select.remove(1);
        }
        while (osat_alalaji_select.options.length - 1) {
            osat_alalaji_select.remove(1);
        }

        //Väliaikaisesti estetään auton, ja osatyyppien valinta
        car_select.setAttribute('disabled', 'disabled');
        osat_ylalaji_select.setAttribute('disabled', 'disabled');
        osat_alalaji_select.setAttribute('disabled', 'disabled');

        //Haetaan tarkka automalli
        if (selModel > 0 ) {
            getVehicleIdsByCriteria(selManu, selModel);
        }
    });//#model.onChange

    document.getElementById("car").addEventListener("change", function() {
        // Kun painaa jotain autoa->
        let car_select = document.getElementById("car");
        let osat_ylalaji_select = document.getElementById("osat_ylalaji");
        let osat_alalaji_select = document.getElementById("osat_alalaji");

        let selCar = parseInt(car_select.options[car_select.selectedIndex].value);

		//Poistetaan vanhat tiedot
        while (osat_ylalaji_select.options.length - 1) {
            osat_ylalaji_select.remove(1);
        }
        while (osat_alalaji_select.options.length - 1) {
            osat_alalaji_select.remove(1);
        }

        //Väliaikaisesti estetään osatyyppien valinta
        osat_ylalaji_select.setAttribute('disabled', 'disabled');
        osat_alalaji_select.setAttribute('disabled', 'disabled');

        //Haetaan osatyyppit
        if (selCar > 0 ) {
            getPartTypes(selCar);
        }
    });//#car.onChange

    document.getElementById("osat_ylalaji").addEventListener("change", function() {
        //kun painaa jotain osan ylätyyppiä->
        let car_select = document.getElementById("car");
        let osat_ylalaji_select = document.getElementById("osat_ylalaji");
        let osat_alalaji_select = document.getElementById("osat_alalaji");

        let selCar = parseInt(car_select.options[car_select.selectedIndex].value);
        let selPartType = parseInt(osat_ylalaji_select.options[osat_ylalaji_select.selectedIndex].value);

		//Poistetaan vanhat tiedot
        while (osat_alalaji_select.options.length - 1) {
            osat_alalaji_select.remove(1);
        }

        //Väliaikaisesti estetään osan alalajin valinta
        osat_alalaji_select.setAttribute('disabled', 'disabled');

        //Haetaan tuoteryhmän alalajit
        if (selPartType > 0 ) {
            getChildNodes(selCar, selPartType);
        }
    });//#osat_ylalaji.onChange

    //Sallitaan ajoneuvomallillahaku vain, jos kaikki tiedot annettu
    document.getElementById("ajoneuvomallihaku").addEventListener("submit", function(e){
        if (document.getElementById("osat_alalaji").selectedIndex !== 0) {
            return true;
        } else {
            e.preventDefault();
            alert("Täytä kaikki kohdat ennen hakua!");
            return false;
        }
    });

    getManufacturers();

</script>
