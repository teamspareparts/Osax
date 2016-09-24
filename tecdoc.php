<?php
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
 * Hakee tuotteet annetuen tuotenumeron (articleNo) perusteella.
 * Toinen parametri määrittelee haun tarkkuutta.
 * @param string $number
 * @param boolean $exact <p> Haetaanko vain tuotenumerolla.
 * 		Jos false, hakee myös vertailu-, OE-, ja EAN-numerolla (yms.)
 * @return array
 */
function getArticleDirectSearchAllNumbersWithState( /*string*/ $number, /*bool*/ $exact ) {
	$function = 'getArticleDirectSearchAllNumbersWithState';
	$params = [
		'lang' => TECDOC_LANGUAGE,
		'articleCountry' => TECDOC_COUNTRY,
		'provider' => TECDOC_PROVIDER,
		'articleNumber' => $number,
        'searchExact' => $exact,
		'numberType' => ($exact ? 0 : 10), //10: mikä tahansa numerotyyppi, 0:tuotenumero
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

/**
 * Catalogin tuotteiden hakua varten. Etsitään lisätiedot articleNo perusteella
 * @param $number
 * @return stdClass
 */
function findMoreInfoByArticleNo( $number ) {
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

	//Pyyntö onnistui
	if ( $response->status === 200 && !empty($response->data->array) ) {
		return $response->data->array[0];
	}

	return NULL;
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
 * Hakee tuotteiden ID:iden perusteella TecDocista kunkin tuotteen tiedot ja yhdistää ne
 * @param $products
 */
function merge_products_with_tecdoc( $products ) {
	// Kerätään tuotteiden ID:t taulukkoon
	$ids = array();
	foreach ($products as $product) {
		$ids[] = $product->id;
	}

	// Haetaan tuotteiden tiedot TecDocista ID:iden perusteella
	$id_chunks = array_chunk($ids, 25); //25 kpl erissä
	$tecdoc_products = [];
	foreach ( $id_chunks as $id_chunk ) {
		$tecdoc_products = array_merge($tecdoc_products, getDirectArticlesByIds4($id_chunk));
	}

	// Yhdistetään TecDocista saatu data $products-taulukkoon
	foreach ( $tecdoc_products as $tecdoc_product ) {
		foreach ( $products as $product ) {
			if ( $product->id == $tecdoc_product->directArticle->articleId ) {
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

/**
 * Yhdistää catalogin (tietokannan) tuotteet tecdocin datan kanssa
 * @param array $catalog_products
 * @param boolean $also_basic_info <p> Merge myös OE, kuvat, EAN ja infot.
 */
function merge_catalog_with_tecdoc( /*array*/ $catalog_products, /*bool*/ $also_basic_info ) {
    if ($also_basic_info){
	    foreach ( $catalog_products as $catalog_product ) {
            $response = findMoreInfoByArticleNo( $catalog_product->articleNo );
            $catalog_product->articleId = $response->articleId;
            $catalog_product->brandName = $response->brandName;
            $catalog_product->articleName = $response->articleName;
        }
    }
	merge_products_with_optional_data( $catalog_products );
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

	//Pyyntö onnistui
	if ( $response->status===200 && isset($response->data->array) ) {
		return $response->data->array;
	}

	return [];
}

/**
 * Funktio yhdistää olemassa olevaan tuotteeseen Infot ja kuvan url:in.
 * Huom! Listassa olevilla tuotteilla oltava ominaisuus articleId.
 * @param $articles
 */
function merge_products_with_optional_data( $articles ) {
	foreach ($articles as $article){
		$product = getOptionalData($article->articleId);
		$article->thumburl = get_thumbnail_url($product[0]);
		$article->infos = get_infos($product[0]);
	}
}

/**
 * @param $id
 * @return array
 */
function get_oe_by_id( $id){
	return get_oe_number( getOptionalData($id)[0] );
}

/**
 * @param $product <p> getDirectArticlesByIds4-funktiosta saatu tuote.
 * @return string <p> EAN-numero, jos olemassa. Muuten tyhjä.
 */
function get_ean_number( $product ) {
	return (!empty($product->eanNumber)
		? $product->eanNumber->array[0]->eanNumber
		: '');
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
 * @param $product <p> getDirectArticlesByIds4-funktiosta saatu tuote.
 * @return array <p> OE-numerot, jos olemassa. Muuten tyhjä array.
 */
function get_oe_number( $product ) {
	$oeNumbers = array();
	if ( !empty($product->oenNumbers) ) {
		for ($i=0; $i < count($product->oenNumbers->array); $i++){
			$oeNumbers[] = strval($product->oenNumbers->array[$i]->oeNumber);
		}
	}
	return array_unique( $oeNumbers );
}
