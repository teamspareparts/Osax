<?php declare(strict_types=1);
require '_start.php'; global $db, $user, $cart;
require 'apufunktiot.php';

if ( !$user->isAdmin() ) {
	header("Location:etusivu.php");
	exit();
}

/**
 * Hakee tilaukset
 * @param DByhteys $db
 * @return array
 */
function hae_tilaukset( DByhteys $db ) : array {
	$sql = "SELECT tilaus.id, tilaus.paivamaara, tilaus.pysyva_rahtimaksu, tilaus.maksettu, kayttaja.etunimi, kayttaja.sukunimi,
				SUM( tilaus_tuote.kpl * 
			        (tilaus_tuote.pysyva_hinta * (1+tilaus_tuote.pysyva_alv) * (1-tilaus_tuote.pysyva_alennus)) )
			        AS summa
			FROM tilaus
			LEFT JOIN kayttaja ON kayttaja.id = tilaus.kayttaja_id
			LEFT JOIN tilaus_tuote ON tilaus_tuote.tilaus_id = tilaus.id
			WHERE tilaus.kasitelty = 0
			GROUP BY tilaus.id";
	return $db->query($sql, [], FETCH_ALL);
}

/** Merkitään tilaukset käsitellyiksi */
if ( isset($_POST['set_done']) ) {
	foreach ($_POST['ids'] as $id) {
		$db->query("UPDATE tilaus SET kasitelty = 1 WHERE id = ?", [$id]);
	}
}
/** Tarkistetaan feedback, ja estetään formin uudelleenlähetys */
if (!empty($_POST)) {
	header("Location: " . $_SERVER['REQUEST_URI']); //Estää formin uudelleenlähetyksen
	exit();
}
$feedback = isset($_SESSION['feedback']) ? $_SESSION['feedback'] : "";
unset($_SESSION["feedback"]);

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

	<div class="otsikko_container">
		<section class="takaisin">
		</section>
		<section class="otsikko">
			<h1>Odottavat tilaukset</h1>
		</section>
		<section class="napit">
			<a href="yp_tilaushistoria.php" class="nappi grey">Tilaushistoria<i class="material-icons">navigate_next</i></a>
		</section>
	</div>

	<section>
		<?php if ($tilaukset) : ?>
			<form action="" method="post">
				<table style="width:100%;">
					<thead>
					<tr><th colspan="5" class="center" style="background-color:#1d7ae2;">Tilaukset</th></tr>
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
									Valitse <input type="checkbox" name="ids[]" value="<?= $tilaus->id?>">
								</label>
								<?php else : ?>
								<span class="small_note" style="color: darkred;">Odottaa maksua.</span>
								<?php endif; ?>
							</td>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table>
				<div style="text-align:right;padding-top:10px;">
					<input name="set_done" type="submit" value="Merkitse valitut käsitellyiksi" class="nappi">
				</div>
			</form>
		<?php else: ?>
			<p class="center"><span style="font-weight: bold">Ei käsittelemättömiä tilauksia.</span></p>
		<?php endif;?>
	</section>
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
