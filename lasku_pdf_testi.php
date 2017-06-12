<?php
require './mpdf/mpdf.php';
require './luokat/laskutiedot.class.php';
require './luokat/dbyhteys.class.php'; $db = new DByhteys();
require './luokat/user.class.php'; $user = new User( $db, 2 );
require './luokat/yritys.class.php'; $yritys = new Yritys( $db, 2 );
require './luokat/tuote.class.php';

function debug($var,$var_dump=false){
	echo"<br><pre>Print_r ::<br>";print_r($var);echo"</pre>";
	if($var_dump){echo"<br><pre>Var_dump ::<br>";var_dump($var);echo"</pre><br>";};
}

session_start();
$_SESSION['indev'] = 1;

$mpdf = new mPDF();
$lasku = new Laskutiedot( $db, 3, $user, $yritys );

require 'misc/lasku_html.php';

$mpdf->SetHTMLHeader( $pdf_lasku_html_header );
$mpdf->SetHTMLFooter( $pdf_lasku_html_footer );

$mpdf->WriteHTML( $pdf_lasku_html_body );

$mpdf->Output();
