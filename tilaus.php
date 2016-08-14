<!DOCTYPE html>
<html lang="fi">
<head>
	<link rel="stylesheet" href="css/styles.css">
	<link rel="stylesheet" href="css/jsmodal-light.css">
	<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.0/jquery.min.js"></script>
	<style type="text/css">
			.class #id tag {}
			#rahtimaksu_listaus { background-color:#cecece; height: 1em; }
			.peruuta {
				background:rgb(200, 70, 70);
				border-color: #b70004;
			}
	</style>
	<meta charset="UTF-8">
	<title>Vahvista tilaus</title>
</head>
<body>
<?php include('header.php');
include('ostoskori_lomake.php');
require 'tecdoc.php';
require 'tietokanta.php';
require 'apufunktiot.php';
require 'email.php';
require 'ostoskori_tilaus_funktiot.php'; //Sisältää kaikki ennen tässä tiedostossa olleet PHP-funktiot

$products = get_products_in_shopping_cart( $connection );
$kayttaja_id = (int)$_SESSION['id'];
$osoitekirja_array = hae_kaikki_toimitusosoitteet_ja_luo_JSON_array( $connection, $kayttaja_id );
$sum = 0.0;

if ( !empty($_POST['vahvista_tilaus']) ) {
	$rahtimaksu = (float)$_POST['rahtimaksu'];
	$toimitusosoite_id = (int)$_POST['toimitusosoite_id'];
	if ( order_products($products, $connection, $kayttaja_id, $rahtimaksu, $toimitusosoite_id) ) {
		empty_shopping_cart();
		header("location:tilaushistoria.php"); exit;
	} else {
		echo '<p class="error">Tilauksen lähetys ei onnistunut!</p>';
	}
} elseif ( empty($products) ) {
	header("location:ostoskori.php"); exit;
}
?>

<!-- HTML -->
<h1 class="otsikko">Vahvista tilaus</h1>
<div class="tulokset">
	<table>
		<tr><th>Tuotenumero</th><th>Tuote</th><th>Valmistaja</th><th class="number">Hinta</th><th class="number">Kpl-hinta</th><th>Kpl</th><th>Info</th></tr>
		<?php
		foreach ( $products as $product ) {
			$product->hinta = tarkista_hinta_era_alennus( $product );
			$sum += $product->hinta * $product->cartCount;?>
			<!-- HTML -->
			<tr>
				<td><?= $product->articleNo?></td><!-- Tuotenumero -->
				<td><?= $product->articleName?></td><!-- Tuotteen nimi -->
				<td><?= $product->brandName?></td><!-- Tuotteen valmistaja -->
				<td class="number"><?= format_euros( $product->hinta * $product->cartCount ) ?></td><!-- Hinta yhteensä -->
				<td class="number"><?= format_euros( $product->hinta ) ?></td><!-- Kpl-hinta (sis. ALV) -->
				<td class="number"><?= $product->cartCount?></td><!-- Kpl-määrä -->
				<td style="padding-top: 0; padding-bottom: 0;"><?= laske_era_alennus_palauta_huomautus( $product, FALSE )?></td>
			</tr><?php
		}
		$rahtimaksu = hae_rahtimaksu( $connection, $kayttaja_id, $sum ); ?>
		<tr id="rahtimaksu_listaus">
			<td>---</td>
			<td>Rahtimaksu</td><!-- Tuotteen nimi -->
			<td>---</td>
			<td class="number"><?= format_euros( $rahtimaksu[0] )?></td><!-- Hinta yhteensä -->
			<td class="number">---</td>
			<td class="number">1</td><!-- Kpl-määrä -->
			<td><?= tulosta_rahtimaksu_alennus_huomautus( $rahtimaksu, FALSE )?></td>
		</tr>
	</table>
	<div id=tilausvahvistus_tilaustiedot_container style="display:flex; height:7em;">
		<div id=tilausvahvistus_maksutiedot style="width:20em; margin:auto;">
			<p>Tuotteiden kokonaissumma: <b><?= format_euros( $sum )?></b></p>
			<p>Summa yhteensä: <b><?= format_euros( $sum + $rahtimaksu[0] )?></b> ( ml. toimitus )</p>
			<span class="small_note">Kaikki hinnat sis. ALV</span>
		</div>
		<div id=tilausvahvistus_toimitusosoite_nappi style="width:12em; margin: auto;">
			<?= tarkista_osoitekirja_ja_tulosta_tmo_valinta_nappi_tai_disabled( count($osoitekirja_array) ) ?>
		</div>
		<div id=tilausvahvistus_toimitusosoite_tulostus style="flex-grow:1; margin:auto;">
			<!-- Osoitteen tulostus -->
		</div>
	</div>

	<?= tarkista_pystyyko_tilaamaan_ja_tulosta_tilaa_nappi_tai_disabled( $products, FALSE )// Varmistetaan, että tuotteita on varastossa ja ainakin minimimyyntierän verran?>
	<p><a class="nappi peruuta" href="ostoskori.php" style="background:rgb(200, 70, 70);border-color: #b70004;">Palaa takaisin</a></p>
</div>

<!-- Hidden form -->
<form style="display:none;" id="laheta_tilaus_form" action="#" method=post>
	<input type=hidden id="toimitusosoite_form_input" name="toimitusosoite_id" value="">
	<input type=hidden id="rahtimaksu_form_input" name="rahtimaksu" value="">
	<input type=hidden name="vahvista_tilaus" value="true">
</form>

<script src="js/jsmodal-1.0d.min.js"></script>
<script>
var osoitekirja = <?= json_encode( $osoitekirja_array, TRUE )?>;

function avaa_Modal_valitse_toimitusosoite() {
	Modal.open({
		content:  ' \
			<?= hae_kaikki_toimitusosoitteet_ja_tulosta_Modal( $osoitekirja_array )?> \
			',
		draggable: true
	});
}

function valitse_toimitusosoite( osoite_id ) {
	var html_osoite = document.getElementById('tilausvahvistus_toimitusosoite_tulostus');
	var osoite_array = osoitekirja[osoite_id];
	html_osoite.innerHTML = ""
		+ "<h4 style='margin-bottom:0;'>Toimitusosoite " + osoite_id + "</h4>"
		+ "Sähköposti: " + osoite_array['sahkoposti'] + "<br>"
		+ "Katuosoite: " + osoite_array['katuosoite'] + "<br>"
		+ "Postinumero ja -toimipaikka: " + osoite_array['postinumero'] + " " + osoite_array['postitoimipaikka'] + "<br>"
		+ "Puhelinnumero: " + osoite_array['puhelin'];

	document.getElementById('toimitusosoite_form_input').value = osoite_id; //Tallenetaan toimitusosoite talteen piilotettuun formiin
}

function laheta_Tilaus () {
	var form_ID = "laheta_tilaus_form";
	var vahvistus = confirm( "Oletko varma, että haluat lähettää tilauksen?\n"
			+ "Tämä on viimeinen vaihe. Toimintoa ei voi perua jälkeenpäin.\n"
			+ "Jos painat OK, tilauksen lähetyksen jälkeen Sinut uudelleenohjataan tilaus-info sivulle.");
	if ( vahvistus ) {
		document.getElementById('rahtimaksu_form_input').value = <?= $rahtimaksu[0] ?>; //Tallenetaan rahtimaksu talteen piilotettuun formiin
		document.getElementById(form_ID).submit();
	} else {
		return false;
	}
}

valitse_toimitusosoite(1);
</script>

</body>
</html>
