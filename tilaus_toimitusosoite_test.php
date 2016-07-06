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

function hae_kaikki_toimitusosoitteet_JSON_array() {
	global $connection;
	global $id;
	global $osoitekirja_array;
	$osoitekirja_array = array();
	$sql_query = "	SELECT	*
					FROM	toimitusosoite
					WHERE	kayttaja_id = '$id'
					ORDER BY osoite_id;";
	$result = mysqli_query($connection, $sql_query) or die(mysqli_error($connection));
	while ( $row = $result->fetch_assoc() ) {
		$id = $row['osoite_id'];
		
		foreach ( $row as $key => $value ) {
			$osoitekirja_array[$id][$key] = $value;
		}
	}
	
	if ($osoitekirja_array) {
		return true;
	} else return false;
}

function hae_kaikki_toimitusosoitteet_ja_tulosta_Modal() {
	global $osoitekirja_array;
	
	foreach ( $osoitekirja_array as $osoite ) {
		
		echo "<div id=\"osoite_id_" . $osoite["osoite_id"] . "\"> Osoite " . $osoite["osoite_id"] . "<br><br> \\";
		
		foreach ( $osoite as $key => $value ) {
			echo "<label><span>" . $key . "</span></label>" . $value . "<br> \\";
		}
		echo "
			<br> \
			<input class=\"nappi\" type=\"button\" value=\"Valitse\" onClick=\"valitse_toimitusosoite(" . $osoite["osoite_id"] . ");\"> \
		</div>\
		<hr> \
		";
	}
}
hae_kaikki_toimitusosoitteet_JSON_array();
?>


<script src="js/jsmodal-1.0d.min.js"></script>
<script>

var osoitekirja = <?= json_encode($osoitekirja_array)?>;
console.log(JSON.stringify(osoitekirja));

function avaa_Modal_valitse_toimitusosoite() {
	Modal.open({
		content:  ' \
			<?= hae_kaikki_toimitusosoitteet_ja_tulosta_Modal()?> \
			',
		draggable: true
	});
}


function valitse_toimitusosoite(osoite_id) {
	var osoite_array = osoitekirja[osoite_id];
	console.log(JSON.stringify(osoite_array));
	//Muuta tempate literal muotoon heti kuun saan päivitettyä tämän EMACS2015
	var html_osoite = document.getElementById('content_here');
	html_osoite.innerHTML = "Toimitusosoite" + osoite_id + "<br>"
		+ "Sähköposti: " + osoite_array['sahkoposti'] + "<br>"
		+ "Katuosoite: " + osoite_array['katuosoite'] + "<br>"
		+ "Postinumero ja -toimipaikka: " + osoite_array['postinumero'] + " " + osoite_array['postitoimipaikka'] + "<br>"
		+ "Puhelinnumero: " + osoite_array['puhelin'];
	console.log(html_osoite);
}
</script>


<!-- HTML -->
<input class="nappi" type="button" value="Valitse toimitusosoite" onClick="avaa_Modal_valitse_toimitusosoite();">
<br><br><br>
<div id="content_here">
</div>
<!-- HTML END -->


</body>
</html>