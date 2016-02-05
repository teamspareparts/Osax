<!DOCTYPE html>
<html>
<body>
<h1>LOADING...</h1>
<?php

	$servername = "localhost";
	$username = "root";
	$password = "password";
	$dbname = "myDB";

	// Create connection
	$connection = new mysqli( $servername, $username, $password, $dbname );
	// Check connection
	if ( $connection->connect_error ) {
		die( "Connection failed: " . $connection->connect_error );
	} else { echo "Connection good.<br><br>"; }
	
	$mode = $_POST["mode"];

	//Sisääkirjautuminen
	if ( $mode == "login" ) {
		$email = trim(strip_tags( $_POST["email"] ));
		$password = trim(strip_tags( $_POST["password"] ));
		$password_hashed = password_hash($password, PASSWORD_DEFAULT);
		
		$sql = "
			SELECT 	id, sahkoposti, salasana
			FROM 	kayttajat
			WHERE 	sahkoposti == $email
					salasana == $password_hashed";
		
		//Pitäisikö se hakea kokonaan, ja tarkistaa vasta tässä,
		// vai kuten se on tehty ylhäällä (tarkistetaan hakuvaiheessa)?
		// Doesn't really matter at this stage.
		$result = $connection->query($sql);

		if ( $result->num_rows > 0 ) {
			while($row = $result->fetch_assoc()) {
				//Testausta varten. I like printing everything.
				echo "id: " . $row["id"]. " - Email: " . $row["sahkoposti"]. "<br>";
				//Tässä ei tarvitse muuta kuin lähettää eteenpäin seuraavalle sivulle.
				//Testaisin tätä mielelläni, mutta XAMPP ei toimi. Oletan, että se toimii.
			}
		}
		else {
		?>
		<!-- Tähän HTML-koodia, kertomaan, että sinulla on väärä kirjautuminen -->
		<br><h1>Sinulla on väärä salasana/sähköposti. Palaa takaisin lähtöruutuun.</h1>
		<?php	
		}
	}

	//Salasanan resetointi
	elseif ( $mode == "password_reset" ) {
		$email = trim(strip_tags( $_POST["email"] ));
		$sql = "
			SELECT id, sahkoposti
			FROM kayttajat
			WHERE salasana == $password_hashed";
		?>
		<!-- Tähän tulee luultavasti kasa HTML-koodia.
		"Salasanan palautuslinkki lähetetty sähköpostiin" 
		Tai jotain sinne päin.
		Tässä ei ole tarkoitus kertoa käyttäjälle onko sähköposti käytössä.
		Tietoturvallisuus risksi. Liikaa tietoa ei ole hyvä asia, kuulemma. -->
		<?php
	}
	
	//Onko tämä tarpeen? Meneekö se eteenpäin? Who knows! ¯\_(ツ)_/¯
	$connection -> close();
?>
</html>
</body>