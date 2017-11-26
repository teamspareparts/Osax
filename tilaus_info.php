<?php declare(strict_types=1);
require '_start.php'; global $db, $user, $cart;

/**
 * Hakee yleiset tiedot tilauksesta, käyttäjän tiedot (mukaan lukien yrityksen nimi), toimitusosoitteet,
 *  ja tilaukset loppusumman ja kpl-määrän. Ei hae tuotteita. Käyttäjän tiedot erikseen, eikä $user-oliosta,
 *  jotta ylläpitäjä voi käyttää sivua.
 * @param DByhteys $db
 * @param int      $tilaus_id
 * @return stdClass <p> tilauksen tiedot, pois lukien tuotteet
 */
function hae_tilauksen_tiedot ( DByhteys $db, int $tilaus_id ) {
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
			WHERE tilaus.id = ?
			GROUP BY tilaus.id";

	return $db->query( $sql, [ $tilaus_id ] );
}

/**
 * Hakee, ja palauttaa tilaukseen liitettyjen tuotteiden tiedot.
 * @param DByhteys $db
 * @param int      $tilaus_id
 * @return Tuote[] <p> Tiedot tilatuista tuotteista.
 */
function hae_tilauksen_tuotteet( DByhteys $db, int $tilaus_id ) : array {
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

if ( !empty($_POST['peruuta_id']) ) {
	require './luokat/paymentAPI.class.php';

	// Yes, yes, voisi tehdä tehokkaammin, I know, I'm just lazy.
	$kayttaja = new User( $db, $_POST['user_id'] );
	$ostoskori = new Ostoskori( $db, $kayttaja->yritys_id, -1 );

	PaymentAPI::peruutaTilausPalautaTuotteet( $db, $kayttaja, $_POST['peruuta_id'], $ostoskori->ostoskori_id );
}

if ( !isset( $_GET[ 'id' ] ) ) {
	header( "Location:tilaushistoria.php?id={$user->id}" );
	exit();
}

$tilaus_tiedot = hae_tilauksen_tiedot( $db, (int)$_GET[ 'id' ] );

// Löytyikö tilauksen tiedot ID:llä.
if ( !$tilaus_tiedot ) {
	header( "Location:tilaushistoria.php" );
	exit();
}

// Tarkistetaan onko tilaus sen hetkisen käyttäjän tekemä, tai onko käyttäjä admin.
// Lähetään pois, jos ei kumpaankin.
elseif ( !($tilaus_tiedot->kayttaja_id == $user->id) && !$user->isAdmin() ) {
	header( "Location:tilaushistoria.php" );
	exit();
}

/** @var Tuote[] $tuotteet <p> Tilauksen tuotteet */
$tuotteet = hae_tilauksen_tuotteet( $db, $tilaus_tiedot->id );

$lasku_file_nimi = "lasku-". sprintf( '%05d', $tilaus_tiedot->laskunro) ."-{$tilaus_tiedot->kayttaja_id}.pdf";
$noutolista_file_nimi = "noutolista-".sprintf('%05d',$tilaus_tiedot->laskunro)."-{$tilaus_tiedot->kayttaja_id}.pdf";

/** Tarkistetaan feedback, ja estetään formin uudelleenlähetys */
if ( !empty( $_POST ) ) { //Estetään formin uudelleenlähetyksen
	header( "Location: " . $_SERVER[ 'REQUEST_URI' ] ); exit();
} else {
	$feedback = isset( $_SESSION[ 'feedback' ] ) ? $_SESSION[ 'feedback' ] : "";
	unset( $_SESSION[ "feedback" ] );
}
?>
<!DOCTYPE html>
<html lang="fi">
<head>
	<meta charset="UTF-8">
	<title>Tilaus-info</title>
	<link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
	<link rel="stylesheet" href="./css/jsmodal-light.css">
	<link rel="stylesheet" href="./css/styles.css">
	<script src="./js/jsmodal-1.0d.min.js" async></script>
</head>
<body>

<?php include 'header.php'; ?>

<main class="main_body_container">
	<div class="otsikko_container">
		<section class="takaisin">
			<?php if ( $user->isAdmin() ) : ?>
				<a href="javascript:history.go(-1)" class="nappi grey">
					<i class="material-icons">navigate_before</i>Takaisin</a>
			<?php else : ?>
				<a href="tilaushistoria.php" class="nappi grey">
					<i class="material-icons">navigate_before</i>Tilaushistoriaan</a>
			<?php endif; ?>
		</section>
		<section class="otsikko">
			<h1>Tilaus <?=sprintf('%04d', $tilaus_tiedot->id)?> &nbsp;&nbsp;</h1>
			<?php if ( $tilaus_tiedot->maksettu == false ) : ?>
				<span class="inline-block" style="color:orangered;"> Odottaa maksua. Lasku ei saatavilla. </span>
				<?php if ( $user->isAdmin() ) : ?>
					<button class="nappi red" id="peruuta_tilaus">Peruuta tilaus?</button>
				<?php endif; ?>
			<?php elseif ( $tilaus_tiedot->maksettu == -1 ) : ?>
				<span class="inline-block" style="color:red;font-weight: bold">Tilaus peruttu. Maksua ei suoritettu.</span>
			<?php elseif ( $tilaus_tiedot->kasitelty == false ) : ?>
				<span class="inline-block" style="color:steelblue;"> Odottaa käsittelyä. </span>
			<?php else: ?>
				<span class="inline-block" style="color:green;"> Käsitelty ja toimitettu. </span>
			<?php endif; ?>
		</section>
		<section class="napit">
			<?php if ( $tilaus_tiedot->maksettu AND !is_null($tilaus_tiedot->laskunro) ) : ?>
				<!-- Laskun lataus -->
				<form method="post" action="download.php" class="inline-block">
					<input type="hidden" name="filepath" value="./tilaukset/<?= $lasku_file_nimi ?>">
					<button type="submit" class="nappi">Lasku <i class="material-icons">file_download</i></button>
				</form>
				<?php if ( $user->isAdmin() ) : ?>
					<!-- Noutolistan lataus -->
					<form method="post" action="download.php" class="inline-block">
						<input type="hidden" name="filepath" value="./tilaukset/<?= $noutolista_file_nimi ?>">
						<button type="submit" class="nappi">Noutolista <i class="material-icons">file_download</i></button>
					</form>
				<?php endif; ?>
			<?php endif; ?>
		</section>
	</div>

	<?= $feedback ?>

	<div class="flex_row">

		<div class="table white-bg">
			<div class="tr">
				<div class="td pad">Tilausnumero: <?= sprintf('%04d', $tilaus_tiedot->id)?></div>
				<div class="td pad">Päivämäärä: <?= date("d.m.Y", strtotime($tilaus_tiedot->paivamaara))?></div>
			</div>
			<div class="tr">
				<div class="td pad">Tilaaja: <?= "{$tilaus_tiedot->etunimi} {$tilaus_tiedot->sukunimi}" ?></div>
				<div class="td pad">Yritys: <?= $tilaus_tiedot->yritys?></div>
			</div>
			<div class="tr">
				<div class="td pad">Tuotteet: <?= $tilaus_tiedot->kpl?></div>
				<div class="td pad">Summa:
					<?= format_number( $tilaus_tiedot->summa+$tilaus_tiedot->pysyva_rahtimaksu ) ?>
					( ml. rahtimaksu )
				</div>
			</div>
			<div>
				<p class="small_note">Kaikki hinnat sisältävät ALV:n</p>
			</div>
		</div>

		<div class="white-bg" style="padding-left: 10px;">
			<p style="font-weight: bold;">Toimitusosoite</p>
			<p style="margin:10px;"><?=$tilaus_tiedot->tmo_koko_nimi . ', ' . $tilaus_tiedot->yritys?></p>
			<p style="margin:10px;"><?=$tilaus_tiedot->tmo_osoite?></p>
			<p style="margin:10px;"><?="{$tilaus_tiedot->tmo_puhelin}, {$tilaus_tiedot->tmo_sahkoposti}"?></p>
		</div>
	</div>
	<br>
	<table width="100%">
		<thead>
			<tr><th colspan="8" class="center" style="background-color:#1d7ae2;">Tilatut tuotteet</th></tr>
			<tr> <th>Tuotenumero</th> <th>Tuote</th> <th>Valmistaja</th> <th class="number">Kpl-hinta</th> <th class="number">Kpl</th> <th class="number">Hinta (yht.)</th> <th class="number">ALV-%</th> <th class="number">Alennus</th>
			</tr>
		</thead>
		<tbody>
		<?php foreach ( $tuotteet as $tuote) : ?>
			<tr>
				<td><?= $tuote->tuotekoodi ?></td>
				<td><?= $tuote->nimi ?></td>
				<td><?= $tuote->valmistaja ?></td>
				<td class="number"><?= $tuote->a_hinta_toString() ?></td>
				<td class="number"><?= $tuote->kpl_maara?></td>
				<td class="number"><?= $tuote->summa_toString() ?></td>
				<td class="number"><?= $tuote->alv_toString() ?></td>
				<td class="number">
					<?=((float)$tuote->alennus_prosentti!=0) ? (round($tuote->alennus_prosentti*100)." %") : ("---")?>
				</td>
			</tr>
		<?php endforeach; ?>
			<tr style="background-color:#cecece;">
				<td>---</td>
				<td>Rahtimaksu</td>
				<td></td>
				<td class="number"><?= format_number( $tilaus_tiedot->pysyva_rahtimaksu ) ?></td>
				<td></td>
				<td class="number"><?= format_number( $tilaus_tiedot->pysyva_rahtimaksu ) ?></td>
				<td class="number">24 %</td>
				<td class="number"><?= ($tilaus_tiedot->pysyva_rahtimaksu==0) ? "Ilmainen toimitus" : "---" ?></td>
			</tr>
		</tbody>
	</table>
</main>

<?php require 'footer.php'; ?>

<?php if ($user->isAdmin()) : ?>
	<script async>
		let peruuta_nappi = document.getElementById('peruuta_tilaus');
		let tilaus_id = <?=$tilaus_tiedot->id?>;
		let user_id = <?=$tilaus_tiedot->kayttaja_id?>;

		peruuta_nappi.addEventListener('click', function() {
			Modal.open({
				content: `
					<div>
						<h2>Oletko varma, että haluat peruuttaa tilauksen?</h2>
						<h3>Tämä palauttaa tuotteet asiakkaan ostoskoriin,<br>
							ja merkitsee tilauksen peruutetuksia.</h3>
						<h3>Huom. Tilaus voi silti olla mahdollisesti maksettu!</h3>
						<button class="nappi grey" onclick="Modal.close();">Palaa takaisin. Älä peruuta tilausta.</button>
						<br><br>
						<form method="post">
							<input type="hidden" name="peruuta_id" value="${tilaus_id}">
							<input type="hidden" name="user_id" value="${user_id}">
							<input type="Submit" value="Poista tilaus." class="nappi red">
						</form>
					</div>
				`,
				draggable: true
			});
		});
	</script>
<?php endif; ?>
</body>
</html>
