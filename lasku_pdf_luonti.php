<?php
require './mpdf/mpdf.php';
require './luokat/laskutiedot.class.php';

$mpdf = new mPDF();
$lasku = new Laskutiedot( $db, $tilaus_id, $user, $yritys );

/** //////////////////////////////////////// */
/** PDF:n HTML:n kirjoitus */
/** //////////////////////////////////////// */
/**
 * Laskun alkuosa. Logo, laskun tiedot ja osoitetiedot. Sen jälkeen tuotetaulukon header row.
 */
$html = "
<div style='width:100%;'><img src='img/osax_logo.jpg' alt='Osax.fi'></div>
<table style='width:100%;'>
	<tbody>
	<tr><td colspan='2'>
		<table style='width:70%;padding:15px;'>
			<thead><tr><th>Päivämäärä</th>
				<th>Tilauspvm</th>
				<th style='text-align:right;'>Tilaus</th>
				<th style='text-align:right;'>Asiakas</th>
				<th style='text-align:right;'>Lasku</th></tr>
			</thead>
			<tbody>
				<tr><td>".date('d.m.Y')."</td><td>$lasku->tilaus_pvm</td>
					<td style='text-align:right;'>".sprintf('%04d', $lasku->tilaus_nro)."</td>
					<td style='text-align:right;'>".sprintf('%04d', $lasku->asiakas->id)."</td>
					<td style='text-align:right;'>".sprintf('%04d', $lasku->tilaus_nro)."</td>
				</tr>
			</tbody>
		</table></td>
	</tr>
	<tr><td>{$lasku->asiakas}</td><td>{$lasku->yritys}</td></tr>
	<tr><td>{$lasku->toimitusosoite}</td></tr>
	</tbody>
</table>
<hr>
<h2>Tilatut tuotteet</h2>
<table style='width:100%;font-size:80%;'>
	<thead>
	<tr><th style='text-align:right;'>#</th>
		<th>Tuotekoodi</th>
		<th>Nimi</th>
		<th>Valmistaja</th>
		<th style='text-align:right;'>A-hinta<br>(sis ALV)</th>
		<th style='text-align:right;'>ALV</th>
		<th style='text-align:right;'>kpl</th>
		<th style='text-align:right;'>Summa<br>(sis ALV)</th></tr>
	</thead>
	<tbody>
";

/**
 * Lisätään tuotteiden tiedot
 */
$i = 1; // Tuotteiden juoksevaa numerointia varten laskussa.
foreach ( $lasku->tuotteet as $tuote ) {
	$html .= "
		<tr><td style='text-align:right;'>".sprintf('%03d', $i++)."</td>
			<td>{$tuote->tuotekoodi}</td>
			<td>{$tuote->tuotenimi}</td>
			<td>{$tuote->valmistaja}</td>
			<td style='text-align:right;'>{$tuote->a_hinta_toString()}</td>
			<td style='text-align:right;'>{$tuote->alv_prosentti} %</td>
			<td style='text-align:right;'>{$tuote->kpl_maara}</td>
			<td style='text-align:right;'>{$tuote->summa_toString()}</td>
		</tr>";
}

/**
 * ALV-kantojen listauksen header-row
 */
$html .= "
	</tbody>
</table>
<hr>
<table><tr><td>
	<table style='margin-right:50px;'>
		<thead>
		<tr><th style='text-align:right;'>ALV-kanta</th>
			<th style='text-align:right;'>ALV-perus</th>
			<th style='text-align:right;'>ALV-määrä</th></tr>
		</thead>
		<tbody>
";

/**
 * Lisätään kaikkien ALV-kantojen tiedot laskun loppuun.
 */
foreach ( $lasku->hintatiedot['alv_kannat'] as $kanta ) {
	$html .= "
		<tr><td style='text-align:right;'>{$kanta['kanta']} %</td>
			<td style='text-align:right;'>{$lasku->float_toString($kanta['perus'])} €</td>
			<td style='text-align:right;'>{$lasku->float_toString($kanta['maara'])} €</td></tr>";
}

/**
 * Laskun loppuosa. Tilauksen summa jne.
 */
$html .= "
		<tr><td style='text-align:center;'>Yht.</td>
			<td style='text-align:right;'>{$lasku->float_toString($lasku->hintatiedot['alv_perus'])} €</td>
			<td style='text-align:right;'>{$lasku->float_toString($lasku->hintatiedot['alv_maara'])} €</td></tr>
		</tbody>
	</table></td><td>
	<table style='margin-right:50px;'>
		<thead><tr><th colspan='2' style='text-align: center;'>LOPPUSUMMA</th></tr></thead>
		<tbody>
		<tr><td>Tuotteet yhteensä:</td>
			<td style='text-align:right;'>{$lasku->float_toString($lasku->hintatiedot['tuotteet_yht'])} €</td></tr>
		<tr><td>Lisäveloitukset:</td>
			<td style='text-align:right;'>{$lasku->float_toString($lasku->hintatiedot['lisaveloitukset'])} €</td></tr>
		<tr><td>Summa yhteensä:</td>
			<td style='text-align:right;'>{$lasku->float_toString($lasku->hintatiedot['summa_yhteensa'])} €</td></tr>
		</tbody>
	</table></td></tr>
</table>
<hr>
<table>
<thead><tr><th>Maksun vastaanottaja</th></tr></thead>
<tbody>
	<tr> <td>Osax Oy,<br>{$lasku->osax->y_tunnus}</td>
	<td>Tilinumero:<br>[000123456789000]</td>
	<td>Viivästyskorko:<br>12 %</td>
	<td>Maksuaika:<br>12 päivää</td> </tr>
	<tr> <td>{$lasku->osax->katuosoite}<br>{$lasku->osax->postinumero}, {$lasku->osax->postitoimipaikka}</td>
	<td>ALV-tunniste:<br>[number]</td> </tr>
</tbody>
</table>
<p>Muita pakollisia tietoja: tuotteiden alennukset, yksikkohinta ilman vero (seriously?).</p>
";

/** //////////////////////////////////////// */
/** PDF:n luonti */
/** //////////////////////////////////////// */
/*
 * PDF-header ja footer
 * Header: "Osax Oy :: Lasku" keskitettynä
 * Footer: "[Päivämäärä] - [sivunumero] - Lasku"
 */
$mpdf->SetHTMLHeader('<div style="font-weight:bold;text-align:center;">Osax Oy :: Lasku</div>');
$mpdf->SetHTMLFooter('
<table width="100%" style="vertical-align:bottom; font-family:serif; font-size:8pt; color:#000000; font-weight:bold; font-style:italic;"><tr>
<td width="33%"><span style="font-weight:bold; font-style:italic;">{DATE j-m-Y}</span></td>
<td width="33%" align="center" style="font-weight:bold; font-style:italic;">{PAGENO}/{nbpg}</td>
<td width="33%" style="text-align:right; ">Lasku</td>
</tr></table>
');

$mpdf->WriteHTML( $html ); // Kirjoittaa HTML:n tiedostoon.

if ( !file_exists('./laskut') ) {
	mkdir( './laskut' );
}

$mpdf->Output("./laskut/Lasku-{$lasku->tilaus_nro}-{$user->id}.pdf",'F');
