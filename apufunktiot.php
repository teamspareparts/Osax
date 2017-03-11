<?php

/**
 * Muotoilee rahasumman muotoon "1 000,00 €" <br>
 * Deprecated, käytä mieluummin _start:issa olevaa funtiota.
 * @param int $amount
 * @return string
 * @deprecated
 */
function format_euros( /*int*/ $amount) {
	return number_format($amount, 2, ',', '&nbsp;') . ' &euro;';
}

/**
 * Muotoilee kokonaisluvun muotoon "1 000 000". <br>
 * Deprecated, käytä mieluummin _start:issa olevaa funtiota.
 * @param int $number
 * @return string
 * @deprecated
 */
function format_integer( /*int*/ $number) {
	return number_format($number, 0, ',', '&nbsp;');
}
