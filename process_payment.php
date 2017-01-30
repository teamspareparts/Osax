<?php
require '_start.php';

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


<form action="https://payment.paytrail.com/" method="post">
	<input name="MERCHANT_ID" type="hidden" value="13466">
	<input name="AMOUNT" type="hidden" value="99.90">
	<input name="ORDER_NUMBER" type="hidden" value="123456">
	<input name="REFERENCE_NUMBER" type="hidden" value="">
	<input name="ORDER_DESCRIPTION" type="hidden" value="Testitilaus">
	<input name="CURRENCY" type="hidden" value="EUR">
	<input name="RETURN_ADDRESS" type="hidden" value="http://www.esimerkki.fi/success">
	<input name="CANCEL_ADDRESS" type="hidden" value="http://www.esimerkki.fi/cancel">
	<input name="PENDING_ADDRESS" type="hidden" value="">
	<input name="NOTIFY_ADDRESS" type="hidden" value="http://www.esimerkki.fi/notify">
	<input name="TYPE" type="hidden" value="S1">
	<input name="CULTURE" type="hidden" value="fi_FI">
	<input name="PRESELECTED_METHOD" type="hidden" value="">
	<input name="MODE" type="hidden" value="1">
	<input name="VISIBLE_METHODS" type="hidden" value="">
	<input name="GROUP" type="hidden" value="">
	<input name="AUTHCODE" type="hidden" value="270729B19016F94BE5263CA5DE95E330">
	<input type="submit" value="Siirry maksamaan">
</form>


<script>
	$(document).ready(function() {
	});
</script>

</body>
</html>
