<?php
require '_start.php'; global $db, $user, $cart;
require 'tecdoc.php';
require 'apufunktiot.php';
require 'email.php';
require 'ostoskori_tilaus_funktiot.php'; //Sisältää kaikki ennen tässä tiedostossa olleet PHP-funktiot

$yritys = new Yritys( $db, $user->yritys_id );
$feedback = '';
$products = get_products_in_shopping_cart( $db, $cart );
$user->haeToimitusosoitteet($db, -1); // Toimitusosoitteen valinta tilausta varten.
if ( empty($products) ) { header("location:ostoskori.php"); exit; }

if ( !empty($_POST['vahvista_tilaus']) ) {
	$result = $db->query( //Tilauksen ID:n ja alkutietojen lisäys
		'INSERT INTO tilaus (kayttaja_id, pysyva_rahtimaksu) VALUES (?, ?)',
		[ $user->id, $_POST['rahtimaksu'] ] );

	if ( $result ) { //Jos ID:n lisääminen onnistui, jatketaan.
		$tilaus_id = $db->getConnection()->lastInsertId(); //Haetaan lisätyn tilauksen ID, sitä tarvitaan vielä.

		//Tuotteiden pysyvä tallennus tietokantaan
		$db->prepare_stmt( '
			INSERT INTO tilaus_tuote (tilaus_id, tuote_id, pysyva_hinta, pysyva_alv, pysyva_alennus, kpl)
			VALUES (?, ?, ?, ?, ?, ?)' );
		foreach ( $products as $product ) {
			$result = $db->run_prepared_stmt( [
				$tilaus_id, $product->id, $product->hinta_ilman_alv, $product->alv_prosentti,
				$product->alennusera_prosentti, $product->cartCount
			] );

			$db->query( //Päivitetään tilattujen tuotteiden varastosaldo
				"UPDATE tuote SET varastosaldo = ? WHERE id = ?",
				[ ($product->varastosaldo - $product->cartCount), $product->id ] );
		}

		$db->query( // Tallennetaan pysyvät osoitetiedot tietokantaan.
			"INSERT INTO tilaus_toimitusosoite
				(tilaus_id, pysyva_etunimi, pysyva_sukunimi, pysyva_sahkoposti, pysyva_puhelin, 
				pysyva_yritys, pysyva_katuosoite, pysyva_postinumero, pysyva_postitoimipaikka)
			SELECT ?, etunimi, sukunimi, sahkoposti, puhelin, yritys, katuosoite, postinumero, postitoimipaikka
			FROM toimitusosoite 
			WHERE kayttaja_id = ? AND osoite_id = ?",
			[$tilaus_id, $user->id, $_POST['toimitusosoite_id']] );

		//lähetetään tilausvahvistus asiakkaalle
		//TODO: Luo lasku. Tulossa... joskus. When it's done.
		laheta_tilausvahvistus( $user->sahkoposti, $products, $tilaus_id );
		//lähetetään tilaus ylläpidolle
		//laheta_tilaus_yllapitajalle($_SESSION["email"], $products, $tilaus_id);

		$cart->tyhjenna_kori( $db );
		header( "location:tilaushistoria.php?id=$user->id" );
		exit;
	}
	else {
		$feedback = '<p class="error">Tilauksen lähetys ei onnistunut!</p>';
	}
}
?>
<!DOCTYPE html>
<html lang="fi">
<head>
	<meta charset="UTF-8">
	<title>Vahvista tilaus</title>
	<link rel="stylesheet" href="css/styles.css">
	<link rel="stylesheet" href="css/jsmodal-light.css">
	<link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
	<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.0/jquery.min.js"></script>
	<script src="js/jsmodal-1.0d.min.js"></script>
	<style type="text/css">
		#rahtimaksu_listaus { background-color:#cecece; height: 1em; }
		.peruuta {
			background:rgb(200, 70, 70);
			border-color: #b70004;
		}
	</style>
</head>
<body>
<?php require 'header.php'; ?>
<main class="main_body_container">
	<h1 class="otsikko">Vahvista tilaus</h1>
	<?= $feedback ?>
	<table>
		<tr><th>Tuotenumero</th><th>Tuote</th><th>Valmistaja</th><th class="number">Hinta</th><th class="number">Kpl-hinta</th><th>Kpl</th><th>Info</th></tr>
		<?php
		$sum = 0;
		foreach ( $products as $product ) {
			$product->hinta = tarkista_hinta_era_alennus( $product );
			$sum += $product->hinta * $product->cartCount; ?>
			<tr>
				<td><?= $product->tuotekoodi?></td><!-- Tuotenumero -->
				<td><?= $product->articleName?></td><!-- Tuotteen nimi -->
				<td><?= $product->brandName?></td><!-- Tuotteen valmistaja -->
				<td class="number"><?= format_euros( $product->hinta * $product->cartCount ) ?></td><!-- Hinta yhteensä -->
				<td class="number"><?= format_euros( $product->hinta ) ?></td><!-- Kpl-hinta (sis. ALV) -->
				<td class="number"><?= $product->cartCount?></td><!-- Kpl-määrä -->
				<td style="padding-top: 0; padding-bottom: 0;"><?= laske_era_alennus_palauta_huomautus( $product, FALSE )?></td>
			</tr><?php
		}
		$rahtimaksu = tarkista_rahtimaksu( $yritys, $sum ); ?>
		<tr id="rahtimaksu_listaus">
			<td>---</td>
			<td>Rahtimaksu</td>
			<td>---</td>
			<td class="number"><?= format_euros( $rahtimaksu[0] )?></td>
			<td class="number">---</td>
			<td class="number">1</td>
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
			<?= tarkista_osoitekirja_ja_tulosta_tmo_valinta_nappi_tai_disabled(
				count($user->toimitusosoitteet) ) ?>
		</div>
		<div id=tilausvahvistus_toimitusosoite_tulostus style="flex-grow:1; margin:auto;">
			<!-- Osoitteen tulostus -->
		</div>
	</div>

	<?= tarkista_pystyyko_tilaamaan_ja_tulosta_tilaa_nappi_tai_disabled(
		$products, count($user->toimitusosoitteet), FALSE )?>
	<p><a class="nappi peruuta" href="ostoskori.php">Palaa takaisin</a></p>
</main>
<form class="hidden" id="laheta_tilaus_form" action="#" method=post>
	<input type=hidden id="toimitusosoite_form_input" name="toimitusosoite_id" value="">
	<input type=hidden id="rahtimaksu_form_input" name="rahtimaksu" value="<?=$rahtimaksu[0]?>">
	<input type=hidden name="vahvista_tilaus" value="true">
</form>

<script>
	var osoitekirja = <?= json_encode( $user->toimitusosoitteet )?>;
	console.log( osoitekirja );

	function avaa_Modal_valitse_toimitusosoite() {
		Modal.open({
			content:  ' \
//				<?//= toimitusosoitteiden_Modal_tulostus( $user->toimitusosoitteet )?>// \
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

		document.getElementById('toimitusosoite_form_input').value = osoite_id+1; //Tallenetaan toimitusosoite talteen piilotettuun formiin
	}

	function laheta_Tilaus () {
		var form_ID = "laheta_tilaus_form";
		var vahvistus = confirm( "Haluatko vahvistaa tilauksen?");
		if ( vahvistus ) {
			document.getElementById(form_ID).submit();
		} else {
			return false;
		}
	}
	valitse_toimitusosoite(0);
</script>

</body>
</html>
