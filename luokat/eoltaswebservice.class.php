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
	 * @throws Exception
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

		$response = curl_exec($ch); // Send curl

		// Tarkistus virheen varalta
		if ( curl_errno($ch) ) {
			throw new Exception('Curl error: ' . curl_error($ch));
		}

		curl_close($ch);
		$response = json_decode($response); // JSON to PHP-Object

		if ( is_null($response) ) {
			throw new Exception('Problem with curl.');
		}
		if ( !$response->acknowledge ) {
			// Response OK, Error in parameters
			throw new Exception( $response->errors[0] );
		}

		// Return php object
		return $response;
	}

	/**
	 * Etsii tuotteita webservicestä annetun hakunumeron ja/tai valmistajien perusteella.
	 * @param string $query
	 * @param string $manufacturers
	 * @return stdClass
	 * @throws Exception
	 */
	private static function searchProduct( string $query, string $manufacturers ) : stdClass {
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
	 * @throws Exception
	 */
	private static function getProduct( string $id ) : stdClass {
		$fields = array(
			'action' => 'getProduct',
			'id' => $id
		);
		return self::sendRequest($fields);
	}

	/**
	 * Hakee ostoskorin sisällön.
	 * @return stdClass
	 * @throws Exception
	 */
	public static function getBasket() : stdClass {
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
	 * @throws Exception
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
	 * @throws Exception
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
	 * @throws Exception
	 */
	private static function removeFromBasket( string $id ) : stdClass {
		$fields = array(
			'action' => 'removeFromBasket',
			'id' => $id
		);
		return self::sendRequest($fields);
	}

	/**
	 * KÄYTÄ ERITTÄIN VAROEN!
	 * Tekee tilauksen.
	 * @return stdClass
	 * @throws Exception
	 */
	private static function order() : stdClass {
		$fields = array(
			'action' => 'order',
			'deliveryId' => 'oxidstandard',
			'addressId' => 'adc7cc6698e9a9689163b7eddc0d280f'
		);
		return self::sendRequest($fields);
	}

	/**
	 * Tyhjentää Eoltaksen ostoskorin.
	 * @return bool
	 * @throws Exception
	 */
	public static function clearBasket() : bool {
		// Haetaan webservicen ostoskori
		$basket = self::getBasket();
		// Poistetaan tuote kerrallaan
		foreach ( $basket->response->basket as $tuote ) {
			$success = self::removeFromBasket($tuote->productId);
			if ( !$success ) {
				throw new Exception('Error while removing product from basket.');
			}
		}
		return true;
	}

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
	public static function checkEoltasHankintapaikkaId( int $id ) : bool {
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
	 * @return string|null <p> Palauttaa löydetyn id:n tai null
	 * @throws Exception
	 */
	private static function getEoltasProductId( string $articleNo, string $brandName ) {
		$eoltas_data = EoltasWebservice::searchProduct( $articleNo , $brandName );
		// Etsitään oikea tuote
		foreach ( $eoltas_data->response->products as $product) {
			$product->supplierCode = str_replace(" ", "", $product->supplierCode);
			$articleNo = str_replace(" ", "", $articleNo);
			if ( strcasecmp($articleNo, $product->supplierCode) === 0 && strcasecmp($brandName, $product->brandName) === 0 ) {
				// Oikea tuote löytyi
				return $product->id;
			}
		}
		return null;
	}

	/**
	 * Hakee ja palauttaa Eoltas-tuotteen tehdassaldon.
	 * @param int $hankintapaikka_id
	 * @param string $articleNo
	 * @param string $brandName
	 * @return int|null
	 */
	public static function getEoltasTehdassaldo( int $hankintapaikka_id, string $articleNo, string $brandName ) {
		// Vain Eoltaksen tuotteet
		if ( !self::checkEoltasHankintapaikkaId( $hankintapaikka_id ) ) {
			return null;
		}
		// Etsitään tuote webservicestä
		try {
			$eoltas_data = EoltasWebservice::searchProduct($articleNo, $brandName);
		} catch (Exception $e) {
			return null;
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
	 * @param int $tuote_id
	 * @param int $kpl
	 * @param int $tilaus_id
	 * @return bool
	 */
	private static function addProductToEoltasOrderBook( DByhteys $db, int $tuote_id, int $kpl, int $tilaus_id ) : bool {
		$eoltas_hankintapaikka_id = EoltasWebservice::getEoltasHankintapaikkaId();
		// Selite tilauskirjalle
		$selite = "PIKATILAUS, TILAUS {$tilaus_id}, {$kpl}kpl, " . date('d.m.Y') . " ";
		// Haetaan Eoltaksen avoimen tilauskirjan id
		$sql = "SELECT id FROM ostotilauskirja WHERE hankintapaikka_id = ? AND toimitusjakso != 0";
		$otk_id = $db->query($sql, [$eoltas_hankintapaikka_id]);
		if ( !$otk_id ) {
			return false;
		}
		// Lisätään tuote ostotilauskirjalle
		$sql = "INSERT INTO ostotilauskirja_tuote (ostotilauskirja_id, tuote_id, tilaustuote,
 					kpl, selite, lisays_kayttaja_id)
				VALUES (?,?,1,?,?,0)
				ON DUPLICATE KEY UPDATE kpl = kpl + VALUES(kpl), selite = CONCAT(VALUES(selite), selite)";
		$result = $db->query($sql, [$otk_id->id, $tuote_id, $kpl, $selite]);
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
	 * @throws Exception
	 */
	private static function addProductToBasket( DByhteys $db, int $tuote_id, int $kpl ) : bool {
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
		$eoltas_id = self::getEoltasProductId( $tuote->articleNo, $tuote->valmistaja );
		if ( !$eoltas_id ) {
			return false;
		}
		// Lisätään tuote ostoskoriin
		$ostoskori = self::addToBasket( $eoltas_id, $kpl );
		if ( !$ostoskori ) {
			return false;
		}
		return true;
	}

	/**
	 * Tilaa tilauksen tilaustuotteet Eoltakselta.
	 * @param DByhteys $db
	 * @param int $tilaus_id
	 * @return bool
	 */
	public static function orderFromEoltas( DByhteys $db, int $tilaus_id, bool $indev ) : bool {
		if ( !$tilaus_id ) {
			return false;
		}
		$hankintapaikka_id = self::getEoltasHankintapaikkaId();
		// Haetaan tilauksen tilaustuotteet, jotka on tilattu Eoltakselta
		$sql = "SELECT tuote.id, tilaus_tuote.tilaustuote, tilaus_tuote.kpl
				FROM tilaus
				INNER JOIN tilaus_tuote
					ON tilaus.id = tilaus_tuote.tilaus_id
				INNER JOIN tuote
					ON tilaus_tuote.tuote_id = tuote.id
				WHERE tilaus.id = ?
					AND tilaus.maksettu = 1
					AND tilaus_tuote.tilaustuote = 1
					AND tuote.hankintapaikka_id = ?
					AND tilaus.tilaustuotteet_tilattu = 0";
		$tuotteet = $db->query($sql, [$tilaus_id, $hankintapaikka_id], FETCH_ALL);
		if ( !$tuotteet ) {
			return true;
		}
		try {
			// Tyhjennetään ostoskori varmuuden varalta
			$result = self::clearBasket();
			if ( !$result ) {
				throw new Exception('Ostoskoria ei voitu tyhjentää.');
			}

			// Lisätään tuotteet Eoltaksen ostoskoriin
			foreach ( $tuotteet as $tuote ) {
				$success = self::addProductToBasket($db, $tuote->id, $tuote->kpl);
				if ( !$success ) {
					throw new Exception('Tuotetta ei löytynyt webservicestä.');
				}
			}
			// Tarkastetaan, että Eoltaksella on tarpeeksi tuotteita varastossa
			$vajaat_tuotteet = self::getInvalidProductsFromWebserviceBasket();
			if ( $vajaat_tuotteet ) {
				// Tilataan niin monta tuotetta kuin mahdollista
				foreach ( $vajaat_tuotteet as $tuote ) {
					if ( $tuote->amount > $tuote->stock ) {
						self::editBasket($tuote->productId, (int)$tuote->stock);
					}
					// Ilmoitus ylläpidolle, että tuotteita ei voinut tilata tarpeeksi!
					Email::lahetaIlmoitusRiittamatonEoltasTehdassaldo($tilaus_id, $vajaat_tuotteet);
				}
			}

			// Lisätään tuotteet tilauskirjalle
			foreach ( $tuotteet as $tuote ) {
				self::addProductToEoltasOrderBook($db, $tuote->id, $tuote->kpl, $tilaus_id);
			}

			// Tehdään tilaus
			if ( !$indev ) {
				$order = self::order();
			} else {
				self::clearBasket();
			}

		} catch ( Exception $e ) {
			// Yritetään tyhjentää ostoskori varmuuden varalta.
			try {
				self::clearBasket();
			} catch ( Exception $e ) {}
			echo $e->getMessage();
			return false;
		}
		return true;
	}

	/**
	 * Tarkistetaan, että Webservicen ostoskorissa varastosaldo on suurempi kuin tilattavien määrä
	 * @return array
	 * @throws Exception
	 */
	public static function getInvalidProductsFromWebserviceBasket() : array {
		$ostoskori = self::getBasket();
		$vajaat_tuotteet = [];
		foreach ( $ostoskori->response->basket as $tuote ) {
			if ( $tuote->amount > $tuote->stock ) {
				// Tilattavia tuotteita enemmän kuin varastossa
				$vajaat_tuotteet[] = $tuote;
			}
		}
		return $vajaat_tuotteet;
	}

	/**
	 * Tarkastetaan, että Eoltaksella on tarpeeksi tuotteita varastossa.
	 * @param array $tuotteet <p> Ostoskorin tuotteet.
	 * @return array
	 */
	public static function checkOstoskoriValidity( array $tuotteet ) : array {
		$vajaat_tuotteet = [];
		foreach ( $tuotteet as $tuote ) {
			// Tarkistus vain tilaustuotteille
			if ( !$tuote->tilaustuote ) {
				continue;
			}
			// Tarkistus vain Eoltaksen tuotteille
			if ( !self::checkEoltasHankintapaikkaId( $tuote->hankintapaikka_id ) ) {
				continue;
			}
			try {
				// Haetaan tuotteen Eoltas id
				$eoltas_id = self::getEoltasProductId($tuote->articleNo, $tuote->valmistaja);
				if ( !$eoltas_id ) {
					$vajaat_tuotteet[] = $tuote;
				} else {
					$eoltas_product = self::getProduct($eoltas_id);
					// Tarkastetaan tilattavien tuotteiden määrä
					if ( $tuote->kpl_maara > $eoltas_product->response->product->stock ) {
						// Tilattavia tuotteita enemmän kuin varastossa
						$tuote->eoltas_stock = $eoltas_product->response->product->stock;
						$vajaat_tuotteet[] = $tuote;
					}
				}
			} catch ( Exception $e ) {
				$vajaat_tuotteet[] = $tuote;
			}
		}
		return $vajaat_tuotteet;
	}

}