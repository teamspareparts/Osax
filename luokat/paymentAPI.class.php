<?php
/**
 * @version 2017-04-09
 */
class PaymentAPI {

	private static $merchant_id = null;
	private static $merchant_auth_hash = null;

	private static $order_id = null;
	private static $amount = null;

	private static $reference_number = ''; // Tyhjä tarkoituksella
	private static $order_descr = ''; // Tyhjä tarkoituksella
	private static $currency = "EUR";
	private static $return_addr = ''; // Haetaan config.ini tiedostosta
	private static $cancel_addr = ''; // Haetaan config.ini tiedostosta
	private static $pending_addr = ''; // Tyhjä tarkoituksella. Ei käytössä Paytrailin API:ssa.
	private static $notify_addr = ''; // Haetaan config.ini tiedostosta
	private static $type = "S1"; // S1-form. Yksinkertaisempi vaihtoehto.
	private static $culture = "fi_FI";
	private static $preselected_method = ''; // Tyhjä tarkoituksella
	private static $mode = "1";
	private static $visible_method = ''; // Tyhjä tarkoituksella
	private static $group = ''; // Tyhjä tarkoituksella. Ei käytössä Paytrailin API:ssa.

	private static $auth_code = '';

	/**
	 * Email constructor.
	 * Static class, joten tätä ei ole tarkoitus käyttää. Hence: "private".
	 */
	private function __construct() {
	}

	/**
	 * @param int   $tilaus_id <p> Tilauksen ID
	 * @param float $summa     <p> Tilauksen maksettava summa
	 */
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
		PaymentAPI::$order_id = $tilaus_id;
		PaymentAPI::$amount = round( $summa, 2 );
		PaymentAPI::haeConfigTiedot();
		PaymentAPI::calculateAuthCode();
	}

	/**
	 * Käyttötapa: <code><?= PaymentAPI::getS1Form ?></code> HTML:n puolella.<p>
	 * <code>Payment::preparePayment</code> pitää kutsua ennen tämän metodin käyttöä.
	 * @return string <p> HTML-form
	 */
	public static function getS1Form() {
		return "
			<form action='https://payment.paytrail.com/' method='post' id='payment'>
				<input name='MERCHANT_ID' type='hidden' value='" . PaymentAPI::$merchant_id . "'>
				<input name='AMOUNT' type='hidden' value='" . PaymentAPI::$amount . "'>
				<input name='ORDER_NUMBER' type='hidden' value='" . PaymentAPI::$order_id . "'>
				<input name='REFERENCE_NUMBER' type='hidden' value='" . PaymentAPI::$reference_number . "'>
				<input name='ORDER_DESCRIPTION' type='hidden' value='" . PaymentAPI::$order_descr . "'>
				<input name='CURRENCY' type='hidden' value='" . PaymentAPI::$currency . "'>
				<input name='RETURN_ADDRESS' type='hidden' value='" . PaymentAPI::$return_addr . "'>
				<input name='CANCEL_ADDRESS' type='hidden' value='" . PaymentAPI::$cancel_addr . "'>
				<input name='PENDING_ADDRESS' type='hidden' value='" . PaymentAPI::$pending_addr . "'>
				<input name='NOTIFY_ADDRESS' type='hidden' value='" . PaymentAPI::$notify_addr . "'>
				<input name='TYPE' type='hidden' value='" . PaymentAPI::$type . "'>
				<input name='CULTURE' type='hidden' value='" . PaymentAPI::$culture . "'>
				<input name='PRESELECTED_METHOD' type='hidden' value='" . PaymentAPI::$preselected_method . "'>
				<input name='MODE' type='hidden' value='" . PaymentAPI::$mode . "'>
				<input name='VISIBLE_METHODS' type='hidden' value='" . PaymentAPI::$visible_method . "'>
				<input name='GROUP' type='hidden' value='" . PaymentAPI::$group . "'>
				<input name='AUTHCODE' type='hidden' value='" . PaymentAPI::$auth_code . "'>
				<input type='submit' value='Siirry maksamaan'>
			</form>";
	}

	/**
	 * @param string $formType [optional] <p> default = 'S1'
	 */
	private static function calculateAuthCode( /*string*/ $formType = 'S1' ) {
		if ( $formType === 'S1' ) {
			PaymentAPI::$auth_code = PaymentAPI::$merchant_auth_hash . '|' . PaymentAPI::$merchant_id . '|' . PaymentAPI::$amount . '|' . PaymentAPI::$order_id . '|' . PaymentAPI::$reference_number . '|' . PaymentAPI::$order_descr . '|' . PaymentAPI::$currency . '|' . PaymentAPI::$return_addr . '|' . PaymentAPI::$cancel_addr . '|' . PaymentAPI::$pending_addr . '|' . PaymentAPI::$notify_addr . '|' . PaymentAPI::$type . '|' . PaymentAPI::$culture . '|' . PaymentAPI::$preselected_method . '|' . PaymentAPI::$mode . '|' . PaymentAPI::$visible_method . '|' . PaymentAPI::$group;
		}
		PaymentAPI::$auth_code = strtoupper( md5( PaymentAPI::$auth_code ) );
	}

	/**
	 * @param array $getVariables <p> $_GET-arvot sellaisenaan (assoc-array).
	 * @param bool  $isCancel     [otional] <p> Onko maksun peruutus?
	 * @return bool
	 */
	public static function checkReturnAuthCode( array $getVariables, /*bool*/ $isCancel = false ) {
		PaymentAPI::haeConfigTiedot();
		if ( $isCancel ) {
			PaymentAPI::$auth_code = $getVariables[ 'ORDER_NUMBER' ] . '|' . $getVariables[ 'TIMESTAMP' ] . '|' . PaymentAPI::$merchant_auth_hash;
		}
		else {
			PaymentAPI::$auth_code = $getVariables[ 'ORDER_NUMBER' ] . '|' . $getVariables[ 'TIMESTAMP' ] . '|' . $getVariables[ 'PAID' ] . '|' . $getVariables[ 'METHOD' ] . '|' . PaymentAPI::$merchant_auth_hash;
		}

		PaymentAPI::$auth_code = strtoupper( md5( PaymentAPI::$auth_code ) );

		if ( PaymentAPI::$auth_code == $getVariables[ 'RETURN_AUTHCODE' ] ) {
			return true;
		}

		return false;
	}
}
