<!DOCTYPE html>
<html>
<body>
<h1>LOADING... Redirecting</h1>
<?php
require 'tietokanta.php';
	session_start();	// Aloitetaan sessio kayttajan tietoja varten
	
	// Luodaan yhteys
	// TODO: try-catch ( or die() -tapaa ei pitäisi käyttää )
	$connection = mysqli_connect( DB_HOST, DB_USERNAME, DB_PASSWORD, DB_NAME ) 
					or die("Connection error:" . mysqli_connect_error());
	
	// Haetaan moodi edelliseltä sivulta
	$mode = $_POST["mode"];

	//Mode --> Sisääkirjautuminen
	if ( $mode == "login" ) {
		$email 			= trim(strip_tags( $_POST["email"] ));			// Sähköposti
		$password 		= trim(strip_tags( $_POST["password"] ));		// Salasana
		
		// SQL-kysely
		$sql_query = "
			SELECT 	*
			FROM 	kayttaja
			WHERE 	sahkoposti = '$email'";
		
		$result = mysqli_query($connection, $sql_query) or die(mysqli_error($connection));	// Kyselyn tulos
		$row_count = mysqli_num_rows($result);				// Kyselyn  tuloksen rivien määrä
	
		if ( $row_count > 0 ) {
			$row = mysqli_fetch_assoc($result);
			if(password_verify($password, $row['salasana_hajautus'])) {
			//TODO: Siirrä tiedot session_dataan, ja lähetä eteenpäin
		   		$_SESSION['email']	= $row['sahkoposti'];
		   		$_SESSION['admin']	= $row['yllapitaja'];
		   		header("Location:tuotehaku.php");
		   		exit;
			}
			else {	//väärä salasana
				header("Location:login.php?redir=3");
				exit;
			}
		   
		} else { //Ei tuloksia == väärä käyttäjätunnus --> lähetä takaisin
			header("Location:login.php?redir=1");
			exit;
		}
	}

	//Mode --> Salasanan resetointi
	elseif ( $mode == "password_reset" ) {
		$email = trim(strip_tags( $_POST["email"] ));
		$sql = "
			SELECT	id, sahkoposti
			FROM	kayttajat
			WHERE	sahkoposti = '$email'";
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
		// Lähetä takaisin kirjautumissivulle, jossa tulostetaan ilmoitus
		header("Refresh: 2;url=http://localhost/login.php?redir=2");
	}
	
	//Onko tämä tarpeen? Meneekö se eteenpäin? Who knows! ¯\_(ツ)_/¯
	$connection -> close();
?>
</html>
</body>