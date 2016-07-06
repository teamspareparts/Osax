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

	$user_id = $_GET["id"];
	$query = "
		SELECT tilaus.id, tilaus.paivamaara, tilaus.kasitelty, kayttaja.etunimi, kayttaja.sukunimi, kayttaja.yritys, kayttaja.sahkoposti, 
			SUM(tilaus_tuote.kpl * (tilaus_tuote.pysyva_hinta * (1+tilaus_tuote.pysyva_alv))) AS summa, 
			SUM(tilaus_tuote.kpl) AS kpl
		FROM tilaus
		LEFT JOIN kayttaja
			ON kayttaja.id=tilaus.kayttaja_id
		LEFT JOIN tilaus_tuote
			ON tilaus_tuote.tilaus_id=tilaus.id
		LEFT JOIN tuote
			ON tuote.id=tilaus_tuote.tuote_id
		WHERE tilaus.id = '$user_id'";
	$result = mysqli_query($connection, $query) or die(mysqli_error($connection));
	$row = mysqli_fetch_assoc($result);
	
	//Päästetään vain oikeat käyttäjät katsomaan tilaushistorioita
	//(Eli asiakas ei pääse muiden asiakkaiden tilaushistoria sivulle
	// vaihtamalla URLia esim. tilaus_info.php?id=4)
	if (is_admin() || ($row["sahkoposti"] == $_SESSION["email"])){
	
	if ($row["kasitelty"] == 0) echo "<h4 style='color:red;'>Odottaa käsittelyä.</h4>";

	$rahtimaksu = 15; if ( $row["summa"] > 200 ) { $rahtimaksu = 0; }
	?><!-- HTML -->
	<table class='tilaus_info'>
	<tr><td>Tilausnumero: <?= $row["id"]?></td><td>Päivämäärä: <?= date("d.m.Y", strtotime($row["paivamaara"]))?></td></tr>
	<tr><td>Tilaaja: <?= $row["etunimi"] . " " . $row["sukunimi"]?></td><td>Yritys: <?= $row["yritys"]?></td></tr>
	<tr><td>Tuotteet: <?= $row["kpl"]?></td><td>Summa: <?= format_euros($row["summa"])?> ( + rahtimaksu: <?= format_euros($rahtimaksu)?> = <?= format_euros($row["summa"]+$rahtimaksu)?>)</td></tr>
	</table>
	<br>
	<?php 
	//tuotelista
	$products = get_products_in_tilaus($user_id);
	if (count($products) > 0) {
		merge_products_with_tecdoc($products);
	
		?><!-- HTML --><table>
		<tr><th>Tuote</th><th>Valmistaja</th><th>Tuotenumero</th><th>Hinta</th><th>|ilman ALV</th><th>ALV-%</th><th>tilattu kpl</th></tr><?php 
		foreach ($products as $product) {
			$article = $product->directArticle;
			echo '<tr>';
			echo "<td>$article->articleName</td>";
			echo "<td>$article->brandName</td>";
			echo "<td>$article->articleNo</td>";
			echo "<td>" . format_euros( ($product->pysyva_hinta)*(1+($product->pysyva_alv)) ) . "</td>";
			echo "<td>" . format_euros( $product->pysyva_hinta ) . "</td>";
			echo "<td>" . round((float)$product->pysyva_alv * 100 ) . "%</td>";
			echo "<td>$product->kpl</td>";
			echo '</tr>';
		}
		echo '</table>';
	} else {
		echo '<p>Ei tilaukseen liitettyjä tuotteita.</p>';
	}
	echo '</div>';
	
	} else{
		header("Location:tilaushistoria.php");
		exit();
	}



//
// Hakee tietokannasta kaikki tietyn tilauksen tuotteet
//
function get_products_in_tilaus($id) {
	global $connection;
	$query = "
		SELECT tilaus_tuote.tuote_id AS id, tilaus_tuote.pysyva_hinta, tilaus_tuote.pysyva_alv, tilaus_tuote.kpl
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
		// TODO: Onko tämä tärkeää??
		// TODO: Hae TecDocista kukin tuote ID:n perusteella ja palauta ne!
		return $products;
	}
	return [];
}

	?>
</div>


</body>
</html>
