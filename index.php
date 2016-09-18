<!DOCTYPE html>
<html>
<head>
	<link rel="stylesheet" type="text/css" href="css/login_styles.css">
	<meta charset="UTF-8">
	<title>Login</title>
</head>
<body>
<main class="login_container">
	<img src="img/osax_logo.jpg" alt="Osax.fi">

<?php
session_start();
if ( !empty($_GET['redir']) || !empty($_SESSION['email']) ) :  // Tarkistetaan onko uudellenohjaus

	if ( !empty($_SESSION['email']) ) { $mode = 99; } //Tarkistetaan onko käyttäjä jo kirjautunut sisään
	else { $mode = $_GET["redir"]; } // Otetaan talteen uudelleenohjauksen syy

	/*
	 * Error-boxin väritys. Muuta haluamaasi väriin. Jos haluat muuttaa vain yksittäisen
	 * boxin värin, niin muuta suoraan style-merkkijonoon.
	 */
	$colors = [
		'warning' => 'red',
		'success' => 'green',
		'note' => 'blue',
	];

	$modes_array = [
		1 => array(
				"otsikko" => " Väärä sähköposti ",
				"teksti" => "Sähköpostia ei löytynyt. Varmista, että kirjoitit tiedot oikein.",
				"style" => "style='color:{$colors['warning']};'"),
		2 => array(
				"otsikko" => " Väärä salasana ",
				"teksti" => "Väärä salasana. Varmista, että kirjoitit tiedot oikein.",
				"style" => "style='color:{$colors['warning']};'"),
		3 => array(
				"otsikko" => " Käyttäjätili de-aktivoitu ",
				"teksti" => "Ylläpitäjä on poistanut käyttöoikeutesi palveluun.",
				"style" => "style='color:{$colors['warning']};'"),
		4 => array(
				"otsikko" => " Et ole kirjautunut sisään ",
				"teksti" => "Ole hyvä, ja kirjaudu sisään.<p>Sinun pitää kirjautua sisään ennen kuin voit käyttää sivustoa.",
				"style" => "style='color:{$colors['warning']};'"),
		5 => array(
				"otsikko" => " Kirjaudutaan ulos ",
				"teksti" => "Olet onnistuneesti kirjautunut ulos.",
				"style" => "style='color:{$colors['note']};'"),
		6 => array(
				"otsikko" => " Salasanan palautus - Palautuslinkki lähetetty",
				"teksti" => "Salasanan palautuslinkki on lähetetty antamaasi osoitteeseen.",
				"style" => "style='color:{$colors['success']};'"),
		7 => array(
				"otsikko" => " Salasanan palautus - Pyyntö vanhentunut ",
				"teksti" => "Salasanan palautuslinkki on vanhentunut. Ole hyvä ja kokeile uudestaan.",
				"style" => "style='color:{$colors['warning']};'"),
		8 => array(
				"otsikko" => " Salasanan palautus - Onnistunut ",
				"teksti" => "Salasana on vaihdettu onnistuneesti. Ole hyvä ja kirjaudu uudella salasanalla sisään.",
				"style" => "style='color:{$colors['success']};'"),
		9 => array(
				"otsikko" => " Käyttöoikeus vanhentunut ",
				"teksti" => "Käyttöoikeutesi palveluun on nyt päättynyt. 
							Jos haluat jatkaa palvelun käyttöä ota yhteyttä sivuston ylläpitäjään.",
				"style" => "style='color:{$colors['warning']};'"),
		10=> array(
				"otsikko" => " Käyttöoikeussopimus ",
				"teksti" => "Sinun on hyväksyttävä käyttöoikeussopimus käyttääksesi sovellusta.",
				"style" => "style='color:{$colors['warning']};'"),

		99=> array(
				"otsikko" => " Kirjautuminen ",
				"teksti" => 'Olet jo kirjautunut sisään.<p><a href="etusivu.php">Linkki etusivulle</a></p>
							<p><a href="logout.php">Kirjaudu ulos</a></p>',
				"style" => "style='color:{$colors['note']};'"),
	];

	if ( in_array( $mode, $modes_array ) ) : ?>
		<fieldset id=error <?= $modes_array[$mode]['style'] ?>><legend> <?= $modes_array[$mode]['otsikko'] ?> </legend>
			<p> <?= $modes_array[$mode]['teksti'] ?> </p>
		</fieldset>
<?php endif; //in_array()
endif; //!empty redir ?>

<!-- <main class="login_container"> -->

	<fieldset><legend>Sisäänkirjautuminen</legend>
		<form action="login_check.php" method="post" accept-charset="utf-8">
			<label>Sähköposti:</label><br>
			<input type="email" name="email" placeholder="Nimi @ Email.com" pattern=".{8,255}$"
				   required autofocus><br>
			<br>
			<label>Salasana:</label><br>
			<input type="password" name="password" placeholder="Salasana" pattern=".{5,255}$" required>
			<br><br>
			<input type="hidden" name="mode" value="login">
			<input type="submit" value="Kirjaudu sisään" id="login_submit">
		</form>
	</fieldset>

	<fieldset><legend>Unohditko salasanasi?</legend>
		<form action="login_check.php" method="post" accept-charset="utf-8">
			<label>Sähköposti:</label><br>
			<input type="email" name="email" placeholder="Nimi @ Email.com" pattern=".{3,255}$"
				   required autofocus ><br>
			<br>
			<input type="hidden" name="mode" value="password_reset">
			<input type="submit" value="Uusi salasana">
		</form>
	</fieldset>

	<fieldset><legend>Yhteystiedot</legend>
		Osax Oy, Lahti<p>
		janne @ osax.fi
	</fieldset>
</main>

<script>
	window.history.pushState('login', 'Title', 'index.php'); //Poistetaan GET URL:sta
	//TODO: Evästeet
</script>

</body>
</html>
<!-- EOF -->
