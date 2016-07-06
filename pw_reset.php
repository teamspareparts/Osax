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
<div id="login_container">
<?php
	if ( !empty($_GET['id']) ) {
		require 'tietokanta.php';	// Tietokannan tiedot
		session_start();			// Aloitetaan sessio kayttajan tietoja varten
		$connection = mysqli_connect( DB_HOST, DB_USERNAME, DB_PASSWORD, DB_NAME ) 
						or die("Connection error:" . mysqli_connect_error()); // Yhdista tietokantaan
						
		$user_id = $_GET["id"]; // Käyttäjän henkilökohtainen, salattu ID; tallennettu tietokantaan
		if (!empty($_GET['redir'])) {	// Onko uudellenohjaus?
			$mode = $_GET["redir"];		// Otetaan moodi talteen
			if ( $mode == 1 ) {			// Jos moodi --> varmistu != salasana 
?>
				<div id="content">
					<fieldset id=error>
						<legend> Salasana ja varmistus ei täsmää </legend>
						<p>Kokeile uudestaan. Varmista, että antamasi salasana ja varmistus täsmäävät</p>
					</fieldset>
				</div>
<?php
			}
		}
		if ( !empty($_POST['new_password']) ) {		//
			$salasana 		= $_POST["new_password"];			// Salasana
			$salasana_varm 	= $_POST["confirm_new_password"];	// Salasanan varmistus
			
			if ( $salasana != $salasana_varm ) { // Salasanat ei tasmaa
				header("Location:pw_reset.php?id=$user_id&redir=1");
						
			} else { // Salasana ja varmistus tasmaa
			
				if ( db_vaihda_salasana($salasana) == TRUE ) {
					unset($_SESSION['sposti']);
					//salasanan vaihto onnistui
					header("Location:login.php?redir=8");
				}
				
			}//if salasana == varmistus
		}//if POST is empty (ei ole uudelleenohjaus)
		
		else {
			// SQL-kysely
			$sql_query = " 
				SELECT 	*
				FROM 	pw_reset
				WHERE 	reset_key = '$user_id'";
			
			$result = mysqli_query( $connection, $sql_query ) 
						or die( mysqli_error($connection) ); // Kyselyn tulos
			$row_count = mysqli_num_rows( $result ); // Kyselyn  tuloksen rivien määrä
			
			if ( $row_count > 0 ) { 	// Katsotaan, loytyiko tulos
				
				//Varmuuden varalta aikavyöhyke oikeaksi
				date_default_timezone_set('Europe/Helsinki');
				
				$row 		= mysqli_fetch_assoc( $result );
				$_SESSION['sposti']	= $row['user_id'];			// Otetaan talteen tiedot
				$mysql_dt	= $row['reset_exp_aika'];	//  Aika, jolloin tieto tallennettiin (kun käyttäjä pyysi uutta salasanaa)
				
				//OLETETAAN ETTÄ MYSQL TIMEZONE ON TALLENNETTU SUOMEN AIKAAN
				$time_then 	= new DateTime( $mysql_dt );//   muunnettuna DateTime-muotoon
				$time_now	= new DateTime();			//	Aika nyt
				
				$interval = $time_now -> diff($time_then);
				
				
				$y = $interval->format('%y');
				$m = $interval->format('%m');
				$d = $interval->format('%d');
				$h = $interval->format('%h');
				
				
				$difference = $y + $m + $d + $h; // Lasketaan aikojen erotus
				/*
				 * Linkki pysyy aktiivisena vain tunnin.
				 */
				
				/****************************************
				 * 										*
				 * HUOM! Allaolevaa lukua pitää 		*
				 * muutta tietokannan asetusten mukaan	*
				 * 										*
				 ****************************************/
				if ( $difference < 4 ) { //Tarkistetaan aika ?>
					<fieldset><legend>Vaihda salasanasi</legend>
						<form name="reset" action="pw_reset.php<?php echo "?id=$user_id";?>" method="post" accept-charset="utf-8">
							<?php echo $_SESSION['sposti']; // Muistutuksena kauttajalle ?>
							<br><br>
							<label>Uusi salasana</label>
							<input name="new_password" type="password" pattern=".{6,}" title="Pituus min 6 merkkiä." required autofocus placeholder="salasana">
							<br><br>
							<label>Vahvista uusi salasana</label>
							<input name="confirm_new_password" type="password" pattern=".{6,}" title="Pituus min 6 merkkiä." required autofocus placeholder="vahvista salasana">
							<br><br>
							<input type="hidden" name="mode" value="password_reset">
							<input type="submit" value="Vaihda salasana">
						</form>
					</fieldset>
				<?php
				}//if difference (pyynto vanhentunut)
				else { echo header("Location:login.php?redir=7"); exit(); }
			}//if account found (ei loytynyt pyyntoa)
			else { header("Location:login.php"); exit(); }
		}//if POST is empty (ei tullut sivulle uudelleenohjauksella)
	}//if ID empty (ei tullut sivulle palautuslinkin kautta)
	
	else { header("Location:login.php"); exit();}
	
	function db_vaihda_salasana($asiakas_uusi_salasana){
		$asiakas_sposti = $_SESSION['sposti'];
		$hajautettu_uusi_salasana = password_hash($asiakas_uusi_salasana, PASSWORD_DEFAULT);

		$sql_query = "
			SELECT 	* 
			FROM 	kayttaja 
			WHERE 	sahkoposti = '$asiakas_sposti'";
			
		$connection = mysqli_connect( DB_HOST, DB_USERNAME, DB_PASSWORD, DB_NAME ) 
					or die("Connection error:" . mysqli_connect_error());
		$result = mysqli_query($connection, $sql_query) or die(mysqli_error($connection));
		$row_count = mysqli_num_rows($result);
		
		if ( $row_count != 1 ) {
			return 0; //Kayttajaa ei loytynyt
		} else {
			//päivitetään uusi salasana tietokantaan
			$query = "
				UPDATE	kayttaja 
				SET 	salasana_hajautus='$hajautettu_uusi_salasana', salasana_vaihdettu=NOW(), salasana_uusittava=0
				WHERE	sahkoposti='$asiakas_sposti'";
			mysqli_query($connection, $query) or die(mysqli_error($connection));

			return 1;	//talletetaan tulos sessioniin
		}
	}
?>
</div>
</html>
</body>