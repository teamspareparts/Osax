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
		<input type="submit" value="Hae">
	</form>
<?php

require 'tecdoc.php';
require 'tietokanta.php';

$connection = mysqli_connect(DB_HOST, DB_USERNAME, DB_PASSWORD, DB_NAME) or die('Tietokantayhteyttä ei voitu muodostaa: ' . mysqli_connect_error());

//
// Hakee tuotteiden ID:iden perusteella TecDocista kunkin tuotteen tiedot ja yhdistää ne
// TODO: Käytetään myös yp_tuotteet.php-sivulla. Voisi sijoittaa johonkin keskeisempään paikkaan.
//
function merge_products_with_tecdoc($products) {
	// Kerätään tuotteiden ID:t taulukkoon
	$ids = [];
	foreach ($products as $product) {
		array_push($ids, $product->id);
	}

	// Haetaan tuotteiden tiedot TecDocista ID:iden perusteella
	$tecdoc_products = get_products_by_id($ids);

	// Yhdistetään TecDocista saatu data $products-taulukkoon
	foreach ($tecdoc_products as $tecdoc_product) {
		foreach ($products as $product) {
			if ($product->id == $tecdoc_product->articleId) {
				foreach ($tecdoc_product as $key => $value) {
					$product->{$key} = $value;
				}
			}
		}
	}
}

//
// Hakee tuotteista vain sellaiset, joilla on haluttu tuotenumero
//
function filter_by_article_no($products, $articleNo) {
	$filtered = [];
	foreach ($products as $product) {
		if (stripos($product->articleNo, $articleNo) !== false) {
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

// Tarkistetaan, onko käyttäjä kirjautunut sisään
if (is_logged_in()) {
	$number = isset($_POST['haku']) ? $_POST['haku'] : false;
	if ($number) {
		echo '<div class="tulokset">';
		echo '<h2>Tulokset</h2>';
		$products = search_for_product_in_catalog($number);
		if (count($products) > 0) {
			echo '<table>';
			echo '<tr><th>Tuote</th><th>Valmistaja</th><th>Tuotenumero</th><th>Hinta</th><th>Varastosaldo</th><th>Minimisaldo</th><th>Toiminnot</th></tr>';
			foreach ($products as $product) {
				echo '<tr>';
				echo "<td>$product->articleName</td>";
				echo "<td>$product->brandName</td>";
				echo "<td>$product->articleNo</td>";
				echo "<td>$product->hinta</td>";
				echo "<td>$product->varastosaldo</td>";
				echo "<td>$product->minimisaldo</td>";
				echo "<td><a href=\"javascript:void(0)\" onclick=\"showRemoveDialog($product->id)\">Poista valikoimasta</a></td>";
				echo '</tr>';
			}
			echo '</table>';
		} else {
		   echo '<p>Ei tuloksia.</p>';
		}
		echo '</div>';
	}
} else {
	echo '<div class="tulokset"><p>Et ole kirjautunut sisään!</p></div>';
}

?>

</body>


</body>
</html>
