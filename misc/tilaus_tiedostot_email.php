<?php
require '../luokat/laskutiedot.class.php';
require '../luokat/dbyhteys.class.php'; // Laskutiedot-luokkaa varten
require '../luokat/yritys.class.php'; // Laskutiedot-luokkaa varten
require '../luokat/email.class.php';
require '../luokat/user.class.php'; // Laskutiedot-luokkaa ja sähköpostia varten

require '../mpdf/mpdf.php';
require './lasku_html.php';
require './noutolista_html.php';

$db = new DByhteys();
$user = new User( $db, $argv[2] );
$tilaus_id = $argv[1];

$mpdf = new mPDF();
$lasku = new Laskutiedot( $db, $tilaus_id, $user );

if ( !file_exists('../tilaukset') ) {
	mkdir( '../tilaukset' );
}

/********************
 * Laskun luonti
 ********************/
$mpdf->SetHTMLHeader( $pdf_lasku_html_header );
$mpdf->SetHTMLFooter( $pdf_lasku_html_footer );
$mpdf->WriteHTML( $pdf_lasku_html_body );
$lasku_nimi = "lasku-" . sprintf('%05d', $lasku->laskunro) . "-{$user->id}.pdf";
$mpdf->Output( "../tilaukset/{$lasku_nimi}", 'F' );

/********************
 * Noutolistan luonti
 ********************/
$mpdf->SetHTMLHeader( $pdf_noutolista_html_header );
$mpdf->SetHTMLFooter( $pdf_noutolista_html_footer );
$mpdf->WriteHTML( $pdf_noutolista_html_body );
$noutolista_nimi = "noutolista-" . sprintf('%05d', $lasku->laskunro) . "-{$user->id}.pdf";
$mpdf->Output( "../tilaukset/{$noutolista_nimi}", 'F' );

/**
 * Sähköpostit
 */
Email::lahetaTilausvahvistus( $user->sahkoposti, $lasku, $tilaus_id, $lasku_nimi );
Email::lahetaNoutolista( $tilaus_id, $noutolista_nimi );
