<!DOCTYPE html>
<html lang="fi">
<head>
	<meta charset="UTF-8">
	<link rel="stylesheet" href="css/styles.css">
	<link rel="stylesheet" href="css/jsmodal-light.css">
</head>
<body>


<?php
require 'tietokanta.php';
global $connection;

$id = 2;

function hae_kaikki_toimitusosoitteet_ja_tulosta() {
	global $connection;
	global $id;
	$sql_query = "	SELECT	*
	FROM	toimitusosoite
	WHERE	kayttaja_id = '$id'";
	$result = mysqli_query($connection, $sql_query) or die(mysqli_error($connection));
	while ($row = $result->fetch_assoc()) {	
	?>
		<li id="osoite_id_<?= $row['osoite_id'] ?>"> Osoite <?= $row['osoite_id'] ?><br> </li> \
			<br> \
			<label><span>Sähköposti</span></label><?= $row['sahkoposti']?><br> \
			<label><span>Puhelin</span></label><?= $row['puhelin']?><br> \
			<label><span>Yritys</span></label><?= $row['yritys']?><br> \
			<label><span>Katuosoite</span></label><?= $row['katuosoite']?><br> \
			<label><span>Postinumero</span></label><?= $row['postinumero']?><br> \
			<label><span>Postitoimipaikka</span></label><?= $row['postitoimipaikka']?><br> \
			<br> \
			<input class="nappi" type="button" value="Valitse" \
				onClick="valitse_toimitusosoite(<?= $row['osoite_id'] ?>);"> \
		\
		<hr> \
	<?php
	}
}
?>


<script src="js/jsmodal-1.0d.min.js"></script>
<script>
function avaa_Modal_valitse_toimitusosoite() {
	Modal.open({
		content:  ' \
			<ul>\
			<?= hae_kaikki_toimitusosoitteet_ja_tulosta()?> \
			</ul>\
			'
	});
}
</script>


<!-- HTML -->
<input class="nappi" type="button" value="Valitse toimitusosoite" onClick="avaa_Modal_valitse_toimitusosoite();">
<!-- HTML END -->


</body>
</html>