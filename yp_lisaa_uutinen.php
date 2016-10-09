<?php
require '_start.php'; global $db, $user, $cart, $yritys;

if ( !$user->isAdmin() ) { // Sivu tarkoitettu vain ylläpitäjille
	header("Location:etusivu.php"); exit();
}

if ( isset($_POST['new_news']) ) {
	$db->query( "INSERT INTO etusivu_uutinen (otsikko, tyyppi, teksti) VALUES (?,?,?)",
		[$_POST['text_headline'], $_POST['text_type'], $_POST['text_content']] );
	header("location:etusivu.php?test"); exit;
}
?>
<!DOCTYPE html>
<html lang="fi">
<head>
	<meta charset="UTF-8">
	<title>Lisää uutinen</title>
	<link rel="stylesheet" href="css/styles.css">
	<link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
	<script src="https://code.jquery.com/jquery-3.1.1.min.js"></script>
	<style>
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

			<select title="Tekstin sisällön sijainti" name="text_type" required>
				<option value="" disabled selected>--- Valitse tekstin sijainti ---</option>
				<option value="0">Vasen kolumni</option>
				<option value="1">Keskimmäinen kolumni</option>
				<option value="2">Oikea kolumni</option>
			</select>

			<textarea maxlength="10000" placeholder="TEKSTIN SISÄLTÖ" rows="10" name="text_content"
					  title="Tekstin sisältö. Hyväksyy HTML:ää." required></textarea>

			<input type="hidden" name="new_news">
			<input type="submit" class="nappi" value="Lisää uusi teksti etusivulle">

		</form>
	</fieldset>
</main>

</body>
</html>
