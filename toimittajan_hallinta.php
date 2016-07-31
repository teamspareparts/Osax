
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
	header("Location:tuotehaku.php");
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
	<a href="#"><span class="nappi">Valikoima</span></a>
</div>

<br><br>
<div style="text-align: center; display:inline-block; margin-left: 5%;">
<?php 


function yhteystiedot($brandAddress, $brandName, $brandId){
	//tarkastetaan onko tietokannassa vaihtoehtoista toimittajaa
	global $connection;
	$table_name = "vaihtoehtoinen_toimittaja";
	$query = "SELECT * FROM $table_name WHERE brandNo=$brandId";
	
	
	echo '<table>';
	echo "<th colspan='2' class='text-center'>Yhteystiedot</th>";
	echo '<tr><td>Yritys</td><td>'. $brandAddress->name .'</td></tr>';
	echo '<tr><td>Osoite</td><td>'. $brandAddress->street . '<br>' . $brandAddress->zip . " " . strtoupper($brandAddress->city) .'</td></tr>';
	echo '<tr><td>Puh</td><td>'. $brandAddress->phone .'</td></tr>';
	if(isset($brandAddress->fax)) echo '<tr><td>Fax</td><td>'. $brandAddress->fax .'</td></tr>';
	if(isset($brandAddress->email)) echo '<tr><td>Email</td><td>'. $brandAddress->email .'</td></tr>';
	echo '<tr><td>URL</td><td>'. $brandAddress->wwwURL .'</td></tr>';
	echo '</table>';
	echo '<input class="nappi" type="button" value="Vaihda toimittajaa" onClick="avaa_Modal_toimittaja_yhteystiedot('.$brandId.', \''.$brandName.'\')">';
}

function tallenna_uusi_toimittaja(){
	global $connection;
	$table_name = "vaihtoehtoinen_toimittaja";
	$query = "INSERT INTO $table_name .......";
	
	//ei vielä valmis
	
	
}



if (isset($_POST['nimi'])) {
	tallenna_uusi_toimittaja();
}

yhteystiedot($brandAddress, $brandName, $brandId);
?>
</div>

<script>
	//
	// Avataan modal, jossa voi täyttää uuden toimittajan yhteystiedot
	//
	function avaa_Modal_toimittaja_yhteystiedot(brandId, brandName){
		Modal.open( {
			content:  '\
				<div id="toimittaja_lomake">\
				<form action="" method="post">\
					<h4>Anna uuden toimittajan tiedot.</h4>\
					<br> \
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
					<label><span>Puh</span></label>\
					<input name="puh" type="text" pattern=".{8,10}" placeholder="040 123 4567">\
					<br><br>\
					<label><span>Fax</span></label>\
					<input name="fax" type="text" pattern=".{1,50}" placeholder="01 234567">\
					<br><br>\
					<label><span>Email</span></label>\
					<input name="email" type="text" pattern=".{1,50}" placeholder="osoite@osoite.fi">\
					<br><br>\
					<label><span>URL</span></label>\
					<input name="url" type="text" pattern=".{1,50}" placeholder="www.url.fi">\
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






