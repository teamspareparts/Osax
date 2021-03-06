<?php declare(strict_types=1);
require '_start.php'; global $db, $user, $cart;

ignore_user_abort( true ); //Tilaus tehdään aina loppuun saakka riippumatta käyttäjästä
set_time_limit( 300 ); // 5 minutes

/**
 * Maskusuoritus Paytraililta
 */
if ( !empty( $_GET['ORDER_NUMBER'] ) ) {
	if ( PaymentAPI::checkReturnAuthCode( $_GET ) ) {
		$tilaus_id = (int)$_GET[ 'ORDER_NUMBER' ];
		$maksutapa = 0; // Maksutapaa ei voi ottaa $user:sta, koska siellä on määritelty ylin mahdollinen mt.
	}
}

/**
 * Käyttäjä valinnut laskulla maksamisen
 */
elseif ( !empty( $_POST[ 'tilaus_id' ] ) ) {
	$tilaus_id = (int)$_POST[ 'tilaus_id' ];
	$maksutapa = (int)$_POST[ 'maksutapa' ];
}

/**
 * Käyttäjä peruuttanut maksamisen
 */
elseif ( !empty( $_POST[ 'peruuta_id' ] ) ) {
	if ( PaymentAPI::peruutaTilausPalautaTuotteet( $db, $user, (int)$_POST[ 'peruuta_id' ], $cart->id ) ) {
		$_SESSION[ 'feedback' ] = "<p class='error'>Tilaus peruutettu. Tuotteet lisätty takaisin ostoskoriin.</p>";
	}
	else {
		$_SESSION[ "feedback" ] = "<p class='error'>Tilauksen peruutus ei onnistunut. 
			Ole hyvä, ja ota yhteys ylläpitäjiin.<br>Virhe: " . print_r( $ex->errorInfo, 1 ) . "</p>";
	}
	header( "location:ostoskori.php?cancel_maksu" );
	exit;
}

/**
 * Ensimmäinen kerta sivulla; käyttäjä vasta vahvistanut tilauksen
 */
elseif ( !empty( $_SESSION[ 'tilaus' ] ) ) {
	PaymentAPI::preparePaymentFormInfo( $_SESSION[ 'tilaus' ][ 0 ], $_SESSION[ 'tilaus' ][ 1 ] );
	// Älä tyhjennä _SESSION-dataa tässä, sitä tarvitaan HTML:n puolella.
}

else {
	header( "location:ostoskori.php" );
	exit;
}

/**
 * Onnistuneen maksun suoritus. Ylhäällä tarkistetaan onko _GET tai _POST, tässä viimeistellään varsinainen maksu.
 */
if ( !empty( $tilaus_id ) ) {
	unset( $_SESSION[ 'tilaus' ] );

	$sql = "UPDATE tilaus
			SET maksettu = 1, maksutapa = ? 
			WHERE id = ? 
				AND kayttaja_id = ? 
				AND maksettu != 1";
	$result = $db->query( $sql, [ $maksutapa, $tilaus_id, $user->id ] );

	/**
	 * Laskujen luonti ja sähköpostit tehdään cronjobin välityksellä.
	 */

	$_SESSION[ "feedback" ] = "<p class='success'>Tilaus on suoritettu onnistuneesti.
		Laskun/kuitin pitäisi saapua minuutin sisällä.</p>";
	header( "location:tilaus_info.php?id={$tilaus_id}" );
	exit();
}

/** Tarkistetaan feedback, ja estetään formin uudelleenlähetys */
if ( !empty( $_POST ) ) { //Estetään formin uudelleenlähetyksen
	header( "Location: " . $_SERVER[ 'REQUEST_URI' ] );
	exit();
} else {
	$feedback = $_SESSION['feedback'] ?? "";
	unset( $_SESSION[ "feedback" ] );
}
?>

<!DOCTYPE html>
<html lang="fi">
<head>
	<meta charset="utf-8">
	<link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
	<link rel="stylesheet" href="css/styles.css">
	<script src="https://code.jquery.com/jquery-3.1.1.min.js"></script>
</head>
<body>

<?php require 'header.php'; ?>
<script>
	// Piilotetaan kaikki linkit headerista, jotta käyttäjä ei vahingossa poistu sivulta.
	document.getElementById("navbar").style.display = "none";
	document.getElementById("head_cart").style.display = "none";
	document.getElementById("headertop").style.border = '2px solid #2f5cad';
</script>

<main class="main_body_container">
	<div style="width: 503px;">
		<form method='post' style="width:50%; display:inline;">
			<input name='peruuta_id' type='hidden' value='<?= $_SESSION[ 'tilaus' ][0] ?>'>
			<input type='submit' value='Peruuta tilaus' class="nappi red" style="width:40%;">
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

<?php require 'footer.php'; //TODO: footer menee päällekkin Paytrailin jutun kanssa. ?>

<script src="//payment.paytrail.com/js/payment-widget-v1.0.min.js"></script>
<script>
	$(document).ready(function () {
		SV.widget.initWithForm('payment', {charset: 'UTF-8'});
	});
</script>

</body>
</html>
