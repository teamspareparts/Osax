<!DOCTYPE html>
<html>
<body>
<h1>LOADING...</h1>
<?php

	$servername = "localhost";
	$username	= "root";
	$password	= "password";
	$dbname		= "myDB";

	// Create connection
	$connection = new mysqli( $servername, $username, $password, $dbname );
	// Check connection
	if ( $connection->connect_error ) {
		die( "Connection failed: " . $connection->connect_error );
	} else { echo "Connection good.<br><br>"; }
	
	$mode = $_POST["mode"];

	//Sisääkirjautuminen
	if ( $mode == "login" ) {
		$email 			= trim(strip_tags( $_POST["email"] ));
		$password 		= trim(strip_tags( $_POST["password"] ));
		$password_hashed= password_hash($password, PASSWORD_DEFAULT);
		
		$sql = "
			SELECT 	id, sahkoposti, salasana
			FROM 	kayttajat
			WHERE 	sahkoposti == $email
					salasana == $password_hashed";
		
		//Pitäisikö se hakea kokonaan, ja tarkistaa vasta tässä,
		// vai kuten se on tehty ylhäällä (tarkistetaan hakuvaiheessa)?
		// Doesn't really matter at this stage.
		$result = $connection->query( $sql );

		if ( $result->num_rows > 0 ) {
			//Tässä ei tarvitse muuta kuin lähettää eteenpäin seuraavalle sivulle.
			//Testaisin tätä mielelläni, mutta XAMPP ei toimi. Oletan, että se toimii.
		}
		else {
		?>
		<br>
		<div id="content">
			<fieldset>
				<legend>Väärät kirjautumistiedot</legend>
				<p>Salasana tai sähköposti on väärä. Varmista, että kirjoitit tiedot oikein.</p>
				<FORM METHOD="LINK" ACTION="default.html"><!-- Tämä on väärin -->
					<INPUT TYPE="submit" VALUE="Palaa takaisin kirjautumissivulle">
				</FORM>
			</fieldset>
		</div>
		<?php	
		}
	}

	//Salasanan resetointi
	elseif ( $mode == "password_reset" ) {
		$email = trim(strip_tags( $_POST["email"] ));
		$sql = "
			SELECT id, sahkoposti
			FROM kayttajat
			WHERE sahkoposti == $email";
		$result = $connection->query( $sql );

		if ( $result->num_rows > 0 ) {
			
			/*
			PHP:ssä on valmis mail()-funktio tätä varten. Jotta se toimisi 
			se pitää konfiguroida PHP:n serveri puolen asetuksissa. 
			Koska meillä ei ole sellaista (vielä), olen ottanut tämän pois käytöstä.
			Lisäksi meiltä puuttuu palautuslinkki, joka annetaan sähköpostissa.
			Sekin pitäisi vielä miettiä.
			*/
			
			/* //Tästä alkaa sähköpostin kirjoitus
			$kohde		= $email;
			$otsikko 	= 'the subject';
			$viesti 	= 'hello\nNew line';
			$viesti 	= wordwrap($msg,70);
			$headers 	= 'From: webmaster@example.com' . "\r\n" .
							'Reply-To: webmaster@example.com' . "\r\n" .
							'X-Mailer: PHP/' . phpversion();
			mail( $kohde, $otsikko, $viesti, $headers );
			*/ //End email writing/sending
		}
		?>
		<div id="content">
			<fieldset>
				<legend>Salasanan palautus</legend>
				<p>Salasanan palautuslinkki on lähetetty sähköpostilla osoitteeseen <?php $email ?></p>
				<FORM METHOD="LINK" ACTION="default.html"><!-- Tämä on väärin -->
					<INPUT TYPE="submit" VALUE="Palaa takaisin kirjautumissivulle">
				</FORM>
			</fieldset>
		</div>
		<?php
	}
	
	//Onko tämä tarpeen? Meneekö se eteenpäin? Who knows! ¯\_(ツ)_/¯
	$connection -> close();
?>
</html>
</body>