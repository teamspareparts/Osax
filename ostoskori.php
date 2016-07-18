<!DOCTYPE html>
<html lang="fi">
<head>
	<link rel="stylesheet" href="css/styles.css">
	<meta charset="UTF-8">
	<style type="text/css">
			.class #id tag {}
			
			.number { text-align:right;	white-space: nowrap; }
			#rahtimaksu_listaus { background-color:#cecece; height: 1em; }
	</style>
	<title>Ostoskori</title>
</head>
<body>
<?php include('header.php');
include('ostoskori_lomake.php');
require 'tecdoc.php';
require 'tietokanta.php';
require 'apufunktiot.php';
require 'ostoskori_tilaus_funktiot.php';

handle_shopping_cart_action();

$products = get_products_in_shopping_cart( $connection );
$kayttaja_id = (int)$_SESSION['id'];

$sum = 0.0;
$rahtimaksu = hae_rahtimaksu( $connection, $kayttaja_id, $sum ); //array(rahtimaksu, ilm.toim.raja); indeksit 0 ja 1
?>
<!-- HTML -->
<h1 class="otsikko">Ostoskori</h1>
<div class="tulokset">
	<table>
		<tr><th>Tuotenumero</th><th>Tuote</th><th>Valmistaja</th><th class="number">Hinta</th><th class="number">Kpl-hinta (sis. ALV)</th><th>Kpl</th><th>Info</th></tr>
		<?php foreach ($products as $product) {
			$article = $product->directArticle;
			$product->hinta = tarkista_hinta_era_alennus( $product );
			$sum += $product->hinta * $product->cartCount; ?>
			<tr>
				<td><?= $article->articleNo?></td><!-- Tuotenumero -->
				<td><?= $article->articleName?></td><!-- Tuotteen nimi -->
				<td><?= $article->brandName?></td><!-- Tuotteen valmistaja -->
				<td class="number"><?= format_euros( $sum ) ?></td><!-- Hinta yhteensä -->
				<td class="number"><?= format_euros( $product->hinta ) ?></td><!-- Kpl-hinta (sis. ALV) -->
				<td style="padding-top: 0; padding-bottom: 0;">
					<input id="maara_<?= $article->articleId ?>" name="maara_<?= $article->articleId ?>" class="maara number" type="number" value="<?= $product->cartCount ?>" min="0">
				</td>
				<td><?= laske_era_alennus_tulosta_huomautus( $product, TRUE )?></td>
				<td class="toiminnot"><a class="nappi" href="javascript:void(0)" onclick="modifyShoppingCart(<?= $article->articleId?>)">Päivitä</a></td>
			</tr>
		<?php } ?>
		
		<tr id="rahtimaksu_listaus">
			<td>---</td>
			<td>Rahtimaksu</td>
			<td>---</td>
			<td class="number"><?= format_euros( $rahtimaksu[0] )?></td>
			<td class="number">---</td>
			<td class="number">1</td>
			<td><?= tulosta_rahtimaksu_alennus_huomautus( $rahtimaksu[0], TRUE )?></td>
		</tr>
	</table>
	<div id=tilausvahvistus_maksutiedot style="width:20em;">
		<p>Tuotteiden kokonaissumma: <b><?= format_euros($sum)?></b></p>
		<p>Summa yhteensä: <b><?= format_euros($sum+$rahtimaksu[0])?></b> ( ml. toimitus )</p>
	</div>
	<?= tarkista_pystyyko_tilaamaan_ja_tulosta_tilaa_nappi_tai_disabled( $products, TRUE )// Varmistetaan, että tuotteita on varastossa ja ainakin minimimyyntierän verran?>
	<p><a class="nappi" href="tuotehaku.php" style="background:rgb(200, 70, 70);border-color: #b70004;">Palaa takaisin</a></p>
</div>

</body>
</body>
</html>