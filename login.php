<!DOCTYPE html>
<html>
<head>
	<link rel="stylesheet" type="text/css" href="css/login_styles.css">
	<meta charset="UTF-8">
	<title>Login</title>
</head>
<body>
<h1 style="text-align:center;">
	<img src="img/rantak_varao-Logo.jpg" alt="Rantakylän Varaosa Oy" style="height:200px;">
</h1>

<!-- -->
<div id="login_container">
<?php
if (!empty($_GET['redir'])) {	// Onko uudellenohjaus?
	$mode = $_GET["redir"];		// Otetaan moodi talteen
	if ( $mode == 1 ) {			// Jos moodi == väärä sähköposti
?>
		<div id="content">
			<fieldset id=error>
				<legend> Väärä sähköposti </legend>
				<p>Sähköposti on väärä. Varmista, että kirjoitit tiedot oikein.</p>
			</fieldset>
		</div>
<?php
	} elseif ( $mode == 3 ) {	// Jos moodi == väärä salasana
?>
		<div id="content">
			<fieldset id=error>
				<legend> Väärä salasana </legend>
				<p>Väärä salasana. Varmista, että kirjoitit tiedot oikein.</p>
			</fieldset>
		</div>
<?php
	} elseif ( $mode == 4 ) {	// Jos moodi == käyttäjätili poistettu
?>
		<div id="content">
			<fieldset id=error>
				<legend> Käyttäjätili poistettu </legend>
				<p>Ylläpitäjä on poistanut käyttöoikeutesi palveluun.</p>
			</fieldset>
		</div>
<?php
	} elseif ( $mode == 5 ) {	// Jos moodi == ei ole kirjautunut sisään
?>
		<div id="content">
			<fieldset id=error>
				<legend> Et ole kirjautunut sisään </legend>
				<p>Ole hyvä, ja kirjaudu sisään.
				Sinun pitää kirjautua sisään ennen kuin voit käyttää sivustoa.</p>
			</fieldset>
		</div>
<?php
	} elseif ( $mode == 6 ) {	// Jos moodi == uloskirjautuminen
?>
		<div id="content">
			<fieldset id=error>
				<legend> :( </legend>
				<p>Olet onnistuneesti kirjautunut ulos.</p>
			</fieldset>
		</div>
<?php
	} elseif ( $mode == 2 ) {	// Jos moodi == salasanan palautus
?>
		<div id="content">
			<fieldset id=error>
				<legend> Salasanan palautus </legend>
				<p>Salasanan palautuslinkki on lähetetty antamaasi osoitteeseen.</p>
			</fieldset>
		</div>
<?php
	} elseif ( $mode == 7 ) {	// Jos moodi == pyyntö vanhentunut
?>
		<div id="content">
			<fieldset id=error>
				<legend> Salasanan palautus - Pyyntö vanhentunut </legend>
				<p>Salasanan palautuslinkki on vanhentunut. Ole hyvä ja kokeile uudestaan.</p>
			</fieldset>
		</div>
<?php
	} elseif ( $mode == 8 ) {	// Jos moodi == salasanan uusiminen onnistunut
?>
		<div id="content">
			<fieldset id=error>
				<legend> Salasanan palautus - Onnistunut </legend>
				<p>Salasana on vaihdettu onnistuneesti. Ole hyvä ja kirjaudu uudella salasanalla sisään.</p>
			</fieldset>
		</div>
<?php
	} elseif ( $mode == 9 ) {	// Jos moodi == salasanan palautuslinkin lähteys
?>
		<div id="content">
			<fieldset id=error>
				<legend> Salasanan palautus - Palautuslinkki lähetetty </legend>
				<p>Salasanan palautuslinkki on lähetetty sähköpostiinne.</p>
			</fieldset>
		</div>
<?php
	}
}
?>


	<fieldset><legend>Sisäänkirjautuminen</legend>
		<form name="login" action="login_check.php" method="post" accept-charset="utf-8">
			<label>Sähköposti:</label><br>
			<input type="email" name="email" placeholder="yourname@email.com" required autofocus><br>
			<br>
			<label>Salasana:</label><br>
			<input type="password" name="password" placeholder="password" required><br>
			<br>
			<input type="hidden" name="mode" value="login">
			<input type="submit" value="Kirjaudu sisään">
		</form>
	</fieldset>

	<fieldset><legend>Unohditko salasanasi?</legend>
		<form name="login" action="login_check.php" method="post" accept-charset="utf-8">
			<label>Sähköposti:</label><br>
			<input type="email" name="email" placeholder="yourname@email.com" required autofocus ><br>
			<br>
			<input type="hidden" name="mode" value="password_reset">
			<input type="submit" value="Uusi salasana">
		</form>
	</fieldset>
</div>



</body>
</html>
<!-- EOF -->
