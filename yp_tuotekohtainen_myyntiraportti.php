<?php
require '_start.php'; global $db, $user, $cart;
require 'tecdoc.php';

//Vain ylläpitäjälle
if ( !$user->isAdmin() ) {
	header("Location:etusivu.php");
	exit();
}
$feedback = "";

/**
 * Haetaan kaikki aktiiviset brändit
 * @param DByhteys $db
 * @return array|int|stdClass
 */
/**function hae_brandit( DByhteys $db ) {
	$sql = "SELECT DISTINCT id, nimi FROM brandi ORDER BY nimi";
	return $db->query($sql, [], FETCH_ALL);
}*/

//Vaihtoehtoinen funktio, joka toimii ilman brandit-taulua
function hae_brandit() {
	$brands = getAmBrands();
	foreach ($brands as $brand) {
		$brand->id = $brand->brandId;
		$brand->nimi = $brand->brandName;
	}
	usort($brands, function ($a, $b){return ($a->nimi > $b->nimi);});
	return $brands;
}

/**
 * Haetaan kaikki hankintapaikat
 * @param DByhteys $db
 * @return array|int|stdClass
 */
function hae_hankintapaikat( DByhteys $db ) {
	$sql = "SELECT DISTINCT nimi, id FROM hankintapaikka ORDER BY id";
	return $db->query($sql, [], FETCH_ALL);
}

/**
 * Haetaan asiakkaat
 * @param DByhteys $db
 * @return array|int|stdClass
 */
function hae_asiakkaat( DByhteys $db ) {
	$sql = "SELECT DISTINCT id, CONCAT(etunimi, ' ', sukunimi) AS nimi
			FROM kayttaja
			WHERE aktiivinen = 1
			ORDER BY id";
	return $db->query($sql, [], FETCH_ALL);
}

$brands = hae_brandit();
$hankintapaikat = hae_hankintapaikat($db);
$asiakkaat = hae_asiakkaat($db);

?>
<!DOCTYPE html>
<html lang="fi">
<head>
	<meta charset="UTF-8">
	<link rel="stylesheet" href="css/styles.css">
	<link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
	<link rel="stylesheet" href="https://code.jquery.com/ui/1.11.4/themes/smoothness/jquery-ui.css">
	<script src="https://code.jquery.com/jquery-3.1.1.min.js"></script>
	<script src="https://code.jquery.com/ui/1.11.4/jquery-ui.min.js"></script>
	<title>Raportit</title>
</head>
<body>
<?php include("header.php");?>
<main class="main_body_container">
	<section>
		<h1 class="otsikko">Tuotekohtainen myyntiraportti</h1>
		<div id="painikkeet">
			<a class="nappi grey" href="yp_raportit.php">Takaisin</a>
		</div>
	</section>

	<div class="feedback success" hidden>Odota kunnes raportti valmistuu!</div>
	<fieldset><legend>Raportin rajaukset</legend>
		<form action="yp_luo_tuotekohtainen_myyntiraportti.php" method="post" id="tuotekohtainen_myyntiraportti">

			<!-- Päivämäärän valinta -->
			<label for="pvm_from">From: </label>
			<input type="text" name="pvm_from" id="pvm_from" class="datepicker" required>
			<br><br>
			<label for="pvm_to">To: </label>
			<input type="text" name="pvm_to" id="pvm_to" class="datepicker" value="<?=date("Y-m-d")?>" required>
			<br><br>

			<!-- Brändin valinta -->
			<label for="myyntiraportti_tuote_brand">Brändi</label>
			<select name="brand" id="myyntiraportti_tuote_brand">
				<option value="0" selected>-- Kaikki --</option>
				<?php foreach( (array)$brands as $brand ) : ?>
					<option value="<?=$brand->id?>"><?=$brand->nimi?></option>
				<?php endforeach;?>
			</select><br><br>

			<!-- Hankintapaikan valinta -->
			<label for="myyntiraportti_tuote_hankintapaikka">Hankintapaikka</label>
			<select name="hankintapaikka" id="myyntiraportti_tuote_hankintapaikka">
				<option value="0" selected>-- Kaikki --</option>
				<?php foreach((array)$hankintapaikat as $hp) : ?>
					<option value="<?=$hp->id?>"><?=$hp->id."-".$hp->nimi?></option>
				<?php endforeach;?>
			</select><br><br>

			<!-- Asiakkaan valinta -->
			<label for="myyntiraportti_tuote_asiakas">Asiakas</label>
			<select name="asiakas" id="myyntiraportti_tuote_asiakas">
				<option value="0" selected>-- Kaikki --</option>
				<?php foreach((array)$asiakkaat as $asiakas) : ?>
					<option value="<?=$asiakas->id?>"><?=$asiakas->id."-".$asiakas->nimi?></option>
				<?php endforeach;?>
			</select><br><br>

			<!-- Raportin järjestys -->
			<label for="myyntiraportti_tuote_sort">Raportin järjestys</label>
			<select name="sort" id="myyntiraportti_tuote_sort">
				<option value="sort_tuotekoodi" selected>Tuotekoodi</option>
				<option value="sort_brandi">brändi</option>
				<option value="sort_myyty_kpl">Myynti kpl</option>
				<option value="sort_myyty_summa">Myynti €</option>
			</select><br><br>

			<input name="luo_raportti" type="submit" value="Lataa raportti">

		</form>
	</fieldset>


</main>
<script>
    $(document).ready(function(){
        $("#tuotekohtainen_myyntiraportti").on("submit", function(e) {
            $(".feedback").show().fadeOut(6000);

        });

	    $('.datepicker').datepicker({
            dateFormat: 'yy-mm-dd',
            maxDate: '+0d',
        })
		.keydown(function(e){
            e.preventDefault();
        });
    });
</script>
</body>
</html>
