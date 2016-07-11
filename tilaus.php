<!DOCTYPE html>
<html lang="fi">
<head>
	<link rel="stylesheet" href="css/styles.css">
	<link rel="stylesheet" href="css/jsmodal-light.css">
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
require 'ostoskori_tilaus_funktiot.php'; //Sisältää kaikki ennen tässä tiedostossa olleet PHP-funktiot

$products = get_products_in_shopping_cart();

if (isset($_GET['vahvista'])) {
	if (order_products($products)) {
		echo '<p class="success">Tilaus lähetetty!</p>';
		empty_shopping_cart();
	} else {
		echo '<p class="error">Tilauksen lähetys ei onnistunut!</p>';
	}
} elseif ( $products ) {
	$sum = 0.0;	
	$rahtimaksu = [15, 200];  //sisältää sekä rahtimaksun, että ilmaisen toimituksen rajan. Varsinaiset arvot lasketaan myöhemmin.?>
	
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
			        <td><?= $product->ean?></td>
			        <td><?= tulosta_taulukko($product->oe)?></td>
			        <td style="text-align: right; white-space: nowrap;"><?= format_euros( $product->hinta ) ?></td>
			        <td style="text-align: right;"><?= format_integer($product->varastosaldo) ?></td>
			        <td style="text-align: right;"><?= format_integer($product->minimimyyntiera) ?></td>
					<td style="text-align: right;"><?= $product->cartCount?></td>
					<td style="padding-top: 0; padding-bottom: 0;"><?= laske_era_alennus_tulosta_huomautus( $product, FALSE )?></td>
				</tr><?php
			}
			tulosta_rahtimaksu_tuotelistaan( FALSE );
			?>
    	
		</table>
    	<div id=tilausvahvistus_tilaustiedot_container style="display:flex; height:7em;">
	    	<div id=tilausvahvistus_maksutiedot style="width:20em;">
		    	<p>Tuotteiden kokonaissumma: <b><?= format_euros($sum)?></b></p>
		    	<br>
		    	<p>Summa yhteensä: <b><?= format_euros($sum+$rahtimaksu[0])?></b> ( ml. toimitus )</p>
	    	</div>
	    	<div id=tilausvahvistus_toimitusosoite_nappi style="width:12em; padding-bottom:1em; padding-top:1em;">
	    		<?= tarkista_osoitekirja_ja_tulosta_tmo_valinta_nappi_tai_disabled() ?>
	    	</div>
	    	<div id=tilausvahvistus_toimitusosoite_tulostus style="flex-grow:1;">
		    	<!-- Osoitteen tulostus -->
	    	</div>
    	</div>
    	 
	    <?= tarkista_pystyyko_tilaamaan_ja_tulosta_tilaa_nappi_tai_disabled( $products, FALSE )// Varmistetaan, että tuotteita on varastossa ja ainakin minimimyyntierän verran?>
    	<p><a class="nappi" href="ostoskori.php" style="background:rgb(200, 70, 70);border-color: #b70004;">Palaa takaisin</a></p>
    </div>
<?php 
} else { //Products-array (tai ostoskori) is empty?>
    <div class="tulokset">
    	<p>Ostoskorissa ei ole tuotteita.</p>
    </div>
<?php } ?>

<script src="js/jsmodal-1.0d.min.js"></script>
<script>
var osoitekirja = <?= json_encode($osoitekirja_array)?>;

function avaa_Modal_valitse_toimitusosoite() {
	Modal.open({
		content:  ' \
			<?= hae_kaikki_toimitusosoitteet_ja_tulosta_Modal()?> \
			',
		draggable: true
	});
}
function valitse_toimitusosoite(osoite_id) {
	var osoite_array = osoitekirja[osoite_id];
	//Muuta tempate literal muotoon heti kuun saan päivitettyä tämän EMACS2015
	var html_osoite = document.getElementById('tilausvahvistus_toimitusosoite_tulostus');
	html_osoite.innerHTML = "Toimitusosoite " + osoite_id + "<br>"
		+ "Sähköposti: " + osoite_array['sahkoposti'] + "<br>"
		+ "Katuosoite: " + osoite_array['katuosoite'] + "<br>"
		+ "Postinumero ja -toimipaikka: " + osoite_array['postinumero'] + " " + osoite_array['postitoimipaikka'] + "<br>"
		+ "Puhelinnumero: " + osoite_array['puhelin'];
}
</script>

</body>
</body>
</html>
