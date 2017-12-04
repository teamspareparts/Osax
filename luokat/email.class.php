<?php declare(strict_types=1);
/**
 * Staattinen luokka sähköpostin käyttöön.
 */
class Email {
	private static $request_url = 'https://api.sendgrid.com/api/mail.send.json';
	private static $config_path = './config/config.ini.php';

	private static $target_email = NULL;
	private static $subject = NULL;
	private static $message = NULL;

	private static $file = NULL;
	private static $file_name = NULL;

	/**
	 * Lähettää sähköpostin. Luokan sisäiseen käyttöön, muut metodit asettavat arvot,
	 * ja sitten kutsuvat tämän metodin.
	 * @param array $config
	 * @return stdClass
	 */
	private static function sendMail( array $config = [] ) : stdClass {
		if ( empty($config) ) {
			$config = parse_ini_file( Email::$config_path );
		}
		$postfields = array(
			'api_user' => $config['email_user'],
			'api_key' => $config['email_pass'],
			'to' => Email::$target_email,
			'subject' => Email::$subject,
			'html' => Email::$message,
			'text' => "",
			'from' => $config['delivery_email'],
			'files[' . Email::$file_name . ']' => Email::$file
		);

		// Luodaan cURL pyyntö.
		$session = curl_init( Email::$request_url );
		// Käytetään HTTP POSTia.
		curl_setopt( $session, CURLOPT_POST, 1 );
		curl_setopt( $session, CURLOPT_POSTFIELDS, $postfields );
		curl_setopt( $session, CURLOPT_RETURNTRANSFER, 1 );
		// Ei headereita
		curl_setopt( $session, CURLOPT_HEADER, 0 );
		// Salaus
		curl_setopt( $session, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1_2 );

		// Lähetetään sähköposti.
		$response = curl_exec( $session );
		curl_close( $session );

		return json_decode($response);
	}

	/**
	 * Lähettää salasanan palautus-linkin annettuun sähköpostiin.
	 * @param string $email <p> Osoite, johon linkki lähetetään.
	 * @param string $key <p> Salasanan palautus-avain. Avaimen luonti login_check.php-tiedostossa.
	 */
	static function lahetaSalasanaLinkki( string $email, string $key ) {
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
	 * @param Laskutiedot $lasku <p> Tilauksen tiedot (tuotteet, tilaaja, tilausnro)
	 * @param string $file_name <p> Laskun nimi (tiedostonimi siis)
	 */
	static function lahetaTilausvahvistus( string $email, Laskutiedot $lasku, string $file_name ) {
		Email::$target_email = $email;
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

		Email::$file_name = $file_name;
		Email::$file = file_get_contents( $file_name );

		Email::sendMail();
	}

	/**
	 * Lähettää noutolistan ylläpidolle myynti@osax.fi osoitteeseen.
	 * @param int $tilausnro <p> Tilauksen numero
	 * @param string $file_name <p> Noutolistan tiedoston nimi
	 */
	static function lahetaNoutolista( int $tilausnro, string $file_name ) {
		$config = parse_ini_file( Email::$config_path );
		Email::$target_email = $config['admin_email'];
		Email::$subject = "Noutolista tilaukseen {$tilausnro}";
		Email::$message = "<p>Tilauksen {$tilausnro} noutolista.</p>
				<p>Liitteenä PDF-tiedosto.</p>
				<p>Käy merkkaamassa tilaus hoidetuksi, kun tilaus on lähetetty!</p>";

		Email::$file_name = $file_name;
		Email::$file = file_get_contents( $file_name );

		Email::sendMail( $config );
	}

	/**
	 * Lähettää eilisen päivän tapahtumalistauksen kirjanpitoon.
	 * @param string $file_name <p>
	 * @param $file <p> Tiedoston sisältö
	 */
	static function lahetaTapahtumalistausraportti( string $file_name, /*file*/ $file ) {
		$config = parse_ini_file( Email::$config_path );
		$date = date('d.m.Y');
		Email::$target_email = $config['kirjanpito_email'];
		Email::$subject = "Osax Oy tapahtumalistaus {$date}";
		Email::$message = "<p>Hei,</p>
				<p>Ohessa Osax Oy:n tilaukset eiliseltä.</p>
				<p>Yhteystiedot:<br>
					Osax Oy<br>
					Jukolankatu 19, 80100 Joensuu<br>		
					puh. 010 5485200<br>
					janne@osax.fi;</p>";

		Email::$file_name = $file_name;
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
	static function lahetaIlmoitus_TilausLahetetty( string $email, int $tilausnro ) {
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
	static function lahetaIlmoitus_EpailyttavaIP( stdClass $user, string $vanha_sijainti, string $uusi_sijainti ) {
		$config = parse_ini_file( Email::$config_path );
		Email::$target_email = $config['admin_email'];
		Email::$subject = "Epäilyttävää käytöstä";
		Email::$message = "<p>Asiakas ...tiedot tähän..... </p>>
			<p>Vanha sijainti: {$vanha_sijainti}</p>>
			<p>Uusi sijainti: {$uusi_sijainti}</p>";

		Email::sendMail( $config );
	}

	/**
	 * Jos config-path on jokin muu kuin default ('./config/config.ini.php').
	 *
	 * @param string $path
	 */
	static function muutaConfigPath( string $path ) {
		Email::$config_path = $path;
	}
}
