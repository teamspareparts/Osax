<?php
/**
 * @version 2017-04-16
 */
class PaymentAPI {

	/**
	 * @var int|null $merchant_id
	 */
	public static $merchant_id = null;
	/**
	 * @var string|null $merchant_auth_hash
	 */
	public static $merchant_auth_hash = null;

	/**
	 * @var int|null $order_id
	 */
	public static $order_id = null;
	/**
	 * @var float|null
	 */
	public static $amount = null;

	/**
	 * @var
	 */
	public static $reference_number = ''; // Tyhjä tarkoituksella
	/**
	 * @var string
	 */
	public static $order_descr = ''; // Tyhjä tarkoituksella
	/**
	 * @var string
	 */
	public static $currency = "EUR";
	/**
	 * @var string
	 */
	public static $return_addr = null; // Haetaan config.ini tiedostosta
	/**
	 * @var string
	 */
	public static $cancel_addr = null; // Haetaan config.ini tiedostosta
	/**
	 * @var string
	 */
	public static $pending_addr = ''; // Tyhjä tarkoituksella. Ei käytössä Paytrailin API:ssa.
	/**
	 * @var string|null $notify_addr
	 */
	public static $notify_addr = null; // Haetaan config.ini tiedostosta
	/**
	 * @var string $type
	 */
	public static $type = "S1"; // S1-form. Yksinkertaisempi vaihtoehto.
	/**
	 * @var string
	 */
	public static $culture = "fi_FI";
	/**
	 * @var string
	 */
	public static $preselected_method = ''; // Tyhjä tarkoituksella
	/**
	 * @var
	 */
	public static $mode = "1";
	/**
	 * @var
	 */
	public static $visible_method = ''; // Tyhjä tarkoituksella
	/**
	 * @var
	 */
	public static $group = ''; // Tyhjä tarkoituksella. Ei käytössä Paytrailin API:ssa.

	/**
	 * @var null
	 */
	public static $auth_code = '';

	public static function haeConfigTiedot() {
		$config = parse_ini_file( "./config/config.ini.php" );
		PaymentAPI::$return_addr = $config[ 'return_osoite' ];
		PaymentAPI::$cancel_addr = $config[ 'cancel_osoite' ];
		PaymentAPI::$notify_addr = $config[ 'notify_osoite' ];
		PaymentAPI::$merchant_id = $config[ 'merch_id' ];
		PaymentAPI::$merchant_auth_hash = $config[ 'merch_auth' ];
	}

	/**
	 * @param int   $tilaus_id <p> Tilauksen ID
	 * @param float $summa     <p> Tilauksen maksettava summa
	 */
	public static function preparePaymentFormInfo( /*int*/ $tilaus_id, /*float*/ $summa ) {
		// Tarkistetaan, että tarvittavat tiedot on haettu config.ini:stä.
		if ( PaymentAPI::$merchant_id === null ) {
			PaymentAPI::haeConfigTiedot();
		}
		PaymentAPI::$order_id = $tilaus_id;
		PaymentAPI::$amount = round( $summa, 2 );
		PaymentAPI::calculateAuthCode();
	}

	/**
	 * @param string $formType [optional] <p> default = 'S1'
	 */
	private static function calculateAuthCode( /*string*/ $formType = 'S1' ) {
		if ( $formType === 'S1' ) {
			PaymentAPI::$auth_code = PaymentAPI::$merchant_auth_hash . '|' . PaymentAPI::$merchant_id . '|' .
				PaymentAPI::$amount . '|' . PaymentAPI::$order_id . '|' . PaymentAPI::$reference_number . '|' .
				PaymentAPI::$order_descr . '|' . PaymentAPI::$currency . '|' . PaymentAPI::$return_addr . '|' .
				PaymentAPI::$cancel_addr . '|' . PaymentAPI::$pending_addr . '|' . PaymentAPI::$notify_addr . '|' .
				PaymentAPI::$type . '|' . PaymentAPI::$culture . '|' . PaymentAPI::$preselected_method . '|' .
				PaymentAPI::$mode . '|' . PaymentAPI::$visible_method . '|' . PaymentAPI::$group;
		}
		PaymentAPI::$auth_code = strtoupper( md5( PaymentAPI::$auth_code ) );
	}

	/**
	 * @param array $getVariables <p> $_GET-arvot sellaisenaan (assoc-array).
	 * @param bool  $isCancel     [otional] <p> Onko maksun peruutus?
	 * @return bool
	 */
	public static function checkReturnAuthCode( array $getVariables, /*bool*/ $isCancel = false ) {
		// Tarkistetaan, että tarvittavat tiedot on haettu config.ini:stä.
		if ( PaymentAPI::$merchant_id === null ) {
			PaymentAPI::haeConfigTiedot();
		}

		if ( $isCancel ) {
			PaymentAPI::$auth_code = $getVariables[ 'ORDER_NUMBER' ] . '|' . $getVariables[ 'TIMESTAMP' ] . '|' .
				PaymentAPI::$merchant_auth_hash;
		}
		else {
			PaymentAPI::$auth_code = $getVariables[ 'ORDER_NUMBER' ] . '|' . $getVariables[ 'TIMESTAMP' ]. '|' .
				$getVariables[ 'PAID' ] . '|' . $getVariables[ 'METHOD' ] . '|' . PaymentAPI::$merchant_auth_hash;
		}

		PaymentAPI::$auth_code = strtoupper( md5( PaymentAPI::$auth_code ) );

		if ( PaymentAPI::$auth_code == $getVariables[ 'RETURN_AUTHCODE' ] ) {
			return true;
		}

		return false;
	}

	/**
	 * @param DByhteys $db
	 * @param User     $user
	 * @param int      $tilausID
	 * @param int      $ostoskoriID
	 * @return bool
	 */
	public static function peruutaTilausPalautaTuotteet( DByhteys $db, User $user,
			/*int*/ $tilausID, /*int*/ $ostoskoriID ) {
		$conn = $db->getConnection();
		$conn->beginTransaction();

		try {
			$stmt = $conn->prepare(
				'UPDATE tilaus SET maksettu = -1, kasitelty = -1 WHERE id = ? AND kayttaja_id = ?' );
			$stmt->execute( [ $tilausID, $user->id ] );

			$stmt = $conn->prepare( "SELECT tuote_id, kpl FROM tilaus_tuote WHERE tilaus_id = ?" );
			$stmt->execute( [ $tilausID ] );
			$results = $stmt->fetchAll();

			// Tuotteiden varastosaldojen palautus takaisin.
			$placeholders = implode( ',', array_fill( 0, count( $results ), '(?,?)' ) );
			$values = array();
			$stmt = $conn->prepare( "INSERT INTO temp_tuote (tuote_id, varastosaldo) VALUES {$placeholders}" );
			foreach ( $results as $tuote ) {
				array_push( $values, $tuote->tuote_id, $tuote->kpl );
			}
			$stmt->execute( $values );

			// Yhdistetään temp_taulu tuote-taulun tietoihin, joka päivittää varastosaldot takaisin.
			$stmt = $conn->prepare( "
				UPDATE tuote 
				JOIN temp_tuote ON tuote.id = temp_tuote.tuote_id 
				SET tuote.varastosaldo = tuote.varastosaldo + temp_tuote.varastosaldo, tuote.paivitettava = 1" );
			$stmt->execute();

			// Lisätään lopuksi tuotteet takaisin ostoskoriin.
			$stmt = $conn->prepare( "
				INSERT INTO ostoskori_tuote (ostoskori_id, tuote_id, kpl_maara)
				SELECT ?, tuote_id, varastosaldo FROM temp_tuote
 				ON DUPLICATE KEY UPDATE kpl_maara = VALUES(kpl_maara)" );
			$stmt->execute( [ $ostoskoriID ] );

			// Tyhjennetään temp_tuote -taulu.
			$conn->query( "DELETE FROM temp_tuote" );

			$conn->commit();

			return true;

		} catch ( PDOException $ex ) {
			$conn->rollback();

			return false;
		}
	}
}
