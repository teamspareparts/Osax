<?php
require '_start.php'; global $db, $user, $cart;
require 'apufunktiot.php';
if( !$user->isAdmin() ) {
	header("Location:etusivu.php"); exit();
}
$sql = "SELECT tilaus.id, tilaus.paivamaara, tilaus.kasitelty, kayttaja.etunimi, kayttaja.sukunimi, 
			SUM( tilaus_tuote.kpl * (tilaus_tuote.pysyva_hinta*(1+tilaus_tuote.pysyva_alv))) AS summa 
		FROM tilaus 
		LEFT JOIN kayttaja ON kayttaja.id=tilaus.kayttaja_id
		LEFT JOIN tilaus_tuote ON tilaus_tuote.tilaus_id=tilaus.id
		GROUP BY tilaus.id
		ORDER BY tilaus.id DESC";
$tilaukset = $db->query( $sql, NULL, FETCH_ALL );
?>
<!DOCTYPE html>
<html lang="fi">
<head>
	<meta charset="UTF-8">
	<title>Tilaukset</title>
	<link rel="stylesheet" href="css/styles.css">
	<link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
	<script src="https://code.jquery.com/jquery-3.1.1.min.js"></script>
</head>
<body>
<?php include 'header.php'; ?>
<main class="main_body_container">
	<section>
		<h1 class="otsikko">Tilaushistoria</h1>
		<div id="painikkeet">
		<a class="nappi" href="yp_tilaukset.php" style="color:#000; background-color:#c5c5c5; border-color:#000;">
			Takaisin</a>
	</div>
	</section>

	<?php if ($tilaukset) : ?>
	<section>
		<table style="width:100%;">
			<thead>
			<tr><th>Tilausnro.</th><th>Päivämäärä</th><th>Tilaaja</th><th>Summa</th><th>Käsitelty</th></tr>
			</thead>
			<tbody>
			<?php foreach ($tilaukset as $tilaus) : ?>
				<tr>
					<td data-href="tilaus_info.php?id=<?= $tilaus->id ?>"><?= $tilaus->id?></td>
					<td data-href="tilaus_info.php?id=<?= $tilaus->id ?>"><?= date("d.m.Y", strtotime($tilaus->paivamaara))?></td>
					<td data-href="tilaus_info.php?id=<?= $tilaus->id ?>"><?= $tilaus->etunimi . " " . $tilaus->sukunimi?></td>
					<td data-href="tilaus_info.php?id=<?= $tilaus->id ?>"><?= format_euros($tilaus->summa)?></td>
					<td data-href="tilaus_info.php?id=<?= $tilaus->id ?>">
						<?php if ( $tilaus->kasitelty === 0 ) : ?>
							<span style="color: red">EI</span>
						<?php else : ?>
							<span style="color: green">OK</span>
						<?php endif;?>
					</td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
	</section>
	<?php else: ?>
	<span style="font-weight: bold;">Ei tilauksia.</span>
	<?php endif;?>


</main>
<script>
	$(function(){
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
