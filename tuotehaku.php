<!DOCTYPE html>
<html lang="fi">
<head>
	<link rel="stylesheet" href="css/styles.css">
	<meta charset="UTF-8">
	<meta name="description" content="Asiakkaalle näkyvä pohja">
	<title>Tuotehaku</title>
</head>
<body>
<?php include('header.php');?>
<h1 class="otsikko">Tuotehaku</h1>
<form action="tuotehaku.php" method="post" class="haku">
	<input type="text" name="haku" placeholder="Tuotenumero">
	<input class="nappi" type="submit" value="Hae">
</form>
<form name="ostoskorilomake" method="post" action="tuotehaku.php">
	<input id="ostoskori_toiminto" type="hidden" name="ostoskori_toiminto" value="">
	<input id="ostoskori_tuote" type="hidden" name="ostoskori_tuote">
	<input id="ostoskori_maara" type="hidden" name="ostoskori_maara">
</form>
<script>

//
// Lisää annetun tuotteen ostoskoriin
//
function addToShoppingCart(articleId) {
	var count = document.getElementById('maara_' + articleId).value;
	document.getElementById('ostoskori_toiminto').value = 'lisaa';
	document.getElementById('ostoskori_tuote').value = articleId;
	document.getElementById('ostoskori_maara').value = count;
	document.ostoskorilomake.submit();
}

//
// Poistaa annetun tuotteen ostoskorista
//
function removeFromShoppingCart(articleId) {
	document.getElementById('ostoskori_toiminto').value = 'poista';
	document.getElementById('ostoskori_tuote').value = articleId;
	document.ostoskorilomake.submit();
}

//
// Tyhjentää koko ostoskorin
//
function emptyShoppingCart(articleId) {
	document.getElementById('ostoskori_toiminto').value = 'tyhjenna';
	document.ostoskorilomake.submit();
}

</script>
<?php

require 'tecdoc.php';
require 'tietokanta.php';
require 'apufunktiot.php';

$connection = mysqli_connect(DB_HOST, DB_USERNAME, DB_PASSWORD, DB_NAME) or die('Tietokantayhteyttä ei voitu muodostaa: ' . mysqli_connect_error());

//
// Lisää tuotteen ostoskoriin
//
function add_product_to_shopping_cart($id) {
	return true;
}

//
// Poistaa tuotteen ostoskorista
//
function remove_product_from_shopping_cart($id) {
	return true;
}

//
// Tyhjentää ostoskorin
//
function empty_shopping_cart() {
	return true;
}

//
// Hakee tuotteista vain sellaiset, joilla on haluttu tuotenumero
//
function filter_by_article_no($products, $articleNo) {
	// Korvaa jokerimerkit * ja ? säännöllisen lausekkeen vastineilla
	// ja jättää muut säännöllisten lausekkeiden merkinnät huomioimatta.
	function replace_wildcards($string) {
		$replaced = preg_quote($string);
		$replaced = str_replace('\*', '.*', $replaced);
		$replaced = str_replace('\?', '.', $replaced);
		return $replaced;
	}

	$articleNo = replace_wildcards($articleNo);
	$regexp = '/^' . $articleNo . '$/i';  // kirjainkoolla ei väliä
	$filtered = [];

	foreach ($products as $product) {
		if (preg_match($regexp, $product->articleNo)) {
			array_push($filtered, $product);
		}
	}
	return $filtered;
}

//
// Hakee tuotevalikoimasta tuotteet tuotenumeron perusteella
//
function search_for_product_in_catalog($number) {
	global $connection;

	$number = addslashes(trim($number));
	$result = mysqli_query($connection, "SELECT id, hinta, varastosaldo, minimisaldo FROM tuote;");

	if ($result) {
		$products = [];
		while ($row = mysqli_fetch_object($result)) {
			array_push($products, $row);
		}
		if (count($products) > 0) {
			merge_products_with_tecdoc($products);
			$products = filter_by_article_no($products, $number);
		}
		return $products;
	}
	return [];
}

$cart_action = isset($_POST['ostoskori_toiminto']) ? $_POST['ostoskori_toiminto'] : null;
if ($cart_action) {
	$cart_product = isset($_POST['ostoskori_tuote']) ? $_POST['ostoskori_tuote'] : null;
	$cart_amount = isset($_POST['ostoskori_maara']) ? $_POST['ostoskori_maara'] : null;

	if ($cart_action === 'lisaa') {
		if (add_product_to_shopping_cart($cart_product, $cart_amount)) {
			echo '<p class="success">Tuote lisätty ostoskoriin.</p>';
		} else {
			echo '<p class="error">Tuotteen lisäys ei onnistunut.</p>';
		}
	} elseif ($cart_action === 'poista') {
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

$number = isset($_POST['haku']) ? $_POST['haku'] : null;
if ($number) {
	echo '<div class="tulokset">';
	echo '<h2>Tulokset</h2>';
	$products = search_for_product_in_catalog($number);
	if (count($products) > 0) {
		echo '<table>';
		echo '<tr><th>Tuotenumero</th><th>Tuote</th><th style="text-align: right;">Hinta</th><th style="text-align: right;">Varastosaldo</th><th>Kpl</th></tr>';
		foreach ($products as $product) {
			echo '<tr>';
			echo "<td>$product->articleNo</td>";
			echo "<td>$product->brandName $product->articleName</td>";
			echo "<td style=\"text-align: right;\">" . format_euros($product->hinta) . "</td>";
			echo "<td style=\"text-align: right;\">" . format_integer($product->varastosaldo) . "</td>";
			echo "<td style=\"padding-top: 0; padding-bottom: 0;\"><input id=\"maara_" . $product->articleId . "\" name=\"maara_" . $product->articleId . "\" class=\"maara\" type=\"number\" value=\"0\" min=\"0\"></td>";
			echo "<td class=\"toiminnot\"><a class=\"nappi\" href=\"javascript:void(0)\" onclick=\"addToShoppingCart($product->articleId)\">Osta</a></td>";
			echo '</tr>';
		}
		echo '</table>';
	} else {
	   echo '<p>Ei tuloksia.</p>';
	}
	echo '</div>';
}

?>

</body>


</body>
</html>
