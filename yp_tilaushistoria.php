<?php
require '_start.php'; global $db, $user, $cart;

if( !$user->isAdmin() ) {
	header("Location:etusivu.php");
	exit();
}

$sql = "SELECT tilaus.id, tilaus.paivamaara, tilaus.kasitelty, tilaus.pysyva_rahtimaksu,
            kayttaja.etunimi, kayttaja.sukunimi, 
			SUM( tilaus_tuote.kpl * 
			    (tilaus_tuote.pysyva_hinta * (1+tilaus_tuote.pysyva_alv) * (1-tilaus_tuote.pysyva_alennus)) )
			    AS summa,
			yritys.nimi AS yritys
		FROM tilaus 
		LEFT JOIN kayttaja ON kayttaja.id=tilaus.kayttaja_id
		LEFT JOIN yritys ON yritys.id = kayttaja.yritys_id
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
		    <a class="nappi grey" href="yp_tilaukset.php">Takaisin</a>
	    </div>
	</section>

	<?php if ($tilaukset) : ?>
	<section>
		<table style="width:100%;">
			<thead>
			<tr><th colspan="6" class="center" style="background-color:#1d7ae2;">Kaikki tehdyt tilaukset</th></tr>
			<tr><th>Tilausnro.</th><th>Päivämäärä</th><th>Yritys</th><th>Tilaaja</th><th>Summa</th><th class="smaller_cell">Käsitelty</th></tr>
			</thead>
			<tbody>
			<?php foreach ($tilaukset as $tilaus) : ?>
				<tr>
					<td data-href="tilaus_info.php?id=<?= $tilaus->id ?>"><?= $tilaus->id?></td>
					<td data-href="tilaus_info.php?id=<?= $tilaus->id ?>"><?= date("d.m.Y", strtotime($tilaus->paivamaara))?></td>
					<td data-href="tilaus_info.php?id=<?= $tilaus->id ?>"><?= $tilaus->yritys?></td>
					<td data-href="tilaus_info.php?id=<?= $tilaus->id ?>"><?= $tilaus->etunimi . " " . $tilaus->sukunimi?></td>
					<td data-href="tilaus_info.php?id=<?= $tilaus->id ?>"><?= format_number($tilaus->summa + $tilaus->pysyva_rahtimaksu)?></td>
					<td class="smaller_cell" data-href="tilaus_info.php?id=<?= $tilaus->id ?>">
						<?=	$tilaus->kasitelty == 1
							? "<span style='color:green;'>OK</span>"
							: "<span style='color:red;'>EI</span>" ?>
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

<?php require 'footer.php'; ?>

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
