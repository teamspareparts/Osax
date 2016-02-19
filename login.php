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
	if ( $mode == 1 ) {			// Jos moodi == väärä kirjautuminen
?>
		<div id="content">
			<fieldset id=error>
				<legend>Väärä sähköposti</legend>
				<p>Sähköposti on väärä. Varmista, että kirjoitit tiedot oikein.</p>
			</fieldset>
		</div>
		
<?php
	} elseif ( $mode == 3 ) {	// Jos moodi == salasanan palautus
?>
		<div id="content">
			<fieldset id=error>
				<legend>Väärä salasana</legend>
				<p>Väärä salasana. Varmista, että kirjoitit tiedot oikein.</p>
			</fieldset>
		</div>
<?php
	} elseif ( $mode == 2 ) {	// Jos moodi == salasanan palautus
?>
		<div id="content">
			<fieldset id=error>
				<legend>Salasanan palautus</legend>
				<p>Salasanan palautuslinkki on lähetetty antamaasi osoitteeseen.</p>
			</fieldset>
		</div>
<?php
	}
}
?>


	<fieldset>
		<legend>Sisäänkirjautuminen</legend>
		<form name="login" action="login_check.php" method="post" accept-charset="utf-8">
			<label>Email:</label><br>
			<input type="email" name="email" placeholder="yourname@email.com" required autofocus ><br>
			<label>Password:</label><br>
			<input type="password" name="password" placeholder="password" required><br>
			<br>
			<input type="hidden" name="mode" value="login">
			<input type="submit" value="Kirjaudu sisään">
		</form>
	</fieldset>
	
	<br><br>
	
	<fieldset>
		<legend>Unohditko salasanasi?</legend>
		<form name="login" action="login_check.php" method="post" accept-charset="utf-8">
			<label>Email:</label><br>
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