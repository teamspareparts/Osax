<?php

//
// Funktioita kommunikointiin TecDoc-tietokannan kanssa.
//

require 'tecdoc_asetukset.php';

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
// Hakee tuotteet annetuen tuotenumeron (articleNo) perusteella
//
function getArticleDirectSearchAllNumbersWithState($number) {
	$function = 'getArticleDirectSearchAllNumbersWithState';
	$params = [
		'lang' => TECDOC_LANGUAGE,
		'articleCountry' => TECDOC_COUNTRY,
		'provider' => TECDOC_PROVIDER,
		"articleNumber" => $number,
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
		'thumbnails' => true
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
	$tecdoc_products = getDirectArticlesByIds4($ids);

	// Yhdistetään TecDocista saatu data $products-taulukkoon
	foreach ($tecdoc_products as $tecdoc_product) {
		foreach ($products as $product) {
			if ($product->id == $tecdoc_product->directArticle->articleId) {
				$product->directArticle = $tecdoc_product->directArticle;
				$product->articleThumbnails = $tecdoc_product->articleThumbnails;
			}
		}
	}
}




/**
 * Palauttaa olioista koostuvan arrayn. 
 * Objekteilla attribuutit manuId ja manuName.
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
	
	// Pyyntö epäonnistui
	if ($response->status !== 200) {
		return [];
	}
	
	if (isset($response->data->array)) {
		return $response->data->array;
	}
	
	return [];
}


//hae kaikki assemblygoupNodeID:t
function getChildNodesAllLinkingTarget2 ($carID, $shortCutID) {
	$function = 'getChildNodesAllLinkingTarget2';
	$params = [
			'articleCountry' => TECDOC_COUNTRY,
			'lang' => TECDOC_LANGUAGE,
			'provider' => TECDOC_PROVIDER,
			"linked" => true,
			"linkingTargetId" => $carID,
			"linkingTargetType" => "P",
			"shortCutId" => $shortCutID,
			"childNodes" => false
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


