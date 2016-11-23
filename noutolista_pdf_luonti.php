<?php
require './mpdf/mpdf.php';

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
	<thead>
	<tr><th colspan='2'>Tilauksen tiedot</th></tr>
	<tr><th>Tilaus ID</th><th>Asiakkaan ID</th></tr>
	</thead>
	<tbody>
	<tr><td></td><td></td></tr>
	</tbody>
</table>
<hr>
<table style='width:100%;font-size:80%;'>
	<thead>
	<tr><th colspan='8' class='center'><h2>Noutolista &mdash; tilatut tuotteet</h2></th></tr>
	<tr><th style='text-align:right;'>#</th>
		<th>Tuotekoodi</th>
		<th>Nimi</th>
		<th>Valmistaja</th>
		<th style='text-align:right;'>kpl</th>
		<th>Hyllypaikka</th>
	</thead>
	<tbody>
";

/**
 * Lisätään tuotteiden tiedot
 */
$i = 1; // Tuotteiden juoksevaa numerointia varten laskussa.
foreach ( $products as $tuote ) {
	$html .= "
		<tr><td style='text-align:right;'>".sprintf('%03d', $i++)."</td>
			<td>{$tuote->tuotekoodi}</td>
			<td>{$tuote->tuotenimi}</td>
			<td>{$tuote->valmistaja}</td>
			<td style='text-align:right;'>{$tuote->a_hinta_toString( true )}</td>
			<td style='text-align:right;'>{$tuote->alv_prosentti} %</td>
			<td style='text-align:right;'>{$tuote->alennus} %</td>
			<td style='text-align:right;'>{$tuote->kpl_maara}</td>
			<td style='text-align:right;'>{$tuote->summa_toString( true )}</td>
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

if ( !file_exists('./laskut') ) { // Tarkistetaan, että kansio on olemassa.
	mkdir( './laskut' ); // Jos ei, luodaan se ja jatketaan eteenpäin.
}

$tiedoston_nimi = "lasku-{$lasku->tilaus_nro}-{$user->id}.pdf";
$mpdf->Output( "./laskut/{$tiedoston_nimi}", 'F' );
