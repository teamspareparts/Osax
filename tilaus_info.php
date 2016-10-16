<?php
require '_start.php'; global $db, $user, $cart;
require 'tecdoc.php';
require 'apufunktiot.php';

/**
 * Hakee tilauksen yleiset tiedot. Ei koske tuotteiden tietoja, ne haetaan erikseen.
 * Tiedot tilaajaasta, tilauksen päivämäärä jne., plus toimitusosoite
 * @param DByhteys $db
 * @param int $tilaus_id
 * @return stdClass; tilauksen tiedot, pois lukien tuotteet
 */
function hae_tilauksen_tiedot ( DByhteys $db, /*int*/ $tilaus_id ) {
	$sql = "SELECT tilaus.id, tilaus.paivamaara, tilaus.kasitelty, tilaus.pysyva_rahtimaksu,
				kayttaja.etunimi, kayttaja.sukunimi, kayttaja.sahkoposti, yritys.nimi AS yritys,
				CONCAT(tmo.pysyva_etunimi, ' ', tmo.pysyva_sukunimi) AS tmo_koko_nimi,
				CONCAT(tmo.pysyva_katuosoite, ', ', tmo.pysyva_postinumero, ' ', tmo.pysyva_postitoimipaikka) AS tmo_osoite,
				tmo.pysyva_sahkoposti AS tmo_sahkoposti, tmo.pysyva_puhelin AS tmo_puhelin,
				SUM( tilaus_tuote.kpl * 
						( (tilaus_tuote.pysyva_hinta * (1+tilaus_tuote.pysyva_alv)) * (1 - tilaus_tuote.pysyva_alennus) ) )
					AS summa,
				SUM(tilaus_tuote.kpl) AS kpl
			FROM tilaus
			LEFT JOIN kayttaja ON kayttaja.id=tilaus.kayttaja_id
			LEFT JOIN tilaus_tuote ON tilaus_tuote.tilaus_id=tilaus.id
			LEFT JOIN tuote ON tuote.id=tilaus_tuote.tuote_id
			LEFT JOIN tilaus_toimitusosoite AS tmo ON tmo.tilaus_id = tilaus.id
			LEFT JOIN yritys ON yritys.id = kayttaja.yritys_id
			WHERE tilaus.id = ? ";
	return $db->query($sql, [$tilaus_id], NULL);
}

/**
 * Hakee, ja palauttaa tilaukseen liitettyjen tuotteiden tiedot.
 * @param DByhteys $db
 * @param int $tilaus_id
 * @return array <p> Array of objects. tiedot tilatuista tuotteista. Palauttaa tyhjän arrayn, jos ei tuotteita
 */
function get_products_in_tilaus( DByhteys $db, /*int*/ $tilaus_id) {
	$sql = "SELECT tuote_id AS id, pysyva_hinta, pysyva_alv, pysyva_alennus, kpl,
				( (pysyva_hinta * (1 + pysyva_alv)) * (1 - pysyva_alennus) ) AS maksettu_hinta,
				 tuote.articleNo, tuote.brandNo
			FROM tilaus_tuote
			LEFT JOIN tuote ON tuote.id = tilaus_tuote.tuote_id
			WHERE tilaus_id = ?";
	$products = $db->query( $sql, [$tilaus_id], FETCH_ALL );
	get_basic_product_info( $products );

	return $products;
}

/**
 * Muokkaa alennuksen tulostettavaan muotoon, tuotelistaan.
 * @param float $alennus
 * @return String; tulostettava merkkijono
 */
function tulosta_alennus_tuotelistaan( /*float*/ $alennus ) {
	if ( (float)$alennus !== 0 ) {
		$alennus = round( $alennus * 100 ) . " %";
	} else {
		$alennus = "---";
	}

	return $alennus;
}

$tilaus_id = isset($_GET["id"]) ? $_GET["id"] : 0;
$tilaus_tiedot = hae_tilauksen_tiedot( $db, $tilaus_id );

//Tarkastetaan tilaus_id:n oikeellisuus
if ($tilaus_tiedot->id === NULL) {
	header("Location:etusivu.php"); exit();
}

//Ei sallita katsoa muiden tilauksia paitsi ylläpitäjänä
if ( !($tilaus_tiedot->sahkoposti == $_SESSION["email"]) && !$user->isAdmin() ) {
		header("Location:tilaushistoria.php"); exit();
}

$products = get_products_in_tilaus( $db, $tilaus_id );
?>
<!DOCTYPE html>
<html lang="fi">
<head>
	<meta charset="UTF-8">
	<link rel="stylesheet" href="css/styles.css">
	<link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
	<title>Tilaus-info</title>
</head>
<body>
<?php include 'header.php';
?>

<main class="main_body_container">
	<section class="flex_row">
		<h1 class="otsikko">Tilauksen tiedot</h1>
		<?php if ($tilaus_tiedot->kasitelty == 0) :?>
			<h4 style="color:red; display:flex; align-items:center;">
			Odottaa käsittelyä.</h4>
		<?php else: ?>
		<h4 style="color:green; display:flex; align-items:center;">
			Käsitelty ja toimitettu.</h4>
		<?php endif;?>
	</section>
	<!-- HTML -->
	<div class="flex_row">

			<table class='tilaus_info'>
				<tr><td>Tilausnumero: <?= sprintf('%04d', $tilaus_tiedot->id)?></td>
					<td>Päivämäärä: <?= date("d.m.Y", strtotime($tilaus_tiedot->paivamaara))?></td></tr>
				<tr><td>Tilaaja: <?= "{$tilaus_tiedot->etunimi} {$tilaus_tiedot->sukunimi}" ?></td>
					<td>Yritys: <?= $tilaus_tiedot->yritys?></td></tr>
				<tr><td>Tuotteet: <?= $tilaus_tiedot->kpl?></td>
					<td>Summa:
						<?= format_euros($tilaus_tiedot->summa + $tilaus_tiedot->pysyva_rahtimaksu)?>
						( ml. rahtimaksu )
					</td></tr>
				<tr><td colspan="2" class="small_note">Kaikki hinnat sisältävät ALV:n</td></tr>
			</table>


			<table class="tilaus_info">
				<tr><td>Toimitusosoite</td></tr>
				<tr><td>Nimi: <?= $tilaus_tiedot->tmo_koko_nimi?></td></tr>
				<tr><td><?= $tilaus_tiedot->tmo_osoite?></td></tr>
				<tr><td><?= "{$tilaus_tiedot->tmo_puhelin}, {$tilaus_tiedot->tmo_sahkoposti}"?></td></tr>
			</table>
	</div>
	<br>
	<table>
		<thead>
			<tr><th>Tuotenumero</th><th>Tuote</th><th>Valmistaja</th><th class="number">Hinta (yht.)</th>
				<th class="number">Kpl-hinta</th><th class="number">ALV-%</th><th class="number">Alennus</th>
				<th class="number">Kpl</th></tr>
		</thead>
		<tbody>
		<?php foreach ($products as $product) : ?>
			<tr>
				<td><?= $product->articleNo?></td>
				<td><?= $product->articleName?></td>
				<td><?= $product->brandName?></td>
				<td class="number"><?= format_euros( $product->maksettu_hinta * $product->kpl )?></td>
				<td class="number"><?= format_euros( $product->maksettu_hinta )?></td>
				<td class="number"><?= round( (float)$product->pysyva_alv * 100 )?> %</td>
				<td class="number"><?= tulosta_alennus_tuotelistaan( (float)$product->pysyva_alennus )?></td>
				<td class="number"><?= $product->kpl?></td>
			</tr>
		<?php endforeach; ?>
			<tr style="background-color:#cecece;">
				<td>---</td>
				<td>Rahtimaksu</td>
				<td>Posti / Itella</td>
				<td class="number"><?= format_euros( $tilaus_tiedot->pysyva_rahtimaksu ) ?></td>
				<td class="number">---</td>
				<td class="number">0 %</td>
				<td class="number">
					<?= ($tilaus_tiedot->pysyva_rahtimaksu===0) ? "Ilmainen toimitus" : "---" ?>
				</td>
				<td class="number">---</td>
			</tr>
		</tbody>
	</table>
</main>

</body>
</html>
