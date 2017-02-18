<?php
/**
 * @version 2017-02-xx <p> WIP
 */
require '_start.php'; global $db, $user, $cart;
require 'luokat/paymentAPI.class.php';
require 'luokat/email.class.php';
require 'lasku_pdf_luonti.php';
require 'noutolista_pdf_luonti.php';

if ( empty( $_GET ) ) {

	if ( PaymentAPI::checkReturnAuthCode( $_GET ) ) {
		$sql = "UPDATE tilaus SET maksettu = 1 WHERE id = ? AND kayttaja_id = ?";
		$result = $db->query( $sql, [ $_GET[ 'ORDER_NUMBER' ], $user->id ] );
		if ( $result ) {

			// Luodaan lasku, ja lähetetään tilausvahvistus.
			require 'lasku_pdf_luonti.php'; // Laskun luonti tässä tiedostossa
			Email::lahetaTilausvahvistus( $user->sahkoposti, $lasku, $tilaus_id, $tiedoston_nimi );

			// Luodaan noutolista, ja lähetetään ylläpidolle ilmoitus
			require 'noutolista_pdf_luonti.php';
			Email::lahetaNoutolista( $tilaus_id, $tiedoston_nimi );

			//TODO Lähetä käyttäjä jonnekin
		}
	}
}
elseif ( !empty( $_SESSION[ 'tilaus' ] ) ) {
	PaymentAPI::preparePaymentFormInfo( $_SESSION[ 'tilaus' ][ 0 ], $_SESSION[ 'tilaus' ][ 1 ] );
}
else {
	header( "location:ostoskori.php" );
	exit;
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
	<?= PaymentAPI::getS1Form() ?>
</section>


<script type="text/javascript" src="//payment.paytrail.com/js/payment-widget-v1.0.min.js"></script>
<script>
	$(document).ready(function () {
		SV.widget.initWithForm('payment', {charset: 'ISO-8859-1'});
	});
</script>

</body>
</html>
