
<?php 
	/**
	 * Tiedosto, joka saa uuden asiakkaan tiedot ja
	 * tarkastaa onko salasana ja vahvistussalasana samat ja 
	 * onko sähköpostia jo tietokannassa. Jos ei, tiedot lisätään
	 * tietokantaan;
	 * 
	 * Palaa takaisin yp_asiakas.php tiedostoon.
	 * Tallettaa $_SESSION['result'] -1,-2 tai 1
	 * (-1: sahkoposti varattu,	-2: salasanat ei täsmää, 1: tiedot lisätty tietokantaan)
	 * 
	 *  
	 */



	$host = "localhost";				// Host
	$username = "root";					// Käyttäjänimi
	$password="";						// Salasana
	$db_name="tuoteluettelo_database";	// Tietokannan nimi
	$tbl_name="kayttaja";				// Taulun nimi

	session_start();
	


	
	$asiakas_etunimi = $_POST['etunimi'];
	$asiakas_sukunimi = $_POST['sukunimi'];
	$asiakas_sposti = $_POST['sposti'];
	$asiakas_puh = $_POST['puh'];
	$asiakas_yritysnimi = $_POST['yritysnimi'];
	$asiakas_salasana = $_POST['password'];
	$asiakas_varmista_salasana = $_POST['confirm_password'];
	$asiakas_hajautettu_salasana = password_hash($_POST['password'], PASSWORD_DEFAULT);
	
	
	//Tarkastetaan, että salsana ja vahvistussalasana ovat samat.
	if ($asiakas_salasana != $asiakas_varmista_salasana){
		$_SESSION['result'] = -2;	//salasanat ei täsmää
		header("location:yp_lisaa_asiakas.php");
	}else {
	
	
		//Palvelimeen liittyminen
		$connection = mysqli_connect($host, $username, $password, $db_name) or die("Connection error:" . mysqli_connect_error());
	
		//Tarkastetaan onko samannimistä käyttäjätunnusta
		$query = "SELECT * FROM $tbl_name WHERE sahkoposti='$asiakas_sposti'";
		$result = mysqli_query($connection, $query);
		$count = mysqli_num_rows($result);
		if($count != 0){
			$_SESSION['result'] = -1; //talletetaan tulos sessioniin: käyttäjänimi varattu	
		} else {
			//lisätään tietokantaan	
			$query = "INSERT INTO $tbl_name (salasana_hajautus, etunimi, sukunimi, yritys, sahkoposti, puhelin) 
			VALUES ('$asiakas_hajautettu_salasana', '$asiakas_etunimi', '$asiakas_sukunimi', '$asiakas_yritysnimi', '$asiakas_sposti', '$asiakas_puh')";
			$result = mysqli_query($connection, $query);
			$_SESSION['result'] = 1;	//talletetaan tulos sessioniin	
		}
		mysqli_close($connection);
		
		header("location:yp_lisaa_asiakas.php");
	}
		
	
?>
