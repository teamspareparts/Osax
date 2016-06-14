<!DOCTYPE html>
<html lang="fi">
<head>
	<meta charset="UTF-8">
	<link rel="stylesheet" href="css/styles.css">
	<title>Asiakkaat</title>
</head>
<body>
<?php 	include 'header.php';?>
<div id=asiakas>
	<h1 class="otsikko">Asiakkaat</h1>
	<div id="painikkeet">
		<a href="yp_lisaa_asiakas.php"><span class="nappi">Lisää uusi asiakas</span></a>
	</div>
	<br><br><br>


	<div id="lista">

		<form action="yp_asiakkaat.php" method="post">
		<fieldset class="lista_info">
			<p><span class="nimi">Nimi</span><span class="puhelin">Puhelin</span><span class="yritys">Yritys</span><span class="sposti">Sähköposti</span><span>Poista</span></p>
		</fieldset>

			<?php
				if (!is_admin()) {
					header("Location:login.php");
					exit();
				}
				require 'tietokanta.php';

				$tbl_name="kayttaja";				// Taulun nimi

				$connection = mysqli_connect(DB_HOST, DB_USERNAME, DB_PASSWORD, DB_NAME) or die("Connection error:" . mysqli_connect_error());

				$query = "SELECT * FROM $tbl_name";
				$result = mysqli_query($connection, $query);


				//listataan kaikki tietokannasta löytyvät asiakkaat ja luodaan
				//niihin linkit, jotka vievät asiakkaan tilaushistoriaan.
				//Linkin mukana välitetään asiakkaan tietokanta id.
				while($row = mysqli_fetch_assoc($result)){
					if ($row["yllapitaja"] == 0 && $row["aktiivinen"] == 1){	//listataan vain asiakkaat
						echo '<fieldset>';
						echo '<a href="tilaushistoria.php?id=' . $row["id"] . '"><span class="nimi">' . $row["etunimi"] . " " . $row["sukunimi"] .
							'</span><span class="puhelin">' . $row["puhelin"] .
							'</span><span class="yritys">' . $row["yritys"] .
							'</span><span class="sposti">' . $row["sahkoposti"] . '</span><a>';
						echo '<input type="checkbox" name="ids[]" value="' . $row["id"] . '"><br>';
						echo '</fieldset>';
					}
				}
				mysqli_close($connection);

			?>
			<br>
			<div id=submit>
				<input type="submit" value="Poista valitut asiakkaat">
			</div>
		</form>
	</div>

</div>
	<?php
		if (isset($_POST['ids'])){
			db_poista_asiakas($_POST['ids']);
			
			header("Location:yp_asiakkaat.php");
			exit;
		}



		function db_poista_asiakas($ids){
			$tbl_name="kayttaja";				// Taulun nimi


			//Palvelimeen liittyminen
			$connection = mysqli_connect(DB_HOST, DB_USERNAME, DB_PASSWORD, DB_NAME) or die("Connection error:" . mysqli_connect_error());

			foreach ($ids as $asiakas_id) {
				$query = "UPDATE $tbl_name
							SET aktiivinen=0
							WHERE id='$asiakas_id'";
				$result = mysqli_query($connection, $query);
			}
			mysqli_close($connection);

			return;
		}
	?>


</body>
</html>
