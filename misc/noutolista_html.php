<?php declare(strict_types=1);
/**
 * @var $lasku \Lasku
 * @var $config array
 */
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
$pdf_noutolista_html_body_begin = "
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
				<td style='text-align: center;'>$lasku->paivamaara</td>
				<td style='text-align:right;'>".sprintf('%04d', $lasku->id)."</td>
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
. ($config['indev'] ? "<span style='color:red;'>dev.osax: tämä noutolista tarkoitettu vain testaukseen.</span><hr>" : "")
. "
<table style='width:100%;font-size:80%;'>
	<thead>
	<tr><th colspan='6'><h2>Noutolista &mdash; tilatut tuotteet</h2></th></tr>
	<tr><th style='text-align:right;'>#</th>
		<th style='text-align:left;'>Tuotekoodi</th>
		<th style='text-align:left;'>Nimi</th>
		<th style='text-align:left;'>Valmistaja</th>
		<th style='text-align:right;'>kpl</th>
		<th style='text-align:right;'>Hyllypaikka</th>
	</tr>
	</thead>
	<tbody>
";

$pdf_noutolista_tuotteet = "";
$pdf_noutolista_tilaustuotteet = "";

/**
 * Lisätään tuotteiden tiedot
 */
$i = 1; // Tuotteiden juoksevaa numerointia varten laskussa.
foreach ( $lasku->tuotteet as $tuote ) {
	if ( !$tuote->tilaustuote ) {
		$pdf_noutolista_tuotteet .= "
		<tr><td style='text-align:right;'>" . sprintf('%03d', $i++) . "</td>
			<td style='text-align:left;'>{$tuote->tuotekoodi}</td>
			<td style='text-align:left;'>{$tuote->nimi}</td>
			<td style='text-align:left;'>{$tuote->valmistaja}</td>
			<td style='text-align:right;'>{$tuote->kpl_maara}</td>
			<td style='text-align:right;'>{$tuote->hyllypaikka}</td>
		</tr>";
	}
}

$i = 1; // Tuotteiden juoksevaa numerointia varten laskussa.
foreach ( $lasku->tuotteet as $tuote ) {
	if ( $tuote->tilaustuote ) {
		$pdf_noutolista_tilaustuotteet .= "
		<tr><td style='text-align:right;'>" . sprintf('%03d', $i++) . "</td>
			<td style='text-align:left;'>{$tuote->tuotekoodi}</td>
			<td style='text-align:left;'>{$tuote->nimi}</td>
			<td style='text-align:left;'>{$tuote->valmistaja}</td>
			<td style='text-align:right;'>{$tuote->kpl_maara}</td>
			<td style='text-align:right;'>{$tuote->hyllypaikka}</td>
		</tr>";
	}
}

$pdf_noutolista_html_body_end = "
	</tbody>
</table>
<hr>
";

$pdf_noutolista_tehdastilaus_otsikko = "
	<div style='font-weight:bold;text-align:center;'> TEHDASTILAUS </div>";

/**
 * Noutolistan body
 */
$pdf_noutolista_tehdastilaus_html_body =
	$pdf_noutolista_tehdastilaus_otsikko . $pdf_noutolista_html_body_begin .
	$pdf_noutolista_tilaustuotteet . $pdf_noutolista_html_body_end;

$pdf_noutolista_html_body =
	$pdf_noutolista_html_body_begin . $pdf_noutolista_tuotteet . $pdf_noutolista_html_body_end;