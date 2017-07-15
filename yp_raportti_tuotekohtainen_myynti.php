<?php
require '_start.php'; global $db, $user, $cart;

//Vain ylläpitäjälle
if ( !$user->isAdmin() ) {
	header("Location:etusivu.php");
	exit();
}

/**
 * Haetaan kaikki brändit
 * @param DByhteys $db
 * @return array|int|stdClass
 */
function hae_brandit( DByhteys $db ) {
	$sql = "SELECT DISTINCT id, nimi FROM brandi ORDER BY nimi";
	return $db->query($sql, [], FETCH_ALL);
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
 * Haetaan asiakasyritykset
 * @param DByhteys $db
 * @return array|int|stdClass
 */
function hae_yritykset( DByhteys $db ) {
	$sql = "SELECT DISTINCT id, nimi
			FROM yritys
			WHERE aktiivinen = 1
			ORDER BY id";
	return $db->query($sql, [], FETCH_ALL);
}

$brands = hae_brandit($db);
$hankintapaikat = hae_hankintapaikat($db);
$yritykset = hae_yritykset($db);

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
	<script src="./js/datepicker-fi.js"></script>
	<title>Raportit</title>
</head>
<body>
<?php include("header.php");?>
<main class="main_body_container">

	<!-- Otsikko ja painikkeet -->
	<div class="otsikko_container">
		<section class="takaisin">
			<a class="nappi grey" href="yp_raportit.php">Takaisin</a>
		</section>
		<section class="otsikko">
			<h1>Tuotekohtainen myyntiraportti</h1>
		</section>
		<section class="napit">
		</section>
	</div>

	<div class="feedback"></div>

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
			<label for="myyntiraportti_tuote_yritys">Yritykset</label>
			<select name="yritys" id="myyntiraportti_tuote_yritys">
				<option value="0" selected>-- Kaikki --</option>
				<?php foreach((array)$yritykset as $yritys) : ?>
					<option value="<?=$yritys->id?>"><?=$yritys->id."-".$yritys->nimi?></option>
				<?php endforeach;?>
			</select><br><br>

			<!-- Kaikki vai pelkästään myydyt tuotteet -->
			<label for="myyntiraportti_tuote_vain_myydyt">Vain myydyt tuotteet</label>
			<input type="checkbox" name="vain_myydyt" id="myyntiraportti_tuote_vain_myydyt" checked><br><br>

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

<?php require 'footer.php'; ?>

<script>
    $(document).ready(function(){
        $("#tuotekohtainen_myyntiraportti").on("submit", function(e) {
            $(".feedback").append("<p class='success'>Odota kunnes raportti valmistuu!</p>").fadeOut(5000);
        });
        $('.datepicker').datepicker({
            dateFormat: 'yy-mm-dd',
            maxDate: '+0d',
        }).keydown(function(e){
            e.preventDefault();
        });
    });
</script>
</body>
</html>

