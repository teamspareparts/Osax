<!doctype html>
<html lang="fi">
<head>
	<meta charset="UTF-8">
	<title>Laskutustesti</title>
</head>
<body>
<?php
require './laskutiedot.class.php';
require './tietokanta.php';

if ( !empty($_POST['tilaus']) ) {
	$lasku = new Laskutiedot( $_POST['tilaus'], $db );

	$lasku->haeTilauksenTiedot();

	$lasku = $lasku->tulostaLasku();
}
?>

<h3>Alustava laskutietojen etsintä, ja testaus. WIP.</h3>
<form action="#" method="post">
	<label>Anna tilauksen numero, ja klikkaa Hae-nappia. (Numero on luultavasti jotain ykkösen luokkaa)</label><br>
	<input type="number" name="tilaus" value="0" min="0" title="Tilausnumero">
	<input type="submit" value="Hae laskun tiedot">
</form><br>
<hr>


<?php echo isset($lasku) ? $lasku : "Haettavan laskun tiedot tulostetaan tähän." ?>

<body>
<html>

