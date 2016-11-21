<?php
ini_set('track_errors', 1);
//
// Funktioita kommunikointiin TecDoc-tietokannan kanssa.
//
require_once 'tecdoc_asetukset.php';

/**
 * Lähettää JSON-pyynnön TecDoc-palvelimelle ja palauttaa vastauksen oliona
 * @param $request
 * @return stdClass
 */
function _send_json( $request ) {
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

/**
 * Sallii clientin ottaa yhteyttä tecdociin 12 tunnin ajan.Aika muokattavissa.
 * @return array <p> ???
 */
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
    if ( $response->status !== 200 ) {
        return [];
    }

    return $response->data->array;
}

/**
 * Hakee aktivoidut toimittajat
 * @return array
 */
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

/**
 * Hakee toimittajan tiedot annetun valmistajanumeron perusteella
 * @param $brandNo
 * @return array
 */
function getAmBrandAddress( $brandNo ) {
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

/**
 * Hakee tuotteet annetun tuotenumeron (articleNo) ja hakutyypin perusteella.
 * Kolmas parametri määrittelee haun tarkkuutta.
 * @param string $number
 * @param int $search_type <p>
 * @param boolean $exact <p> Haetaanko vain tuotenumerolla.
 * @param int $brandNo [optional], default = NULL <p>
 * @return array
 */
function getArticleDirectSearchAllNumbersWithState( /*string*/ $number, /*int*/ $search_type,
													/*bool*/ $exact, /*int*/ $brandNo = NULL ) {
	$function = 'getArticleDirectSearchAllNumbersWithState';
	$params = [
		'lang' => TECDOC_LANGUAGE,
		'articleCountry' => TECDOC_COUNTRY,
		'provider' => TECDOC_PROVIDER,
		'articleNumber' => $number,
        'brandNo' => $brandNo,
        'searchExact' => $exact,
		'numberType' => $search_type, //10: mikä tahansa numerotyyppi, 0:tuotenumero, 3:vertailut
	];

	// Lähetetään JSON-pyyntö
	$request =	[$function => $params];
	$response = _send_json($request);

    //Pyyntö onnistui
    if ( $response->status === 200 && !empty($response->data->array) ) {
		return $response->data->array;
	}

	return [];
}


/**
 * Hakee tuotteet annettujen tunnisteiden (articleId) perusteella.
 * @param $ids
 * @return array
 */
function getDirectArticlesByIds4( $ids ) { //TODO: Miksi nimessä on nelonen?
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


/**
 * Hakee kaikki automerkit.
 * @return array
 */
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

	//Pyyntö onnistui
	if ( $response->status===200 && isset($response->data->array)) {
		return $response->data->array;
	}

	return [];
}

/**
 * Hakee tiettyyn autoon ja osaluokkaan linkitetyt tuotteet.
 * @param $carID
 * @param $groupID
 * @return array
 */
function getArticleIdsWithState( $carID, $groupID ) {
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

	//Pyyntö onnistui
	if ( $response->status===200 && isset($response->data->array)) {
		return $response->data->array;
	}

	return [];
}

/**
 * Hakee halutun tuotteen EAN-numeron, infot ja kuvan url:in.
 * @param $id
 * @return array
 */
function getOptionalData( $id ) {
	$function = 'getDirectArticlesByIds7';
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

	//Pyyntö onnistui
	if ( $response->status===200 && isset($response->data->array) ) {
		return $response->data->array;
	}

	return [];
}



/**
 * Yhdistää catalogin tuotteille perustiedot tecdocista (TecdocID, brandin nimi, artikkelin nimi).
 * @param array $catalog_products
 */
function get_basic_product_info( /*array*/ $catalog_products ) {
    foreach ( $catalog_products as $catalog_product ) {
        //var_dump($catalog_product);
        $response = getArticleDirectSearchAllNumbersWithState($catalog_product->articleNo, 0, true, $catalog_product->brandNo);

        $catalog_product->articleId = $response ? $response[0]->articleId : false;
        $catalog_product->brandName = $response ? $response[0]->brandName : "";
        $catalog_product->articleName = $response ? $response[0]->articleName : "";
    }
}

/**
 * Funktio yhdistää tuotteeseen Infot ja kuvan url:in.
 * Huom! Listassa olevilla tuotteilla oltava ominaisuus articleId.
 * @param $products
 */
function merge_products_with_optional_data( $products ) {
	foreach ($products as $product){
		$response = getOptionalData($product->articleId);
		$product->thumburl = get_thumbnail_url($response[0]);
		$product->infos = get_infos($response[0]);
	}
}

/**
 * Palauttaa annetun tuotteen kuvan URL:n
 * @param $product
 * @param bool $small
 * @return string
 */
function get_thumbnail_url( $product, /*bool*/ $small = true) {
    if (empty($product->articleThumbnails)) {
        return 'img/ei-kuvaa.png';
    }
    $thumb_id = $product->articleThumbnails->array[0]->thumbDocId;
    return TECDOC_THUMB_URL . $thumb_id . '/' . ($small ? 1 : 0);
}


/**
 * @param $product <p> getDirectArticlesByIds4-funktiosta saatu tuote.
 * @return array <p> Infot arrayna, jos olemassa. Muuten tyhjä array.
 */
function get_infos( $product ) {
	return (!empty($product->immediateAttributs)
		? $product->immediateAttributs->array
		: []);
}

/**
 * @param $product <p> getOptionalData-funktiosta saatu vastaus.
 * @return array <p> OE-numerot, jos olemassa. Muuten tyhjä array.
 */
function get_oe_number( $product ) {
	$oeNumbers = [];
	if ( !empty($product->oenNumbers) ) {
		for ($i=0; $i < count($product->oenNumbers->array); $i++){
			$oeNumbers[] = strval($product->oenNumbers->array[$i]->oeNumber);
		}
	}
	return array_unique( $oeNumbers );
}
