<!DOCTYPE html>
<html lang="fi">

<head>
	<link rel="stylesheet" href="css/styles.css">
	<meta charset="UTF-8">
	<meta name="description" content="Asiakkaalle näkyvä pohja">
	<title>Tuotehaku</title>

</head>
<body>

	<?php include('header_asiakas.php');?>
	<h1 class="otsikko">Tuotehaku</h1>
	<form action="tuotehaku.php" method="post" class="haku">
		<input type="text" name="haku" placeholder="Tuotenumero">
		<input type="submit" value="Hae">
	</form>
<?php

require 'tecdoc.php';
require 'tietokanta.php';

$email = isset($_SESSION['email']) ? addslashes($_SESSION['email']) : false;
$email = 'testi@testi.testi';
$admin = false;

$result = false;
if ($email) {
	$connection = mysqli_connect(DB_HOST, DB_USERNAME, DB_PASSWORD, DB_NAME) or die('Tietokantayhteyttä ei voitu muodostaa: ' . mysqli_connect_error());
	$result = mysqli_query($connection, "SELECT yllapitaja FROM kayttaja WHERE sahkoposti='$email';");
}

// Tarkistetaan, onko käyttäjä kirjautunut sisään
if ($result) {
	$admin = (bool) mysqli_fetch_row($result)[0];
	$number = isset($_POST['haku']) ? $_POST['haku'] : false;

	if ($number) {
		echo '<div class="tulokset">';
		echo '<h2>Tulokset:</h2>';
		$products = get_products_by_number($number);
		if (count($products) > 0) {
			echo '<table>';
			echo '<tr><th>Tuote</th><th>Valmistaja</th><th>Tuotenumero</th></tr>';
			foreach ($products as $product) {
				echo '<tr>';
				echo "<td>$product->articleName</td>";
				echo "<td>$product->brandName</td>";
				echo "<td>$product->articleNo</td>";
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
