﻿<?php

/**
 * curl.cainfo osoitettava certifikaatteihin php.ini tiedostossa.
 * Esim cacert.pem.txt tiedoston saa osoitteesta https://curl.haxx.se/docs/caextract.html
 * (Viittaus esim: curl.cainfo = "C:\__polku__tähän__\cacert.pem.txt")
 */


function send_email($email, $subject, $message){

	$url = 'https://api.sendgrid.com/';

	
	//sähköpostin parametrit
	$params = array(
			'api_user'  => "$user",
			'api_key'   => "$pass",
			'to'        => "$email",
			'subject'   => "$subject", //otsikko
			'html'      => "<html><head></head><body>
			$message </body></html>", //HTML runko
			'text'      => "",
			'from'      => "noreply@tuoteluettelo.com", //lähetysosoite

	);
	$request =  $url.'api/mail.send.json';

	// Luodaan curl pyyntö
	$session = curl_init();
	curl_setopt ($session, CURLOPT_URL, $request);
	// Käytetään HTTP POSTia
	curl_setopt ($session, CURLOPT_POST, true);
	// Runko
	curl_setopt ($session, CURLOPT_POSTFIELDS, $params);
	// Palauta vastaus, ei headereja
	curl_setopt($session, CURLOPT_RETURNTRANSFER, 1);
	// Lähetetään sposti
	$response = curl_exec($session);


	
	/* // debuggaukseen
	if (curl_errno($session)) {
		print "Error: " . curl_error($session);
	} else {
		// Show me the result
		var_dump($response);
		curl_close($session);
	}
	print_r($response); */
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
 * @param $email
 * @param $products
 * @param $tilausnro
 * @return bool
 */
function laheta_tilausvahvistus( /*string*/$email, array $products, /*string*/ $tilausnro ) {
	$subject = "Tilausvahvistus";
	$summa = 0.00;
	$productTable = '<table><tr><th>Tuotenumero</th><th>Tuote</th><th style="text-align: right;">Hinta/kpl</th><th style="text-align: right;">Kpl</th></tr>';
	if (empty($products)) return false;
	foreach ($products as $product) {
		$article = $product->directArticle;
		$productTable .= "<tr><td>$article->articleNo</td><td>$article->brandName $article->articleName</td><td style='text-align: right;'>" . format_euros($product->hinta) . "</td><td style='text-align: right;'>$product->cartCount</td></tr>";
		$summa += $product->hinta * $product->cartCount;
	}
	$productTable .= "</table><br><br><br>";
	$contactinfo = 'Yhteystiedot:<br>
					Rantakylän Varaosa Oy<br>
					Jukolankatu 19 80100 Joensuu<br>		
					puh. 010 5485200<br>
					toimisto@rantakylanvaraosa.fi';
	$message = 'Tilaaja: ' . $email . '<br>Tilausnumero: ' . $tilausnro. '<br>Summa: ' . format_euros($summa) . '<br> Tilatut tuotteet:<br>' . $productTable . $contactinfo;
	send_email($email, $subject, $message);

	return true;
}

/**
 * Lähettää tilausvahvistuksen ylläpidolle
 * @param DByhteys $db
 * @param $email
 * @param $products
 * @param $tilausnro
 * @return bool
 */
function laheta_tilaus_yllapitajalle( DByhteys $db, $email, $products, $tilausnro){
	//haetaan ylläpitäjän sposti ja asiakkaan tiedot
	//(tai voidaan määritellä erikseen sähköposti, johon tilaukset lähetetään)
	
	//yllapitajan sposti
	$query = "SELECT sahkoposti FROM kayttaja WHERE yllapitaja=1";
	$yp = $db->query( $query, NULL, NULL, PDO::FETCH_OBJ );
	if (!$yp) return false;

	//haetaan käyttäjän tiedot
	$query = "SELECT etunimi, sukunimi, yritys, puhelin FROM kayttaja WHERE sahkoposti=?";
	$asiakas = $db->query( $query, [$email], NULL, PDO::FETCH_OBJ );
	if (!$asiakas) return false;

	$enimi = $asiakas->etunimi;
	$snimi = $asiakas->sukunimi;
	$yritys = $asiakas->yritys;
	$puhelin = $asiakas->puhelin;
	
	
	
	$subject = 'Tilaus';
	$summa = 0.00;
	$productTable = '<table><tr><th>Tuotenumero</th><th>Tuote</th><th style="text-align: right;">Hinta/kpl</th><th style="text-align: right;">Kpl</th></tr>';
	if (empty($products)) return false;
	foreach ($products as $product) {
		$article = $product->directArticle;
		$productTable .= "<tr><td>$article->articleNo</td><td>$article->brandName $article->articleName</td><td style='text-align: right;'>" . format_euros($product->hinta) . "</td><td style='text-align: right;'>$product->cartCount</td></tr>";
		$summa += $product->hinta * $product->cartCount;
	}
	$productTable .= "</table><br><br><br>";
	$message = 'Tilaaja: ' . $enimi . " " . $snimi . '<br>
				Yritys: ' . $yritys . '<br>
				S-posti: ' . $email .  '<br>
				Puh: ' . $puhelin . '<br><br>
				Tilausnumero: ' . $tilausnro. '<br>
				Summa: ' . format_euros($summa) . '<br>
				Tilatut tuotteet:<br>
				'. $productTable;
	
	
	send_email($yp->sahkoposti, $subject, $message);
	return true;
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
}





?>
