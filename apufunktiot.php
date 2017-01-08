<?php

/**
 * Muotoilee rahasumman muotoon "1 000,00 €"
 * @param $amount
 * @return string
 */
function format_euros( /*int*/ $amount) {
	return number_format($amount, 2, ',', '&nbsp;') . ' &euro;';
}

/**
 * Muotoilee kokonaisluvun muotoon "1 000 000"
 * @param $number
 * @return string
 */
function format_integer( /*int*/ $number) {
	return number_format($number, 0, ',', '&nbsp;');
}
