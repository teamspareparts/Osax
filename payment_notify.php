<?php
/**
 * @version 2017-03-09 <p> DByhteys.class-tiedoston nimeä vaihdettu
 */

require "luokat/dbyhteys.class.php";
require "luokat/user.class.php";
require "luokat/ostoskori.class.php"; // Tuotteiden palauttamista ostoskoriin varten.
require 'luokat/paymentAPI.class.php';

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
			$sql = "UPDATE tilaus SET maksettu = -1, kasitelty = -1 WHERE id = ? AND kayttaja_id = ? AND maksettu != -1";
			$result = $db->query( $sql, [ $_GET[ 'ORDER_NUMBER' ], $user->id ] );

			if ( $result ) {
				$sql = "SELECT tuote_id, kpl FROM tilaus_tuote WHERE tilaus_id = ?";
				$results = $db->query( $sql, [ $_GET[ 'ORDER_NUMBER' ] ] );

				// Varastosaldon korjaus takaisin.
				$db->prepare_stmt( "UPDATE tuote SET varastosaldo = varastosaldo + ? WHERE id = ?" );

				foreach ( $results as $tuote ) {
					$db->run_prepared_stmt( [ $tuote->kpl, $tuote->id ] ); // Varastosaldon korjaus takaisin
					//Lisätään tuotteet takaisin ostoskoriin.
					$cart->lisaa_tuote( $db, $tuote->id, $tuote->kpl );
				}
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
			$sql = "UPDATE tilaus SET maksettu = 1 WHERE id = ? AND kayttaja_id = ? AND maksettu != 1";
			$result = $db->query( $sql, [ $_GET[ 'ORDER_NUMBER' ], $user->id ] );
			if ( $result ) {

				// Luodaan lasku, ja lähetetään tilausvahvistus.
				require 'lasku_pdf_luonti.php'; // Laskun luonti tässä tiedostossa
				Email::lahetaTilausvahvistus( $user->sahkoposti, $lasku, $tilaus_id, $tiedoston_nimi );

				// Luodaan noutolista, ja lähetetään ylläpidolle ilmoitus
				require 'noutolista_pdf_luonti.php';
				Email::lahetaNoutolista( $tilaus_id, $tiedoston_nimi );
			}
		}
		break;
	default :
		// Hei, mitä sinä tällä sivulla teet?!
		// Get lost!
		break;
}
