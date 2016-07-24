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

function get_shopping_cart() {
	return isset($_SESSION['cart']) ? $_SESSION['cart'] : [];
}

//
// Lisää tuotteen ostoskoriin
//
function add_product_to_shopping_cart($id, $amount) {
	$cart = get_shopping_cart();
	$prev_amount = isset($cart[$id]) ? $cart[$id] : 0;
	$cart[$id] = $prev_amount + $amount;
	$_SESSION['cart'] = $cart;
	return true;
}

//
// Muokkaa tuotetta ostoskorissa
//
function modify_product_in_shopping_cart($id, $amount) {
	$cart = get_shopping_cart();
	if ($amount <= 0) {
		return remove_product_from_shopping_cart($id);
	} else {
		$cart[$id] = $amount;
		$_SESSION['cart'] = $cart;
		return true;
	}
}

//
// Poistaa tuotteen ostoskorista
//
function remove_product_from_shopping_cart($id) {
	$cart = get_shopping_cart();
	if (isset($cart[$id])) {
		unset($cart[$id]);
		$_SESSION['cart'] = $cart;
		return true;
	}
	return false;
}

//
// Tyhjentää ostoskorin
//
function empty_shopping_cart() {
	$_SESSION['cart'] = [];
	return true;
}

//
// TODO: Siirrä jonnekin järkevämpään paikkaan
//
function handle_shopping_cart_action() {
	$cart_action = isset($_POST['ostoskori_toiminto']) ? $_POST['ostoskori_toiminto'] : null;
	if ($cart_action) {
		$cart_product = isset($_POST['ostoskori_tuote']) ? str_replace(" ", "", $_POST['ostoskori_tuote']) : null;
		$cart_amount = isset($_POST['ostoskori_maara']) ? $_POST['ostoskori_maara'] : null;

		if ($cart_action === 'lisaa') {
			if (add_product_to_shopping_cart($cart_product, $cart_amount)) {
				echo '<p class="success">Tuote lisätty ostoskoriin.</p>';
			} else {
				echo '<p class="error">Tuotteen lisäys ei onnistunut.</p>';
			}
		} elseif ($cart_action === 'muokkaa') {
			if (modify_product_in_shopping_cart($cart_product, $cart_amount)) {
				echo '<p class="success">Ostoskori päivitetty.</p>';
			} else {
				echo '<p class="error">Ostoskorin päivitys ei onnistunut.</p>';
			}
		}  elseif ($cart_action === 'poista') {
			if (remove_product_from_shopping_cart($cart_product)) {
				echo '<p class="success">Tuote poistettu ostoskorista.</p>';
			} else {
				echo '<p class="error">Tuotteen poistaminen ei onnistunut.</p>';
			}
		} elseif ($cart_action === 'empty') {
			if (empty_shopping_cart()) {
				echo '<p class="success">Ostoskori tyhjennetty.</p>';
			} else {
				echo '<p class="error">Ostoskorin tyhjentäminen ei onnistunut.</p>';
			}
		}
	}
}

/*
 * Hakee annetun ALV-tason prosentin tietokannasta
 */
function hae_ALV_prosentti($ALV_kanta) {
	global $connection;
	$sql_query = "
			SELECT	prosentti
			FROM	ALV_kanta
			WHERE	kanta = '$ALV_kanta'";
	$result = mysqli_query($connection, $sql_query) or die(mysqli_error($connection));
	$prosentti = mysqli_fetch_assoc($result)['prosentti'];
	return $prosentti;
}