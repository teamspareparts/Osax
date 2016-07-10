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
			SELECT	id, (hinta_ilman_alv * (1+ALV_kanta.prosentti)) AS hinta, varastosaldo, minimisaldo, minimimyyntiera, alennusera_kpl, alennusera_prosentti
			FROM	tuote 
			JOIN	ALV_kanta
			ON		tuote.ALV_kanta = ALV_kanta.kanta
			WHERE	id in ($ids);");

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

function tulosta_taulukko ( $array ) {
	$tulostus = "";
	foreach ($array as $data) {
		$tulostus .= $data . " | ";
	}
	$tulostus = mb_strimwidth($tulostus, 0, 35, "..."); //Lyhentää tulostuksen tiettyyn mittaan (35 tässä tapauksessa)
	$tulostus = wordwrap($tulostus, 10, "<br />\n", true); //... ja wordwrap, 10 merkkiä pisin OE sillä hetkellä korissa
	return $tulostus;
}

function laske_era_alennus_tulosta_huomautus ( $product ) {
	$jakotulos =  $product->cartCount / $product->alennusera_kpl; //Onko tuotetta tilattu tarpeeksi eräalennukseen, tai huomautuksen tulostukseen
	
	$tulosta_huomautus = ( $jakotulos >= 0.75 && $jakotulos < 1 ) && ( $product->alennusera_kpl != 0 && $product->alennusera_prosentti != 0 );
	//Jos: kpl-määrä 75% alennuserä kpl-rajasta, mutta alle 100%. Lisäksi tuotteella on eräalennus asetettu (kpl-raja ei ole nolla, ja prosentti ei ole nolla).
	$tulosta_alennus = ( $jakotulos >= 1 ) && ( $product->alennusera_kpl != 0 && $product->alennusera_prosentti != 0 );
	//Jos: kpl-määrä yli 100%. Lisäksi tuotteella on eräalennus asetettu.
	
	if ( $tulosta_huomautus ) {
		$puuttuva_kpl_maara = $product->alennusera_kpl - $product->cartCount;
		$alennus_prosentti = round((float)$product->alennusera_prosentti * 100 ) . ' %';
		echo "Lisää $puuttuva_kpl_maara kpl saadaksesi $alennus_prosentti alennusta!";
		
	} elseif ( $tulosta_alennus ) { 
		$alennus_prosentti = round((float)$product->alennusera_prosentti * 100 ) . ' %';
		echo "Eräalennus ($alennus_prosentti) asetettu."; 
		
	} else { echo "---"; }
}

function tarkista_hinta_era_alennus ( $product ) {
	$jakotulos =  $product->cartCount / $product->alennusera_kpl;
	if ( $jakotulos >= 1 ) {
		echo "Woo! Alennus!";
		$alennus_prosentti = 1 - (float)$product->alennusera_prosentti;
		$product->hinta = ($product->hinta * $alennus_prosentti);
		return $product->hinta;
	
	} else { return $product->hinta; }
}

function laske_rahtimaksu() {
	global $row; //tarvitaan pari muuttujaa asiakaskohtaiseen rahtimaksuun liittyen
	global $connection;
	global $sum;
	
	$id = $_SESSION['id']; //Haetaan asiakkaan tiedot mahdollisesta yksilöllisestä rahtimaksusta
	$result = mysqli_query($connection, "SELECT	rahtimaksu, ilmainen_toimitus_summa_raja FROM kayttaja WHERE id = '$id';");
	$row = mysqli_fetch_array( $result, MYSQLI_ASSOC );
	
	$rahtimaksu = 15;
	$ilmaisen_toimituksen_raja = 200; // Default summa, jonka jälkeen saa ilmaisen toimituksen

	if ( $row["rahtimaksu"] !== 0.00 ) {//Asiakkaalla on asetettu rahtimaksu
		$rahtimaksu = $row["rahtimaksu"]; }

	if ( $row["ilmainen_toimitus_summa_raja"] !== 0.00 ) {	//Asiakkaalla on asetettu yksilöllinen ilmaisen toimituksen raja.
		$ilmaisen_toimituksen_raja = $row["ilmainen_toimitus_summa_raja"]; }

	if ( $sum > $ilmaisen_toimituksen_raja ) { //Onko tilaus-summa rajan yli?
		$rahtimaksu = 0; }

	return $rahtimaksu;
}

function tulosta_product_infos_part($infos) {
	foreach ( $infos as $info ) {
		if ( !empty($info->attrName) ) { 
			echo $info->attrName . " "; }
		
		if ( !empty($info->attrValue) ) {
			echo $info->attrValue . " "; }
			
		if ( !empty($info->attrUnit) ) { 
			echo $info->attrUnit . " "; }
			
		echo "<br>";
	}
}

function tarkista_pystyyko_tilaamaan_ja_tulosta_tilaa_nappi($products) {
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

    if ( $enough_in_stock && $enough_ordered ) {
    	?><p><a class="nappi" href="tilaus.php">Tilaa tuotteet</a></p><?php 
    } else {
        ?><p><a class="nappi disabled">Tilaa tuotteet</a> Tuotteita ei voi tilata, koska niitä ei ole tarpeeksi varastossa tai minimimyyntierää ei ole ylitetty.</p><?php 
    }
}

function tulosta_rahtimaksu_tuotelistaan() {
	global $rahtimaksu;
	$rahtimaksu = laske_rahtimaksu();
	if ( $rahtimaksu === 0 ) { $alennus = "Ilmainen toimitus"; } else { $alennus = "---"; }
	
	?><!-- HTML -->
	<tr style="background-color:#cecece; height: 1em;">
		<td>---</td>
		<td>RAHTIMAKSU</td>
		<td>Rahtimaksu</td>
		<td>Posti / Itella</td>
		<td>---</td>
		<td>---</td>
		<td style="text-align: right;"><?= format_euros($rahtimaksu)?></td>
		<td style="text-align: right;">---</td>
		<td style="text-align: right;">---</td>
		<td style="text-align: right;">1</td>
		<td><?= $alennus?></td>
	</tr>
	<?php
}
handle_shopping_cart_action();

$products = get_products_in_shopping_cart();

if ( $products ) {
	$sum = 0.0;
	$rahtimaksu = 15;?>
	
	<!-- HTML -->
    <div class="tulokset">
	    <table>
		    <tr><th>Kuva</th><th>Tuotenumero</th><th>Tuote</th><th>Info</th><th>EAN</th><th>OE</th><th style="text-align: right;">Hinta</th><th style="text-align: right;">Varastosaldo</th><th style="text-align: right;">Minimimyyntierä</th><th>Kpl</th><th>Muuta</th></tr>
		    <?php
		    foreach ($products as $product) {
		        $article = $product->directArticle;
				$sum += $product->hinta * $product->cartCount;?>
				<!-- HTML -->
		       	<tr>
			        <td class="thumb"><img src="<?= $product->thumburl?>" alt="<?= $article->articleName?>"></td>
			        <td><?= $article->articleNo?></td>
			        <td><?= $article->brandName?> <br> <?= $article->articleName?></td>
			        <td><?= tulosta_product_infos_part($product->infos)?></td>
			        <td><?= $product->ean ?></td>
			        <td><?= tulosta_taulukko($product->oe)?></td>
			        <td style="text-align: right;"><?= format_euros(tarkista_hinta_era_alennus( $product )) ?></td>
			        <td style="text-align: right;"><?= format_integer($product->varastosaldo) ?></td>
			        <td style="text-align: right;"><?= format_integer($product->minimimyyntiera) ?></td>
			        <td style="padding-top: 0; padding-bottom: 0;"><input id="maara_<?= $article->articleId ?>" name="maara_<?= $article->articleId ?>" class="maara" type="number"value="<?= $product->cartCount ?>" min="0"></td>
			        <td style="padding-top: 0; padding-bottom: 0;"><?= laske_era_alennus_tulosta_huomautus( $product )?></td>
			        <td class="toiminnot"><a class="nappi" href="javascript:void(0)" onclick="modifyShoppingCart(<?= $article->articleId?>)">Päivitä</a></td>
		        </tr>
		        <!-- HTML END --><?php 
		    }
			tulosta_rahtimaksu_tuotelistaan()?>
		<!-- HTML -->
	    </table>
	    <div id=tilausvahvistus_maksutiedot style="width:20em;">
			<p>Tuotteiden kokonaissumma: <b><?= format_euros($sum)?></b></p>
			<p>Summa yhteensä: <b><?= format_euros($sum+$rahtimaksu)?></b> ( ml. toimitus )</p>
	    </div>
	    <?= tarkista_pystyyko_tilaamaan_ja_tulosta_tilaa_nappi($products)?>
	    <p><a class="nappi" href="tuotehaku.php" style="background:rgb(200, 70, 70);border-color: #b70004;">Palaa takaisin</a></p>
    </div><?php 
} else { //Products-array is empty?>
    <div class="tulokset">
    	<p>Ostoskorissa ei ole tuotteita.</p>
    </div>
<?php } ?>

</body>
</body>
</html>