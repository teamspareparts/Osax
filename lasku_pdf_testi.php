<?php
require './mpdf/mpdf.php';
require './luokat/laskutiedot.class.php';
require './luokat/dbyhteys.class.php'; $db = new DByhteys( 'root', '', 'tuoteluettelo_database' );
require './luokat/user.class.php'; $user = new User( $db, 4 );
require './luokat/yritys.class.php'; $yritys = new Yritys( $db, 2 );
require './luokat/tuote.class.php';

$mpdf = new mPDF();
$lasku = new Laskutiedot( $db, 10, $user, $yritys );
$lasku->tuotteet[] = new Tuote();

/** ////////////////////////////////////////////////////////////////////// */
/** PDF:n HTML:n kirjoitus */
/** ////////////////////////////////////////////////////////////////////// */
/**
 * Laskun alkuosa. Logo, laskun tiedot ja osoitetiedot. Sen jälkeen tuotetaulukon header row.
 */
$html = "
<!-- Laskun logo, pvm, ja numero -->
<table style='width:100%;'>
	<tbody>
	<tr><td><img src='img/osax_logo.jpg' alt='Osax.fi'></td>
		<td colspan='2'>
		<table style='width:70%;padding:15px;'>
			<thead>
			<tr><th>Laskunro</th>
				<th>Päivämäärä</th></tr>
			</thead>
			<tbody>
			<tr><td style='text-align:center;'>".sprintf('%04d', $lasku->tilaus_nro)."</td>
				<td style='text-align:center;'>".date('d.m.Y')."</td>
			</tr>
			</tbody>
		</table></td></tr>
	</tbody>
</table><br>
<!-- Asiakkaan tiedot/toimitusosoite, ja maksutapa -->
<table style='width:100%; margin-left:20px;'>
	<tbody>
	<tr><th style='text-align:left;'>Asiakas (".sprintf('%04d', $lasku->asiakas->id).")</th></tr>
	<tr><td>{$lasku->asiakas->yrityksen_nimi}<br>
			{$lasku->toimitusosoite->katuosoite}<br>
			{$lasku->toimitusosoite->postinumero} {$lasku->toimitusosoite->postitoimipaikka}<br><br>
			
			{$lasku->asiakas->puhelin}, {$lasku->asiakas->sahkoposti}<br>
			</td>
		<td>Maksutapa: e-korttimaksu<br>
			</td>
		</tr>
	</tbody>
</table>
<hr>
<!-- Tilauksen numero ja tilausaika -->
<table style='width:60%;'>
	<tbody>
	<tr><td style='text-align:center;'>Tilausnro: ".sprintf('%04d', $lasku->tilaus_nro)."</td>
		<td style='text-align:center;'>Tilausaika: {$lasku->tilaus_pvm}</td>
	</tr>
	</tbody>
</table>
<!-- Tuotteet-taulukko, header-rivi -->
<table style='width:100%;font-size:80%;'>
	<thead>
	<tr><th colspan='9' class='center'><h2>Tilatut tuotteet</h2></th></tr>
	<tr><th style='text-align:right;'>#</th>
		<th>Tuotekoodi</th>
		<th>Nimi</th>
		<th></th>
		<th style='text-align:right;'>Veroton<br>&agrave;-hinta</th>
		<th style='text-align:right;'>ALV</th>
		<th style='text-align:right;'>Ale</th>
		<th style='text-align:right;'>kpl</th>
		<th style='text-align:right;'>Veroton<br>Rivisumma</th></tr>
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
			<td>{$tuote->nimi}</td>
			<td></td>
			<td style='text-align:right;'>{$tuote->a_hinta_toString( true )}</td>
			<td style='text-align:right;'>{$tuote->alv_prosentti} %</td>
			<td style='text-align:right;'>{$tuote->alennus} %</td>
			<td style='text-align:right;'>{$tuote->kpl_maara}</td>
			<td style='text-align:right;'>{$tuote->summa_toString( true )}</td>
		</tr>";
}

/**
 * Lisätään rahti tuote-listaukseen
 */
$html .= "
		<tr><td style='text-align:right;'>".sprintf('%03d', $i++)."</td>
			<td></td>
			<td>Rahtimaksu</td>
			<td></td>
			<td style='text-align:right;'>{$lasku->asiakas->rahtimaksu_toString()}</td>
			<td style='text-align:right;'>24 %</td>
			<td style='text-align:right;'>". (($lasku->asiakas->rahtimaksu === 0) ? "100 %" : "") ."</td>
			<td style='text-align:right;'>1</td>
			<td style='text-align:right;'>{$lasku->asiakas->rahtimaksu_toString()}</td>
		</tr>";

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
		<tr><th style='text-align:center;'>Yht.</th>
			<td style='text-align:right;'>{$lasku->float_toString($lasku->hintatiedot['alv_perus'])} €</td>
			<td style='text-align:right;'>{$lasku->float_toString($lasku->hintatiedot['alv_maara'])} €</td></tr>
		</tbody>
	</table></td><td>
	<table style='margin-right:50px;'>
		<thead><tr><th colspan='2' style='text-align: center;'>LOPPUSUMMA</th></tr></thead>
		<tbody>
		<tr><td>Summa yhteensä:</td>
			<td style='text-align:right;'>{$lasku->float_toString($lasku->hintatiedot['summa_yhteensa'])} €</td></tr>
		</tbody>
	</table></td></tr>
</table>
<hr>

<p style='font-weight:bold;'>! Maksettu korttiveloituksena tilausta tehdessä !</p>

<hr>
<table style='width:100%; font-size:80%;'>
	<tbody>
		<tr><td>Osax Oy</td>
			<td>Y-tunnus: {$lasku->osax->y_tunnus}</td>
			<td>FI40 4600 0010 7476 95 ( ITELFIHH )</td> 
			<td>ALV.REK</td> 
			</tr>
	</tbody>
</table>
<table style='width:100%; font-size:80%;'>
	<tbody>
		<tr><td>{$lasku->osax->katuosoite}, {$lasku->osax->postinumero} {$lasku->osax->postitoimipaikka}</td>
			<td>Kotipaikka: Lahti</td>
			<td>Email: janne@osax.fi </td> </tr>
	</tbody>
</table>
";


/** ////////////////////////////////////////////////////////////////////// */
/** PDF:n luonti */
/** ////////////////////////////////////////////////////////////////////// */
/**
 * PDF-header ja footer
 * Header: "Osax Oy :: Lasku" keskitettynä
 * Footer: Päivämäärä, sivunumero ja dokumentin nimi ("lasku")
 */
$mpdf->SetHTMLHeader('<div style="font-weight:bold;text-align:center;">Osax Oy :: Kuitti</div>');
$mpdf->SetHTMLFooter('
<table width="100%" style="vertical-align:bottom; font-family:serif; font-size:8pt; color:#000000; font-weight:bold; font-style:italic;"><tr>
<td width="33%"><span style="font-weight:bold; font-style:italic;">{DATE Y-m-j}</span></td>
<td width="33%" align="center" style="font-weight:bold; font-style:italic;">{PAGENO}/{nbpg}</td>
<td width="33%" style="text-align:right; ">Lasku</td>
</tr></table>
');

$mpdf->WriteHTML( $html );

$mpdf->Output();
