<?php
require 'luokat/dbyhteys.class.php';
session_start();

/**
 * Tarkistetaan onko avain validi ja onko se vanhentunut
 * @param DByhteys $db
 * @param string $reset_key_hash <p> Hajautettu reset-avain, jota verrataan tietokannassa olevaan.
 * @return object|false <p> Palauttaa joko löydetyn pw_reset objektin, tai
 * 		FALSE:n tapauksessa heittää takaisin kirjautumissivulle.
 */
function tarkista_pw_reset_key_ja_aika ( DByhteys $db, /*string*/ $reset_key_hash ) {
	$expiration_time = 1; //aika, jonka jälkeen avain vanhenee  (1 tunti)
	date_default_timezone_set('Europe/Helsinki'); //Ajan tarkistusta varten

	$sql = "SELECT kayttaja_id, reset_exp_aika, kaytetty
			FROM pw_reset
			WHERE reset_key_hash = ? ";
	$pw_reset_tiedot = $db->query( $sql, [$reset_key_hash] );

	if ( !$pw_reset_tiedot ) {
		header("location:index.php"); exit();
	}

	$time_then 	= new DateTime( $pw_reset_tiedot->reset_exp_aika ); // Muunnettuna DateTime-muotoon
	$time_now	= new DateTime( 'now' );
	$interval = $time_now->diff($time_then); //Kahden ajan välinen ero
	$difference = $interval->y + $interval->m + $interval->d + $interval->h; // Lasketaan aikojen erotus
	if ( $difference > ($expiration_time - 1) ) {
		header("location:index.php?redir=7"); exit();
	}

	return $pw_reset_tiedot;
}

/**
 * Haetaan käyttäjän perustiedot
 * @param DByhteys $db
 * @param stdClass $pw_reset
 * @return array|bool|stdClass
 */
function hae_kayttaja ( DByhteys $db, stdClass $pw_reset ) {
	$row = $db->query( "SELECT id, sahkoposti FROM kayttaja WHERE id = ? LIMIT 1",
		[$pw_reset->kayttaja_id] );

	if ( !$row ) { header("location:index.php?redir=98"); exit(); }

	return $row;
}

/**
 * Salasanan vaihtaminen
 * @param DByhteys $db
 * @param stdClass $user
 * @param string $uusi_salasana
 * @param string $reset_key
 */
function db_vaihda_salasana ( DByhteys $db, stdClass $user, /*string*/ $uusi_salasana, $reset_key ) {
	$hajautettu_uusi_salasana = password_hash($uusi_salasana, PASSWORD_DEFAULT);
	$sql = "UPDATE kayttaja SET salasana_hajautus = ?, salasana_vaihdettu=NOW(), salasana_uusittava = 0
			WHERE id = ?";
	$db->query( $sql, [$hajautettu_uusi_salasana, $user->id] );

	//Merkataan avain käytetyksi
	$sql = "UPDATE pw_reset SET kaytetty = 1 WHERE kayttaja_id = ? AND reset_key_hash = ?";
	$db->query( $sql, [$user->id, $reset_key] );
}

if ( empty($_GET['id']) ) {
	if ( empty($_SESSION['id']) ) {
		header("location:etusivu.php"); exit();
	} else header("location:index.php"); exit();
}

$db = new DByhteys();
$error = FALSE;
$reset_id_hash = sha1( $_GET['id'] );
$pw_reset = tarkista_pw_reset_key_ja_aika( $db, $reset_id_hash ); // Tässä kohtaa heitetään ulos, jos FALSE.
$reset_user = hae_kayttaja( $db, $pw_reset );

if ( !empty($_POST['reset']) ) {
	if ( $_POST['new_password'] === $_POST['confirm_new_password'] ) {
		db_vaihda_salasana( $db, $reset_user, $_POST['new_password'], $reset_id_hash );
		header("location:index.php?redir=8"); exit;
	} else {
		$error = TRUE;
	}
}

/** Tarkistetaan feedback, ja estetään formin uudelleenlähetys */
if ( !empty($_POST) ) { //Estetään formin uudelleenlähetyksen
	header("Location: " . $_SERVER['REQUEST_URI']); exit();
} else {
	$feedback = isset($_SESSION['feedback']) ? $_SESSION['feedback'] : "";
	unset($_SESSION["feedback"]);
}
?>
<!DOCTYPE html>
<html>
<head>
	<link rel="stylesheet" type="text/css" href="css/login_styles.css">
	<meta charset="UTF-8">
	<title>Password reset</title>
    <script src="https://code.jquery.com/jquery-3.1.1.min.js"></script>
    <link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
	<style>
		.info {
			position: relative;
			display: block;
			padding: 10pt;
			margin: 1em;
			width: auto;
			font-weight: bold;
			background-color: lightsteelblue;
		}
	</style>
</head>
<body>

<main class="login_container">
	<img src="img/osax_logo.jpg" alt="Osax.fi">

	<?= $feedback ?>

	<?php if ( $error ) : ?>
		<fieldset id=error><legend> Salasana ja varmistus eivät täsmää </legend>
			<p>Kokeile uudestaan. Varmista, että antamasi salasana ja varmistus täsmäävät.</p>
		</fieldset>
	<?php endif; ?>

	<fieldset><legend> Salasanan vaihto </legend>
		<form action="pw_reset.php?id=<?=$_GET['id']?>" method="post" accept-charset="utf-8">
			<?= $reset_user->sahkoposti // Muistutuksena käyttajalle ?>
			<br><br>
			<label for="uusi_salasana">Uusi salasana</label>
			<input type="password" name="new_password" id="uusi_salasana" pattern=".{6,}"
				   title="Pituus min 6 merkkiä." placeholder="Uusi salasana" required autofocus>
			<br><br>
			<label for="vahv_uusi_salasana">Vahvista uusi salasana</label>
			<input type="password" name="confirm_new_password" id="vahv_uusi_salasana" pattern=".{6,}"
				   title="Pituus min 6 merkkiä." placeholder="vahvista uusi salasana" required autofocus>
			<span id="check"></span>
			<br><br>
			<input type="hidden" name="reset" value="password_reset">
			<input type="submit" value="Vaihda salasana" id="pw_submit">
		</form>
	</fieldset>
</main>

<script>
	$(document).ready(function() {
		let pwSubmit = $('#pw_submit'); // Salasanan pituuden ja vahvistuksen tarkistusta varten
		let newPassword = $('#uusi_salasana'); // Ditto
		let pwCheck = $('#check'); // Ditto

		/** Salasanojen tarkastus reaaliajassa */
		$('#uusi_salasana, #vahv_uusi_salasana').on('keyup', function () {
			pwSubmit.prop('disabled', true);
			if ( newPassword.val().length >= 8 ) {
				if ( newPassword.val() === $('#vahv_uusi_salasana').val() ) {
					pwCheck.html('<i class="material-icons">done</i>Salasana OK.').css('color', 'green');
					pwSubmit.prop('disabled', false);
				} else {
					pwCheck.html('<i class="material-icons">warning</i>Salasanat eivät täsmää.').css('color', 'red');
				}
			} else {
				pwCheck.html('<i class="material-icons">warning</i>Salasanat min. pituus on 8 merkkiä.')
					.css('color', 'red');
			}
		});
	}
</script>

</body>
</html>
