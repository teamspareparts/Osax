<?php
/**
 * @version 2017-04-16 <p> Form siirretty HTML:ään.
 */
require '_start.php'; global $db, $user, $cart;
require 'luokat/paymentAPI.class.php';
require 'luokat/email.class.php';

// Tarkistetaan onko maksusuoritus Paytrailin sivulta _GET-muuttujaan
if ( !empty( $_GET['ORDER_NUMBER'] ) ) {
	if ( PaymentAPI::checkReturnAuthCode( $_GET ) ) {
		$tilaus_id = $_GET[ 'ORDER_NUMBER' ];
		$maksutapa = 0;
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
	header( "location:ostoskori.php" );
	exit;
}

// Ei maksunsuoritus; käyttäjä vasta tullut tilaus.php-sivulta
elseif ( !empty( $_SESSION[ 'tilaus' ] ) ) {
	PaymentAPI::preparePaymentFormInfo( $_SESSION[ 'tilaus' ][ 0 ], $_SESSION[ 'tilaus' ][ 1 ] );
}
else {
	header( "location:ostoskori.php" );
	exit;
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

<main class="main_body_container">
	<div style="width: 503px;">
		<form method='post' style="width:50%; display:inline;">
			<input name='peruuta_id' type='hidden' value='<?= $_SESSION[ 'tilaus' ][0] ?>'>
			<input type='submit' value='Peruuta tilaus' class="nappi grey" style="width:40%;">
		</form>

		<?php if ( $user->maksutapa ) : ?>
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
		<input name='MERCHANT_ID' type='hidden' value='<?= PaymentAPI::$merchant_id ?>'>
		<input name='AMOUNT' type='hidden' value='<?= PaymentAPI::$amount ?>'>
		<input name='ORDER_NUMBER' type='hidden' value='<?= PaymentAPI::$order_id ?>'>
		<input name='REFERENCE_NUMBER' type='hidden' value='<?= PaymentAPI::$reference_number ?>'>
		<input name='ORDER_DESCRIPTION' type='hidden' value='<?= PaymentAPI::$order_descr ?>'>
		<input name='CURRENCY' type='hidden' value='<?= PaymentAPI::$currency ?>'>
		<input name='RETURN_ADDRESS' type='hidden' value='<?= PaymentAPI::$return_addr ?>'>
		<input name='CANCEL_ADDRESS' type='hidden' value='<?= PaymentAPI::$cancel_addr ?>'>
		<input name='PENDING_ADDRESS' type='hidden' value='<?= PaymentAPI::$pending_addr ?>'>
		<input name='NOTIFY_ADDRESS' type='hidden' value='<?= PaymentAPI::$notify_addr ?>'>
		<input name='TYPE' type='hidden' value='<?= PaymentAPI::$type ?>'>
		<input name='CULTURE' type='hidden' value='<?= PaymentAPI::$culture ?>'>
		<input name='PRESELECTED_METHOD' type='hidden' value='<?= PaymentAPI::$preselected_method ?>'>
		<input name='MODE' type='hidden' value='<?= PaymentAPI::$mode ?>'>
		<input name='VISIBLE_METHODS' type='hidden' value='<?= PaymentAPI::$visible_method ?>'>
		<input name='GROUP' type='hidden' value='<?= PaymentAPI::$group ?>'>
		<input name='AUTHCODE' type='hidden' value='<?= PaymentAPI::$auth_code ?>'>
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
