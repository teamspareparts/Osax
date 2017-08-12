<?php
require '_start.php'; global $db, $user, $cart;
if ( !$user->isAdmin() ) {
	header("Location:etusivu.php"); exit();
}

$hankintapaikka_id = !empty($_GET['hkp']) ? $_GET['hkp'] : null;

$sql = "SELECT id, nimi, DATE_FORMAT(tehdassaldo_viim_paivitys,'%d.%m.%Y %H:%i') AS viimPaiv FROM hankintapaikka WHERE id = ?";
$hkp = $db->query( $sql, [$hankintapaikka_id]);

if ( !empty( $_FILES ) and !empty($hankintapaikka_id) ) {
	// Alustukset
	$ohita_otsikkorivi = !empty($_POST['otsikkorivi']);
	$rows_in_query_at_one_time = 15000;
	$values = array();
	$handle = fopen($_FILES['tehdassaldot_csv']['tmp_name'], 'r');

	if ( $ohita_otsikkorivi ) {
		fgetcsv($handle, 100, ";");
	}

	while (($data = fgetcsv($handle, 1000, ";")) !== false) {
		$values[] = (int)$hankintapaikka_id; // hkp-ID
		$values[] = utf8_encode( str_replace( [" ","'"], "", $data[ 0 ] ) );  // tuote artikkeli-nro
		$values[] = (int)$data[ 1 ]; // tehdassaldo
	}

	$sql = "INSERT INTO toimittaja_tehdassaldo (hankintapaikka_id, tuote_articleNo, tehdassaldo) VALUES (?,?,?)
			ON DUPLICATE KEY UPDATE tehdassaldo = VALUES(tehdassaldo)";

	$values = array_chunk($values,$rows_in_query_at_one_time);

	foreach ( $values as $values_chunk ) {
		$db->query(
			str_replace("(?,?,?)",
						str_repeat('(?, ?, ?),', (count($values_chunk)/3)-1) . "(?, ?, ?)",
						$sql),
			$values_chunk);
	}

	$db->query( "UPDATE hankintapaikka SET tehdassaldo_viim_paivitys = NOW() WHERE id = ?",
	            [$hankintapaikka_id] );

	$_SESSION['feedback'] = "<p class='success'>Tehdassaldot päivitetty onnistuneesti</p>";
}

/** Tarkistetaan feedback, ja estetään formin uudelleenlähetys */
if ( false and !empty($_POST) or !empty($_FILES) ) { //Estetään formin uudelleenlähetyksen
	header("Location: " . $_SERVER['REQUEST_URI']); exit();
} else {
	$feedback = isset($_SESSION['feedback']) ? $_SESSION['feedback'] : '';
	unset($_SESSION["feedback"]);
}
?>
<!DOCTYPE html><html lang="fi">
<head>
	<meta charset="utf-8">
	<link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
	<link rel="stylesheet" href="./css/styles.css">
	<style type="text/css">
		#csv_tiedosto {
			border: 1px dashed;
			border-radius: 6px;
			padding: 15px;
		}
		#csv_tiedosto:hover {
			border-color: #1d7ae2;
		}
	</style>
</head>
<body>

<?php require 'header.php'; ?>

<main class="main_body_container lomake">
	<div class="otsikko_container">
		<section class="takaisin">
			<a href="yp_hankintapaikka.php?hankintapaikka_id=<?=$hkp->id?>" class="nappi grey">
				<i class="material-icons">navigate_before</i>Hankintapaikka</a>
		</section>
		<section class="otsikko">
			<h1>Tehdassaldojen manuaalinen päivitys</h1>
		</section>
		<section class="napit">
		</section>
	</div>

	<div class="white-bg" style="border:1px solid;border-radius:3px;width:450px;margin:auto;text-align:left;padding:10px;">
		<p>Tällä sivulla voit päivittää <strong><?="{$hkp->id}: {$hkp->nimi}"?></strong> tehdassaldot.</p>
		<p>Viimeksi päivitetty: <strong><?= !empty($hkp->viimPaiv) ? $hkp->viimPaiv : "Ei koskaan" ?></strong></p>
		<div>CSV-tiedoston muoto:<br>
			<p style="margin-left:20px;margin-top:3px;">
			(Vaihtoehtoinen otsikkorivi)<br>
			tuote-nro ; kpl-määrä
			</p>
		</div>
		<p>Tuotenumeroista poistetaan tyhjät välit ja heittomerkit.</p>
	</div>
	<br><br>

	<fieldset style="width:475px;"><legend>Tehdassaldot</legend>
		<form action="#" method="post" enctype="multipart/form-data">
			<label>Otsikkorivi:
				<input type="checkbox" name="otsikkorivi">
			</label>
			<br><br>
			<label>CSV:
				<input type="file" name="tehdassaldot_csv" accept=".csv" id="csv_tiedosto">
			</label>
			<input type="submit" name="submit" value="Submit" class="nappi" id="submit_csv" disabled>

			<p class="small_note">Drag&Drop toimii myös.</p>
		</form>
	</fieldset>
</main>

<?php require 'footer.php'; ?>

<script type="text/javascript">
	document.getElementById('csv_tiedosto').addEventListener('change', function (el) {
		document.getElementById('submit_csv').disabled = !el.target.value;
	})
</script>

</body>
</html>
