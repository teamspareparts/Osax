<?php
require '_start.php'; global $db, $user, $cart;

if ( !$user->isAdmin() ) { // Sivu tarkoitettu vain ylläpitäjille
	header("Location:etusivu.php"); exit();
}

/**
 * Hakee kaikki ALV-kannat tietokannasta. Lisäksi täyttää kantojen arrayin, jos siinä ei ole viisi elementtiä,
 *  koska olen päättänyt yksimielisesti, että meillä on nyt viisi ALV-kantaa + nolla.
 * @param DByhteys $db
 * @return stdClass[]
 */
function hae_kaikki_ALV_kannat( DByhteys $db ) {
	$sql = "SELECT kanta, prosentti FROM ALV_kanta ORDER BY kanta ASC";
	$rows = $db->query( $sql, NULL, FETCH_ALL );

	if ($rows[0]->kanta==0 && $rows[0]->prosentti==0) { unset($rows[0]); }
	for ( $i=count($rows)+1; $i<=5; $i++ ) { // Täytetään array, jos ei tarpeeksi elementtejä
		$rows[] = (object)["kanta" => $i, "prosentti" => 0.00]; // Lisätään tyhjä alv arrayhin object muodossa.
	}
	return $rows;
}

$alvit = hae_kaikki_ALV_kannat( $db );

if ( !empty($_POST["muokkaa_ALV"]) ) {
	$sql = "INSERT INTO ALV_kanta (kanta, prosentti) VALUES ( ?, ? )
			ON DUPLICATE KEY UPDATE prosentti = VALUES(prosentti)";
	$db->prepare_stmt( $sql );
	for ( $i=0; $i<=5; $i++ ) {
		$db->run_prepared_stmt( [$_POST['kanta'][$i], $_POST['pros'][$i]/100] );
	}
}

/** Tarkistetaan feedback, ja estetään formin uudelleenlähetys */
if ( !empty($_POST) ) { //Estetään formin uudelleenlähetyksen
	header("Location: " . $_SERVER['REQUEST_URI']); exit();
} else {
	$feedback = isset($_SESSION['feedback']) ? $_SESSION['feedback'] : '';
	unset($_SESSION["feedback"]);
}
?>
<!DOCTYPE html>
<html lang="fi">
<head>
	<meta charset="UTF-8">
	<link rel="stylesheet" href="css/styles.css">
	<link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
	<script src="https://code.jquery.com/jquery-3.1.1.min.js"></script>
	<title>ALV-muokkaus</title>
	<style>
		input[type=number] {
			text-align: end;
		}
	</style>
</head>
<body>
<?php require "header.php"; ?>
<main class="main_body_container lomake">

	<div class="otsikko_container">
		<section class="takaisin">
		</section>
		<section class="otsikko">
			<h1>ALV-kantojen muokkaus</h1>
		</section>
		<section class="napit">
		</section>
	</div>

	<form method="post">
		<fieldset><legend>Muokkaa ALV-kantoja</legend>
			Kokonaislukuina, kiitos.<br><br>

			<label disabled style="color: #6f6f6f;">ALV-kanta 0:
				<input type='hidden' name='kanta[]' value="0"><input type='hidden' name='pros[]' value="0">
				<input type='number' value="0" disabled>
			</label>
			<br><br>

			<?php foreach ( $alvit as $alv ) : ?>
				<label>ALV-kanta <?= $alv->kanta ?>:
					<input type='hidden' name='kanta[]' value="<?= $alv->kanta ?>">
					<input type='number' name='pros[]' min="0" max="100"
						   value='<?= $alv->prosentti * 100 ?>'> %
				</label>
				<br><br>
			<?php endforeach; ?>

			<br><br>
			<div class="center">
				<input type="submit" class="nappi center" name="muokkaa_ALV" value="Tallenna muutokset">
			</div>
		</fieldset>
	</form>
</main>

<?php require 'footer.php'; ?>

</body>
</html>
