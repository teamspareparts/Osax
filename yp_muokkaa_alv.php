<?php
require "tietokanta.php";

function debug ($var) { echo("<pre>");print_r($var);var_dump($var);echo("</pre>"); }

function hae_kaikki_ALV_kannat_ja_tulosta( DByhteys $db ) {
	$return = '';
	$sql_query = "	SELECT	kanta, prosentti
					FROM	ALV_kanta
					ORDER BY kanta ASC ";
	$rows = $db->query( $sql_query, NULL, DByhteys::FETCH_ALL );

	if ( $rows ){
		foreach ( $rows as $row ) {
			$prosentti = str_replace( '.', ',', $row->prosentti );
			$return .=
				"<label>ALV-kanta {$row->kanta}:</label><input type='text' name='alv[]' value='{$prosentti}'><br>";
		}
	} else {
		$return .= "<label>ALV-kanta 1:</label><input type='text' name='alv[]' placeholder='0,00'><br>";
	}
	return $return;
}


function hae_kaikki_ALV_kannat( DByhteys $db ) {
	$sql_query = "	SELECT kanta, prosentti
					FROM ALV_kanta
					WHERE kanta != 0
					ORDER BY kanta ASC ";
	return $db->query( $sql_query, NULL, DByhteys::FETCH_ALL );

}

function hae_ALV_indeksi( DByhteys $db ) {
	$sql_query = "	SELECT	COUNT(*) as count
					FROM	ALV_kanta";
	$row_count = $db->query( $sql_query, NULL );
	return $row_count;
}

function tallenna_uudet_ALV_tiedot( DByhteys $db, $alv_array ) {
	$index = 0;

	$sql_query = "	INSERT INTO ALV_kanta 
						(kanta, prosentti)
					VALUES ( ?, ? )
					ON DUPLICATE KEY 
						UPDATE prosentti = VALUES(prosentti)";
	$db->prepare_stmt( $sql_query );

	foreach ( $alv_array as $alv ) {
		if ( empty($alv) ) {
			$alv = "0.00";
		}
		$alv = str_replace(',', '.', $alv);
		$db->run_prepared_stmt( [$index, $alv] );
		$index++;
	}
}

$alv_indeksi = hae_ALV_indeksi( $db )->count;
$alvit = hae_kaikki_ALV_kannat ( $db );
//debug( $alvit );

if ( !empty($_POST["muokkaa_ALV"]) ) {
	debug( $_POST["alv"] );
	tallenna_uudet_ALV_tiedot( $db, $_POST["alv"] );
}
?>

<!DOCTYPE html>
<html lang="fi">
<head>
	<meta charset="UTF-8">
	<link rel="stylesheet" href="css/styles.css">
	<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.0/jquery.min.js"></script>
	<title>ALV-muokkaus</title>
	<style>
		.alv_muokkaus {
			width: 350px;
			margin: 20px auto auto;
		}

		input[type=number] {
			text-align: end;
		}
	</style>
</head>
<body>

<?php require "header.php"; ?>
<main class="main_body_container flex_column">
	<fieldset class="alv_muokkaus"><legend>Muokkaa ALV-kantoja</legend>
		Kokonaislukuina, kiitos.
		<form action="#" method="post">

			<label disabled style="color: #6f6f6f;">ALV-kanta 0: </label>
			<input type='number' name='alv[]' value="0" placeholder="0" disabled><br>

			<?php foreach ( $alvit as $alv ) : ?>
				<label>ALV-kanta <?= $alv->kanta ?>:</label>
				<input type='number' name='alv[]' min="0" pattern=".{0,2}"
					   value='<?= substr( $alv->prosentti, 2 )?>'> %<br>
			<?php endforeach;
			if ( !$alvit ) : ?>
				<label>ALV-kanta 1: </label>
				<input type='number' name='alv[]' min="0" pattern=".{0,2}" placeholder="0,00"><br>
			<?php endif; ?>
			<input type="button" id="btnALVadd" name="add_New_ALV_button" value="+ uusi ALV-kanta" onclick="lisaa_uusi_ALV()">
			<br><br>
			<input type="submit" class="nappi" name="muokkaa_ALV" value="Tallenna muutokset" style="text-align:center;">
		</form>
	</fieldset>


	<fieldset class="alv_muokkaus"><legend>Muokkaa ALV-kantoja</legend>
		<form action="#" method="post">
			<div id="alv_form_container">
				<?= hae_kaikki_ALV_kannat_ja_tulosta( $db ) ?>
			</div>
			<input type="button" id="btnALVadd" name="add_New_ALV_button" value="+ uusi ALV-kanta" onclick="lisaa_uusi_ALV()">
			<br><br>
			<input type="submit" class="nappi" name="muokkaa_ALV" value="Tallenna muutokset" style="text-align:center;">
		</form>
	</fieldset>
</main>
</body>

<script>
	var alv_indeksi = <?=$alv_indeksi?> + 1;

	function lisaa_uusi_ALV() {
		var newdiv = document.createElement('div');
		if ( alv_indeksi <= 5 ) {
			newdiv.innerHTML =
				"<label>ALV-kanta " + (alv_indeksi++) + ":</label><input type='text' name='alv[]' placeholder='0,00'><br>";
			$("#alv_form_container").append(newdiv);

			if ( alv_indeksi === 5 ) {
				$("#btnALVadd")
					.prop('disabled', true);
			}
		}
	}

	$(document).ready(function() {
		if ( alv_indeksi === 5 ) {
			$("#btnALVadd").prop('disabled', true);
		}
	});
</script>
</html>
