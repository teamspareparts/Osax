<?php
require '_start.php'; global $db, $user, $cart;

/**
 * Hakee yleiset tiedot tilauksesta, käyttäjän tiedot (mukaan lukien yrityksen nimi), toimitusosoitteet,
 *  ja tilaukset loppusumman ja kpl-määrän. Ei hae tuotteita. Käyttäjän tiedot erikseen, eikä $user-oliosta,
 *  jotta ylläpitäjä voi käyttää sivua.
 * @param DByhteys $db
 * @param int      $tilaus_id
 * @return stdClass <p> tilauksen tiedot, pois lukien tuotteet
 */
function hae_tilauksen_tiedot ( DByhteys $db, /*int*/ $tilaus_id ) {
	$sql = "SELECT tilaus.id, tilaus.kayttaja_id, tilaus.paivamaara, tilaus.kasitelty, tilaus.pysyva_rahtimaksu,
				kayttaja.etunimi, kayttaja.sukunimi, kayttaja.sahkoposti, yritys.nimi AS yritys, tilaus.maksettu,
				tilaus.laskunro,
				CONCAT(tmo.pysyva_etunimi, ' ', tmo.pysyva_sukunimi) AS tmo_koko_nimi,
				CONCAT(tmo.pysyva_katuosoite, ', ', tmo.pysyva_postinumero, ' ', tmo.pysyva_postitoimipaikka) AS tmo_osoite,
				tmo.pysyva_sahkoposti AS tmo_sahkoposti, tmo.pysyva_puhelin AS tmo_puhelin,
				SUM( tilaus_tuote.kpl * 
						(tilaus_tuote.pysyva_hinta * (1+tilaus_tuote.pysyva_alv) * (1-tilaus_tuote.pysyva_alennus)) )
					AS summa,
				SUM(tilaus_tuote.kpl) AS kpl
			FROM tilaus
			LEFT JOIN kayttaja ON kayttaja.id=tilaus.kayttaja_id
			LEFT JOIN tilaus_tuote ON tilaus_tuote.tilaus_id=tilaus.id
			LEFT JOIN tuote ON tuote.id=tilaus_tuote.tuote_id
			LEFT JOIN tilaus_toimitusosoite AS tmo ON tmo.tilaus_id = tilaus.id
			LEFT JOIN yritys ON yritys.id = kayttaja.yritys_id
			WHERE tilaus.id = ?";

	return $db->query( $sql, [ $tilaus_id ] );
}

/**
 * Hakee, ja palauttaa tilaukseen liitettyjen tuotteiden tiedot.
 * @param DByhteys $db
 * @param int      $tilaus_id
 * @return Tuote[] <p> Tiedot tilatuista tuotteista.
 */
function hae_tilauksen_tuotteet( DByhteys $db, /*int*/ $tilaus_id ) {
	$sql = "SELECT tuote_id AS id, pysyva_hinta AS a_hinta_ilman_alv, pysyva_alv AS alv_prosentti,
				pysyva_alennus AS alennus_prosentti, kpl AS kpl_maara, tuotteen_nimi AS nimi, tilaus_tuote.valmistaja,
  				(pysyva_hinta * (1+pysyva_alv)) AS a_hinta, 
				(pysyva_hinta * (1+pysyva_alv) * (1-pysyva_alennus)) AS a_hinta_alennettu, 
				(kpl * (pysyva_hinta * (1+pysyva_alv) * (1-pysyva_alennus))) AS summa, 
				tuote.tuotekoodi
			FROM tilaus_tuote
			LEFT JOIN tuote ON tuote.id = tilaus_tuote.tuote_id
			WHERE tilaus_id = ?";

	return $db->query( $sql, [ $tilaus_id ], FETCH_ALL, PDO::FETCH_CLASS, 'Tuote' );
}

// Tarkistetaan URL:n ID
if ( empty( $_GET[ 'id' ] ) ) {
	header( "Location:tilaushistoria.php?id={$user->id}" );
	exit();
}

$tilaus_tiedot = hae_tilauksen_tiedot( $db, $_GET[ 'id' ] );

// Löytyikö tilauksen tiedot ID:llä. Tarkistus NULL:lla, eikä empty():lla,
//  koska haku palauttaa ei-tyhjää aina jostain syystä.
if ( $tilaus_tiedot->id === null ) {
	header( "Location:tilaushistoria.php" );
	exit();
}

// Tarkistetaan onko tilaus sen hetkisen käyttäjän tekemä, tai onko käyttäjä admin.
// Lähetään pois, jos ei kumpaankin.
elseif ( !($tilaus_tiedot->sahkoposti == $user->sahkoposti) && !$user->isAdmin() ) {
	header( "Location:tilaushistoria.php" );
	exit();
}

/** @var Tuote[] $tuotteet <p> Tilauksen tuotteet */
$tuotteet = hae_tilauksen_tuotteet( $db, $tilaus_tiedot->id );

$lasku_file_nimi = "lasku-". sprintf( '%05d', $tilaus_tiedot->laskunro) ."-{$tilaus_tiedot->kayttaja_id}.pdf";
$noutolista_file_nimi =
	"noutolista-". sprintf('%05d', $tilaus_tiedot->laskunro) ."-{$tilaus_tiedot->kayttaja_id}.pdf";
?>
<!DOCTYPE html>
<html lang="fi">
<head>
	<meta charset="UTF-8">
	<title>Tilaus-info</title>
	<link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
	<link rel="stylesheet" href="css/styles.css">
</head>
<body>

<!-- Tiedoston latausta varten -->
<form id="download_lasku" method="post" action="download.php">
    <input type="hidden" name="filepath" value="./tilaukset/<?= $lasku_file_nimi ?>">
</form>
<form id="download_noutolista" method="post" action="download.php">
    <input type="hidden" name="filepath" value="./tilaukset/<?= $noutolista_file_nimi ?>">
</form>

<?php include 'header.php'; ?>

<main class="main_body_container">
	<section style="white-space: nowrap">
		<div class="otsikko">
            <h1 class="inline-block" style="margin-right: 35pt">Tilauksen tiedot</h1>
			<?php if ( $tilaus_tiedot->maksettu == false ) : ?>
				<span class="inline-block" style="color:red;"> Odottaa maksua. Lasku ei saatavilla. </span>
				<!-- //TODO: Tilauksen peruutus ylläpitäjän toimesta tähän? -->
            <?php elseif ( $tilaus_tiedot->maksettu == -1 ) : ?>
                <span class="inline-block" style="color:red;font-weight: bold">Tilaus peruttu. Maksua ei suoritettu.</span>
			<?php elseif ( $tilaus_tiedot->kasitelty == false ) : ?>
				<span class="inline-block" style="color:red;"> Odottaa käsittelyä. </span>
			<?php else: ?>
				<span class="inline-block" style="color:green;"> Käsitelty ja toimitettu. </span>
			<?php endif; ?>
		</div>
		<div id="painikkeet">
            <?php if ( $tilaus_tiedot->maksettu ) : ?>
	            <!-- //TODO: Miksei se form ole suoraan tässä? -->
	            <a href="#" onclick="document.getElementById('download_lasku').submit()"
                   class="nappi">Lasku</a>
                <?php if ( $user->isAdmin() ) : ?>
	                <a href="#" onclick="document.getElementById('download_noutolista').submit()"
	                   class="nappi">Noutolista</a>
				<?php endif; ?>
            <?php endif; ?>
		</div>
	</section>

	<div class="flex_row">

		<table class='tilaus_info'>
			<tr><td>Tilausnumero: <?= sprintf('%04d', $tilaus_tiedot->id)?></td>
				<td>Päivämäärä: <?= date("d.m.Y", strtotime($tilaus_tiedot->paivamaara))?></td></tr>
			<tr><td>Tilaaja: <?= "{$tilaus_tiedot->etunimi} {$tilaus_tiedot->sukunimi}" ?></td>
				<td>Yritys: <?= $tilaus_tiedot->yritys?></td></tr>
			<tr><td>Tuotteet: <?= $tilaus_tiedot->kpl?></td>
				<td>Summa:
					<?= format_number( $tilaus_tiedot->summa+$tilaus_tiedot->pysyva_rahtimaksu ) ?>
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
			<tr> <th>Tuotenumero</th> <th>Tuote</th> <th>Valmistaja</th> <th class="number">Hinta (yht.)</th>
				<th class="number">Kpl-hinta</th> <th class="number">ALV-%</th> <th class="number">Alennus</th>
				<th class="number">Kpl</th> </tr>
		</thead>
		<tbody>
		<?php foreach ( $tuotteet as $tuote) : ?>
			<tr>
				<td><?= $tuote->tuotekoodi ?></td>
				<td><?= $tuote->nimi ?></td>
				<td><?= $tuote->valmistaja ?></td>
				<td class="number"><?= $tuote->summa_toString() ?></td>
				<td class="number"><?= $tuote->a_hinta_toString() ?></td>
				<td class="number"><?= $tuote->alv_toString() ?></td>
				<td class="number">
					<?=((float)$tuote->alennus_prosentti!=0) ? (round($tuote->alennus_prosentti*100)." %") : ("---")?>
				</td>
				<td class="number"><?= $tuote->kpl_maara?></td>
			</tr>
		<?php endforeach; ?>
			<tr style="background-color:#cecece;">
				<td>---</td>
				<td>Rahtimaksu</td>
				<td></td>
				<td class="number"></td>
				<td class="number"><?= format_number( $tilaus_tiedot->pysyva_rahtimaksu ) ?></td>
				<td class="number">24 %</td>
				<td class="number"><?= ($tilaus_tiedot->pysyva_rahtimaksu==0) ? "Ilmainen toimitus" : "---" ?></td>
				<td class="number">1</td>
			</tr>
		</tbody>
	</table>
</main>

<?php require 'footer.php'; ?>

</body>
</html>
