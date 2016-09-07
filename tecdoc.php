<?php

//
// Funktioita kommunikointiin TecDoc-tietokannan kanssa.
//

require_once 'tecdoc_asetukset.php';

//
// Lähettää JSON-pyynnön TecDoc-palvelimelle ja palauttaa vastauksen taulukkomuodossa
//
function _send_json($request) {
	$params = ['http' => ['method' => 'POST', 'content' => json_encode($request)]];
	$context = stream_context_create($params);

	if (($file = @fopen(TECDOC_SERVICE_URL, 'rb', false, $context)) === false) {
		die("Lukuvirhe: $php_errormsg");
	}

	if (($json_response = @stream_get_contents($file)) === false) {
		die("Lukuvirhe: $php_errormsg");
	}

	$response = json_decode($json_response);

	// Debug-tulostuksia tarvittaessa
	if (TECDOC_DEBUG) {
		echo '<b>Pyyntö:</b><pre>'; print_r($request); echo '</pre>';
		echo '<b>Vastaus:</b><pre>'; print_r($response); echo '</pre>';
	}

	return $response;
}

//
// Sallii clientin ottaa yhteyttä tecdociin XX tunnin ajan
//
function addDynamicAddress() {
    $function = 'addDynamicAddress';
    $params = [
        'validityHours' => 12,
        'provider' => TECDOC_PROVIDER,
        'address' => $_SERVER['REMOTE_ADDR'],
    ];

    // Lähetetään JSON-pyyntö
    $request =	[$function => $params];
    $response = _send_json($request);

    // Pyyntö epäonnistui
    if ($response->status !== 200) {
        return [];
    }

    return $response->data->array;
}

//
// Hakee aktivoidut toimittajat
//
function getAmBrands() {
	$function = 'getAmBrands';
	$params = [
			'lang' => TECDOC_LANGUAGE,
			'articleCountry' => TECDOC_COUNTRY,
			'provider' => TECDOC_PROVIDER,
	];

	// Lähetetään JSON-pyyntö
	$request =	[$function => $params];
	$response = _send_json($request);

	// Pyyntö epäonnistui
	if ($response->status !== 200) {
		return [];
	}

	return $response->data->array;
}

//
// Hakee toimittajan tiedot annetun valmistajanumeron perusteella
//
function getAmBrandAddress($brandNo) {
	$function = 'getAmBrandAddress';
	$params = [
			'lang' => TECDOC_LANGUAGE,
			'articleCountry' => TECDOC_COUNTRY,
			'provider' => TECDOC_PROVIDER,
			'brandNo' => $brandNo,
	];

	// Lähetetään JSON-pyyntö
	$request =	[$function => $params];
	$response = _send_json($request);

	// Pyyntö epäonnistui
	if ($response->status !== 200) {
		return [];
	}

	return $response->data->array;
}

//
// Hakee tuotteet annetuen tuotenumeron (articleNo) perusteella.
// Jos ei exact, haetaan myös vertailunumerolla, oe-numerolla, ean-numerolla yms.
// Jos exact, haetaan vai tuotenumerolla.
//
function getArticleDirectSearchAllNumbersWithState($number, $exact) {
	$function = 'getArticleDirectSearchAllNumbersWithState';
    $numberType = $exact ? 0 : 10;
	$params = [
		'lang' => TECDOC_LANGUAGE,
		'articleCountry' => TECDOC_COUNTRY,
		'provider' => TECDOC_PROVIDER,
		'articleNumber' => $number,
        'searchExact' => $exact,
		'numberType' => $numberType, //10: mikä tahansa numerotyyppi, 0:tuotenumero
	];

	// Lähetetään JSON-pyyntö
	$request =	[$function => $params];
	$response = _send_json($request);

	// Pyyntö epäonnistui
	if ($response->status !== 200) {
		return [];
	}

	if (isset($response->data->array)) {
		return $response->data->array;
	}

	return [];
}


//Catalogin tuotteiden hakua varten
//Etsitään lisätiedot articleNo perusteella
function findMoreInfoByArticleNo($number) {
	$function = 'getArticleDirectSearchAllNumbersWithState';
	$params = [
			'lang' => TECDOC_LANGUAGE,
			'articleCountry' => TECDOC_COUNTRY,
			'provider' => TECDOC_PROVIDER,
			'articleNumber' => $number,
			'searchExact' => true,
			'numberType' => 0, // mikä tahansa numerotyyppi (OE, EAN, vertailunumero, jne.)
	];

	// Lähetetään JSON-pyyntö
	$request =	[$function => $params];
	$response = _send_json($request);

	// Pyyntö epäonnistui
	if ($response->status !== 200) {
		return [];
	}

	if (isset($response->data->array)) {
		return $response->data->array[0];
	}

	return [];
}



//
// Hakee tuotteet annettujen tunnisteiden (articleId) perusteella
//
function getDirectArticlesByIds4($ids) {
	$function = 'getDirectArticlesByIds4';
	$params = [
		'lang' => TECDOC_LANGUAGE,
		'articleCountry' => TECDOC_COUNTRY,
		'provider' => TECDOC_PROVIDER,
		'basicData' => true,
		'articleId' => ['array' => $ids],
		'thumbnails' => true,
		'immediateAttributs' => true,
		'eanNumbers' => true,
		'oeNumbers' => true
	];

	// Lähetetään JSON-pyyntö
	$request =	[$function => $params];
	$response = _send_json($request);

	// Pyyntö epäonnistui
	if ($response->status !== 200) {
		return [];
	}

	return $response->data->array;
}

//
// Palauttaa annetun tuotteen kuvan URL:n
//
function get_thumbnail_url($product, $small = true) {
	/*
	echo '<pre>';
	var_dump($product);
	echo '</pre>';
	*/
	if (empty($product->articleThumbnails)) {
		return 'img/ei-kuvaa.png';
	}
	$thumb_id = $product->articleThumbnails->array[0]->thumbDocId;
	return TECDOC_THUMB_URL . $thumb_id . '/' . ($small ? 1 : 0);
}

//
// Hakee tuotteiden ID:iden perusteella TecDocista kunkin tuotteen tiedot ja yhdistää ne
//
function merge_products_with_tecdoc($products) {
	// Kerätään tuotteiden ID:t taulukkoon
	$ids = [];
	foreach ($products as $product) {
		array_push($ids, $product->id);
	}

	// Haetaan tuotteiden tiedot TecDocista ID:iden perusteella
	$id_chunks = array_chunk($ids, 25);
	//25 kpl erissä
	$tecdoc_products = [];
	foreach ($id_chunks as $id_chunk) {
		$tecdoc_products = array_merge($tecdoc_products, getDirectArticlesByIds4($id_chunk));
	}

	// Yhdistetään TecDocista saatu data $products-taulukkoon
	foreach ($tecdoc_products as $tecdoc_product) {
		foreach ($products as $product) {
			if ($product->id == $tecdoc_product->directArticle->articleId) {
				$product->directArticle = $tecdoc_product->directArticle;
				$product->articleThumbnails = $tecdoc_product->articleThumbnails;
				$product->ean = get_ean_number($tecdoc_product);
				$product->infos = get_infos($tecdoc_product);
				$product->thumburl = get_thumbnail_url($tecdoc_product);
				$product->oe = get_oe_number($tecdoc_product);
			}
		}
	}
}


//Yhdistää catalogin (tietokannan) tuotteet tecdocin datan kanssa
//jos $all_info: merge myös oe, kuvat, ean ja infot
function merge_catalog_with_tecdoc($catalog_products, $also_basic_info) {

    if ($also_basic_info){
	    foreach ($catalog_products as $catalog_product) {
            $response = findMoreInfoByArticleNo($catalog_product->articleNo);
            $catalog_product->articleId = $response->articleId;
            $catalog_product->brandName = $response->brandName;
            $catalog_product->articleName = $response->articleName;
        }
    }
	merge_products_with_optional_data($catalog_products);

}




//hakee kaikki automerkit
function getManufacturers() {
	$function = 'getManufacturers';
	$params = [
			'favouredList' => 1,
			'linkingTargetType' => 'P',
			'country' => TECDOC_COUNTRY,
			'lang' => TECDOC_LANGUAGE,
			'provider' => TECDOC_PROVIDER
	];

	// Lähetetään JSON-pyyntö
	$request =	[$function => $params];
	$response = _send_json($request);

	// Pyyntö epäonnistui
	if ($response->status !== 200) {
		return [];
	}

	if (isset($response->data->array)) {
		return $response->data->array;
	}

	return [];
}



//hakee tiettyyn autoon ja osaluokkaan linkitetyt tuotteet.
function getArticleIdsWithState($carID, $groupID) {
	$function = 'getArticleIdsWithState';
	$params = [
			'articleCountry' => TECDOC_COUNTRY,
			'lang' => TECDOC_LANGUAGE,
			'provider' => TECDOC_PROVIDER,
			"linkingTargetId" => $carID,
			"assemblyGroupNodeId" => $groupID,
			"linkingTargetType" => "P",
	];

	// Lähetetään JSON-pyyntö
	$request =	[$function => $params];
	$response = _send_json($request);

	// Pyyntö epäonnistui
	if ($response->status !== 200) {
		return [];
	}

	if (isset($response->data->array)) {
		return $response->data->array;
	}

	return [];
}


//hakee halutun tuotteen EAN-numeron, infot ja kuvan url:in.
function getOptionalData($id) {
	$function = 'getDirectArticlesByIds4';
	$params = [
			'lang' => TECDOC_LANGUAGE,
			'articleCountry' => TECDOC_COUNTRY,
			'provider' => TECDOC_PROVIDER,
			'basicData' => false,
			'articleId' => ['array' => $id],
			'thumbnails' => true,
			'immediateAttributs' => true,
			'eanNumbers' => false,
			'oeNumbers' => false

	];

	// Lähetetään JSON-pyyntö
	$request =	[$function => $params];
	$response = _send_json($request);

	// Pyyntö epäonnistui
	if ($response->status !== 200) {
		return [];
	}

	return $response->data->array;
}


//Funktio yhdistää olemassa olevaan tuotteeseen EAN-numeron, Infot ja
//kuvan url:in.

//Huom! Listassa olevilla tuotteilla oltava ominaisuus articleId.
function merge_products_with_optional_data($articles) {
	foreach ($articles as $article){
		$product = getOptionalData($article->articleId);
		$article->thumburl = get_thumbnail_url($product[0]);
		//$article->ean = get_ean_number($product[0]);
		$article->infos = get_infos($product[0]);
		//$article->oe = get_oe_number($product[0]);
	}
}

function get_oe_by_id($id){
	$product = $product = getOptionalData($id);
	$oe = get_oe_number($product[0]);
	return $oe;
}



//Saa parametrina getDirectArticlesByIds4 funktiosta saadun tuotteen
//Palauttaa EAN-numeron, jos olemassa. Muuten tyhjä.
function get_ean_number($product) {
	if (empty($product->eanNumber)) {
		return '';
	}
	return $product->eanNumber->array[0]->eanNumber;
}

//Saa parametrina getDirectArticlesByIds4 funktiosta saadun tuotteen
//Palauttaa infot arrayna, jos olemassa. Muuten tyhjä array.
function get_infos($product) {
	if (empty($product->immediateAttributs)) {
		return array();
	}
	return $product->immediateAttributs->array;
}

//Saa parametrina getDirectArticlesByIds4 funktiosta saadun tuotteen
//Palauttaa numeron, jos olemassa. Muuten tyhjän.
function get_oe_number($product) {
	if (empty($product->oenNumbers)) {
		return array();
	}
	$oeNumbers = array();
	for ($i=0; $i < count($product->oenNumbers->array); $i++){
		array_push($oeNumbers, strval($product->oenNumbers->array[$i]->oeNumber));
	}
	return array_unique($oeNumbers);
}
