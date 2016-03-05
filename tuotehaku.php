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
<?php

require 'tecdoc.php';
require 'tietokanta.php';

$connection = mysqli_connect(DB_HOST, DB_USERNAME, DB_PASSWORD, DB_NAME) or die('Tietokantayhteyttä ei voitu muodostaa: ' . mysqli_connect_error());

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

?>

</body>


</body>
</html>
