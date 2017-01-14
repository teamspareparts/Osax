<?php
/**
 * TODO: WIP don't use!
 * Luokka sunnitteilla.
 */
class Email {

	//TODO: Move to .ini file
	private static $request_url = 'https://api.sendgrid.com/api/mail.send.json';
	private static $user = "";
	private static $pass = "";

	const admin_email = 'myynti@osax.fi';
	private $kohde_email = NULL;
	private $subject = NULL;
	private $message = NULL;

	private $file = NULL;
	private $fileName = NULL;

	/**
	 * Email constructor.
	 * Static class, joten tätä ei ole tarkoitus käyttää. Hence: "private".
	 */
	private function __construct () {}

	private function sendMail() {

		//sähköpostin parametrit
		$params = array(
			'api_user'  => Email::$user,
			'api_key'   => Email::$pass,
			'to'        => "$this->kohde_email",
			'subject'   => "$this->subject", //otsikko
			'html'      => "$this->message", //HTML runko
			'text'      => "",
			'from'      => "noreply@osax.com", //lähetysosoite
			'files['.$this->fileName.']' => $this->file //liitetiedosto
		);

		// Luodaan curl pyyntö
		$session = curl_init( Email::$request_url );
		// Käytetään HTTP POSTia
		curl_setopt ($session, CURLOPT_POST, true);
		// Runko
		curl_setopt ($session, CURLOPT_POSTFIELDS, $params);
		// Palauta vastaus, mutta ilman headereja
		curl_setopt($session, CURLOPT_HEADER, false);
		// Käytetään TLS ei SSL3
		curl_setopt($session, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1_2);
		curl_setopt($session, CURLOPT_RETURNTRANSFER, true);

		// Lähetetään sposti
		curl_exec($session);
		curl_close($session);
	}

	function lahetaSalasanaLinkki() {}

	function lahetaTilausvahvistus() {}

	function lahetaNoutolista() {}

	function lahetaIlmoitus_TilausLahetetty( /*String*/ $email, /*int*/ $tilausnro ) {
		$this->kohde_email = $email;
		$this->subject = "Tilaus {$tilausnro}";
		$this->message = "Hei! Tilauksesi {$tilausnro} on nyt lähetetty.";
		//TODO: Lisää linkki tilausinfoon?

		$this->sendMail();
		Email::sendMail();
	}

	function lahetaIlmoitus_EpailyttavaIP() {}
}
