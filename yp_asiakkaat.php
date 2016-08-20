<!DOCTYPE html>
<html lang="fi">
<head>
	<meta charset="UTF-8">
	<link rel="stylesheet" href="css/styles.css">
	<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.0/jquery.min.js"></script>
	<title>Asiakkaat</title>
</head>
<body>
<?php
include 'header.php';
require 'tietokanta.php';
$yritys_id = isset($_GET['yritys_id']) ? $_GET['yritys_id'] : null;
if (!is_admin() || !$yritys_id) {
	header("Location:etusivu.php");
	exit();
}
//Haetaan yrityksen tiedot
global $db;
$query = "SELECT * FROM yritys WHERE id=?;";
$yritys = $db->query($query, [$yritys_id], FETCH_ALL, PDO::FETCH_OBJ);
if (count($yritys) != 1) {	//Varmistetaan että yritys löytyi
	header("Location:yp_yritykset.php");
	exit();
}
$yritys = $yritys[0];


//poistetaanko käyttäjä
if (isset($_POST['ids'])){
	db_poista_asiakas($_POST['ids']);
}
?>
<div id=asiakas>
	<h1 class="otsikko"><?=$yritys->nimi;?></h1>
	<div id="painikkeet">
		<a href="yp_lisaa_asiakas.php?yritys_id=<?=$_GET['yritys_id'] ?>"><span class="nappi">Lisää uusi asiakas</span></a>
	</div>
	<table style="margin-left: 5%; white-space: nowrap;">
		<tr>
			<td style="padding-right: 40px;"><?=$yritys->y_tunnus?><br><?=$yritys->puhelin?><br><?=$yritys->sahkoposti?></td>
			<td><?=$yritys->katuosoite?><br><?=$yritys->postinumero?> <?=$yritys->postitoimipaikka?><br><?=$yritys->maa?></td>

		</tr>
	</table>

	<br><br>


	<div id="lista">

		<form action="" method="post">
		<table class="asiakas_lista">
			<tr><th>Nimi</th><th>Puhelin</th><th>Sähköposti</th><th class=smaller_cell>Poista</th><th class=smaller_cell></th></tr>
			<?php
				$tbl_name="kayttaja";				// Taulun nimi

				$query = "SELECT * FROM $tbl_name WHERE yritys_id='$yritys_id';";
				$result = mysqli_query($connection, $query) or die(mysqli_error($connection));

				//listataan kaikki tietokannasta löytyvät asiakkaat ja luodaan
				//niihin linkit, jotka vievät asiakkaan tilaushistoriaan.
				//Linkin mukana välitetään asiakkaan tietokanta id.
				while($row = mysqli_fetch_assoc($result)){
					if ($row["yllapitaja"] == 0 && $row["aktiivinen"] == 1){	//listataan vain asiakkaat
						
						echo '<tr data-val="'. $row['id'] .'">';
						echo '<td class="cell">' . $row["etunimi"] . " " . $row["sukunimi"] .
						'</td><td class="cell">' . $row["puhelin"] .
						'</td><td class="cell">' . $row["sahkoposti"] . 
						'</td><td class="smaller_cell">' . 
						'<input type="checkbox" name="ids[]" value="' . $row["id"] . '">' .
						'</td><td class="smaller_cell"><a href="yp_muokkaa_asiakasta.php?id=' . $row['id'] . '"><span class="nappi">Muokkaa</span></a></td>';
						
						echo '</tr>';
					}
				}
				echo '</table>';

			?>
			<br>
			<div id=submit>
				<input type="submit" value="Poista valitut asiakkaat">
			</div>
		</form>
	</div>

</div>
	<?php

		function db_poista_asiakas($ids){
			$tbl_name="kayttaja";				// Taulun nimi

			//Palvelimeen liittyminen
			global $connection;

			foreach ($ids as $asiakas_id) {
				$query = "UPDATE $tbl_name
							SET aktiivinen=0
							WHERE id='$asiakas_id'";
				$result = mysqli_query($connection, $query) or die(mysqli_error($connection));
			}

			return;
		}
	?>


<script type="text/javascript">
	$(document).ready(function(){


		//painettaessa taulun riviä ohjataan asiakkaan tilaushistoriaan
		$('.cell').click(function(){
			$('tr').click(function(){
				var id = $(this).attr('data-val');
				window.document.location = 'tilaushistoria.php?id='+id;		
			});
		});

		$('.cell').css('cursor', 'pointer');

	});

</script>

</body>
</html>
