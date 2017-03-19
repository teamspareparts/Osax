<?php
/**
 * @version 2017-02-xx <p> WIP
 */
//TODO: Mitä jos käyttäjä vain poistuu sivulta tai sulkee selaimen? --SL 19.3
require '_start.php'; global $db, $user, $cart;
require 'luokat/paymentAPI.class.php';
require 'luokat/email.class.php';

// Tarkistetaan onko maksusuoritus Paytrailin sivulta _GET-muuttujaan
if ( !empty( $_GET ) ) {
	if ( PaymentAPI::checkReturnAuthCode( $_GET ) ) {
		$tilaus_id = $_GET[ 'ORDER_NUMBER' ];
	}
}
// _POST-muuttuja, jos käyttäjä valinnut laskulla maksun
elseif ( !empty( $_POST[ 'tilaus_id' ] ) ) {
	$tilaus_id = $_POST[ 'tilaus_id' ];
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
	$sql = "UPDATE tilaus SET maksettu = 1 WHERE id = ? AND kayttaja_id = ?";
	$result = $db->query( $sql, [ $tilaus_id, $user->id ] );
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
	header( "Location: " . $_SERVER[ 'REQUEST_URI' ] ); // $_SERVER[ 'REQUEST_URI' ] == nykyinen sivu
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

<section class="main_body_container">
	<?php if ( true OR $user->maksutapa ) : ?>
		<form method='post'>
			<input name='tilaus_id' type='hidden' value='<?= $_SESSION[ 'tilaus' ][0] ?>'>
			<input name='maksutapa' type='hidden' value='lasku'>
			<input type='submit' class="nappi" value='Maksa laskulla'>
		</form>
	<?php endif; ?>

	<?= PaymentAPI::getS1Form() ?>
</section>


<script src="//payment.paytrail.com/js/payment-widget-v1.0.min.js"></script>
<script>
	$(document).ready(function () {
		SV.widget.initWithForm('payment', {charset: 'UTF-8'});
	});
</script>

</body>
</html>
