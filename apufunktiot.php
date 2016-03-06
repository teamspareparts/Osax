<?php

//
// Muotoilee rahasumman muotoon "1 000,00 €"
//
function format_euros($amount) {
	return number_format($amount, 2, ',', '&nbsp;') . ' &euro;';
}

//
// Muotoilee kokonaisluvun muotoon "1 000 000"
//
function format_integer($number) {
	return number_format($number, 0, ',', '&nbsp;');
}
