<!DOCTYPE html>
<html>
<head>
	<link rel="stylesheet" type="text/css" href="css/login_styles.css">
	<meta charset="UTF-8">
	<title>Password reset</title> 
</head>
<body>
<h1 style="text-align:center;">
	<img src="img/rantak_varao-Logo.jpg" alt="Rantakylän Varaosa Oy" style="height:200px;">
</h1>
<?php
	require 'tietokanta.php';	// Tietokannan tiedot
	session_start();			// Aloitetaan sessio kayttajan tietoja varten
	// Yhdista tietokantaan
	$connection = mysqli_connect( DB_HOST, DB_USERNAME, DB_PASSWORD, DB_NAME ) 
					or die("Connection error:" . mysqli_connect_error());

	
	
	if ( !empty($_GET['id']) ) {
		$id = $_GET["id"]; // Käyttäjän henkilökohtainen, salattu ID; tallennettu tietokantaan
		
		if ( !empty($_POST['new_password']) ) {		//
			$salasana 		= $_POST["new_password"];			// Salasana
			$salasana_varm 	= $_POST["confirm_new_password"];	// Salasanan varmistus
			$url = "http://localhost/tuoteluettelo_wamp/pw_reset.php?id=$id";
			
			if ( $salasana != $salasana_varm ) { // Salasanat ei tasmaa
				echo ( "
						<h2>Salasana ja varmistus eivät ole samat. Kokeile uudestaan:<br />
						<a href='$url'>$url</a><br /><br />
						</h2>
						");
			} else { // Salasana ja varmistus tasmaa
			
				db_vaihda_salasana();
				
			}//if salasana == varmistus
		}//if POST is empty (ei ole uudelleenohjaus)
		
		else {
			// SQL-kysely
			$sql_query = " 
				SELECT 	*
				FROM 	pw_reset
				WHERE 	reset_key = '$id'";
			
			$result = mysqli_query( $connection, $sql_query ) 
						or die( mysqli_error($connection) ); // Kyselyn tulos
			$row_count = mysqli_num_rows( $result ); // Kyselyn  tuloksen rivien määrä
			
			if ( $row_count > 0 ) { 	// Katsotaan, loytyiko tulos
				$row = mysqli_fetch_assoc( $result );
				$_SESSION['email']	= $row['user_id'];			// Otetaan talteen tiedot
				$time_then			= $row['reset_exp_aika'];	//  Aika, jolloin tieto tallennettiin (kun käyttäjä pyysi uutta salasanaa)
				$time_now			= time();					//	Aika nyt
				$difference			= $time_now - $time_then;	//	Kulunut aika pyynnosta (pyynto vanhenee tunnissa)
				
				
				echo "Foo -- <br /> 
					time_then: " . $time_then . "<br />" .
					"time_now: " . $time_now . "<br />" .
					"difference: " . $difference;
				
				if ( $difference < 3600 ) { //Tarkistetaan aika (sekunneissa, 1h = 3600s)?>
					<?php echo "... bar";?>
					<fieldset><legend>Unohditko salasanasi?</legend>
						<form name="reset" action="pw_reset.php<?php echo "?id=$id";?>" method="post" accept-charset="utf-8">
							<?php echo $_SESSION['email']; // Muistutuksena kauttajalle ?>
							<br><br>
							<label>Uusi salasana</label>
							<input name="new_password" type="password" pattern=".{6,}" title="Pituus min 6 merkkiä." required autofocus placeholder="salasana">
							<br><br>
							<label>Vahvista uusi salasana</label>
							<input name="confirm_new_password" type="password" pattern=".{6,}" title="Pituus min 6 merkkiä." required autofocus placeholder="vahvista salasana">
							<br><br>
						</form>
					</fieldset>
				<?php
				}//if difference (pyynto vanhentunut)
				else { echo "Pyyntö vanhentunut."; }
			}//if account found (ei loytynyt pyyntoa)
		}//if POST is empty (ei tullut sivulle uudelleenohjauksella)
	}//if ID empty (ei tullut sivulle palautuslinkin kautta)
	
	else { echo "<h2>Go away.</h2>"; }
	
	function db_vaihda_salasana($asiakas_uusi_salasana){
		$asiakas_sposti = $_SESSION['email'];
		$hajautettu_uusi_salasana = password_hash($asiakas_uusi_salasana, PASSWORD_DEFAULT);

		$query = "
			SELECT 	* 
			FROM 	kayttaja 
			WHERE 	sahkoposti = '$asiakas_sposti'";
			
		$result = mysqli_query($connection, $query) or die(mysqli_error($connection));
		$row_count = mysqli_num_rows($result);
		
		if ( $row_count != 1 ) {
			return -1; //Kayttajaa ei loytynyt
		} else {
			//päivitetään uusi salasana tietokantaan
			$query = "
				UPDATE	$tbl_name 
				SET 	salasana_hajautus='$hajautettu_uusi_salasana'
				WHERE	sahkoposti='$asiakas_sposti'";
			mysqli_query($connection, $query) or die(mysqli_error($connection));

			return 1;	//talletetaan tulos sessioniin
		}
	}
?>
</html>
</body>