<?php declare(strict_types=1);
/**
 * Staattinen luokka Eoltaksen webservicen käyttöön.
 */

class EoltasWebservice {
	private static $request_url = 'https://b2b.eoltas.lt/index.php?cl=nfqwebservicemainview';
	private static $config_path = './config/config.ini.php';
	private static $timeout = 4; //sekuntia

	/**
	 * Curl pyynnön lähetys Eoltakselle.
	 * @param array $action_fields
	 * @return stdClass
	 */
	private static function sendRequest( array $action_fields ) : stdClass {
		$config = parse_ini_file( self::$config_path );
		$postfields = array(
			'oxid' => $config['eoltas_oxid'],
			'user' => $config['eoltas_user'],
			'token' => $config['eoltas_token'],
			'juridicalId' => $config['eoltas_juridical_id'],
			'contractId' => $config['eoltas_contract_id']
		);
		$postfields = array_merge($postfields, $action_fields);

		$ch = curl_init(self::$request_url);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS,  http_build_query($postfields));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1 );
		curl_setopt($ch, CURLOPT_HEADER, 0);
		// Salaus
		curl_setopt( $ch, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1_2 );
		// Timeout
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT ,0);
		curl_setopt($ch, CURLOPT_TIMEOUT, self::$timeout);

		$response = curl_exec($ch);
		curl_close($ch);

		// Return php object
		return json_decode($response);
	}

	/**
	 * Etsii tuotteita webservicestä annetun hakunumeron ja/tai valmistajien perusteella.
	 * @param string $query
	 * @param string $manufacturers
	 * @return stdClass
	 */
	static function searchProduct( string $query, string $manufacturers ) : stdClass {
		$fields = array(
			'action' => 'searchProduct',
			'query' => $query,
			'manufacturers' => $manufacturers
		);
		return self::sendRequest($fields);
	}

	/**
	 * Hakee touotteen id:n perusteella.
	 * @param string $id
	 * @return stdClass
	 */
	static function getProduct( string $id ) : stdClass {
		$fields = array(
			'action' => 'addToBasket',
			'id' => $id
		);
		return self::sendRequest($fields);
	}

	/**
	 * Hakee ostoskorin.
	 * @return stdClass
	 */
	static function getBasket() : stdClass {
		$fields = array(
			'action' => 'getBasket'
		);
		return self::sendRequest($fields);
	}

	/**
	 * Lisää tuotteen ostoskoriin. Palauttaa ostoskorin.
	 * @param string $id <p> Eoltaksen oma id
	 * @param int $amount
	 * @return stdClass
	 */
	private static function addToBasket( string $id, int $amount ) : stdClass {
		$fields = array(
			'action' => 'addToBasket',
			'id' => $id,
			'amount' => $amount
		);
		return self::sendRequest($fields);
	}

	/**
	 * Muokkaa tuotetta ostoskorissa.
	 * @param string $id
	 * @param int $amount
	 * @return stdClass
	 */
	private static function editBasket( string $id, int $amount ) : stdClass {
		$fields = array(
			'action' => 'editBasket',
			'id' => $id,
			'amount' => $amount
		);
		return self::sendRequest($fields);
	}

	/**
	 * Poistaa tuotteen ostoskorista.
	 * @param string $id
	 * @return stdClass
	 */
	private static function removeFromBasket( string $id ) : stdClass {
		$fields = array(
			'action' => 'addToBasket',
			'id' => $id
		);
		return self::sendRequest($fields);
	}

	/**
	 * Palauttaa config tiedostoon tallennetun Eoltaksen hankintapaikka id:n.
	 * @return int
	 */
	static function getEoltasHankintapaikkaId() : int {
		$config = parse_ini_file( self::$config_path );
		return (int)$config['eoltas_hankintapaikka_id'];
	}

	/**
	 * Hakee ja palauttaa Eoltas tuotteen tehdassaldon.
	 * @param int $hankintapaikka_id
	 * @param string $articleNo
	 * @return int|null
	 */
	static function getEoltasTehdassaldo( int $hankintapaikka_id, string $articleNo, string $brandName ) {
		$eoltas_hankintapaikka_id = EoltasWebservice::getEoltasHankintapaikkaId();
		// Tehdään webservice haku vain Eoltaksen tuotteille
		if ( $hankintapaikka_id != $eoltas_hankintapaikka_id) {
			return null;
		}
		$eoltas_data = EoltasWebservice::searchProduct( $articleNo , $brandName);
		// Tarkistetaan webservice-yhteys
		if ( !$eoltas_data || !$eoltas_data->acknowledge ) {
			trigger_error('Cannot connect to Eoltas webservice.');
		}
		// Etsitään oikea tuote ja lisätään tuotteelle tehdassaldo
		foreach ( $eoltas_data->response->products as $eoltas_product) {
			$eoltas_product->supplierCode = str_replace(" ", "", $eoltas_product->supplierCode);
			$articleNo = str_replace(" ", "", $articleNo);
			if ( $articleNo == $eoltas_product->supplierCode ) {
				return $eoltas_product->stock;
			}
		}
		return null;
	}


	private static function addProductToEoltasOrderBook( string $articleNo, string $brandNo ) : bool {
		$eoltas_hankintapaikka_id = EoltasWebservice::getEoltasHankintapaikkaId();

	}

}