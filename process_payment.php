<?php

function debug($var){
	echo"<br><pre>Print_r ::<br>";print_r($var);echo"<br>Var_dump ::<br>";var_dump($var);echo"</pre><br>";}


if ( !empty($_GET) ) {
	debug($_GET);
}

elseif ( !empty($_POST) ) {
	debug($_POST);
}

$merchant_auth_hash = "6pKF4jkv97zmqBJ3ZL8gUw5DfT2NMQ";
$merchant_id = "13466"; // Testi-ID: 13466
$amount = "99.90";
$order_id = "123456";
$reference_number = ""; // Tyhjä tarkoituksella
$order_descr = ""; // Tyhjä tarkoituksella
$currency = "EUR";
$return_addr = "http://www.osax.fi/process_payment.php";
$cancel_addr = "http://www.osax.fi/process_payment.php";
$pending_addr = ""; // Tyhjä tarkoituksella. Ei käytössä Paytrailin API:ssa.
$notify_addr = "http://www.osax.fi/process_payment.php";
$type = "S1"; // S1-form. Yksinkertaisempi vaihtoehto.
$culture = "fi_FI";
$preselected_method = ""; // Tyhjä tarkoituksella
$mode = "1";
$visible_method = ""; // Tyhjä tarkoituksella
$group = ""; // Tyhjä tarkoituksella. Ei käytössä Paytrailin API:ssa.

$auth_code = $merchant_auth_hash . '|' . $merchant_id . '|' . $amount . '|' . $order_id . '|' . $reference_number. '|'
	. $order_descr . '|' . $currency . '|' . $return_addr . '|' . $cancel_addr . '|' . $pending_addr . '|'
	. $notify_addr . '|' . $type . '|' . $culture . '|' . $preselected_method . '|' . $mode	. '|'
	. $visible_method . '|' . $group;

$auth_code = strtoupper( md5($auth_code) );
?>
<!DOCTYPE html><html lang="fi">
<head>
	<meta charset="utf-8">
	<link rel="stylesheet" href="css/styles.css">
	<link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
	<script src="https://code.jquery.com/jquery-3.1.1.min.js"></script>
	<style> form, p, div, section { border: 1px solid; }</style>
</head>
<body>

<!--
    Tämä on S1-form, joka on yksinkertaisempi kahdesta vaihtoehdosta (E1 ja S1).
  -->

<form action="https://payment.paytrail.com/" method="post" id="payment">

	<input name="MERCHANT_ID" type="hidden" value="<?= $merchant_id ?>">
	<input name="AMOUNT" type="hidden" value="<?= $amount ?>">
	<input name="ORDER_NUMBER" type="hidden" value="<?= $order_id ?>">
	<input name="REFERENCE_NUMBER" type="hidden" value="<?= $reference_number ?>">
	<input name="ORDER_DESCRIPTION" type="hidden" value="<?= $order_descr ?>">
	<input name="CURRENCY" type="hidden" value="<?= $currency ?>">
	<input name="RETURN_ADDRESS" type="hidden" value="<?= $return_addr ?>">
	<input name="CANCEL_ADDRESS" type="hidden" value="<?= $cancel_addr ?>">
	<input name="PENDING_ADDRESS" type="hidden" value="<?= $pending_addr ?>">
	<input name="NOTIFY_ADDRESS" type="hidden" value="<?= $notify_addr ?>">
	<input name="TYPE" type="hidden" value="<?= $type ?>">
	<input name="CULTURE" type="hidden" value="<?= $culture ?>">
	<input name="PRESELECTED_METHOD" type="hidden" value="<?= $preselected_method ?>">
	<input name="MODE" type="hidden" value="<?= $mode ?>">
	<input name="VISIBLE_METHODS" type="hidden" value="<?= $visible_method ?>">
	<input name="GROUP" type="hidden" value="<?= $group ?>">
	<input name="AUTHCODE" type="hidden" value="<?= $auth_code ?>">
	<input type="submit" value="Siirry maksamaan">

</form>


<script type="text/javascript" src="//payment.paytrail.com/js/payment-widget-v1.0.min.js"></script>
<script>
	$(document).ready(function() {
		SV.widget.initWithForm('payment', {charset:'ISO-8859-1'});
	});
</script>

</body>
</html>
