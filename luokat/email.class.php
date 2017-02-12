﻿<?php

/**
 * Staattinen luokka sähköpostin käyttöön.
 *
 * @version 2017-02-06 <p> versionumero lisätty, ja metodien PhpDoc lisätty.
 */
class Email {
	//TODO: Move to .ini file? --JJ
	//TODO: Samalla tavalla kuin db-config.ini.php. Ei välttämätöntä. --SL
	private static $request_url = 'https://api.sendgrid.com/api/mail.send.json';
	//private static $user = ""; //TODO: Mutta eikö nämä tiedot pitäisi olla piilotettu käyttäjältä? --JJ/17-02-06
	//private static $pass = "";
	private static $ini_path = "./tietokanta/db-config.ini.php";

	const delivery_email = 'noreply@osax.fi';
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
	private function __construct() {
	}

	/**
	 * Lähettää sähköpostin. Luokan sisäiseen käyttöön, muut metodit asettavat arvot,
	 * ja sitten kutsuvat tämän metodin.
	 */
	private static function sendMail() {
		$values = parse_ini_file( Email::$ini_path );
		//sähköpostin parametrit
		$params = array(
			'api_user' => $values['email_user'],
			'api_key' => $values['email_pass'],
			'to' => Email::$target_email,
			'subject' => Email::$subject, //otsikko
			'html' => Email::$message, //HTML runko
			'text' => "",
			'from' => Email::delivery_email, //lähetysosoite
			'files[' . Email::$fileName . ']' => Email::$file //liitetiedosto
		);

		// Luodaan cURL pyyntö.
		$session = curl_init( Email::$request_url );
		// Käytetään HTTP POSTia.
		curl_setopt( $session, CURLOPT_POST, true );
		// Lisätään viestin runko, otsikko, jne.
		curl_setopt( $session, CURLOPT_POSTFIELDS, $params );
		// Palauta vastaus, mutta ilman headereja.
		curl_setopt( $session, CURLOPT_HEADER, false );
		// Käytetään TLS, ei SSL3.
		curl_setopt( $session, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1_2 );
		curl_setopt( $session, CURLOPT_RETURNTRANSFER, true );

		// Lähetetään sähköposti.
		curl_exec( $session );
		curl_close( $session );
	}

	/**
	 * Lähettää salasanan palautus-linkin annettuun sähköpostiin.
	 * @param string $email <p> Osoite, johon linkki lähetetään.
	 * @param string $key <p> Salasanan palautus-avain. Avaimen luonti login_check.php-tiedostossa.
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
	 * Lähettää tilausvahvistuksen käyttäjälle; tuotetiedot ja lasku mukaan lukien.
	 * @param string $email <p> Osoite, johon viesti lähetetään.
	 * @param Ostoskori $cart <p> Tuotetietoja varten
	 * @param int $tilausnro <p> Tilauksen numero
	 * @param string $fileName <p> Laskun nimi (tiedostonimi siis)
	 */
	static function lahetaTilausvahvistus(
			/*String*/ $email, Ostoskori $cart, /*int*/ $tilausnro, /*string*/ $fileName ) {
		Email::$target_email = $email;
		Email::$subject = "Tilausvahvistus";

		$productTable = '<table><tr><th>Tuotenumero</th><th>Tuote</th><th style="text-align:right;">Hinta/kpl</th>
								<th style="text-align:right;">Kpl</th></tr>';
		foreach ( $cart->tuotteet as $tuote ) {
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
		Email::$file = file_get_contents( "./laskut/{$fileName}" );

		Email::sendMail();
	}

	/**
	 * Lähettää noutolistan ylläpidolle myynti@osax.fi osoitteeseen.
	 * @param int $tilausnro <p> Tilauksen numero
	 * @param string $fileName <p> Noutolistan tiedoston nimi
	 */
	static function lahetaNoutolista( /*int*/ $tilausnro, /*String*/ $fileName ) {
		Email::$target_email = Email::admin_email;
		Email::$subject = "Noutolista tilaukseen {$tilausnro}";
		Email::$message = "<p>Tilauksen {$tilausnro} nouotlista.</p>
				<p>Liitteenä PDF-tiedosto.</p>
				<p>Käy merkkaamassa tilaus hoidetuksi, kun tilaus on lähetetty!</p>";

		Email::$fileName = $fileName;
		Email::$file = file_get_contents( "./noutolistat/{$fileName}" );

		Email::sendMail();
	}

	/**
	 * Lähettää ilmoituksen käyttäjälle annettuun osoitteeseen.
	 * //TODO: Lisää linkki tilausinfoon? --JJ/2016
	 * //TODO: Vaatii systeemin sisäänkirjautumiselle ja uudellenohjaukselle. --JJ/2017-02-06
	 * @param string $email <p> Target email
	 * @param int $tilausnro <p> Tilauksen numero
	 */
	static function lahetaIlmoitus_TilausLahetetty( /*String*/ $email, /*int*/ $tilausnro ) {
		Email::$target_email = $email;
		Email::$subject = "Tilaus {$tilausnro}";
		Email::$message = "<p>Hei! Tilauksesi {$tilausnro} on nyt lähetetty.</p>";

		Email::sendMail();
	}

	/**
	 * Lähettää ilmoituksen ylläpidolle myynti@osax.fi osoitteeseen.
	 * //TODO: WIP Käyttäjän tiedot puuttuu. --JJ 2017-02-06
	 * @param stdClass $user <p> Epäilyttävän käyttäjän tiedot.
	 * @param string $vanha_sijainti
	 * @param string $uusi_sijainti
	 */
	static function lahetaIlmoitus_EpailyttavaIP( stdClass $user, /*string*/ $vanha_sijainti, /*string*/ $uusi_sijainti ) {
		Email::$target_email = Email::admin_email;
		Email::$subject = "Epäilyttävää käytöstä";
		Email::$message = "<p>Asiakas ...tiedot tähän..... </p>>
			<p>Vanha sijainti: {$vanha_sijainti}</p>>
			<p>Uusi sijainti: {$uusi_sijainti}</p>";

		Email::sendMail();
	}
}
