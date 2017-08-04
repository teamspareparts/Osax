<?php
/**
 * Staattinen luokka sähköpostin käyttöön.
 */
class Email {
	private static $requestURL = 'https://api.sendgrid.com/api/mail.send.json';
	private static $configPath = './config/config.ini.php';

	private static $targetEmail = NULL;
	private static $subject = NULL;
	private static $message = NULL;

	private static $file = NULL;
	private static $fileName = NULL;

	/**
	 * Lähettää sähköpostin. Luokan sisäiseen käyttöön, muut metodit asettavat arvot,
	 * ja sitten kutsuvat tämän metodin.
	 * @param array|null $config
	 */
	private static function sendMail( array $config = null ) {
		if ( $config === null ) {
			$config = parse_ini_file( Email::$configPath );
		}
		$apiParametres = array(
			'api_user' => $config['email_user'],
			'api_key' => $config['email_pass'],
			'to' => Email::$targetEmail,
			'subject' => Email::$subject,
			'html' => Email::$message,
			'text' => "",
			'from' => $config['delivery_email'],
			'files[' . Email::$fileName . ']' => Email::$file
		);

		// Luodaan cURL pyyntö.
		$session = curl_init( Email::$requestURL );
		// Käytetään HTTP POSTia.
		curl_setopt( $session, CURLOPT_POST, true );
		// Lisätään viestin runko, otsikko, jne.
		curl_setopt( $session, CURLOPT_POSTFIELDS, $apiParametres );
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
		Email::$targetEmail = $email;
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
	 * @param Laskutiedot $lasku <p> Tilauksen tiedot (tuotteet, tilaaja, tilausnro)
	 * @param string $fileName <p> Laskun nimi (tiedostonimi siis)
	 */
	static function lahetaTilausvahvistus(
			/*String*/ $email, Laskutiedot $lasku, /*string*/ $fileName ) {
		Email::$targetEmail = $email;
		Email::$subject = "Tilausvahvistus";

		$productTable = '<table><tr><th>Tuotenumero</th><th>Tuote</th><th style="text-align:right;">Hinta/kpl</th>
								<th style="text-align:right;">Kpl</th></tr>';
		foreach ( $lasku->tuotteet as $tuote ) {
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
		Email::$message = "Tilaaja: {$lasku->asiakas->sahkoposti}<br>Tilausnumero:{$lasku->tilaus_nro}
			<br>Summa: {$lasku->float_toString($lasku->hintatiedot['summa_yhteensa'])} €<br>
			Tilatut tuotteet:<br>{$productTable} {$contactinfo}";

		Email::$fileName = $fileName;
		Email::$file = file_get_contents( $fileName );

		Email::sendMail();
	}

	/**
	 * Lähettää noutolistan ylläpidolle myynti@osax.fi osoitteeseen.
	 * @param int $tilausnro <p> Tilauksen numero
	 * @param string $fileName <p> Noutolistan tiedoston nimi
	 */
	static function lahetaNoutolista( /*int*/ $tilausnro, /*String*/ $fileName ) {
		$config = parse_ini_file( Email::$configPath );
		Email::$targetEmail = $config['admin_email'];
		Email::$subject = "Noutolista tilaukseen {$tilausnro}";
		Email::$message = "<p>Tilauksen {$tilausnro} noutolista.</p>
				<p>Liitteenä PDF-tiedosto.</p>
				<p>Käy merkkaamassa tilaus hoidetuksi, kun tilaus on lähetetty!</p>";

		Email::$fileName = $fileName;
		Email::$file = file_get_contents( $fileName );

		Email::sendMail( $config );
	}

	/**
	 * Lähettää eilisen päivän tapahtumalistauksen kirjanpitoon.
	 * @param string $fileName <p>
	 * @param $file <p> Tiedoston sisältö
	 */
	static function lahetaTapahtumalistausraportti( /*string*/$fileName, /*file*/ $file ) {
		$config = parse_ini_file( Email::$configPath );
		$date = date('d.m.Y');
		Email::$targetEmail = $config['kirjanpito_email'];
		Email::$subject = "Tapahtumalistausraportti {$date}";
		Email::$message = "<p>Hei,</p>
				<p>Ohessa Osax Oy:n vimeisimmät tilaukset.</p>
				<p>Yhteystiedot:<br>
					Osax Oy<br>
					Jukolankatu 19 80100 Joensuu<br>		
					puh. 010 5485200<br>
					janne@osax.fi';</p>";

		Email::$fileName = $fileName;
		Email::$file = $file;

		Email::sendMail( $config );
	}

	/**
	 * Lähettää ilmoituksen käyttäjälle annettuun osoitteeseen.
	 * //TODO: Lisää linkki tilausinfoon? --JJ/2016
	 * //TODO: Vaatii systeemin sisäänkirjautumiselle ja uudellenohjaukselle. --JJ/2017-02-06
	 * @param string $email <p> Target email
	 * @param int $tilausnro <p> Tilauksen numero
	 */
	static function lahetaIlmoitus_TilausLahetetty( /*String*/ $email, /*int*/ $tilausnro ) {
		Email::$targetEmail = $email;
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
		$config = parse_ini_file( Email::$configPath );
		Email::$targetEmail = $config['admin_email'];
		Email::$subject = "Epäilyttävää käytöstä";
		Email::$message = "<p>Asiakas ...tiedot tähän..... </p>>
			<p>Vanha sijainti: {$vanha_sijainti}</p>>
			<p>Uusi sijainti: {$uusi_sijainti}</p>";

		Email::sendMail( $config );
	}

	static function muutaConfigPath( /*string*/ $string ) {
		Email::$configPath = $string;
	}
}
