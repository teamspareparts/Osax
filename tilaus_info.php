<?php require '_start.php';
require 'tietokanta.php';
require 'tecdoc.php';
require 'apufunktiot.php';

/**
 * Hakee tilauksen yleiset tiedot. Ei koske tuotteiden tietoja, ne haetaan erikseen.
 * Tiedot tilaajaasta, tilauksen päivämäärä jne., plus toimitusosoite
 * @param DByhteys $db
 * @param int $tilaus_id
 * @return stdClass; tilauksen tiedot, pois lukien tuotteet
 */
function hae_tilauksen_tiedot ( DByhteys $db, /* int */ $tilaus_id ) {
	$query = "
		SELECT tilaus.id, tilaus.paivamaara, tilaus.kasitelty, tilaus.pysyva_rahtimaksu,
			kayttaja.etunimi, kayttaja.sukunimi, kayttaja.sahkoposti, yritys.nimi AS yritys,
			CONCAT(tmo.pysyva_etunimi, ' ', tmo.pysyva_sukunimi) AS tmo_koko_nimi,
			CONCAT(tmo.pysyva_katuosoite, ', ', tmo.pysyva_postinumero, ' ', tmo.pysyva_postitoimipaikka) AS tmo_osoite,
			tmo.pysyva_sahkoposti AS tmo_sahkoposti, tmo.pysyva_puhelin AS tmo_puhelin,
			SUM( tilaus_tuote.kpl * 
					( (tilaus_tuote.pysyva_hinta * (1+tilaus_tuote.pysyva_alv)) * (1 - tilaus_tuote.pysyva_alennus) ) )
				AS summa,
			SUM(tilaus_tuote.kpl) AS kpl
		FROM tilaus
		LEFT JOIN kayttaja
			ON kayttaja.id=tilaus.kayttaja_id
		LEFT JOIN tilaus_tuote
			ON tilaus_tuote.tilaus_id=tilaus.id
		LEFT JOIN tuote
			ON tuote.id=tilaus_tuote.tuote_id
		LEFT JOIN tilaus_toimitusosoite AS tmo
			ON tmo.tilaus_id = tilaus.id
		LEFT JOIN yritys
			ON yritys.id = kayttaja.yritys_id
		WHERE tilaus.id = :order_id ";

	$values = [ 'order_id' => $tilaus_id ];
	return ( $db->query($query, $values) );
}

/**
 * Hakee, ja palauttaa tilaukseen liitettyjen tuotteiden tiedot.
 * //TODO: Varmista, mikä on about maksimi määrä tuotteita, joka tilauksessa voi käytännössä olla
 * //	Hajoaa, jos liikaa tuotteita haetaan.
 * @param DByhteys $db
 * @param int $tilaus_id
 * @return array <p> Array of objects. tiedot tilatuista tuotteista. Palauttaa tyhjän arrayn, jos ei tuotteita
 */
function get_products_in_tilaus( DByhteys $db, /* int */ $tilaus_id) {
	$query = "
		SELECT tuote_id AS id, pysyva_hinta, pysyva_alv, pysyva_alennus, kpl,
			( (pysyva_hinta * (1 + pysyva_alv)) * (1 - pysyva_alennus) ) AS maksettu_hinta,
			 tuote.articleNo, tuote.brandNo
		FROM tilaus_tuote
		LEFT JOIN tuote
			ON tuote.id = tilaus_tuote.tuote_id
		WHERE tilaus_id = ? ";
	return ( $db->query( $query, [$tilaus_id], FETCH_ALL ) );
}


/**
 * Muokkaa alennuksen tulostettavaan muotoon, tuotelistaan.
 * @param float $alennus
 * @return String; tulostettava merkkijono
 */
function tulosta_alennus_tuotelistaan( /* float */ $alennus ) {
	if ( (float)$alennus !== 0 ) {
		$alennus = round( $alennus * 100 ) . " %";
	} else {
		$alennus = "---";
	}
	return $alennus;
}

$tilaus_id = $_GET["id"];
$tilaus_tiedot = hae_tilauksen_tiedot( $db, $tilaus_id );

if ( !($tilaus_tiedot["sahkoposti"] == $_SESSION["email"]) ) {
	if ( !is_admin() ) {
		header("Location:tilaushistoria.php");
		exit(); }
}

$products = get_products_in_tilaus( $db, $tilaus_id );
if ( $products) {
	merge_catalog_with_tecdoc($products, true);
}
?>
<!DOCTYPE html>
<html lang="fi">
<head>
	<meta charset="UTF-8">
	<link rel="stylesheet" href="css/styles.css">
	<style type="text/css">
			.class #id tag {}
			#tilaus_info_container {
				background-color: #FFFFFF;
			}
			#tilaus_toimitusosoite {
				padding-left: 0.5em;
			}
	</style>
	<title>Tilaus-info</title>
</head>
<body>
<?php include 'header.php';
if ( !$products) { echo '<p>Ei tilaukseen liitettyjä tuotteita.</p>'; }
?>

<main class="main_body_container">
	<div id="otsikko_container" class="flex">
		<h1 class="otsikko">Tilauksen tiedot</h1>
		<?php if ($tilaus_tiedot["kasitelty"] == 0) { echo "<h4 style='color:red; display:flex; align-items:center;'>
			Odottaa käsittelyä.</h4>"; }
		else { echo "<h4 style='color:green; display:flex; align-items:center;'>
			Käsitelty ja toimitettu.</h4>"; } ?>
	</div>
	<!-- HTML -->
	<div id="tilaus_info_container" class="flex">
		<div id="tilaus_info">
			<table class='tilaus_info'>
				<tr><td>Tilausnumero: <?= sprintf('%04d', $tilaus_tiedot["id"])?></td>
					<td>Päivämäärä: <?= date("d.m.Y", strtotime($tilaus_tiedot["paivamaara"]))?></td></tr>
				<tr><td>Tilaaja: <?= $tilaus_tiedot["etunimi"] . " " . $tilaus_tiedot["sukunimi"]?></td>
					<td>Yritys: <?= $tilaus_tiedot["yritys"]?></td></tr>
				<tr><td>Tuotteet: <?= $tilaus_tiedot["kpl"]?></td>
					<td>Summa:
						<?= format_euros( $tilaus_tiedot["summa"] + $tilaus_tiedot["pysyva_rahtimaksu"])?>
						( ml. rahtimaksu )
					</td></tr>
				<tr><td class="small_note">Kaikki hinnat sisältävät ALV:n</td><td></td></tr>
			</table>

		</div>
		<div id="tilaus_toimitusosoite">
			<span>Toimitusosoite</span>
			<p>Nimi: <?= $tilaus_tiedot["tmo_koko_nimi"]?></p>
			<p><?= $tilaus_tiedot["tmo_osoite"]?></p>
			<p><?= $tilaus_tiedot["tmo_puhelin"] . ", " . $tilaus_tiedot["tmo_sahkoposti"]?></p>
		</div>
	</div>
	<br>
	<table>
		<tr><th>Tuotenumero</th><th>Tuote</th><th>Valmistaja</th><th class="number">Hinta (yht.)</th>
			<th class="number">Kpl-hinta</th><th class="number">ALV-%</th><th class="number">Alennus</th>
			<th class="number">Kpl</th></tr>
		<?php
		foreach ($products as $product) {?>
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
		<?php } ?>

		<tr style="background-color:#cecece;">
			<td>---</td>
			<td>Rahtimaksu</td>
			<td>Posti / Itella</td>
			<td class="number"><?= format_euros( $tilaus_tiedot["pysyva_rahtimaksu"] ) ?></td>
			<td class="number">---</td>
			<td class="number">0 %</td>
			<td class="number">
				<?php if ($tilaus_tiedot["pysyva_rahtimaksu"]===0) { echo "Ilmainen toimitus"; } else { echo "---"; } ?>
			</td>
			<td class="number">---</td>
		</tr>
	</table>
</main>

</body>
</html>
