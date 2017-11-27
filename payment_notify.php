<?php
spl_autoload_register(function (string $class_name) { require './luokat/' . $class_name . '.class.php'; });

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
				require './mpdf/mpdf.php';

				$config = parse_ini_file( "./config/config.ini.php" );

				$lasku = new Laskutiedot( $db, $_GET[ 'ORDER_NUMBER' ], $user, $config['indev'] );

				if ( !file_exists('./tilaukset') ) {
					mkdir( './tilaukset' );
				}

				/********************
				 * Laskun luonti
				 ********************/
				$mpdf = new mPDF();
				require './misc/lasku_html.php';
				$mpdf->SetHTMLHeader( $pdf_lasku_html_header );
				$mpdf->SetHTMLFooter( $pdf_lasku_html_footer );
				$mpdf->WriteHTML( $pdf_lasku_html_body );
				$lasku_nimi = "./tilaukset/lasku-" . sprintf('%05d', $lasku->laskunro) . "-{$user->id}.pdf";
				$mpdf->Output( $lasku_nimi, 'F' );

				/********************
				 * Noutolistan luonti
				 ********************/
				$mpdf = new mPDF();
				require './misc/noutolista_html.php';
				$mpdf->SetHTMLHeader( $pdf_noutolista_html_header );
				$mpdf->SetHTMLFooter( $pdf_noutolista_html_footer );
				$mpdf->WriteHTML( $pdf_noutolista_html_body );
				$noutolista_nimi = "./tilaukset/noutolista-" . sprintf('%05d', $lasku->laskunro) . "-{$user->id}.pdf";
				$mpdf->Output( $noutolista_nimi, 'F' );

				/********************
				 * Sähköpostit
				 ********************/
				Email::lahetaTilausvahvistus( $user->sahkoposti, $lasku, $lasku_nimi );
				Email::lahetaNoutolista( $_GET[ 'ORDER_NUMBER' ], $noutolista_nimi );

				if ( !$_SESSION['indev'] ) {
					// Kopio Jannelle
					Email::lahetaTilausvahvistus( 'janne@osax.fi', $lasku, $lasku_nimi );
				}
			}
		}
		break;
}

header( 'Location: etusivu.php' );
exit;
