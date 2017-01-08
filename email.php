<?php
/**
 * curl.cainfo osoitettava certifikaatteihin php.ini tiedostossa.
 * Esim cacert.pem.txt tiedoston saa osoitteesta https://curl.haxx.se/docs/caextract.html
 * (Viittaus esim: curl.cainfo = "C:\__polku__tähän__\cacert.pem.txt")
 */

/**
 * Sähköpostin lähetykseen käytettävä functio, jolla voi lähettää myös liitetiedoston.
 * @param string $email <p> Vastaanottajan sähköpostiosoite
 * @param string $subject <p> Otsikko
 * @param string $message <p> Viestin sisältö html-muodossa
 * @param $file [optional]<p> Liitetiedosto sisältöineen
 * @param string $fileName [optional] <p> Liitettävän tiedoston nimi
 */
function send_email( /*string*/ $email, /*string*/ $subject, /*string*/ $message,
								$file = NULL, /*string*/ $fileName = NULL ){
	$url = 'https://api.sendgrid.com/';
	$user = "";
	$pass = "";
    $file = isset($file) ? $file : '';
	
	//sähköpostin parametrit
	$params = array(
		'api_user'  => $user,
		'api_key'   => $pass,
		'to'        => "$email",
		'subject'   => "$subject", //otsikko
		'html'      => "$message", //HTML runko
		'text'      => "",
		'from'      => "noreply@osax.com", //lähetysosoite
		'files['.$fileName.']' => $file //liitetiedosto
	);

	$request =  $url.'api/mail.send.json';

	// Luodaan curl pyyntö
	$session = curl_init($request);
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

/**
 * Lähettää käyttäjälle linkin sivulle, jossa salasanan vaihto tapahtuu.
 * @param $email
 * @param $key <p> Palautussivun GUID (tai avain)
 */
function laheta_salasana_linkki($email, $key){
	$subject = "Slasanan vaihtaminen";
	$message = "Salasanan vaihto onnistuu osoitteessa: http://osax.fi/pw_reset.php?id={$key}";
	send_email($email, $subject, $message);
}

/**
 * Lähettää tilausvahvistuksen sähköpostiin
 * @param string $email
 * @param Ostoskori $cart
 * @param int $tilausnro
 * @param string $fileName
 * @return bool
 */
function laheta_tilausvahvistus( /*string*/ $email, Ostoskori $cart, /*int*/ $tilausnro, /*string*/ $fileName ) {
	$products = $cart->tuotteet;
	if ( $products ) {
		$subject = "Tilausvahvistus";
		$productTable = '<table><tr><th>Tuotenumero</th><th>Tuote</th><th style="text-align:right;">Hinta/kpl</th>
									<th style="text-align:right;">Kpl</th></tr>';
		foreach ($products as $tuote) {
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
		$message = "Tilaaja: {$email}<br>Tilausnumero:{$tilausnro}<br>Summa: {$cart->summa_toString()}<br>
			Tilatut tuotteet:<br>{$productTable} {$contactinfo}";

		$file = file_get_contents("./laskut/{$fileName}");
		send_email( $email, $subject, $message, $file, $fileName );

		return true;

	} else {
		return false; }
}

/**
 * @param $tilausnro int Tilauksen numero
 * @param $fileName String Noutolistan tiedostonimi
 * @return bool
 */
function laheta_noutolista( /*int*/ $tilausnro, /*String*/ $fileName){
	$admin_email = 'myynti@osax.fi';
	$subject = "Noutolista tilaukseen {$tilausnro}";
	$message = "Tilauksen {$tilausnro} nouotlista.<br>
				Käy merkkaamassa tilaus hoidetuksi, kun tilaus on lähetetty!";
	$file = file_get_contents("./noutolistat/{$fileName}");

	send_email( $admin_email, $subject, $message, $file, $fileName );
	return true;
}

/**s
 * @param $email
 * @param $tilausnro
 * @return bool
 */
function laheta_ilmoitus_tilaus_lahetetty( /*String*/ $email, /*int*/ $tilausnro ){
	$subject = "Tilaus {$tilausnro}";
	$message = "Hei! Tilauksesi {$tilausnro} on nyt lähetetty.";

	send_email( $email, $subject, $message );
	return true;
}

/**
 * Lähettää ilmoituksen epäilyttävästä IP-osoitteesta ylläpidolle.
 * @param User $user
 * @param $vanha_sijainti
 * @param $uusi_sijainti
 * @return bool
 */
function laheta_ilmoitus_epailyttava_IP( User $user, $vanha_sijainti, $uusi_sijainti){

	$admin_email = 'myynti@osax.fi';
	
	//emailin sisältö
	$subject = "Epäilyttävää käytöstä";
	$message = "Asiakas ...tiedot tähän..... <br>" .
				"Vanha sijainti:" . $vanha_sijainti . "<br>" .
				"Uusi sijainti:" . $uusi_sijainti;
	
	send_email( $admin_email, $subject, $message );
    return true;
}
