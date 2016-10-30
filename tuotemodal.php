<!-------------------------------------------------------------------------

    Tiedosto sisältää kaiken tuotemodalin luomista ja näyttämistä varten.
    Modalin saa näkyviin kutsumalla funktiota productModal ja
    antamalla sille parametriksi tuotteen id:n.

---------------------------------------------------------------------------->


<!-- Tuoteikkuna Modal -->
<div class="modal fade" id="myModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
	<div class="modal-dialog" role="document" style="top:50px;">
		<div class="modal-content">
			<div class="modal-header" style="height: 35px;">
				<button type="button" class="close" style="display: inline-block;" data-dismiss="modal" aria-label="Close">
					<span aria-hidden="true">&times;</span></button>
				<ul class="nav nav-pills" id="modalnav" style="position:relative; top:-20px; max-width: 450px;">
					<li class="active"><a data-toggle="tab" href="#menu1" id="maintab">Tuote</a></li>
					<li><a data-toggle="tab" href="#menu2">Kuvat</a></li>
					<li><a data-toggle="tab" href="#menu3">Vertailunumerot</a></li>
					<li><a data-toggle="tab" href="#menu4">Autot</a></li>
				</ul>
			</div>

			<div class="modal-body" style="margin-top:-20px;">
				<div class="tab-content">
					<div id="menu1" class="tab-pane fade in active"></div>
					<div id="menu2" class="tab-pane fade text-center"></div>
					<div id="menu3" class="tab-pane fade"></div>
					<div id="menu4" class="tab-pane fade">
						<br><div id="dd" class="car_dropdown"></div> <!-- Dropdown -->
					</div>
				</div>
			</div>

		</div>
	</div>
</div>
<!-- Spinning kuvake ladattaessa -->
<div id="cover"></div>



<script type="text/javascript">
    /**
     * Haetaan tuotteen tiedot annetulla id:llä
     * @param id
     */
    function productModal ( id ) {

        //spinning icon
        $("#cover").addClass("loading");

        var functionName = "getDirectArticlesByIds6";
        var params = {
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
    function createModal( response ) {

        function oesToHTML(array) {
            var i;
            var result = "";
            if (array.length !== 0) {
                array = array.array;
                result = "" +
                    '<div style="display:inline-block; width:50%;">' +
                    '	<table style="margin-left:auto; margin-right:auto;">' +
                    '		<th colspan="2" class="text-center">OE</th>';
                for (i = 0; i < array.length; i++) {
                    result += "<tr>";
                    result += "" +
                        "<td style='font-size:14px;'>" + array[i].brandName + "</td>" +
                        "<td style='font-size:14px;'><a href='?haku=" + array[i].oeNumber + "&numerotyyppi=oe&exact=on' style='color:black;'>" + array[i].oeNumber + "</a></td>";
                    result += "</tr>";
                }
                result += "</table>";
            }

            return result;
        }

        //Tehdään peräkkäinen html-muotoinen lista, jossa kaikki löytyneet kuvat peräkkäin
        function imgsToHTML(response) {
            var i, img, thumb_id;
            var imgs = "<img src='img/ei-kuvaa.png' class='no-image' />";
            if (response.articleThumbnails.length !== 0) {
                imgs = "";
                for (i = 0; i < response.articleThumbnails.array.length; i++) {
                    thumb_id = response.articleThumbnails.array[i].thumbDocId;
                    img = TECDOC_THUMB_URL + thumb_id + '/0/';
                    imgs += '<img src=' + img + ' border="1" class="tuote_img kuva" /><br>';
                }
            }

            return imgs;
        }

        //Tehdään html-muotoinen listaus tuotteen infoista
        function infosToHTML(response) {
            var i;
            var infos = "";

            //saatavuustiedot
            if (response.directArticle.articleState != 1) {
                infos += "<span style='color:red;'>" + response.directArticle.articleStateName + "</span><br>";
            }
            //pakkaustiedot
            if (typeof response.directArticle.packingUnit != 'undefined') {
                infos += "Pakkauksia: " + response.directArticle.packingUnit + "<br>";
            }
            if (typeof response.directArticle.quantityPerPackingUnit != 'undefined') {
                infos += "Kpl/pakkaus: " + response.directArticle.quantityPerPackingUnit + "<br>";
            }

            infos += "<br>";

            //infot
            if (response.articleAttributes != "") {
                for (i = 0; i < response.articleAttributes.array.length; i++) {
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
            }

            return infos;
        }

        //Tehdään dokumenttien latauslinkit, jos olemassa
        function getDocuments(response) {
            var docTypeName, docName, doc, i;
            var documentlink = "";

            if (response.articleDocuments != "") {
                for (i = 0; i < response.articleDocuments.array.length; i++) {
                    //Dokumentit
                    if (response.articleDocuments.array[i].docTypeName != "Valokuva" && response.articleDocuments.array[i].docTypeName != "Kuva") {
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

        function getComparableNumber(articleNumber) {
            var functionName = "getArticleDirectSearchAllNumbersWithState";
            var params = {
                "articleCountry": TECDOC_COUNTRY,
                "lang": TECDOC_LANGUAGE,
                "provider": TECDOC_MANDATOR,
                "articleNumber": articleNumber,
                "numberType": 3,
                "searchExact": true
            };
            params = toJSON(params);
            tecdocToCatPort[functionName](params, addComparableNumbersToModal);
        }

        //Lisätään vertailunumerot modaliin
        function addComparableNumbersToModal(response) {

            // Any better now? ;D
            // :: slow_clap
            var i, comparableNumbers;

            //Luodaan haetuista vertailunumeroista html-muotoinen taulu
            if (response.data != "") {
                response = response.data.array;
            }
            //luodaan taulu ja lisätään siihen tuote, jota on haettu
            //(Tätä tuotetta ei palauteta vertailunumerojen mukana)
            comparableNumbers = "<div style='display:inline-block; width:49%; vertical-align:top;'>" +
                "<table style='margin-left:auto; margin-right:auto;'>" +
                "<th colspan='2' class='text-center'>Vertailunumerot</th>" +
                "<tr><td style='font-size:14px;'>" + brand + "</td>" +
                "<td style='font-size:14px;'><a href='?haku=" + articleNo + "&numerotyyppi=comparable&exact=on' style='color:black;'>" + articleNo + "</a></td></tr>";

            if (response.length !== 0) {
                for (i = 0; i < response.length; i++) {
                    comparableNumbers += "<tr>";
                    comparableNumbers += "<td style='font-size:14px;'>" + response[i].brandName + "</td>" +
                        "<td style='font-size:14px;'><a href='?haku=" + response[i].articleNo + "&numerotyyppi=comparable&exact=on' style='color:black;'>"
                        + response[i].articleNo + "</a></td>";
                    comparableNumbers += "</tr>";
                }
                comparableNumbers += "</table>";
            }


            //lisätään modaliin
            $("#menu3").append('\ ' + comparableNumbers + '\ ');
        }


        function getLinkedManufacturers(articleId) {
            var functionName = "getArticleLinkedAllLinkingTargetManufacturer";
            var params = {
                "articleCountry": TECDOC_COUNTRY,
                "provider": TECDOC_MANDATOR,
                "articleId": articleId,
                "linkingTargetType": "P"
            };
            params = toJSON(params);
            tecdocToCatPort[functionName](params, function (response) {
                var i;
                for (i = 0; i < response.data.array.length; i++) {
                    $(".car_dropdown").append("<span style='cursor:pointer; display:block;' onClick=\"showCars(this," + articleId + ")\" data-list-filled='false' data-manuId=" + response.data.array[i].manuId + ">" + response.data.array[i].manuName + "</span>" +
                        "<div class='car_dropdown_content' id=manufacturer-" + response.data.array[i].manuId + "></div>");
                }
                //getLinkedVehicleIds(articleId);
            })
        }


        /**
         * Avataan tuoteikkuna ja pysäytetään spinning icon
         */
        function showModal() {
            //lopetetaan spinning iconin näyttäminen
            $('#cover').removeClass("loading");
            //avataan modal
            $("#myModal").modal({
                keyboard: true
            });
            //avataan aina "tuote" tabi ensin
            $('#maintab').tab('show');
        }

        var documents, infos, brand, articleNo, name, OEtable, imgs, articleId, display_img, display_img_id, img;
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
        display_img_id = "";
        if (response.articleThumbnails.length === 0) {
            display_img = "img/ei-kuvaa.png";
            img = '<img src=' + display_img + ' border="1" id="display_img"/>'
        } else {
            display_img_id = response.articleThumbnails.array[0].thumbDocId;
            display_img = TECDOC_THUMB_URL + display_img_id + '/0/';
            img = '<img src=' + display_img + ' border="1" id="display_img" class="kuva"/>'
        }

        //Lisätään tuote modaliin sisältö
        $("#menu1").append('\
			<br>\
			<div class="flex_row">\
			    <div style="display: inline-block;">\
			        ' + img + '\
			    </div> \
			    <div>\
			        <div style="font-size: 20px;">\
			            <span style="font-weight:bold;">' + name + '</span><br>' + articleNo + '<br>' + brand + '<br> \
			        </div>\
			        <div style="font-size: 18px;">\
			            <br>' + infos + '<br><br>' + documents + ' \
			        </div>\
			    </div>\
			</div>\
		');
        $("#menu2").append(imgs);

        $("#menu3").append(OEtable);


        //Haetaan muiden valmistajien vastaavat tuotteet (vertailunumerot) ja lisätään modaliin
        getComparableNumber(articleNo);
        //Haetaan tuotteeseen linkitetyt autot ja lisätään modaliin
        getLinkedManufacturers(articleId);
        showModal(); //näytetään modal

    }

	function showCars(elmnt, articleId){
        //Valitaan DIV painetun elementin data-manuId:n avulla
        var car_dropdown = $("#manufacturer-"+elmnt.getAttribute('data-manuId'));
		//Haetaan autot, jos niitä ei ole vielä haettu
		if (elmnt.getAttribute("data-list-filled") == "false") {
			elmnt.setAttribute("data-list-filled", "true");
			car_dropdown.addClass("loader");
			getLinkedVehicleIds(articleId, elmnt.getAttribute("data-manuId"));
		}
		if (car_dropdown.css("display") == "none") {
			car_dropdown.css("display", "block");
		}
		else {
            car_dropdown.css("display", "none");
		}
	}
</script>