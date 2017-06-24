<?php
require "luokat/dbyhteys.class.php";
require "luokat/user.class.php";
require "luokat/ostoskori.class.php"; // Tuotteiden palauttamista ostoskoriin varten.
require 'luokat/paymentAPI.class.php';

if ( empty( $_GET[ 'ORDER_NUMBER' ] ) ) {
	header( 'Location: etusivu.php' );
	exit;
}

sleep(300); // Jotta käyttäjä varmasti ehtii ensin payment_process sivulle.

$db = new DByhteys();

// Haetaan kayttajan ID tilauksesta.
$sql = "SELECT kayttaja_id FROM tilaus WHERE id = ?";
$row = $db->query( $sql, [ $_GET[ 'ORDER_NUMBER' ] ] );

$user = new User( $db, $row->kayttaja_id );
$get_count = count( $_GET );

switch ( $get_count ) {
	/*
	 * Maksu peruutettu; vain kolme GET-arvoa.
	 */
	case 3 : // Maksu peruutettu
		if ( PaymentAPI::checkReturnAuthCode( $_GET, true ) ) {
			/*
			 * Päivitetään tilaus peruutetuksi, mutta vain jos sitä ei ole jo peruutettu.
			 * Jos käyttäjä palaa suoraan takaisin payment_cancel-sivulle, se päivitetään siellä.
			 * Jos tilaus on jo peruutettu, tällä sivulla ei tehdä mitään.
			 */

			$sql = "SELECT id FROM tilaus WHERE id = ? AND kayttaja_id = ? AND maksettu = 0";
			$result = $db->query( $sql, [ $_GET[ 'ORDER_NUMBER' ], $user->id ] );

			if ( $result ) {
				PaymentAPI::peruutaTilausPalautaTuotteet( $db, $user, $_GET[ 'ORDER_NUMBER' ], $cart->ostoskori_id );
			}
		}
		break;
	/*
	 * Maksu hyväksytty; kaikki viisi GET-arvot.
	 */
	case 5 :
		if ( PaymentAPI::checkReturnAuthCode( $_GET ) ) {
			/*
			 * Päivitetään maksu suoritetuksi, mutta vain jos sitä ei ole jo hyväksytty.
			 * Jos käyttäjä palaa suoraan takaisin payment_process-sivulle, se hyväksytään siellä.
			 * Jos maksu on jo hyväksytty, tällä sivulla ei tehdä mitään.
			 */
			$sql = "UPDATE tilaus SET maksettu = 1, maksutapa = 0 WHERE id = ? AND kayttaja_id = ? AND maksettu != 1";
			$result = $db->query( $sql, [ $_GET[ 'ORDER_NUMBER' ], $user->id ] );

			if ( $result ) {
				require './luokat/laskutiedot.class.php';
				require './luokat/email.class.php';
				require './mpdf/mpdf.php';

				$mpdf = new mPDF();
				$lasku = new Laskutiedot( $db, $tilaus_id, $user );

				// Alemmat tiedostot vaativat $lasku-objektia.
				require './misc/lasku_html.php';
				require './misc/noutolista_html.php';

				if ( !file_exists('./tilaukset') ) {
					mkdir( './tilaukset' );
				}

				/********************
				 * Laskun luonti
				 ********************/
				$mpdf->SetHTMLHeader( $pdf_lasku_html_header );
				$mpdf->SetHTMLFooter( $pdf_lasku_html_footer );
				$mpdf->WriteHTML( $pdf_lasku_html_body );
				$lasku_nimi = "./tilaukset/lasku-" . sprintf('%05d', $lasku->laskunro) . "-{$user->id}.pdf";
				$mpdf->Output( $lasku_nimi, 'F' );

				/********************
				 * Noutolistan luonti
				 ********************/
				$mpdf->SetHTMLHeader( $pdf_noutolista_html_header );
				$mpdf->SetHTMLFooter( $pdf_noutolista_html_footer );
				$mpdf->WriteHTML( $pdf_noutolista_html_body );
				$noutolista_nimi = "./tilaukset/noutolista-" . sprintf('%05d', $lasku->laskunro) . "-{$user->id}.pdf";
				$mpdf->Output( $noutolista_nimi, 'F' );

				/********************
				 * Sähköpostit
				 ********************/
				Email::lahetaTilausvahvistus( $user->sahkoposti, $lasku, $tilaus_id, $lasku_nimi );
				Email::lahetaNoutolista( $tilaus_id, $noutolista_nimi );

				if ( !$_SESSION['indev'] ) {
					// Kopio Jannelle
					Email::lahetaTilausvahvistus( 'janne@osax.fi', $lasku, $tilaus_id, $lasku_nimi );
				}
			}
		}
		break;
}

header( 'Location: etusivu.php' );
exit;
