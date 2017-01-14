<?php
/**
 * TODO: WIP don't use! --JJ
 * Luokka sunnitteilla.
 */
class Email {

	//TODO: Move to .ini file? --JJ
	private static $request_url = 'https://api.sendgrid.com/api/mail.send.json';
	private static $user = "";
	private static $pass = "";

	const admin_email = 'myynti@osax.fi';
	private static $target_email = NULL;
	private static $subject = NULL;
	private static $message = NULL;

	private static $file = NULL;
	private static $fileName = NULL;

	/**
	 * Email constructor.
	 * Static class, joten tätä ei ole tarkoitus käyttää. Hence: "private".
	 */
	private function __construct () {}

	/**
	 * //TODO: PHPdoc
	 */
	private static function sendMail() {

		//sähköpostin parametrit
		$params = array(
			'api_user'  => Email::$user,
			'api_key'   => Email::$pass,
			'to'        => Email::$target_email,
			'subject'   => Email::$subject, //otsikko
			'html'      => Email::$message, //HTML runko
			'text'      => "",
			'from'      => "noreply@osax.com", //lähetysosoite
			'files['.Email::$fileName.']' => Email::$file //liitetiedosto
		);

		// Luodaan cURL pyyntö.
		$session = curl_init( Email::$request_url );
		// Käytetään HTTP POSTia.
		curl_setopt ($session, CURLOPT_POST, true);
		// Lisätään viestin runko, otsikko, jne.
		curl_setopt ($session, CURLOPT_POSTFIELDS, $params);
		// Palauta vastaus, mutta ilman headereja.
		curl_setopt($session, CURLOPT_HEADER, false);
		// Käytetään TLS, ei SSL3.
		curl_setopt($session, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1_2);
		curl_setopt($session, CURLOPT_RETURNTRANSFER, true);

		// Lähetetään sähköposti.
		curl_exec($session);
		curl_close($session);
	}

	/**
	 * //TODO: PHPdoc
	 * @param string $email
	 * @param string $key
	 */
	static function lahetaSalasanaLinkki( /*String*/ $email, /*String*/ $key ) {
		Email::$target_email = $email;
		Email::$subject = "Osax.fi - Salasanan resetointi";
		Email::$message = "<p>Salasanan vaihto onnistuu osoitteessa:</p>
			<a href='http://osax.fi/pw_reset.php?id={$key}'>Linkki salasanan resetointi-sivulle</a>
			<p>Jos ylläoleva linkki ei toimi, kopioi seuraava rivi selaimen otsikkokenttään:<br>
			http://osax.fi/pw_reset.php?id={$key}</p>
			<p>Linkki on voimassa vain yhden tunnin ajan, jonka jälkeen sinun pitää pyytää uusi linkki.</p>";

		Email::sendMail();
	}

	/**
	 * //TODO: PHPdoc
	 * @param string $email
	 * @param Ostoskori $cart
	 * @param int $tilausnro
	 * @param string $fileName
	 */
	static function lahetaTilausvahvistus( /*string*/$email, Ostoskori $cart, /*int*/$tilausnro, /*string*/$fileName ) {
		// Tarkistetaan varmuuden vuoksi, että ostoskorissa on tuotteita.
		//TODO: Eikö tuo tarkistus ole hieman liioittelua? --JJ
		if ( $cart->tuotteet ) {
			Email::$subject = "Tilausvahvistus";

			$productTable = '<table><tr><th>Tuotenumero</th><th>Tuote</th><th style="text-align:right;">Hinta/kpl</th>
									<th style="text-align:right;">Kpl</th></tr>';
			foreach ($cart->tuotteet as $tuote) {
				$productTable .= "
				<tr><td>{$tuote->tuotekoodi}</td><td>{$tuote->valmistaja} {$tuote->nimi}</td>
					<td style='text-align:right;'>{$tuote->a_hinta_toString()}</td>
					<td style='text-align:right;'>{$tuote->kpl_maara}</td></tr>";
			}

			$productTable .= "</table><br><br><br>";
			$contactinfo = 'Yhteystiedot:<br>
						Osax Oy<br>
						Jukolankatu 19 80100 Joensuu<br>		
						puh. 010 5485200<br>
						janne@osax.fi';
			Email::$message = "Tilaaja: {$email}<br>Tilausnumero:{$tilausnro}<br>Summa: {$cart->summa_toString()}<br>
			Tilatut tuotteet:<br>{$productTable} {$contactinfo}";

			Email::$fileName = $fileName;
			Email::$file = file_get_contents("./laskut/{$fileName}");

			Email::sendMail();
		}
	}

	/**
	 * //TODO: PHPdoc
	 * @param $tilausnro
	 * @param $fileName
	 */
	static function lahetaNoutolista( /*int*/$tilausnro, /*String*/ $fileName ) {
		Email::$target_email = Email::admin_email;
		Email::$subject = "Noutolista tilaukseen {$tilausnro}";
		Email::$message = "<p>Tilauksen {$tilausnro} nouotlista.</p>
				<p>Liitteenä PDF-tiedosto.</p>
				<p>Käy merkkaamassa tilaus hoidetuksi, kun tilaus on lähetetty!</p>";

		Email::$fileName = $fileName;
		Email::$file = file_get_contents("./noutolistat/{$fileName}");

		Email::sendMail();
	}

	/**
	 * //TODO: PHPdoc
	 * @param $email
	 * @param $tilausnro
	 */
	static function lahetaIlmoitus_TilausLahetetty( /*String*/$email, /*int*/$tilausnro ) {
		Email::$target_email = $email;
		Email::$subject = "Tilaus {$tilausnro}";
		Email::$message = "Hei! Tilauksesi {$tilausnro} on nyt lähetetty.";
		//TODO: Lisää linkki tilausinfoon? --JJ

		Email::sendMail();
	}

	/**
	 * //TODO: PHPdoc
	 * @param stdClass $user
	 * @param string $vanha_sijainti
	 * @param string $uusi_sijainti
	 */
	static function lahetaIlmoitus_EpailyttavaIP( stdClass $user, /*string*/$vanha_sijainti, /*string*/$uusi_sijainti ) {
		Email::$target_email = Email::admin_email;
		Email::$subject = "Epäilyttävää käytöstä";
		Email::$message = "<p>Asiakas ...tiedot tähän..... </p>>
			<p>Vanha sijainti: {$vanha_sijainti}</p>>
			<p>Uusi sijainti: {$uusi_sijainti}</p>";

		Email::sendMail();
	}
}
