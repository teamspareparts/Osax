<!DOCTYPE html>
<html lang="fi">
<head>
	<meta charset="UTF-8">
	<link rel="stylesheet" href="css/styles.css">
	<link rel="stylesheet" href="css/jsmodal-light.css">
	<title>Tuotteet</title>
</head>
<body>
<?php include("header.php");?>
<h1 class="otsikko">Tuotteet</h1>
<form action="yp_tuotteet.php" method="post" class="haku">
	<input type="text" name="haku" placeholder="Tuotenumero">
	<input class="nappi" type="submit" value="Hae">
</form>
<script src="js/jsmodal-1.0d.min.js"></script>
<script>

// Tuotteen lisäys valikoimaan
function showAddDialog(id) {
	Modal.open({
    	content: '\
			<div class="dialogi-otsikko">Lisää tuote</div> \
			<form action="yp_tuotteet.php" name="lisayslomake" method="post"> \
			<label for="hinta">Hinta:</label><br><input name="hinta" placeholder="0.00"><br><br> \
			<label for="varastosaldo">Varastosaldo:</label><br><input name="varastosaldo" placeholder="0"><br><br> \
			<label for="minimisaldo">Minimisaldo:</label><br><input name="minimisaldo" placeholder="0"><br><br> \
			<input class="nappi" type="submit" name="laheta" value="Lisää" onclick="document.lisayslomake.submit()"> \
			<input type="hidden" name="lisaa" value="' + id + '"> \
			</form>'
	});
}

// Tuotteen poisto valikoimasta
function showRemoveDialog(id) {
	Modal.open({
    	content: '\
		<div class="dialogi-otsikko">Poista tuote</div> \
		<p>Haluatko varmasti poistaa tuotteen valikoimasta?</p> \
		<p style="margin-top: 20pt;"><a class="nappi" href="yp_tuotteet.php?poista=' + id + '">Poista</a><a class="nappi" style="margin-left: 10pt;" href="javascript:void(0)" onclick="Modal.close()">Peruuta</a></p>'
	});
}

</script>
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

//
// Hakee tuotteiden ID:iden perusteella TecDocista kunkin tuotteen tiedot ja yhdistää ne
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
// Tulostaa hakutulokset
//
function print_results($number) {
	if (!$number) {
		return;
	}

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
			echo "<td><a href=\"javascript:void(0)\" onclick=\"showAddDialog($product->articleId)\">Lisää valikoimaan</a></td>";
			echo '</tr>';
		}
		echo '</table>';
	} else {
		echo '<p>Ei tuloksia.</p>';
	}
	echo '</div>';
}

//
// Tulostaa tuotevalikoiman
//
function print_catalog() {
	echo '<div class="tulokset">';
	echo '<h2>Valikoima</h2>';
	$products = get_products_in_catalog();
	if (count($products) > 0) {
		merge_products_with_tecdoc($products);

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
		echo '<p>Ei tuotteita valikoimassa.</p>';
	}
	echo '</div>';
}

if (is_logged_in()) {
	$number = isset($_POST['haku']) ? $_POST['haku'] : false;

	if (is_admin()) {
		if (isset($_POST['lisaa'])) {
			$id = intval($_POST['lisaa']);
			$hinta = doubleval(str_replace(',', '.', $_POST['hinta']));
			$varastosaldo = intval($_POST['varastosaldo']);
			$minimisaldo = intval($_POST['minimisaldo']);
			$success = add_product_to_catalog($id, $hinta, $varastosaldo, $minimisaldo);
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
				echo '<p class="error">Tuotteen poisto epäonnistui!<br><br>Luultavasti kyseistä tuotetta ei ollut valikoimassa.</p>';
			}
		}

		print_results($number);
		print_catalog();
	} else {
		echo '<div class="tulokset"><p>Et ole ylläpitäjä!</p></div>';
	}
} else {
	echo '<div class="tulokset"><p>Et ole kirjautunut sisään!</p></div>';
}

?>

</body>
</html>
