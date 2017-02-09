<?php
/**
 * @version 2016-02-08 <p> Lisätty versionumero ja <noscript>-varoitus.
 */
//TODO: Hetkinen, oliko meidän tarkoitus muuttaa tämän tiedoston nimeä? --JJ 17-02-08
session_start();
/**
 * Tarkistetaan onko kyseessä uudelleenohjaus, ja tulostetaan viesti sen mukaan.
 */
if ( !empty($_GET['redir']) || !empty($_SESSION['id']) ) {

	if ( !empty($_SESSION['id']) ) { $mode = 99; } //Tarkistetaan onko käyttäjä jo kirjautunut sisään
	else { $mode = $_GET["redir"]; } // Otetaan talteen uudelleenohjauksen syy

	/**
	 * @var array <p> Error-boxin väritys. Muuta haluamaasi väriin. Jos haluat muuttaa
	 * vain yksittäisen boxin värin, niin muuta suoraan style-merkkijonoon.
	 */
	$colors = [
		'warning' => 'red',
		'success' => 'green',
		'note' => 'blue',
	];

	/**
	 * @var array <p> Pitää sisällään <fieldset>-tagin sisälle tulostettavan viestin.
	 * GET-arvona saatava $mode määrittelee mikä index tulostetaan.
	 */
	$modes_array = [
		1 => array(
			"otsikko" => " Väärä sähköposti ",
			"teksti" => "Sähköpostia ei löytynyt. Varmista, että kirjoitit tiedot oikein.",
			"style" => "style='color:{$colors['warning']};'" ),
		2 => array(
			"otsikko" => " Väärä salasana ",
			"teksti" => "Väärä salasana. Varmista, että kirjoitit tiedot oikein.",
			"style" => "style='color:{$colors['warning']};'" ),
		3 => array(
			"otsikko" => " Käyttäjätili de-aktivoitu ",
			"teksti" => "Ylläpitäjä on poistanut käyttöoikeutesi palveluun.",
			"style" => "style='color:{$colors['warning']};'" ),
		4 => array(
			"otsikko" => " Et ole kirjautunut sisään ",
			"teksti" => "Ole hyvä, ja kirjaudu sisään." . "<p>" .
						"Sinun pitää kirjautua sisään ennen kuin voit käyttää sivustoa.",
			"style" => "style='color:{$colors['warning']};'" ),
		5 => array(
			"otsikko" => " Kirjaudutaan ulos ",
			"teksti" => "Olet onnistuneesti kirjautunut ulos.",
			"style" => "style='color:{$colors['note']};'" ),
		6 => array(
			"otsikko" => " Salasanan palautus - Palautuslinkki lähetetty",
			"teksti" => "Salasanan palautuslinkki on lähetetty antamaasi osoitteeseen.",
			"style" => "style='color:{$colors['success']};'" ),
		7 => array(
			"otsikko" => " Salasanan palautus - Pyyntö vanhentunut ",
			"teksti" => "Salasanan palautuslinkki on vanhentunut. Ole hyvä ja kokeile uudestaan.",
			"style" => "style='color:{$colors['warning']};'" ),
		8 => array(
			"otsikko" => " Salasanan palautus - Onnistunut ",
			"teksti" => "Salasana on vaihdettu onnistuneesti. Ole hyvä ja kirjaudu uudella salasanalla sisään.",
			"style" => "style='color:{$colors['success']};'" ),
		9 => array(
			"otsikko" => " Käyttöoikeus vanhentunut ",
			"teksti" => "Käyttöoikeutesi palveluun on nyt päättynyt. 
						 Jos haluat jatkaa palvelun käyttöä ota yhteyttä sivuston ylläpitäjään.",
			"style" => "style='color:{$colors['warning']};'" ),
		10 => array(
			"otsikko" => " Käyttöoikeussopimus ",
			"teksti" => "Sinun on hyväksyttävä käyttöoikeussopimus käyttääksesi sovellusta.",
			"style" => "style='color:{$colors['warning']};'" ),

		99 => array(
			"otsikko" => " Kirjautuminen ",
			"teksti" => 'Olet jo kirjautunut sisään.<p>
						<a href="etusivu.php">Linkki etusivulle</a><p>
						<a href="logout.php">Kirjaudu ulos</a>',
			"style" => "style='color:{$colors['note']};'" ),
	];
}
// Varmistetaan vielä lopuksi, että uusin CSS-tiedosto on käytössä. (See: cache-busting)
$css_version = filemtime( 'css/login_styles.css' );
?>
<!DOCTYPE html>
<html>
<head>
	<link rel="stylesheet" type="text/css" href="css/login_styles.css?v=<?=$css_version?>">
	<meta charset="UTF-8">
	<title>Login</title>
</head>
<body>
<main class="login_container">
	<img src="img/osax_logo.jpg" alt="Osax.fi">

<?php
if ( !empty($mode) && !empty($modes_array) && array_key_exists( $mode, $modes_array ) ) : ?>
	<fieldset id=error <?= $modes_array[$mode]['style'] ?>><legend> <?= $modes_array[$mode]['otsikko'] ?> </legend>
		<p> <?= $modes_array[$mode]['teksti'] ?> </p>
	</fieldset>
<?php endif; ?>

	<fieldset><legend>Sisäänkirjautuminen</legend>
		<noscript>
			<p>Sivusto vaatii javascriptin toimiakseen. Juuri nyt käyttämässäsi selaimessa ei ole
			javascript päällä. Ohjeet miten javascriptin saa päälle selaimessa (englanniksi):
			<a href="http://www.enable-javascript.com/" target="_blank">
			instructions how to enable JavaScript in your web browser</a>.</p>
		</noscript>
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
		janne (at) osax.fi
	</fieldset>
</main>

<script>
	window.history.pushState('login', 'Title', 'index.php'); //Poistetaan GET URL:sta
	//TODO: Evästeet
</script>

</body>
</html>
