<?php

/**
 * @version 2017-02-xx <p> WIP
 */
class PaymentAPI {

	/**
	 * @var string <p> Public test authentication.
	 */
	private static $merchant_auth_hash = "6pKF4jkv97zmqBJ3ZL8gUw5DfT2NMQ";
	/**
	 * @var string <p> Public test merchant ID.
	 */
	private static $merchant_id = "13466";

	private static $amount = '';
	private static $order_id = "123456";

	private static $reference_number = ''; // Tyhjä tarkoituksella
	private static $order_descr = ''; // Tyhjä tarkoituksella
	private static $currency = "EUR";
	private static $return_addr = "https://www.osax.fi/payment_process.php";
	private static $cancel_addr = "https://www.osax.fi/payment_cancel.php";
	private static $pending_addr = ''; // Tyhjä tarkoituksella. Ei käytössä Paytrailin API:ssa.
	private static $notify_addr = "https://www.osax.fi/payment_notify.php";
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
	public static function preparePaymentFormInfo( /*int*/ $tilaus_id, /*float*/ $summa ) {
		PaymentAPI::$order_id = $tilaus_id;
		PaymentAPI::$amount = $summa;
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
	 * @param string $formType [optional] default = 'S1'
	 */
	private static function calculateAuthCode( /*string*/ $formType = 'S1' ) {
		if ( $formType === 'S1' ) {
			PaymentAPI::$auth_code = PaymentAPI::$merchant_auth_hash . '|' . PaymentAPI::$merchant_id . '|' . PaymentAPI::$amount . '|' . PaymentAPI::$order_id . '|' . PaymentAPI::$reference_number . '|' . PaymentAPI::$order_descr . '|' . PaymentAPI::$currency . '|' . PaymentAPI::$return_addr . '|' . PaymentAPI::$cancel_addr . '|' . PaymentAPI::$pending_addr . '|' . PaymentAPI::$notify_addr . '|' . PaymentAPI::$type . '|' . PaymentAPI::$culture . '|' . PaymentAPI::$preselected_method . '|' . PaymentAPI::$mode . '|' . PaymentAPI::$visible_method . '|' . PaymentAPI::$group;
		}
		PaymentAPI::$auth_code = strtoupper( md5( PaymentAPI::$auth_code ) );
	}

	/**
	 * @param array $getVariables <p> $_GET-arvot sellaisenaan.
	 * @param bool  $isCancel     [otional] <p> Onko maksun peruutus?
	 * @return bool
	 */
	public static function checkReturnAuthCode( array $getVariables, /*bool*/ $isCancel = false ) {
		if ( $isCancel ) {
			PaymentAPI::$auth_code = $getVariables[ 'ORDER_NUMBER' ] . '|' . $getVariables[ 'TIMESTAMP' ];
		}
		else {
			PaymentAPI::$auth_code = $getVariables[ 'ORDER_NUMBER' ] . '|' . $getVariables[ 'TIMESTAMP' ] . '|' . $getVariables[ 'PAID' ] . '|' . $getVariables[ 'METHOD' ];
		}

		PaymentAPI::$auth_code = strtoupper( md5( PaymentAPI::$auth_code ) );

		if ( PaymentAPI::$auth_code === $getVariables[ 'RETURN_AUTHCODE' ] ) {
			return true;
		}

		return false;
	}
}
