<!DOCTYPE html>
<html lang="fi">
<head>
	<meta charset="UTF-8">
	<link rel="stylesheet" href="css/styles.css">
	<title>Asiakkaat</title>
</head>
<body>
<?php 	include 'header.php';?>
<h1 class="otsikko">Asiakkaan Tilaushistoria</h1>
<br>

<?php 
	//haetaan kyseisen asiakkaan tiedot
	require 'tietokanta.php';
	$id = $_GET['id'];
	
	$connection = mysqli_connect(DB_HOST, DB_USERNAME, DB_PASSWORD, DB_NAME) or die("Connection error:" . mysqli_connect_error());
	
	$tbl_name="kayttaja";				// Taulun nimi
	$query = "SELECT * FROM $tbl_name WHERE id='$id'";
	$result = mysqli_query($connection, $query) or die(mysqli_error($connection));
	$row = mysqli_fetch_assoc($result);
	
	$enimi = $row["etunimi"];
	$snimi = $row["sukunimi"];
	$ynimi = $row["yritys"];
	

	echo '<p class="asiakas_info">Tilaaja: ' . $enimi . ' ' . $snimi . '<p>';
	echo '<p class="asiakas_info">Yritys: ' . $ynimi . '<p>';
?>

<div id="lista">
	<form>
	<fieldset class="lista_info">
		<p><span class="tilausnumero">Tilausnumero</span><span class="pvm">Päivämäärä</span><span class="tuotteet_kpl">Tuotteet (kpl)</span><span class="sum">Summa</span></p>
	</fieldset>
		<?php
		
			$tbl_name = "tilaus";
			$query = "SELECT * FROM $tbl_name WHERE kayttaja_id='$id'";
			
			$result = mysqli_query($connection, $query) or die(mysqli_error($connection));
			
			while($row = mysqli_fetch_assoc($result)){
	
				echo '<fieldset>';
				echo '<a href="#?id=' . $row["id"] . '"><span class="tilausnumero">' . $row["id"] .
				'</span><span class="pvm">' . date("d.m.Y", strtotime($row["paivamaara"])) .
				'</span><span class="tuotteet_kpl">' . 'tuotteet kpl' . '</span>' . 
				'</span><span class="sum">' . 'summa' . '</span><a>';
				echo '</fieldset>';
			}
			
			
			mysqli_close($connection);

		?>
		<br>
	</form>
</div>

</body>
</html>
