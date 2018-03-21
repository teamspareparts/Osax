<?php declare(strict_types=1);
set_include_path(get_include_path().PATH_SEPARATOR.'luokat/');
spl_autoload_extensions('.class.php');
spl_autoload_register();

if ( empty( $_GET[ 'ORDER_NUMBER' ] ) ) {
	header( 'Location: etusivu.php' );
	exit;
}

$tilaus_id = (int)$_GET[ 'ORDER_NUMBER' ];

sleep(300); // Jotta käyttäjä varmasti ehtii ensin payment_process sivulle.

$db = new DByhteys();

// Haetaan kayttajan ID tilauksesta.
$sql = "SELECT kayttaja_id FROM tilaus WHERE id = ?";
$row = $db->query( $sql, [ $tilaus_id ] );

$user = new User( $db, $row->kayttaja_id );
$cart = new Ostoskori( $db, $user->yritys_id, -1 );
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

			$sql = "SELECT id 
					FROM tilaus 
					WHERE id = ? 
						AND kayttaja_id = ? 
						AND maksettu = 0";
			$result = $db->query( $sql, [ $tilaus_id, $user->id ] );

			if ( $result ) {
				PaymentAPI::peruutaTilausPalautaTuotteet( $db, $user, $tilaus_id, $cart->id );
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
			$sql = "UPDATE tilaus 
					SET maksettu = 1, maksutapa = 0 
					WHERE id = ? 
						AND kayttaja_id = ? 
						AND maksettu != 1";
			$result = $db->query( $sql, [ $tilaus_id, $user->id ] );

			// Laskun/noutolistan luonti tapahtuu cronjobin kautta.

		}
		break;
}

header( 'Location: etusivu.php' );
exit;
