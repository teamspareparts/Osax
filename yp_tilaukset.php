<!DOCTYPE html>
<html lang="fi">
<head>
	<meta charset="UTF-8">
	<link rel="stylesheet" href="css/styles.css">
	<title>Tilaukset</title>
</head>
<body>
<?php 	include 'header.php';?>
<div id=tilaukset>
	<h1 class="otsikko">Tilaukset</h1>
	<div id="painikkeet">
		<a href="#"><span class="tilaushistoria_painike">Tilaushistoria</span></a>
	</div>
	<br><br><br>
</div>

<div id="tilaukset">
	<div id="lista">

		<form action="yp_tilaukset.php" method="post">
		<fieldset class="lista_info">
			<p><span class="tilaukset_tilausnumero">Tilausnumero</span><span class="tilaukset_pvm">Päivämäärä</span><span class="tilaukset_tilaaja">Tilaaja</span><span class="tilaukset_yritys">Yritys</span><span class="tilaukset_sum">Summa</span><span>Käsitelty</span></p>
		</fieldset>

			<?php
				require 'tietokanta.php';

				$connection = mysqli_connect(DB_HOST, DB_USERNAME, DB_PASSWORD, DB_NAME) or die("Connection error:" . mysqli_connect_error());

				$query = "SELECT tilaus.id, tilaus.paivamaara, kayttaja.etunimi, kayttaja.sukunimi, kayttaja.yritys FROM tilaus INNER JOIN kayttaja ON kayttaja.id=tilaus.kayttaja_id AND tilaus.kasitelty = 0";
				$result = mysqli_query($connection, $query);

				
				while($row = mysqli_fetch_assoc($result)){
					echo '<fieldset>';
					echo '<a href="#?id=' . $row["id"] . '"><span class="tilaukset_tilausnumero">' . $row["id"] .
						'</span><span class="tilaukset_pvm">' . date("d.m.Y", strtotime($row["paivamaara"])) .
						'</span><span class="tilaukset_tilaaja">' . $row["etunimi"] . ' ' . $row["sukunimi"] .
						'</span><span class="tilaukset_yritys">' . $row["yritys"] .
						'</span><span class="tilaukset_sum">' . "summa" .
						'</span><a>';
					echo '<input type="checkbox" name="ids[]" value="' . $row["id"] . '"><br>';
					echo '</fieldset>';
				}
				


				mysqli_close($connection);

			?>
			<br>
			<div id=submit>
				<input type="submit" value="Merkitse käsitellyksi">
			</div>
		</form>
	</div>
</div>


	<?php
		if (isset($_POST['ids'])){
			db_merkitse_tilaus($_POST['ids']);
		}



		function db_merkitse_tilaus($ids){
			$tbl_name="tilaus";				// Taulun nimi


			//Palvelimeen liittyminen
			$connection = mysqli_connect(DB_HOST, DB_USERNAME, DB_PASSWORD, DB_NAME) or die("Connection error:" . mysqli_connect_error());

			foreach ($ids as $id) {
				$query = "UPDATE $tbl_name
				SET kasitelty = 1
				WHERE id='$id'";
				mysqli_query($connection, $query) or die(mysqli_error($connection));
			}
			mysqli_close($connection);

			header("Location:yp_tilaukset.php");
			exit;
		}
	?>


</body>
</html>
