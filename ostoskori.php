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
	$result = mysqli_query($connection, "
			SELECT	id, (hinta_ilman_alv * (1+alv_kanta.prosentti)) AS hinta, varastosaldo, minimisaldo, minimimyyntiera
			FROM	tuote 
			JOIN	alv_kanta
			ON		tuote.alv_kanta = alv_kanta.kanta
			WHERE	id in ($ids);");

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

function tulosta_taulukko ( $array ) {
	$tulostus = "";
	foreach ($array as $data) {
		$tulostus .= $data . " | ";
	}
	$tulostus = mb_strimwidth($tulostus, 0, 35, "..."); //Lyhentää tulostuksen tiettyyn mittaan (10 tässä tapauksessa)
	$tulostus = wordwrap($tulostus, 10, " ", true); //... ja wordwrap
	return $tulostus;
}

handle_shopping_cart_action();

$products = get_products_in_shopping_cart();

if (empty($products)) {
    echo '<div class="tulokset"><p>Ostoskorissa ei ole tuotteita.</p></div>';
} else {
	$sum = 0.0;
	?><!-- HTML -->
    <div class="tulokset">
    <table>
    <tr><th>Kuva</th><th>Tuotenumero</th><th>Tuote</th><th>Info</th><th>EAN</th><th>OE</th>
    	<th style="text-align: right;">Hinta</th><th style="text-align: right;">Varastosaldo</th>
    	<th style="text-align: right;">Minimimyyntierä</th><th>Kpl</th></tr>
    <?php
    foreach ($products as $product) {
        $article = $product->directArticle;
        echo '<tr>';
        echo "<td class=\"thumb\"><img src=\"$product->thumburl\" alt=\"$article->articleName\"></td>";
        echo "<td>$article->articleNo</td>";
        echo "<td>$article->brandName <br> $article->articleName</td>";
        echo "<td>";
        foreach ($product->infos as $info){
            if(!empty($info->attrName)) echo $info->attrName . " ";
            if(!empty($info->attrValue)) echo $info->attrValue . " ";
            if(!empty($info->attrUnit)) echo $info->attrUnit . " ";
            echo "<br>";
        }
        echo "</td>";
        echo "<td>$product->ean</td>";
        echo "<td>" . tulosta_taulukko($product->oe) . "</td>";
        echo "<td style=\"text-align: right;\">" . format_euros($product->hinta) . "</td>";
        echo "<td style=\"text-align: right;\">" . format_integer($product->varastosaldo) . "</td>";
        echo "<td style=\"text-align: right;\">" . format_integer($product->minimimyyntiera) . "</td>";
        echo "<td style=\"padding-top: 0; padding-bottom: 0;\"><input id=\"maara_" . $article->articleId . "\" name=\"maara_" . $article->articleId . "\" class=\"maara\" type=\"number\" value=\"" . $product->cartCount . "\" min=\"0\"></td>";
        echo "<td class=\"toiminnot\"><a class=\"nappi\" href=\"javascript:void(0)\" onclick=\"modifyShoppingCart($article->articleId)\">Päivitä</a></td>";
        echo '</tr>';

		$sum += $product->hinta * $product->cartCount;
    }
    echo '</table>';
	echo '<p>Summa yhteensä: <b>' . format_euros($sum) . '</b></p>';

    // Varmistetaan, että tuotteita on varastossa ja ainakin minimimyyntierän verran
    $enough_in_stock = true;
    $enough_ordered = true;
    foreach ($products as $product) {
        if ($product->cartCount > $product->varastosaldo) {
            $enough_in_stock = false;
        }
        if ($product->cartCount < $product->minimimyyntiera) {
            $enough_ordered = false;
        }
    }
    $can_order = $enough_in_stock && $enough_ordered;

    if ($can_order) {
    	?><p><a class="nappi" href="tilaus.php">Tilaa tuotteet</a></p><?php 
    } else {
        ?><p><a class="nappi disabled">Tilaa tuotteet</a> Tuotteita ei voi tilata, koska niitä ei ole tarpeeksi varastossa tai minimimyyntierää ei ole ylitetty.</p><?php 
    }

    ?></div><?php 
}

?>

</body>
</body>
</html>
