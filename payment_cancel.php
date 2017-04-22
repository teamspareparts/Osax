<?php
require '_start.php'; global $db, $user, $cart;
require 'luokat/paymentAPI.class.php';

/*
 * Jos maksua ei hyväksytä, tai käyttäjä peruuttaa maksun, hänet suunnataan tälle sivulle.
 * Maksun tietojen tarkistuksen jälkeen, tilaus merkitään peruutetuksi, ja tuotteet lisätään takaisin ostoskoriin.
 */

if ( !empty( $_GET['ORDER_NUMBER'] ) ) {
	if ( PaymentAPI::checkReturnAuthCode( $_GET, true ) ) {

		if ( PaymentAPI::peruutaTilausPalautaTuotteet( $db, $user, $_GET['ORDER_NUMBER'], $cart->ostoskori_id ) ) {
			$_SESSION[ 'feedback' ] = "<p class='error'>Tilaus peruutettu. Tuotteet lisätty takaisin ostoskoriin.</p>";
		}
		else {
			$_SESSION[ "feedback" ] = "<p class='error'>Tilauksen peruutus ei onnistunut. 
			Ole hyvä, ja ota yhteys ylläpitäjiin.<br>Virhe: " . print_r( $ex->errorInfo, 1 ) . "</p>";
		}
	}
}
// Lähetetään käyttäjä takaisin ostoskoriin. Se vaikuttaisi loogiselta kohteelta.
header( "location:ostoskori.php" );
exit;
