<?php declare(strict_types=1);
/**
 * Staattinen luokka Eoltaksen webservicen käyttöön.
 */

class EoltasWebservice {
	private static $request_url = 'https://b2b.eoltas.lt/index.php?cl=nfqwebservicemainview';
	private static $config_path = './config/config.ini.php';
	private static $timeout = 5; //sekuntia

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
	 * Hakee ostoskorin sisällön.
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
	 * KÄYTÄ ERITTÄIN VAROEN!
	 * Tekee tilauksen.
	 * @return stdClass
	 */
	/*private static function order() : stdClass {
		$fields = array(
			'action' => 'order',
			'deliveryId' => 'oxidstandard',
			'addressId' => 'adc7cc6698e9a9689163b7eddc0d280f'
		);
		return self::sendRequest($fields);
	}*/

	/**
	 * Palauttaa config tiedostoon tallennetun Eoltaksen hankintapaikka id:n.
	 * @return int
	 */
	private static function getEoltasHankintapaikkaId() : int {
		$config = parse_ini_file( self::$config_path );
		return (int)$config['eoltas_hankintapaikka_id'];
	}

	/**
	 * Tarkastetaan onko tuotteen hankintapaikka Eoltas.
	 * @param int $id
	 * @return bool
	 */
	private static function checkEoltasHankintapaikkaId( int $id ) : bool {
		$eoltas_hankintapaikka_id = EoltasWebservice::getEoltasHankintapaikkaId();
		if ( $id != $eoltas_hankintapaikka_id) {
			return false;
		}
		return true;
	}

	/**
	 * Hakee Eoltaksen tuote-id:n.
	 * @param string $articleNo
	 * @param string $brandName
	 * @param string $brandId
	 * @return string|null
	 */
	private static function getEoltasProductId( string $articleNo, string $brandName, string $brandId ) {
		$eoltas_data = EoltasWebservice::searchProduct( $articleNo , $brandName );
		// Tarkistetaan webservice-yhteys
		if ( !$eoltas_data || !$eoltas_data->acknowledge ) {
			trigger_error('Cannot connect to Eoltas webservice.');
		}
		// Etsitään oikea tuote
		foreach ( $eoltas_data->response->products as $product) {
			$product->supplierCode = str_replace(" ", "", $product->supplierCode);
			$articleNo = str_replace(" ", "", $articleNo);
			if ( strcasecmp($articleNo, $product->supplierCode) === 0 && $brandId == $product->brandId ) {
				// Oikea tuote löytyi
				return $product->id;
			}
		}
		return null;
	}

	/**
	 * Hakee ja palauttaa Eoltas tuotteen tehdassaldon.
	 * @param int $hankintapaikka_id
	 * @param string $articleNo
	 * @param string $brandName
	 * @return int|null
	 */
	static function getEoltasTehdassaldo( int $hankintapaikka_id, string $articleNo, string $brandName ) {
		if ( !self::checkEoltasHankintapaikkaId( $hankintapaikka_id ) ) {
			return null;
		}
		$eoltas_data = EoltasWebservice::searchProduct( $articleNo , $brandName );
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

	/**
	 * Lisää tuotteen Eoltaksen avoimelle ostotilauskirjalle.
	 * @param DByhteys $db
	 * @param int $id <p> Tuotteen id.
	 * @param int $kpl
	 * @param int $tilaus_id
	 * @return bool
	 */
	static function addProductToEoltasOrderBook( DByhteys $db, int $id, int $kpl, int $tilaus_id ) : bool {
		$eoltas_hankintapaikka_id = EoltasWebservice::getEoltasHankintapaikkaId();
		// Haetaan Eoltaksen avoimen ostotilauskirjan id
		$sql = "SELECT id FROM ostotilauskirja WHERE hankintapaikka_id = ? AND toimitusjakso != 0";
		$otk_id = $db->query($sql, [$eoltas_hankintapaikka_id]);
		$selite = "Tilaus id: {$tilaus_id}, {$kpl}kpl.";
		if ( !$otk_id ) {
			return false;
		}
		// Lisätään tuote ostotilauskirjalle
		$sql = "INSERT INTO ostotilauskirja_tuote (ostotilauskirja_id, tuote_id, tilaustuote,
 					kpl, selite, lisays_kayttaja_id)
				VALUES (?,?,1,?,?,0)
				ON DUPLICATE KEY UPDATE kpl = kpl + VALUES(kpl), selite = CONCAT(VALUES(selite), selite)";
		$result = $db->query($sql, [$otk_id->id, $id, $kpl, $selite]);
		if ( !$result ) {
			return false;
		}
		return true;
	}

	/**
	 * Lisätään tuote Eoltaksen ostoskoriin tuote-id:n perusteella.
	 * @param DByhteys $db
	 * @param int $tuote_id
	 * @param int $kpl
	 * @return bool
	 */
	static function addProductToBasket( DByhteys $db, int $tuote_id, int $kpl ) : bool {
		$sql = "SELECT hankintapaikka_id, articleNo, brandNo, valmistaja FROM tuote WHERE id = ?";
		$tuote = $db->query($sql, [$tuote_id]);
		if ( !$tuote ) {
			return false;
		}
		// Webservice-kyselyt vain Eoltaksen tuotteille
		if ( !self::checkEoltasHankintapaikkaId( $tuote->hankintapaikka_id ) ) {
			return false;
		}
		// Haetaan Eoltaksen oma tuote-id
		$eoltas_id = self::getEoltasProductId( $tuote->articleNo, $tuote->valmistaja, $tuote->brandNo );
		if ( !$eoltas_id ) {
			return false;
		}
		// Lisätään tuote ostoskoriin
		$ostoskori = self::addToBasket( $eoltas_id, $kpl );
		return true;
	}

	//TODO: KESKEN
	static function addBasketProductsToEoltasBasket( DByhteys $db, int $tilaus_id, array $tuotteet ) : array {
		// Lisätään tuotteet Eoltaksen ostoskoriin
		foreach ( $tuotteet as $tuote ) {
			//self::addProductToBasket( $db, $tuote->id, $tuote->kpl );
			//self::addProductToEoltasOrderBook( $db, $tuote->id, $tuote->kpl, $tilaus_id );
		}
		// Palautetaan tuotteet, joita yritetään tilata enemmäin kuin tehtaalla varastossa
		return self::getInvalidProductsFromBasket();
	}

	static function orderFromEoltas() : bool {
		if ( self::getInvalidProductsFromBasket() ) {
			return false;
		}
		//$order = self::order();
	}

	/**
	 * Tarkastetaan, että tilattavia tuotteita on tarpeeksi.
	 * @return array <p> Tuotteet joita ei ollut tarpeeksi.
	 */
	static function getInvalidProductsFromBasket() : array {
		$ostoskori = self::getBasket();
		$vajaat_tuotteet = [];
		foreach ( $ostoskori->response->basket as $product ) {
			if ( $product->amount > $product->stock ) {
				// Tilattavia tuotteita enemmän kuin varastossa
				$vajaat_tuotteet[] = $product;
			}
		}
		return $vajaat_tuotteet;
	}

}