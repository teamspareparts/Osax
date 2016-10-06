<?php
require '_start.php'; global $db, $user, $cart, $yritys;
require 'apufunktiot.php';

/**
 * @param DByhteys $db
 * @param User $user
 * @return stdClass[]
 */
function hae_tilaukset ( DByhteys $db, User $user ) {
	$sql = "SELECT tilaus.id, tilaus.paivamaara, tilaus.kasitelty, 
				SUM(tilaus_tuote.kpl) AS kpl, 
				SUM( tilaus_tuote.kpl * ( (tilaus_tuote.pysyva_hinta * (1 + tilaus_tuote.pysyva_alv))
					* (1 - tilaus_tuote.pysyva_alennus) ) )
					AS summa
			FROM tilaus 
			LEFT JOIN tilaus_tuote ON tilaus_tuote.tilaus_id = tilaus.id
			WHERE kayttaja_id = ?";

	return $db->query( $sql, [$user->id], DByhteys::FETCH_ALL );
}

// Jos käyttäjä on admin, ja tarkoitus hakea asiakkaan tilaukset
if ( $user->isAdmin() && !empty($_GET['id']) ) {
	$asiakas = new User( $db, $_GET['id'] );
	$tilaukset = hae_tilaukset( $db, $asiakas );
} else {
	$tilaukset = hae_tilaukset( $db, $user ); //Muuten haetaan vain sis.kirj. käyttäjän tilaukset
}
?>

<!DOCTYPE html>
<html lang="fi">
<head>
	<meta charset="UTF-8">
	<link rel="stylesheet" href="css/styles.css">
	<title>Tilaushistoria</title>
</head>
<body>
<?php include 'header.php'; ?>
<main class="main_body_container">
	<h1 class="otsikko">Asiakkaan Tilaushistoria</h1>
	<?php if ( $user->isAdmin() ) : ?>
		<p class="asiakas_info">Tilaaja: <?=$asiakas->kokoNimi()?></p>
	<?php endif; ?>

	<table>
	<?php if ( $tilaukset ) : ?>
		<thead>
			<tr><th>Tilausnumero</th>
				<th>Päivämäärä</th>
				<th>Tuotteet (kpl)</th>
				<th>Summa</th>
				<th>Käsitelty</th>
			</tr>
		</thead>
		<tbody>
		<?php foreach ( $tilaukset as $tilaus ) : ?>
			<tr data-href="tilaus_info.php?id=<?= $tilaus->id ?>">
				<th><?= $tilaus->id ?></th>
				<th><?= $tilaus->paivamaara ?></th>
				<th><?= $tilaus->kpl ?></th>
				<th><?= $tilaus->summa ?></th>
				<th><?=	$tilaus->kasitelty == 1
					? "<span style='color:green;'>OK</span>"
					: "<span style='color:red;'>EI</span>" ?></th>
			</tr>
		<?php endforeach; ?>
		</tbody>
	<?php else : ?>
		<p>Ei tehtyjä tilauksia.</p>
	<?php endif; ?>
	</table>
</main>
<br>

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
