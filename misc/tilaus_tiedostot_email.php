<?php
chdir(__DIR__); // Määritellään työskentelykansio // This breaks symlinks on Windows
set_time_limit(300); // 5min

require '../luokat/laskutiedot.class.php';
require '../luokat/dbyhteys.class.php'; // Laskutiedot-luokkaa varten
require '../luokat/yritys.class.php'; // Laskutiedot-luokkaa varten
require '../luokat/tuote.class.php'; // Laskutiedot-luokkaa varten
require '../luokat/email.class.php';
require '../luokat/user.class.php'; // Laskutiedot-luokkaa ja sähköpostia varten
require '../mpdf/mpdf.php';

$config = parse_ini_file( "../config/config.ini.php" ); // Jannen sähköpostin tarkistusta varten

$db = new DByhteys( $config );

// Haetaan niiden tilauksien tiedot, joilla ei ole vielä laskua (siten juuri tilattu)
$sql = "SELECT id, kayttaja_id FROM tilaus WHERE maksettu = 1 AND laskunro IS NULL";
$rows = $db->query( $sql, null, FETCH_ALL );

if ( $rows ) {

	// Aivan ensimmäiseksi päivitämme kaikkiin tilauksiin laskunumeron, jotta ei tule ongelmia päällekkäisyyden kanssa.
	// Cronjob ajetaan 1 minuutin välein, laskujen luominen saattaa kestää pitempään.
	$laskunro = $db->query("SELECT laskunro FROM laskunumero LIMIT 1" )->laskunro;

	echo "Laskunumero: {$laskunro}<br>\r\n";
	echo "---<br>\r\n";

	foreach ( $rows as $tilaus ) {
		$sql = "UPDATE tilaus SET laskunro = ? WHERE id = ?";
		$result = $db->query( $sql, [$laskunro++, $tilaus->id], FETCH_ALL );

		echo "{$tilaus->id} lisätty laskunumero ". ($laskunro-1) . "<br>\r\n";
	}

	$db->query("UPDATE laskunumero SET laskunro = ? LIMIT 1", [$laskunro] );
	echo "---<br>\r\n";
	echo "{$laskunro} päivitetty laskunumero<br>\r\n";
	echo "---<br>\r\n";
	echo "Luodaan laskut ja sähköpostit:<br>\r\n";

	if ( !file_exists('../tilaukset') ) {
		mkdir( '../tilaukset' );
	}

	Email::muutaConfigPath("../config/config.ini.php");

	foreach ( $rows as $tilaus ) {

		echo "- $tilaus->id :: ";

		$user = new User( $db, $tilaus->kayttaja_id );
		$lasku = new Laskutiedot( $db, $tilaus->id, $user, $config['indev'] );

		require './lasku_html.php';     // HTML-tiedostot vaativat $lasku-objektia, joten siksi nämä ei alussa.
		require './noutolista_html.php';

		/********************
		 * Laskun luonti
		 ********************/
		$mpdf = new mPDF();
		$mpdf->SetHTMLHeader( $pdf_lasku_html_header );
		$mpdf->SetHTMLFooter( $pdf_lasku_html_footer );
		$mpdf->WriteHTML( $pdf_lasku_html_body );
		$lasku_nimi = "../tilaukset/lasku-" . sprintf('%05d', $lasku->laskunro) . "-{$user->id}.pdf";
		$mpdf->Output( $lasku_nimi, 'F' );

		if ( file_exists("../tilaukset/lasku-" . sprintf('%05d', $lasku->laskunro) . "-{$user->id}.pdf") ) {
			echo " Lasku: OK -";
		}

		/********************
		 * Noutolistan luonti
		 ********************/
		$mpdf = new mPDF();
		$mpdf->SetHTMLHeader( $pdf_noutolista_html_header );
		$mpdf->SetHTMLFooter( $pdf_noutolista_html_footer );
		$mpdf->WriteHTML( $pdf_noutolista_html_body );
		$noutolista_nimi = "../tilaukset/noutolista-" . sprintf('%05d', $lasku->laskunro) . "-{$user->id}.pdf";
		$mpdf->Output( $noutolista_nimi, 'F' );

		if ( file_exists("../tilaukset/noutolista-" . sprintf('%05d', $lasku->laskunro) . "-{$user->id}.pdf") ) {
			echo " Noutolista: OK";
		}

		/********************
		 * Sähköpostit
		 ********************/
		Email::lahetaTilausvahvistus( $user->sahkoposti, $lasku, $lasku_nimi );
		Email::lahetaNoutolista( $tilaus->id, $noutolista_nimi );

		if ( !$_SESSION['indev'] ) {
			Email::lahetaTilausvahvistus( 'janne@osax.fi', $lasku, $lasku_nimi );
		}
		echo "<br>\r\n";
	}

	echo "---<br>\r\n";
	echo "Kaikki valmiina!<br>\r\n";
}
