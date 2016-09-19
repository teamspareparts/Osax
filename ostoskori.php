<?php
require '_start.php'; global $db, $user, $yritys, $cart;
require 'tecdoc.php';
require 'apufunktiot.php';
require 'ostoskori_tilaus_funktiot.php';

$user->haeToimitusosoitteet( $db, -2 ); //Tilaus-nappia varten; ei anneta edetä, jos ei toimitusosoitteita.
$products = get_products_in_shopping_cart( $db, $cart );
$sum = 0.0; // Alhaalla listauksessa; tuotteiden summan laskentaa varten.
$cart_feedback = "";

if ( !empty($_POST['ostoskori_tuote']) ) {
	$tuote_id = $_POST['ostoskori_tuote'];
	$tuote_kpl = isset($_POST['ostoskori_maara']) ? $_POST['ostoskori_maara'] : null;
	if ( $tuote_kpl > 0 ) {
		if ( $cart->lisaa_tuote( $tuote_id, $tuote_kpl ) ) {
			$cart_feedback = '<p class="success">Ostoskori päivitetty.</p>';
		} else {
			$cart_feedback = '<p class="error">Ostoskorin päivitys ei onnistunut.</p>';
		}
	} elseif ( $tuote_kpl == 0 ) {
		if ( $cart->poista_tuote( $tuote_id ) ) {
			$cart_feedback = '<p class="success">Tuote poistettu ostoskorista.</p>';
		} else {
			$cart_feedback = '<p class="error">Tuotteen poistaminen ei onnistunut.</p>';
		}
	}
}
?>
<!DOCTYPE html>
<html lang="fi">
<head>
	<link rel="stylesheet" href="css/styles.css">
	<meta charset="UTF-8">

	<link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
	<!-- https://design.google.com/icons/ -->

	<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.0/jquery.min.js"></script>

	<style type="text/css">
		#rahtimaksu_listaus { background-color:#cecece; height: 1em; }
		.peruuta {
			background:rgb(200, 70, 70);
			border-color: #b70004;
		}
	</style>
	<title>Ostoskori</title>
</head>
<body>

<?php require "header.php"; ?>
<main class="main_body_container">
	<h1 class="otsikko">Ostoskori</h1>
	<?= $cart_feedback ?>
	<table>
		<tr><th>Tuotenumero</th><th>Tuote</th><th>Valmistaja</th>
			<th class="number">Hinta</th><th class="number">Kpl-hinta</th><th>Kpl</th><th>Info</th></tr>
		<?php foreach ($products as $product) :
			$product->hinta = tarkista_hinta_era_alennus( $product );
			$sum += $product->hinta * $product->cartCount; ?>
			<tr>
				<td><?= $product->articleNo?></td><!-- Tuotenumero -->
				<td><?= $product->articleName?></td><!-- Tuotteen nimi -->
				<td><?= $product->brandName?></td><!-- Tuotteen valmistaja -->
				<td class="number"><?= format_euros( $product->hinta * $product->cartCount ) ?></td><!-- Hinta yhteensä -->
				<td class="number"><?= format_euros( $product->hinta ) ?></td><!-- Kpl-hinta (sis. ALV) -->
				<td style="padding-top: 0; padding-bottom: 0;">
					<input id="maara_<?= $product->id ?>" name="maara_<?= $product->id ?>" class="maara number" type="number" value="<?= $product->cartCount ?>" min="0" title="Kappalemäärä">
				</td>
				<td><?= laske_era_alennus_palauta_huomautus( $product )?></td>
				<td class="toiminnot"><a class="nappi" href="javascript:void(0)" onclick="cartAction('<?= $product->id?>')">Päivitä</a></td>
			</tr>
		<?php endforeach;
		$rahtimaksu = hae_rahtimaksu( $yritys, $sum );  ?>
		<tr id="rahtimaksu_listaus">
			<td>---</td>
			<td>Rahtimaksu</td>
			<td>---</td>
			<td class="number"><?= format_euros( $rahtimaksu[0] )?></td>
			<td class="number">---</td>
			<td class="number">1</td>
			<td><?= tulosta_rahtimaksu_alennus_huomautus( $rahtimaksu, TRUE )?></td>
		</tr>
	</table>
	<div id=tilausvahvistus_maksutiedot style="width:20em;">
		<p>Tuotteiden kokonaissumma: <b><?= format_euros( $sum )?></b></p>
		<p>Summa yhteensä: <b><?= format_euros( $sum + $rahtimaksu[0] )?></b> ( ml. toimitus )</p>
		<span class="small_note">Kaikki hinnat sis. ALV</span>
	</div>
	<?= tarkista_pystyyko_tilaamaan_ja_tulosta_tilaa_nappi_tai_disabled(
		$products, $user->toimitusosoitteet['count'] ) ?>
	<p><a class="nappi peruuta" href="tuotehaku.php">Palaa takaisin</a></p>
</main>

<form name="ostoskorilomake" method="post" class="hidden">
	<input id="ostoskori_tuote" type="hidden" name="ostoskori_tuote">
	<input id="ostoskori_maara" type="hidden" name="ostoskori_maara">
</form>

<script>

	/**
	 * Muokkaa annetun tuotteen kpl-määrää ostoskorissa.
	 * Jos kpl-määrä nolla (0), tuote poistetaan ostoskorista.
	 * @param id
	 */
	function cartAction(id) {
		var count = $('#maara_' + id).value;
		$('#ostoskori_tuote').value = id;
		$('#ostoskori_maara').value = count;
		document.ostoskorilomake.submit();
	}

</script>

</body>
</html>
