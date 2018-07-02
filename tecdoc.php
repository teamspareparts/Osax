<?php
ini_set('track_errors', '1');
//
// Funktioita kommunikointiin TecDoc-tietokannan kanssa.
//
require_once 'tecdoc_asetukset.php';

/**
 * Lähettää JSON-pyynnön TecDoc-palvelimelle ja palauttaa vastauksen oliona
 * @param array $request
 * @return stdClass
 */
function _send_json( array $request ) : stdClass {
	$params = ['http' => ['method' => 'POST', 'content' => json_encode($request)]];
	$context = stream_context_create($params);

	if (($file = @fopen(TECDOC_SERVICE_URL, 'rb', false, $context)) === false) {
		trigger_error( "Problem with TecDoc, $php_errormsg" );
	}

	if (($json_response = @stream_get_contents($file)) === false) {
		trigger_error( "Problem reading data from TecDoc, $php_errormsg" );
	}

	$response = json_decode($json_response);

	// Debug-tulostuksia tarvittaessa
	if (TECDOC_DEBUG) {
		echo '<b>Pyyntö:</b><pre>'; print_r($request); echo '</pre>';
		echo '<b>Vastaus:</b><pre>'; print_r($response); echo '</pre>';
	}

	return (object)$response;
}

/**
 * Sallii clientin ottaa yhteyttä tecdociin 12 tunnin ajan.
 * @return bool <p> Onnistuiko yhteyden salliminen
 */
function addDynamicAddress() : bool {
	$validity_hours = 12;
    $function = 'addDynamicAddress';
    $params = [
        'validityHours' => $validity_hours,
        'provider' => TECDOC_PROVIDER,
        'address' => $_SERVER['REMOTE_ADDR'],
    ];

    // Lähetetään JSON-pyyntö
    $request =	[$function => $params];
    $response = _send_json($request);

    // Pyyntö epäonnistui
    if ( $response->status !== 200 ) {
        return false;
    }

    return true;
}

/**
 * Hakee aktivoidut toimittajat
 * @return array <p> Toimittajat
 */
function getAmBrands() : array {
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
function getAmBrandAddress( int $brandNo ) : array {
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
	// Brändiä ei löytynyt
	if (!isset($response->data->array)) {
		return [];
	}
	return $response->data->array;
}

/**
 * Hakee tuotteet annetun tuotenumeron (articleNo) ja hakutyypin perusteella.
 * Kolmas parametri määrittelee haun tarkkuutta.
 * @param string $number <p> Haettava artikkelinumero
 * @param int $search_type <p> Haun tyyppi
 * @param boolean $exact <p> Tarkka haku
 * @param int|NULL $brandNo [optional], default = NULL <p>
 * @param int|NULL $genericArticleId [optional] <p> Osan tyypin (esim. hammashihna tai jarrulevy), jos tiedossa.
 * @return array
 */
function getArticleDirectSearchAllNumbersWithState( string $number, int $search_type,
													bool $exact, int $brandNo = NULL,
													int $genericArticleId = NULL) : array {
	$function = 'getArticleDirectSearchAllNumbersWithState';
	$params = [
		'lang' => TECDOC_LANGUAGE,
		'articleCountry' => TECDOC_COUNTRY,
		'provider' => TECDOC_PROVIDER,
		'articleNumber' => $number,
        'brandId' => $brandNo,
		'genericArticleId' => $genericArticleId,
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
 * Hakee kaikki automerkit.
 * @return array
 */
function getManufacturers() : array {
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
 * @param int $car_id
 * @param int $group_id
 * @return array
 */
function getArticleIdsWithState( int $car_id, int $group_id ) : array {
	$function = 'getArticleIdsWithState';
	$params = [
			'articleCountry' => TECDOC_COUNTRY,
			'lang' => TECDOC_LANGUAGE,
			'provider' => TECDOC_PROVIDER,
			"linkingTargetId" => $car_id,
			"assemblyGroupNodeId" => $group_id,
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
 * Hakee halutun tuotteen infot ja kuvan url:in.
 * @param int $article_id
 * @return array
 */
function getOptionalData( int $article_id ) : array {
	$function = 'getDirectArticlesByIds7';
	$params = [
			'lang' => TECDOC_LANGUAGE,
			'articleCountry' => TECDOC_COUNTRY,
			'provider' => TECDOC_PROVIDER,
			'basicData' => false,
			'articleId' => ['array' => $article_id],
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
function get_basic_product_info( array $catalog_products ) {
    foreach ( $catalog_products as $catalog_product ) {
        $response = getArticleDirectSearchAllNumbersWithState($catalog_product->articleNo, 0, true, (int)$catalog_product->brandNo);

        $catalog_product->articleId = $response ? $response[0]->articleId : false;
        $catalog_product->brandName = $response ? $response[0]->brandName : "";
        $catalog_product->articleName = $response ? $response[0]->articleName : "";
    }
}

/**
 * Funktio yhdistää tuotteeseen Infot ja kuvan url:in.
 * Huom! Listassa olevilla tuotteilla oltava attribuutti articleId.
 * @param array $products
 */
function merge_products_with_optional_data( array $products ) {
	foreach ($products as $product){
		if ( !$product->articleId ) {
			continue;
		}
		$response = getOptionalData($product->articleId);
		$product->thumburl = get_thumbnail_url($response[0]);
		$product->infos = get_infos($response[0]);
	}
}

/**
 * Hakee tuotteen kuvan URLin
 * @param stdClass $product
 * @param bool $small
 * @return string
 */
function get_thumbnail_url( stdClass $product, bool $small = true ) : string {
    if (empty($product->articleThumbnails)) {
        return 'img/ei-kuvaa.png';
    }
    $thumb_id = $product->articleThumbnails->array[0]->thumbDocId;
    return TECDOC_THUMB_URL . $thumb_id . '/' . ($small ? 1 : 0);
}

/**
 * Palauttaa tuotteen infot arrayna.
 * @param $product <p> getOptionalData-funktiosta saatu tuote.
 * @return array <p> Infot arrayna, jos olemassa. Muuten tyhjä array.
 */
function get_infos( stdClass $product ) : array {
	return (!empty($product->immediateAttributs)
		? $product->immediateAttributs->array
		: []);
}
