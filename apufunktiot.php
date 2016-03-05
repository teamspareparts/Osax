<?php

//
// Muotoilee rahasumman muotoon "1 000,00 €"
//
function format_euros($amount) {
	return number_format($amount, 2, ',', '&nbsp;') . ' &euro;';
}
