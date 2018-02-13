<?php declare(strict_types=1);

require '_start.php'; global $db, $user, $cart;
require 'ostoskori_tilaus_funktiot.php';

if ( !empty($_POST['ostoskori_tuote']) ) {
	$tuote_id = (int)$_POST['ostoskori_tuote'];
	$tuote_kpl = (int)$_POST['ostoskori_maara'] ?? 0;
	$tilaustuote = (bool)$_POST['ostoskori_tilaustuote'];
	if ( $tuote_kpl > 0 ) {
		if ( $cart->lisaa_tuote( $db, $tuote_id, $tuote_kpl, $tilaustuote ) ) {
			$_SESSION["feedback"] = '<p class="success">Ostoskori päivitetty.</p>';
		} else {
			$_SESSION["feedback"] = '<p class="error">Ostoskorin päivitys ei onnistunut.</p>';
		}
	} elseif ( $tuote_kpl == 0 ) {
		if ( $cart->poista_tuote( $db, $tuote_id ) ) {
			$_SESSION["feedback"] = '<p class="success">Tuote poistettu ostoskorista.</p>';
		} else {
			$_SESSION["feedback"] = '<p class="error">Tuotteen poistaminen ei onnistunut.</p>';
		}
	}
}

/** Tarkistetaan feedback, ja estetään formin uudelleenlähetys */
if ( !empty($_POST) ) { //Estetään formin uudelleenlähetyksen
	header("Location: " . $_SERVER['REQUEST_URI']); exit();
} else {
	$feedback = $_SESSION['feedback'] ?? "";
	unset($_SESSION["feedback"]);
}

// Tarkistetaan $feedback ennen näitä, koska nämä hakevat juttuja tietokannasta HTML-osuutta varten.
$user->haeToimitusosoitteet( $db, -2 ); // Tilaus-nappia varten; ei anneta edetä, jos ei toimitusosoitteita.
$cart->hae_ostoskorin_sisalto( $db, true, true );
check_products_in_shopping_cart( $cart, $user ); // Tarkistetaan hinnat, ja rahtimaksu.
?>
<!DOCTYPE html>
<html lang="fi">
<head>
	<meta charset="UTF-8">
	<title>Ostoskori</title>
	<link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
	<link rel="stylesheet" href="css/styles.css">
	<style type="text/css">
		#rahtimaksu_listaus { background-color:#cecece; height: 1em; }
	</style>
</head>
<body>

<?php require "header.php"; ?>
<main class="main_body_container">

	<div class="otsikko_container">
		<section class="takaisin">
			<button class="nappi grey" id="takaisin_nappi">
				<i class="material-icons">navigate_before</i>Takaisin</button>
		</section>
		<section class="otsikko">
			<h1>Ostoskori</h1>
		</section>
	</div>

	<?= $feedback ?>

	<table style="width:90%;">
		<thead>
		<tr><th colspan="8" class="center" style="background-color:#1d7ae2;">Tuotteet ostoskorissa</th></tr>
		<tr> <th>Tuotenumero</th> <th>Tuote</th> <th>Valmistaja</th>
			<th class="number">Hinta</th> <th class="number">Kpl-hinta</th> <th class="number">Kpl</th>
			<th>Info</th> <th></th> </tr>
		</thead>
		<tbody>
		<?php foreach ( $cart->tuotteet as $tuote) : ?>
			<tr>
				<td><?= $tuote->tuotekoodi?></td> <!-- Tuotenumero -->
				<td><?= $tuote->nimi?></td> <!-- Tuotteen nimi -->
				<td><?= $tuote->valmistaja?></td> <!-- Tuotteen valmistaja -->
				<td class="number"><?= $tuote->summa_toString() ?></td> <!-- Hinta yhteensä (sis. ALV) -->
				<td class="number"><?= $tuote->aHintaAlennettu_toString() ?></td> <!-- Kpl-hinta (sis. ALV) -->
				<td class="number">
					<input id="maara_<?= $tuote->id ?>" name="maara_<?= $tuote->id ?>"
					       class="maara number" type="number" value="<?= $tuote->kpl_maara ?>"
					       min="0" title="Kappalemäärä"> <!-- Kpl-määrä (käyttäjän muokattavissa) -->
				</td>
				<td><?= $tuote->alennus_huomautus ?></td>
				<td class="toiminnot">
					<button class="nappi" onclick="cartAction(<?= $tuote->id ?>, <?= $tuote->tilaustuote ?>)">
						Päivitä</button> <!-- Ostoskorin päivittäminen -->
				</td>
			</tr>
		<?php endforeach; ?>
		<tr id="rahtimaksu_listaus">
			<td>---</td>
			<td>Rahtimaksu</td>
			<td>---</td>
			<td class="number"><?= $user->rahtimaksu_toString() ?></td>
			<td class="number">---</td>
			<td class="number">---</td>
			<td colspan="2"><?= ($user->rahtimaksu == 0) ? 'Ilmainen toimitus'
					: "Ilmainen toimitus<br>{$user->ilmToimRaja_toString()}:n jälkeen." ?></td>
		</tr>
		</tbody>
	</table>
	<div id=tilausvahvistus_maksutiedot style="width:20em;">
		<p>Tuotteiden kokonaissumma: <b><?= $cart->summa_toString() ?></b></p>
		<p>Summa yhteensä: <b><?= format_number( ($cart->summa_yhteensa + $user->rahtimaksu) )?></b> ( ml. toimitus )</p>
		<span class="small_note">Kaikki hinnat sis. ALV</span>
	</div>
	<?= tarkista_pystyyko_tilaamaan_ja_tulosta_tilaa_nappi_tai_disabled( $cart, $user ) ?>
</main>

<?php require 'footer.php'; ?>

<form name="ostoskorilomake" method="post" class="hidden">
	<input id="ostoskori_tuote" type="hidden" name="ostoskori_tuote">
	<input id="ostoskori_maara" type="hidden" name="ostoskori_maara">
	<input id="ostoskori_tilaustuote" type="hidden" name="ostoskori_tilaustuote">
</form>
<script>
	/**
	 * Muokkaa annetun tuotteen kpl-määrää ostoskorissa.
	 * Jos kpl-määrä nolla (0), tuote poistetaan ostoskorista.
	 * //TODO: Ajaxilla saisi toimimaan hieman siistimmin, mutta vaikeampi toteuttaa. --JJ170609
	 * @param id
	 * @param tilaustuote
	 */
	function cartAction( id, tilaustuote ) {
		let count = document.getElementById('maara_' + id).value;
		document.getElementById('ostoskori_tuote').value = id;
		document.getElementById('ostoskori_maara').value = count;
        document.getElementById('ostoskori_tilaustuote').value = tilaustuote;
		document.ostoskorilomake.submit();
	}

	document.getElementById('takaisin_nappi').addEventListener('click', function() {
		if ( window.location.search === "?cancel_maksu" ) {
			window.history.go(-4);
		} else if ( window.location.search === "?cancel" ) {
			window.history.go(-3);
		} else {
			window.history.back();
		}
	});
</script>

</body>
</html>
