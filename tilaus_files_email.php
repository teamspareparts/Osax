<?php
require './mpdf/mpdf.php';
require './luokat/laskutiedot.class.php';

$mpdf = new mPDF();
$lasku = new Laskutiedot( $db, $tilaus_id, $user );

// Tarkistetaan että laskut- ja noutolistat-kansiot ovat olemassa ensimmäiseksi.
if ( !file_exists('./laskut') ) {
	mkdir( './laskut' );
}
if ( !file_exists('./noutolistat') ) {
	mkdir( './noutolistat' );
}

require 'lasku_html.php';
$mpdf->SetHTMLHeader( $pdf_html_header );
$mpdf->SetHTMLFooter( $pdf_html_footer );
$mpdf->WriteHTML( $pdf_html_body );
$tiedoston_nimi = "lasku-" . sprintf('%05d', $lasku->laskunro) . "-{$lasku->asiakas->id}.pdf";
$mpdf->Output( "./laskut/{$tiedoston_nimi}", 'F' );

require 'noutolista_html.php';
$mpdf->SetHTMLHeader( $pdf_html_header );
$mpdf->SetHTMLFooter( $pdf_html_footer );
$mpdf->WriteHTML( $pdf_html_body );
$tiedoston_nimi = "noutolista-" . sprintf('%05d', $lasku->laskunro) . "-{$lasku->asiakas->id}.pdf";
$mpdf->Output( "./noutolistat/{$tiedoston_nimi}", 'F' );


Email::lahetaTilausvahvistus( $user->sahkoposti, $lasku, $tilaus_id, $tiedoston_nimi );
Email::lahetaNoutolista( $tilaus_id, $tiedoston_nimi );

if ( !$_SESSION['indev'] ) {
	//TODO: Lisää tähän jannen sähköposti-ilmoitus. --JJ 17-04-28
}

exit();
