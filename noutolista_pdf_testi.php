<?php
require './mpdf/mpdf.php';
require './luokat/laskutiedot.class.php';
require './luokat/dbyhteys.class.php'; $db = new DByhteys();
require './luokat/user.class.php'; $user = new User( $db, 2 );
require './luokat/yritys.class.php'; $yritys = new Yritys( $db, 2 );
require './luokat/tuote.class.php';

session_start();
$_SESSION['indev'] = 1;

$mpdf = new mPDF();
$lasku = new Laskutiedot( $db, 3, $user, $yritys );

require 'noutolista_html.php';

$mpdf->SetHTMLHeader( $pdf_html_header );
$mpdf->SetHTMLFooter( $pdf_html_footer );

$mpdf->WriteHTML( $pdf_html_body );

$mpdf->Output();
