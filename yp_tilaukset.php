<?php
require '_start.php'; global $db, $user, $cart;
require 'apufunktiot.php';

if ( !$user->isAdmin() ) {
	header("Location:etusivu.php");
	exit();
}

/**
 * Hakee tilaukset
 * @param DByhteys $db
 * @return stdClass[]
 */
function hae_tilaukset( DByhteys $db ) {
	$sql = "SELECT tilaus.id, tilaus.paivamaara, tilaus.pysyva_rahtimaksu, tilaus.maksettu, kayttaja.etunimi, kayttaja.sukunimi,
				SUM( tilaus_tuote.kpl * 
			        (tilaus_tuote.pysyva_hinta * (1+tilaus_tuote.pysyva_alv) * (1-tilaus_tuote.pysyva_alennus)) )
			        AS summa
			FROM tilaus
			LEFT JOIN kayttaja ON kayttaja.id = tilaus.kayttaja_id
			LEFT JOIN tilaus_tuote ON tilaus_tuote.tilaus_id = tilaus.id
			WHERE tilaus.kasitelty = 0
			GROUP BY tilaus.id";
	return $db->query($sql, NULL, FETCH_ALL);
}

/** Merkitään tilaukset käsitellyiksi */
if ( isset($_POST['set_done']) ) {
	foreach ($_POST['ids'] as $id) {
		$db->query("UPDATE tilaus SET kasitelty = 1 WHERE id = ?", [$id]);
	}
}

$tilaukset = hae_tilaukset( $db );
?>
<!DOCTYPE html>
<html lang="fi">
<head>
	<meta charset="UTF-8">
	<link rel="stylesheet" href="css/styles.css">
	<link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
	<script src="https://code.jquery.com/jquery-3.1.1.min.js"></script>
	<title>Tilaukset</title>
</head>
<body>
<?php require 'header.php'; ?>
<main class="main_body_container">
	<section>
		<h1 class="otsikko">Tilaukset</h1>
		<div id="painikkeet">
			<a href="yp_tilaushistoria.php" class="nappi grey">Tilaushistoria</a>
		</div>
	</section>

	<section>
		<?php if ($tilaukset) : ?>
			<table style="width:100%;">
				<thead>
				<tr><th>Tilausnro.</th><th>Päivämäärä</th><th>Tilaaja</th><th>Summa</th><th>Merkitse käsitellyksi</th></tr>
				</thead>
				<tbody>
				<?php foreach ($tilaukset as $tilaus) : ?>
					<tr>
						<td data-href="tilaus_info.php?id=<?= $tilaus->id ?>"><?= $tilaus->id?></td>
						<td data-href="tilaus_info.php?id=<?= $tilaus->id ?>"><?= date("d.m.Y", strtotime($tilaus->paivamaara))?></td>
						<td data-href="tilaus_info.php?id=<?= $tilaus->id ?>"><?= $tilaus->etunimi . " " . $tilaus->sukunimi?></td>
						<td data-href="tilaus_info.php?id=<?= $tilaus->id ?>"><?= format_number($tilaus->summa + $tilaus->pysyva_rahtimaksu)?></td>
						<td <?=(!$tilaus->maksettu) ? "data-href='tilaus_info.php?id={$tilaus->id}'" : ''?>>
							<?php if ( $tilaus->maksettu ) : ?>
							<label>
								Valitse <input form="done" type="checkbox" name="ids[]" value="<?= $tilaus->id?>">
							</label>
							<?php else : ?>
							<span class="small_note" style="color: darkred;">Odottaa maksua.</span>
							<?php endif; ?>
						</td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
			<form id="done" action="" method="post">
				<div style="text-align:right;padding-top:10px;">
					<input name="set_done" type="submit" value="Merkitse valitut käsitellyiksi" class="nappi">
				</div>
			</form>
		<?php else: ?>
			<p class="center"><span style="font-weight: bold">Ei käsittelemättömiä tilauksia.</span></p>
		<?php endif;?>
	</section>
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
