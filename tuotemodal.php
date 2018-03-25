<?php
/**
 * Tiedosto sisältää kaiken tuotemodalin luomista ja näyttämistä varten.
 * Modalin saa näkyviin kutsumalla funktiota productModal ja
 * antamalla sille parametriksi tuotteen id:n.
 *
 * Vaatii jQueryn, Tecdocin jsonEndpointin, bootstrap3 ja image_modal.css
 */
require_once 'tecdoc_asetukset.php';?>

<!-- Tuoteikkuna Modal -->
<div class="modal fade" id="myModal">
	<div class="modal-dialog modal-lg">
		<div class="modal-content">

			<!-- Modal header -->
			<div class="modal-header">
				<ul class="nav nav-pills modal-title">
					<li class="nav-item"><a class="nav-link active" data-toggle="tab" href="#menu1" id="maintab">Tuote</a></li>
					<li class="nav-item"><a class="nav-link" data-toggle="tab" href="#menu2">Kuvat</a></li>
					<li class="nav-item"><a class="nav-link" data-toggle="tab" href="#menu3">Vertailunumerot</a></li>
					<li class="nav-item"><a class="nav-link" data-toggle="tab" href="#menu4">Autot</a></li>
				</ul>
				<button type="button" class="close" data-dismiss="modal">&times;</button>
			</div>

			<!-- Modal body -->
            <div class="modal-body">
	            <!-- Tab panes -->
				<div class="tab-content">
					<div id="menu1" class="tab-pane fade show active">
						<div class="flex_row">
							<!-- Tuotteen kuva -->
							<div id="modal-thumbnail"></div>
							<div>
								<!-- Tuotteen nimi -->
								<div id="modal-product" style="font-size: 20px;"></div>
								<!-- Tuotteen hintatiedot -->
								<div id="modal-price" style="font-size: 18px;"></div>
								<!-- Tuotteen tiedot -->
								<div id="modal-infos" style="font-size: 18px;"></div>
							</div>
						</div>
					</div>
					<div id="menu2" class="tab-pane fade text-center"></div>
					<div id="menu3" class="tab-pane fade">
						<div class="flex_row" style="vertical-align: top;">
							<div style="width: 50%;">
								<table class="vertailunumero_table" id="modal-oe">
									<thead>
										<tr><th colspan="2" class="center">OE</th></tr>
									</thead>
								</table>
							</div>
							<div style="width: 50%;">
								<table class="vertailunumero_table" id="modal-comparable">
									<thead>
										<tr><th colspan="2" class="center">Vertailunumerot</th></tr>
									</thead>
								</table>
							</div>
						</div>
					</div>
					<div id="menu4" class="tab-pane fade">
						<ul id="dd"></ul> <!-- Dropdown -->
					</div>
				</div>
			</div>

		</div>
	</div>
</div>

<!-- Spinning kuvake ladattaessa -->
<div id="cover"></div>

<!-- Kuvalle oma modal -->
<div id="imageModal" class="image_modal">
    <span class="image_modal-close" onclick="document.getElementById('imageModal').style.display='none'">&times;</span>
    <img class="image_modal-content" id="image_modal_img" src="" >
    <div id="caption"></div>
</div>

<!--suppress JSUnresolvedVariable -->
<script type="text/javascript">
    var TECDOC_MANDATOR = <?= json_encode(TECDOC_PROVIDER); ?>;
    var TECDOC_COUNTRY = <?= json_encode(TECDOC_COUNTRY); ?>;
    var TECDOC_LANGUAGE = <?= json_encode(TECDOC_LANGUAGE); ?>;
    var TECDOC_THUMB_URL = <?= json_encode(TECDOC_THUMB_URL); ?>;

    const TIMEOUT = 8000; // Aika (ms), jonka jälkeen modalin avaus keskeytetään
    let MODAL_OPEN = false; // Muuttuja ilmaisee pitäisikö modalin olla auki vai ei

    /**
     * Tuoteikkuna omille tuotteille
     * @param tuote_id  Tuotteen id tietokannassa
     * @param tecdoc_id Tuotteen tecodc_id
     */
    function productModal(/*int*/ tuote_id, /*int*/ tecdoc_id) {
        if ( !tuote_id && !tecdoc_id ) {
            return false;
        }
        // Tarkastetaan, onko modal jo auki
        if ( MODAL_OPEN ) {
            return false;
        }
        MODAL_OPEN = true;

        // Spinning icon
        let cover = $("#cover");
        cover.addClass("loader");

        // Timeout
        setTimeout(function (){
            cover.removeClass("loader");
            MODAL_OPEN = false;
        }, TIMEOUT);

        let functionName = "getDirectArticlesByIds6";
        let params = {
            "articleCountry": TECDOC_COUNTRY,
            "lang": TECDOC_LANGUAGE,
            "provider": TECDOC_MANDATOR,
            "basicData": true,
            "articleId": {"array": tecdoc_id},
            "thumbnails": true,
            "attributs": true,
            "eanNumbers": true,
            "oeNumbers": true,
            "documents": true
        };
        params = JSON.stringify(params).replace(/,/g, ", ");
        if ( !tuote_id ) {
            // Jos tuote ei ole aktivoitu, ohitetaan tietokantahaku
            tecdocToCatPort[functionName](params, createFirstPageForTecdocProduct);
        } else {
            // Haetaan tiedot tietokannasta ajaxilla
            $.post(
                "ajax_requests.php",
                {
                    tuote_modal_tiedot: true,
                    tuote_id: tuote_id
                },
                function (tuote) {
                    // Lisätään hintatiedot modaliin
	                hintatiedot(tuote);
	                // Lisätään perustiedot ensimmäiselle sivulle
                    if (tuote.tecdocissa) {
                        tecdocToCatPort[functionName](params, createFirstPageForTecdocProduct);
                    } else {
                        createFirstPageForOwnProduct(tuote);
                    }
                });
        }
    }

    /**
     * Luodaan modalin pääsivu tecdoc-tuotteelle.
     * @param response Tecdocista saatavat tuotetiedot.
     */
    function  createFirstPageForTecdocProduct( response ) {

        /**
         * Luodaan peräkkäinen html-muotoinen lista, jossa kaikki löytyneet kuvat peräkkäin
         * @param response
         * @returns {string}
         */
        function imgsToHTML( /*array*/response) {
            let imgs, img_url;
            if (response.articleThumbnails.length !== 0) {
                imgs = "";
                for (let i = 0; i < response.articleThumbnails.array.length; i++) {
                    img_url = TECDOC_THUMB_URL + response.articleThumbnails.array[i].thumbDocId + '/0/';
                    imgs += '<img src=' + img_url + ' border="1" class="tuote_img kuva"><br>';
                }
            } else {
                imgs = "<img src='img/ei-kuvaa.png' class='no-image'>";
            }
            return imgs;
        }

        /**
         * Luodaan html-muotoinen listaus tuotteen infoista
         * @param response
         * @returns {string}
         */
        function infosToHTML( /*array*/response ) {
            let infos = "";

            // Saatavuustiedot
            if (response.directArticle.articleState !== 1) {
                infos += "<span style='color:red;'>" + response.directArticle.articleStateName + "</span><br>";
            }
            // Pakkaustiedot
            if (typeof response.directArticle.packingUnit !== 'undefined') {
                infos += "Pakkauksia: " + response.directArticle.packingUnit + "<br>";
            }
            if (typeof response.directArticle.quantityPerPackingUnit !== 'undefined') {
                infos += "Kpl/pakkaus: " + response.directArticle.quantityPerPackingUnit + "<br>";
            }
            infos += "<br>";

            // Infot
            if (response.articleAttributes !== "") {
                for (let i = 0; i < response.articleAttributes.array.length; i++) {
                    if (typeof response.articleAttributes.array[i].attrName !== 'undefined') {
                        infos += response.articleAttributes.array[i].attrName;
                    }
                    if (typeof response.articleAttributes.array[i].attrValue !== 'undefined') {
                        infos += ": " + response.articleAttributes.array[i].attrValue + " ";
                    }
                    if (typeof response.articleAttributes.array[i].attrUnit !== 'undefined') {
                        infos += response.articleAttributes.array[i].attrUnit;
                    }
                    infos += "<br>";
                }
            }

            return infos;
        }

        /**
         * Luodaan dokumenttien latauslinkit html-muodossa.
         * @param response
         * @returns {string}
         */
        function getDocuments( /*array*/response ) {
            let docTypeName, docName, doc;
            let documentlink = "";

            if ( response.articleDocuments ) {
                for (let i = 0; i < response.articleDocuments.array.length; i++) {
                    //Dokumentit
                    if ( response.articleDocuments.array[i].docTypeName !== "Valokuva" &&
	                    response.articleDocuments.array[i].docTypeName !== "Kuva" ) {
                        doc = TECDOC_THUMB_URL + response.articleDocuments.array[i].docId;
                        docName = response.articleDocuments.array[i].docFileName;
                        docTypeName = response.articleDocuments.array[i].docTypeName;

                        documentlink += '<img src="./img/pdficon.png" style="margin-right:5px;margin-bottom:7px;">' +
                            '<a href="' + doc + '" download="' + docName + '">' + docTypeName + ' (PDF)</a><br>';
                    }
                }
            }

            return documentlink;
        }

        let display_img, img;
        let articleNo = response.data.array[0].directArticle.articleNo;
        // Luodaan kaikki html elementit valmiiksi, joita käytetään Modal ikkunassa
        let imgs = imgsToHTML(response.data.array[0]);
        let name = response.data.array[0].directArticle.articleName;
        let brand = response.data.array[0].directArticle.brandName;
        let infos = infosToHTML(response.data.array[0]);
        let documents = getDocuments(response.data.array[0]);

        // Display image
        if (response.data.array[0].articleThumbnails.length === 0) {
            display_img = "img/ei-kuvaa.png";
            img = '<img src=' + display_img + ' border="1" id="display_img">'
        } else {
            display_img = TECDOC_THUMB_URL + response.data.array[0].articleThumbnails.array[0].thumbDocId + '/0/';
            img = '<img src=' + display_img + ' border="1" id="display_img" class="kuva">'
        }

        //Lisätään modaliin sisältö
        $("#modal-thumbnail").empty().append(img);
        $("#modal-product").empty().append('<span style="font-weight:bold;">'+ name +'</span><br>'+ articleNo +'<br>'+ brand +'<br><br>');
        $("#modal-infos").empty().append(infos + '<br><br>'+ documents);
        $("#menu2").empty().append(imgs);
        createRestOfTheModal(response);
    }

    /**
     * Luodaan modalin pääsivu itseperustetulle tuotteelle.
     * @param data
     */
    function createFirstPageForOwnProduct( data ){
        let articleNo = data.articleNo;
        let name = data.nimi;
        let brand = data.valmistaja;
        let img_url = (data.kuva_url) ? data.kuva_url :  './img/ei-kuvaa.png';
        let img = '<img src=' + img_url + ' border="1" id="display_img" class="kuva">';
        let imgs = '<img src=' + img_url + ' border="1" id="display_img" class="kuva">';
        let infos = (data.infot) ? data.infot.split("|").join("<br>") : "";

        //Lisätään modaliin sisältö
        $("#modal-thumbnail").empty().append(img);
        $("#modal-product").empty().append('<span style="font-weight:bold;">'+ name +'</span><br>'+ articleNo +'<br>'+ brand +'<br><br>');
        $("#modal-infos").empty().append(infos);
        $("#menu2").empty().append(imgs);

        // Jos tuotteelle ei ole linkitetty vertailutuotetta, näytetään modal
        if ( !data.c_articleNo || !data.c_brandNo || !data.c_genericArticleId ) {
            showModal();
            return true;
        }

        // Haetaan tecdocista verrattavan tuotteen articleId
	    let functionName = "getArticleDirectSearchAllNumbersWithState";
	    let params = {
		    "articleCountry": TECDOC_COUNTRY,
		    "lang": TECDOC_LANGUAGE,
		    "provider": TECDOC_MANDATOR,
		    "articleNumber": data.c_articleNo,
		    "brandId" : data.c_brandNo,
		    "genericArticleId": data.c_genericArticleId,
		    "numberType": 0,
		    "searchExact": true
	    };
	    params = JSON.stringify(params).replace(/,/g,", ");
	    tecdocToCatPort[functionName](params,
		    function(response){
	            // Haetaan vertailutuotteen tiedot
	            let functionName = "getDirectArticlesByIds6";
	            let params = {
	                "articleCountry" : TECDOC_COUNTRY,
	                "lang" : TECDOC_LANGUAGE,
	                "provider" : TECDOC_MANDATOR,
	                "basicData" : true,
	                "articleId" : {"array" : response.data.array[0].articleId},
	                "thumbnails" : true,
	                "attributs" : true,
	                "eanNumbers" : true,
	                "oeNumbers" : true,
	                "documents" : true
	            };
	            params = JSON.stringify(params).replace(/,/g,", ");
	            tecdocToCatPort[functionName] (params, createRestOfTheModal);
	    });
    }

    /**
     * Luodaan loput modalista.
     * @param response
     */
    function createRestOfTheModal(response){

        /**
         * Luodaan OE-taulukon sisältö html-muodossa.
         * @param array
         * @returns {string}
         */
        function oesToHTML(array) {
            let result = "";
            let oes;
            if (array.length !== 0) {
                oes = array.array;
                for (let i = 0; i < oes.length; i++) {
	                result += "<tr><td>" + oes[i].brandName + "</td>" +
                        "<td><a href='?haku=" + oes[i].oeNumber + "&numerotyyppi=oe&exact=on'>"
		                + oes[i].oeNumber + "</a></td></tr>";
                }
            }
            return result;
        }

        /**
         * Haetaan tecdocista vertailutuotteet ja lisätään ne vertailunumero-taulukkoon.
         * @param articleNumber
         * @param genericArticleId
         */
        function getComparableNumber( /*string*/articleNumber, /*int*/genericArticleId ) {
            let functionName = "getArticleDirectSearchAllNumbersWithState";
            let params = {
                "articleCountry": TECDOC_COUNTRY,
                "lang": TECDOC_LANGUAGE,
                "provider": TECDOC_MANDATOR,
                "articleNumber": articleNumber,
                "genericArticleId": genericArticleId,
                "numberType": 10,
                "searchExact": true
            };
            params = JSON.stringify(params).replace(/,/g,", ");
            tecdocToCatPort[functionName](params,
	            function(response){
	                //Luodaan haetuista vertailunumeroista html-muotoinen taulu
	                let comparable_numbers_table_content = "";
	                let sql_values = []; //ajax-pyyntöä varten
		            let comparable_numbers;
                    let comparable_table;

	                if ( response.data ) {
                        comparable_numbers = response.data.array;
	                    for (let i = 0; i < comparable_numbers.length; i++) {
	                        if ( comparable_numbers[i].numberType === 0 || comparable_numbers[i].numberType === 3 ) {
	                            comparable_numbers_table_content += "<tr><td>" + comparable_numbers[i].brandName + "</td>" +
	                                "<td><a href='?haku=" + comparable_numbers[i].articleNo + "&numerotyyppi=comparable&exact=on'>"
	                                + comparable_numbers[i].articleNo + "</a></td></tr>";

	                            //Otetaan talteen tuotteen articleNo ja brandNo sql-kyselyä varten
                                sql_values.push(comparable_numbers[i].articleNo.toString().replace(/ /g, ''));
                                sql_values.push(comparable_numbers[i].brandNo);
	                        }
	                    }
	                }
	                comparable_table = $("#modal-comparable");
                    comparable_table.find("tr:gt(0)").remove();
	                comparable_table.append(comparable_numbers_table_content);

	                // Haetaan omat vertailutuotteet
                    $.post(
                        "ajax_requests.php",
                        {
                            tuote_modal_omat_vertailutuotteet: true,
                            tuotteet: sql_values
                        },
                        function (tuotteet) {
                            if ( tuotteet.length === 0 ) {
                                return false;
                            }
                            comparable_numbers_table_content = "";
                            for (let i = 0; i < tuotteet.length; i++) {
                                comparable_numbers_table_content += "<tr><td>" + tuotteet[i].valmistaja + "</td>" +
		                            "<td><a href='?haku=" + tuotteet[i].articleNo + "&numerotyyppi=comparable&exact=on'>"
		                            + tuotteet[i].articleNo + "</a></td></tr>";
                            }
                            comparable_table.append(comparable_numbers_table_content);
                        });

                 });
        }

        /**
         * Haetaan kaikkki tuotteeseen linkitetyt valmistajat ja lisätään ne modaliin
         * @param articleId
         */
        function getLinkedManufacturers( /*int*/articleId ) {
            let functionName = "getArticleLinkedAllLinkingTargetManufacturer";
            let params = {
                "articleCountry": TECDOC_COUNTRY,
                "provider": TECDOC_MANDATOR,
                "articleId": articleId,
                "linkingTargetType": "P"
            };
            params = JSON.stringify(params).replace(/,/g,", ");
            tecdocToCatPort[functionName](params, function (response) {
                let dropdown = $("#dd");
                dropdown.empty(); //Tyhjennetään modalin välilehti varmuuden varalta
                for (let i = 0; i < response.data.array.length; i++) {
	                dropdown.append("<li data-list-filled='false' data-articleId='"+articleId+"' data-manuId='" + response.data.array[i].manuId + "' " +
		                "style='cursor: pointer'>" + response.data.array[i].manuName + "</li>" +
		                "<div style='display:none' id=manufacturer-" + response.data.array[i].manuId + "></div>");
                }
            });
        }

        let product = response.data.array[0];
        let articleId = product.directArticle.articleId;
        let articleNo = product.directArticle.articleNo;
        let genericArticleId = product.directArticle.genericArticleId;
        let OEtable = oesToHTML(product.oenNumbers);

        //Lisätään OE-taulukko modaliin
	    let oe_table = $("#modal-oe");
        oe_table.find("tr:gt(0)").remove();
        oe_table.append(OEtable);
        // Haetaan muiden valmistajien vastaavat tuotteet (vertailunumerot) ja lisätään modaliin
        getComparableNumber(articleNo, genericArticleId);
        // Haetaan tuotteeseen linkitetyt autot ja lisätään modaliin
        getLinkedManufacturers(articleId);
        showModal(); // Näytetään modal
    }

    /**
     * Avataan tuoteikkuna ja pysäytetään spinning icon
     */
    function showModal() {
        // Poistetaan cover
        $('#cover').removeClass("loader");
        // Avataan modal
        if ( MODAL_OPEN === false ) {
            return false;
        }
        $("#myModal").modal({
            keyboard: true
        });
        // Avataan "tuote" tabi ensin
        $('#maintab').tab('show');
    }

    /**
     * Haetaan linkitetyt autot artikkelinumeron ja valmistaja-id:n perusteella
     * @param articleId
     * @param manuId
     */
    function getLinkedVehicleIds( /*int*/articleId, /*int*/manuId ) {
        let functionName = "getArticleLinkedAllLinkingTarget3";
        let params = {
            "articleCountry" : TECDOC_COUNTRY,
            "lang" : TECDOC_LANGUAGE,
            "provider" : TECDOC_MANDATOR,
            "articleId" : articleId,
            "linkingTargetManuId" : manuId,
            "linkingTargetType" : "P"
        };
        params = JSON.stringify(params).replace(/,/g,", ");
        tecdocToCatPort[functionName] (params, function (response){
            let pair;
            let articleIdPairs = [];
            let links;
            if ( response.data ) {
                links = response.data.array[0].articleLinkages.array;
                for (let i = 0; i < links.length; i++) {
                    pair = {
                        "articleLinkId" : links[i].articleLinkId,
                        "linkingTargetId" : links[i].linkingTargetId
                    };
                    articleIdPairs.push(pair);
                    if ( articleIdPairs.length === 25 ) {
                        getLinkedVehicleInfos(articleId, articleIdPairs);
                        articleIdPairs = [];
                    }
                }
                getLinkedVehicleInfos(articleId, articleIdPairs);
            }
        });
    }

    /**
     * Haetaan linkitettyjen autojen tiedot ja lisätään ne modaliin
     * @param articleId
     * @param articleIdPairs
     */
    function getLinkedVehicleInfos( /*int*/articleId, /*array*/articleIdPairs ) {

        /**
         * Apufunktio ajoneuvojen vuosimallien muotoiluun
         * @param teksti
         * @returns {string}
         */
        function addSlashes( /*string*/teksti ) {
            let text = String(teksti);
            return (text.substr(0, 4) + "/" + text.substr(4));
        }

        let functionName = "getArticleLinkedAllLinkingTargetsByIds3";
        let params = {
            "articleCountry" : TECDOC_COUNTRY,
            "lang" : TECDOC_LANGUAGE,
            "provider" : TECDOC_MANDATOR,
            "articleId" : articleId,
            "linkingTargetType" : "P",
            "linkedArticlePairs" : {
                "array" : articleIdPairs
            }
        };
        params = JSON.stringify(params).replace(/,/g,", ");
        tecdocToCatPort[functionName] (params,
	        function (response) {
			let links = response.data.array;
	        $("#manufacturer-" + links[0].linkedVehicles.array[0].manuId).removeClass("loading small");
	        for (let i = 0; i < links.length; i++) {
	            let yearTo = "";
	            if (typeof links[i].linkedVehicles.array[0].yearOfConstructionTo !== 'undefined') {
	                yearTo = addSlashes(links[i].linkedVehicles.array[0].yearOfConstructionTo);
	            }
	            $("#manufacturer-" + links[i].linkedVehicles.array[0].manuId).append("<li style='font-size: 14px;'>" +
                    links[i].linkedVehicles.array[0].modelDesc + " " +
                    links[i].linkedVehicles.array[0].carDesc + " " +
	                addSlashes(links[i].linkedVehicles.array[0].yearOfConstructionFrom + "-" +
	                    yearTo + "</li>"));
	        }
        });
    }


    /**
     * Hintatietojen lisääminen modaliin.
     * @param tuote Tuotetiedot tietokannasta.
     */
    function hintatiedot( tuote ) {
        let html, hinta, alv;
        hinta = +tuote.a_hinta;
        hinta = hinta.toFixed(2).replace(".", ",");
        alv = tuote.alv_prosentti * 100;

        html ="<p>" + hinta + " € <span class='small_note'>(sis. alv " + alv + " %)</span></p>";
        $("#modal-price").empty().append(html);
    }


    //avaa tuotteen kuvan uuteen modaliin
    $(document.body)
	    .on('click', '.kuva', function(){
	        let src = this.src;
	        let image_modal = document.getElementById('imageModal');
	        let modal_img = document.getElementById("image_modal_img");
	        let caption_text = document.getElementById("caption");
	        image_modal.style.display = "block";
	        modal_img.src = src;
	        caption_text.innerHTML = this.src;

	        image_modal.onclick = function(event) {
	            if (event.target === image_modal) {
	                image_modal.style.display = "none";
	            }
	        }
	    })
	    .on('click', '#dd > li', function(){
			//Valitaan oikea lista data-manuId:n avulla
		    let manuf = $(this);
		    let manuf_id = manuf.attr("data-manuId");
            let article_id = manuf.attr("data-articleId");
            let car_dropdown = $("#manufacturer-"+ manuf.attr('data-manuId'));
            //Haetaan autot, jos niitä ei ole vielä haettu
            if (manuf.attr("data-list-filled") === "false") {
                manuf.attr("data-list-filled", "true");
                car_dropdown.addClass("loading small");
                getLinkedVehicleIds(article_id, manuf_id);
            }
            //car_dropdown.show();
            if (car_dropdown.css("display") === "none") {
                car_dropdown.show();
            }
            else {
                car_dropdown.hide();

            }
	    });

    $("#myModal").on("hidden.bs.modal", function () {
        //Tyhjennetään modal
        $( "#modal-thumbnail" ).empty();
        $( "#modal-product" ).empty();
        $( "#modal-price" ).empty();
        $( "#modal-infos" ).empty();
        $( "#menu2" ).empty();
        $( "#modal-oe" ).find("tr:gt(0)").remove();
        $( "#modal-comparable" ).find("tr:gt(0)").remove();
        $( "#dd" ).empty();
        MODAL_OPEN = false;
    });

</script>
