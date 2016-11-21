<?php
require '_start.php'; global $db, $user, $cart;
require 'tecdoc.php';
require 'apufunktiot.php';
if ( !$user->isAdmin() ) {
	header("Location:etusivu.php"); exit();
}

/**
 * Tässä tiedostossa listataan kaikki toimittajille lähetetyt ostotilauskirjat, jotka voi merkata saapuneeksi.
 * Tietoja voi vielä muuttaa, mikäli saapunut erä ei vastaa ostotilauskirjaa.
 */


if ( !empty($_POST) ){
	header("Location: " . $_SERVER['REQUEST_URI']); //Estää formin uudelleenlähetyksen
	exit();
}

$feedback = isset($_SESSION["feedback"]) ? $_SESSION["feedback"] : "";
unset($_SESSION["feedback"]);


$ostotilauskirjat = $db->query("SELECT * FROM ostotilauskirja_arkisto WHERE hyvaksytty = 0", [], FETCH_ALL);


?>



<!DOCTYPE html>
<html lang="fi" xmlns="http://www.w3.org/1999/html">
<head>
	<meta charset="UTF-8">
	<link rel="stylesheet" href="css/styles.css">
	<link rel="stylesheet" href="css/jsmodal-light.css">
	<link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
	<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.0/jquery.min.js"></script>
	<script src="js/jsmodal-1.0d.min.js"></script>
	<title>Ostotilauskirjat</title>
</head>
<body>
<?php require 'header.php'?>
<main class="main_body_container">

	<?= $feedback?>

	<?php if ( $ostotilauskirjat ) : ?>
		<table>
			<thead>
			<tr><th colspan="3" class="center" style="background-color:#1d7ae2;">Odottavat Ostotilauskirjat</th></tr>
			<tr><th style="max-width: 200pt">Tunniste</th>
				<th>Oletettu Saapumispäivä</th>
				<th>Rahti</th>
			</tr>
			</thead>
			<tbody>
			<?php foreach ( $ostotilauskirjat as $otk ) : ?>
				<tr>
					<td data-href="yp_ostotilauskirja_tuote_odottavat.php?id=<?=$otk->id?>">
						<?= $otk->tunniste?></td>
					<td data-href="yp_ostotilauskirja_tuote_odottavat.php?id=<?=$otk->id?>">
						<?= date("d.m.Y", strtotime($otk->oletettu_saapumispaiva))?></td>
					<td data-href="yp_ostotilauskirja_tuote_odottavat.php?id=<?=$otk->id?>">
						<?= format_euros($otk->rahti)?></td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
		<form
	<?php else : ?>
		<p>Ei ostotilaukirjoja.</p>
	<?php endif; ?>

</main>




<script type="text/javascript">

	$(document).ready(function(){

		$('*[data-href]')
			.css('cursor', 'pointer')
			.click(function(){
				window.location = $(this).data('href');
				return false;
			});
	});

</script>
</body>
</html>
