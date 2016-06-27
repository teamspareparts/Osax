<?php
/*
Kirjoitin taman katsyn koodin testaamista varten.
Se luo seka ALV-tasot (kolme, ei aitoja),
 ja asiakkaan, jolla kaksi osoitetta.
Pienena varoituksena, tassa ei ole tarkistuksia, joten se luo kopioita,
 jos ajaa usemman kerran.
*/
require '../tietokanta.php';

$connection = mysqli_connect(DB_HOST, DB_USERNAME, DB_PASSWORD, DB_NAME)
				or die("Connection error:" . mysqli_connect_error());

/* Asiakkaan lisäys */
$asiakas = "asiakas@asiakas";
$salasana = password_hash("asiakas", PASSWORD_DEFAULT);/*
$sql_query = "
	INSERT
	INTO	kayttaja (sahkoposti, salasana_hajautus) 
	VALUES ('$asiakas', '$salasana');";
$result = mysqli_query($connection, $sql_query) or die(mysqli_error($connection));

/* Haetaan asiakkaan tiedot myohempaa kayttoa varten */
$sql_query = "
		SELECT	id
		FROM	kayttaja
		WHERE	sahkoposti = '$asiakas'";
$result = mysqli_query($connection, $sql_query) or die(mysqli_error($connection));
$row = $result->fetch_assoc();
$asiakas_id = $row['id'];


/* ALV-tasojen lisäys */
$pros1 = 0.24;
$pros2 = 0.20;
$pros3 = 0.10;
$sql_query = "
	INSERT
	INTO	ALV_kanta (kanta, prosentti) 
	VALUES ('1', '$pros1'),
			('2', '$pros2'),
			('3', '$pros3');";
$result = mysqli_query($connection, $sql_query) or die(mysqli_error($connection));

/* Osoitteiden lisays */
$asiakkaan_id = $asiakas_id;
$sahkoposti = $asiakas;
$puhelin = "555 ASIAKAS";
$yritys = "Asiakas Oy";
$katuosoite = "asiakaskatu 55";
$postinumero = "55500";
$postitoimipaikka = "ASIAKAS";
$sql_query = "
	INSERT
	INTO	toimitusosoite 
		(kayttaja_id, osoite_id, sahkoposti, puhelin, yritys, 
			katuosoite, postinumero, postitoimipaikka) 
	VALUES 
		('$asiakkaan_id', 1, '$sahkoposti', '$puhelin', '$yritys', 
			'$katuosoite', '$postinumero', '$postitoimipaikka');";
$result = mysqli_query($connection, $sql_query) or die(mysqli_error($connection));

/* Osoitteiden lisays - Toinen osoite, sama asiakas */
$asiakkaan_id = $asiakas_id;
$sahkoposti = "jokuemail@best.net";
$puhelin = "555 369874";
$yritys = "Asiakas INC";
$katuosoite = "Katukatu 42";
$postinumero = "00005";
$postitoimipaikka = "ASIAKAS";
$sql_query = "
	INSERT
	INTO	toimitusosoite 
		(kayttaja_id, osoite_id, sahkoposti, puhelin, yritys, 
			katuosoite, postinumero, postitoimipaikka) 
	VALUES 
		('$asiakkaan_id', 2, '$sahkoposti', '$puhelin', '$yritys', 
			'$katuosoite', '$postinumero', '$postitoimipaikka');";
$result = mysqli_query($connection, $sql_query) or die(mysqli_error($connection));

?>