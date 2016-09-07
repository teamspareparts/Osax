<!DOCTYPE html>
<html lang="fi">
<head>
	<meta charset="UTF-8">
	<link rel="stylesheet" href="css/styles.css">
	<title>Tilaukset</title>
</head>
<body>
<?php 	
require 'header.php';
?>
<div id=tilaukset>
	<h1 class="otsikko">Tilaukset</h1>
	<div id="painikkeet">
		<a href="yp_tilaushistoria.php"><span class="nappi">Tilaushistoria</span></a>
	</div>
	<br><br>
</div>

<div id="tilaukset">
	<div id="lista">
		<form action="yp_tilaukset.php" method="post">
			<fieldset class="lista_info">
				<p><span class="tilausnumero">Tilausnro.</span><span class="pvm">Päivämäärä</span><span class="tilaaja">Tilaaja</span><span class="sum">Summa</span>Käsitelty</p>
			</fieldset>

			<?php
				require 'tietokanta.php';
				require 'apufunktiot.php';
				
				if (!is_admin()) {
					header("Location:etusivu.php");
					exit();
				}


				$query = "	SELECT tilaus.id, tilaus.paivamaara, kayttaja.etunimi, kayttaja.sukunimi, 
								SUM(tilaus_tuote.kpl * (tilaus_tuote.pysyva_hinta * (1+tilaus_tuote.pysyva_alv))) AS summa
							FROM tilaus
							LEFT JOIN kayttaja
								ON kayttaja.id=tilaus.kayttaja_id
							LEFT JOIN tilaus_tuote
								ON tilaus_tuote.tilaus_id=tilaus.id
							WHERE tilaus.kasitelty = 0
							GROUP BY tilaus.id";
				$tilaukset = $db->query($query, [], FETCH_ALL, PDO::FETCH_OBJ);

				foreach ($tilaukset as $tilaus) {
					?>
					<fieldset>
						<a href="tilaus_info.php?id=<?= $tilaus->id?>"><span class="tilausnumero"><?= $tilaus->id?>
						</span><span class="pvm"><?= date("d.m.Y", strtotime($tilaus->paivamaara))?>
						</span><span class="tilaaja"><?= $tilaus->etunimi . " " . $tilaus->sukunimi?>
						</span><span class="sum"><?= format_euros($tilaus->summa)?>
						</span></a><input type="checkbox" name="ids[]" value="<?= $tilaus->id?>">
					</fieldset>
					<?php 
				}

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
	header("Location:yp_tilaukset.php");
	exit();
}

	function db_merkitse_tilaus($ids){
		$tbl_name="tilaus";				// Taulun nimi

		//Palvelimeen liittyminen
		$connection = mysqli_connect(DB_HOST, DB_USERNAME, DB_PASSWORD, DB_NAME) or die("Connection error:" . mysqli_connect_error());

		foreach ($ids as $id) {
			$query = "	UPDATE	$tbl_name
						SET		kasitelty = 1
						WHERE	id='$id'";
			mysqli_query($connection, $query) or die(mysqli_error($connection));
		}
		mysqli_close($connection);

		return;
	}
?>

</body>
</html>
