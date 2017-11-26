<?php declare(strict_types=1);
require '_start.php'; global $db, $user, $cart;
require 'apufunktiot.php';

/**
 * Hakee käyttäjän kaikkien tilausten tiedot
 * @param DByhteys $db
 * @param int $user_id
 * @return array
 */
function hae_tilaukset ( DByhteys $db, int $user_id ) : array {
	$sql = "SELECT tilaus.id, tilaus.paivamaara, tilaus.kasitelty,
				SUM(tilaus_tuote.kpl) AS kpl,
				SUM( tilaus_tuote.kpl * ( (tilaus_tuote.pysyva_hinta*(1+tilaus_tuote.pysyva_alv))
					* (1-tilaus_tuote.pysyva_alennus) ) ) AS summa
			FROM tilaus
			LEFT JOIN tilaus_tuote ON tilaus_tuote.tilaus_id = tilaus.id
			WHERE kayttaja_id = ?
			GROUP BY tilaus.id";

	return $db->query( $sql, [$user_id], FETCH_ALL );
}

// Jos käyttäjä on admin, ja tarkoitus hakea asiakkaan tilaukset
if ( $user->isAdmin() && isset($_GET['id']) ) {
	$user_id = (int)$_GET['id'];
	$asiakas = new User( $db, $user_id );
} else {
	// Muuten haetaan vain sis.kirj. käyttäjän tilaukset
	$user_id = $user->id;
}
$tilaukset = hae_tilaukset( $db, $user_id );

?>
<!DOCTYPE html>
<html lang="fi">
<head>
	<meta charset="UTF-8">
	<title>Tilaushistoria</title>
	<link rel="stylesheet" href="css/styles.css">
	<link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
	<script src="https://code.jquery.com/jquery-3.1.1.min.js"></script>
</head>
<body>
<?php include 'header.php'; ?>
<main class="main_body_container">
	<div class="otsikko_container">
		<section class="takaisin">
			<?php if ($user->isAdmin()) :?>
				<button class="nappi grey" onclick="history.back();">Takaisin</button>
			<?php endif;?>
		</section>
		<section class="otsikko">
			<h1>Tilaushistoria</h1>
			<?php if ( $user->isAdmin() ) : ?>
				<span> &nbsp;Tilaaja: <?=$asiakas->kokoNimi()?>, <?=$asiakas->yrityksen_nimi?></span>
			<?php endif; ?>
		</section>
		<section class="napit">
			<!--<button class="nappi"></button>-->
		</section>
	</div>

	<?php if ( $tilaukset ) : ?>
	<table>
		<thead>
			<tr><th colspan="5" class="center" style="background-color:#1d7ae2;">Kaikki tehdyt tilaukset</th></tr>
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
				<td><?= $tilaus->id ?></td>
				<td><?= $tilaus->paivamaara ?></td>
				<td><?= $tilaus->kpl ?></td>
				<td><?= format_number($tilaus->summa) ?></td>
				<td><?=	$tilaus->kasitelty == 1
					? "<span style='color:green;'>OK</span>"
					: "<span style='color:red;'>EI</span>" ?></td>
			</tr>
		<?php endforeach; ?>
		</tbody>
	</table>
	<?php else : ?>
		<p>Ei tehtyjä tilauksia.</p>
	<?php endif; ?>
</main>

<?php include 'footer.php'; ?>

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
