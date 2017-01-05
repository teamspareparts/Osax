<?php
require '_start.php'; global $db, $user, $cart;
require 'tecdoc.php';
require 'apufunktiot.php';
require 'email.php';
require 'ostoskori_tilaus_funktiot.php';

$user->haeToimitusosoitteet($db, -1); // Toimitusosoitteen valinta tilausta varten.
$cart->hae_ostoskorin_sisalto( $db, TRUE, TRUE );
if ( $cart->montako_tuotetta == 0 ) {
	header("location:ostoskori.php"); exit;
}
check_products_in_shopping_cart( $cart, $user);

if ( !empty($_POST['vahvista_tilaus']) ) {

	$conn = $db->getConnection();
	$conn->beginTransaction();
    $toimitusosoite_id = $_POST['toimitusosoite_id'];

	try {

		$stmt = $conn->prepare( 'INSERT INTO tilaus (kayttaja_id, pysyva_rahtimaksu) VALUES (?, ?)' );
		$stmt->execute( [$user->id, $_POST['rahtimaksu']] );

		$tilaus_id = $conn->lastInsertId();

		// Prep.stmt. tuotteiden lisäys tietokantaan. Ostoskorista yksikerrallaan.
		// Päivita varastosaldo jokaisen tuotteen kohdalla.
		$stmt = $conn->prepare( '
			INSERT INTO tilaus_tuote
				(tilaus_id, tuote_id, tuotteen_nimi, valmistaja, pysyva_hinta, pysyva_alv, pysyva_alennus, kpl)
			VALUES (?, ?, ?, ?, ?, ?, ?, ?)' );
		foreach ( $cart->tuotteet as $tuote ) {
			$stmt->execute( [ $tilaus_id, $tuote->id, $tuote->nimi, $tuote->valmistaja, $tuote->a_hinta,
				$tuote->alv_prosentti, $tuote->alennus_prosentti, $tuote->kpl_maara
			] );

			$stmt2 = $conn->prepare( "UPDATE tuote SET varastosaldo = ? WHERE id = ?" );
			$stmt2->execute( [($tuote->varastosaldo - $tuote->kpl_maara), $tuote->id] );
		}

		$stmt = $conn->prepare(
			'INSERT INTO tilaus_toimitusosoite
				(tilaus_id, pysyva_etunimi, pysyva_sukunimi, pysyva_sahkoposti, pysyva_puhelin, 
				pysyva_yritys, pysyva_katuosoite, pysyva_postinumero, pysyva_postitoimipaikka)
			SELECT ?, etunimi, sukunimi, sahkoposti, puhelin, yritys, katuosoite, postinumero, postitoimipaikka
			FROM toimitusosoite 
			WHERE kayttaja_id = ? AND osoite_id = ?' );
		$stmt->execute( [$tilaus_id, $user->id, $_POST['toimitusosoite_id']] );

		$conn->commit();

		require 'lasku_pdf_luonti.php';
		//lähetetään tilausvahvistus asiakkaalle
		laheta_tilausvahvistus( $user->sahkoposti, $cart->tuotteet, $tilaus_id, $tiedoston_nimi );
		//lähetetään tilaus ylläpidolle
		require 'noutolista_pdf_luonti.php';
		laheta_noutolista($tilaus_id, $tiedoston_nimi);

		$cart->tyhjenna_kori( $db );
		header( "location:tilaushistoria.php?id=$user->id" );
		exit;

	} catch ( PDOException $ex ) {
		// Rollback any changes, and print error message to user.
		$conn->rollback();
		$_SESSION["feedback"] = "<p class='error'>Tilauksen lähetys ei onnistunut!<br>Virhe: {$ex->errorInfo}</p>";
		// TODO: Do not print error message to user in full (only generic)!
	}
}

/** Tarkistetaan feedback, ja estetään formin uudelleenlähetys */
if ( !empty($_POST) ) { //Estetään formin uudelleenlähetyksen
	header("Location: " . $_SERVER['REQUEST_URI']); exit();
} else {
	$feedback = isset($_SESSION['feedback']) ? $_SESSION['feedback'] : "";
	unset($_SESSION["feedback"]);
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
	<script src="https://code.jquery.com/jquery-3.1.1.min.js"></script>
	<script src="js/jsmodal-1.0d.min.js"></script>
	<style type="text/css">
		#rahtimaksu_listaus { background-color:#cecece; height: 1em; }
	</style>
</head>
<body>
<?php require 'header.php'; ?>
<main class="main_body_container">
	<?= $feedback ?>
	<table style="width:90%;">
		<thead>
		<tr> <th colspan="8" class="center" style="background-color:#1d7ae2;">Tilauksen vahvistus</th> </tr>
		<tr> <th>Tuotenumero</th> <th>Tuote</th> <th>Valmistaja</th> <th class="number">Hinta</th>
			 <th class="number">Kpl-hinta</th> <th>Kpl</th> <th>Info</th> </tr>
		</thead>
		<tbody>
		<?php foreach ( $cart->tuotteet as $tuote ) { ?>
			<tr>
				<td><?= $tuote->tuotekoodi ?></td><!-- Tuotenumero -->
				<td><?= $tuote->nimi ?></td><!-- Tuotteen nimi -->
				<td><?= $tuote->valmistaja ?></td><!-- Tuotteen valmistaja -->
				<td class="number"><?= $tuote->summa_toString() ?></td><!-- Hinta yhteensä -->
				<td class="number"><?= $tuote->a_hinta_toString() ?></td><!-- Kpl-hinta (sis. ALV) -->
				<td class="number"><?= $tuote->kpl_maara ?></td><!-- Kpl-määrä -->
				<td style="padding-top: 0; padding-bottom: 0;"><?= $tuote->alennus_huomautus ?></td><!-- Info -->
			</tr><?php
		} ?>
		<tr id="rahtimaksu_listaus">
			<td>---</td><!-- Tuotenumero -->
			<td>Rahtimaksu</td><!-- Tuotteen nimi -->
			<td>---</td><!-- Tuotteen valmistaja -->
			<td class="number"><?= $user->rahtimaksu_toString() ?></td><!-- Hinta yhteensä -->
			<td class="number">---</td><!-- Kpl-hinta (sis. ALV) -->
			<td class="number">1</td><!-- Kpl-määrä -->
			<td><?= ($user->rahtimaksu == 0) ? 'Ilmainen toimitus' : "---" ?></td><!-- Info -->
		</tr>
		</tbody>
	</table>
	<div id=tilausvahvistus_tilaustiedot_container style="display:flex; height:7em;">
		<div id=tilausvahvistus_maksutiedot style="width:20em; margin:auto;">
			<p>Tuotteiden kokonaissumma: <b><?= $cart->summa_toString() ?></b></p>
			<p>Summa yhteensä: <b><?=format_number(($cart->summa_yhteensa+$user->rahtimaksu))?></b> ( ml. toimitus )</p>
			<span class="small_note">Kaikki hinnat sis. ALV</span>
		</div>
		<div id=tilausvahvistus_toimitusosoite_nappi style="width:12em; margin: auto;">
			<?= tarkista_osoitekirja_ja_tulosta_tmo_valinta_nappi_tai_disabled(
				count($user->toimitusosoitteet) ) ?>
		</div>
		<div id=tilausvahvistus_toimitusosoite_tulostus style="flex-grow:1; margin:auto;">
			<!-- Osoitteen tulostus tulee tähän -->
		</div>
	</div>

	<?= tarkista_pystyyko_tilaamaan_ja_tulosta_tilaa_nappi_tai_disabled( $cart, $user, FALSE ) ?>
	<p><a class="nappi red" href="ostoskori.php">Palaa takaisin</a></p>
</main>
<form class="hidden" id="laheta_tilaus_form" action="#" method=post>
	<input type=hidden id="toimitusosoite_form_input" name="toimitusosoite_id" value="">
	<input type=hidden id="rahtimaksu_form_input" name="rahtimaksu" value="<?=$rahtimaksu[0]?>">
	<input type=hidden name="vahvista_tilaus" value="true">
</form>

<script>
	let osoitekirja = <?= json_encode( $user->toimitusosoitteet )?>;

	/**
	 * Avaa Modalin toimitusosoitteen valintaa varten
	 */
	function avaa_Modal_valitse_toimitusosoite() {
		Modal.open({
			content:  '\
				<?= toimitusosoitteiden_Modal_tulostus( $user->toimitusosoitteet )?> \
				',
			draggable: true
		});
	}

	/**
	 * Valitse toimitusosoite. Tulostaa, ja asettaa osoitteen ID:n piilotettuun formiin.
	 * @param osoite_id
	 */
	function valitse_toimitusosoite( osoite_id ) {
		let html_osoite = document.getElementById('tilausvahvistus_toimitusosoite_tulostus');
		let osoite_array = osoitekirja[osoite_id];
		html_osoite.innerHTML = ""
			+ "<h4 style='margin-bottom:0;'>Toimitusosoite " + (osoite_id+1) + "</h4>"
			+ "Sähköposti: " + osoite_array['sahkoposti'] + "<br>"
			+ "Katuosoite: " + osoite_array['katuosoite'] + "<br>"
			+ "Postinumero ja -toimipaikka: " + osoite_array['postinumero'] + " " + osoite_array['postitoimipaikka'] + "<br>"
			+ "Puhelinnumero: " + osoite_array['puhelin'];

		document.getElementById('toimitusosoite_form_input').value = osoite_id+1; //Tallenetaan toimitusosoite talteen piilotettuun formiin
	}

	function laheta_Tilaus () {
		let form_ID = "laheta_tilaus_form";
		let vahvistus = confirm( "Haluatko vahvistaa tilauksen?");
		if ( vahvistus ) {
			document.getElementById(form_ID).submit();
		} else {
			return false;
		}
	}

	$(document).ready(function() {
		valitse_toimitusosoite(0);
	});//doc.ready
</script>

</body>
</html>
