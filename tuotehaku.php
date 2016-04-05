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
<?php
$cart_contents = '0 tuotetta';
if (isset($_SESSION['cart'])) {
	$cart_count = count($_SESSION['cart']);
	if ($cart_count === 1) {
		$cart_contents = '1 tuote';
	} else {
		$cart_contents = $cart_count . ' tuotetta';
	}
}
?>
<div id="ostoskori-linkki"><a href="ostoskori.php">Ostoskori (<?php echo $cart_contents; ?>)</a></div>
<form action="tuotehaku.php" method="post" class="haku">
	<input type="text" name="haku" placeholder="Tuotenumero">
	<input class="nappi" type="submit" value="Hae">
</form>
<?php include('ostoskori_lomake.php'); ?>
<?php

require 'tecdoc.php';
require 'tietokanta.php';
require 'apufunktiot.php';

$connection = mysqli_connect(DB_HOST, DB_USERNAME, DB_PASSWORD, DB_NAME) or die('Tietokantayhteyttä ei voitu muodostaa: ' . mysqli_connect_error());

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

handle_shopping_cart_action();

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
