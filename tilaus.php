<?php
require '_start.php'; global $db, $user, $cart;
require 'ostoskori_tilaus_funktiot.php';
require 'luokat/email.class.php';

ignore_user_abort( true ); //Tilaus tehdään aina loppuun saakka riippumatta käyttäjästä
set_time_limit( 100 );

$user->haeToimitusosoitteet( $db, -1 ); // Toimitusosoitteen valintaa varten haetaan kaikki toimitusosoitteet.

$cart->hae_ostoskorin_sisalto( $db, true, true );
if ( $cart->montako_tuotetta == 0 ) {
	header( "location:ostoskori.php" );
	exit;
}
check_products_in_shopping_cart( $cart, $user );

/*
 * Varsinaisen tilauksen teko käyttääjn vahvistuksen jälkeen.
 * Tässä vaiheessa merkitään tilaus maksamattomaksi. Maksu tapahtuu seuraavalla sivulla.
 */
if ( !empty( $_POST[ 'vahvista_tilaus' ] ) ) {

	/*
	 * Varmistuksena käytetään transactionia, jotta kaikki tietokanta-muutokset varmasti toimivat.
	 * Transactionia varten tarvitaan oma tietokantayhteys, koska sitä ei ole toteutettu luokassa ollenkaan.
	 */
	$conn = $db->getConnection();
	$conn->beginTransaction();
	$toimitusosoite_id = $_POST[ 'toimitusosoite_id' ];

	try {
		// Tallennetaan tilauksen tiedot tietokantaan
		$stmt = $conn->prepare( 'INSERT INTO tilaus (kayttaja_id, pysyva_rahtimaksu) VALUES (?, ?)' );
		$stmt->execute( [ $user->id, $_POST[ 'rahtimaksu' ] ] );

		$tilaus_id = $conn->lastInsertId(); // Haetaan tilaus-ID, sitä tarvitaan vielä.

		/*
		 * Prep.stmt. tuotteiden lisäys tietokantaan. Lisätään kaikki tuotteet kerralla.
		 * Kustomi haun rakennusta tehokkuuden vuoksi. Yksitellen tehtynä hyvin hidasta.
		 */
		$questionmarks = implode( ',', array_fill( 0, count( $cart->tuotteet ), '(?,?,?,?,?,?,?,?)' ) );
		$values = [];
		$stmt = $conn->prepare( "
			INSERT INTO tilaus_tuote
				(tilaus_id, tuote_id, tuotteen_nimi, valmistaja, pysyva_hinta, pysyva_alv, pysyva_alennus, kpl)
			VALUES {$questionmarks}" );

		// Päivitetään tuotteiden varastosaldot
		$questionmarks2 = implode( ',', array_fill( 0, count( $cart->tuotteet ), '(?,?)' ) );
		$values_varastosaldot = [];

		$sql = "INSERT INTO tuote (id, varastosaldo) VALUES {$questionmarks2}
		        ON DUPLICATE KEY 
		        UPDATE varastosaldo = VALUES(varastosaldo), paivitettava = 1";
		$stmt_varastosaldot = $conn->prepare( $sql);

		foreach ( $cart->tuotteet as $tuote ) {
			array_push( $values, $tilaus_id, $tuote->id, $tuote->nimi, $tuote->valmistaja,
						$tuote->a_hinta_ilman_alv, $tuote->alv_prosentti, $tuote->alennus_prosentti,
						$tuote->kpl_maara );

			array_push( $values_varastosaldot, $tuote->id, ($tuote->varastosaldo - $tuote->kpl_maara) );
		}

		// Lisätään tilauksen tuotteet
		$stmt->execute( $values );
		// Päivitetään varastosaldot
		$stmt_varastosaldot->execute( $values_varastosaldot );

		// Toimitusosoitteen lisäys tilaustietoihin pysyvästi.
		$stmt = $conn->prepare(
			'INSERT INTO tilaus_toimitusosoite
				(tilaus_id, pysyva_etunimi, pysyva_sukunimi, pysyva_sahkoposti, pysyva_puhelin, 
				pysyva_yritys, pysyva_katuosoite, pysyva_postinumero, pysyva_postitoimipaikka)
			SELECT ?, etunimi, sukunimi, sahkoposti, puhelin, yritys, katuosoite, postinumero, postitoimipaikka
			FROM toimitusosoite 
			WHERE kayttaja_id = ? AND osoite_id = ?' );
		$stmt->execute( [$tilaus_id, $user->id, $_POST['toimitusosoite_id']] );

		// Tallennetaan muutokset, jos ei yhtään virhettä.
		$conn->commit();

		$cart->tyhjenna_kori( $db );

		// Tallenetaan seuraavaa sivua varten tilauksen perustiedot.
		// [ Tilaus-ID, Koko summa yhteensä, Eri tuotteiden määrä, Tuotteiden kpl-määrä yhteensä]
		$_SESSION['tilaus'] = [
			$tilaus_id,
			($cart->summa_yhteensa + $user->rahtimaksu),
			$cart->montako_tuotetta,
			$cart->montako_tuotetta_kpl_maara_yhteensa ];

		header( "location:payment_process.php" );
		exit;

	} catch ( PDOException $ex ) {
		// Rollback any changes, and print error message to user.
		$conn->rollback();
		$_SESSION["feedback"] = "<p class='error'>Tilauksen lähetys ei onnistunut!<br>Virhe: ".
			print_r($ex->errorInfo,1)."</p>";
		// TODO: Do not print error message to user in full (only generic)!
	}
}

/** Tarkistetaan feedback, ja estetään formin uudelleenlähetys */
if ( !empty($_POST) ) { //Estetään formin uudelleenlähetyksen
	header("Location: " . $_SERVER['REQUEST_URI']);
	exit();
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
	<link rel="stylesheet" href="css/jsmodal-light.css">
	<link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
	<link rel="stylesheet" href="css/styles.css">
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
			 <th class="number">Kpl-hinta</th> <th class="number">Kpl</th> <th>Info</th> </tr>
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
			<td>---</td>
			<td>Rahtimaksu</td> <!-- Tuotteen nimi -->
			<td>---</td>
			<td class="number"><?= $user->rahtimaksu_toString() ?></td> <!-- Hinta yhteensä -->
			<td class="number">---</td>
			<td class="number">1</td> <!-- Kpl-määrä -->
			<td><?= ($user->rahtimaksu == 0) ? 'Ilmainen toimitus' : "---" ?></td> <!-- Info -->
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
	<p><a class="nappi red" href="ostoskori.php?cancel_tilaus">Palaa takaisin</a></p>
</main>

<script>
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

		document.getElementById('toimitusosoite_form_input').value = osoite_id+1;

		Modal.close();
	}

	let osoitekirja = <?= json_encode( $user->toimitusosoitteet )?>;
	valitse_toimitusosoite(0);
</script>

</body>
</html>
