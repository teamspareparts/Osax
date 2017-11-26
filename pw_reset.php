<?php declare(strict_types=1);
require 'luokat/dbyhteys.class.php';
session_start();


/**
 * Palautetaan löytyneet tiedot salasanan uusimisesta avaimen perusteella.
 * @param DByhteys $db
 * @param string $reset_key_hash <p> Hajautettu reset-avain, jota verrataan tietokannassa olevaan.
 * @return array|bool|stdClass <p> Palauttaa löydetyn pw_reset objektin tai false
 */
function get_pw_reset_tiedot( DByhteys $db, string $reset_key_hash ) {
	$sql = "SELECT kayttaja_id, reset_exp_aika, kaytetty
			FROM pw_reset
			WHERE reset_key_hash = ? AND kaytetty = 0
			LIMIT 1";
	return $db->query( $sql, [$reset_key_hash], false, PDO::FETCH_OBJ);
}

/**
 * Tarkistetaan onko avain vanhentunut.
 * @param string $expiration_time
 * @return bool
 */
function tarkista_pw_reset_aika ( string $expiration_time ) : bool {
	$expiration_hours = 1; //aika, jonka jälkeen avain vanhenee  (1 tunti)
	date_default_timezone_set('Europe/Helsinki'); //Ajan tarkistusta varten

	$time_then 	= new DateTime( $expiration_time ); // Muunnettuna DateTime-muotoon
	$time_now	= new DateTime( 'now' );
	$interval = $time_now->diff($time_then); //Kahden ajan välinen ero
	$difference = $interval->y + $interval->m + $interval->d + $interval->h; // Lasketaan aikojen erotus
	if ( $difference >= $expiration_hours ) {
		return false;
	}

	return true;
}

/**
 * Haetaan käyttäjän perustiedot
 * @param DByhteys $db
 * @param int $kayttaja_id
 * @return array|bool|stdClass
 */
function hae_kayttaja ( DByhteys $db, int $kayttaja_id ) {
	$sql = "SELECT id, sahkoposti FROM kayttaja WHERE id = ? LIMIT 1";
	return $db->query( $sql, [$kayttaja_id], false, PDO::FETCH_OBJ);
}

/**
 * Salasanan vaihtaminen
 * @param DByhteys $db
 * @param stdClass $user
 * @param string $uusi_salasana
 * @param string $reset_key
 */
function db_vaihda_salasana ( DByhteys $db, stdClass $user, string $uusi_salasana, string $reset_key ) {
	$hajautettu_uusi_salasana = password_hash($uusi_salasana, PASSWORD_DEFAULT);
	//Merkataan salasana vaihdetuksi ja vaihdetaan salasana
	$sql = "UPDATE kayttaja SET salasana_hajautus = ?, salasana_vaihdettu=NOW(), salasana_uusittava = 0 WHERE id = ?";
	$result1 = $db->query( $sql, [$hajautettu_uusi_salasana, $user->id] );
	//Merkataan avain käytetyksi
	$sql = "UPDATE pw_reset SET kaytetty = 1 WHERE kayttaja_id = ? AND reset_key_hash = ?";
	$result2 = $db->query( $sql, [$user->id, $reset_key] );
	if ( !$result1 || !$result2 ) {
		return false;
	}
	return true;
}

if ( empty($_GET['id']) ) {
	empty($_SESSION['id']) ? header("location:etusivu.php") : header("location:index.php");
	exit();
}

$db = new DByhteys();
$reset_id_hash = sha1( $_GET['id'] );
$pw_reset = get_pw_reset_tiedot($db, $reset_id_hash);
if ( !$pw_reset ) {
	header("location:index.php"); exit;
}
if ( !tarkista_pw_reset_aika($pw_reset->reset_exp_aika) ) {
	header("location:index.php?redir=7"); exit();
}
$reset_user = hae_kayttaja($db, $pw_reset->kayttaja_id);
if ( !$reset_user ) {
	header("location:index.php?redir=98"); exit();
}

if ( !empty($_POST['reset']) ) {
	if ( $_POST['new_password'] !== $_POST['confirm_new_password'] ) {
		$_SESSION['feedback'] = "<p class='error'>Salasana ja varmistus eivät täsmää.</p>";
	}
	elseif ( strlen($_POST['new_password']) < 8 ) {
		$_SESSION['feedback'] = "<p class='error'>Salasanan pitää olla vähintään kahdeksan merkkiä pitkä.</p>";
	}
	else {
		db_vaihda_salasana( $db, $reset_user, $_POST['new_password'], $reset_id_hash );
		header("location:index.php?redir=8");
		exit;
	}
}

/** Tarkistetaan feedback, ja estetään formin uudelleenlähetys */
if ( !empty($_POST) ) { //Estetään formin uudelleenlähetyksen
	header("Location: " . $_SERVER['REQUEST_URI']);
	exit();
} else {
	$feedback = isset($_SESSION['feedback']) ? $_SESSION['feedback'] : "";
	unset($_SESSION["feedback"]);
}
?>
<!DOCTYPE html>
<html>
<head>
	<meta charset="UTF-8">
	<title>Password reset</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
	<link rel="stylesheet" type="text/css" href="css/login_styles.css">
	<script src="https://code.jquery.com/jquery-3.1.1.min.js"></script>
</head>
<body>

<main class="login_container">
	<img src="img/osax_logo.jpg" alt="Osax.fi">

	<?= $feedback ?>

	<fieldset><legend> Salasanan vaihto </legend>
		<form action="pw_reset.php?id=<?=$_GET['id']?>" method="post" accept-charset="utf-8">
			<?= $reset_user->sahkoposti // Muistutuksena käyttajalle ?>
			<br><br>
			<label for="uusi_salasana">Uusi salasana</label>
			<input type="password" name="new_password" id="uusi_salasana" pattern=".{8,}"
				   title="Pituus min 8 merkkiä." placeholder="Uusi salasana" required autofocus>
			<br><br>
			<label for="vahv_uusi_salasana">Vahvista uusi salasana</label>
			<input type="password" name="confirm_new_password" id="vahv_uusi_salasana" pattern=".{8,}"
				   title="Pituus min 8 merkkiä." placeholder="vahvista uusi salasana" required>
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
