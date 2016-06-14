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
$connection = mysqli_connect(DB_HOST, DB_USERNAME, DB_PASSWORD, DB_NAME)
				or die("Connection error:" . mysqli_connect_error());
				
$id = $_SESSION['id']; //Tarvitaan funktioissa

/* 
Hakee kirjautuneen kayttajan toimitusosoitteen,
 ja palauttaa sen arrayna.
Param: 
	$osoite_id : int, muokattavan t.o.:n ID
Return:	Array, jossa toimitusosoitteen tiedot
*/
function hae_toimitusosoite($osoite_id) {
	global $connection;
	global $id;
	
	$sql_query = "
			SELECT	*
			FROM	toimitusosoite
			WHERE	kayttaja_id = '$id' 
				AND osoite_id = '$osoite_id'";
	$result = mysqli_query($connection, $sql_query) or die(mysqli_error($connection));
	$row = $result->fetch_assoc();
	return $row;
}

/* 
Tallentaa uudet tiedot tietokantaan.
 Tallennettavat tiedot $_POST-muuttujan kautta.
 Funktio on tarkoitus kutsua vain lomakkeen lähetyksen jälkeen.
Param: ---
Return:	Boolean, true/false
*/
function tallenna_uudet_tiedot() {
	global $id;
	global $connection;
	
	$osoite_id = $_POST['osoite_id'];
	$a = $_POST['email'];		//Olin laiska kun nimesin nama muuttujat
	$b = $_POST['puhelin'];
	$c = $_POST['yritys'];
	$d = $_POST['katuosoite'];
	$e = $_POST['postinumero'];
	$f = $_POST['postitoimipaikka'];
	
	$sql_query = "
			UPDATE	toimitusosoite
			SET		sahkoposti='$a', puhelin='$b', yritys='$c', katuosoite='$d', postinumero='$e', postitoimipaikka='$f'
			WHERE	kayttaja_id = '$id' 
				AND osoite_id = '$osoite_id'";
				
	$result = mysqli_query($connection, $sql_query) or die(mysqli_error($connection));
	return $result;
}

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
		<input name=email type=email pattern=".{3,50}" value="<?= $email; ?>" required>
		<br>
		<label>Puhelin</label>
		<input name=puhelin type=tel pattern=".{1,20}" value="<?= $puhelin; ?>" required>
		<br>
		<label>Yritys</label>
		<input name=yritys type=text pattern=".{3,50}" value="<?= $yritys; ?>" required>
		<br>
		<label>Katuosoite</label>
		<input name=katuosoite type=text pattern=".{3,50}" value="<?= $katuosoite; ?>" required>
		<br>
		<label>Postinumero</label>
		<input name=postinumero type=number pattern=".{3,50}" value="<?= $postinumero; ?>" required>
		<br>
		<label>Postitoimipaikka</label>
		<input name=postitoimipaikka type=text pattern="[a-öA-Ö]{3,50}" value="<?= $postitoimipaikka; ?>" required>
		<input name=osoite_id type=hidden value="<?= $osoite_id; ?>">
		<br><br>
		<input type=submit name=tallenna value="Tallenna muutokset">
		<br>
	</form> <!-- HTML --><?php
} elseif ( !empty($_POST["tallenna"]) ) {
	tallenna_uudet_tiedot();
}
?>

</body>
</html>