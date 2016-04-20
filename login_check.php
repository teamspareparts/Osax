<!DOCTYPE html>
<html>
<body>
<h1>LOADING... Redirecting</h1>
<?php
	require 'tietokanta.php';
	require 'email.php';
	session_start();	// Aloitetaan sessio kayttajan tietoja varten
	
	// Luodaan yhteys
	// TODO: try-catch ( or die() -tapaa ei pitäisi käyttää )
	$connection = mysqli_connect( DB_HOST, DB_USERNAME, DB_PASSWORD, DB_NAME ) 
					or die("Connection error:" . mysqli_connect_error());
	
	if (!isset($_POST["mode"])) {
		header("Location:login.php");
		exit();
	}
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
			if ($row["aktiivinen"] == 0){
				header("Location:login.php?redir=4");
				exit;
			}
			else if ( password_verify($password, $row['salasana_hajautus']) ) {
		   		$_SESSION['email']	= $row['sahkoposti'];
		   		$_SESSION['admin']	= $row['yllapitaja'];
		   		$_SESSION['id']		= $row['id'];
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
		$sql_query = "
			SELECT	id, sahkoposti, aktiivinen
			FROM	kayttaja
			WHERE	sahkoposti = '$email'";
		$result = mysqli_query($connection, $sql_query) or die(mysqli_error($connection));	// Kyselyn tulos
		
		if ( $result->num_rows > 0 ) {
			$row = $result->fetch_assoc();
			if ($row["aktiivinen"] == 1){
				$key = GUID();
				// SQL-kysely
				$sql_query = "
					INSERT INTO pw_reset 
						(reset_key, user_id)
					VALUES 
						('$key','$email')";
				
				$result = mysqli_query($connection, $sql_query) or die(mysqli_error($connection));	// Kyselyn tulos
				// Luo GUID
				// Lisää data tietokantaan
				// Tulosta linkki
				//echo ( "<br /><br /><h2>Odota hetki, ohjataan uudelleen...<br /></h2>");
				//sleep(2); //Jotain outoa ajoituksen kanssa. Jos sen lähettää heti, niin se valitaa pyyntöä vanhentuneeksi.
				// Oletan, että jotain outoa aikojen vertailun kanssa. Tämä on nopea (ja ehkä huono) korjaus.
				//header("Location:pw_reset.php?id=$key");
				
				laheta_salasana_linkki($email, $key);
				header("Location:login.php?redir=9");
				exit();
			}
			else {
				header("Location:login.php?redir=4");
				exit();
			}
		}
		else {
			header("Location:login.php?redir=1");
			exit();
		}
		
	}
	
	function GUID()	{ // Just... don't even ask.
		if (function_exists('com_create_guid') === true) {
			return trim(com_create_guid(), '{}');
		} else
		return sprintf('%04X%04X-%04X-%04X-%04X-%04X%04X%04X', mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(16384, 20479), mt_rand(32768, 49151), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535));
	}
?>
</html>
</body>