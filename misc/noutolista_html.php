<?php
$pdf_noutolista_html_header = "
	<div style='font-weight:bold;text-align:center;'> Osax Oy :: Tilauksen noutolista </div>";
$pdf_noutolista_html_footer = '
	<table width="100%" style="font-size:9pt;">
		<tr>
			<td width="33%">{DATE j-m-Y}</td>
			<td width="33%" align="center" style="font-weight:bold; font-size:10pt;">{PAGENO}/{nbpg}</td>
			<td width="33%" align="right">Noutolista</td>
		</tr>
	</table>';

/**
 * Noutolistan alkuosa. Logo ja laskun tiedot. Sen jälkeen tuotetaulukon header row.
 */
$pdf_noutolista_html_body = "
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
			<tr><td>".date('d.m.Y')."</td>
				<td style='text-align: center;'>$lasku->tilaus_pvm</td>
				<td style='text-align:right;'>".sprintf('%04d', $lasku->tilaus_nro)."</td>
				<td style='text-align:right;'>".sprintf('%04d', $lasku->asiakas->id)."</td>
			</tr>
			</tbody>
		</table></td>
	</tr>
	<tr><th>Toimitusosoite</th><th>Asiakkaan tiedot</th></tr>
	<tr><td>{$lasku->asiakas->kokoNimi()}<br>
			{$lasku->toimitusosoite["katuosoite"]}<br>
			{$lasku->toimitusosoite["postinumero"]} {$lasku->toimitusosoite["postitoimipaikka"]}<br></td>
		<td>{$lasku->asiakas->kokoNimi()}<br>
			{$lasku->asiakas->puhelin}, {$lasku->asiakas->sahkoposti}<br>
			{$lasku->asiakas->yrityksen_nimi}</td></tr>
	</tbody>
</table>
<hr>"
. ($_SESSION['indev'] ? "<span style='color:red;'>dev.osax: tämä noutolista tarkoitettu vain testaukseen.</span><hr>" : "")
. "
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
foreach ( $lasku->tuotteet as $tuote ) {
	$pdf_noutolista_html_body .= "
		<tr><td style='text-align:right;'>".sprintf('%03d', $i++)."</td>
			<td style='text-align:center;'>{$tuote->tuotekoodi}</td>
			<td style='text-align:center;'>{$tuote->nimi}</td>
			<td style='text-align:center;'>{$tuote->valmistaja}</td>
			<td style='text-align:right;'>{$tuote->kpl_maara}</td>
			<td style='text-align:center;'>{$tuote->hyllypaikka}</td>
		</tr>";
}

$pdf_noutolista_html_body .= "
	</tbody>
</table>
<hr>
";
