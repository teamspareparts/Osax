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
<div class="modal fade" id="myModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
	<div class="modal-dialog" role="document" style="top: 10%">
		<div class="modal-content">
			<div class="modal-header" style="height: 30pt;">
				<button type="button" class="close" data-dismiss="modal" aria-label="Close">
					<span aria-hidden="true">&times;</span></button>
				<ul class="nav nav-pills modal-title" id="modalnav">
					<li class="active"><a data-toggle="tab" href="#menu1" id="maintab">Tuote</a></li>
					<li><a data-toggle="tab" href="#menu2">Kuvat</a></li>
					<li><a data-toggle="tab" href="#menu3">Vertailunumerot</a></li>
					<li><a data-toggle="tab" href="#menu4">Autot</a></li>
				</ul>
			</div>


            <div class="modal-body">
				<div class="tab-content">
					<div id="menu1" class="tab-pane fade in active"></div>
					<div id="menu2" class="tab-pane fade text-center"></div>
					<div id="menu3" class="tab-pane fade"></div>
					<div id="menu4" class="tab-pane fade">
						<div id="dd" class="car_dropdown"></div> <!-- Dropdown -->
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

    let MODAL_OPEN = false;

    /**
     * Tuoteikkuna
     * @param id    Tuotteen articleId
     */
    function productModal ( /*int*/id ) {
        if ( MODAL_OPEN ) {
			return false;
        }
        MODAL_OPEN = true;

        //spinning icon (max 5s)
        let cover = $("#cover");
        cover.addClass("loading");
        setTimeout(function (){ cover.removeClass("loading"); MODAL_OPEN = false; }, 5000);

        //Haetaan tuotteen tiedot
        let functionName = "getDirectArticlesByIds6";
        let params = {
            "articleCountry" : TECDOC_COUNTRY,
            "lang" : TECDOC_LANGUAGE,
            "provider" : TECDOC_MANDATOR,
            "basicData" : true,
            "articleId" : {"array" : id},
            "thumbnails" : true,
            "attributs" : true,
            "eanNumbers" : true,
            "oeNumbers" : true,
            "documents" : true
        };
        params = JSON.stringify(params).replace(/,/g,", ");
        tecdocToCatPort[functionName] (params, createModal);
    }

    /**
     * Luodaan modalin sisältö ja näytetään modal
     * @param response
     */
    function createModal( /*array*/response ) {

        /**
         * Luodaan html-muotoinen vertailunumerotaulukko
         * @param array
         * @returns {string}
         */
        function oesToHTML(array) {
            let result = "";
            if (array.length !== 0) {
                array = array.array;
                result = "" +
                    '<div style="display:inline-block; width:50%;">' +
                    '	<table class="vertailunumero_table">' +
                    '		<th colspan="2" class="center">OE</th>';
                for (let i = 0; i < array.length; i++) {
                    result += "<tr>";
                    result += "" +
                        "<td>" + array[i].brandName + "</td>" +
                        "<td><a href='?haku=" + array[i].oeNumber + "&numerotyyppi=oe&exact=on' style='color:black;'>" + array[i].oeNumber + "</a></td>";
                    result += "</tr>";
                }
                result += "</table>";
            }

            return result;
        }

        /**
         * Luodaan peräkkäinen html-muotoinen lista, jossa kaikki löytyneet kuvat peräkkäin
         * @param response
         * @returns {string}
         */
        function imgsToHTML( /*array*/response) {
            let imgs, img_url, thumb_id;
            if (response.articleThumbnails.length !== 0) {
                imgs = "";
                for (let i = 0; i < response.articleThumbnails.array.length; i++) {
                    thumb_id = response.articleThumbnails.array[i].thumbDocId;
                    img_url = TECDOC_THUMB_URL + thumb_id + '/0/';
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

            //infot
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
         * Tehdään dokumenttien latauslinkit html-muodossa.
         * @param response
         * @returns {string}
         */
        function getDocuments( /*array*/response ) {
            let docTypeName, docName, doc;
            let documentlink = "";

            if (response.articleDocuments !== "") {
                for (let i = 0; i < response.articleDocuments.array.length; i++) {
                    //Dokumentit
                    if (response.articleDocuments.array[i].docTypeName !== "Valokuva" && response.articleDocuments.array[i].docTypeName !== "Kuva") {
                        doc = TECDOC_THUMB_URL + response.articleDocuments.array[i].docId;
                        docName = response.articleDocuments.array[i].docFileName;
                        docTypeName = response.articleDocuments.array[i].docTypeName;

                        documentlink += '<img src="img/pdficon.png" style="margin-right:5px;margin-bottom:7px;">' +
                            '<a href="' + doc + '" download="' + docName + '" id="asennusohje">' +
                            '' + docTypeName + ' (PDF)</a><br>';
                    }
                }
            }

            return documentlink;
        }

        /**
         * Haetaan vertailunumerot
         * @param articleNumber
         */
        function getComparableNumber( /*string*/articleNumber ) {
            let functionName = "getArticleDirectSearchAllNumbersWithState";
            let params = {
                "articleCountry": TECDOC_COUNTRY,
                "lang": TECDOC_LANGUAGE,
                "provider": TECDOC_MANDATOR,
                "articleNumber": articleNumber,
                "numberType": 10,
                "searchExact": true
            };
            params = JSON.stringify(params).replace(/,/g,", ");
            tecdocToCatPort[functionName](params, addComparableNumbersToModal);
        }

        /**
         * Lisätään vertailunumerotaulukko modaliin
         * @param response
         */
        function addComparableNumbersToModal( /*array*/response ) {
            let comparableNumbers;

            //Luodaan haetuista vertailunumeroista html-muotoinen taulu
            comparableNumbers = "<div style='display:inline-block; width:49%; vertical-align:top;'>" +
                "<table class='vertailunumero_table'>" +
                "<th colspan='2' class='center'>Vertailunumerot</th>";

            if ( response.data ) {
                response = response.data.array;
                for (let i = 0; i < response.length; i++) {
                    if ( response[i].numberType === 0 || response[i].numberType === 3 ) {
                        comparableNumbers += "<tr><td style='font-size:14px;'>" + response[i].brandName + "</td>" +
                            "<td style='font-size:14px;'><a href='?haku=" + response[i].articleNo + "&numerotyyppi=comparable&exact=on' style='color:black;'>"
                            + response[i].articleNo + "</a></td></tr>";
                    }
                }
            }
            comparableNumbers += "</table>";

            //lisätään modaliin
            $("#menu3").append(comparableNumbers);
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
                $('#dd').empty(); //Tyhjennetään modalin välilehti varmuuden varalta
                for (let i = 0; i < response.data.array.length; i++) {
                    $(".car_dropdown").append("<span style='cursor:pointer; display:block;' onClick=\"showCars(this," + articleId + ")\" data-list-filled='false' data-manuId=" + response.data.array[i].manuId + ">" + response.data.array[i].manuName + "</span>" +
                        "<div style='display:none' id=manufacturer-" + response.data.array[i].manuId + "></div>");
                }
            })
        }


        /**
         * Avataan tuoteikkuna ja pysäytetään spinning icon
         */
        function showModal() {
            // Poistetaan cover
            $('#cover').removeClass("loading");
            // Avataan modal
            $("#myModal").modal({
                keyboard: true
            });
            // Avataan aina "tuote" tabi ensin
            $('#maintab').tab('show');
        }

        let documents, infos, brand, articleNo, name, OEtable, imgs, articleId, display_img, display_img_id, img;
        response = response.data.array[0];
        articleId = response.directArticle.articleId;

        //Luodaan kaikki html elementit valmiiksi, joita käytetään Modal ikkunassa
        imgs = imgsToHTML(response);
        OEtable = oesToHTML(response.oenNumbers);
        name = response.directArticle.articleName;
        articleNo = response.directArticle.articleNo;
        brand = response.directArticle.brandName;
        infos = infosToHTML(response);
        documents = getDocuments(response);

        //display image
        if (response.articleThumbnails.length === 0) {
            display_img = "img/ei-kuvaa.png";
            img = '<img src=' + display_img + ' border="1" id="display_img">'
        } else {
            display_img_id = response.articleThumbnails.array[0].thumbDocId;
            display_img = TECDOC_THUMB_URL + display_img_id + '/0/';
            img = '<img src=' + display_img + ' border="1" id="display_img" class="kuva">'
        }

        //Lisätään modaliin sisältö
        $("#menu1").empty().append('\
			<div class="flex_row">\
			    <div style="display: inline-block;">\
			        '+ img +'\
			    </div>\
			    <div>\
			        <div style="font-size: 20px;">\
			            <span style="font-weight:bold;">'+ name +'</span><br>'+ articleNo +'<br>'+ brand +'<br>\
			        </div>\
			        <div style="font-size: 18px;">\
			            <br>'+ infos + '<br><br>'+ documents +'\
			        </div>\
			    </div>\
			</div>\
		');
        $("#menu2").empty().append(imgs);
        $("#menu3").empty().append(OEtable);
        $("#dd").empty();

        //Haetaan muiden valmistajien vastaavat tuotteet (vertailunumerot) ja lisätään modaliin
        getComparableNumber(articleNo);
        //Haetaan tuotteeseen linkitetyt autot ja lisätään modaliin
        getLinkedManufacturers(articleId);
        showModal(); //näytetään modal

    }

    /**
     * Näytetään linkitetyt autot
     * @param elmnt
     * @param articleId
     */
	function showCars( elmnt, /*int*/articleId ){
        //Valitaan DIV painetun elementin data-manuId:n avulla
        let car_dropdown = $("#manufacturer-"+elmnt.getAttribute('data-manuId'));
		//Haetaan autot, jos niitä ei ole vielä haettu
		if (elmnt.getAttribute("data-list-filled") === "false") {
			elmnt.setAttribute("data-list-filled", "true");
			car_dropdown.addClass("loader");
			getLinkedVehicleIds(articleId, elmnt.getAttribute("data-manuId"));
		}
		//car_dropdown.show();
		if (car_dropdown.css("display") === "none") {
			car_dropdown.show();
		}
		else {
            car_dropdown.hide();

		}
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
            if ( response.data ) {
                response = response.data.array[0];
                for (let i = 0; i < response.articleLinkages.array.length; i++) {
                    pair = {
                        "articleLinkId" : response.articleLinkages.array[i].articleLinkId,
                        "linkingTargetId" : response.articleLinkages.array[i].linkingTargetId
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
     * Haetaan linkitettyjen autojen tiedot
     * @param articleId
     * @param articleIdPairs
     */
    function getLinkedVehicleInfos( /*int*/articleId, /*array*/articleIdPairs ) {
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
        tecdocToCatPort[functionName] (params, addLinkedVehiclesToModal);
    }

    /**
     * Lisätään haetut autojen tiedot modaliin
     * @param response
     */
    function addLinkedVehiclesToModal( /*array*/response ) {
        $("#manufacturer-"+response.data.array[0].linkedVehicles.array[0].manuId).removeClass("loader");
        for (let i=0; i<response.data.array.length ; i++) {
            let yearTo = "";
            if (typeof response.data.array[i].linkedVehicles.array[0].yearOfConstructionTo !== 'undefined') {
                yearTo = addSlashes(response.data.array[i].linkedVehicles.array[0].yearOfConstructionTo);
            }
            $("#manufacturer-" + response.data.array[i].linkedVehicles.array[0].manuId).append("<li style='font-size: 14px;'>" +
                response.data.array[i].linkedVehicles.array[0].modelDesc + " " +
                response.data.array[i].linkedVehicles.array[0].carDesc + " " +
                addSlashes(response.data.array[i].linkedVehicles.array[0].yearOfConstructionFrom + "-" +
                    yearTo + "</li>"));
        }
    }

    /**
     * Apufunktio, jonka avulla voidaan muotoilla ajoneuvomallihaun vuosiluvut parempaan muotoon
     * @param text
     * @returns {string}
     */
    function addSlashes( /*string*/text ) {
        text = String(text);
        return (text.substr(0, 4) + "/" + text.substr(4));
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
        .on('mouseover', '#asennusohje', function(){
            $(this).css("text-decoration", "underline");
        })
        .on('mouseout', '#asennusohje', function(){
            $(this).css("text-decoration", "none");
        });

    $("#myModal").on("hidden.bs.modal", function () {
        //Tyhjennetään modal
        $( "#menu1" ).empty();
        $( "#menu2" ).empty();
        $( "#menu3" ).empty();
        $( "#dd" ).empty();
        MODAL_OPEN = false;
    });

</script>
