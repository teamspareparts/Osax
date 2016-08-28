<!DOCTYPE html>
<html>
<head>
	<link rel="stylesheet" type="text/css" href="css/login_styles.css">
	<meta charset="UTF-8">
	<title>Password reset</title>
	<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.1.0/jquery.min.js"></script>
	<link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
</head>
<body>
<?php require 'tietokanta.php';

/**
 * @param DByhteys $db
 * @param string $reset_key_hash <p> Hajautettu reset-avain, jota verrataan tietokannassa olevaan.
 * @return object|false <p> Palauttaa joko löydetyn pw_reset objektin, tai
 * 		FALSE:n tapauksessa heittää takaisin kirjautumissivulle.
 */
function tarkista_pw_reset_key_ja_aika ( DByhteys $db, /*string*/ $reset_key_hash ) {
	$sql_q = "	SELECT	kayttaja_id, reset_exp_aika, kaytetty
				FROM	pw_reset
				WHERE	reset_key_hash = ? ";
	$pw_reset = $db->query( $sql_q, [$reset_key_hash], NULL, PDO::FETCH_OBJ );

	if ( !$pw_reset ) { header("location:index.php"); exit(); }
	//OLETETAAN ETTÄ MYSQL TIMEZONE ON TALLENNETTU SUOMEN AIKAAN
	$time_then 	= new DateTime( $pw_reset->reset_exp_aika ); // Muunnettuna DateTime-muotoon
	$time_now	= new DateTime( 'now' );
	$interval = $time_now->diff($time_then); //Kahden ajan välinen ero

	$difference = $interval->y + $interval->m + $interval->d + $interval->h; // Lasketaan aikojen erotus

	//TODO: Tässä oli alunperin 4. Oliko se tarkoituksellista?
	/**
	 * Default aika, jonka jälkeen avain vanhenee: 1 tunti
	 * HUOM! Allaolevaa lukua pitää muutta tietokannan asetusten mukaan
	 */
	if ( $difference > 0 ) { header("location:index.php?redir=7"); exit(); }

	return $pw_reset;
}


/**
 * @param DByhteys $db
 * @param stdClass $pw_reset
 * @return array|bool|stdClass
 */
function hae_kayttaja ( DByhteys $db, stdClass $pw_reset ) {
	$sql_q = "	SELECT	id, sahkoposti
				FROM	kayttaja
				WHERE	id = ? ";
	$row = $db->query( $sql_q, [$pw_reset->kayttaja_id], NULL, PDO::FETCH_OBJ );

	if ( !$row ) { header("location:index.php?redir=98"); exit(); }

	return $row;
}

/**
 * @param DByhteys $db
 * @param stdClass $user
 * @param string $uusi_salasana
 * @param string $reset_key
 */
function db_vaihda_salasana ( DByhteys $db, stdClass $user, /*string*/ $uusi_salasana, $reset_key ) {
	$hajautettu_uusi_salasana = password_hash($uusi_salasana, PASSWORD_DEFAULT);

	$query = "	UPDATE	kayttaja 
				SET 	salasana_hajautus = ?, salasana_vaihdettu=NOW(), salasana_uusittava = 0
				WHERE	sahkoposti = ? ";
	$db->query( $query, [$hajautettu_uusi_salasana, $user->kayttaja_id] );

	$query = "UPDATE pw_reset SET kaytetty = 1 WHERE kayttaja_id = ? AND reset_key_hash = ?";
	$db->query( $query, [$user->kayttaja_id, $reset_key] );
}

if ( empty($_GET['id']) ) {
	header("location:index.php"); exit();
}
date_default_timezone_set('Europe/Helsinki'); //Ajan tarkistusta varten
$error = FALSE;
$reset_id_hash = sha1( $_GET['id'] );
$pw_reset = tarkista_pw_reset_key_ja_aika( $db, $reset_id_hash ); // Tässä kohtaa heitetään ulos, jos FALSE.
$user = hae_kayttaja( $db, $pw_reset );

if ( !empty($_POST['reset']) ) {
	if ( $_POST['new_password'] === $_POST['confirm_new_password'] ) {
		db_vaihda_salasana( $db, $user, $_POST['new_password'], $reset_id_hash );
		header("location:index.php?redir=8"); exit;
	} else {
		$error = TRUE;
	}
}
?>
<main class="login_container">
	<img src="img/osax-Logo.jpg" alt="Osax.fi">

	<?php if ( $error ) : ?>
		<fieldset id=error><legend> Salasana ja varmistus eivät täsmää </legend>
			<p>Kokeile uudestaan. Varmista, että antamasi salasana ja varmistus täsmäävät.</p>
		</fieldset>
	<?php endif; ?>

	<fieldset><legend> Salasanan vaihto </legend>
		<form action="pw_reset.php?id=<?=$_GET['id']?>" method="post" accept-charset="utf-8">
			<?= $user->sahkoposti // Muistutuksena kauttajalle ?>
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
	/** Salasanojen tarkastus reaaliajassa */
	$('#uusi_salasana, #vahv_uusi_salasana').on('keyup', function () {
		if ( $('#uusi_salasana').val() == $('#vahv_uusi_salasana').val() ) {
			$('#check').html('<i class="material-icons">done</i>').css('color', 'green');
		} else {
			$('#check').html('<i class="material-icons">warning</i>Salasanat eivät täsmää').css('color', 'red'); }
		$('#pw_submit').prop('disabled', true);
	});
</script>

</body>
</html>
