<!DOCTYPE html>
<html lang="fi">
<head>
	<meta charset="UTF-8">
	<title>Toimitusosoite-testi</title>
	<?php require 'tietokanta.php';?>
</head>
<body>

<?php
$connection = mysqli_connect(DB_HOST, DB_USERNAME, DB_PASSWORD, DB_NAME)
				or die("Connection error:" . mysqli_connect_error());
				
function hae_kaikki_toimitusosoitteet_ja_tulosta($kayttaja_id) {
	global $connection;
	$sql_query = "
			SELECT	*
			FROM	toimitusosoite
			WHERE	kayttaja_id = '$kayttaja_id'";
	$result = mysqli_query($connection, $sql_query) or die(mysqli_error($connection));
	$i = 1;
	while ($row = $result->fetch_assoc()) {
        /*HTML-tulostus*/?>
		<p> Osoite <?= $i ?><br>
		Sähköposti: <?= $row['sahkoposti']?><br>
		Puhelin:  <?= $row['puhelin']?><br>
		Yritys:  <?= $row['yritys']?><br>
		Katuosoite:  <?= $row['katuosoite']?><br>
		Postinumero:  <?= $row['postinumero']?><br>
		Postitoimipaikka:  <?= $row['postitoimipaikka']?><br><br>
		<form action="_toimitusosoite-test.php" name="testilomake" method="post">
			<input type=hidden name=muokkaa value="<?= $i ?>">
			<input type=submit value="Muokkaa">
		</form>
		</p><hr>
		<?php $i++;
    }
}
function hae_toimitusosoite($kayttaja_id, $osoite_id) {
	global $connection;
	$sql_query = "
			SELECT	*
			FROM	toimitusosoite
			WHERE	kayttaja_id = '$kayttaja_id' 
				AND osoite_id = '$osoite_id'";
	$result = mysqli_query($connection, $sql_query) or die(mysqli_error($connection));
	
	$row = $result->fetch_assoc();
	return $row;
}

function tallenna_uudet_tiedot() {
	global $connection;
	$osoite_id = $_POST['osoite_id'];
	$a = $_POST['email'];
	$b = $_POST['puhelin'];
	$c = $_POST['yritys'];
	$d = $_POST['katuosoite'];
	$e = $_POST['postinumero'];
	$f = $_POST['postitoimipaikka'];
	$sql_query = "
			UPDATE	toimitusosoite
			SET		sahkoposti='$a', puhelin='$b', yritys='$c', katuosoite='$d', postinumero='$e', postitoimipaikka='$f'
			WHERE	kayttaja_id = '2' 
				AND osoite_id = '$osoite_id'";
				
	$result = mysqli_query($connection, $sql_query) or die(mysqli_error($connection));
	
	echo $result;
}

if ( empty($_POST["muokkaa"]) && empty($_POST["tallenna"])) {
	hae_kaikki_toimitusosoitteet_ja_tulosta(2);
} elseif ( empty($_POST["tallenna"]) ) {
	$osoite_id = $_POST["muokkaa"];
	$row = hae_toimitusosoite(2, $osoite_id); 
	$email 		= $row['sahkoposti'];
	$puhelin 	= $row['puhelin'];
	$yritys		= $row['yritys'];
	$katuosoite	= $row['katuosoite'];
	$postinumero = $row['postinumero'];
	$postitoimipaikka = $row['postitoimipaikka']?>
	
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
	</form> <?php
} elseif ( !empty($_POST["tallenna"]) ) {
	tallenna_uudet_tiedot();
}
?>

</body>
</html>