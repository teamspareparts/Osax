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

$manufs = getManufacturers();

?>

<div class="ajoneuvomallihaku">
    Ajoneuvomallilla haku:<br>
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


<!-- Jos mietit mitä nuo kaksi juttua tuossa alhaalla tekee: ensimmäinen poistaa valitukset jokaisesta
 		tecdocin metodista; toinen poistaa jokaisen varoituksen siitä kun asettaa parametrin arvon
 		heti funktion alussa. //TODO: Pitäisikö tämä korjata? -->
<!--suppress JSUnresolvedVariable, AssignmentToFunctionParameterJS -->
<script type="text/javascript">
    var TECDOC_MANDATOR = <?= json_encode(TECDOC_PROVIDER); ?>;
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


    /**
     * Apufunktio, jonka avulla voidaan muotoilla ajoneuvomallihaun vuosiluvut parempaan muotoon
     * @param text
     * @returns {string}
     */
    function addSlash(text) {
        text = String(text);
        return (text.substr(0, 4) + "/" + text.substr(4));
    }


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

</script>
