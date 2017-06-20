<?php
sleep(300); // Jotta käyttäjä varmasti ehtii ensin payment_process sivulle.

require "luokat/dbyhteys.class.php";
require "luokat/user.class.php";
require "luokat/ostoskori.class.php"; // Tuotteiden palauttamista ostoskoriin varten.
require 'luokat/paymentAPI.class.php';

if ( empty( $_GET[ 'ORDER_NUMBER' ] ) ) {
	header( 'Location: etusivu.php' );
	exit;
}

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

				//TODO Korjaa
				$args = escapeshellarg($_GET[ 'ORDER_NUMBER' ]) . " " . escapeshellarg($user->id);
				exec( "php tilaus_tiedostot_email.php {$args} > /dev/null &" );

			}
		}
		break;
}

header( 'Location: etusivu.php' );
exit;
