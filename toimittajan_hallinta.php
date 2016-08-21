
<!DOCTYPE html>
<html lang="fi">
<head>
<meta charset="UTF-8">
<link rel="stylesheet" href="css/styles.css">
<link rel="stylesheet" href="css/jsmodal-light.css">
<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.0/jquery.min.js"></script>
<script src="js/jsmodal-1.0d.min.js"></script>
<title>Toimittajat</title>
</head>
<body>
<?php
require 'header.php';
require 'tietokanta.php';
require 'tecdoc.php';
if (!is_admin()) {
	header("Location:etusivu.php");
	exit();
}
//tarkastetaan onko tultu toimittajat sivulta
$brandId = isset($_GET['brandId']) ? $_GET['brandId'] : null;
if(!$brandId) {
	header("Location:toimittajat.php");
	exit();
}
$brandName = $_GET['brandName'];
$brandAddress = getAmBrandAddress($brandId)[0];
$logo_src = TECDOC_THUMB_URL . $brandAddress->logoDocId . "/";
?>
<br>
<div class="otsikko"><img src="<?= $logo_src?>" style="vertical-align: middle; padding-right: 20px; display:inline-block;" /><h2 style="display:inline-block; vertical-align:middle;"><?= $brandName?></h2></div>
<div id="painikkeet">
	<a href="lisaa_tuotteita.php?brandId=<?= $brandId?>&brandName=<?= $brandName?>"><span class="nappi">Lisää tuotteita</span></a>
	<a href="yp_valikoima.php?brand=<?=$brandId?>"><span class="nappi">Valikoima</span></a>
</div>

<br><br>
<div style="text-align: center; display:inline-block; margin-left: 5%;">
<?php

/**
 * @param $brandAddress
 */
function tulosta_yhteystiedot($brandAddress){

	echo '<div style="float:left; padding-right: 200px;">';
	echo '<table>';
	echo "<th colspan='2' class='text-center'>Yhteystiedot</th>";
	echo '<tr><td>Yritys</td><td>'. $brandAddress->name .'</td></tr>';
	echo '<tr><td>Osoite</td><td>'. $brandAddress->street . '<br>' . $brandAddress->zip . " " . strtoupper($brandAddress->city) .'</td></tr>';
	echo '<tr><td>Puh</td><td>'. $brandAddress->phone .'</td></tr>';
	if(isset($brandAddress->fax)) echo '<tr><td>Fax</td><td>'. $brandAddress->fax .'</td></tr>';
	if(isset($brandAddress->email)) echo '<tr><td>Email</td><td>'. $brandAddress->email .'</td></tr>';
	echo '<tr><td>URL</td><td>'. $brandAddress->wwwURL .'</td></tr>';
	echo '</table>';
	echo '</div>';


}

function tallenna_uusi_hankintapaikka(){
	global $connection;
	$table_name = "hankintapaikka";
	$query = "INSERT INTO $table_name VALUES ";
	
	//ei vielä valmis
}

function tulosta_hankintapaikka($brandId) {

	//tarkastetaan onko tietokannassa vaihtoehtoista toimittajaa
	global $connection; // *gough*globaalien muutttujien käyttö on huonoa tyyliä*gough*
	$table_name = "hankintapaikka";
	$query = "SELECT * FROM $table_name WHERE brandNo=$brandId";
	$result = mysqli_query($connection, $query) or die(mysqli_error($connection));
	if (mysqli_num_rows($result) !== 0) {
		echo '<div style="float:left;">';
		echo '<table>';
		echo "<th colspan='2' class='text-center'>Hankintapaikka</th>";
		echo '<tr><td>Yritys</td><td>'. $brandAddress->name .'</td></tr>';
		echo '<tr><td>Osoite</td><td>'. $brandAddress->street . '<br>' . $brandAddress->zip . " " . strtoupper($brandAddress->city) .'</td></tr>';
		echo '<tr><td>Puh</td><td>'. $brandAddress->phone .'</td></tr>';
		if(isset($brandAddress->fax)) echo '<tr><td>Fax</td><td>'. $brandAddress->fax .'</td></tr>';
		if(isset($brandAddress->email)) echo '<tr><td>Email</td><td>'. $brandAddress->email .'</td></tr>';
		echo '<tr><td>URL</td><td>'. $brandAddress->wwwURL .'</td></tr>';
		echo '</table>';
		echo '<input class="nappi" type="button" value="Vaihda toimittajaa" onClick="avaa_Modal_toimittaja_yhteystiedot('.$brandId.')">';

		echo '</div>';
	}
	else {
		echo '<div style="float:left;">';
		echo '<p>Valitse hankintapaikka!</p>';
		echo '<input class="nappi" type="button" value="Vaihda hankintapaikka" onClick="avaa_Modal_toimittaja_yhteystiedot('.$brandId.')">';
		echo '</div>';
	}


}



if (isset($_POST['nimi'])) {
	tallenna_uusi_hankintapaikka();
}

tulosta_yhteystiedot($brandAddress, $brandName, $brandId);
tulosta_hankintapaikka($brandId);
?>
</div>

<script>
	//
	// Avataan modal, jossa voi täyttää uuden toimittajan yhteystiedot
	// tai valita jo olemassa olevista
	//
	function avaa_Modal_toimittaja_yhteystiedot(brandId){
		Modal.open( {
			content:  '\
				<div>\
				<h4>Anna uuden hankintapaikan tiedot tai valitse listasta.</h4>\
				<br>\
				<form action="" method="post">\
				<label><span>Toimittajat</span></label></label><select name="hankintapaikka"></select>\
				<br>\
				<input class="nappi" type="submit" name="submit" value="Valitse"> \
				<input type="hidden" name="brandId" value="'+brandId+'">\
				</form>\
				<hr>\
				<form action="" method="post">\
					\
					<label><span>Yritys</span></label>\
					<input name="nimi" type="text" pattern="[a-öA-Ö]{3,20}" placeholder="Yritys Oy" title="Vain aakkosia.">\
					<br><br>\
					<label><span>Katuosoite</span></label>\
					<input name="katuosoite" type="text" pattern="[a-öA-Ö]{3,20}" placeholder="Katu" title="Vain aakkosia">\
					<br><br>\
					<label><span>Postiumero</span></label>\
					<input name="postinumero" type="text" pattern="[0-9]{1,20}" placeholder="00000">\
					<br><br>\
					<label><span>Kaupunki</span></label>\
					<input name="kaupunki" type="text" pattern=".{1,50}" placeholder="KAUPUNKI">\
					<br><br>\
					<label><span>Maa</span></label>\
					<input name="maa" type="text" pattern=".{1,50}" placeholder="Maa">\
					<br><br>\
					<label><span>Puh</span></label>\
					<input name="puh" type="text" pattern=".{8,10}" placeholder="040 123 4567">\
					<br><br>\
					<label><span>Fax</span></label>\
					<input name="fax" type="text" pattern=".{1,50}" placeholder="01 234567">\
					<br><br>\
					<label><span>URL</span></label>\
					<input name="url" type="text" pattern=".{1,50}" placeholder="www.url.fi">\
					<br><br>\
					<label><span>Yhteyshenkilö</span></label>\
					<input name="email" type="text" pattern=".{1,50}" placeholder="Etunimi Sukunimi">\
					<br><br>\
					<label><span>Yhteyshenk. puh.</span></label>\
					<input name="email" type="text" pattern=".{1,50}" placeholder="040 123 4567">\
					<br><br>\
					<label><span>Yhteyshenk. email</span></label>\
					<input name="email" type="text" pattern=".{1,50}" placeholder="osoite@osoite.fi">\
					<br><br>\
					<label><span>Tilaustapa</span></label>\
					<input name="url" type="text" pattern=".{1,50}" placeholder="???">\
					<br><br>\
					<input class="nappi" type="submit" name="submit" value="Tallenna"> \
					<input type="hidden" name="brandId" value="'+brandId+'">\
				</form>\
				</div>\
				',
			draggable: true
		} );
	}
	
</script>
</body>
</html>






