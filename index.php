<?php
session_start();
$config = parse_ini_file( "./config/config.ini.php" );

/**
 * Tarkistetaan onko kyseessä uudelleenohjaus, ja tulostetaan viesti sen mukaan.
 */
if ( !empty($_GET['redir']) || !empty($_SESSION['id']) ) {

	$mode = !empty( $_SESSION[ 'id' ] ) ? 99 : $_GET[ "redir" ];

	/**
	 * @var array <p> Pitää sisällään käyttäjälle sisälle tulostettavan viestin.
	 * GET-arvona saatava $mode määrittelee mikä index tulostetaan.
	 */
	$modes_array = [
		1 => array(
			"otsikko" => "Sähköpostia ei löytynyt.",
			"teksti" => "Varmista, että kirjoitit tiedot oikein.",
			"style" => "error" ),
		2 => array(
			"otsikko" => "Väärä salasana",
			"teksti" => "Varmista, että kirjoitit tiedot oikein.",
			"style" => "error" ),
		3 => array(
			"otsikko" => "Käyttäjätili de-aktivoitu",
			"teksti" => "Ylläpitäjä on poistanut käyttöoikeutesi palveluun.",
			"style" => "error" ),
		4 => array(
			"otsikko" => "Et ole kirjautunut sisään",
			"teksti" => "Sinun pitää kirjautua sisään ennen kuin voit käyttää sivustoa.",
			"style" => "error" ),
		5 => array(
			"otsikko" => "Kirjaudutaan ulos",
			"teksti" => "Olet onnistuneesti kirjautunut ulos.",
			"style" => "info" ),
		6 => array(
			"otsikko" => "Salasanan palautus - Palautuslinkki lähetetty",
			"teksti" => "Salasanan palautuslinkki on lähetetty antamaasi osoitteeseen.<p></p>
						 Varmista, että sähköposti ei mennyt roskaposteihin.",
			"style" => "warning" ),
		7 => array(
			"otsikko" => " Salasanan palautus - Pyyntö vanhentunut ",
			"teksti" => "Salasanan palautuslinkki on vanhentunut. Ole hyvä ja kokeile uudestaan.",
			"style" => "error" ),
		8 => array(
			"otsikko" => " Salasanan palautus - Onnistunut ",
			"teksti" => "Salasana on vaihdettu onnistuneesti. Voit nyt kirjautua sisään uudella salasanalla.",
			"style" => "success" ),
		9 => array(
			"otsikko" => " Käyttöoikeus vanhentunut ",
			"teksti" => "Käyttöoikeutesi palveluun on nyt päättynyt. 
						 Jos haluat jatkaa palvelun käyttöä ota yhteyttä sivuston ylläpitäjään.",
			"style" => "error" ),
		10 => array(
			"otsikko" => " Käyttöoikeussopimus ",
			"teksti" => "Sinun on hyväksyttävä käyttöoikeussopimus käyttääksesi sovellusta.",
			"style" => "error" ),

		98 => array(
			"otsikko" => " Error ",
			"teksti" => 'Jotain meni vikaan',
			"style" => "error" ),
		99 => array(
			"otsikko" => "Olet jo kirjautunut sisään.",
			"teksti" => '<p><a href="etusivu.php">Linkki etusivulle</a>
						<p><a href="logout.php">Kirjaudu ulos</a>',
			"style" => "info" ),
	];
}
// Varmistetaan vielä lopuksi, että uusin CSS-tiedosto on käytössä. (See: cache-busting)
$css_version = filemtime( 'css/login_styles.css' );
?>
<!DOCTYPE html>
<html>
<head>
	<meta charset="UTF-8">
	<link rel="stylesheet" type="text/css" href="css/login_styles.css?v=<?= $css_version ?>">
	<title>Login</title>
</head>
<body>
<main class="login_container">
	<img src="img/osax_logo.jpg" alt="Osax.fi">

	<noscript>
		<div class="error">
			<span style="font-weight:bold;">Sivusto vaatii javascriptin toimiakseen.</span> <hr>
			Juuri nyt käyttämässäsi selaimessa ei ole javascript päällä.
			Ohjeet miten javascriptin saa päälle selaimessa (englanniksi):
			<a href="http://www.enable-javascript.com/" target="_blank">
				instructions how to enable JavaScript in your web browser</a>.
		</div>
	</noscript>


	<?php if ( !empty( $mode ) && !empty( $modes_array ) && array_key_exists( $mode, $modes_array ) ) : ?>
		<div class="<?= $modes_array[ $mode ][ 'style' ] ?>">
			<span style="font-weight:bold;display: block;"> <?= $modes_array[ $mode ][ 'otsikko' ] ?> </span>
			<hr>
			<?= $modes_array[ $mode ][ 'teksti' ] ?>
		</div>
	<?php endif;
	if ( $config[ 'update' ] ) : ?>
		<div class="warning">
			<?= $config[ 'update_txt' ] ?>
		</div>
	<?php endif;
	if ( $config[ 'indev' ] ) : ?>
		<div class="warning">
			<?= $config[ 'indev_txt' ] ?>
		</div>
	<?php endif; ?>
	<fieldset><legend>Sisäänkirjautuminen</legend>
		<form action="login_check.php" method="post" accept-charset="utf-8">
			<label>Sähköposti:
				<input type="email" name="email" placeholder="Nimi @ Email.com" pattern=".{8,255}$" id="login_email"
				       required autofocus disabled>
			</label>
			<br><br>
			<label>Salasana:
				<input type="password" name="password" placeholder="Salasana" pattern=".{5,255}$" required>
			</label>
			<br><br>
			<input type="hidden" name="mode" value="login">
			<input type="submit" value="Kirjaudu sisään" id="login_submit" disabled>
		</form>
	</fieldset>

	<fieldset><legend>Unohditko salasanasi?</legend>
		<form action="login_check.php" method="post" accept-charset="utf-8">
			<label>Sähköposti:
				<input type="email" name="email" placeholder="Nimi @ Email.com" pattern=".{3,255}$"
					   required autofocus>
			</label>
			<br><br>
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
	let update = <?= $config['update'] ?>;
	if ( !update ) {
		document.getElementById('login_email').removeAttribute('disabled');
		document.getElementById('login_submit').removeAttribute('disabled');
	}
	window.history.pushState('login', 'Title', 'index.php'); //Poistetaan GET URL:sta
</script>

</body>
</html>
