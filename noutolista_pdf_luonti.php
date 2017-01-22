<?php
require_once './mpdf/mpdf.php';

$mpdf = new mPDF();

/** ////////////////////////////////////////////////////////////////////// */
/** PDF:n HTML:n kirjoitus */
/** ////////////////////////////////////////////////////////////////////// */
/**
 * Noutolistan alkuosa. Logo ja laskun tiedot. Sen jälkeen tuotetaulukon header row.
 */
$html = "
<div style='width:100%;'><img src='img/osax_logo.jpg' alt='Osax.fi'></div>

<table style='width:100%;'>
	<tbody>
	<tr><td colspan='2'>
		<table style='width:70%;padding:15px;'>
			<thead>
			<tr><th>Päivämäärä</th>
				<th>Tilauspvm</th>
				<th style='text-align:right;'>Tilaus</th>
				<th style='text-align:right;'>Asiakas</th>
			</tr>
			</thead>
			<tbody>
			<tr><td>".date('d.m.Y')."</td><td style='text-align: center'>$lasku->tilaus_pvm</td>
				<td style='text-align:right;'>".sprintf('%04d', $lasku->tilaus_nro)."</td>
				<td style='text-align:right;'>".sprintf('%04d', $lasku->asiakas->id)."</td>
			</tr>
			</tbody>
		</table></td>
	</tr>
	<tr><th>Toimitusosoite</th><th>Asiakkaan tiedot</th></tr>
	<tr><td>{$lasku->toimitusosoite->koko_nimi}<br>
			{$lasku->toimitusosoite->katuosoite}<br>
			{$lasku->toimitusosoite->postinumero} {$lasku->toimitusosoite->postitoimipaikka}<br></td>
		<td>{$lasku->asiakas->kokoNimi()}<br>
			{$lasku->asiakas->puhelin}, {$lasku->asiakas->sahkoposti}<br>
			{$lasku->asiakas->yrityksen_nimi}</td></tr>
	</tbody>
</table>
<hr>
<table style='width:100%;font-size:80%;'>
	<thead>
	<tr><th colspan='6'><h2>Noutolista &mdash; tilatut tuotteet</h2></th></tr>
	<tr><th style='text-align:right;'>#</th>
		<th>Tuotekoodi</th>
		<th>Nimi</th>
		<th>Valmistaja</th>
		<th style='text-align:right;'>kpl</th>
		<th>Hyllypaikka</th>
	</tr>
	</thead>
	<tbody>
";

/**
 * Lisätään tuotteiden tiedot
 */
$i = 1; // Tuotteiden juoksevaa numerointia varten laskussa.
foreach ( $cart->tuotteet as $tuote ) {
	$html .= "
		<tr><td style='text-align:right;'>".sprintf('%03d', $i++)."</td>
			<td style='text-align:center;'>{$tuote->tuotekoodi}</td>
			<td style='text-align:center;'>{$tuote->nimi}</td>
			<td style='text-align:center;'>{$tuote->valmistaja}</td>
			<td style='text-align:right;'>{$tuote->kpl_maara}</td>
			<td style='text-align:center;'>{$tuote->hyllypaikka}</td>
		</tr>";
}

$html .= "
	</tbody>
</table>
<hr>
";

/** //////////////////////////////////////// */
/** PDF:n luonti */
/** //////////////////////////////////////// */
/*
 * PDF-header ja footer
 * Header: "Osax Oy :: Noutotilaus" keskitettynä
 * Footer: "[Päivämäärä] - [sivunumero] - Noutotilaus"
 */
$mpdf->SetHTMLHeader('<div style="font-weight:bold;text-align:center;">Osax Oy :: Noutotilaus</div>');
$mpdf->SetHTMLFooter('
<table width="100%" style="vertical-align:bottom; font-family:serif; font-size:8pt; color:#000000; font-weight:bold; font-style:italic;"><tr>
	<td width="33%"><span style="font-weight:bold; font-style:italic;">{DATE j-m-Y}</span></td>
	<td width="33%" align="center" style="font-weight:bold; font-style:italic;">{PAGENO}/{nbpg}</td>
	<td width="33%" style="text-align:right; ">Noutolista</td>
</tr></table>
');

$mpdf->WriteHTML( $html ); // Kirjoittaa HTML:n tiedostoon.



if ( !file_exists('./noutolistat') ) { // Tarkistetaan, että kansio on olemassa.
	mkdir( './noutolistat' ); // Jos ei, luodaan se ja jatketaan eteenpäin.
}

$tiedoston_nimi = "noutolista-{$lasku->tilaus_nro}-{$user->id}.pdf";
$mpdf->Output( "./noutolistat/{$tiedoston_nimi}", 'F' );
