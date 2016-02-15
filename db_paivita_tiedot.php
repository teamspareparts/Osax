<?php

	$host = "localhost";				// Host
	$username = "root";					// Käyttäjänimi
	$password="";						// Salasana
	$db_name="tuoteluettelo_database";	// Tietokannan nimi
	$tbl_name="kayttaja";				// Taulun nimi
	
	session_start();
	
	
	$asiakas_sposti = $_SESSION['email'];		//oltava telletettuna sessioniin
	
	$asiakas_etunimi = $_POST['etunimi'];
	$asiakas_sukunimi = $_POST['sukunimi'];
	$asiakas_puh = $_POST['puh'];
	$asiakas_yritysnimi = $_POST['yritysnimi'];
	$asiakas_uusi_salasana = $_POST['new_password'];
	$asiakas_varmista_uusi_salasana = $_POST['confirm_new_password'];
	$hajautettu_uusi_salasana = password_hash($asiakas_uusi_salasana, PASSWORD_DEFAULT);
	
	
	
	//Tarkastetaan, että salasana ja vahvistussalasana ovat samat.
	//Voi olla tyhjä vielä tässä vaiheessa!
	
	
		//Palvelimeen liittyminen
	$connection = mysqli_connect($host, $username, $password, $db_name) or die("Connection error:" . mysqli_connect_error());

	//Tarkastetaan löytyykö käyttäjätunnusta
	$query = "SELECT * FROM $tbl_name WHERE sahkoposti='$asiakas_sposti'";
	$result = mysqli_query($connection, $query) or die(mysqli_error($connection));	
	$count = mysqli_num_rows($result);
	if($count != 1){
		$_SESSION['result'] = -1; //käyttäjänimeä ei löytynyt
	} else {
		if ($asiakas_uusi_salasana != $asiakas_varmista_uusi_salasana){
			$_SESSION['result'] = -2;	//salasanat ei täsmää
		}else {
			//päivitetään tietokantaan
			$query = "UPDATE $tbl_name SET etunimi='$asiakas_etunimi', sukunimi='$asiakas_sukunimi', puhelin='$asiakas_puh', yritys='$asiakas_yritysnimi'
			WHERE sahkoposti='$asiakas_sposti'";
			mysqli_query($connection, $query) or die(mysqli_error($connection));
			
			
			//päivitetään myös salasana, jos muutettu
			if ($asiakas_uusi_salasana != "" && $asiakas_varmista_uusi_salasana != ""){
				$query = "UPDATE $tbl_name SET salasana_hajautus='$hajautettu_uusi_salasana'
				WHERE sahkoposti='$asiakas_sposti'";
				mysqli_query($connection, $query) or die(mysqli_error($connection));
			}
			
			$_SESSION['result'] = 1;	//talletetaan tulos sessioniin
		}
	}
	mysqli_close($connection);
	
	
	
	//tarkastetaan mennäänkö takaisin asiakkaan vai ylläpitäjän sivulle
	$row = mysqli_fetch_assoc($result);
	if ($row["yllapitaja"] == 1){
		header("location:yp_omat_tiedot.php");
	}else {
		header("location:omat_tiedot.php");
	}


?>

