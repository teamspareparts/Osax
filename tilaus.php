<!DOCTYPE html>
<html lang="fi">
<head>
	<link rel="stylesheet" href="css/styles.css">
	<meta charset="UTF-8">
	<title>Vahvista tilaus</title>
</head>
<body>
<?php include('header.php');?>
<h1 class="otsikko">Vahvista tilaus</h1>
<?php include('ostoskori_lomake.php'); ?>
<?php

require 'tecdoc.php';
require 'tietokanta.php';
require 'apufunktiot.php';
require 'email.php';

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

//
// Tilaa ostoskorissa olevat tuotteet
//
function order_products($products) {
	global $connection;

	if (empty($products)) {
		return false;
	}

	// Lisätään uusi tilaus
	$user_id = addslashes($_SESSION['id']);
	$result = mysqli_query($connection, "INSERT INTO tilaus (kayttaja_id) VALUES ($user_id);");

	if (!$result) {
		return false;
	}

	$order_id = mysqli_insert_id($connection);

	// Lisätään tilaukseen liittyvät tuotteet
	foreach ($products as $product) {
		$article = $product->directArticle;
		$product_id = addslashes($article->articleId);
		$product_price = addslashes($product->hinta);
		$product_count = addslashes($product->cartCount);
		$result = mysqli_query($connection, "INSERT INTO tilaus_tuote (tilaus_id, tuote_id, pysyva_hinta, kpl) VALUES ($order_id, $product_id, $product_price, $product_count);");
		if (!$result) {
			return false;
		}
	}

	/**
	 * Laitan sähköpostin lähetyksen kommentiksi niin kukaan ei lähettele vahingossa sähköpostia
	 */
	//lähetetään tilausvahvistus asiakkaalle
	//laheta_tilausvahvistus($_SESSION["email"], $products, $order_id);
	//lähetetään tilaus ylläpidolle
	//laheta_tilaus_yllapitajalle($_SESSION["email"], $products, $order_id);
	return true;
}

$products = get_products_in_shopping_cart();

if (isset($_GET['vahvista'])) {
	if (order_products($products)) {
		echo '<p class="success">Tilaus lähetetty!</p>';
		empty_shopping_cart();
	} else {
		echo '<p class="error">Tilauksen lähetys ei onnistunut!</p>';
	}
} else {
    if (empty($products)) {
        echo '<p class="error">Ostoskorissa ei ole tuotteita.</p>';
    } else {
        $sum = 0.0;
        echo '<div class="tulokset">';
        echo '<table>';
        echo '<tr><th>Kuva</th><th>Tuotenumero</th><th>Tuote</th><th>Info</th><th>EAN</th><th>OE</th><th style="text-align: right;">Hinta</th><th style="text-align: right;">Varastosaldo</th><th style="text-align: right;">Minimimyyntierä</th><th>Kpl</th></tr>';
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
            echo "<td>$product->oe</td>";
            echo "<td style=\"text-align: right;\">" . format_euros($product->hinta) . "</td>";
            echo "<td style=\"text-align: right;\">" . format_integer($product->varastosaldo) . "</td>";
            echo "<td style=\"text-align: right;\">" . format_integer($product->minimimyyntiera) . "</td>";
            echo "<td style=\"text-align: right;\">$product->cartCount</td>";
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
            echo '<p><a class="nappi" href="tilaus.php?vahvista">Vahvista tilaus</a></p>';
        } else {
            echo '<p><a class="nappi disabled">Vahvista tilaus</a> Tuotteita ei voi tilata, koska niitä ei ole tarpeeksi varastossa tai minimimyyntierää ei ole ylitetty.</p>';
        }

        echo '</div>';
    }
}

?>

</body>
</body>
</html>
