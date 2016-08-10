<!doctype html>
<html lang="fi">
<head>
	<meta charset="UTF-8">
	<title>Laskutustesti</title>
	<link rel="stylesheet" href="css/styles.css">
	<style>
		.class #id tag {}


		html{ position:relative; min-height: 100%; width:100%; /*Varmuuden varalle...*/ }

		html,body{ background-color: white; /* The searing white is destroying my eyes */ }

		body{ font-family: Helvetica, 'Open Sans', sans-serif; }

		.lasku_body {
			/*border: solid 1px;*/
		}

		#laskun_perustiedot {
			display: flex;
		}
		.perustiedot_subsection {
			display: flex;
			flex-direction: column;
			flex-grow: 1;
		}

		#lasku_logo {
			margin: auto;
			/*padding-bottom: 30px;*/
		}
		#lasku_toimitusosoite {
			margin: auto;
		}
		#tuotteet {
			border-top: 1px solid;
			border-bottom: 1px solid;
		}
		#tuotteet .table_jakaja {
			width: 100%;
		}
		#maksutiedot {
			display: flex;
		}

		table {
			margin: 1px;
		}
		thead td {
			font-weight: bold;
			padding-bottom: 0;
			background: #ccc;
		}
		h3, h4 {
			margin-bottom: 0;
		}

		.grow_2 { flex-grow: 2; }
		.bold { font-weight: bold; }
	</style>
</head>
<body>
<?php
require './laskutiedot.class.php';
require './tietokanta.php';
date_default_timezone_set('Europe/Helsinki');

$lasku = new Laskutiedot( $db );

if ( !empty($_POST['tilaus']) || !empty($_GET['getLaskuID']) ) {
	$lasku->haeTilauksenTiedot( (isset($_POST['tilaus']) ? $_POST['tilaus'] : $_GET['getLaskuID']) );
	$lasku_S = $lasku->tulostaLasku();
}

$asiakas = $lasku->getAsiakas();
$yritys = $lasku->getYritys();
$tmo = $lasku->getToimitusosoite();
$tuotteet = $lasku->getTuotteet();
$hinta = $lasku->getHintatiedot();
$i = 0; //Tuotteiden numerointia varten
?>

<header>
	<h3>Alustava laskutietojen etsintä, ja testaus. WIP.</h3>
	<form action="#" method="post">
		<label>Anna tilauksen numero, ja klikkaa Hae-nappia. (Numero on luultavasti jotain ykkösen luokkaa)</label><br>
		<input type="number" name="tilaus" value="0" min="0" title="Tilausnumero">
		<input type="submit" value="Hae laskun tiedot">
	</form><br>
	<hr>
</header>

<main class="main_body_container">
	<div class="lasku_body">
		<section id="laskun_perustiedot">
			<div class="perustiedot_subsection">
				<div id="lasku_logo">
					<h1>LOGO</h1>
				</div>
				<div id="lasku_toimitusosoite">
					<h4>Toimitusosoite</h4>
					<?= $tmo->koko_nimi ?><br>
					<?= $tmo->katuosoite ?><br>
					<?= "{$tmo->postinumero} {$tmo->postitoimipaikka}" ?><br>
					<?= $tmo->puhelin ?><br>
					<?= $tmo->sahkoposti ?><br>
				</div>
			</div>
			<div class="perustiedot_subsection grow_2" style="margin-left: 10px;">
				<div id="tilaus_id_tiedot">
					<table>
						<thead>
						<tr><td>Päivämäärä</td><td>Tilauspvm</td>
							<td class="number">Tilausnro</td><td class="number">Asiakasnro</td></tr></thead>
						<tbody>
							<tr><td><?= date("d.m.Y")?></td><td><?= $lasku->tilaus_pvm?></td>
								<td class="number"><?= sprintf('%04d', $lasku->tilaus_nro)?></td>
								<td class="number"><?= sprintf('%04d', $asiakas->id)?></td></tr>
						</tbody>
					</table>
				</div>
				<div id="maksaja">
					<h3>Laskun maksajan tiedot</h3>
					<h4>Asiakas</h4>
					<?= $asiakas->koko_nimi ?><br>
					<?= "{$asiakas->puhelin} // {$asiakas->sahkoposti}" ?><br>
					<h4>Yritys</h4>
					<?= "{$yritys->yritysnimi}, {$yritys->y_tunnus}" ?><br>
					<?= "{$yritys->postinumero} {$yritys->postitoimipaikka}" ?><br>
					<?= "{$yritys->puhelin} // {$yritys->sahkoposti}" ?><br>
				</div>
			</div>
		</section>
		<section id="tuotteet">
			<div class="flex">
				<h3 style="margin:5px 65px 5px 65px;">TILATUT TUOTTEET</h3>
				<span class="small_note" style="margin:auto auto auto 0;">Kaikki hinnat sis. ALV</span>
			</div>
			<table class="lasku_tuotteet">
				<thead>
				<tr><td class="number">#</td>
					<td>Tuotekoodi</td>
					<td>Nimi</td>
					<td class="table_jakaja">Valmistaja</td>
					<td class="number">A-hinta</td>
					<td class="number">ALV</td>
					<td class="number">Kpl</td>
					<td class="number">Summa</td>
				</thead>
				<tbody>
				<?php foreach ($tuotteet as $tuote) : ?>
					<tr><td class="number"><?= sprintf('%02d', $i+1)?></td>
						<td><?= $tuote->tuotekoodi ?></td>
						<td><?= $tuote->tuotenimi ?></td>
						<td><?= $tuote->valmistaja ?></td>
						<td class="number"><?= $tuote->a_hinta ?></td>
						<td class="number"><?= $tuote->alv_prosentti ?> %</td>
						<td class="number"><?= $tuote->kpl_maara ?></td>
						<td class="number"><?= $tuote->summa ?></td>
					</tr>
					<?php $i++; ?>
				<?php endforeach ?>
				</tbody>
			</table>
		</section>
		<section id="maksutiedot">
			<div class="flex grow_2">
			</div>
			<div class="flex">
				<table style="margin-right: 50px;">
					<thead>
					<tr><td class="number">ALV-perus</td><td class="number">ALV-määrä</td></tr>
					</thead>
					<tbody>
					<tr><td class="number"><?= $hinta['alv_perus']?></td>
						<td class="number"><?= $hinta['alv_maara']?></td></tr>
					</tbody>
				</table>
				<table>
					<thead><tr><td colspan="2" style="text-align: center;">LOPPUSUMMA</td></tr></thead>
					<tbody>
					<tr><td class="bold">Tuotteet yhteensä:</td>
						<td class="number"><?= $hinta['tuotteet_yht']?></td></tr>
					<tr><td class="bold">Lisäveloitukset:</td>
						<td class="number"><?= $hinta['lisaveloitukset']?></td></tr>
					<tr><td class="bold">Summa yhteensä:</td>
						<td class="number"><?= $hinta['summa_yhteensa']?></td></tr>
					</tbody>
				</table>
			</div>
		</section>
	</div>

</main>
<body>
<html>

