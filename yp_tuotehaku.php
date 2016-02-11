<!DOCTYPE html>
<html lang="fi">
<head>
	<meta charset="UTF-8">
	<link rel="stylesheet" href="css/styles.css">
	<title>Tuotehaku</title>
</head>
<body>
<?php include("header_yllapito.php");?>
<h1 class="otsikko">Tuotehaku</h1>
<p>
	<form action="yp_tuotehaku.php" method="post">
		<input type="text" name="haku" placeholder="Tuotenumero">
		<input type="submit" value="Hae">
	</form>
</p>
<?php

require 'tecdoc.php';

$number = isset($_POST['haku']) ? $_POST['haku'] : false;

if ($number) {
	echo '<h2>Tulokset:</h2>';
	foreach (get_products_by_number($number) as $product) {
		echo '<p>';
		echo "<b>Nimi:</b> $product->articleName<br>";
		echo "<b>Valmistaja</b>: $product->brandName<br>";
		echo "<b>Tuotenumero:</b>: $product->articleNo<br>";
		echo '</p>';
	}
}

?>
</body>
</html>
