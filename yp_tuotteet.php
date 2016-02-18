<!DOCTYPE html>
<html lang="fi">
<head>
	<meta charset="UTF-8">
	<link rel="stylesheet" href="css/styles.css">
	<title>Tuotteet</title>
</head>
<body>
<?php include("header_yllapito.php");?>
<h1 class="otsikko">Tuotteet</h1>
<form action="yp_tuotteet.php" method="post" class="haku">
	<input type="text" name="haku" placeholder="Tuotenumero">
	<input type="submit" value="Hae">
</form>
<?php

require 'tecdoc.php';
require 'tietokanta.php';

$connection = mysqli_connect(DB_HOST, DB_USERNAME, DB_PASSWORD, DB_NAME) or die('Tietokantayhteyttä ei voitu muodostaa: ' . mysqli_connect_error());

//
// Lisää uuden tuotteen valikoimaan
//
function add_product_to_catalog($id, $price, $count, $minimum_count) {
	global $connection;
	$id = intval($id);
	$price = doubleval($price);
	$count = intval($count);
	$minimum_count = intval($minimum_count);
	$result = mysqli_query($connection, "INSERT INTO tuote (id, hinta, varastosaldo, minimisaldo) VALUES ($id, $price, $count, $minimum_count);");
	return $result;
}

//
// Poistaa tuotteen valikoimasta
//
function remove_product_from_catalog($id) {
	global $connection;
	$id = intval($id);
	mysqli_query($connection, "DELETE FROM tuote WHERE id=$id;");
	return mysqli_affected_rows($connection) > 0;
}

//
// Hakee tietokannasta kaikki tuotevalikoimaan lisätyt tuotteet
//
function get_products_in_catalog() {
	global $connection;
	$result = mysqli_query($connection, "SELECT id, hinta, varastosaldo, minimisaldo FROM tuote;");
	if ($result) {
		$products = [];
		while ($row = mysqli_fetch_object($result)) {
			array_push($products, $row);
		}
		// TODO: Hae TecDocista kukin tuote ID:n perusteella ja palauta ne!
		return $products;
	}
	return [];
}

$email = isset($_SESSION['email']) ? addslashes($_SESSION['email']) : false;
$email = 'testi@testi.testi';
$admin = false;

$result = false;
if ($email) {
	$connection = mysqli_connect(DB_HOST, DB_USERNAME, DB_PASSWORD, DB_NAME) or die('Tietokantayhteyttä ei voitu muodostaa: ' . mysqli_connect_error());
	$result = mysqli_query($connection, "SELECT yllapitaja FROM kayttaja WHERE sahkoposti='$email';");
}

if ($result) {
	$admin = (bool) mysqli_fetch_row($result)[0];
	$number = isset($_POST['haku']) ? $_POST['haku'] : false;

	if (isset($_GET['lisaa'])) {
		// TODO: Käyttäjän määrittelemä hinta, varastosaldo ja minimisaldo
		$success = add_product_to_catalog($_GET['lisaa'], 0, 0, 0);
		if ($success) {
			echo '<p class="success">Tuote lisätty!</p>';
		} else {
			echo '<p class="error">Tuotteen lisäys epäonnistui!</p>';
		}
	} elseif (isset($_GET['poista'])) {
		$success = remove_product_from_catalog($_GET['poista']);
		if ($success) {
			echo '<p class="success">Tuote poistettu!</p>';
		} else {
			echo '<p class="error">Tuotteen poisto epäonnistui!<br>Luultavasti kyseistä tuotetta ei ollut valikoimassa.</p>';
		}
	}

	if ($number) {
		echo '<div class="tulokset">';
		echo '<h2>Tulokset:</h2>';
		$products = get_products_by_number($number);
		if (count($products) > 0) {
			echo '<table>';
			echo '<tr><th>Tuote</th><th>Valmistaja</th><th>Tuotenumero</th><th>Toiminnot</th></tr>';
			foreach ($products as $product) {
				echo '<tr>';
				echo "<td>$product->articleName</td>";
				echo "<td>$product->brandName</td>";
				echo "<td>$product->articleNo</td>";
				echo "<td><a href=\"yp_tuotteet.php?lisaa=$product->articleId\">Lisää valikoimaan</a></td>";
				echo '</tr>';
			}
			echo '</table>';
		} else {
			echo '<p>Ei tuloksia.</p>';
		}
		echo '</div>';
	}

	// Näytetään nykyinen valikoima
	echo '<div class="tulokset">';
	echo '<h2>Valikoima</h2>';
	$products = get_products_in_catalog();
	if (count($products) > 0) {
		echo '<table>';
		//echo '<tr><th>Tuote</th><th>Valmistaja</th><th>Tuotenumero</th><th>Toiminnot</th></tr>';
		echo '<tr><th>ID</th><th>Hinta</th><th>Varastosaldo</th><th>Minimisaldo</th><th>Toiminnot</th></tr>';
		foreach ($products as $product) {
			echo '<tr>';
			/*
			echo "<td>$product->articleName</td>";
			echo "<td>$product->brandName</td>";
			echo "<td>$product->articleNo</td>";
			*/
			// TODO: Näytä tuotteiden oikeat tiedot (TecDocista haetut). Nämä on tässä vain tilapäisesti.
			echo "<td>$product->id</td>";
			echo "<td>$product->hinta</td>";
			echo "<td>$product->varastosaldo</td>";
			echo "<td>$product->minimisaldo</td>";
			echo "<td><a href=\"yp_tuotteet.php?poista=$product->id\">Poista valikoimasta</a></td>";
			echo '</tr>';
		}
		echo '</table>';
	} else {
		echo '<p>Ei tuotteita valikoimassa.</p>';
	}
	echo '</div>';
} else {
	echo '<div class="tulokset"><p>Et ole kirjautunut sisään!</p></div>';
}

?>

</body>
</html>
