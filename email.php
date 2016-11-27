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
 * @param array $products
 * @param string $tilausnro
 * @param string $fileName
 * @return bool
 */
function laheta_tilausvahvistus( /*string*/ $email, array $products, /*string*/ $tilausnro, /*string*/ $fileName ) {
	if ( $products ) {
		$subject = "Tilausvahvistus";
		$summa = 0.00;
		$productTable = '<table><tr><th>Tuotenumero</th><th>Tuote</th><th style="text-align:right;">Hinta/kpl</th>
									<th style="text-align:right;">Kpl</th></tr>';
		foreach ($products as $product) {
			$productTable .= "
				<tr><td>{$product->tuotekoodi}</td><td>{$product->valmistaja} {$product->nimi}</td>
					<td style='text-align:right;'>" . format_euros($product->hinta) . "</td>
					<td style='text-align:right;'>{$product->cartCount}</td></tr>";
			$summa += $product->hinta * $product->cartCount;
		}
		$productTable .= "</table><br><br><br>";
		$contactinfo = 'Yhteystiedot:<br>
						Osax Oy<br>
						Jukolankatu 19 80100 Joensuu<br>		
						puh. 010 5485200<br>
						janne@osax.fi';
		$message = "Tilaaja: {$email}<br>Tilausnumero:{$tilausnro}<br>Summa: " . format_euros($summa) . "<br>
			Tilatut tuotteet:<br>{$productTable} {$contactinfo}";

		//$file = fopen("./laskut/{$fileName}", 'r');
		$file = file_get_contents("./laskut/{$fileName}");
		send_email( $email, $subject, $message, $file, $fileName );

		return true;
	} else return false;
}

/**
 * Lähettää tilausvahvistuksen ylläpidolle
 * @param User $asiakas <p> Tilauksen tehnyt asiakas
 * @param stdClass[] $products
 * @param int $tilausnro
 * @return bool
 */
function laheta_tilaus_yllapitajalle( User $asiakas, /*array*/ $products, /*int*/ $tilausnro){
	if ( !empty($products) && !empty($tilausnro) && $asiakas->isValid() ) {
		$admin_email = 'myynti@osax.fi';

		$subject = "Tilaus {$tilausnro}";
		$summa = 0.00;
		$productTable = '
			<table><tr><th>Tuotenumero</th><th>Tuote</th><th style="text-align:right;">Hinta/kpl</th>
				<th style="text-align:right;">Kpl</th></tr>';

		foreach ($products as $product) {
			$summa += $product->hinta * $product->cartCount;
			$productTable .= "
				<tr><td>{$product->tuotekoodi}</td><td>{$product->valmistaja} {$product->nimi}</td>
					<td style='text-align:right;'>".format_euros($product->hinta)."</td>
					<td style='text-align:right;'>{$product->cartCount}</td></tr>";
		}

		$productTable .= "</table><br><br><br>";
		$message = "Tilaaja: {$asiakas->kokoNimi()}<br>
				Yritys: {$asiakas->yrityksen_nimi}<br>
				S-posti: {$asiakas->sahkoposti}<br>
				Puh: {$asiakas->puhelin}<br><br>
				Tilausnumero: {$tilausnro}<br>
				Summa: ".format_euros($summa)."<br>
				Tilatut tuotteet:<br>{$productTable}";

		require 'noutolista_pdf_luonti.php';
		send_email( $admin_email, $subject, $message );
		return true;
	} else
		return false;

}

/**
 * Lähettää ilmoituksen epäilyttävästä IP-osoitteesta ylläpidolle.
 * @param DByhteys $db
 * @param $email
 * @param $vanha_sijainti
 * @param $uusi_sijainti
 * @return bool
 */
function laheta_ilmoitus_epailyttava_IP( DByhteys $db, $email, $vanha_sijainti, $uusi_sijainti){
	//haetaan yllapitajan sposti
	$query = "SELECT sahkoposti FROM kayttaja WHERE yllapitaja=1";
	$yp = $db->query( $query, NULL, NULL, PDO::FETCH_OBJ );
	if (!$yp) return false;
	
	//emailin sisältö
	$subject = "Epäilyttävää käytöstä";
	$message = "Asiakas ...tiedot tähän..... <br>" .
				"Vanha sijainti:" . $vanha_sijainti . "<br>" .
				"Uusi sijainti:" . $uusi_sijainti;
	
	send_email($yp->sahkoposti, $subject, $message);
    return true;
}
