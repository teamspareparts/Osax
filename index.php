<!DOCTYPE html>
<html>
<head>
	<link rel="stylesheet" type="text/css" href="css/login_styles.css">
	<meta charset="UTF-8">
	<title>Login</title>
</head>
<body class=login>
<h1 style="text-align:center;">
	<img src="img/rantak_varao-Logo.jpg" alt="Rantakylän Varaosa Oy" style="height:200px;">
</h1>

<!-- -->
<div id="login_container"><a></a>
<?php

if (!empty($_GET['redir'])) {	// Onko uudellenohjaus?
	/**
	 * Mikäli halutaan poistaa urlissa oleva get
	 * ?>  
	 *	<script type="text/javascript">
	 *	window.history.pushState('login', 'Title', 'index.php');
	 *	</script> 
	 * <?php
	**/
	$mode = $_GET["redir"];		// Otetaan moodi talteen
	$modes_array = [
		1 => array(
				"otsikko" => " Väärä sähköposti ",
				"teksti" => "Sähköpostia ei löytynyt. Varmista, että kirjoitit tiedot oikein."),
		2 => array(
				"otsikko" => " Käyttäjätili poistettu ",
				"teksti" => "Ylläpitäjä on poistanut käyttöoikeutesi palveluun."),
		3 => array(
				"otsikko" => " Väärä salasana ",
				"teksti" => "Väärä salasana. Varmista, että kirjoitit tiedot oikein."),
		4 => array(
				"otsikko" => " Et ole kirjautunut sisään ",
				"teksti" => "Ole hyvä, ja kirjaudu sisään.<p>Sinun pitää kirjautua sisään ennen kuin voit käyttää sivustoa."),
		5 => array(
				"otsikko" => " :( ",
				"teksti" => "Olet onnistuneesti kirjautunut ulos."),
		6 => array(
				"otsikko" => " Salasanan palautus - Palautuslinkki lähetetty",
				"teksti" => "Salasanan palautuslinkki on lähetetty antamaasi osoitteeseen."),
		7 => array(
				"otsikko" => " Salasanan palautus - Pyyntö vanhentunut ",
				"teksti" => "Salasanan palautuslinkki on vanhentunut. Ole hyvä ja kokeile uudestaan."),
		8 => array(
				"otsikko" => " Salasanan palautus - Onnistunut ",
				"teksti" => "Salasana on vaihdettu onnistuneesti. Ole hyvä ja kirjaudu uudella salasanalla sisään."),
		9 => array(
				"otsikko" => " Käyttöoikeus vanhentunut ",
				"teksti" => "Käyttöoikeutesi palveluun on nyt päättynyt. Jos haluat jatkaa palvelun käyttöä ota yhteyttä Rantakylän Varaosa Oy:hyn."),
	];
?>
	<div id="content">
		<fieldset id=error><legend> <?= $modes_array[$mode]['otsikko'] ?> </legend>
			<p> <?= $modes_array[$mode]['teksti'] ?> </p>
		</fieldset>
	</div>
<?php
}?>

	<fieldset><legend>Sisäänkirjautuminen</legend>
		<form name="login" action="login_check.php" method="post" accept-charset="utf-8">
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
		<form name="login" action="login_check.php" method="post" accept-charset="utf-8">
			<label>Sähköposti:</label><br>
			<input type="email" name="email" placeholder="yourname@email.com" pattern="^{3,255}$" required autofocus ><br>
			<br>
			<input type="hidden" name="mode" value="password_reset">
			<input type="submit" value="Uusi salasana">
		</form>
	</fieldset>
	
</div>
</body>
</html>
<!-- EOF -->
