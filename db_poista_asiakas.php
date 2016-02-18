<?php


	require 'tietokanta.php';
	$tbl_name="kayttaja";				// Taulun nimi
	
	
	session_start();
	
	
	
	
	$asiakas_ids = $_POST['ids'];
	
	
	//Palvelimeen liittyminen
	$connection = mysqli_connect(DB_HOST, DB_USERNAME, DB_PASSWORD, DB_NAME) or die("Connection error:" . mysqli_connect_error());
	
	foreach ($asiakas_ids as $asiakas_id) {
		$query = "DELETE FROM $tbl_name 
				WHERE id='$asiakas_id'";
		$result = mysqli_query($connection, $query);
	}

	//Tarkastetaan onko samannimistä käyttäjätunnusta
	
	mysqli_close($connection);

	header("location:yp_asiakkaat.php");
	exit;
	
?>