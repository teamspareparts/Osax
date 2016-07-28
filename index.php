<!DOCTYPE html>
<html>
<head>
	<link rel="stylesheet" type="text/css" href="css/login_styles.css">
	<meta charset="UTF-8">
	<title>Login</title>
</head>
<body>
<main class="login_container">
	<img src="img/rantak_varao-Logo.jpg" alt="Osax.fi">

<?php
session_start();
if ( !empty($_GET['redir']) || !empty($_SESSION['email']) ) {	// Tarkistetaan onko uudellenohjaus

	if ( !empty($_SESSION['email']) ) { $mode = 99; } //Tarkistetaan onko käyttäjä jo kirjautunut sisään
	else { $mode = $_GET["redir"]; } // Otetaan talteen uudelleenohjauksen syy

	$modes_array = [
		1 => array(
				"otsikko" => " Väärä sähköposti ",
				"teksti" => "Sähköpostia ei löytynyt. Varmista, että kirjoitit tiedot oikein.",
				"style" => 'style="color:#0afec0;"'),
		2 => array(
				"otsikko" => " Väärä salasana ",
				"teksti" => "Väärä salasana. Varmista, että kirjoitit tiedot oikein.",
				"style" => 'style="color:#c9f2d0;"'),
		3 => array(
				"otsikko" => " Käyttäjätili de-aktivoitu ",
				"teksti" => "Ylläpitäjä on poistanut käyttöoikeutesi palveluun.",
				"style" => 'style="color:#9b923e;"'),
		4 => array(
				"otsikko" => " Et ole kirjautunut sisään ",
				"teksti" => "Ole hyvä, ja kirjaudu sisään.<p>Sinun pitää kirjautua sisään ennen kuin voit käyttää sivustoa.",
				"style" => 'style="color:#7a0e0b;"'),
		5 => array(
				"otsikko" => " :( ",
				"teksti" => "Olet onnistuneesti kirjautunut ulos.",
				"style" => 'style="color:#dbe32a;"'),
		6 => array(
				"otsikko" => " Salasanan palautus - Palautuslinkki lähetetty",
				"teksti" => "Salasanan palautuslinkki on lähetetty antamaasi osoitteeseen.",
				"style" => 'style="color:#F4917F;"'),
		7 => array(
				"otsikko" => " Salasanan palautus - Pyyntö vanhentunut ",
				"teksti" => "Salasanan palautuslinkki on vanhentunut. Ole hyvä ja kokeile uudestaan.",
				"style" => 'style="color:#312f78;"'),
		8 => array(
				"otsikko" => " Salasanan palautus - Onnistunut ",
				"teksti" => "Salasana on vaihdettu onnistuneesti. Ole hyvä ja kirjaudu uudella salasanalla sisään.",
				"style" => 'style="color:#76b944;"'),
		9 => array(
				"otsikko" => " Käyttöoikeus vanhentunut ",
				"teksti" => "Käyttöoikeutesi palveluun on nyt päättynyt. Jos haluat jatkaa palvelun käyttöä ota yhteyttä sivuston ylläpitäjään.",
				"style" => 'style="color:#1e4d2e;"'),
		10=> array(
				"otsikko" => " Käyttöoikeussopimus ",
				"teksti" => "Sinun on hyväksyttävä käyttöoikeussopimus käyttääksesi sovellusta.",
				"style" => 'style="color:#4a117c;"'),

		99=> array(
				"otsikko" => " Kirjautuminen ",
				"teksti" => 'Olet jo kirjautunut sisään.<p><a href="tuotehaku.php">Linkki etusivulle</a></p>',
				"style" => 'style="color:#934219;"'),
	];
?>
	<fieldset id=error <?= @$modes_array[$mode]['style'] ?>><legend> <?= @$modes_array[$mode]['otsikko'] ?> </legend>
		<p> <?= $modes_array[$mode]['teksti'] ?> </p>
	</fieldset>
<?php } ?>

<!-- <main class="login_container"> -->

	<fieldset><legend>Sisäänkirjautuminen</legend>
		<form action="login_check.php" method="post" accept-charset="utf-8">
			<label>Sähköposti:</label><br>
			<input type="email" name="email" placeholder="yourname@email.com" pattern="^{3,255}$" required autofocus><br>
			<br>
			<label>Salasana:</label><br>
			<input type="password" name="password" placeholder="password" pattern="^{3,255}$" required><br>
			<br>
			<input type="hidden" name="mode" value="login">
			<input type="submit" value="Kirjaudu sisään">
		</form>
	</fieldset>

	<fieldset><legend>Unohditko salasanasi?</legend>
		<form action="login_check.php" method="post" accept-charset="utf-8">
			<label>Sähköposti:</label><br>
			<input type="email" name="email" placeholder="yourname@email.com" pattern="^{3,255}$" required autofocus ><br>
			<br>
			<input type="hidden" name="mode" value="password_reset">
			<input type="submit" value="Uusi salasana">
		</form>
	</fieldset>
</main>

<script>
/** Otamme tämän käyttöön, jos tämä sivusto koskaan menee ihan oikeaan asiakas käyttöön.
 * window.history.pushState('login', 'Title', 'index.php'); //Poistetaan GET URL:sta
 */

/**
 * Tähän evästeiden tarkistus. Evästeillä voimme tallentaa pysyvää tietoa lokaalisti.
 */
</script>

</body>
</html>
<!-- EOF -->