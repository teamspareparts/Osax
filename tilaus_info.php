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

				$connection = mysqli_connect(DB_HOST, DB_USERNAME, DB_PASSWORD, DB_NAME) or die("Connection error:" . mysqli_connect_error());


				$id = $_GET["id"];
				$query = "SELECT tilaus.id, tilaus.paivamaara, tilaus.kasitelty, kayttaja.etunimi, kayttaja.sukunimi, kayttaja.yritys, SUM(tilaus_tuote.kpl * tilaus_tuote.pysyva_hinta) AS summa, SUM(tilaus_tuote.kpl) AS kpl
							FROM tilaus
							LEFT JOIN kayttaja
								ON kayttaja.id=tilaus.kayttaja_id
							LEFT JOIN tilaus_tuote
								ON tilaus_tuote.tilaus_id=tilaus.id
							LEFT JOIN tuote
								ON tuote.id=tilaus_tuote.tuote_id
							WHERE tilaus.id = '$id'";
				$result = mysqli_query($connection, $query) or die(mysqli_error($connection));
				$row = mysqli_fetch_assoc($result);
				if ($row["kasitelty"] == 0) echo "<h4 style='color:red;'>Odottaa käsittelyä.</h4>";
				echo "<p>Tilausnro: " .$row["id"]. " Päivämäärä: " .date("d.m.Y", strtotime($row["paivamaara"])) . "</p>";
				echo "<p>Tilaaja: " .$row["etunimi"] . " " . $row["sukunimi"]. " Yritys: " .$row["yritys"]. "</p>";
				echo "<p>Tuotteet: " .$row["kpl"]. " Summa: " .$row["summa"] . "eur</p>";



				//tuotelista
				$products = get_products_in_tilaus($id);
				if (count($products) > 0) {
					merge_products_with_tecdoc($products);

					echo '<table>';
					echo '<tr><th>Tuote</th><th>Valmistaja</th><th>Tuotenumero</th><th>Hinta</th><th>tilattu kpl</th></tr>';
					foreach ($products as $product) {
						$article = $product->directArticle;
						echo '<tr>';
						echo "<td>$article->articleName</td>";
						echo "<td>$article->brandName</td>";
						echo "<td>$article->articleNo</td>";
						echo "<td>$product->pysyva_hinta</td>";
						echo "<td>$product->kpl</td>";
						echo '</tr>';
					}
					echo '</table>';
				} else {
					echo '<p>Ei tilaukseen liitettyjä tuotteita.</p>';
				}
				echo '</div>';



				//
				// Hakee tietokannasta kaikki tietyn tilauksen tuotteet
				//
				function get_products_in_tilaus($id) {
					global $connection;
					$query = "SELECT tilaus_tuote.tuote_id AS id, tilaus_tuote.pysyva_hinta, tilaus_tuote.kpl
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
						// TODO: Hae TecDocista kukin tuote ID:n perusteella ja palauta ne!
						return $products;
					}
					return [];
				}


			?>
</div>


</body>
</html>
