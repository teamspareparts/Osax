<?php
require '_start.php'; global $db, $user, $cart;

if ( !$user->isAdmin() ) { // Sivu tarkoitettu vain ylläpitäjille
	header("Location:etusivu.php"); exit();
}

if ( isset($_POST['text_headline']) ) {
	$db->query( "INSERT INTO etusivu_uutinen (otsikko, tyyppi, summary, details) VALUES (?,?,?,?)",
		[$_POST['text_headline'], $_POST['text_type'], $_POST['text_summary'], $_POST['text_details']] );
	header("location:etusivu.php?test"); exit;
}
?>
<!DOCTYPE html>
<html lang="fi">
<head>
	<meta charset="UTF-8">
	<title>Lisää uutinen</title>
	<link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
	<link rel="stylesheet" href="css/styles.css">
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

			<input type="text" name="text_headline" id="otsikko" title="Tekstin otsikko" placeholder="TEKSTIN OTSIKKO"
				   maxlength="50" required>

			<select title="Tekstin sisällön sijainti" name="text_type" required>
				<option disabled selected>--- Valitse tekstin sijainti ---</option>
				<option value="0">Vasen kolumni</option>
				<option value="1">Keskimmäinen kolumni</option>
				<option value="2">Oikea kolumni</option>
			</select>

			<textarea name="text_summary" maxlength="200" placeholder="SUMMARY. Tekstin tiivistelmä."
			          rows="3" title="Tekstin sisältö. Hyväksyy HTML:ää." required></textarea>

			<textarea name="text_details" maxlength="10000" placeholder="DETAILS. Tarkempaa lisätietoa." rows="10"
					  title="Tekstin sisältö. Hyväksyy HTML:ää."></textarea>

			<input type="submit" value="Lisää uusi teksti etusivulle" class="nappi">

		</form>
	</fieldset>

	<div>
		<p>Tekstin summary näytetään käyttäjälle. Details on piilotettu kunnes käyttäjä klikkaa uutista.</p>
	</div>
</main>

</body>
</html>
