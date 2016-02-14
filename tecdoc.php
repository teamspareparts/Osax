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
function get_products_by_number($number) {
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

	return $response->data->array;
}

//
// Hakee tuotteet annettujen tunnisteiden (articleId) perusteella
//
function get_products_by_id($ids) {
	$function = 'getDirectArticlesByIds4';
	$params = [
		'lang' => TECDOC_LANGUAGE,
		'articleCountry' => TECDOC_COUNTRY,
		'provider' => TECDOC_PROVIDER,
		'basicData' => true,
		'articleId' => ['array' => $ids],
	];

	// Lähetetään JSON-pyyntö
	$request =	[$function => $params];
	$response = _send_json($request);

	// Pyyntö epäonnistui
	if ($response->status !== 200) {
		return [];
	}

	// Haetaan tuotteet helpommin käsiteltävään taulukkoon
	$products = [];
	foreach ($response->data->array as $product) {
		if (isset($product->directArticle)) {
			array_push($products, $product->directArticle);
		}
	}

	return $products;
}