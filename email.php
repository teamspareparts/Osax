<?php

/**
 * curl.cainfo osoitettava certifikaatteihin php.ini tiedostossa.
 * Esim cacert.pem.txt tiedoston saa osoitteesta https://curl.haxx.se/docs/caextract.html
 * (Viittaus esim: curl.cainfo = "C:\__polku__tähän__\cacert.pem.txt")
 */


function send_email($email, $subject, $message){
	// SendGrid:n tunnarit
	// ei yleiseen jakeluun kiitos
	$url = 'https://api.sendgrid.com/';
	$user = 'azure_4477d243090deff5da12ff391b078d6f@azure.com';
	$pass = 'KettujenKevat123';

	
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


	
	// debuggaukseen
	if (curl_errno($session)) {
		print "Error: " . curl_error($session);
	} else {
		// Show me the result
		var_dump($response);
		curl_close($session);
	}
	print_r($response);
}


function laheta_salasana_linkki($email, $key){
	$subject = "Slasanan vaihtaminen";
	$message = 'Salasanan vaihto onnistuu osoitteessa: http://localhost/Tuoteluettelo/pw_reset.php?id=' . $key;
	send_email($email, $subject, $message);
}

function laheta_tilausvahvistus(){
	$subject = "Tilausvahvistus";
	$message = '';
	//send_email($email, $subject, $message);
}
?>