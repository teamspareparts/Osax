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
	
	$sql_query = "	SELECT	*
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
	
	$sql_query = "	UPDATE	toimitusosoite
					SET		sahkoposti='$a', puhelin='$b', yritys='$c', katuosoite='$d', postinumero='$e', postitoimipaikka='$f'
					WHERE	kayttaja_id = '$id' 
						AND osoite_id = '$osoite_id'";

	$result = mysqli_query($connection, $sql_query) or die(mysqli_error($connection));
	return $result;
}

/* lisaa_uusi_osoite()
Lisaa uuden osoitteen tietokantaan listan loppuun
 Tallennettavat tiedot $_POST-muuttujan kautta.
 Funktio on tarkoitus kutsua vain lomakkeen vastaanottamisen jalkeen.
Param: ---
Return:	Boolean, true/false
 */
function lisaa_uusi_osoite() {
	global $id;
	global $connection;

	$a = $_POST['email'];		//Olin laiska kun nimesin nama muuttujat
	$b = $_POST['puhelin'];
	$c = $_POST['yritys'];
	$d = $_POST['katuosoite'];
	$e = $_POST['postinumero'];
	$f = $_POST['postitoimipaikka'];
	$uusi_osoite_id = hae_osoitteet_indeksi();
	
	$sql_query = "	INSERT 
					INTO	toimitusosoite
						(kayttaja_id, osoite_id, sahkoposti, puhelin, yritys, katuosoite, postinumero, postitoimipaikka)
					VALUES 	('$id', '$uusi_osoite_id', '$a', '$b', '$c', '$d', '$e', '$f');";
	
	$result = mysqli_query($connection, $sql_query) or die(mysqli_error($connection));
	return $result;
}


/* poista_osoite()
Poistaa tietokannasta toimitusosoitteen annetulla ID:lla. Siirtaa viimeiselta
 paikalta toimitusosoitteen poistetun paikalle listan eheyden sailyttamiseksi.
 Poistettava ID $_POST-muuttujan kautta. Funktio on tarkoitus 
 kutsua vain lomakkeen vastaanottamisen jalkeen.
Param: ---
Return:	Boolean, true/false
 */
function poista_osoite() {
	global $id;
	global $connection;
	$osoite_id = $_POST["poista"];
	$osoite_id_viimeinen = hae_osoitteet_indeksi();
	$osoite_id_viimeinen = --$osoite_id_viimeinen;


	$sql_query = "	SELECT	id
					FROM 	tilaus
					WHERE 	osoite_id = '$osoite_id'";
	$result = mysqli_query($connection, $sql_query) or die(mysqli_error($connection));
	if ( mysqli_num_rows($result) < 1 ) {
		$sql_query = "	DELETE 
						FROM 	toimitusosoite
						WHERE 	osoite_id = '$osoite_id'";
		$result = mysqli_query($connection, $sql_query) or die(mysqli_error($connection));
		
		if ( mysqli_affected_rows($connection) > 0 ) {
			$sql_query = "	UPDATE	toimitusosoite
							SET		osoite_id='$osoite_id'
							WHERE	kayttaja_id = '$id'
								AND osoite_id = '$osoite_id_viimeinen'";
			$result = mysqli_query($connection, $sql_query) or die(mysqli_error($connection));
		}
	}
	return $result;
}
/* hae_osoitteet_indeksi()
Hakee viimeisen toimitusosoitteen indeksin + 1.
Param: ---
Return:	int, indeksi+1
 */
function hae_osoitteet_indeksi(){
	global $connection;
	global $id;
	
	$sql_query = "	SELECT	*
					FROM	toimitusosoite
					Where	kayttaja_id = '$id';";
	
	$result = mysqli_query($connection, $sql_query) or die(mysqli_error($connection));
	$row_count = mysqli_num_rows($result);
	return ++$row_count;
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
		<label>S�hk�posti</label>
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
		<input type=submit name=tallenna_vanha value="Tallenna muutokset">
		<br>
	</form> <!-- HTML --><?php
	
} elseif ( !empty($_POST["tallenna_vanha"]) ) {
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
	<div>Lis�� uuden toimitusosoitteen tiedot</div>
	<br>
	<form action=_toimitusosoite-test.php name=testilomake method=post>
		<label>S�hk�posti</label>
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