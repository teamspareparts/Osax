<?php declare(strict_types=1);
require '_start.php'; global $db, $user, $cart;

$tilaus_id = isset( $_GET[ 'id' ] ) ? (int)$_GET[ 'id' ] : null;
if ( !$tilaus_id ) {
	header( "Location:tilaushistoria.php?id={$user->id}" );
	exit();
}

if ( !empty($_POST['peruuta_id']) ) {
	require './luokat/paymentAPI.class.php';
	if ( !$user->isAdmin() ) {
		return;
	}

	// Yes, yes, voisi tehdä tehokkaammin, I know, I'm just lazy.
	$kayttaja = new User( $db, (int)$_POST['user_id'] );
	$ostoskori = new Ostoskori( $db, $kayttaja->yritys_id, -1 );

	PaymentAPI::peruutaTilausPalautaTuotteet( $db, $kayttaja, (int)$_POST['peruuta_id'], $ostoskori->ostoskori_id );
}

$tilaus = new Laskutiedot($db, $tilaus_id);

// Löytyikö tilauksen tiedot ID:llä.
if ( !$tilaus ) {
	header( "Location:tilaushistoria.php" );
	exit();
}

// Tarkistetaan onko tilaus sen hetkisen käyttäjän tekemä, tai onko käyttäjä admin.
// Lähetään pois, jos ei kumpaankin.
elseif ( ($tilaus->asiakas->id != $user->id) && !$user->isAdmin() ) {
	header( "Location:tilaushistoria.php" );
	exit();
}

$lasku_file_nimi = "lasku-". sprintf( '%05d', $tilaus->laskunro) ."-{$tilaus->asiakas->id}.pdf";
$noutolista_file_nimi = "noutolista-".sprintf('%05d',$tilaus->laskunro)."-{$tilaus->asiakas->id}.pdf";

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
			<h1 class="inline-block">Tilaus <?=sprintf('%04d', $tilaus->tilaus_nro)?> </h1>
			<!-- Tilauksen tila -->
			<?php if ( $tilaus->maksettu == 0 ) : ?>
				<span style="color:orangered;"> Odottaa maksua. Lasku ei saatavilla. </span>
				<?php if ( $user->isAdmin() ) : ?>
					<button class="nappi red" id="peruuta_tilaus">Peruuta tilaus?</button>
				<?php endif; ?>
			<?php elseif ( $tilaus->maksettu == -1 ) : ?>
				<span style="color:red;font-weight: bold">Tilaus peruttu. Maksua ei suoritettu.</span>
			<?php elseif ( $tilaus->kasitelty == false ) : ?>
				<span style="color:steelblue;"> Odottaa käsittelyä. </span>
			<?php else: ?>
				<span style="color:green;"> Käsitelty ja toimitettu. </span>
			<?php endif; ?>
		</section>
		<section class="napit">
			<?php if ( $tilaus->maksettu AND !is_null($tilaus->laskunro) ) : ?>
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

	<?= $feedback?>

	<div class="flex_row">

		<table class="white-bg">
			<tr>
				<td>Tilausnumero: <?= sprintf('%04d', $tilaus->tilaus_nro)?></td>
				<td>Päivämäärä: <?= date("d.m.Y", strtotime($tilaus->tilaus_pvm))?></td>
			</tr>
			<tr>
				<td>Tilaaja: <?= $tilaus->asiakas->etunimi?> <?= $tilaus->asiakas->sukunimi?></td>
				<td>Yritys: <?= $tilaus->yritys->nimi?></td>
			</tr>
			<tr>
				<td>Tuotteet: <?= $tilaus->tuotteet_kpl?></td>
				<td>Summa: <?= format_number( $tilaus->hintatiedot["summa_yhteensa"] )?>
					( ml. rahtimaksu )
				</td>
			</tr>
			<tr>
				<td colspan="2">Maksutapa: <?=$tilaus->maksutapa_toString()?></td>
			</tr>
			<tr>
				<td colspan="2" class="small_note">Kaikki hinnat sisältävät ALV:n</td>
			</tr>
		</table>

		<div class="white-bg" style="padding-left: 10px;">
			<p style="font-weight: bold;">Toimitusosoite</p>
			<p style="margin:10px;"><?= $tilaus->toimitusosoite["koko_nimi"]?>, <?= $tilaus->toimitusosoite["yritys"]?></p>
			<p style="margin:10px;"><?= $tilaus->toimitusosoite["katuosoite"]?></p>
			<p style="margin:10px;"><?= $tilaus->toimitusosoite["postinumero"]?> <?= $tilaus->toimitusosoite["postitoimipaikka"]?></p>
			<p style="margin:10px;"><?= $tilaus->toimitusosoite["puhelin"]?> <?= $tilaus->toimitusosoite["sahkoposti"]?></p>
		</div>
	</div>

	<br>
	<table width="100%">
		<thead>
			<tr><th colspan="9" class="center" style="background-color:#1d7ae2;">Tilatut tuotteet</th></tr>
			<tr><th>Tuotenumero</th>
				<th>Tuote</th>
				<th>Valmistaja</th>
				<th class="number">Kpl-hinta</th>
				<th class="number">Kpl</th>
				<th class="number">Hinta (yht.)</th>
				<th class="number">ALV-%</th>
				<th class="number">Alennus</th>
				<th></th><!-- Lisäinfot -->
			</tr>
		</thead>
		<tbody>
		<?php foreach ( $tilaus->tuotteet as $tuote ) : ?>
			<tr>
				<td><?= $tuote->tuotekoodi ?></td>
				<td><?= $tuote->nimi ?></td>
				<td><?= $tuote->valmistaja ?></td>
				<td class="number"><?= $tuote->aHinta_toString() ?></td>
				<td class="number"><?= $tuote->kpl_maara?></td>
				<td class="number"><?= $tuote->summa_toString() ?></td>
				<td class="number"><?= $tuote->alv_toString() ?></td>
				<td class="number">
					<?=((float)$tuote->alennus_prosentti!=0) ? (round($tuote->alennus_prosentti*100)." %") : ("---")?>
				</td>
				<td>
					<?php if ( $tuote->tilaustuote ) : ?>
						<span style="color: darkorange">Tilaustuote</span>
					<?php endif; ?>
				</td>
			</tr>
		<?php endforeach; ?>
			<tr style="background-color:#cecece;">
				<td>---</td>
				<td>Rahtimaksu</td>
				<td></td>
				<td class="number"><?= $tilaus->rahtimaksu_toString() ?></td>
				<td></td>
				<td class="number"><?= $tilaus->rahtimaksu_toString() ?></td>
				<td class="number"><?= $tilaus->rahtimaksuALV_toString() ?></td>
				<td class="number"><?= ($tilaus->hintatiedot["rahtimaksu"]==0) ? "Ilmainen toimitus" : "---" ?></td>
				<td></td>
			</tr>
		</tbody>
	</table>
</main>

<?php require 'footer.php'; ?>

<?php if ($user->isAdmin()) : ?>
	<script async>
		let peruuta_nappi = document.getElementById('peruuta_tilaus');
		let tilaus_id = <?=$tilaus->tilaus_nro?>;
		let user_id = <?=$tilaus->asiakas->id?>;

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
