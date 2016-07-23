<!DOCTYPE html>
<html lang="fi">
<head>
	<meta charset="UTF-8">
	<link rel="stylesheet" href="css/styles.css">
	<title>Tilaushistoria</title>
</head>
<body>
<?php 	include 'header.php';
require 'apufunktiot.php'; ?>
<h1 class="otsikko">Asiakkaan Tilaushistoria</h1>
<br>
<div id="asiakas_tilaushistoria">
	<?php
		require 'tietokanta.php';
		
		if (is_admin()){
			//haetaan kyseisen asiakkaan tiedot
			$user_id = $_GET['id']; //TODO: korjaa/muuta. Jos admin haluaa tarkastella omia tilauksiaan, tämä kaatuu.
			
			$tbl_name="kayttaja";				// Taulun nimi
			$query = "SELECT * FROM $tbl_name WHERE id='$user_id'";
			$result = mysqli_query($connection, $query) or die(mysqli_error($connection));
			$row = mysqli_fetch_assoc($result);
			
			$enimi = $row["etunimi"];
			$snimi = $row["sukunimi"];
			$ynimi = $row["yritys"];
		
			echo '<p class="asiakas_info">Tilaaja: ' . $enimi . ' ' . $snimi . '<p>';
			echo '<p class="asiakas_info">Yritys: ' . $ynimi . '<p>';
		} else {
			$user_id = $_SESSION["id"];
		}
	?>
	
	<div id="lista">
		<form>
		<?php		
			$tbl_name = "tilaus";
			$query = "
				SELECT tilaus.id, tilaus.paivamaara, tilaus.kasitelty, 
					SUM(tilaus_tuote.kpl) AS kpl, 
					SUM( tilaus_tuote.kpl * ( (tilaus_tuote.pysyva_hinta * (1 + tilaus_tuote.pysyva_alv)) * (1 - tilaus_tuote.pysyva_alennus) ) )
						AS summa
				FROM $tbl_name 
				LEFT JOIN tilaus_tuote
					ON tilaus_tuote.tilaus_id = tilaus.id
				WHERE kayttaja_id='$user_id'
				GROUP BY tilaus.id;";
			
			$result = mysqli_query($connection, $query) or die(mysqli_error($connection));
			
			if (mysqli_num_rows($result) > 0) {?>
				<fieldset class="lista_info">
					<p><span class="tilausnumero">Tilausnumero</span><span class="pvm">Päivämäärä</span><span class="tuotteet_kpl">Tuotteet (kpl)</span><span class="sum">Summa</span>Käsitelty</p>
				</fieldset>
				<?php
				while($row = mysqli_fetch_assoc($result)){
					?>
					<fieldset><a href="tilaus_info.php?id=<?= $row["id"]?>"><span class="tilausnumero"><?= $row["id"]?>
						</span><span class="pvm"><?= date("d.m.Y", strtotime($row["paivamaara"]))?>
						</span><span class="tuotteet_kpl"><?= $row["kpl"]?></span>
						</span><span class="sum"><?= format_euros($row["summa"]) ?></span>
					<?php
					if ($row["kasitelty"] == 1) echo "OK";
					else echo "<span style='color:red'>EI</span>"?>
					</a></fieldset>
					<?php 
				}
			} else {
				echo 'Ei tehtyjä tilauksia.';
			}
			
			mysqli_close($connection);
	
			?>
			<br>
		</form>
	</div>
</div>
</body>
</html>
