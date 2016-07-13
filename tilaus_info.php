<!DOCTYPE html>
<html lang="fi">
<head>
	<meta charset="UTF-8">
	<link rel="stylesheet" href="css/styles.css">
	<title>Tilaushistoria</title>
</head>
<body>
<?php 	include 'header.php';
require 'tietokanta.php';
require 'tecdoc.php';
require 'apufunktiot.php';

function alku_toimenpiteet_ja_tarkistukset () {
	global $tilaus_tiedot;
	
	if ( is_admin() || ($tilaus_tiedot["sahkoposti"] == $_SESSION["email"]) ) {
		return TRUE;
	} else {
		header("Location:tilaushistoria.php");
		exit();
	}
}

function hae_tilauksen_tiedot () {
	global $connection;
	global $tilaus_id;
	$query = "
		SELECT tilaus.id, tilaus.paivamaara, tilaus.kasitelty, tilaus.pysyva_rahtimaksu,
			kayttaja.etunimi, kayttaja.sukunimi, kayttaja.yritys, kayttaja.sahkoposti,
			SUM( tilaus_tuote.kpl * ( (tilaus_tuote.pysyva_hinta * (1 + tilaus_tuote.pysyva_alv)) * (1 - tilaus_tuote.pysyva_alennus) ) )
				AS summa,
			SUM(tilaus_tuote.kpl) AS kpl
		FROM tilaus
		LEFT JOIN kayttaja
			ON kayttaja.id=tilaus.kayttaja_id
		LEFT JOIN tilaus_tuote
			ON tilaus_tuote.tilaus_id=tilaus.id
		LEFT JOIN tuote
			ON tuote.id=tilaus_tuote.tuote_id
		WHERE tilaus.id = '$tilaus_id'";
	$result = mysqli_query($connection, $query) or die(mysqli_error($connection));
	
	return mysqli_fetch_assoc($result);
}

function get_products_in_tilaus($id) {
	global $connection;
	$query = "
		SELECT tilaus_tuote.tuote_id AS id, tilaus_tuote.pysyva_hinta, tilaus_tuote.pysyva_alv, tilaus_tuote.pysyva_alennus, tilaus_tuote.kpl,
			( (tilaus_tuote.pysyva_hinta * (1 + tilaus_tuote.pysyva_alv)) * (1 - tilaus_tuote.pysyva_alennus) ) AS maksettu_hinta
		FROM tilaus
		LEFT JOIN tilaus_tuote
			ON tilaus_tuote.tilaus_id=tilaus.id
		WHERE tilaus.id = '$id'";
	$result = mysqli_query($connection, $query);
	if ($result) {
		$products = [];
		while ($row = mysqli_fetch_object($result)) {
			array_push($products, $row);
		}
		return $products;
	}
	return [];
}

function tulosta_rahtimaksu_tuotelistaan() {
	global $rahtimaksu;
	if ( $rahtimaksu === 0 ) { $alennus = "Ilmainen toimitus"; } else { $alennus = "---"; }
	?><!-- HTML -->
	<tr style="background-color:#cecece;">
		<td>Rahtimaksu</td>
		<td>Posti / Itella</td>
		<td>---</td>
		<td><?= format_euros($rahtimaksu)?></td>
		<td>---</td>
		<td>0 %</td>
		<td><?= $alennus?></td>
		<td>1</td>
	</tr>
	<?php
}

function tulosta_alennus_tuotelistaan( $alennus ) {
	if ( $alennus !== 0 ) {
		$alennus = round( (float)$alennus * 100 ) . " %";
	} else {
		$alennus = "---";
	}
	return $alennus;
}

function laske_rahtimaksu() {
	global $tilaus_tiedot; //tarvitaan pari muuttujaa asiakaskohtaiseen rahtimaksuun liittyen
	
	$rahtimaksu = 15;
	$ilmaisen_toimituksen_raja = 200; // Default summa, jonka jälkeen saa ilmaisen toimituksen
	
	if ( $tilaus_tiedot["rahtimaksu"] !== 0 ) {//Asiakkaalla on asetettu rahtimaksu
		$rahtimaksu = $tilaus_tiedot["rahtimaksu"]; }
	
	if ( $tilaus_tiedot["ilmainen_toimitus_summa_raja"] !== 0 ) {	//Asiakkaalla on asetettu yksilöllinen ilmaisen toimituksen raja.
		$ilmaisen_toimituksen_raja = $tilaus_tiedot["ilmainen_toimitus_summa_raja"]; }
		
	if ( $tilaus_tiedot["summa"] > $ilmaisen_toimituksen_raja ) { //Onko tilaus-summa rajan yli?
		$rahtimaksu = 0; }
	
	return $rahtimaksu;
}

$tilaus_id = $_GET["id"];
$tilaus_tiedot = hae_tilauksen_tiedot();
if ( !($tilaus_tiedot["sahkoposti"] == $_SESSION["email"]) ) { 
	if ( !is_admin() ) {
		header("Location:tilaushistoria.php");
		exit(); }
}
// $rahtimaksu = laske_rahtimaksu();
$products = get_products_in_tilaus($tilaus_id);
if (count($products) > 0) {
	merge_products_with_tecdoc($products);
} else {
	echo '<p>Ei tilaukseen liitettyjä tuotteita.</p>';
}
?>

<h1 class="otsikko">Tilaus Info</h1>
<div class="tulokset">
	<?php if ($tilaus_tiedot["kasitelty"] == 0) echo "<h4 style='color:red;'>Odottaa käsittelyä.</h4>";
	else echo "<h4 style='color:green;'>Käsitelty ja toimitettu.</h4>";?>
	<!-- HTML -->
	<table class='tilaus_info'>
		<tr><td>Tilausnumero: <?= $tilaus_tiedot["id"]?></td>
			<td>Päivämäärä: <?= date("d.m.Y", strtotime($tilaus_tiedot["paivamaara"]))?></td></tr>
		<tr><td>Tilaaja: <?= $tilaus_tiedot["etunimi"] . " " . $tilaus_tiedot["sukunimi"]?></td>
			<td>Yritys: <?= $tilaus_tiedot["yritys"]?></td></tr>
		<tr><td>Tuotteet: <?= $tilaus_tiedot["kpl"]?></td>
			<td>Summa: <?= format_euros( $tilaus_tiedot["summa"] + $tilaus_tiedot["pysyva_rahtimaksu"])?> ( ml. rahtimaksu )</td></tr>
	</table>
	<br>
	<table>
		<tr><th>Tuotenumero</th><th>Tuote</th><th>Valmistaja</th><th>Hinta (yhteensä)</th><th>Kpl-hinta</th><th>ALV-%</th><th>Alennus</th><th>Kpl</th></tr>
		<?php 
		foreach ($products as $product) {
			$article = $product->directArticle;
			echo '<tr>';
			echo "<td>$article->articleNo</td>";
			echo "<td>$article->articleName</td>";
			echo "<td>$article->brandName</td>";
			echo "<td>" . format_euros( $product->maksettu_hinta * $product->kpl ) . "</td>";
			echo "<td>" . format_euros( $product->maksettu_hinta ) . "</td>";
			echo "<td>" . round( (float)$product->pysyva_alv * 100 ) . " %</td>";
			echo "<td>" . tulosta_alennus_tuotelistaan($product->pysyva_alennus) . "</td>";
			echo "<td>$product->kpl</td>";
			echo '</tr>';
		}?>
		
		<tr style="background-color:#cecece;">
			<td>Rahtimaksu</td>
			<td>Posti / Itella</td>
			<td>---</td>
			<td><?= format_euros($tilaus_tiedot["pysyva_rahtimaksu"])?></td>
			<td>---</td>
			<td>0 %</td>
			<td><?php if ( $tilaus_tiedot["pysyva_rahtimaksu"] === 0 ) { echo "Ilmainen toimitus"; } else { echo "---"; }?></td>
			<td>1</td>
		</tr>
		?><!-- HTML -->
	</table>
</div>

</body>
</html>
