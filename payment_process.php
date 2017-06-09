<?php
require '_start.php'; global $db, $user, $cart;
require 'luokat/paymentAPI.class.php';
require 'luokat/email.class.php';

// Tarkistetaan onko maksusuoritus Paytrailin sivulta _GET-muuttujaan
if ( !empty( $_GET['ORDER_NUMBER'] ) ) {
	if ( PaymentAPI::checkReturnAuthCode( $_GET ) ) {
		$tilaus_id = $_GET[ 'ORDER_NUMBER' ];
		$maksutapa = 0; // Maksutapaa ei voi ottaa user:sta, koska siellä on määritelty ylin mahdollinen mt.
	}
}
// _POST-muuttuja, jos käyttäjä valinnut laskulla maksun
elseif ( !empty( $_POST[ 'tilaus_id' ] ) ) {
	$tilaus_id = $_POST[ 'tilaus_id' ];
	$maksutapa = $_POST[ 'maksutapa' ];
}
// Tilauksen peruutus ("Peruuta"-nappi)
elseif ( !empty( $_POST[ 'peruuta_id' ] ) ) {

	if ( PaymentAPI::peruutaTilausPalautaTuotteet( $db, $user, $_POST[ 'peruuta_id' ], $cart->ostoskori_id ) ) {
		$_SESSION[ 'feedback' ] = "<p class='error'>Tilaus peruutettu. Tuotteet lisätty takaisin ostoskoriin.</p>";
	}
	else {
		$_SESSION[ "feedback" ] = "<p class='error'>Tilauksen peruutus ei onnistunut. 
			Ole hyvä, ja ota yhteys ylläpitäjiin.<br>Virhe: " . print_r( $ex->errorInfo, 1 ) . "</p>";
	}
	header( "location:ostoskori.php?cancel_maksu" );
	exit;
}

// Ei maksunsuoritus; käyttäjä vasta tullut tilaus.php-sivulta
elseif ( !empty( $_SESSION[ 'tilaus' ] ) ) {
	PaymentAPI::preparePaymentFormInfo( $_SESSION[ 'tilaus' ][ 0 ], $_SESSION[ 'tilaus' ][ 1 ] );
}
else {
	//header( "location:ostoskori.php" );
	//exit;
}

/*
 * Onnistuneen maksun suoritus. Ylhäällä tarkistetaan onko _GET tai _POST, tässä viimeistellään varsinainen maksu.
 */
if ( !empty( $tilaus_id ) ) {
	$sql = "UPDATE tilaus SET maksettu = 1, maksutapa = ? 
			WHERE id = ? AND kayttaja_id = ? AND maksettu != 1";
	$result = $db->query( $sql, [ $maksutapa, $tilaus_id, $user->id ] );
	// Jos maksu on jo hyväksytty (maksettu == 1), niin kyselyn pitäisi palauttaa 0 (muutettua riviä).
	if ( $result ) {

		// Luodaan lasku, ja lähetetään tilausvahvistus.
		require 'lasku_pdf_luonti.php'; // Laskun luonti tässä tiedostossa
		Email::lahetaTilausvahvistus( $user->sahkoposti, $lasku, $tilaus_id, $tiedoston_nimi );

		// Luodaan noutolista, ja lähetetään ylläpidolle ilmoitus
		require 'noutolista_pdf_luonti.php';
		Email::lahetaNoutolista( $tilaus_id, $tiedoston_nimi );

		unset( $_SESSION[ 'tilaus' ] );
		header( "location:tilaus_info.php?id={$tilaus_id}" );
		exit();
	}
}

/** Tarkistetaan feedback, ja estetään formin uudelleenlähetys */
if ( !empty( $_POST ) ) { //Estetään formin uudelleenlähetyksen
	header( "Location: " . $_SERVER[ 'REQUEST_URI' ] );
	exit();
} else {
	$feedback = isset( $_SESSION[ 'feedback' ] ) ? $_SESSION[ 'feedback' ] : "";
	unset( $_SESSION[ "feedback" ] );
}
?>

<!DOCTYPE html>
<html lang="fi">
<head>
	<meta charset="utf-8">
	<link rel="stylesheet" href="css/styles.css">
	<link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
	<script src="https://code.jquery.com/jquery-3.1.1.min.js"></script>
</head>
<body>

<?php require 'header.php'; ?>
<script>
	document.getElementById("navbar").style.display = "none";
	document.getElementById("headertop").style.border = '2px solid #2f5cad';
</script>

<main class="main_body_container">
	<div style="width: 503px;">
		<form method='post' style="width:50%; display:inline;">
			<input name='peruuta_id' type='hidden' value='<?= $_SESSION[ 'tilaus' ][0] ?>'>
			<input type='submit' value='Peruuta tilaus' class="nappi grey" style="width:40%;">
		</form>

		<?php if ( $user->maksutapa > 0 ) : ?>
			<form method='post' style="width:50%; display:inline;">
				<input name='tilaus_id' type='hidden' value='<?= $_SESSION[ 'tilaus' ][0] ?>'>
				<input name='maksutapa' type='hidden' value='<?= $user->maksutapa ?>'>
				<input type='submit' value='Maksa laskulla' class="nappi" style="width:50%; float:right;">
			</form>
		<?php endif; ?>
	</div>

	<br>

	<h2>Maksu Paytrailin kautta:</h2>
	<form action='https://payment.paytrail.com/' method='post' id='payment'>

		<input type='hidden' name='MERCHANT_ID' value='<?= PaymentAPI::$merchant_id ?>'>
		<input type='hidden' name='AMOUNT' value='<?= PaymentAPI::$amount ?>'>
		<input type='hidden' name='ORDER_NUMBER' value='<?= PaymentAPI::$order_id ?>'>
		<input type='hidden' name='REFERENCE_NUMBER' value='<?= PaymentAPI::$reference_number ?>'>
		<input type='hidden' name='ORDER_DESCRIPTION' value='<?= PaymentAPI::$order_descr ?>'>
		<input type='hidden' name='CURRENCY' value='<?= PaymentAPI::$currency ?>'>
		<input type='hidden' name='RETURN_ADDRESS' value='<?= PaymentAPI::$return_addr ?>'>
		<input type='hidden' name='CANCEL_ADDRESS' value='<?= PaymentAPI::$cancel_addr ?>'>
		<input type='hidden' name='PENDING_ADDRESS' value='<?= PaymentAPI::$pending_addr ?>'>
		<input type='hidden' name='NOTIFY_ADDRESS' value='<?= PaymentAPI::$notify_addr ?>'>
		<input type='hidden' name='TYPE' value='<?= PaymentAPI::$type ?>'>
		<input type='hidden' name='CULTURE' value='<?= PaymentAPI::$culture ?>'>
		<input type='hidden' name='PRESELECTED_METHOD' value='<?= PaymentAPI::$preselected_method ?>'>
		<input type='hidden' name='MODE' value='<?= PaymentAPI::$mode ?>'>
		<input type='hidden' name='VISIBLE_METHODS' value='<?= PaymentAPI::$visible_method ?>'>
		<input type='hidden' name='GROUP' value='<?= PaymentAPI::$group ?>'>
		<input type='hidden' name='AUTHCODE' value='<?= PaymentAPI::$auth_code ?>'>
		<input type='submit' value='Siirry maksamaan'>

	</form>
</main>


<script src="//payment.paytrail.com/js/payment-widget-v1.0.min.js"></script>
<script>
	$(document).ready(function () {
		SV.widget.initWithForm('payment', {charset: 'UTF-8'});
	});
</script>

</body>
</html>
