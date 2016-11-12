<?php
require '_start.php'; global $db, $user, $cart;
require 'tecdoc.php';
require 'apufunktiot.php';


/**
 * Jakaa tecdocista löytyvät tuotteet kahteen ryhmään: niihin, jotka löytyvät
 * valikoimasta ja niihin, jotka eivät löydy.
 * Lopuksi lisää liittää TecDoc-tiedot valikoiman tuotteisiin.
 *
 * @param DByhteys $db <p> Tietokantayhteys
 * @param array $products <p> Tuote-array, josta etsitään aktivoidut tuotteet.
 * @return array <p> Kolme arrayta:
 * 		[0]: saatavilla olevat tuotteet, jotka löytyvät catalogista;
 *      [1]: ei saatavilla olevat tuotteet;
 * 		[2]: tuotteet, jotka eivät löydy catalogista
 */
function filter_catalog_products ( DByhteys $db, array $products ) {

    /**
     * Haetaan tuote tietokannasta artikkelinumeron ja brandinumeron perusteella.
     * @param DByhteys $db
     * @param stdClass $product
     * @return array|bool|stdClass
     */
    function get_product_from_database(DByhteys $db, stdClass $product){
        $query = "	SELECT 	*, (hinta_ilman_alv * (1+ALV_kanta.prosentti)) AS hinta
			    		FROM 	tuote 
		  	  	    	JOIN 	ALV_kanta
		    				ON	tuote.ALV_kanta = ALV_kanta.kanta
				    	WHERE 	tuote.articleNo = ?
					        AND tuote.brandNo = ?
					 	    AND tuote.aktiivinen = 1 ";

        return $db->query($query, [str_replace(" ", "", $product->articleNo), $product->brandNo], FETCH_ALL, PDO::FETCH_OBJ);
    }


	$catalog_products = $not_available_catalog_products = $not_in_catalog = array();
	$ids = $articleIds = array();	//duplikaattien tarkistusta varten

    //Lajitellaan tuotteet sen mukaan, löytyikö tietokannasta vai ei.
	foreach ( $products as $product ) {
        $row = get_product_from_database($db, $product);
		if ( !$row && !in_array($product->articleId, $articleIds)) {
			$articleIds[] = $product->articleId;
			$product->articleName = isset($product->articleName) ? $product->articleName : $product->genericArticleName;
			$not_in_catalog[] = $product;
		}
		if ( $row ) {
		    //Kaikki löytyneet tuotteet (eri hankintapaikat)
            foreach ($row as $tuote) {
                if (!in_array($tuote->id, $ids)){
                    $ids[] = $tuote->id;
                    $tuote->articleId = $product->articleId;
                    $tuote->articleName = isset($product->articleName) ? $product->articleName : $product->genericArticleName;
                    $tuote->brandName = $product->brandName;
                    if (($tuote->varastosaldo >= $tuote->minimimyyntiera) && ($tuote->varastosaldo != 0)) {
                       $catalog_products[] = $tuote;
                    } else {
                        $not_available_catalog_products[] = $tuote;
                    }
                }
            }
		}
	}
	merge_products_with_optional_data( $catalog_products );
	merge_products_with_optional_data( $not_available_catalog_products );

	return [$catalog_products, $not_available_catalog_products, $not_in_catalog];
}

/** Järjestetään tuotteet hinnan mukaan
 * @param $catalog_products
 * @return array <p> Sama array, mutta sorted
 */
function sortProductsByPrice( $catalog_products ){
	/** @param $a
	 * @param $b
	 * @return bool
	 */
	function cmpPrice($a, $b) {
		return ($a->hinta > $b->hinta);
	}
	usort($catalog_products, "cmpPrice");
	return $catalog_products;
}

/**
 * Tarkastaa onko numerossa hankintapaikkaan viittaavaa etuliitettä.
 * @param $number
 * @return bool
 */
function tarkasta_etuliite(/*String*/ $number){
    if ( strlen($number)>4 && $number[3]==="-" && is_numeric(substr($number, 0, 3)) ){
        return true;
    } else {
		return false;
	}
}

/**
 * Jakaa hakunumeron etuliitteeseen ja hakunumeroon
 * @param int $number reference
 * @param int $etuliite reference
 */
function halkaise_hakunumero(&$number, &$etuliite){
    $etuliite = substr($number, 0, 3);
    $number = substr($number, 4);
}


$haku = FALSE;
$products = $catalog_products = $not_in_catalog = $not_available = [];

if ( !empty($_GET['haku']) ) {
	$haku = TRUE; // Hakutulosten tulostamista varten.
	$number = addslashes(str_replace(" ", "", $_GET['haku']));  //hakunumero
    $etuliite = null;                                           //mahdollinen etuliite
    //TODO: Jos tuotenumerossa on neljäs merkki: -, tulee se jättää pois tai haku epäonnistuu
    //TODO: sillä ei voida tietää kuuluuko etuliite tuotenumeroon vai kertooko se hankintapaikan (Esim 200-149)


	$numerotyyppi = isset($_GET['numerotyyppi']) ? $_GET['numerotyyppi'] : null;	//numerotyyppi
    $exact = (isset($_GET['exact']) && $_GET['exact'] === 'false') ? false : true;	//tarkka haku
	switch ($numerotyyppi) {
		case 'all':
            if(tarkasta_etuliite($number)) halkaise_hakunumero($number, $etuliite);
			$products = getArticleDirectSearchAllNumbersWithState($number, 10, $exact);
			break;
		case 'articleNo':
            if(tarkasta_etuliite($number)) halkaise_hakunumero($number, $etuliite);
			$products = getArticleDirectSearchAllNumbersWithState($number, 0, $exact);
			break;
		case 'comparable':
            if(tarkasta_etuliite($number)) halkaise_hakunumero($number, $etuliite);
			$products1 = getArticleDirectSearchAllNumbersWithState($number, 0, $exact);	//tuote
			$products2 = getArticleDirectSearchAllNumbersWithState($number, 3, $exact);	//vertailut
			$products = array_merge($products1, $products2);
			break;
		case 'oe':
			$products = getArticleDirectSearchAllNumbersWithState($number, 1, $exact);
			break;
		default:	//jos numerotyyppiä ei ole määritelty (= joku on ruvennut leikkimään GET parametrilla)
            $products = getArticleDirectSearchAllNumbersWithState($number, 10, $exact);
			break;
	}

	// Filtteröidään catalogin tuotteet kolmeen listaan: saatavilla, ei saatavilla ja tuotteet, jotka ei ole valikoimassa.
	$filtered_product_arrays = filter_catalog_products( $db, $products, $etuliite );
	$catalog_products = $filtered_product_arrays[0];
	$not_available = $filtered_product_arrays[1];
	$not_in_catalog = $filtered_product_arrays[2];
	$catalog_products = sortProductsByPrice($catalog_products);
}

if ( !empty($_GET["manuf"]) ) {
	$haku = TRUE; // Hakutulosten tulostamista varten. Ei tarvitse joka kerta tarkistaa isset()
	$selectCar = $_GET["car"];
	$selectPartType = $_GET["osat_alalaji"];

	$products = getArticleIdsWithState($selectCar, $selectPartType);
	$filtered_product_arrays = filter_catalog_products( $db, $products );
	$catalog_products = $filtered_product_arrays[0];
	$not_available = $filtered_product_arrays[1];
	$not_in_catalog = $filtered_product_arrays[2];
	$catalog_products = sortProductsByPrice($catalog_products);
}
?>
<!DOCTYPE html>
<html lang="fi">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="css/jsmodal-light.css">
    <link rel="stylesheet" href="css/bootstrap.css">

    <link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.0/jquery.min.js"></script>
    <script src="https://ajax.googleapis.com/ajax/libs/jqueryui/1.11.4/jquery-ui.min.js"></script>

    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>
    <script src="http://webservicepilot.tecdoc.net/pegasus-3-0/services/TecdocToCatDLB.jsonEndpoint?js"></script>
    <script src="js/jsmodal-1.0d.min.js"></script>
    <title>Tuotehaku</title>
</head>
<body>
<?php
require 'header.php';
require_once 'tuotemodal.php';
?>
<main class="main_body_container">
	<section class="flex_row">
		<div class="tuotekoodihaku">
			<form action="tuotehaku.php" method="get" class="haku">
                <div class="inline-block">
                    <label for="search">Hakunumero:</label>
                    <br>
				    <input id="search" type="text" name="haku" placeholder="Tuotenumero">
                </div>
                <div class="inline-block">
                    <label for="numerotyyppi">Numerotyyppi:</label>
                    <br>
				    <select id="numerotyyppi" name="numerotyyppi">
				    	<option value="all">Kaikki numerot</option>
				    	<option value="articleNo">Tuotenumero</option>
				    	<option value="comparable">Tuotenumero + vertailut</option>
				    	<option value="oe">OE-numerot</option>
				    </select>
                </div>
                <div class="inline-block">
                    <label for="hakutyyppi">Hakutyyppi:</label>
                    <br>
                    <select id="hakutyyppi" name="exact">
                        <option value="true">Tarkka</option>
                        <option value="false">Samankaltainen</option>
                    </select>
                </div>
                <br>
				<input class="nappi" type="submit" value="Hae">
			</form>
			<?php if ( $haku ) : ?>
			<h3>Yhteensä löydettyjä tuotteita:
				<?=count($catalog_products) + count($not_available) + count($not_in_catalog)?></h3>
			<?php endif; ?>
		</div>
        <?php require 'ajoneuvomallillahaku.php';?>
	</section>

	<section class="hakutulokset">
	<?php if ( $haku ) : ?>
		<?php if ( $catalog_products) : // Tulokset (saatavilla) ?>
		<table style="min-width: 90%;"><!-- Katalogissa saatavilla, tilattavissa olevat tuotteet (varastosaldo > 0) -->
			<thead>
			<tr><th colspan="8" class="center" style="background-color:#1d7ae2;">Saatavilla: (<?=count($catalog_products)?>)</th></tr>
			<tr><th>Kuva</th>
				<th>Tuotenumero</th>
				<th>Tuote</th>
				<th>Info</th>
				<th class="number">Saldo</th>
				<th class="number">Hinta (sis. ALV)</th>
				<?php if ( $user->isAdmin() ) : ?>
					<th class="number">Ostohinta ALV0%</th>
				<?php endif; ?>
				<th>Kpl</th>
				<th></th>
			</tr>
			</thead>
			<tbody>
			<?php foreach ($catalog_products as $product) : ?>
				<tr data-val="<?=$product->articleId?>">
					<td class="clickable thumb">
						<img src="<?=$product->thumburl?>" alt="<?=$product->articleName?>"></td>
					<td class="clickable"><?=$product->tuotekoodi?></td>
					<td class="clickable"><?=$product->brandName?><br><?=$product->articleName?></td>
					<td class="clickable">
						<?php foreach ( $product->infos as $info ) :
							echo (!empty($info->attrName) ? $info->attrName : "") . " " .
								(!empty($info->attrValue) ? $info->attrValue : "") .
								(!empty($info->attrUnit) ? $info->attrUnit : "") . "<br>";
						endforeach; ?>
					</td>
					<td class="number"><?=format_integer($product->varastosaldo)?></td>
					<td class="number"><?=format_euros($product->hinta)?></td>
					<?php if ( $user->isAdmin() ) : ?>
						<td class="number"><?=format_euros($product->sisaanostohinta)?></td>
					<?php endif;?>
					<td style="padding-top: 0; padding-bottom: 0;">
						<input id="maara_<?=$product->id?>" name="maara_<?=$product->id?>" class="maara"
							   type="number" value="0" min="0" title="Kappale-määrä"></td>
					<td class="toiminnot" id="tuote_cartAdd_<?=$product->id?>">
						<!-- //TODO: Disable nappi, ja väritä tausta lisäyksen jälkeen -->
						<button class="nappi" onclick="addToShoppingCart(<?=$product->id?>)">
							<i class="material-icons">add_shopping_cart</i>Osta</button></td>
				</tr>
			<?php endforeach; //TODO: Poista ostoskorista -nappi(?) ?>
			</tbody>
		</table>
		<?php endif; //if $catalog_products

		if ( $not_available) : // Tulokset (ei saatavilla) ?>
		<table style="min-width: 90%;"><!-- Katalogissa olevat, ei tilattavissa olevat tuotteet (varastosaldo < minimimyyntierä) -->
			<thead>
			<tr><th colspan="7" class="center" style="background-color:#1d7ae2;">Ei varastossa: (<?=count($not_available)?>)</th></tr>
			<tr><th>Kuva</th>
				<th>Tuotenumero</th>
				<th>Tuote</th>
				<th>Info</th>
				<th class="number">Saldo</th>
				<th class="number">Hinta (sis. ALV)</th>
				<?php if ( $user->isAdmin() ) : ?>
					<th class="number">Ostohinta ALV0%</th>
				<?php endif; ?>
				<th></th>
			</tr>
			</thead>
			<tbody>
			<?php foreach ($not_available as $product) : ?>
				<tr data-val="<?=$product->articleId?>">
					<td class="clickable thumb">
						<img src="<?=$product->thumburl?>" alt="<?=$product->articleName?>"></td>
					<td class="clickable"><?=$product->tuotekoodi?></td>
					<td class="clickable"><?=$product->brandName?><br><?=$product->articleName?></td>
					<td class="clickable">
						<?php foreach ( $product->infos as $info ) :
							echo (!empty($info->attrName) ? $info->attrName : "") . " " .
								(!empty($info->attrValue) ? $info->attrValue : "") .
								(!empty($info->attrUnit) ? $info->attrUnit : "") . "<br>";
						endforeach; ?>
					</td>
					<td class="number"><?=format_integer($product->varastosaldo)?></td>
					<td class="number"><?=format_euros($product->hinta)?></td>
					<?php if ( $user->isAdmin() ) : ?>
						<td class="number"><?=format_euros($product->sisaanostohinta)?></td>
					<?php endif; ?>
					<td id="tuote_ostopyynto_<?=$product->id?>">
						<button onClick="ostopyynnon_varmistus(<?=$product->id?>);">
							<i class="material-icons">info</i></button>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
		<?php endif; //if $not_available catalog products

		if ( $not_in_catalog) : //Tulokset (ei katalogissa)?>
		<table><!-- Katalogissa ei olevat, ei tilattavissa olevat tuotteet. TecDocista. -->
			<thead>
			<tr><th colspan="3" class="center" style="background-color:#1d7ae2;">Tilaustuotteet: (<?=count($not_in_catalog)?>)</th></tr>
			<tr><th>Tuotenumero</th>
				<th>Tuote</th>
				<th>Info</th>
			</tr>
			</thead>
			<tbody>
			<?php foreach ($not_in_catalog as $product) : ?>
				<tr data-val="<?=$product->articleId?>">
					<td class="clickable"><?=$product->articleNo?></td>
					<td class="clickable"><?=$product->brandName?><br><?=$product->articleName?></td>
					<td><button id="tuote_hnktpyynto_<?=$product->articleId?>"
								onClick="hankintapyynnon_varmistus(
									'<?=$product->articleNo?>',
									'<?=$product->brandName?>',
									'<?=$product->articleName?>',
									'<?=$product->articleId?>');">
						<i class="material-icons">help_outline</i></button></td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
		<?php endif; //if $not_in_catalog products

		if ( !$catalog_products && !$not_available && !$not_in_catalog ) : ?>
		<h2>Ei tuloksia.</h2>
    	<?php endif; //if ei tuloksia?>

	<?php endif; //if $haku?>
	</section>
</main>

<script type="text/javascript">


	/**
	 * Tallentaa ostospyynnön tietokantaan
	 * @param {int} product_id - Halutun tuotteen ID
	 */
	function ostopyynnon_varmistus( product_id ) {
		var vahvistus = confirm( "Olisin tilannut tuotteen, jos sitä olisi ollut saatavilla?");
		if ( vahvistus ) {
			$.post(
				"ajax_requests.php",
				{ tuote_ostopyynto: product_id },
				function( data ) {
					if ( (!!+data) === true ) { // Typecasting to int to boolean: !! + variable
						$("#tuote_ostopyynto_" + product_id)
							.css("background-color","green")
							.addClass("disabled");
					} else {
						alert("ERROR: Ostopyyntö ei onnistunut.");
					}
				}
			);
		}
	}

	/**
	 * Tallentaa hankintapyynnön tietokantaan (Käyttäjältä varmistuksen kysymisen jälkeen).
	 * @param {string} articleNo
	 * @param {string} valmistaja
	 * @param {string} tuotteet_nimi
	 * @param {string} articleId
	 */ //TODO: Selitys-tekstikenttä, ja Käykö korvaava -checkbox. jQuery UI?
	function hankintapyynnon_varmistus( articleNo, valmistaja, tuotteet_nimi, articleId ) {
		var vahvistus, selitys, korvaava_okey;
		vahvistus = confirm( "Olisin tilannut tuotteen, jos sitä olisi ollut saatavilla?");
		if ( vahvistus ) {
			korvaava_okey = confirm( "Kelpaako korvaava tuote?" );
			selitys = prompt( "Syy, miksi olisit halunnut tämä tuotteen? (Vapaaehtoinen)" );
			$.post("ajax_requests.php",
				{	tuote_hankintapyynto: true,
					articleNo: articleNo,
					valmistaja: valmistaja,
					tuotteen_nimi: tuotteet_nimi,
					selitys: selitys,
					korvaava_okey: korvaava_okey },
				function( data ) {
					if ( (!!+data) === true ) { // Typecasting to int to boolean: !! + variable
						$("#tuote_hnktpyynto_" + articleId)
							.css("background-color","green")
							.addClass("disabled");
					} else {
						alert("ERROR: Hankintapyyntö ei onnistunut.");
					}
				}
			);
		}
	}

	/**
	 * Tämän pitäisi lisätä tuote ostoskoriin...
	 * @param product_id
	 */
	function addToShoppingCart( product_id ) {
		var kpl_maara = $("#maara_" + product_id).val();
		if ( kpl_maara > 0 ) {
			$.post("ajax_requests.php",
				{	ostoskori_toiminto: true,
					tuote_id: product_id,
					kpl_maara: kpl_maara },
				function( data ) {
					if ( data.success === true ) {
						$("#tuote_cartAdd_" + product_id)
							.css("background-color","green")
							.addClass("disabled");
						$("#head_cart_tuotteet").text(data.tuotteet_kpl);
						$("#head_cart_kpl").text(data.yhteensa_kpl);
					} else {
						alert("ERROR: Tuotteen lisääminen ei onnistunut.");
					}
				}
			);
		}
	}



	$(document).ready(function(){

		//info-nappulan sisältö
		$("span.info-box").hover(function () {
			$(this).append('<div class="tooltip"><p>Tarkka haku</p></div>');
		}, function () {
			$("div.tooltip").remove();
		});

		$('.clickable')
			.css('cursor', 'pointer')
			.click(function(){
				var articleId = $(this).closest('tr').attr('data-val'); //haetaan tuotteen id
				productModal(articleId); //haetaan tuotteen tiedot tecdocista
			});

	});//doc.ready

	//qs["haluttu ominaisuus"] voi hakea urlista php:n GET
	//funktion tapaan tietoa
	var qs = (function(a) {
		var p, i, b = {};
		if (a != "") {
			for ( i = 0; i < a.length; ++i ) {
				p = a[i].split('=', 2);

				if (p.length == 1) {
					b[p[0]] = "";
				} else {
					b[p[0]] = decodeURIComponent(p[1].replace(/\+/g, " ")); }
			}
		}

		return b;
	})(window.location.search.substr(1).split('&'));

	//laitetaan ennen sivun päivittämistä tehdyt valinnat takaisin
	if ( qs["manuf"] ){
		var manuf = qs["manuf"];
		var model = qs["model"];
		var car = qs["car"];
		var osat = qs["osat"];
		var osat_alalaji = qs["osat_alalaji"];

		getModelSeries(manuf);
		getVehicleIdsByCriteria(manuf, model);
		getPartTypes(car);
		getChildNodes(car, osat);

		setTimeout(setSelected ,1000);

		function setSelected(){
			$("#manufacturer").find("option[value=" + manuf + "]").attr('selected', 'selected');
			$("#model").find("option[value=" + model + "]").attr('selected', 'selected');
			$("#car").find("option[value=" + car + "]").attr('selected', 'selected');
			$("#osaTyyppi").find("option[value=" + osat + "]").attr('selected', 'selected');
			$("#osat_alalaji").find("option[value=" + osat_alalaji + "]").attr('selected', 'selected');
		}

	}

	if ( qs["haku"] ){
		var search = qs["haku"];
		$("#search").val(search);
	}

	if( qs["numerotyyppi"] ){
		var number_type = qs["numerotyyppi"];
        if (number_type == "all" || number_type == "articleNo" ||
            number_type == "comparable" || number_type == "oe") {
            $("#numerotyyppi").val(number_type);
        }
	}

	if ( qs["exact"] ){
		var exact = qs["exact"];
        if (exact == "true" || exact == "false") {
            $("#hakutyyppi").val(exact);
        }
	}



</script>

</body>
</html>
