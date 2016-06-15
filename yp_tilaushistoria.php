<!DOCTYPE html>
<html lang="fi">
<head>
	<meta charset="UTF-8">
	<link rel="stylesheet" href="css/styles.css">
	<title>Tilaukset</title>
</head>
<body>
<?php 	include 'header.php';?>
<h1 class="otsikko">Tilaushistoria</h1>
<br><br>


<div id="tilaukset">
	<div id="lista">

		<form>
		<fieldset class="lista_info">
			<p><span class="tilausnumero">Tilausnro.</span><span class="pvm">Päivämäärä</span><span class="tilaaja">Tilaaja</span><span class="yritys">Yritys</span><span class="sum">Summa</span>Käsitelty</p>
		</fieldset>

			<?php
			require 'tietokanta.php';
			if(!is_admin()){
				header("Location:tuotehaku.php");
				exit();
			}

			$connection = mysqli_connect(DB_HOST, DB_USERNAME, DB_PASSWORD, DB_NAME) or die("Connection error:" . mysqli_connect_error());

			$query = "
				SELECT tilaus.id, tilaus.paivamaara, tilaus.kasitelty, kayttaja.etunimi, kayttaja.sukunimi, kayttaja.yritys, SUM(tilaus_tuote.kpl * tilaus_tuote.pysyva_hinta) AS summa 
				FROM tilaus 
				LEFT JOIN kayttaja 
					ON kayttaja.id=tilaus.kayttaja_id
				LEFT JOIN tilaus_tuote
					ON tilaus_tuote.tilaus_id=tilaus.id
				GROUP BY tilaus.id
				ORDER BY tilaus.id DESC";
			$result = mysqli_query($connection, $query) or die(mysqli_error($connection));

			while($row = mysqli_fetch_assoc($result)){
				echo '<fieldset>';
				echo '<a href="tilaus_info.php?id=' . $row["id"] . '"><span class="tilausnumero">' . $row["id"] .
					'</span><span class="pvm">' . date("d.m.Y", strtotime($row["paivamaara"])) .
					'</span><span class="tilaaja">' . $row["etunimi"] . ' ' . $row["sukunimi"] .
					'</span><span class="yritys">' . $row["yritys"] .
					'</span><span class="sum">' . $row["summa"] . "eur" .
					'</span>';
				if ($row["kasitelty"] == 1) {
					echo 'OK';
				} else {
					echo '<span style="color:red">EI</span>';
				}
				echo '</a></fieldset>';
			}
			


			mysqli_close($connection);

			?>
		</form>
	</div>
</div>
</body>
</html>
