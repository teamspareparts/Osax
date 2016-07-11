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
require 'ostoskori_tilaus_funktiot.php';

handle_shopping_cart_action();

$products = get_products_in_shopping_cart();

if ( $products ) {
	$sum = 0.0;
	$rahtimaksu = [15, 200]; //sisältää sekä rahtimaksun, että ilmaisen toimituksen rajan. Varsinaiset arvot lasketaan myöhemmin.?>
	
	<!-- HTML -->
    <div class="tulokset">
	    <table>
		    <tr><th>Kuva</th><th>Tuotenumero</th><th>Tuote</th><th>Info</th><th>EAN</th><th>OE</th><th style="text-align: right; ">Hinta</th><th style="text-align: right;">Varastosaldo</th><th style="text-align: right;">Minimimyyntierä</th><th>Kpl</th><th>Muuta</th></tr>
		    <?php
		    foreach ($products as $product) {
		        $article = $product->directArticle;
				$product->hinta = tarkista_hinta_era_alennus( $product );
				$sum += $product->hinta * $product->cartCount;?>
				<!-- HTML -->
		       	<tr>
			        <td class="thumb"><img src="<?= $product->thumburl?>" alt="<?= $article->articleName?>"></td>
			        <td><?= $article->articleNo?></td>
			        <td><?= $article->brandName?> <br> <?= $article->articleName?></td>
			        <td><?= tulosta_product_infos_part($product->infos)?></td>
			        <td><?= $product->ean ?></td>
			        <td><?= tulosta_taulukko($product->oe)?></td>
			        <td style="text-align: right; white-space: nowrap;"><?= format_euros( $product->hinta ) ?></td>
			        <td style="text-align: right;"><?= format_integer($product->varastosaldo) ?></td>
			        <td style="text-align: right;"><?= format_integer($product->minimimyyntiera) ?></td>
			        <td style="padding-top: 0; padding-bottom: 0;"><input id="maara_<?= $article->articleId ?>" name="maara_<?= $article->articleId ?>" class="maara" type="number"value="<?= $product->cartCount ?>" min="0" style="text-align: right;"></td>
			        <td style="padding-top: 0; padding-bottom: 0;"><?= laske_era_alennus_tulosta_huomautus( $product, TRUE )?></td>
			        <td class="toiminnot"><a class="nappi" href="javascript:void(0)" onclick="modifyShoppingCart(<?= $article->articleId?>)">Päivitä</a></td>
		        </tr>
		        <!-- HTML END --><?php 
		    }
			tulosta_rahtimaksu_tuotelistaan( TRUE )?>
		<!-- HTML -->
	    </table>
	    <div id=tilausvahvistus_maksutiedot style="width:20em;">
			<p>Tuotteiden kokonaissumma: <b><?= format_euros($sum)?></b></p>
			<p>Summa yhteensä: <b><?= format_euros($sum+$rahtimaksu[0])?></b> ( ml. toimitus )</p>
	    </div>
	    <?= tarkista_pystyyko_tilaamaan_ja_tulosta_tilaa_nappi_tai_disabled( $products, TRUE )// Varmistetaan, että tuotteita on varastossa ja ainakin minimimyyntierän verran?>
	    <p><a class="nappi" href="tuotehaku.php" style="background:rgb(200, 70, 70);border-color: #b70004;">Palaa takaisin</a></p>
    </div>
<?php 
} else { //Products-array is empty?>
    <div class="tulokset">
    	<p>Ostoskorissa ei ole tuotteita.</p>
    </div>
<?php } ?>

</body>
</body>
</html>