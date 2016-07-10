<!DOCTYPE html>
<html lang="fi">
<head>
	<meta charset="UTF-8">
	<link rel="stylesheet" href="css/styles.css">
	<title>Tilaushistoria</title>
</head>
<body>
<?php 	include 'header.php';?>
<div id=tilaukset>
	<h1 class="otsikko">Tilaus Info</h1>
	<br>
</div>
<div class="tulokset">
	<?php
	require 'tietokanta.php';
	require 'tecdoc.php';
	require 'apufunktiot.php';

	$tilaus_id = $_GET["id"];
	$query = "
		SELECT tilaus.id, tilaus.paivamaara, tilaus.kasitelty, kayttaja.etunimi, kayttaja.sukunimi, kayttaja.yritys, kayttaja.sahkoposti, 
			kayttaja.rahtimaksu, kayttaja.ilmainen_toimitus_summa_raja,
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
	$row = mysqli_fetch_assoc($result);
	
	//Päästetään vain oikeat käyttäjät katsomaan tilaushistorioita
	//(Eli asiakas ei pääse muiden asiakkaiden tilaushistoria sivulle
	// vaihtamalla URLia esim. tilaus_info.php?id=4)
	if ( is_admin() || ($row["sahkoposti"] == $_SESSION["email"]) ) {
	
		if ($row["kasitelty"] == 0) echo "<h4 style='color:red;'>Odottaa käsittelyä.</h4>";
	
		$rahtimaksu = laske_rahtimaksu();
		
		?><!-- HTML -->
		<table class='tilaus_info'>
		<tr><td>Tilausnumero: <?= $row["id"]?></td><td>Päivämäärä: <?= date("d.m.Y", strtotime($row["paivamaara"]))?></td></tr>
		<tr><td>Tilaaja: <?= $row["etunimi"] . " " . $row["sukunimi"]?></td><td>Yritys: <?= $row["yritys"]?></td></tr>
		<tr><td>Tuotteet: <?= $row["kpl"]?></td><td>Summa: <?= format_euros($row["summa"])?> ( ml. rahtimaksu: <?= format_euros($rahtimaksu)?> )</td></tr>
		</table>
		<br>
		<?php 
		//tuotelista
		$products = get_products_in_tilaus($tilaus_id);
		if (count($products) > 0) {
			merge_products_with_tecdoc($products);
		
			?><!-- HTML -->
			<table>
				<tr><th>Tuote</th><th>Valmistaja</th><th>Tuotenumero</th><th>Hinta</th><th>|ilman ALV</th><th>ALV-%</th><th>Alennus</th><th>tilattu kpl</th></tr><?php 
				foreach ($products as $product) {
					$article = $product->directArticle;
					echo '<tr>';
					echo "<td>$article->articleName</td>";
					echo "<td>$article->brandName</td>";
					echo "<td>$article->articleNo</td>";
					echo "<td>" . format_euros( $product->maksettu_hinta ) . "</td>";
					echo "<td>" . format_euros( $product->pysyva_hinta ) . "</td>";
					echo "<td>" . round( (float)$product->pysyva_alv * 100 ) . " %</td>";
// 					echo "<td>" . round( (float)$product->pysyva_alennus * 100 ) . " %</td>";
					echo "<td>" . tulosta_alennus_tuotelistaan($product->pysyva_alennus) . " %</td>";
					echo "<td>$product->kpl</td>";
					echo '</tr>';
				}
				tulosta_rahtimaksu_tuotelistaan()?><!-- HTML -->
			</table><?php 
		} else {
			echo '<p>Ei tilaukseen liitettyjä tuotteita.</p>';
		}
		echo '</div>';
	
	} else{
		header("Location:tilaushistoria.php");
		exit();
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
	if ( $alennus === 0 ) {
		$alennus = round( (float)$alennus * 100 );
	} else {
		$alennus = "---";
	}
	return $alennus;
}

function laske_rahtimaksu() {
	global $row; //tarvitaan pari muuttujaa asiakaskohtaiseen rahtimaksuun liittyen
	
	$rahtimaksu = 15;
	$ilmaisen_toimituksen_raja = 200; // Default summa, jonka jälkeen saa ilmaisen toimituksen
	
	if ( $row["rahtimaksu"] !== 0 ) {//Asiakkaalla on asetettu rahtimaksu
		$rahtimaksu = $row["rahtimaksu"]; }
	
	if ( $row["ilmainen_toimitus_summa_raja"] !== 0 ) {	//Asiakkaalla on asetettu yksilöllinen ilmaisen toimituksen raja.
		$ilmaisen_toimituksen_raja = $row["ilmainen_toimitus_summa_raja"]; }
		
	if ( $row["summa"] > $ilmaisen_toimituksen_raja ) { //Onko tilaus-summa rajan yli?
		$rahtimaksu = 0; }
	
	return $rahtimaksu;
}

//
// Hakee tietokannasta kaikki tietyn tilauksen tuotteet
//
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

	?>
</div>


</body>
</html>
