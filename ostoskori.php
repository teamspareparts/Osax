<!DOCTYPE html>
<html lang="fi">
<head>
	<link rel="stylesheet" href="css/styles.css">
	<meta charset="UTF-8">
	<title>Ostoskori</title>
</head>
<body>
<?php include('header.php');?>
<h1 class="otsikko">Ostoskori</h1>
<?php include('ostoskori_lomake.php'); ?>
<?php

require 'tecdoc.php';
require 'tietokanta.php';
require 'apufunktiot.php';

$connection = mysqli_connect(DB_HOST, DB_USERNAME, DB_PASSWORD, DB_NAME) or die('Tietokantayhteyttä ei voitu muodostaa: ' . mysqli_connect_error());

//
// Hakee tietokannasta kaikki tuotevalikoimaan lisätyt tuotteet
//
function get_products_in_shopping_cart() {
	global $connection;

    $cart = get_shopping_cart();
    if (empty($cart)) {
        return [];
    }

    $ids = addslashes(implode(', ', array_keys($cart)));
	$result = mysqli_query($connection, "SELECT id, hinta, varastosaldo, minimisaldo, minimimyyntiera FROM tuote WHERE id in ($ids);");

	if ($result) {
		$products = [];
		while ($row = mysqli_fetch_object($result)) {
            $row->cartCount = $cart[$row->id];
			array_push($products, $row);
		}
		merge_products_with_tecdoc($products);
		return $products;
	}
	return [];
}

handle_shopping_cart_action();

$products = get_products_in_shopping_cart();

if (empty($products)) {
    echo '<div class="tulokset"><p>Ostoskorissa ei ole tuotteita.</p></div>';
} else {
    echo '<div class="tulokset">';
    echo '<table>';
    echo '<tr><th>Tuotenumero</th><th>Tuote</th><th style="text-align: right;">Hinta</th><th style="text-align: right;">Varastosaldo</th><th>Kpl</th></tr>';
    foreach ($products as $product) {
		$article = $product->directArticle;
        echo '<tr>';
        echo "<td>$article->articleNo</td>";
        echo "<td>$article->brandName $article->articleName</td>";
        echo "<td style=\"text-align: right;\">" . format_euros($product->hinta) . "</td>";
        echo "<td style=\"text-align: right;\">" . format_integer($product->varastosaldo) . "</td>";
        echo "<td style=\"padding-top: 0; padding-bottom: 0;\"><input id=\"maara_" . $article->articleId . "\" name=\"maara_" . $article->articleId . "\" class=\"maara\" type=\"number\" value=\"" . $product->cartCount . "\" min=\"0\"></td>";
        echo "<td class=\"toiminnot\"><a class=\"nappi\" href=\"javascript:void(0)\" onclick=\"modifyShoppingCart($article->articleId)\">Päivitä</a></td>";
        echo '</tr>';
    }
    echo '</table>';
	echo '<p><a class="nappi" href="tilaus.php">Tilaa tuotteet</a></p>';
    echo '</div>';
}

?>

</body>
</body>
</html>
