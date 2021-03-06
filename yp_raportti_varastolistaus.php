<?php declare(strict_types=1);
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

$brands = hae_brandit($db);
$hankintapaikat = hae_hankintapaikat($db);

?>
<!DOCTYPE html>
<html lang="fi">
<head>
	<meta charset="UTF-8">
	<link rel="stylesheet" href="./css/styles.css">
	<link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
	<script src="https://code.jquery.com/jquery-3.1.1.min.js"></script>
	<title>Raportit</title>
</head>
<body>
<?php include("header.php");?>
<main class="main_body_container">

	<!-- Otsikko ja painikkeet -->
	<div class="otsikko_container">
		<section class="takaisin">
			<a class="nappi grey" href="yp_raportit.php"><i class="material-icons">navigate_before</i>Takaisin</a>
		</section>
		<section class="otsikko">
			<h1>Varastolistausraportti</h1>
		</section>
		<section class="napit">
		</section>
	</div>

	<div class="feedback"></div>

	<fieldset><legend>Raportin rajaukset</legend>
		<form action="yp_luo_varastolistausraportti.php" method="post" id="varastolistausraportti">

			<!-- Brändin valinta -->
			<label for="varastolistausraportti_brand">Brändi</label>
			<select name="brand" id="varastolistausraportti_brand">
				<option value="0" selected>-- Kaikki --</option>
				<?php foreach( (array)$brands as $brand ) : ?>
					<option value="<?=$brand->id?>"><?=$brand->nimi?></option>
				<?php endforeach;?>
			</select><br><br>

			<!-- Hankintapaikan valinta -->
			<label for="varastolistausraportti_hankintapaikka">Hankintapaikka</label>
			<select name="hankintapaikka" id="varastolistausraportti_hankintapaikka">
				<option value="0" selected>-- Kaikki --</option>
				<?php foreach((array)$hankintapaikat as $hp) : ?>
					<option value="<?=$hp->id?>"><?=$hp->id."-".$hp->nimi?></option>
				<?php endforeach;?>
			</select><br><br>

			<!-- Näytetäänkö raportissa myös myyntitiedot -->
			<label for="varastolistausraportti_myyntitiedot">Myyntitiedot</label>
			<input type="checkbox" name="myyntitiedot" id="varastolistausraportti_myyntitiedot">
			<br><br>

			<!-- Raportin järjestys -->
			<label for="varastolistausraportti_sort">Raportin järjestys</label>
			<select name="sort" id="varastolistausraportti_sort">
				<option value="sort_tuotekoodi" selected>Tuotekoodi</option>
				<option value="sort_hyllypaikka">Hyllypaikka</option>
			</select><br><br>

			<input name="luo_raportti" type="submit" value="Lataa raportti">

		</form>
	</fieldset>
</main>

<?php require 'footer.php'; ?>

<script>
    $(document).ready(function(){
        $("#varastolistausraportti").on("submit", function(e) {
            $(".feedback").append("<p class='success'>Odota kunnes raportti valmistuu!</p>").fadeOut(5000);
        });
    });
</script>
</body>
</html>

