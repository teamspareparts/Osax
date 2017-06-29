<?php
chdir(__DIR__); // Määritellään työskentelykansio
set_time_limit(300); // 5min

require '../luokat/laskutiedot.class.php';
require '../luokat/dbyhteys.class.php'; // Laskutiedot-luokkaa varten
require '../luokat/yritys.class.php'; // Laskutiedot-luokkaa varten
require '../luokat/tuote.class.php'; // Laskutiedot-luokkaa varten
require '../luokat/email.class.php';
require '../luokat/user.class.php'; // Laskutiedot-luokkaa ja sähköpostia varten
require '../mpdf/mpdf.php';

$db = new DByhteys( null, "../config/config.ini.php" );

$sql = "SELECT id, kayttaja_id
		FROM tilaus
		WHERE maksettu = 1
			AND laskunro IS NULL";
$rows = $db->query( $sql, null, DByhteys::FETCH_ALL );

if ( !file_exists('../tilaukset') ) {
	mkdir( '../tilaukset' );
}

Email::muutaConfigPath("../config/config.ini.php");

foreach ( $rows as $tilaus ) {

	$user = new User( $db, $tilaus->kayttaja_id );

	$mpdf = new mPDF();
	$lasku = new Laskutiedot( $db, $tilaus->id, $user );

	require './lasku_html.php';     // HTML-tiedostot vaativat $lasku-objektia, joten siksi nämä ei alussa.
	require './noutolista_html.php';

	/********************
	 * Laskun luonti
	 ********************/
	$mpdf->SetHTMLHeader( $pdf_lasku_html_header );
	$mpdf->SetHTMLFooter( $pdf_lasku_html_footer );
	$mpdf->WriteHTML( $pdf_lasku_html_body );
	$lasku_nimi = "../tilaukset/lasku-" . sprintf('%05d', $lasku->laskunro) . "-{$user->id}.pdf";
	$mpdf->Output( $lasku_nimi, 'F' );

	/********************
	 * Noutolistan luonti
	 ********************/
	$mpdf->SetHTMLHeader( $pdf_noutolista_html_header );
	$mpdf->SetHTMLFooter( $pdf_noutolista_html_footer );
	$mpdf->WriteHTML( $pdf_noutolista_html_body );
	$noutolista_nimi = "../tilaukset/noutolista-" . sprintf('%05d', $lasku->laskunro) . "-{$user->id}.pdf";
	$mpdf->Output( $noutolista_nimi, 'F' );

	/********************
	 * Sähköpostit
	 ********************/
	Email::lahetaTilausvahvistus( $user->sahkoposti, $lasku, $tilaus->id, $lasku_nimi );
	Email::lahetaNoutolista( $tilaus->id, $noutolista_nimi );

	if ( !$_SESSION['indev'] ) {
		Email::lahetaTilausvahvistus( 'janne@osax.fi', $lasku, $tilaus_id, $lasku_nimi );
	}
}
