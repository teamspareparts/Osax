<?php

ini_set('display_errors', 1);


set_include_path(get_include_path().PATH_SEPARATOR.'luokat/');
spl_autoload_extensions('.class.php');
spl_autoload_register();
require './mpdf/mpdf.php';


function debug($var,$var_dump=false){
	echo"<br><pre>Print_r ::<br>";print_r($var);echo"</pre>";
	if($var_dump){echo"<br><pre>Var_dump ::<br>";var_dump($var);echo"</pre><br>";};
}


$user_id = 1;
$tilaus_id = 1;
$config['indev'] = 1;


$db = new DByhteys();
$user = new User( $db, $user_id );
$yritys = new Yritys( $db, $user->yritys_id );
$lasku = new Lasku( $db, $tilaus_id, $config['indev'] );



require 'misc/lasku_html.php';
$mpdf = new mPDF();
$mpdf->SetHTMLHeader( $pdf_lasku_html_header );
$mpdf->SetHTMLFooter( $pdf_lasku_html_footer );
$mpdf->WriteHTML( $pdf_lasku_html_body );
$mpdf->Output("./testi-lasku.pdf", 'F');


require 'misc/noutolista_html.php';
$mpdf = new mPDF();
$mpdf->SetHTMLHeader( $pdf_noutolista_html_header );
$mpdf->SetHTMLFooter( $pdf_noutolista_html_footer );
$mpdf->WriteHTML( $pdf_noutolista_html_body );
$mpdf->Output("./testi-noutolista.pdf", 'F');


//$mpdf->Output();
