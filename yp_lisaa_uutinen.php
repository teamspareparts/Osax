<?php
require 'tietokanta.php';

function debug ($var) {echo "<pre>";print_r($var);var_dump($var);echo "</pre>";}

if ( isset($_POST['new_news']) ) {
	$db->query( "INSERT INTO etusivu_uutinen (otsikko, tyyppi, teksti) VALUES (?,?,?)",
		[$_POST['text_headline'],$_POST['text_type'],$_POST['text_content']] );
	header("location:etusivu.php?test"); exit;
}
?>
<!DOCTYPE html>
<html lang="fi">
<head>
	<meta charset="UTF-8">
	<link rel="stylesheet" href="css/styles.css">
	<link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
	<!-- https://design.google.com/icons/ -->

	<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.0/jquery.min.js"></script>
	<script src="https://ajax.googleapis.com/ajax/libs/jqueryui/1.11.4/jquery-ui.min.js"></script>

	<title>Osax - Etusivu</title>
	<style>
		main, div, section {
			border: 1px solid;
		}
		.fp_content_form input[type=text], select, textarea {
			padding: 5px;
			margin: 5px;
		}
	</style>
</head>
<body>
<?php require 'header.php'; ?>
<main class="main_body_container">
	<fieldset><legend>Lisää uusi uutinen/mainos etusivulle</legend>
		<form class="flex_column fp_content_form" method="post">

			<input type="text" id="otsikko" title="Tekstin otsikko" placeholder="TEKSTIN OTSIKKO"
				   maxlength="50" name="text_headline" required>

			<select title="Tekstin sisällön tyyppi" name="text_type" required>
				<option value="" disabled selected>--- Valitse tekstin tyyppi ---</option>
				<option value="0">Mainos (vasen kolumni)</option>
				<option value="1">Uutinen (keskimmäinen kolumni)</option>
			</select>

			<textarea maxlength="10000" placeholder="TEKSTIN SISÄLTÖ" rows="10" name="text_content"
					  title="Tekstin sisältö. Hyväksyy HTML:ää." required></textarea>

			<input type="hidden" name="new_news">
			<input type="submit" class="nappi" value="Lisää uusi teksti etusivulle">

		</form>
	</fieldset>
	<?php

	if ( isset($_POST['new_news']) ) {
		debug($_POST);
	}
	?>
</main>

</body>
</html>
