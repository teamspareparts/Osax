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
		SELECT	id, hinta_ilman_alv, varastosaldo, minimisaldo, minimimyyntiera,
			(hinta_ilman_alv * (1+alv_kanta.prosentti)) AS hinta,
			alv_kanta.prosentti AS alv_prosentti
		FROM	tuote  
		LEFT JOIN	alv_kanta
		ON		tuote.alv_kanta = alv_kanta.kanta
		WHERE 	id in ($ids);");

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
		$product_price = addslashes($product->hinta_ilman_alv);
		$alv_prosentti = addslashes($product->alv_prosentti);
		$product_count = addslashes($product->cartCount);
		$result = mysqli_query($connection, "
			INSERT INTO tilaus_tuote 
				(tilaus_id, tuote_id, pysyva_hinta, pysyva_alv, kpl) 
			VALUES 
				($order_id, $product_id, $product_price, $alv_prosentti, $product_count);");
		if (!$result) {
			return false;
		}
		//päivitetään varastosaldo
		$uusi_varastosaldo = $product->varastosaldo - $product_count;
		$query = "
			UPDATE	tuote
			SET		varastosaldo = '$uusi_varastosaldo'
			WHERE 	id = '$product_id'";
		$result = mysqli_query($connection, $query);
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

/*
 * Ottaa taulukon, lyhentää sen, ja pistää wordwrapin.
 * Tarkoitettu OE-koodien tulostukseen, mutta melko yleiskäyttöinen.
 * Param: $array, tulostettava taulukko.
 * Return: Merkkijono, jonka voi suoraan tulostaa
 */
function tulosta_taulukko ( $array ) {
	$tulostus = "";
	foreach ($array as $data) {
		$tulostus .= $data . " | ";
	}
	$tulostus = mb_strimwidth($tulostus, 0, 35, "..."); //Lyhentää tulostuksen tiettyyn mittaan (35 tässä tapauksessa)
	$tulostus = wordwrap($tulostus, 10, " ", true); //... ja wordwrap, 10 merkkiä pisin OE sillä hetkellä korissa
	return $tulostus;
}

/*
 * Tulostaa rahtimaksun. Laskee onko tilauksen summa >200€, ja sen mukaan tulostaa joko
 * 0€ tai $rahtimaksu € rahtimaksuksi.
 * Param: ---
 * Return: --- (Tulostaa suoraan funktion sisällä)
 */
function tulosta_rahtimaksu () {
	global $sum;
	global $rahtimaksu;
	
	if ($sum > 200) {  //Ilmainen toimitus tilauksille yli 200€
		echo "<b><s>" . $rahtimaksu . "€</s> <ins>Yli 200€ tilauksille ilmainen toimitus!</ins></b>"; 
		$rahtimaksu = 0;
	} else { 
		echo "<b>" . $rahtimaksu . "€</b>"; }
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
            echo "<td>" . tulosta_taulukko($product->oe) . "</td>";
            echo "<td style=\"text-align: right;\">" . format_euros($product->hinta) . "</td>";
            echo "<td style=\"text-align: right;\">" . format_integer($product->varastosaldo) . "</td>";
            echo "<td style=\"text-align: right;\">" . format_integer($product->minimimyyntiera) . "</td>";
            echo "<td style=\"text-align: right;\">$product->cartCount</td>";
            echo '</tr>';

    		$sum += $product->hinta * $product->cartCount;
    		$rahtimaksu = 15;
        }
        echo '</table>';
        /*
		echo '<p>Tuotteiden kokonaissumma: <b>' . format_euros($sum) . '</b></p>';
		echo '<p>Rahtimaksu: <b>';
	    if ($sum > 200) { echo '<s>15€</s> <ins>Yli 200€ tilauksille ilmainen toimitus!</ins></b></p>'; }
		else { echo '15€ </b></p>'; }
    	echo '<p>Summa yhteensä: <b>' . format_euros($sum+15) . '</b></p>';
*/
    	?>
    	
    	<div id=tilausvahvistus_tilaustiedot_container style="display:flex; border:1px solid;">
	    	<div id=tilausvahvistus_maksutiedot style="width:20em; height:7em; border:1px solid;">
		    	<p>Tuotteiden kokonaissumma: <b><?= format_euros($sum)?></b></p>
		    	<p>Rahtimaksu: <?= tulosta_rahtimaksu() ?></p>
		    	<p>Summa yhteensä: <b><?= format_euros($sum+$rahtimaksu)?></b></p>
	    	</div>
	    	<div id=tilausvahvistus_toimitusosoite_nappi style="width:10em; height:7em; border:1px solid;">
				<div style="margin:2%;
    background-color:#888888;
    width:30%;
    padding-bottom:30%; /* relative size and position on page */
    float: left;
    position:relative;  /* coord system stop */
    top:0px;">
					<div style="position: absolute;
    text-align: center;
    padding-top:100%;
    -webkit-transform: translateY(-50%); /* child now centers itself relative to the midline based on own contents */
    -moz-transform: translateY(-50%);
    -o-transform: translateY(-50%);
    -ms-transform: translateY(-50%);
    transform: translateY(-50%);">This text</div>
				</div>
		    	<!-- Osoitteen valinta 
				Nappi tähän, josta aukeaa Modal-ikkuna, jossa listaus. -->
	    	</div>
	    	<div id=tilausvahvistus_toimitusosoite_nappi style="flex-grow:1; border:1px solid; margin:1px;">
		    	<p>OSOITTEEN TULOSTUS</p>
		    	<!-- Osoitteen valinta 
				Nappi tähän, josta aukeaa Modal-ikkuna, jossa listaus. -->
	    	</div>
    	</div>
    	<?php 
    	
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

        echo '<p><a class="nappi" href="ostoskori.php" style="background:rgb(180, 0, 6);border-color: #b70004;">Peruuta</a></p>';
        echo '</div>';
    }
}

?>

</body>
</body>
</html>
