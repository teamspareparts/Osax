<?php
/**
 * Staattinen luokka Eoltaksen webservicen käyttöön.
 */

class EoltasWebservice {
	private static $request_url = "https://b2b.eoltas.lt/index.php?cl=nfqwebservicemainview";
	private static $config_path = './config/config.ini.php';
	private static $timeout = 3; //sekuntia

	private static function sendRequest(array $action_fields) {
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

	static function searchProduct(/*string*/ $query, /*string*/ $manufacturers = '') {
		$fields = array(
			'action' => 'searchProduct',
			'query' => $query,
			'manufacturers' => $manufacturers
		);
		return self::sendRequest($fields);
	}
}