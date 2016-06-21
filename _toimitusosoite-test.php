<!DOCTYPE html>
<html lang="fi">
<head>
	<meta charset="UTF-8">
	<title>Toimitusosoite-testi</title>
	<?php require 'tietokanta.php';?>
</head>
<body>

	<!-- 
TODO: Incorporate this page into Omat_tiedot.php-page
... yes, that would very good indeed.
	-->
	
<?php

if ( !empty($_POST["muokkaa"]) ) {
	$osoite_id = $_POST["muokkaa"];
	$row = hae_toimitusosoite($id, $osoite_id); 
	$email 		= $row['sahkoposti'];
	$puhelin 	= $row['puhelin'];
	$yritys		= $row['yritys'];
	$katuosoite	= $row['katuosoite'];
	$postinumero = $row['postinumero'];
	$postitoimipaikka = $row['postitoimipaikka']?>
	<!-- HTML -->
	<div>Muokkaa toimitusosoitteita</div>
	<br>
	<form action=_toimitusosoite-test.php name=testilomake method=post>
		<label>Sähköposti</label>
			<input name=email type=email pattern=".{3,50}" value="<?= $email; ?>" required><br>
		<label>Puhelin</label>
			<input name=puhelin type=tel pattern=".{1,20}" value="<?= $puhelin; ?>" required><br>
		<label>Yritys</label>
			<input name=yritys type=text pattern=".{3,50}" value="<?= $yritys; ?>" required><br>
		<label>Katuosoite</label>
			<input name=katuosoite type=text pattern=".{3,50}" value="<?= $katuosoite; ?>" required><br>
		<label>Postinumero</label>
			<input name=postinumero type=number pattern=".{3,50}" value="<?= $postinumero; ?>" required><br>
		<label>Postitoimipaikka</label>
			<input name=postitoimipaikka type=text pattern="[a-öA-Ö]{3,50}" value="<?= $postitoimipaikka; ?>" required>
		<input name=osoite_id type=hidden value="<?= $osoite_id; ?>">
		<br><br>
		<input type=submit name=muokkaa_vanha value="Tallenna muutokset">
		<br>
	</form> <!-- HTML --><?php
	
} elseif ( !empty($_POST["muokkaa_vanha"]) ) {
	tallenna_uudet_tiedot();
	header("Refresh:0;url=omat_tiedot.php");
	
} elseif ( !empty($_POST["tallenna_uusi"]) ) {
	lisaa_uusi_osoite();
	header("Refresh:0;url=omat_tiedot.php");
	
} elseif ( !empty($_POST["poista"]) ) {
	poista_osoite();
	header("Refresh:0;url=omat_tiedot.php");
	
} elseif ( !empty($_POST['uusi_osoite']) ) {?>
	<!-- HTML -->
	<div>Lisää uuden toimitusosoitteen tiedot</div>
	<br>
	<form action=_toimitusosoite-test.php name=testilomake method=post>
		<label>Sähköposti</label>
			<input name=email type=email pattern=".{3,50}" placeholder="yourname@email.com" required><br>
		<label>Puhelin</label>
			<input name=puhelin type=tel pattern=".{1,20}" placeholder="000 1234 789" required><br>
		<label>Yritys</label>
			<input name=yritys type=text pattern=".{3,50}" placeholder="Yritys Oy" required><br>
		<label>Katuosoite</label>
			<input name=katuosoite type=text pattern=".{3,50}" placeholder="Katu 42" required><br>
		<label>Postinumero</label>
			<input name=postinumero type=number pattern=".{3,50}" placeholder="00001" required><br>
		<label>Postitoimipaikka</label>
			<input name=postitoimipaikka type=text pattern="[a-öA-Ö]{3,50}" placeholder="KAUPUNKI" required>
		<br><br>
		<input type=submit name=tallenna_uusi value="Tallenna uusi osoite">
		<br>
	</form> <!-- HTML --><?php
} else {
	header("Refresh:0;url=omat_tiedot.php");
}
?>

</body>
</html>